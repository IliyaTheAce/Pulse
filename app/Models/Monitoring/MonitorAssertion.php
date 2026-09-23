<?php

namespace App\Models\Monitoring;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitorAssertion extends Model
{
    /** @use HasFactory<\Database\Factories\Monitoring\MonitorAssertionFactory> */
    use HasFactory;

    protected $fillable = ['expected_value', 'field', 'operator', 'monitor_id', 'type'];

    public const TYPE_OPTIONS = ['json', 'status', 'contains', 'latency'];

    public const OPERATOR_OPTIONS = ['equal', 'not_equal', 'contains', 'lt', 'gt'];

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}
