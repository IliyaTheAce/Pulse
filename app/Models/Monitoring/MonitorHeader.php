<?php

namespace App\Models\Monitoring;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitorHeader extends Model
{
    /** @use HasFactory<\Database\Factories\Monitoring\MonitorHeaderFactory> */
    use HasFactory;

    protected $fillable = [
        'monitor_id',
        'key',
        'value',
    ];

    protected $hidden = ['value'];

    protected function casts(): array
    {
        return [
            'value' => 'encrypted',
        ];
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}
