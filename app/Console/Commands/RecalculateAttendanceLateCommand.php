<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Services\Attendance\AttendanceShiftRuleService;
use Illuminate\Console\Command;

class RecalculateAttendanceLateCommand extends Command
{
    protected $signature = 'attendance:recalculate-lates {--chunk=200 : Rows per chunk}';

    protected $description = 'Recompute late_minutes and late_incident from shift + grace for all attendance rows';

    public function handle(AttendanceShiftRuleService $rules): int
    {
        $chunk = max(50, (int) $this->option('chunk'));

        Attendance::query()
            ->with('employee')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use ($rules) {
                foreach ($rows as $row) {
                    if (! $row->employee) {
                        continue;
                    }
                    $rules->recalculateAttendance($row);
                }
            });

        $this->info('Done.');

        return self::SUCCESS;
    }
}
