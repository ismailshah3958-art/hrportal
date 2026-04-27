<?php

namespace App\Http\Resources\Recruitment;

use App\Models\JobPosition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin JobPosition */
class JobPositionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'department_id' => $this->department_id,
            'department_name' => $this->department?->name,
            'location' => $this->location,
            'employment_type' => $this->employment_type,
            'openings' => (int) $this->openings,
            'status' => $this->status,
            'posted_at' => $this->posted_at?->format('Y-m-d'),
            'closing_date' => $this->closing_date?->format('Y-m-d'),
            'description' => $this->description,
            'applications_count' => $this->whenCounted('applications', fn () => (int) $this->applications_count),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
