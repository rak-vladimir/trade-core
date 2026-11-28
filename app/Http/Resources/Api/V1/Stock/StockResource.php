<?php

namespace App\Http\Resources\Api\V1\Stock;

use App\Http\Resources\Api\V1\Catalog\ProductResource;
use App\Http\Resources\Api\V1\Warehouse\WarehouseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Stock
 */
class StockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'warehouse'  => WarehouseResource::make(
                $this->whenLoaded('warehouse'),
            ),
            'product'    => ProductResource::make(
                $this->whenLoaded('product'),
            ),
            'quantity'   => $this->quantity,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
