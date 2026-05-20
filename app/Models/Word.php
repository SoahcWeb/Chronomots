<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'word',
    'normalized_word',
    'length',
    'frequency',
    'age_level',
])]
class Word extends Model
{
    use HasFactory;
}
