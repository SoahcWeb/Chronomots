<?php

namespace Database\Seeders;

use App\Models\Word;
use App\Services\WordNormalizerService;
use Illuminate\Database\Seeder;

class WordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wordNormalizer = app(WordNormalizerService::class);

        collect([
            ['word' => 'ami', 'frequency' => 90, 'age_level' => '7-9'],
            ['word' => 'amis', 'frequency' => 85, 'age_level' => '7-9'],
            ['word' => 'amer', 'frequency' => 70, 'age_level' => '7-9'],
            ['word' => 'amers', 'frequency' => 64, 'age_level' => '7-9'],
            ['word' => 'chat', 'frequency' => 88, 'age_level' => '7-9'],
            ['word' => 'chats', 'frequency' => 82, 'age_level' => '7-9'],
            ['word' => 'chien', 'frequency' => 82, 'age_level' => '7-9'],
            ['word' => 'loup', 'frequency' => 74, 'age_level' => '7-9'],
            ['word' => 'lune', 'frequency' => 80, 'age_level' => '7-9'],
            ['word' => 'lunes', 'frequency' => 73, 'age_level' => '7-9'],
            ['word' => 'mare', 'frequency' => 62, 'age_level' => '7-9'],
            ['word' => 'mer', 'frequency' => 92, 'age_level' => '7-9'],
            ['word' => 'rame', 'frequency' => 68, 'age_level' => '7-9'],
            ['word' => 'moto', 'frequency' => 79, 'age_level' => '7-9'],
            ['word' => 'motos', 'frequency' => 72, 'age_level' => '7-9'],
            ['word' => 'mot', 'frequency' => 98, 'age_level' => '7-9'],
            ['word' => 'mots', 'frequency' => 95, 'age_level' => '7-9'],
            ['word' => 'robot', 'frequency' => 71, 'age_level' => '7-9'],
            ['word' => 'soleil', 'frequency' => 77, 'age_level' => '7-9'],
            ['word' => 'été', 'frequency' => 83, 'age_level' => '7-9'],
            ['word' => 'orange', 'frequency' => 86, 'age_level' => '10-13'],
            ['word' => 'tomate', 'frequency' => 76, 'age_level' => '10-13'],
            ['word' => 'maison', 'frequency' => 89, 'age_level' => '10-13'],
            ['word' => 'logique', 'frequency' => 72, 'age_level' => '10-13'],
            ['word' => 'calcul', 'frequency' => 81, 'age_level' => '10-13'],
            ['word' => 'jardin', 'frequency' => 67, 'age_level' => '10-13'],
            ['word' => 'pirate', 'frequency' => 69, 'age_level' => '10-13'],
            ['word' => 'nombre', 'frequency' => 75, 'age_level' => '10-13'],
            ['word' => 'vitesse', 'frequency' => 65, 'age_level' => '10-13'],
            ['word' => 'question', 'frequency' => 58, 'age_level' => '10-13'],
            ['word' => 'triangle', 'frequency' => 63, 'age_level' => '10-13'],
            ['word' => 'stratégie', 'frequency' => 41, 'age_level' => '14+'],
            ['word' => 'réflexion', 'frequency' => 45, 'age_level' => '14+'],
            ['word' => 'équation', 'frequency' => 44, 'age_level' => '14+'],
            ['word' => 'algèbre', 'frequency' => 39, 'age_level' => '14+'],
            ['word' => 'variable', 'frequency' => 37, 'age_level' => '14+'],
            ['word' => 'quotient', 'frequency' => 35, 'age_level' => '14+'],
            ['word' => 'précision', 'frequency' => 42, 'age_level' => '14+'],
            ['word' => 'consonne', 'frequency' => 31, 'age_level' => '14+'],
            ['word' => 'voyelle', 'frequency' => 33, 'age_level' => '14+'],
        ])->each(function (array $entry) use ($wordNormalizer): void {
            $normalizedWord = $wordNormalizer->normalize($entry['word']);

            Word::updateOrCreate(
                ['normalized_word' => $normalizedWord],
                [
                    'word' => $entry['word'],
                    'normalized_word' => $normalizedWord,
                    'length' => strlen($normalizedWord),
                    'frequency' => $entry['frequency'],
                    'age_level' => $entry['age_level'],
                ],
            );
        });
    }
}
