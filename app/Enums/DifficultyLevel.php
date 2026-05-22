<?php

namespace App\Enums;

enum DifficultyLevel: string
{
    case EASY = 'easy';
    case NORMAL = 'normal';
    case HARD = 'hard';
    case EXPERT = 'expert';
}
