<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'employee_code',
        'zk_badge_user_id',
        'full_name',
        'profile_photo_path',
        'work_email',
        'personal_email',
        'phone',
        'whatsapp_phone',
        'cnic',
        'date_of_birth',
        'gender',
        'address_line1',
        'address_line2',
        'city',
        'country',
        'postal_code',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'department_id',
        'designation_id',
        'manager_id',
        'joining_date',
        'exit_date',
        'employment_type',
        'salary',
        'status',
        'bank_name',
        'bank_branch',
        'bank_account_title',
        'bank_account_number',
        'bank_iban',
        'notes',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'zk_badge_user_id' => 'integer',
            'date_of_birth' => 'date',
            'joining_date' => 'date',
            'exit_date' => 'date',
            'salary' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(EmployeeShiftAssignment::class);
    }

    public function profilePhotoUrl(): ?string
    {
        if (! $this->profile_photo_path) {
            return null;
        }

        $path = $this->profile_photo_path;

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $base = rtrim((string) config('app.url'), '/');

        return $base.'/storage/'.ltrim($path, '/');
    }
}
