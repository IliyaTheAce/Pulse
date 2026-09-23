<?php

namespace App\Models\Monitoring;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitorResult extends Model
{
    protected $fillable = ['monitor_id',
        'team_id',
        'execution_id',
        'checked_at',
        'status',
        'http_status',
        'duration_ms',
        'response_bytes',
        'assertion_failures',
        "error_type"
    ];


    protected function casts(): array
    {
        return [
            'assertion_failures' => 'array',
            "http_status" => "integer",
            "checked_at" => "datetime",
        ];
    }
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}
