<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GameplaySecurityService
{
    /**
     * @return array{started_at: string, expires_at: string, timer_seconds: int}
     */
    public function createTimedWindow(int $timerSeconds, ?CarbonInterface $startedAt = null): array
    {
        $startedAt = CarbonImmutable::instance($startedAt ?? now());
        $timerSeconds = max(0, $timerSeconds);

        return [
            'started_at' => $startedAt->toIso8601String(),
            'expires_at' => $startedAt->addSeconds($timerSeconds)->toIso8601String(),
            'timer_seconds' => $timerSeconds,
        ];
    }

    public function isExpired(array $state, ?int $fallbackTimerSeconds = null): bool
    {
        $expiresAt = $this->expiresAt($state, $fallbackTimerSeconds);

        return $expiresAt === null || now()->greaterThanOrEqualTo($expiresAt);
    }

    public function remainingSeconds(array $state, ?int $fallbackTimerSeconds = null): int
    {
        $expiresAt = $this->expiresAt($state, $fallbackTimerSeconds);

        if ($expiresAt === null) {
            return 0;
        }

        return max(0, now()->diffInSeconds($expiresAt, false));
    }

    public function startedAt(array $state): ?CarbonImmutable
    {
        return $this->parseTimestamp($state['started_at'] ?? null);
    }

    public function expiresAt(array $state, ?int $fallbackTimerSeconds = null): ?CarbonImmutable
    {
        $expiresAt = $this->parseTimestamp($state['expires_at'] ?? null);

        if ($expiresAt !== null) {
            return $expiresAt;
        }

        $startedAt = $this->startedAt($state);
        $timerSeconds = $this->timerSeconds($state, $fallbackTimerSeconds);

        if ($startedAt === null || $timerSeconds === null) {
            return null;
        }

        return $startedAt->addSeconds($timerSeconds);
    }

    public function timerSeconds(array $state, ?int $fallbackTimerSeconds = null): ?int
    {
        if (isset($state['timer_seconds']) && is_numeric($state['timer_seconds'])) {
            return max(0, (int) $state['timer_seconds']);
        }

        if ($fallbackTimerSeconds === null) {
            return null;
        }

        return max(0, $fallbackTimerSeconds);
    }

    public function stateMatches(array $state, string $gameType, ?int $ageGroupId = null, ?int $challengeId = null): bool
    {
        if (array_key_exists('game_type', $state) && ($state['game_type'] ?? null) !== $gameType) {
            return false;
        }

        if ($ageGroupId !== null && array_key_exists('age_group_id', $state) && (int) ($state['age_group_id'] ?? 0) !== $ageGroupId) {
            return false;
        }

        if ($challengeId !== null && array_key_exists('challenge_id', $state) && (int) ($state['challenge_id'] ?? 0) !== $challengeId) {
            return false;
        }

        return true;
    }

    public function expirationMessage(string $modeLabel): string
    {
        return 'Temps dépassé pour '.$modeLabel.'. Cette tentative a expiré côté serveur. Relance une nouvelle partie.';
    }

    public function timerSyncMessage(string $modeLabel): string
    {
        return 'Le chrono de '.$modeLabel.' n’est plus synchronisé. Recharge la page pour relancer une tentative valide.';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logExpiredSubmission(string $channel, Request $request, array $context = []): void
    {
        Log::warning('Gameplay submission expired.', $this->baseContext($channel, $request) + $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logInvalidAttempt(string $channel, Request $request, string $reason, array $context = []): void
    {
        Log::warning('Gameplay submission rejected.', $this->baseContext($channel, $request) + [
            'reason' => $reason,
        ] + $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logRateLimitHit(string $channel, Request $request, array $context = []): void
    {
        Log::warning('Gameplay rate limit exceeded.', $this->baseContext($channel, $request) + $context);
    }

    private function parseTimestamp(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function baseContext(string $channel, Request $request): array
    {
        return [
            'channel' => $channel,
            'route' => $request->route()?->getName(),
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
        ];
    }
}
