<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyChallengeAttempt extends Model
{
    protected $fillable = [
        'daily_challenge_id',
        'user_id',
        'game_session_id',
        'score',
        'submitted_word',
        'submitted_solution',
        'result_payload',
        'is_perfect',
        'attempted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'result_payload' => 'array',
            'is_perfect' => 'boolean',
            'attempted_at' => 'datetime',
        ];
    }

    public function dailyChallenge(): BelongsTo
    {
        return $this->belongsTo(DailyChallenge::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }
}
