<?php

namespace App\Http\Requests\Api\V1\Stock;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexStockMovementRequest extends FormRequest
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
            'type'         => [
                'sometimes',
                Rule::in(['receipt', 'write_off', 'transfer']),
            ],
            'product_id'   => ['sometimes', 'integer', 'exists:products,id'],
            'warehouse_id' => ['sometimes', 'integer', 'exists:warehouses,id'],
            'date_from'    => ['sometimes', 'date'],
            'date_to'      => ['sometimes', 'date', 'after_or_equal:date_from'],
            'per_page'     => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
