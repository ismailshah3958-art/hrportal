<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

class CurrentUserController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('employee');

        $emp = $user->employee?->loadMissing(['department:id,name', 'designation:id,name']);

        $isLeaveApprover = $user->can('hr.leave.manage')
            || ($emp && Employee::query()->where('manager_id', $emp->id)->exists());

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'employee' => $emp ? [
                'id' => $emp->id,
                'full_name' => $emp->full_name,
                'employee_code' => $emp->employee_code,
                'profile_photo_url' => $emp->profilePhotoUrl(),
                'department_name' => $emp->department?->name,
                'designation_name' => $emp->designation?->name,
                'date_of_birth' => $emp->date_of_birth?->toDateString(),
            ] : null,
            'flags' => [
                'hr_dashboard' => $user->can('hr.dashboard.view'),
                'hr_leave_manage' => $user->can('hr.leave.manage'),
                'hr_payroll_manage' => $user->can('hr.payroll.manage'),
                'hr_recruitment_manage' => $user->can('hr.recruitment.manage'),
                'hr_announcements_manage' => $user->can('hr.announcements.manage'),
                'ess_leave_apply' => $user->can('ess.leave.apply'),
                'ess_attendance_view' => $user->can('ess.attendance.view'),
                'ess_payslip_view' => $user->can('ess.payslip.view'),
                'leave_approver' => $isLeaveApprover,
            ],
        ]);
    }
}
