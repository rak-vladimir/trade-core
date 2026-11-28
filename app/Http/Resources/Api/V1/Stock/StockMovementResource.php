<?php

namespace App\Http\Resources\Api\V1\Stock;

use App\Http\Resources\Api\V1\Catalog\ProductResource;
use App\Http\Resources\Api\V1\Warehouse\WarehouseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\StockMovement
 */
class StockMovementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'type'           => $this->type,
            'quantity'       => $this->quantity,
            'product'        => ProductResource::make(
                $this->whenLoaded('product'),
            ),
            'from_warehouse' => WarehouseResource::make(
                $this->whenLoaded('fromWarehouse'),
            ),
            'to_warehouse'   => WarehouseResource::make(
                $this->whenLoaded('toWarehouse'),
            ),
            'reference'      => $this->reference,
            'comment'        => $this->comment,
            'user'           => $this->whenLoaded('user', fn() => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'created_at'     => $this->created_at,
        ];
    }
}
