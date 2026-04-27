<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class LeaveRequestSubmitted extends Notification
{
    public function __construct(
        public LeaveRequest $leaveRequest
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->leaveRequest->loadMissing(['employee', 'leaveType']);

        $emp = $this->leaveRequest->employee;
        $type = $this->leaveRequest->leaveType;

        return [
            'title' => 'Leave request pending approval',
            'body' => ($emp?->full_name ?? 'Employee').' requested '.($type?->name ?? 'leave')
                .' ('.$this->leaveRequest->start_date?->format('Y-m-d').' → '.$this->leaveRequest->end_date?->format('Y-m-d').').',
            'leave_request_id' => $this->leaveRequest->id,
            'employee_id' => $this->leaveRequest->employee_id,
            'link' => '/leave-approvals',
        ];
    }

    /**
     * Users who should receive this notification (reporting manager + admins, de-duplicated).
     *
     * @return Collection<int, User>
     */
    public static function recipients(LeaveRequest $leaveRequest): Collection
    {
        $leaveRequest->loadMissing(['employee.manager.user']);

        $users = collect();

        $managerUser = $leaveRequest->employee?->manager?->user;
        if ($managerUser instanceof User) {
            $users->push($managerUser);
        }

        $admins = User::query()->role('admin')->get();
        $hr = User::query()->role('hr')->get();
        $users = $users->merge($admins)->merge($hr);

        return $users->unique('id')->values();
    }
}
