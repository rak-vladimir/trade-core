<?php

namespace App\Http\Requests\Api\V1\Catalog;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexProductRequest extends FormRequest
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
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],

            'category_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('is_active', true)
                    ->whereNull('deleted_at'),
            ],

            'unit_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('units', 'id')
                    ->where('is_active', true),
            ],

            'currency_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('currencies', 'id')
                    ->where('is_active', true),
            ],

            'is_active' => ['sometimes', 'boolean'],

            'sort' => [
                'sometimes',
                'string',
                Rule::in(['name', 'sku', 'price', 'created_at']),
            ],

            'direction' => [
                'sometimes',
                'string',
                Rule::in(['asc', 'desc']),
            ],

            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
