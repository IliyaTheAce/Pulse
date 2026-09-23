<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMonitorRequest;
use App\Http\Requests\UpdateMonitorRequest;
use App\Http\Resources\MonitorResource;
use App\Jobs\RunMonitorCheck;
use App\Models\Monitoring\Monitor;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class MonitorController extends Controller
{
    /**
     * Display a listing of the resource.
     * Tenant-scoped via TenantScope on Monitor::query().
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Monitor::class);

        $page = $request->page ?? 1;
        $limit = $request->limit ?? 10;
        $skip = ($page - 1) * $limit;

        $monitors = Monitor::query()->skip($skip)->take($limit)->get();

        return $this->successResponse(null, MonitorResource::collection($monitors));
    }

    public function projects_monitors(Project $project, Request $request)
    {
        Gate::authorize('view', $project);

        $page = $request->page ?? 1;
        $limit = $request->limit ?? 10;
        $skip = ($page - 1) * $limit;

        $monitors = Monitor::query()
            ->where('project_id', $project->id)
            ->skip($skip)
            ->take($limit)
            ->get();

        return $this->successResponse(null, MonitorResource::collection($monitors));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMonitorRequest $request, Project $project)
    {
        Gate::authorize('create', [Monitor::class, $project]);

        $body = $request->validated();

        $body = array_merge($body, [
            'project_id' => $project->id,
            'next_check_at' => now(),
        ]);

        $monitor = DB::transaction(function () use ($body, $request) {
            $monitor = Monitor::query()->create($body);

            $headers = $request->validated('headers', []);
            foreach ($headers as $header) {
                $monitor->headers()->create([
                    'key' => $header['key'],
                    'value' => $header['value'],
                ]);
            }

            $assertions = $request->validated('assertions', []);
            foreach ($assertions as $assertion) {
                $monitor->assertions()->create($assertion);
            }

            return $monitor;
        });

        return $this->successResponse(null, new MonitorResource($monitor));
    }

    /**
     * Display the specified resource.
     */
    public function show(Monitor $monitor)
    {
        Gate::authorize('view', $monitor);

        return $this->successResponse('', new MonitorResource($monitor));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMonitorRequest $request, Monitor $monitor)
    {
        Gate::authorize('update', $monitor);

        DB::transaction(function () use ($request, $monitor) {
            $monitor->update($request->validated());

            if ($request->has('headers')) {
                $monitor->syncHeaders($request->input('headers') ?? []);
            }

            if ($request->has('assertions')) {
                $monitor->syncAssertions($request->input('assertions'));
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
        Gate::authorize('delete', $monitor);

        $monitor->delete();

        return $this->successResponse(null, new MonitorResource($monitor));
    }

    public function disable_monitor(Monitor $monitor)
    {
        Gate::authorize('update', $monitor);

        $monitor->update(['enabled' => false]);

        return $this->successResponse('', $monitor);
    }

    public function enable_monitor(Monitor $monitor)
    {
        Gate::authorize('update', $monitor);

        $monitor->update([
            'enabled' => true,
            'next_check_at' => $monitor->next_check_at ?? now(),
        ]);

        return $this->successResponse('', $monitor);
    }

    public function manual_run_monitor(Monitor $monitor)
    {
        Gate::authorize('update', $monitor);

        RunMonitorCheck::dispatch($monitor->toProbeSnapshot(), (string) Str::uuid());

        return $this->successResponse('Monitor has been queued to be checked');
    }

    public function results(Monitor $monitor, Request $request)
    {
        Gate::authorize('view', $monitor);

        $page = $request->page ?? 1;
        $limit = $request->limit ?? 25;
        $skip = ($page - 1) * $limit;
        $status_filter = $request->query('status') ?? null;
        $http_status_filter = $request->query('http_status') ?? null;
        $duration_filter = $request->query('duration') ?? null;
        $duration_operator = $request->query('duration_operator') ?? 'gte';

        // from and to must match this pattern "2026-09-23T15:33:58"
        $from = $request->query('from') ?? null;
        $to = $request->query('to') ?? null;

        $results = $monitor->results();

        if ($status_filter) {
            $results = $results->where('status', $status_filter);
        }

        if ($http_status_filter) {
            $results = $results->where('http_status', $http_status_filter);
        }

        if ($duration_filter) {
            $results = $results->where('duration_ms', $duration_operator === 'gte' ? '>=' : '<=', $duration_filter);
        }

        if ($from) {
            $results = $results->where('checked_at', '>=', $from);
        }

        if ($to) {
            $results = $results->where('checked_at', '<=', $to);
        }

        $results = $results->skip($skip)->take($limit)->get();

        return $this->successResponse('', $results);
    }
}
