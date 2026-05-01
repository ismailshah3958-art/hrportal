<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaveRequestRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Notifications\ActivityNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LeaveRequestController extends Controller
{
    public function index(Request $request, Employee $employee)
    {
        $this->authorizeEmployeeLeaveAccess($request, $employee);

        $rows = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->with('leaveType')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return LeaveRequestResource::collection($rows);
    }

    public function pending(Request $request)
    {
        $user = $request->user();
        abort_unless(
            $user->can('hr.leave.manage') || $user->employee,
            403
        );

        $query = LeaveRequest::query()
            ->where('status', 'pending')
            ->with(['employee', 'leaveType'])
            ->orderByDesc('created_at');

        if (! $user->can('hr.leave.manage')) {
            $mid = $user->employee?->id;
            abort_unless($mid, 403);
            $query->whereHas('employee', fn ($q) => $q->where('manager_id', $mid));
        }

        return LeaveRequestResource::collection($query->limit(100)->get());
    }

    public function store(StoreLeaveRequestRequest $request)
    {
        $validated = $request->validated();

        $employee = Employee::query()->findOrFail($validated['employee_id']);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->startOfDay();
        $totalDays = (float) ($start->diffInDays($end) + 1);

        $row = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_days' => $totalDays,
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
            'manager_id' => $employee->manager_id,
        ]);

        if ($request->user()) {
            $request->user()->notify(new ActivityNotification([
                'title' => 'Leave request submitted',
                'body' => 'Your leave request has been submitted and is waiting for approval.',
                'link' => '/my/leave',
                'meta' => ['leave_request_id' => $row->id],
            ]));
        }

        return (new LeaveRequestResource(
            $row->load('leaveType')
        ))->response()->setStatusCode(201);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorizeLeaveDecision($request, $leaveRequest);

        if ($leaveRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['Only pending requests can be approved.'],
            ]);
        }

        $leaveRequest->update([
            'status' => 'approved',
            'approved_by_user_id' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $leaveRequest->loadMissing('employee.user', 'leaveType');
        $employeeUser = $leaveRequest->employee?->user;
        if ($employeeUser) {
            $employeeUser->notify(new ActivityNotification([
                'title' => 'Leave approved',
                'body' => ($leaveRequest->leaveType?->name ?? 'Leave').' request was approved.',
                'link' => '/my/leave',
                'meta' => ['leave_request_id' => $leaveRequest->id],
            ]));
        }

        return new LeaveRequestResource($leaveRequest->fresh()->load(['employee', 'leaveType']));
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorizeLeaveDecision($request, $leaveRequest);

        if ($leaveRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['Only pending requests can be rejected.'],
            ]);
        }

        $validated = $request->validate([
            'hr_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $leaveRequest->update([
            'status' => 'rejected',
            'approved_by_user_id' => $request->user()->id,
            'approved_at' => now(),
            'hr_notes' => $validated['hr_notes'] ?? null,
        ]);

        $leaveRequest->loadMissing('employee.user', 'leaveType');
        $employeeUser = $leaveRequest->employee?->user;
        if ($employeeUser) {
            $employeeUser->notify(new ActivityNotification([
                'title' => 'Leave rejected',
                'body' => ($leaveRequest->leaveType?->name ?? 'Leave').' request was rejected. Please review notes.',
                'link' => '/my/leave',
                'meta' => ['leave_request_id' => $leaveRequest->id],
            ]));
        }

        return new LeaveRequestResource($leaveRequest->fresh()->load(['employee', 'leaveType']));
    }

    private function authorizeEmployeeLeaveAccess(Request $request, Employee $employee): void
    {
        $user = $request->user();
        if ($user->can('hr.leave.manage')) {
            return;
        }

        if ($user->can('ess.leave.apply') && $user->employee && $user->employee->id === $employee->id) {
            return;
        }

        abort(403);
    }

    private function authorizeLeaveDecision(Request $request, LeaveRequest $leaveRequest): void
    {
        $user = $request->user();
        $leaveRequest->loadMissing('employee');

        if ($user->can('hr.leave.manage')) {
            return;
        }

        $report = $leaveRequest->employee;
        abort_unless($report, 403);

        $managerEmployeeId = $user->employee?->id;
        if ($managerEmployeeId && (int) $report->manager_id === (int) $managerEmployeeId) {
            return;
        }

        abort(403);
    }
}
