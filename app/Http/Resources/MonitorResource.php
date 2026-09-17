<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "description" => $this->description,
            "enabled" => $this->enabled,
            "url" => $this->url,
            "type" => $this->type,
            "method" => $this->method,
            "timeout_ms" => $this->timeout_ms,
            "last_checked_at" => $this->last_checked_at,
            "interval_seconds" => $this->interval_seconds,
            "expected_status" => $this->expected_status,
            "next_check_at" => $this->next_check_at,
            "project" => $this->project,
            "headers" => $this->headers,
            "assertions" => $this->assertions
        ];
    }
}
