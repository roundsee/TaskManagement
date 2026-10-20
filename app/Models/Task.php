<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory;

    protected $fillable = [
        'task_type',
        'title',
        'description',
        'user_id',
        'status',
        'loan_reference_no',
        'complexity_flag',
        'complexity_note',
        'ba_id',
        'business_requirement',
        'test_case_narration',
        'repository_url',
        'is_test_passed_100',
        'rejection_note',
        'estimated_duration_minutes',
        'actual_duration_minutes',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_test_passed_100' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ba(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ba_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TaskLog::class);
    }

    public function scopeForToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    public function isItWorkflow(): bool
    {
        return $this->task_type === 'it_dev';
    }
}
