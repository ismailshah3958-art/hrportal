<?php

namespace App\Http\Controllers\Api\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Resources\Recruitment\InterviewResource;
use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\JobPosition;
use Illuminate\Http\Request;

class ApplicationInterviewController extends Controller
{
    public function index(Request $request, JobPosition $jobPosition, JobApplication $jobApplication)
    {
        abort_unless($request->user()?->can('hr.recruitment.manage'), 403);
        $this->assertApplication($jobPosition, $jobApplication);

        $rows = Interview::query()
            ->where('job_application_id', $jobApplication->id)
            ->with('interviewer:id,full_name')
            ->orderByDesc('scheduled_at')
            ->get();

        return response()->json([
            'data' => InterviewResource::collection($rows)->resolve(),
        ]);
    }

    public function store(Request $request, JobPosition $jobPosition, JobApplication $jobApplication)
    {
        abort_unless($request->user()?->can('hr.recruitment.manage'), 403);
        $this->assertApplication($jobPosition, $jobApplication);

        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:480'],
            'mode' => ['nullable', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:255'],
            'interviewer_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ]);

        $row = Interview::query()->create([
            'job_application_id' => $jobApplication->id,
            'scheduled_at' => $data['scheduled_at'],
            'duration_minutes' => $data['duration_minutes'] ?? 60,
            'mode' => $data['mode'] ?? 'video',
            'location' => $data['location'] ?? null,
            'interviewer_employee_id' => $data['interviewer_employee_id'] ?? null,
            'status' => 'scheduled',
        ]);

        $row->load('interviewer:id,full_name');

        return response()->json([
            'message' => 'Interview scheduled.',
            'data' => (new InterviewResource($row))->resolve(),
        ], 201);
    }

    private function assertApplication(JobPosition $jobPosition, JobApplication $jobApplication): void
    {
        abort_unless((int) $jobApplication->job_position_id === (int) $jobPosition->id, 404);
    }
}
