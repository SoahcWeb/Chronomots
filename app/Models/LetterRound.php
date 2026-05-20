<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'game_session_id',
    'letters',
    'submitted_word',
    'best_word',
    'score',
])]
class LetterRound extends Model
{
    use HasFactory;

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }
}
