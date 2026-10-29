<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Models\SecurityLog;
use App\Services\SecurityMonitorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSecurityController extends Controller
{
    protected SecurityMonitorService $securityMonitor;

    public function __construct(SecurityMonitorService $securityMonitor)
    {
        $this->securityMonitor = $securityMonitor;
    }

    public function index(): View
    {
        $stats = $this->securityMonitor->getDashboardStats();
        return view('admin.security.index', compact('stats'));
    }

    public function blockIp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ip_address' => 'required|string|max:45',
            'reason' => 'required|string|max:255',
            'hours' => 'nullable|integer|min:1|max:8760',
            'is_permanent' => 'nullable|boolean',
        ], [
            'ip_address.required' => 'يرجى إدخال عنوان IP',
            'reason.required' => 'يرجى توضيح سبب الحظر',
        ]);

        $this->securityMonitor->blockIp(
            ip: $validated['ip_address'],
            reason: $validated['reason'],
            hours: !empty($validated['hours']) ? (int)$validated['hours'] : null,
            isPermanent: !empty($validated['is_permanent']),
            blockedBy: auth()->user()->name . ' (مدير)'
        );

        return back()->with('success', "تم حظر عنوان IP {$validated['ip_address']} بنجاح وإدراجه في جدار الحماية.");
    }

    public function unblockIp(string $ip): RedirectResponse
    {
        $unblocked = $this->securityMonitor->unblockIp($ip);

        if ($unblocked) {
            return back()->with('success', "تم فك الحظر عن عنوان IP: {$ip} بنجاح.");
        }

        return back()->with('error', "عنوان IP غير موجود في قائمة الحظر.");
    }

    public function clearOldLogs(): RedirectResponse
    {
        $deleted = SecurityLog::where('created_at', '<', now()->subDays(30))->delete();
        return back()->with('success', "تم تنظيف {$deleted} من سجلات الأمان القديمة.");
    }
}

