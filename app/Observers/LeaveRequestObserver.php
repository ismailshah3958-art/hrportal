<?php

namespace App\Observers;

use App\Models\LeaveRequest;
use App\Notifications\LeaveRequestSubmitted;
use Illuminate\Support\Facades\Notification;

class LeaveRequestObserver
{
    public function created(LeaveRequest $leaveRequest): void
    {
        $leaveRequest->load(['employee.manager.user', 'leaveType']);

        $users = LeaveRequestSubmitted::recipients($leaveRequest);
        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new LeaveRequestSubmitted($leaveRequest));
    }
}
