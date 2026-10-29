<?php

namespace App\Services;

use App\Models\BlockedIp;
use App\Models\LoginAttempt;
use App\Models\SecurityLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SecurityMonitorService
{
    /**
     * Check whether an IP address is currently blocked
     */
    public function isIpBlocked(string $ip): bool
    {
        // Short cache to avoid querying database on every static asset
        $cacheKey = "ip_blocked_{$ip}";
        if (Cache::has($cacheKey)) {
            return (bool) Cache::get($cacheKey);
        }

        $blocked = BlockedIp::where('ip_address', $ip)->first();
        if (!$blocked) {
            Cache::put($cacheKey, false, 60);
            return false;
        }

        $isBlocked = $blocked->isBlockedNow();
        if (!$isBlocked) {
            // Expired block
            $blocked->delete();
            Cache::put($cacheKey, false, 60);
            return false;
        }

        Cache::put($cacheKey, true, 60);
        return true;
    }

    /**
     * Record a login attempt and auto-ban if brute-forcing detected
     */
    public function recordLoginAttempt(string $ip, ?string $email, bool $isSuccess, ?string $userAgent = null): void
    {
        LoginAttempt::create([
            'ip_address' => $ip,
            'email' => $email,
            'is_successful' => $isSuccess,
            'user_agent' => substr($userAgent ?? '', 0, 255),
            'attempted_at' => now(),
        ]);

        if ($isSuccess) {
            // Clear recent failed attempts counter on success
            Cache::forget("failed_logins_{$ip}");
            return;
        }

        // Count failed attempts from this IP in the last 15 minutes
        $since = now()->subMinutes(15);
        $failedCount = LoginAttempt::where('ip_address', $ip)
            ->where('is_successful', false)
            ->where('attempted_at', '>=', $since)
            ->count();

        // Threshold: 5 failed attempts within 15 minutes -> Automatic 24-hour ban
        if ($failedCount >= 5) {
            $this->blockIp(
                ip: $ip,
                reason: "حظر تلقائي: تم رصد {$failedCount} محاولات دخول فاشلة خلال 15 دقيقة (هجوم تخمين Brute-Force)",
                hours: 24,
                isPermanent: false,
                blockedBy: 'نظام الحماية الذاتي (WAF)'
            );

            $this->logSecurityEvent(
                ip: $ip,
                eventType: 'brute_force',
                severity: 'critical',
                url: request()->fullUrl(),
                method: request()->method(),
                userAgent: $userAgent,
                payload: json_encode(['email' => $email, 'attempts' => $failedCount], JSON_UNESCAPED_UNICODE),
                actionTaken: 'blocked'
            );
        }
    }

    /**
     * Inspect incoming HTTP request for malicious payloads (SQLi, XSS, Scanner, Path Traversal)
     */
    public function inspectRequest(Request $request): ?string
    {
        $uri = $request->getRequestUri();
        $payload = json_encode($request->all(), JSON_UNESCAPED_UNICODE);
        $userAgent = $request->userAgent() ?? '';
        $ip = $request->ip();

        // 1. Scanner probes
        $scannerPatterns = [
            '/\.(env|git|svn|hg|bzr)/i',
            '/(wp-admin|wp-login|xmlrpc\.php)/i',
            '/(phpmyadmin|pma|adminer|sqlweb)/i',
            '/(eval-stdin\.php|setup\.php|install\.php)/i',
            '/(cgi-bin|solr|actuator|api-docs)/i',
        ];
        foreach ($scannerPatterns as $pattern) {
            if (preg_match($pattern, $uri)) {
                $this->handleDetectedAttack($ip, 'scanner_probe', 'high', $request, 'محاولة فحص ملفات حساسة ومسارات محظورة');
                return 'scanner_probe';
            }
        }

        // 2. Path Traversal
        if (preg_match('/(\.\.[\/\\\\])+/i', $uri) || preg_match('/(\.\.[\/\\\\])+/i', $payload)) {
            $this->handleDetectedAttack($ip, 'path_traversal', 'critical', $request, 'محاولة اختراق مسار الخادم (Directory Traversal)');
            return 'path_traversal';
        }

        // 3. SQL Injection patterns
        $sqliPatterns = [
            '/(\%27)|(\')|(\-\-)|(\%23)|(#)/i',
            '/\b(union(\s+all)?\s+select)\b/i',
            '/\b(select\s+.*\s+from\s+.*\b)/i',
            '/\b(drop\s+table|delete\s+from|insert\s+into|truncate\s+table)\b/i',
            '/\b(exec(\s|\+)+(s|x)p\w+)\b/i',
            '/\b(or\s+[\'"]?1[\'"]?\s*=\s*[\'"]?1)\b/i',
        ];
        foreach ($sqliPatterns as $pattern) {
            // Test query string and input keys/values
            if (preg_match($pattern, $payload) || preg_match($pattern, $uri)) {
                // Ignore legitimate rich text with quotes
                if (str_contains($payload, 'union') || str_contains($payload, 'drop table') || str_contains($uri, 'union select') || str_contains($uri, 'or 1=1')) {
                    $this->handleDetectedAttack($ip, 'sql_injection', 'critical', $request, 'رصد محاولة حقن استعلامات SQL مشبوهة');
                    return 'sql_injection';
                }
            }
        }

        // 4. XSS Injection patterns
        $xssPatterns = [
            '/<script\b[^>]*>(.*?)<\/script>/is',
            '/javascript\s*:\s*[^\s"\']+/i',
            '/(onload|onerror|onclick|onmouseover)\s*=/i',
        ];
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $uri) || preg_match($pattern, $payload)) {
                $this->handleDetectedAttack($ip, 'xss_attempt', 'high', $request, 'رصد محاولة حقن نصوص برمجية خبيثة (XSS)');
                return 'xss_attempt';
            }
        }

        return null;
    }

    /**
     * Handle detected attack and decide whether to ban immediately
     */
    protected function handleDetectedAttack(string $ip, string $eventType, string $severity, Request $request, string $reason): void
    {
        $counterKey = "attack_count_{$ip}";
        $attacksCount = Cache::increment($counterKey);
        Cache::put($counterKey, $attacksCount, 3600);

        $action = 'logged';

        // Critical attacks (SQLi, path traversal) or 3+ violations result in instant IP ban
        if ($severity === 'critical' || $attacksCount >= 3) {
            $action = 'blocked';
            $this->blockIp(
                ip: $ip,
                reason: "{$reason} (تكرار: {$attacksCount})",
                hours: 48,
                isPermanent: false,
                blockedBy: 'جدار الحماية التلقائي (Firewall WAF)'
            );
        }

        $this->logSecurityEvent(
            ip: $ip,
            eventType: $eventType,
            severity: $severity,
            url: $request->fullUrl(),
            method: $request->method(),
            userAgent: $request->userAgent(),
            payload: substr(json_encode($request->all(), JSON_UNESCAPED_UNICODE), 0, 1000),
            actionTaken: $action
        );
    }

    /**
     * Block an IP address manually or automatically
     */
    public function blockIp(string $ip, string $reason, ?int $hours = null, bool $isPermanent = false, string $blockedBy = 'مدير النظام'): BlockedIp
    {
        $blockedUntil = $hours ? Carbon::now()->addHours($hours) : null;
        if ($isPermanent) {
            $blockedUntil = null;
        }

        $existing = BlockedIp::where('ip_address', $ip)->first();
        $attempts = $existing ? ($existing->attempts_count + 1) : 1;

        $blocked = BlockedIp::updateOrCreate(
            ['ip_address' => $ip],
            [
                'reason' => $reason,
                'blocked_until' => $blockedUntil,
                'is_permanent' => $isPermanent,
                'blocked_by' => $blockedBy,
                'attempts_count' => $attempts,
            ]
        );

        Cache::put("ip_blocked_{$ip}", true, 3600);

        return $blocked;
    }

    /**
     * Unblock an IP address
     */
    public function unblockIp(string $ip): bool
    {
        Cache::forget("ip_blocked_{$ip}");
        Cache::forget("attack_count_{$ip}");
        Cache::forget("failed_logins_{$ip}");

        $deleted = BlockedIp::where('ip_address', $ip)->delete();

        if ($deleted) {
            $this->logSecurityEvent(
                ip: $ip,
                eventType: 'admin_unblock',
                severity: 'info',
                url: request()->fullUrl(),
                method: request()->method(),
                userAgent: request()->userAgent(),
                payload: json_encode(['unblocked_by' => auth()->user()?->name ?? 'Admin'], JSON_UNESCAPED_UNICODE),
                actionTaken: 'unblocked'
            );
        }

        return (bool) $deleted;
    }

    /**
     * Log a security event into database
     */
    public function logSecurityEvent(
        string $ip,
        string $eventType,
        string $severity = 'medium',
        ?string $url = null,
        string $method = 'GET',
        ?string $userAgent = null,
        ?string $payload = null,
        string $actionTaken = 'logged'
    ): SecurityLog {
        return SecurityLog::create([
            'ip_address' => $ip,
            'user_id' => auth()->id(),
            'event_type' => $eventType,
            'severity' => $severity,
            'url' => substr($url ?? '', 0, 500),
            'method' => $method,
            'user_agent' => substr($userAgent ?? '', 0, 350),
            'payload' => $payload,
            'action_taken' => $actionTaken,
        ]);
    }

    /**
     * Summary statistics for Admin Security Dashboard
     */
    public function getDashboardStats(): array
    {
        $today = Carbon::today();

        return [
            'active_blocks_count' => BlockedIp::count(),
            'attacks_today_count' => SecurityLog::where('created_at', '>=', $today)->count(),
            'failed_logins_today' => LoginAttempt::where('is_successful', false)->where('attempted_at', '>=', $today)->count(),
            'critical_events_count' => SecurityLog::where('severity', 'critical')->count(),
            'events_by_type' => SecurityLog::selectRaw('event_type, count(*) as total')
                ->groupBy('event_type')
                ->orderByDesc('total')
                ->pluck('total', 'event_type')
                ->toArray(),
            'recent_logs' => SecurityLog::with('user')->latest()->take(25)->get(),
            'blocked_ips' => BlockedIp::latest()->take(30)->get(),
        ];
    }
}
