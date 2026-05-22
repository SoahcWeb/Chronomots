<?php

namespace Tests\Feature;

use App\Models\AgeGroup;
use App\Services\GameIntelligence\AgeDifficultyProfileService;
use App\Services\GameIntelligence\DrawConstraintService;
use App\Services\GameIntelligence\GameIntelligenceManager;
use App\Services\GameIntelligence\LetterPoolService;
use App\Services\GameIntelligence\LettersDrawGenerator;
use App\Services\GameIntelligence\LettersSolvabilityService;
use App\Services\GameIntelligence\NumbersSolvabilityService;
use App\Services\GameIntelligence\OpponentAiService;
use Database\Seeders\AgeGroupSeeder;
use Database\Seeders\WordSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameIntelligenceDrawTest extends TestCase
{
    use RefreshDatabase;

    public function test_letters_draws_are_playable_for_every_age_group(): void
    {
        $this->seed([AgeGroupSeeder::class, WordSeeder::class]);

        $manager = app(GameIntelligenceManager::class);
        $profiles = app(AgeDifficultyProfileService::class);
        $solvability = app(LettersSolvabilityService::class);

        AgeGroup::query()->orderBy('min_age')->each(function (AgeGroup $ageGroup) use ($manager, $profiles, $solvability): void {
            $draw = $manager->createLettersDraw($ageGroup);
            $profile = $profiles->forLetters($ageGroup);
            $report = $solvability->analyze($draw['letters'], $ageGroup, $profile);

            $this->assertTrue($report->valid, sprintf(
                'Le tirage lettres doit être solvable pour %s. letters=%s solutions=%d best_length=%d target_word_count=%d minSolutions=%d minBestLength=%d metadata=%s',
                $ageGroup->name,
                implode('', $draw['letters']),
                $report->solutionsCount,
                (int) ($report->metadata['best_length'] ?? 0),
                (int) ($report->metadata['target_word_count'] ?? 0),
                $profile->minSolutions,
                $profile->minBestLength,
                json_encode($report->metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            ));
            $this->assertGreaterThanOrEqual($profile->minSolutions, $report->solutionsCount);
            $this->assertGreaterThanOrEqual($profile->minBestLength, (int) ($report->metadata['best_length'] ?? 0));
            $this->assertGreaterThanOrEqual(1, (int) ($report->metadata['target_word_count'] ?? 0));
        });
    }

    public function test_numbers_draws_always_choose_a_reachable_target(): void
    {
        $this->seed([AgeGroupSeeder::class]);

        $manager = app(GameIntelligenceManager::class);
        $profiles = app(AgeDifficultyProfileService::class);
        $solvability = app(NumbersSolvabilityService::class);

        AgeGroup::query()->orderBy('min_age')->each(function (AgeGroup $ageGroup) use ($manager, $profiles, $solvability): void {
            $draw = $manager->createNumbersDraw($ageGroup);
            $profile = $profiles->forNumbers($ageGroup);
            $report = $solvability->analyze($draw['numbers'], $profile);

            $this->assertTrue($report->valid, 'Le tirage chiffres doit proposer une cible atteignable pour '.$ageGroup->name);
            $this->assertSame($draw['target_number'], $report->bestValue);
            $this->assertGreaterThanOrEqual($profile->targetMin, $draw['target_number']);
            $this->assertLessThanOrEqual($profile->targetMax, $draw['target_number']);
        });
    }

    public function test_letter_pool_uses_the_modern_weight_map(): void
    {
        $pool = app(LetterPoolService::class);
        $vowels = $pool->weightsForType('vowel');
        $consonants = $pool->weightsForType('consonant');

        $this->assertSame(14, $vowels['E']);
        $this->assertSame(9, $vowels['A']);
        $this->assertSame(8, $vowels['I']);
        $this->assertSame(5, $vowels['O']);
        $this->assertSame(5, $vowels['U']);
        $this->assertSame(7, $consonants['S']);
        $this->assertSame(1, $consonants['J']);
        $this->assertSame(1, $consonants['Y']);
        $this->assertSame(1, $consonants['Z']);
    }

    public function test_letters_draws_respect_modern_vowel_and_rare_constraints(): void
    {
        $this->seed([AgeGroupSeeder::class, WordSeeder::class]);

        $manager = app(GameIntelligenceManager::class);
        $profiles = app(AgeDifficultyProfileService::class);
        $constraints = app(DrawConstraintService::class);
        $pool = app(LetterPoolService::class);

        AgeGroup::query()->orderBy('min_age')->each(function (AgeGroup $ageGroup) use ($manager, $profiles, $constraints, $pool): void {
            $draw = $manager->createLettersDraw($ageGroup);
            $profile = $profiles->forLetters($ageGroup);
            $vowelCount = $constraints->countVowels($draw['letters']);
            $rareCount = count(array_filter($draw['letters'], fn (string $letter) => $pool->isRareLetter($letter)));

            $this->assertGreaterThanOrEqual($constraints->minVowels($profile), $vowelCount);
            $this->assertLessThanOrEqual($constraints->maxVowels($profile), $vowelCount);
            $this->assertLessThanOrEqual((int) ($profile->metadata['max_rare_letters'] ?? 1), $rareCount);
            $this->assertTrue($constraints->isCompletedDrawValid($draw['letters'], $profile));
        });
    }

    public function test_interactive_letters_draw_progressively_reveals_letters_until_completion(): void
    {
        $this->seed([AgeGroupSeeder::class, WordSeeder::class]);

        $manager = app(GameIntelligenceManager::class);
        $profiles = app(AgeDifficultyProfileService::class);
        $constraints = app(DrawConstraintService::class);

        $ageGroup = AgeGroup::query()->where('min_age', '>=', 10)->orderBy('min_age')->firstOrFail();
        $profile = $profiles->forLetters($ageGroup);
        $draw = $manager->startLettersDraw($ageGroup);

        $this->assertSame([], $draw['letters']);

        while (count($draw['letters']) < $profile->lettersCount) {
            $allowedChoices = $draw['allowed_choices'] ?? ['vowel', 'consonant'];
            $preferredChoice = in_array('vowel', $allowedChoices, true) ? 'vowel' : $allowedChoices[0];
            $draw = $manager->revealLettersChoice($ageGroup, $draw, $preferredChoice);
        }

        $this->assertCount($profile->lettersCount, $draw['letters']);
        $this->assertTrue($constraints->isCompletedDrawValid($draw['letters'], $profile));
    }

    public function test_interactive_letters_draw_stays_compatible_with_ai_solver(): void
    {
        $this->seed([AgeGroupSeeder::class, WordSeeder::class]);

        $manager = app(GameIntelligenceManager::class);
        $profiles = app(AgeDifficultyProfileService::class);
        $constraints = app(DrawConstraintService::class);
        $opponentAi = app(OpponentAiService::class);

        $ageGroup = AgeGroup::query()->where('min_age', '>=', 14)->orderBy('min_age')->firstOrFail();
        $profile = $profiles->forLetters($ageGroup);
        $draw = $manager->startLettersDraw($ageGroup);

        while (count($draw['letters']) < $profile->lettersCount) {
            $allowedChoices = $draw['allowed_choices'] ?? ['vowel', 'consonant'];
            $choice = in_array('consonant', $allowedChoices, true) && count($draw['letters']) % 2 === 0
                ? 'consonant'
                : $allowedChoices[0];
            $draw = $manager->revealLettersChoice($ageGroup, $draw, $choice);
        }

        $this->assertTrue($constraints->isCompletedDrawValid($draw['letters'], $profile));

        $result = $opponentAi->playLetters($draw['letters'], $ageGroup, 'expert');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('submitted_word', $result);
        $this->assertArrayHasKey('score', $result);
        $this->assertGreaterThanOrEqual(0, $result['score']);
    }

    public function test_allowed_choice_types_force_vowel_when_remaining_slots_must_cover_minimum_vowels(): void
    {
        $this->seed(AgeGroupSeeder::class);

        $ageGroup = AgeGroup::query()->where('min_age', '>=', 14)->orderBy('min_age')->firstOrFail();
        $profile = app(AgeDifficultyProfileService::class)->forLetters($ageGroup);
        $constraints = app(DrawConstraintService::class);

        $letters = ['B', 'C', 'D', 'F', 'G', 'H', 'L'];

        $this->assertSame(['vowel'], $constraints->allowedChoiceTypes($letters, $profile));
    }

    public function test_allowed_choice_types_force_consonant_when_max_vowels_is_already_reached(): void
    {
        $this->seed(AgeGroupSeeder::class);

        $ageGroup = AgeGroup::query()->where('min_age', '>=', 14)->orderBy('min_age')->firstOrFail();
        $profile = app(AgeDifficultyProfileService::class)->forLetters($ageGroup);
        $constraints = app(DrawConstraintService::class);

        $letters = ['A', 'E', 'I', 'O', 'U', 'A', 'B', 'C', 'D'];

        $this->assertSame(['consonant'], $constraints->allowedChoiceTypes($letters, $profile));
    }

    public function test_consonant_candidates_exclude_rare_letters_when_rare_limit_is_already_reached(): void
    {
        $this->seed(AgeGroupSeeder::class);

        $ageGroup = AgeGroup::query()->where('min_age', '>=', 10)->orderBy('min_age')->firstOrFail();
        $profile = app(AgeDifficultyProfileService::class)->forLetters($ageGroup);
        $constraints = app(DrawConstraintService::class);
        $pool = app(LetterPoolService::class);

        $letters = ['A', 'E', 'I', 'B', 'J', 'T'];
        $candidates = $constraints->candidateLettersForType('consonant', $letters, $profile);

        $this->assertNotEmpty($candidates);
        $this->assertSame([], array_values(array_filter($candidates, fn (string $letter) => $pool->isRareLetter($letter))));
    }

    public function test_reveal_next_letter_recovers_from_contradictory_state_without_exception(): void
    {
        $this->seed(AgeGroupSeeder::class);

        $ageGroup = AgeGroup::query()->where('min_age', '>=', 14)->orderBy('min_age')->firstOrFail();
        $profile = app(AgeDifficultyProfileService::class)->forLetters($ageGroup);
        $generator = app(LettersDrawGenerator::class);
        $pool = app(LetterPoolService::class);

        $state = [
            'letters' => ['A', 'E', 'I', 'O', 'U', 'A', 'B', 'C', 'D'],
            'choice_history' => ['vowel', 'vowel', 'vowel', 'vowel', 'vowel', 'vowel', 'consonant', 'consonant', 'consonant'],
            'seed_word' => null,
            'seed_letters_remaining' => [],
            'letters_target' => $profile->lettersCount,
            'latest_letter' => null,
        ];

        $updated = $generator->revealNextLetter($state, 'vowel', $profile);

        $this->assertCount($profile->lettersCount, $updated['letters']);
        $this->assertFalse($pool->isVowel($updated['latest_letter']));
        $this->assertTrue(app(DrawConstraintService::class)->isCompletedDrawValid($updated['letters'], $profile));
    }

    public function test_completed_contradictory_draw_can_be_repaired_to_a_valid_final_state(): void
    {
        $this->seed(AgeGroupSeeder::class);

        $ageGroup = AgeGroup::query()->where('min_age', '>=', 14)->orderBy('min_age')->firstOrFail();
        $profile = app(AgeDifficultyProfileService::class)->forLetters($ageGroup);
        $constraints = app(DrawConstraintService::class);

        $letters = ['A', 'E', 'I', 'O', 'U', 'A', 'B', 'C', 'D', 'F'];
        $repaired = $constraints->repairCompletedDraw($letters, $profile);

        $this->assertNotNull($repaired);
        $this->assertCount($profile->lettersCount, $repaired);
        $this->assertTrue($constraints->isCompletedDrawValid($repaired, $profile));
    }

    public function test_near_complete_interactive_draw_can_still_finish_with_a_valid_last_letter(): void
    {
        $this->seed(AgeGroupSeeder::class);

        $ageGroup = AgeGroup::query()->where('min_age', '<', 10)->orderBy('min_age')->firstOrFail();
        $profile = app(AgeDifficultyProfileService::class)->forLetters($ageGroup);
        $generator = app(LettersDrawGenerator::class);
        $constraints = app(DrawConstraintService::class);
        $pool = app(LetterPoolService::class);

        $state = [
            'letters' => ['B', 'A', 'C', 'E', 'D', 'L'],
            'choice_history' => ['consonant', 'vowel', 'consonant', 'vowel', 'consonant', 'consonant'],
            'seed_word' => null,
            'seed_letters_remaining' => [],
            'letters_target' => $profile->lettersCount,
            'latest_letter' => null,
        ];

        $this->assertSame(['vowel'], $constraints->allowedChoiceTypes($state['letters'], $profile));

        $updated = $generator->revealNextLetter($state, 'consonant', $profile);

        $this->assertCount($profile->lettersCount, $updated['letters']);
        $this->assertSame('consonant', $updated['requested_choice_type']);
        $this->assertSame('vowel', $updated['applied_choice_type']);
        $this->assertTrue($updated['was_choice_overridden']);
        $this->assertTrue($pool->isVowel($updated['latest_letter']));
        $this->assertTrue($constraints->isCompletedDrawValid($updated['letters'], $profile));
    }
}
