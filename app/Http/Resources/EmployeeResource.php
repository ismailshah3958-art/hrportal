<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Employee */
class EmployeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_code' => $this->employee_code,
            'zk_badge_user_id' => $this->zk_badge_user_id,
            'full_name' => $this->full_name,
            'profile_photo_url' => $this->resource->profilePhotoUrl(),
            'work_email' => $this->work_email,
            'phone' => $this->phone,
            'cnic' => $this->cnic,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender,
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2,
            'city' => $this->city,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'joining_date' => $this->joining_date?->format('Y-m-d'),
            'exit_date' => $this->exit_date?->format('Y-m-d'),
            'employment_type' => $this->employment_type,
            'salary' => $this->salary !== null ? (string) $this->salary : null,
            'status' => $this->status,
            'notes' => $this->notes,
            'user_id' => $this->user_id,
            'department' => $this->when(
                $this->relationLoaded('department'),
                fn () => $this->department
                    ? ['id' => $this->department->id, 'name' => $this->department->name]
                    : null
            ),
            'designation' => $this->when(
                $this->relationLoaded('designation'),
                fn () => $this->designation
                    ? ['id' => $this->designation->id, 'name' => $this->designation->name]
                    : null
            ),
            'manager' => $this->when(
                $this->relationLoaded('manager'),
                fn () => $this->manager
                    ? [
                        'id' => $this->manager->id,
                        'full_name' => $this->manager->full_name,
                        'employee_code' => $this->manager->employee_code,
                    ]
                    : null
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
