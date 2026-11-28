<?php

namespace App\Http\Resources\Api\V1\Sales;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Order
 */
class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'number'       => $this->number,
            'status'       => $this->status->value,
            'total_amount' => $this->total_amount,
            'comment'      => $this->comment,

            'customer' => $this->whenLoaded('customer', fn() => [
                'id'   => $this->customer->id,
                'name' => $this->customer->name,
            ]),

            'currency' => $this->whenLoaded('currency', fn() => [
                'id'   => $this->currency->id,
                'code' => $this->currency->code,
            ]),

            'warehouse' => $this->whenLoaded('warehouse', fn() => [
                'id'   => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ]),

            'user' => $this->whenLoaded('user', fn() => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),

            'items' => $this->whenLoaded('items', fn() => $this->items->map(fn($item) => [
                'id'          => $item->id,
                'product'     => [
                    'id'   => $item->product->id,
                    'sku'  => $item->product->sku,
                    'name' => $item->product->name,
                ],
                'quantity'    => $item->quantity,
                'unit_price'  => $item->unit_price,
                'total_price' => $item->total_price,
            ])
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
