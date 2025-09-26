<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RolePermissionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role_id' => "required|integer|exists:roles,id",
            // 'permissions' => 'required|array',
            // 'permissions.*' => 'exists:permissions,id',
            'permissions_id'=> "required|array|exists:permissions,id",
            'permissions_id.*' => "integer|exists:permissions,id"
        ];
    }
}
