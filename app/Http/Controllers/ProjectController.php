<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $page = $request->page ?? 1;
        $skip = ($page - 1) * 15;
        $projects = Project::query()
            ->whereIn("team_id", $user->teams()->get()->pluck("id"))
            ->with("team")
            ->skip($skip)
            ->take(15)
            ->get();
        return $this->successResponse("", ProjectResource::collection($projects));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request)
    {
        $attrs = $request->validated();

        $project = Project::query()->create($attrs);

        return $this->successResponse(__("success_creation", ["attribute" => "project"]), new ProjectResource($project));
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project, Request $request)
    {
        Gate::authorize('view', $project);
        $project->load("team");
        return $this->successResponse(null, new ProjectResource($project));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        $project->update($request->validated());
        return $this->successResponse(__("success_update", ["attribute" => "project"]), new ProjectResource($project));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $project->delete();
        return $this->successResponse(__("success_deletion", ["attribute" => "project"]), new ProjectResource($project));
    }
}
