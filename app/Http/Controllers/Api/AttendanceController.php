<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\Attendance\AttendanceShiftRuleService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
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

        return new AttendanceResource($attendance->fresh()->load('employee'));
    }

    public function destroy(Request $request, Attendance $attendance)
    {
        abort_unless($request->user()?->can('hr.attendance.manage'), 403);

        $attendance->delete();

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
}
