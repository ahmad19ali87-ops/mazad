<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use App\Services\SecurityMonitorService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityFirewallMiddleware
{
    protected SecurityMonitorService $securityMonitor;

    public function __construct(SecurityMonitorService $securityMonitor)
    {
        $this->securityMonitor = $securityMonitor;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // 1. Check if IP is currently blocked
        if ($this->securityMonitor->isIpBlocked($ip)) {
            $blocked = BlockedIp::where('ip_address', $ip)->first();
            $reason = $blocked ? $blocked->reason : 'حظر أمني بسبب مخالفة معايير الاستخدام';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => 'IP_BLOCKED',
                    'message' => 'تم حظر عنوان IP الخاص بك لدواعي الأمان والحماية.',
                    'details' => $reason,
                ], 403);
            }

            return response()->view('errors.blocked', [
                'ip' => $ip,
                'reason' => $reason,
                'blocked_until' => $blocked?->blocked_until?->format('Y-m-d H:i') ?? 'حظر دائم حتى إشعار آخر',
            ], 403);
        }

        // 2. Real-time attack inspection (SQLi, XSS, Scanner, Traversal)
        // Skip inspection on safe admin and safe asset routes
        if (!$request->is('admin/*') && !$request->is('build/*') && !$request->is('storage/*')) {
            $attack = $this->securityMonitor->inspectRequest($request);
            if ($attack && $this->securityMonitor->isIpBlocked($ip)) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'error' => 'ATTACK_PREVENTED',
                        'message' => 'تم رصد هجوم مشبوه وتم حظر اتصالك فورياً.',
                    ], 403);
                }

                return response()->view('errors.blocked', [
                    'ip' => $ip,
                    'reason' => 'تم رصد هجوم إلكتروني مشبوه وحظر الوصول حمايةً للنظام',
                    'blocked_until' => 'حظر أمني فوري',
                ], 403);
            }
        }

        return $next($request);
    }
}

