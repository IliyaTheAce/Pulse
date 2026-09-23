<?php

namespace App\Models\Monitoring;

use App\Models\Project;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy([TenantScope::class])]
class Monitor extends Model
{
    /** @use HasFactory<\Database\Factories\Monitoring\MonitorFactory> */
    use HasFactory;

    const METHOD_OPTIONS = ['get', 'post', 'put', 'patch', 'delete', 'head'];
    const TYPE_OPTIONS = ['http', 'https'];

    protected $fillable = [
        'name',
        'description',
        'enabled',
        'project_id',
        'url',
        'type',
        'method',
        'timeout_ms',
        'last_checked_at',
        'expected_status',
        'next_check_at',
        'interval_seconds',
    ];

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

    public function results(): HasMany
    {
        return $this->hasMany(MonitorResult::class);
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

    public function toProbeSnapshot(): array
    {
        $this->loadMissing(['headers', 'assertions', 'project']);

        return [
            'monitor_id' => $this->id,
            'team_id' => $this->project->team_id,
            'url' => $this->type.'://'.$this->url,
            'method' => strtoupper((string) $this->method),
            'timeout_ms' => (int) $this->timeout_ms,
            'expected_status' => (int) $this->expected_status,
            'headers' => $this->headers
                ->mapWithKeys(fn ($header) => [$header->key => $header->value])
                ->all(),
            'assertions' => $this->assertions
                ->map(fn ($assertion) => $assertion->only([
                    'type', 'field', 'operator', 'expected_value',
                ]))
                ->all(),
        ];
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
