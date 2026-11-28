<?php

namespace App\Http\Requests\Api\V1\Sales;

use App\Enums\OrderStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexOrderRequest extends FormRequest
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
            'search'       => ['sometimes', 'string', 'max:30'],
            'customer_id'  => ['sometimes', 'integer', 'min:1'],
            'warehouse_id' => ['sometimes', 'integer', 'min:1'],
            'status'       => ['sometimes', Rule::enum(OrderStatus::class)],
            'date_from'    => ['sometimes', 'date'],
            'date_to'      => ['sometimes', 'date', 'after_or_equal:date_from'],
            'sort'         => [
                'sometimes',
                Rule::in(['number', 'status', 'total_amount', 'created_at']),
            ],
            'direction'    => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page'     => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
