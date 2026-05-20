<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'game_session_id',
    'numbers',
    'target_number',
    'submitted_solution',
    'result_value',
    'score',
])]
class NumberRound extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'numbers' => 'array',
        ];
    }

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }
}
