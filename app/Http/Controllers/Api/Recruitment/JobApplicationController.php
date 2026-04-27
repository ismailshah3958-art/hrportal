<?php

namespace App\Http\Controllers\Api\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Resources\Recruitment\JobApplicationResource;
use App\Http\Resources\Recruitment\JobPositionResource;
use App\Models\JobApplication;
use App\Models\JobPosition;
use Illuminate\Http\Request;

class JobApplicationController extends Controller
{
    public function index(Request $request, JobPosition $jobPosition)
    {
        abort_unless($request->user()?->can('hr.recruitment.manage'), 403);

        $rows = JobApplication::query()
            ->where('job_position_id', $jobPosition->id)
            ->with([
                'interviews' => function ($q) {
                    $q->with('interviewer:id,full_name')->orderByDesc('scheduled_at');
                },
            ])
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'position' => (new JobPositionResource($jobPosition->load('department:id,name')))->resolve(),
            'data' => JobApplicationResource::collection($rows)->resolve(),
        ]);
    }

    public function store(Request $request, JobPosition $jobPosition)
    {
        abort_unless($request->user()?->can('hr.recruitment.manage'), 403);

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $row = JobApplication::query()->create([
            'job_position_id' => $jobPosition->id,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'stage' => 'applied',
            'notes' => $data['notes'] ?? null,
            'created_by_user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Application added.',
            'data' => (new JobApplicationResource($row))->resolve(),
        ], 201);
    }

    public function update(Request $request, JobPosition $jobPosition, JobApplication $jobApplication)
    {
        abort_unless($request->user()?->can('hr.recruitment.manage'), 403);
        abort_unless((int) $jobApplication->job_position_id === (int) $jobPosition->id, 404);

        $data = $request->validate([
            'stage' => ['sometimes', 'required', 'in:'.implode(',', JobApplication::STAGES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $jobApplication->update($data);

        return response()->json([
            'message' => 'Application updated.',
            'data' => (new JobApplicationResource($jobApplication->fresh()))->resolve(),
        ]);
    }
}
