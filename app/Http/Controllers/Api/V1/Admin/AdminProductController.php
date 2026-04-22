<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::with('category')
            ->when($request->query('filter.category'), fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->orderBy('name')
            ->paginate(min((int) $request->query('limit', 25), 100));

        return ProductResource::collection($products);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['options_json'] = $data['options'] ?? null;
        unset($data['options']);

        $product = Product::create($data);

        return response()->json(['data' => new ProductResource($product->load('category'))], 201);
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();
        $data['options_json'] = $data['options'] ?? null;
        unset($data['options']);

        $product->update($data);

        return response()->json(['data' => new ProductResource($product->load('category'))]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }
}
