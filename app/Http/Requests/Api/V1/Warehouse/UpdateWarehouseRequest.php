<?php

namespace App\Http\Requests\Api\V1\Warehouse;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var \App\Models\Warehouse $warehouse */
        $warehouse = $this->route('warehouse');

        return [
            'code'      => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('warehouses', 'code')->ignore($warehouse),
            ],
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'address'   => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
