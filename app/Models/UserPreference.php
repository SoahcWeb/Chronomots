<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'sound_enabled',
        'music_enabled',
        'volume_level',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sound_enabled' => 'boolean',
            'music_enabled' => 'boolean',
            'volume_level' => 'integer',
        ];
    }

    /**
     * Default audio preferences used when the player has not saved settings yet.
     *
     * @return array<string, bool|int>
     */
    public static function defaults(): array
    {
        return [
            'sound_enabled' => true,
            'music_enabled' => false,
            'volume_level' => 70,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
