<?php

namespace App\Services\Zkteco;

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\Attendance\AttendanceShiftRuleService;
use Carbon\Carbon;
use CodingLibs\ZktecoPhp\Libs\ZKTeco;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ZktecoAttendanceSyncService
{
    public function __construct(
        private readonly AttendanceShiftRuleService $shiftRules
    ) {}

    /**
     * Pull attendance log from the device and upsert daily rows (first punch = in, last = out when needed).
     *
     * @return array{synced:int, logs_read:int, unmapped_device_user_ids:array<int, int>, message?:string}
     */
    public function sync(): array
    {
        if (! extension_loaded('sockets')) {
            return [
                'synced' => 0,
                'logs_read' => 0,
                'unmapped_device_user_ids' => [],
                'message' => 'PHP sockets extension is required for ZKTeco (UDP). Enable extension=sockets in php.ini.',
            ];
        }

        if (! config('zkteco.enabled')) {
            return [
                'synced' => 0,
                'logs_read' => 0,
                'unmapped_device_user_ids' => [],
                'message' => 'ZKTeco is disabled (ZKTECO_ENABLED).',
            ];
        }

        $host = (string) config('zkteco.host');
        if ($host === '') {
            return [
                'synced' => 0,
                'logs_read' => 0,
                'unmapped_device_user_ids' => [],
                'message' => 'ZKTECO_HOST is empty.',
            ];
        }

        $port = (int) config('zkteco.port', 4370);
        $timeout = (int) config('zkteco.timeout', 25);
        $shouldPing = (bool) config('zkteco.should_ping', false);
        $password = (int) config('zkteco.password', 0);

        $zk = new ZKTeco($host, $port, $shouldPing, $timeout, $password);
        $logs = [];

        try {
            if (! $zk->connect()) {
                throw new \RuntimeException(
                    'Could not connect to ZKTeco at '.$host.':'.$port.' (UDP). Check LAN, device IP, and Windows firewall.'
                );
            }
            $logs = $zk->getAttendances();
        } catch (Throwable $e) {
            Log::error('ZKTeco sync: connection or read failed', ['exception' => $e]);

            throw $e;
        } finally {
            $zk->disconnect();
        }

        if (! is_array($logs)) {
            $logs = [];
        }

        $employeesByBadge = Employee::query()
            ->whereNotNull('zk_badge_user_id')
            ->get()
            ->keyBy('zk_badge_user_id');

        $grouped = [];
        foreach ($logs as $row) {
            if (! is_array($row)) {
                continue;
            }
            $userId = (int) ($row['user_id'] ?? 0);
            if ($userId < 1) {
                continue;
            }
            $recordTime = $row['record_time'] ?? '';
            if (! is_string($recordTime) || $recordTime === '') {
                continue;
            }
            try {
                $dt = Carbon::parse($recordTime);
            } catch (Throwable) {
                continue;
            }
            $dateKey = $dt->toDateString();
            $grouped[$userId][$dateKey][] = [
                'record_time' => $dt,
                'state' => (int) ($row['state'] ?? 0),
            ];
        }

        $unmapped = [];
        $synced = 0;

        DB::transaction(function () use ($grouped, $employeesByBadge, &$unmapped, &$synced) {
            foreach ($grouped as $deviceUserId => $byDate) {
                $employee = $employeesByBadge->get($deviceUserId);
                if (! $employee) {
                    $unmapped[$deviceUserId] = $deviceUserId;

                    continue;
                }

                foreach ($byDate as $dateStr => $punches) {
                    $collection = collect($punches);
                    [$checkIn, $checkOut] = $this->deriveCheckInOut($collection);

                    if (! $checkIn instanceof Carbon) {
                        continue;
                    }

                    $workMinutes = null;
                    if ($checkOut instanceof Carbon && $checkOut->gte($checkIn)) {
                        $workMinutes = (int) $checkIn->diffInMinutes($checkOut);
                    }

                    $data = [
                        'employee_id' => $employee->id,
                        'attendance_date' => $dateStr,
                        'check_in_at' => $checkIn,
                        'check_out_at' => $checkOut,
                        'status' => 'present',
                        'source' => 'biometric',
                        'early_leave_minutes' => 0,
                        'work_minutes' => $workMinutes,
                        'notes' => null,
                    ];

                    $this->shiftRules->applyLateRules($data, $employee);

                    Attendance::query()->updateOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'attendance_date' => $dateStr,
                        ],
                        [
                            'check_in_at' => $data['check_in_at'],
                            'check_out_at' => $data['check_out_at'],
                            'status' => $data['status'],
                            'source' => $data['source'],
                            'early_leave_minutes' => $data['early_leave_minutes'],
                            'work_minutes' => $data['work_minutes'],
                            'notes' => $data['notes'],
                            'late_minutes' => (int) ($data['late_minutes'] ?? 0),
                            'late_incident' => (bool) ($data['late_incident'] ?? false),
                        ]
                    );

                    $synced++;
                }
            }
        });

        return [
            'synced' => $synced,
            'logs_read' => count($logs),
            'unmapped_device_user_ids' => array_values($unmapped),
        ];
    }

    /**
     * @param  Collection<int, array{record_time: Carbon, state: int}>  $punches
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function deriveCheckInOut(Collection $punches): array
    {
        if ($punches->isEmpty()) {
            return [null, null];
        }

        $checkIns = $punches->filter(fn (array $p) => $p['state'] === 1);
        $checkOuts = $punches->filter(fn (array $p) => $p['state'] === 2);

        $firstPunch = Carbon::parse($punches->min('record_time'));
        $lastPunch = Carbon::parse($punches->max('record_time'));

        $checkIn = $checkIns->isNotEmpty()
            ? Carbon::parse($checkIns->min('record_time'))
            : $firstPunch;

        $checkOut = null;
        if ($checkOuts->isNotEmpty()) {
            $checkOut = Carbon::parse($checkOuts->max('record_time'));
        } elseif ($punches->count() > 1 && $lastPunch->gt($checkIn)) {
            $checkOut = $lastPunch;
        }

        return [$checkIn, $checkOut];
    }
}
