<?php

namespace App\Http\Controllers\Api\V1\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Warehouse\IndexWarehouseRequest;
use App\Http\Requests\Api\V1\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Api\V1\Warehouse\UpdateWarehouseRequest;
use App\Http\Resources\Api\V1\Warehouse\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexWarehouseRequest $request): AnonymousResourceCollection
    {
        $warehouses = Warehouse::query()
            ->when(
                $request->has('is_active'),
                fn($query) => $query->where('is_active', $request->boolean('is_active')),
                fn($query) => $query->where('is_active', true),
            )
            ->orderBy('name')
            ->get();

        return WarehouseResource::collection($warehouses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWarehouseRequest $request):Response
    {
        $warehouse = Warehouse::create($request->validated());

        return new WarehouseResource($warehouse)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Warehouse $warehouse): WarehouseResource
    {
        return new WarehouseResource($warehouse);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): WarehouseResource
    {
        $warehouse->update($request->validated());

        return new WarehouseResource($warehouse->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $hasDependencies = $warehouse->stocks()->exists()
            || $warehouse->outgoingMovements()->exists()
            || $warehouse->incomingMovements()->exists();

        if ($hasDependencies) {
            return response()->json([
                'message' => 'Warehouse cannot be deleted because it has related stock or movement records.',
            ], Response::HTTP_CONFLICT);
        }

        $warehouse->delete();

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
