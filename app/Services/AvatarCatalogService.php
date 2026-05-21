<?php

namespace App\Services;

use App\Models\User;

class AvatarCatalogService
{
    /**
     * @return array<int, array{slug: string, name: string, mark: string, tone: string, description: string}>
     */
    public function avatars(): array
    {
        return [
            ['slug' => 'comete', 'name' => 'Comète', 'mark' => 'CM', 'tone' => 'comet', 'description' => 'Rapide et lumineuse.'],
            ['slug' => 'tempo', 'name' => 'Tempo', 'mark' => 'TP', 'tone' => 'tempo', 'description' => 'Rythmée et régulière.'],
            ['slug' => 'prisme', 'name' => 'Prisme', 'mark' => 'PR', 'tone' => 'prism', 'description' => 'Curieuse et logique.'],
            ['slug' => 'pixel', 'name' => 'Pixel', 'mark' => 'PX', 'tone' => 'pixel', 'description' => 'Précise et vive.'],
            ['slug' => 'orbite', 'name' => 'Orbite', 'mark' => 'OR', 'tone' => 'orbit', 'description' => 'Stable et posée.'],
            ['slug' => 'bloom', 'name' => 'Bloom', 'mark' => 'BL', 'tone' => 'bloom', 'description' => 'Douce et confiante.'],
            ['slug' => 'echo', 'name' => 'Echo', 'mark' => 'EC', 'tone' => 'echo', 'description' => 'Attentive et maligne.'],
            ['slug' => 'nova', 'name' => 'Nova', 'mark' => 'NV', 'tone' => 'nova', 'description' => 'Audacieuse et brillante.'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function avatarSlugs(): array
    {
        return array_map(
            static fn (array $avatar): string => $avatar['slug'],
            $this->avatars(),
        );
    }

    /**
     * @return array{slug: string, name: string, mark: string, tone: string, description: string}
     */
    public function defaultAvatar(): array
    {
        return $this->avatarBySlug(null);
    }

    /**
     * @return array{slug: string, name: string, mark: string, tone: string, description: string}
     */
    public function avatarBySlug(?string $slug): array
    {
        foreach ($this->avatars() as $avatar) {
            if ($avatar['slug'] === $slug) {
                return $avatar;
            }
        }

        return $this->avatars()[0];
    }

    /**
     * @return array{slug: string, name: string, mark: string, tone: string, description: string}
     */
    public function avatarForUser(?User $user): array
    {
        return $this->avatarBySlug($user?->playerProfile?->avatar_slug);
    }

    /**
     * @return array{slug: string, name: string, mark: string, tone: string, description: string}
     */
    public function aiAvatar(?string $level): array
    {
        return match ($level) {
            'facile' => ['slug' => 'ia-facile', 'name' => 'IA Facile', 'mark' => 'IF', 'tone' => 'ai-easy', 'description' => 'Guide bienveillant.'],
            'moyen' => ['slug' => 'ia-moyen', 'name' => 'IA Moyen', 'mark' => 'IM', 'tone' => 'ai-medium', 'description' => 'Adversaire équilibré.'],
            'difficile' => ['slug' => 'ia-difficile', 'name' => 'IA Difficile', 'mark' => 'ID', 'tone' => 'ai-hard', 'description' => 'Réponse affûtée.'],
            default => ['slug' => 'ia-expert', 'name' => 'IA Expert', 'mark' => 'IX', 'tone' => 'ai-expert', 'description' => 'Presque imbattable.'],
        };
    }
}
