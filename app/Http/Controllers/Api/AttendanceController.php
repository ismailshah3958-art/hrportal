<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\ActivityNotification;
use App\Services\Attendance\AttendanceShiftRuleService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceShiftRuleService $shiftRules
    ) {}

    /**
     * All attendance rows for one employee within a calendar month (YYYY-MM).
     */
    public function forEmployee(Request $request, Employee $employee)
    {
        $user = $request->user();
        $canHr = $user?->can('hr.attendance.manage') ?? false;
        $canSelf = $user?->can('ess.attendance.view')
            && $user->employee
            && (int) $user->employee->id === (int) $employee->id;

        abort_unless($canHr || $canSelf, 403);

        $month = $request->string('month')->trim()->toString();
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $rows = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('attendance_date')
            ->orderBy('id')
            ->get();

        return response()->json([
            'month' => $month,
            'range_from' => $start->toDateString(),
            'range_to' => $end->toDateString(),
            'employee' => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'full_name' => $employee->full_name,
            ],
            'data' => AttendanceResource::collection($rows)->resolve(),
        ]);
    }

    /**
     * Dashboard spotlight: best attendance + users needing attention.
     */
    public function spotlight(Request $request)
    {
        abort_unless($request->user()?->can('hr.dashboard.view'), 403);

        $month = $request->string('month')->trim()->toString();
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $summaryRows = Attendance::query()
            ->selectRaw('employee_id')
            ->selectRaw('COUNT(*) as attendance_days')
            ->selectRaw('SUM(CASE WHEN COALESCE(late_minutes, 0) > 0 OR COALESCE(late_incident, 0) = 1 THEN 1 ELSE 0 END) as late_days')
            ->selectRaw("SUM(CASE WHEN status = 'on_leave' THEN 1 ELSE 0 END) as leave_days")
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('employee_id')
            ->having('attendance_days', '>=', 3)
            ->get();

        if ($summaryRows->isEmpty()) {
            return response()->json([
                'month' => $month,
                'best' => [],
                'needs_attention' => [],
            ]);
        }

        $employeeIds = $summaryRows->pluck('employee_id')->all();
        $employees = Employee::query()
            ->whereIn('id', $employeeIds)
            ->where('status', 'active')
            ->get()
            ->keyBy('id');

        $rows = $summaryRows
            ->filter(fn ($r) => $employees->has((int) $r->employee_id))
            ->values();

        $best = $this->buildBestAttendance($rows, $employees);
        $needsAttention = $this->buildNeedsAttention($rows, $employees);

        return response()->json([
            'month' => $month,
            'best' => $best,
            'needs_attention' => $needsAttention,
        ]);
    }

    public function store(StoreAttendanceRequest $request)
    {
        $data = $request->validated();
        $data['source'] = $data['source'] ?? 'manual';

        $this->syncWorkMinutes($data);

        $employee = Employee::query()->findOrFail($data['employee_id']);
        $this->shiftRules->applyLateRules($data, $employee);

        try {
            $attendance = Attendance::create($data);
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw ValidationException::withMessages([
                    'employee_id' => ['This employee already has a row for that date. Edit the existing record instead.'],
                ]);
            }
            throw $e;
        }

        $attendance->loadMissing('employee.user');
        $actorId = $request->user()?->id;
        $recipients = User::query()->role(['admin', 'hr'])->get()->reject(fn ($u) => (int) $u->id === (int) $actorId);
        if ($attendance->employee?->user_id) {
            $recipients->push($attendance->employee->user);
        }
        $recipients = $recipients->unique('id')->values();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ActivityNotification([
                'title' => 'Attendance added',
                'body' => ($attendance->employee?->full_name ?? 'Employee').' attendance added for '.$attendance->attendance_date?->format('Y-m-d').'.',
                'link' => '/attendance/'.($attendance->employee_id ?? ''),
                'meta' => ['attendance_id' => $attendance->id],
            ]));
        }

        return (new AttendanceResource(
            $attendance->fresh()->load('employee')
        ))->response()->setStatusCode(201);
    }

    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        abort_unless($request->user()?->can('hr.attendance.manage'), 403);

        $attendance->loadMissing('employee');

        $defaults = [
            'check_in_at' => $attendance->check_in_at,
            'check_out_at' => $attendance->check_out_at,
            'late_minutes' => $attendance->late_minutes,
            'early_leave_minutes' => $attendance->early_leave_minutes,
            'work_minutes' => $attendance->work_minutes,
            'status' => $attendance->status,
            'source' => $attendance->source,
            'notes' => $attendance->notes,
        ];

        $data = array_merge($defaults, $request->validated());
        $data['source'] = $data['source'] ?? 'manual';

        $this->syncWorkMinutes($data);

        $this->shiftRules->applyLateRules($data, $attendance->employee);

        $attendance->update($data);

        $attendance->loadMissing('employee.user');
        $actorId = $request->user()?->id;
        $recipients = User::query()->role(['admin', 'hr'])->get()->reject(fn ($u) => (int) $u->id === (int) $actorId);
        if ($attendance->employee?->user_id) {
            $recipients->push($attendance->employee->user);
        }
        $recipients = $recipients->unique('id')->values();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ActivityNotification([
                'title' => 'Attendance updated',
                'body' => ($attendance->employee?->full_name ?? 'Employee').' attendance updated for '.$attendance->attendance_date?->format('Y-m-d').'.',
                'link' => '/attendance/'.($attendance->employee_id ?? ''),
                'meta' => ['attendance_id' => $attendance->id],
            ]));
        }

        return new AttendanceResource($attendance->fresh()->load('employee'));
    }

    public function destroy(Request $request, Attendance $attendance)
    {
        abort_unless($request->user()?->can('hr.attendance.manage'), 403);

        $attendance->loadMissing('employee.user');
        $employeeName = $attendance->employee?->full_name ?? 'Employee';
        $employeeId = $attendance->employee_id;
        $date = $attendance->attendance_date?->format('Y-m-d');
        $attendance->delete();

        $actorId = $request->user()?->id;
        $recipients = User::query()->role(['admin', 'hr'])->get()->reject(fn ($u) => (int) $u->id === (int) $actorId);
        if ($attendance->employee?->user_id) {
            $recipients->push($attendance->employee->user);
        }
        $recipients = $recipients->unique('id')->values();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ActivityNotification([
                'title' => 'Attendance deleted',
                'body' => $employeeName.' attendance deleted for '.$date.'.',
                'link' => '/attendance/'.$employeeId,
            ]));
        }

        return response()->json(null, 204);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncWorkMinutes(array &$data): void
    {
        $checkIn = $data['check_in_at'] ?? null;
        $checkOut = $data['check_out_at'] ?? null;

        if ($checkIn && $checkOut) {
            $ci = Carbon::parse($checkIn);
            $co = Carbon::parse($checkOut);
            if ($co->lt($ci)) {
                throw ValidationException::withMessages([
                    'check_out_at' => ['Check-out must be on or after check-in.'],
                ]);
            }
            $data['work_minutes'] = (int) $ci->diffInMinutes($co);

            return;
        }

        if (array_key_exists('check_in_at', $data) || array_key_exists('check_out_at', $data)) {
            $data['work_minutes'] = null;
        }
    }

    private function buildBestAttendance(Collection $rows, Collection $employees): array
    {
        return $rows
            ->sortBy([
                fn ($r) => (int) $r->late_days,
                fn ($r) => (int) $r->leave_days,
                fn ($r) => -1 * (int) $r->attendance_days,
            ])
            ->take(5)
            ->map(function ($r) use ($employees) {
                $emp = $employees->get((int) $r->employee_id);

                return [
                    'employee_id' => $emp->id,
                    'employee_code' => $emp->employee_code,
                    'full_name' => $emp->full_name,
                    'profile_photo_url' => $emp->profilePhotoUrl(),
                    'attendance_days' => (int) $r->attendance_days,
                    'late_days' => (int) $r->late_days,
                    'leave_days' => (int) $r->leave_days,
                ];
            })
            ->values()
            ->all();
    }

    private function buildNeedsAttention(Collection $rows, Collection $employees): array
    {
        return $rows
            ->sortByDesc(function ($r) {
                $late = (int) $r->late_days;
                $leave = (int) $r->leave_days;

                return ($late * 2) + $leave;
            })
            ->take(5)
            ->map(function ($r) use ($employees) {
                $emp = $employees->get((int) $r->employee_id);

                return [
                    'employee_id' => $emp->id,
                    'employee_code' => $emp->employee_code,
                    'full_name' => $emp->full_name,
                    'profile_photo_url' => $emp->profilePhotoUrl(),
                    'attendance_days' => (int) $r->attendance_days,
                    'late_days' => (int) $r->late_days,
                    'leave_days' => (int) $r->leave_days,
                ];
            })
            ->values()
            ->all();
    }
}
