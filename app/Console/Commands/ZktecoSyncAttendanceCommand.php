<?php

namespace App\Console\Commands;

use App\Services\Zkteco\ZktecoAttendanceSyncService;
use Illuminate\Console\Command;
use Throwable;

class ZktecoSyncAttendanceCommand extends Command
{
    protected $signature = 'zkteco:sync-attendance';

    protected $description = 'Read ZKTeco attendance log (UDP) and upsert daily check-in/out for mapped employees';

    public function handle(ZktecoAttendanceSyncService $sync): int
    {
        try {
            $result = $sync->sync();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (! empty($result['message'])) {
            $this->warn($result['message']);
        }

        $this->info('Log rows read from device: '.($result['logs_read'] ?? 0));
        $this->info('Attendance day rows upserted: '.($result['synced'] ?? 0));

        $unmapped = $result['unmapped_device_user_ids'] ?? [];
        if ($unmapped !== []) {
            $this->warn('Device user IDs with no employee mapping (set zk_badge_user_id): '.implode(', ', $unmapped));
        }

        return self::SUCCESS;
    }
}
