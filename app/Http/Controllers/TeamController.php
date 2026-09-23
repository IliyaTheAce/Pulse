<?php

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Team::class);

        $page = $request->page ?? 1;
        $skip = ($page - 1) * 15;

        $teams = Team::query()
            ->whereHas('members', fn ($query) => $query->where('users.id', $request->user()->id))
            ->skip($skip)
            ->take(15)
            ->get();

        return TeamResource::collection($teams);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeamRequest $request)
    {
        Gate::authorize('create', Team::class);

        $attrs = $request->validated();
        $attrs['owner_id'] = $request->user()->id;

        $team = Team::query()->create($attrs);
        $team->addMember($request->user(), TeamRole::Owner);

        return $this->successResponse(__('success_creation', ['attribute' => 'team']), new TeamResource($team));
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        Gate::authorize('view', $team);

        $team->load('members', 'owner');

        return $this->successResponse(null, new TeamResource($team));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTeamRequest $request, Team $team)
    {
        Gate::authorize('update', $team);

        $team->update($request->validated());

        return $this->successResponse(__('success_update', ['attribute' => 'team']), new TeamResource($team));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        Gate::authorize('delete', $team);

        $team->delete();

        return $this->successResponse(__('success_deletion', ['attribute' => 'team']), new TeamResource($team));
    }
}
