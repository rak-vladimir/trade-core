<?php

namespace App\Http\Requests\Api\V1\Catalog;

use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
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
        /** @var Category $category */
        $category = $this->route('category');

        return [
            'parent_id'   => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
                function (string $attribute, mixed $value, \Closure $fail) use ($category): void {
                    if ($value === null) {
                        return;
                    }

                    $parents = Category::query()->pluck('parent_id', 'id');

                    $parentId = (int)$value;

                    while ($parentId !== 0) {
                        if ($parentId === $category->id) {
                            $fail('A category cannot be moved into its own descendant.');

                            return;
                        }

                        $parentId = (int)($parents->get($parentId) ?? 0);
                    }
                },
            ],
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}
