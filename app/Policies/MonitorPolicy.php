<?php

namespace App\Policies;

use App\Models\Monitoring\Monitor;
use App\Models\Project;
use App\Models\User;

class MonitorPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Monitor $monitor): bool
    {
        $monitor->loadMissing('project');

        return $user->isTeamMember($monitor->project->team_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, ?Project $project = null): bool
    {
        if ($project === null) {
            return false;
        }

        return $user->canManageTeam($project->team_id);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Monitor $monitor): bool
    {
        $monitor->loadMissing('project');

        return $user->canManageTeam($monitor->project->team_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Monitor $monitor): bool
    {
        $monitor->loadMissing('project');

        return $user->canManageTeam($monitor->project->team_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Monitor $monitor): bool
    {
        return $this->update($user, $monitor);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Monitor $monitor): bool
    {
        return $this->delete($user, $monitor);
    }
}
