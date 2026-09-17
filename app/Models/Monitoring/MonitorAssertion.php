<?php

namespace App\Models\Monitoring;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitorAssertion extends Model
{
    protected $fillable = ["expected_value", "field", "operator", "monitor_id", "type"];

    public function monitor(): BelongsTo{
        return $this->belongsTo(Monitor::class);
    }
}
