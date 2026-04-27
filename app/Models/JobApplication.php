<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobApplication extends Model
{
    public const STAGES = ['applied', 'interview', 'offer', 'hired', 'rejected'];

    protected $fillable = [
        'job_position_id',
        'full_name',
        'email',
        'phone',
        'stage',
        'notes',
        'created_by_user_id',
    ];

    public function jobPosition(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }
}
