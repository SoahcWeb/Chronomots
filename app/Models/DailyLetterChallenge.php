<?php

namespace App\Models;

use App\Enums\DifficultyLevel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class DailyLetterChallenge extends Model
{
    protected $fillable = [
        'challenge_date',
        'difficulty_level',
        'age_group_id',
        'letters',
        'solution_word',
        'max_score',
        'quality_score',
        'metadata',
        'starts_at',
        'ends_at',
        'generated_at',
    ];

    protected $casts = [
        'challenge_date' => 'date',
        'difficulty_level' => DifficultyLevel::class,
        'letters' => 'array',
        'metadata' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'generated_at' => 'datetime',
    ];

    protected function challengeDate(): Attribute
    {
        return Attribute::make(
            set: static fn ($value) => $value === null ? null : Carbon::parse($value)->toDateString(),
        );
    }

    public function ageGroup(): BelongsTo
    {
        return $this->belongsTo(AgeGroup::class);
    }
}
