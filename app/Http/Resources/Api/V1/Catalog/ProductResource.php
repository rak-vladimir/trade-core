<?php

namespace App\Http\Resources\Api\V1\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Product
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'sku'         => $this->sku,
            'name'        => $this->name,
            'description' => $this->description,
            'price'       => $this->price,
            'is_active'   => $this->is_active,

            'category' => CategoryResource::make(
                $this->whenLoaded('category'),
            ),

            'unit' => $this->whenLoaded('unit', fn() => [
                'id'             => $this->unit->id,
                'code'           => $this->unit->code,
                'decimal_places' => $this->unit->decimal_places,
            ]),

            'currency' => $this->whenLoaded('currency', fn() => [
                'id'             => $this->currency->id,
                'code'           => $this->currency->code,
                'name'           => $this->currency->name,
                'symbol'         => $this->currency->symbol,
                'decimal_places' => $this->currency->decimal_places,
            ]),

            'created_at' => $this->created_at,
        ];
    }
}
