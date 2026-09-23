<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Project::class);

        $user = $request->user();
        $page = $request->page ?? 1;
        $skip = ($page - 1) * 15;

        $projects = Project::query()
            ->whereIn('team_id', $user->teams()->pluck('teams.id'))
            ->with('team')
            ->skip($skip)
            ->take(15)
            ->get();

        return $this->successResponse('', ProjectResource::collection($projects));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request)
    {
        $team = Team::query()->findOrFail($request->validated('team_id'));
        Gate::authorize('create', [Project::class, $team]);

        $project = Project::query()->create($request->validated());

        return $this->successResponse(__('success_creation', ['attribute' => 'project']), new ProjectResource($project));
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        Gate::authorize('view', $project);

        $project->load('team');

        return $this->successResponse(null, new ProjectResource($project));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        Gate::authorize('update', $project);

        $project->update($request->validated());

        return $this->successResponse(__('success_update', ['attribute' => 'project']), new ProjectResource($project));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        Gate::authorize('delete', $project);

        $project->delete();

        return $this->successResponse(__('success_deletion', ['attribute' => 'project']), new ProjectResource($project));
    }
}
