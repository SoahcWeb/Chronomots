<?php

namespace App\Services;

use Illuminate\Support\Str;

class WordNormalizerService
{
    /**
     * Normalize a word for dictionary storage and lookups.
     * The normalized value is ASCII-only and lowercase to stay SQLite-friendly.
     */
    public function normalize(string $word): string
    {
        $asciiWord = Str::of($word)
            ->replace(['’', '`', '´'], "'")
            ->squish()
            ->ascii()
            ->lower()
            ->toString();

        return preg_replace('/[^a-z]/', '', $asciiWord) ?? '';
    }

    /**
     * Clean the source word while keeping a readable lowercase representation.
     */
    public function sanitizeSourceWord(string $word): string
    {
        return Str::of($word)
            ->replace(['’', '`', '´'], "'")
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->lower()
            ->toString();
    }

    public function isValidNormalizedWord(string $normalizedWord): bool
    {
        return $normalizedWord !== ''
            && preg_match('/^[a-z]+$/', $normalizedWord) === 1
            && strlen($normalizedWord) >= 2
            && strlen($normalizedWord) <= 32;
    }

    /**
     * Prepare one dictionary row for a batched import.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>|null
     */
    public function prepareImportRecord(string $word, array $metadata = []): ?array
    {
        $sourceWord = $this->sanitizeSourceWord($word);
        $normalizedWord = $this->normalize($sourceWord);

        if (! $this->isValidNormalizedWord($normalizedWord)) {
            return null;
        }

        return [
            'word' => $sourceWord,
            'normalized_word' => $normalizedWord,
            'length' => strlen($normalizedWord),
            'frequency' => $this->normalizeNullableFrequency($metadata['frequency'] ?? null),
            'difficulty_level' => $this->normalizeNullableInteger($metadata['difficulty_level'] ?? null),
            'source' => $this->normalizeNullableString($metadata['source'] ?? null),
            'age_level' => $this->normalizeNullableString($metadata['age_level'] ?? null),
            'is_active' => $this->normalizeBoolean($metadata['is_active'] ?? true),
        ];
    }

    private function normalizeNullableFrequency(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $normalized = round((float) $value, 3);

        return $normalized >= 0 ? $normalized : null;
    }

    private function normalizeNullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return max(0, (int) $value);
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'oui'], true);
        }

        return false;
    }
}
