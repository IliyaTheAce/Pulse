<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMonitorRequest;
use App\Http\Requests\UpdateMonitorRequest;
use App\Http\Resources\MonitorResource;
use App\Models\Monitoring\Monitor;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MonitorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page ?? 1;
        $limit = $request->limit ?? 10;
        $skip = ($page - 1) * $limit;

        $monitors = Monitor::query()->skip($skip)->take($limit)->get();

        return $this->successResponse(null, MonitorResource::collection($monitors));
    }

    public function projects_monitors(Project $project, Request $request)
    {
        $page = $request->page ?? 1;
        $limit = $request->limit ?? 10;
        $skip = ($page - 1) * $limit;

        $monitors = Monitor::query()
            ->where("project_id", $project->id)
            ->skip($skip)
            ->take($limit)
            ->get();

        return $this->successResponse(null, MonitorResource::collection($monitors));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(StoreMonitorRequest $request, Project $project)
    {
        $body = $request->validated();
        $body = array_merge($body, [
            "project_id" => $project->id,
            "next_check_at" => now()
        ]);

        $monitor = Monitor::query()->create($body);

        $headers = $request->validated('headers', []);
        foreach ($headers as $header) {
            $monitor->headers()->create([
                'key' => $header['key'],
                'value' => Hash::make($header['value']),
            ]);
        }

        $assertions = $request->validated('assertions', []);
        foreach ($assertions as $assertion) {
            $monitor->assersions()->create($assertion);
        }

        return $this->successResponse(null, new MonitorResource($monitor));
    }

    /**
     * Display the specified resource.
     */
    public function show(Monitor $monitor)
    {
        return $this->successResponse('', new MonitorResource($monitor));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMonitorRequest $request, Monitor $monitor)
    {
        DB::transaction(function () use ($request, $monitor) {
            $monitor->update($request->validated());
            $monitor->syncHeaders($request->input("headers"));

            if ($request->has('assertions')) {
                $monitor->syncAssertions($request->input("assertions"));
            }
            return $monitor;
        });
        return $this->successResponse(null, new MonitorResource($monitor));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Monitor $monitor)
    {
        $monitor->delete();
        return $this->successResponse(null, new MonitorResource($monitor));
    }

    public function disable_monitor(Monitor $monitor)
    {
        $monitor->update(["enabled" => false]);
        return $this->successResponse('', $monitor);
    }

    public function enable_monitor(Monitor $monitor)
    {
        $monitor->update(["enabled" => true]);
        return $this->successResponse('', $monitor);
    }

    public function manual_run_monitor(Monitor $monitor)
    {
        //Todo:add manual run for monitors
    }
}
