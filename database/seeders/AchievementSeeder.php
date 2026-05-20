<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Services\AchievementService;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            [
                'code' => AchievementService::FIRST_VALID_WORD,
                'name' => 'Premier Mot',
                'description' => 'Valider ton premier mot dans Chronomots.',
                'icon' => 'M1',
                'category' => 'letters',
                'unlock_type' => 'count',
                'unlock_value' => 1,
                'sort_order' => 10,
            ],
            [
                'code' => AchievementService::FIRST_PERFECT_SCORE,
                'name' => 'Score Parfait',
                'description' => 'Obtenir un premier score parfait de 100 points.',
                'icon' => '100',
                'category' => 'general',
                'unlock_type' => 'score',
                'unlock_value' => 1,
                'sort_order' => 20,
            ],
            [
                'code' => AchievementService::TEN_GAMES_PLAYED,
                'name' => 'Marathon 10',
                'description' => 'Jouer 10 parties complètes.',
                'icon' => '10J',
                'category' => 'general',
                'unlock_type' => 'count',
                'unlock_value' => 10,
                'sort_order' => 30,
            ],
            [
                'code' => AchievementService::VICTORY_VS_AI_EASY,
                'name' => 'Duel IA Facile',
                'description' => 'Remporter une partie contre l’IA facile.',
                'icon' => 'IA1',
                'category' => 'duel',
                'unlock_type' => 'duel',
                'unlock_value' => 1,
                'sort_order' => 40,
            ],
            [
                'code' => AchievementService::VICTORY_VS_AI_MEDIUM,
                'name' => 'Duel IA Moyen',
                'description' => 'Remporter une partie contre l’IA moyenne.',
                'icon' => 'IA2',
                'category' => 'duel',
                'unlock_type' => 'duel',
                'unlock_value' => 1,
                'sort_order' => 50,
            ],
            [
                'code' => AchievementService::VICTORY_VS_AI_HARD,
                'name' => 'Duel IA Difficile',
                'description' => 'Remporter une partie contre l’IA difficile.',
                'icon' => 'IA3',
                'category' => 'duel',
                'unlock_type' => 'duel',
                'unlock_value' => 1,
                'sort_order' => 60,
            ],
            [
                'code' => AchievementService::VICTORY_VS_AI_EXPERT,
                'name' => 'Duel IA Expert',
                'description' => 'Remporter une partie contre l’IA expert.',
                'icon' => 'IA4',
                'category' => 'duel',
                'unlock_type' => 'duel',
                'unlock_value' => 1,
                'sort_order' => 70,
            ],
            [
                'code' => AchievementService::EIGHT_LETTER_WORD,
                'name' => 'Mot de 8 Lettres',
                'description' => 'Trouver un mot d’au moins 8 lettres.',
                'icon' => '8L',
                'category' => 'letters',
                'unlock_type' => 'letters',
                'unlock_value' => 1,
                'sort_order' => 80,
            ],
            [
                'code' => AchievementService::TOTAL_SCORE_MILESTONE,
                'name' => 'Score Cumulé',
                'description' => 'Atteindre 500 points cumulés.',
                'icon' => '500',
                'category' => 'general',
                'unlock_type' => 'score_total',
                'unlock_value' => 500,
                'sort_order' => 90,
            ],
        ])->each(function (array $achievement): void {
            Achievement::updateOrCreate(
                ['code' => $achievement['code']],
                $achievement,
            );
        });
    }
}
