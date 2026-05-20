<?php

namespace Database\Seeders;

use App\Models\AgeGroup;
use Illuminate\Database\Seeder;

class AgeGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect([
            [
                'name' => '7-9 ans',
                'min_age' => 7,
                'max_age' => 9,
                'description' => 'Mode découverte avec temps plus long et difficulté réduite.',
                'letters_timer_seconds' => 90,
                'numbers_timer_seconds' => 120,
            ],
            [
                'name' => '10-13 ans',
                'min_age' => 10,
                'max_age' => 13,
                'description' => 'Mode entraînement avec difficulté intermédiaire.',
                'letters_timer_seconds' => 60,
                'numbers_timer_seconds' => 90,
            ],
            [
                'name' => '14+',
                'min_age' => 14,
                'max_age' => null,
                'description' => 'Mode expert proche des règles classiques.',
                'letters_timer_seconds' => 45,
                'numbers_timer_seconds' => 60,
            ],
        ])->each(function (array $ageGroup): void {
            AgeGroup::updateOrCreate(
                ['name' => $ageGroup['name']],
                $ageGroup,
            );
        });
    }
}
