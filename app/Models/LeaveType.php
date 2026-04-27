<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'default_days_per_year',
        'requires_approval',
        'is_paid',
        'can_carry_forward',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_days_per_year' => 'decimal:2',
            'requires_approval' => 'boolean',
            'is_paid' => 'boolean',
            'can_carry_forward' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
