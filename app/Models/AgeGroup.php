<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'min_age',
    'max_age',
    'description',
    'letters_timer_seconds',
    'numbers_timer_seconds',
])]
class AgeGroup extends Model
{
    use HasFactory;

    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }
}
