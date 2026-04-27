<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Attendance */
class AttendanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'attendance_date' => $this->attendance_date?->format('Y-m-d'),
            'check_in_at' => $this->check_in_at?->toIso8601String(),
            'check_out_at' => $this->check_out_at?->toIso8601String(),
            'late_minutes' => (int) $this->late_minutes,
            'late_incident' => (bool) $this->late_incident,
            'early_leave_minutes' => (int) $this->early_leave_minutes,
            'work_minutes' => $this->work_minutes !== null ? (int) $this->work_minutes : null,
            'status' => $this->status,
            'source' => $this->source,
            'notes' => $this->notes,
            'employee' => $this->when(
                $this->relationLoaded('employee'),
                fn () => $this->employee
                    ? [
                        'id' => $this->employee->id,
                        'employee_code' => $this->employee->employee_code,
                        'full_name' => $this->employee->full_name,
                    ]
                    : null
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
