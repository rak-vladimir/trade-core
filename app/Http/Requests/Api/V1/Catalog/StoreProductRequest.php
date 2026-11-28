<?php

namespace App\Http\Requests\Api\V1\Catalog;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('is_active', true)
                    ->whereNull('deleted_at'),
            ],
            'unit_id'     => [
                'required',
                'integer',
                Rule::exists('units', 'id')
                    ->where('is_active', true),
            ],
            'currency_id' => [
                'required',
                'integer',
                Rule::exists('currencies', 'id')
                    ->where('is_active', true),
            ],
            'sku'         => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku'),
            ],
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price'       => [
                'required',
                'numeric',
                'gt:0',
                'regex:/^\d{1,16}(?:\.\d{1,4})?$/',
            ],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}
