<?php

namespace App\Http\Requests\Api\V1\Stock;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockWriteOffRequest extends FormRequest
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
            'product_id'        => [
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->where(fn($query) => $query
                        ->where('is_active', true)
                        ->whereNull('deleted_at')),
            ],
            'from_warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')
                    ->where('is_active', true),
            ],
            'quantity'          => [
                'required',
                'numeric',
                'gt:0',
                'regex:/^\d{1,16}(?:\.\d{1,4})?$/',
            ],
            'reference'         => ['nullable', 'string', 'max:255'],
            'comment'           => ['nullable', 'string', 'max:2000'],
        ];
    }
}
