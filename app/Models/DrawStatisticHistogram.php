<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrawStatisticHistogram extends Model
{
    protected $fillable = [
        'draw_statistic_id',
        'metric',
        'bucket',
        'entries_count',
    ];

    public function drawStatistic(): BelongsTo
    {
        return $this->belongsTo(DrawStatistic::class);
    }
}
