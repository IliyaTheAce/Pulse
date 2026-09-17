<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');
        $user = $this->user();
        if (!$user->can('update', $project)) {
            return false;
        }

        if (!$this->has('team_id') || $this->team_id === $project->team_id) {
            return true;
        }

        return $user->teams()
            ->whereKey($this->team_id)
            ->exists();
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
            "description" => "required|string",
            "team_id" => "nullable|integer|exists:teams,id",
        ];
    }

    public function messages(): array
    {
        return [
            "name.required" => __("validation_required", ["attribute" => "name"]),
            "description.required" => __("validation_required", ["attribute" => "description"]),
            "team_id.exists" => __("validation_foreign_key_not_exists", ["table" => "team", "key" => $this->input("team_id")]),
            "team_id.integer" => __("validation_wrong_type", ["attribute" => "team_id", "type" => "integer"]),
        ];
    }
}
