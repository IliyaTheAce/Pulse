<?php

namespace App\Models;

use App\Enums\TeamRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function roleOnTeam(Team|int $team): ?TeamRole
    {
        $teamId = $team instanceof Team ? $team->id : $team;

        $membership = $this->teams()->whereKey($teamId)->first();

        if ($membership === null || $membership->pivot->role === null) {
            return null;
        }

        return TeamRole::tryFrom($membership->pivot->role);
    }

    public function isTeamMember(Team|int $team): bool
    {
        return $this->roleOnTeam($team) !== null;
    }

    public function canManageTeam(Team|int $team): bool
    {
        return $this->roleOnTeam($team)?->canManage() ?? false;
    }

    public function isTeamOwner(Team|int $team): bool
    {
        return $this->roleOnTeam($team) === TeamRole::Owner;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
