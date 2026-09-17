<?php

namespace App\Models\Monitoring;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Monitor extends Model
{
    /** @use HasFactory<\Database\Factories\Monitoring\MonitorFactory> */
    use HasFactory;

    const METHOD_OPTIONS = ['get', 'post', 'put', 'patch', 'delete', 'head'];
    const TYPE_OPTIONS = ["http", "https"];

    protected $fillable = ["name",
        'description',
        "enabled",
        "project_id",
        "url",
        "type",
        "method",
        "timeout_ms",
        "last_checked_at",
        "expected_status",
        "next_check_at",
        "interval_seconds"];


    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function headers(): HasMany
    {
        return $this->hasMany(MonitorHeader::class);
    }

    public function assertions(): HasMany
    {
        return $this->hasMany(MonitorAssertion::class);
    }

    public function syncHeaders(array $headers): void
    {
        $incoming = collect($headers);

        // Delete missing
        $this->headers()
            ->whereNotIn('key', $incoming->pluck('key'))
            ->delete();

        foreach ($incoming as $header) {
            $attrs = ['key' => $header['key']];

            $values = array_key_exists('value', $header) && $header['value'] !== null
                ? ['value' => $header['value']]
                : [];

            if (empty($values)) {
                $this->headers()->firstOrCreate($attrs, ['value' => '']);
            } else {
                $this->headers()->updateOrCreate($attrs, $values);
            }
        }
    }

    public function syncAssertions(array $assertions): void
    {
        $incoming = collect($assertions);

        $this->assertions()->delete();

        foreach ($incoming as $assertion) {
            $assertion['monitor_id'] = $this->id;
            $this->assertions()->create($assertion);
        }
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'last_checked_at' => 'datetime',
            'next_check_at' => 'datetime',
        ];
    }
}
