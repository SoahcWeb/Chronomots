<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'word',
    'normalized_word',
    'length',
    'frequency',
    'difficulty_level',
    'source',
    'age_level',
    'is_active',
])]
class Word extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'frequency' => 'float',
            'difficulty_level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected function word(): Attribute
    {
        return Attribute::make(
            set: static fn (?string $value): ?string => $value !== null ? mb_strtolower(trim($value)) : null,
        );
    }

    protected function normalizedWord(): Attribute
    {
        return Attribute::make(
            set: static fn (?string $value): ?string => $value !== null ? mb_strtolower(trim($value)) : null,
        );
    }
}
