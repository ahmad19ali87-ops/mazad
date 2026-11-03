<?php

namespace App\Console\Commands;

use App\Models\BlockedIp;
use App\Services\SecurityMonitorService;
use Illuminate\Console\Command;

class SecurityUnblockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:unblock {ip? : The IP address to unblock (or "all" to unblock all)} {--list : List all currently blocked IPs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unblock an IP address from the security firewall or list blocked IPs';

    /**
     * Execute the console command.
     */
    public function handle(SecurityMonitorService $securityMonitor): int
    {
        if ($this->option('list')) {
            $blocked = BlockedIp::all();
            if ($blocked->isEmpty()) {
                $this->info('قائمة الحظر فارغة حالياً. لا توجد أي عناوين IP محظورة.');
                return 0;
            }

            $this->table(
                ['IP Address', 'Reason', 'Blocked By', 'Blocked Until', 'Permanent'],
                $blocked->map(fn($item) => [
                    $item->ip_address,
                    $item->reason,
                    $item->blocked_by,
                    $item->blocked_until ? $item->blocked_until->format('Y-m-d H:i') : 'N/A',
                    $item->is_permanent ? 'Yes' : 'No',
                ])
            );
            return 0;
        }

        $ip = $this->argument('ip');

        if (!$ip || strtolower($ip) === 'all') {
            $count = BlockedIp::count();
            BlockedIp::truncate();
            $this->info("تم فك الحظر عن كافة عناوين IP بنجاح ({$count} عنوان تم تحريره).");
            return 0;
        }

        $unblocked = $securityMonitor->unblockIp($ip);
        if ($unblocked) {
            $this->info("تم فك الحظر عن عنوان IP بنجاح: {$ip}");
        } else {
            $this->warn("عنوان IP ({$ip}) غير موجود في قائمة الحظر.");
        }

        return 0;
    }
}

