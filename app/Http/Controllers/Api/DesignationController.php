<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->can('hr.employees.manage'), 403);

        $query = Designation::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($request->filled('department_id')) {
            $deptId = (int) $request->input('department_id');
            $query->where(function ($q) use ($deptId) {
                $q->whereNull('department_id')
                    ->orWhere('department_id', $deptId);
            });
        }

        $rows = $query->get(['id', 'name', 'department_id', 'level']);

        return response()->json(['data' => $rows]);
    }
}
