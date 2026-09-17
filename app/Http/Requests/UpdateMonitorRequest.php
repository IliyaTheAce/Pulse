<?php

namespace App\Http\Requests;

use App\Models\Monitoring\Monitor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateMonitorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $monitor = $this->route('monitor');
        $user = Auth::user();
        if ($user->teams()->whereKey($monitor->project->team_id)->exists()) {
            return true;
        }
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "name" => "required|string",
            "description" => "nullable|string",
            "type" => "required|string|in:http,https",
            "method" => "required|string|in:get,post,patch,put,delete,head",
            "enabled" => "nullable|boolean",
            "url" => "string|required",
            "interval_seconds" => "required|integer|gt:1",
            "timeout_ms" => "integer|required|lt:interval_seconds",
            "expected_status" => "string|required",
            "headers" => ['required', 'array'],
            'headers.*.key' => ['required', 'string'],
            'headers.*.value' => ['nullable', 'string'],
            "assertions" => ['sometimes', 'array'],
            "assertions.*.expected_value" => ['required', 'string'],
            "assertions.*.field" => ['nullable', 'string'],
            "assertions.*.operator" => ['required', 'in:equal,not_equal,contains,lt,gt'],
            "assertions.*.type" => ['required', 'in:json,status,contains,latency'],
        ];
    }

    public function messages(): array
    {
        return [
            "name.required" => __("validation_required", ["attribute" => "name"]),
            "type.required" => __("validation_required", ["attribute" => "type"]),
            "method.required" => __("validation_required", ["attribute" => "method"]),
            "url.required" => __("validation_required", ["attribute" => "url"]),
            "expected_status.required" => __("validation_required", ["attribute" => "expected_status"]),
            "timeout_ms.required" => __("validation_required", ["attribute" => "timeout_ms"]),
            "interval_seconds.required" => __("validation_required", ["attribute" => "interval_seconds"]),

            "type.in" => __("validation_in", ["attribute" => "type", "values" => "'" . implode(", ", Monitor::TYPE_OPTIONS) . "'"]),
            "method.in" => __("validation_in", ["attribute" => "method", "values" => "'" . implode(", ", Monitor::METHOD_OPTIONS) . "'"]),

            "interval_seconds.integer" => __("validation_wrong_type", ["attribute" => "interval_seconds", "type" => "integer"]),
            "timeout_ms.integer" => __("validation_wrong_type", ["attribute" => "interval_seconds", "type" => "integer"]),

            "timeout_ms.lt" => __("validation_less_than", ["attribute" => "timeout_ms", "value" => "interval_seconds"]),
            "interval_seconds.gt" => __("validation_greater_than", ["attribute" => "interval_seconds", "value" => "1"]),
        ];
    }
}
