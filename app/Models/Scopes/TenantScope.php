<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    /**
     * Restrict monitor queries to teams the authenticated user belongs to.
     * Unauthenticated contexts (scheduler, queue workers, tests) remain unscoped.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! Auth::check()) {
            return;
        }

        $teamIds = Auth::user()->teams()->pluck('teams.id');

        $builder->whereHas('project', function (Builder $query) use ($teamIds) {
            $query->whereIn('team_id', $teamIds);
        });
    }
}
