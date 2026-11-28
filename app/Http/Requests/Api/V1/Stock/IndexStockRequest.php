<?php

namespace App\Http\Requests\Api\V1\Stock;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexStockRequest extends FormRequest
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
            'warehouse_id' => ['sometimes', 'integer', 'exists:warehouses,id'],
            'product_id'   => ['sometimes', 'integer', 'exists:products,id'],
            'search'       => ['sometimes', 'string', 'max:255'],
            'sort'         => ['sometimes', 'in:quantity,created_at'],
            'direction'    => ['sometimes', 'in:asc,desc'],
            'per_page'     => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
