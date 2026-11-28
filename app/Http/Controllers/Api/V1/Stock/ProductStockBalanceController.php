<?php

namespace App\Http\Controllers\Api\V1\Stock;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Stock\ProductStockBalanceResource;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\Request;

class ProductStockBalanceController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Product $product): ProductStockBalanceResource
    {
        $stocks = Stock::query()
            ->with('warehouse:id,code,name')
            ->where('product_id', $product->id)
            ->get();

        $totalQuantity = \DB::table('stocks')
            ->where('product_id', $product->id)
            ->selectRaw('COALESCE(SUM(quantity), 0.0000)::text AS total_quantity')
            ->value('total_quantity');

        return new ProductStockBalanceResource([
            'product_id'     => $product->id,
            'total_quantity' => $totalQuantity,
            'warehouses'     => $stocks,
        ]);
    }
}
