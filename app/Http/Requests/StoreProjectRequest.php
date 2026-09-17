<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        if ($user->teams()->whereKey($this->input('team_id'))->exists()) {
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
            "description" => "required|string",
            "team_id" => "required|integer|exists:teams,id",
        ];
    }

    public function messages(): array
    {
        return [
            "name.required" => __("validation_required", ["attribute" => "name"]),
            "description.required" => __("validation_required", ["attribute" => "description"]),
            "team_id.required" => __("validation_required", ["attribute" => "team"]),
            "team_id.exists" => __("validation_foreign_key_not_exists", ["table" => "team", "key" => $this->input("team_id")]),
            "team_id.integer" => __("validation_wrong_type", ["attribute" => "team_id", "type" => "integer"]),
        ];
    }
}
