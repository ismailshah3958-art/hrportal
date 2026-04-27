<?php

namespace App\Http\Resources\Recruitment;

use App\Models\Interview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Interview */
class InterviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_application_id' => $this->job_application_id,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'duration_minutes' => $this->duration_minutes,
            'mode' => $this->mode,
            'location' => $this->location,
            'interviewer_employee_id' => $this->interviewer_employee_id,
            'interviewer_name' => $this->whenLoaded('interviewer', fn () => $this->interviewer?->full_name),
            'status' => $this->status,
            'feedback' => $this->feedback,
            'rating' => $this->rating,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
