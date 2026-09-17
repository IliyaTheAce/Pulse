<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page ?? 1;
        $skip = ($page - 1) * 15;
        return TeamResource::collection(Team::query()->skip($skip)->take(15)->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeamRequest $request)
    {
        $attrs = $request->validated();
        $attrs["owner_id"] = $request->user()->id;
        $team = Team::query()->create($attrs);
        $team->members()->attach($request->user());
        return $this->successResponse(__("success_creation", ["attribute" => "team"]), new TeamResource($team));
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        $team->load('members', "owner");
        $team = new TeamResource($team);
        return $this->successResponse(null, $team);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTeamRequest $request, Team $team)
    {
        $team->update($request->validated());
        return $this->successResponse(__("success_update", ["attribute" => "team"]), new TeamResource($team));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        $team->delete();
        return $this->successResponse(__("success_deletion", ["attribute" => "team"]), new TeamResource($team));
    }
}
