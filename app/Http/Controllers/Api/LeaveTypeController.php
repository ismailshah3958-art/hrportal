<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(
            $request->user()?->can('hr.leave.manage') || $request->user()?->can('ess.leave.apply'),
            403
        );

        $rows = LeaveType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'default_days_per_year', 'is_paid', 'requires_approval']);

        return response()->json(['data' => $rows]);
    }
}
