<?php

namespace App\Http\Controllers\Api\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Resources\Recruitment\JobPositionResource;
use App\Models\JobPosition;
use Illuminate\Http\Request;

class JobPositionController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->can('hr.recruitment.manage'), 403);

        $rows = JobPosition::query()
            ->with('department:id,name')
            ->withCount('applications')
            ->orderByRaw("case when status = 'open' then 0 else 1 end")
            ->orderByDesc('posted_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => JobPositionResource::collection($rows)->resolve(),
        ]);
    }

    public function show(Request $request, JobPosition $jobPosition)
    {
        abort_unless($request->user()?->can('hr.recruitment.manage'), 403);

        $jobPosition->load('department:id,name');
        $jobPosition->loadCount('applications');

        return response()->json([
            'data' => (new JobPositionResource($jobPosition))->resolve(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->can('hr.recruitment.manage'), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'location' => ['nullable', 'string', 'max:150'],
            'employment_type' => ['required', 'string', 'max:30'],
            'openings' => ['required', 'integer', 'min:1', 'max:999'],
            'posted_at' => ['nullable', 'date'],
            'closing_date' => ['nullable', 'date', 'after_or_equal:posted_at'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $data['status'] = 'open';
        $data['created_by_user_id'] = $request->user()->id;
        $data['posted_at'] = $data['posted_at'] ?? now()->toDateString();

        $row = JobPosition::query()->create($data)->load('department:id,name');

        return response()->json([
            'message' => 'Position created.',
            'data' => (new JobPositionResource($row))->resolve(),
        ], 201);
    }

    public function update(Request $request, JobPosition $jobPosition)
    {
        abort_unless($request->user()?->can('hr.recruitment.manage'), 403);

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:150'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'location' => ['nullable', 'string', 'max:150'],
            'employment_type' => ['sometimes', 'required', 'string', 'max:30'],
            'openings' => ['sometimes', 'required', 'integer', 'min:1', 'max:999'],
            'status' => ['sometimes', 'required', 'in:open,closed,on_hold'],
            'posted_at' => ['nullable', 'date'],
            'closing_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $jobPosition->update($data);

        return response()->json([
            'message' => 'Position updated.',
            'data' => (new JobPositionResource($jobPosition->fresh()->load('department:id,name')))->resolve(),
        ]);
    }
}
