<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DrawStatistic extends Model
{
    protected $fillable = [
        'scope_key',
        'scope',
        'game_type',
        'age_group_id',
        'accepted_draws_count',
        'rejected_draws_count',
        'total_letters_drawn',
        'total_possible_words',
        'total_possible_word_length',
        'total_difficulty_score',
        'average_possible_word_length',
        'average_difficulty_score',
        'rejection_rate',
        'letter_frequency',
        'last_rebuilt_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'letter_frequency' => 'array',
            'last_rebuilt_at' => 'datetime',
            'average_possible_word_length' => 'decimal:2',
            'average_difficulty_score' => 'decimal:2',
            'rejection_rate' => 'decimal:4',
        ];
    }

    public function ageGroup(): BelongsTo
    {
        return $this->belongsTo(AgeGroup::class);
    }

    public function histograms(): HasMany
    {
        return $this->hasMany(DrawStatisticHistogram::class);
    }
}
