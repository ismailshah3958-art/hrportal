<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosition extends Model
{
    protected $fillable = [
        'title',
        'department_id',
        'location',
        'employment_type',
        'openings',
        'status',
        'posted_at',
        'closing_date',
        'description',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'posted_at' => 'date',
            'closing_date' => 'date',
            'openings' => 'integer',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }
}
