<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(
            $request->user()?->can('hr.employees.manage')
            || $request->user()?->can('hr.recruitment.manage')
            || $request->user()?->can('hr.announcements.manage'),
            403
        );

        $rows = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json(['data' => $rows]);
    }
}
