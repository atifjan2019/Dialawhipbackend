<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatalogController extends Controller
{
    public function categories(): AnonymousResourceCollection
    {
        $categories = Category::where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')->get();

        return CategoryResource::collection($categories);
    }

    public function products(Request $request): AnonymousResourceCollection
    {
        $query = Product::query()
            ->with(['category', 'variants' => fn ($q) => $q->where('is_active', true)])
            ->where('is_active', true);

        if ($categorySlug = $request->query('filter.category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug)->where('is_active', true));
        }

        if ($search = $request->query('filter.search')) {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)->orWhere('description', 'like', $term);
            });
        }

        $products = $query->orderBy('name')->paginate(min((int) $request->query('limit', 25), 100));

        return ProductResource::collection($products);
    }

    public function product(Product $product): JsonResponse
    {
        abort_unless($product->is_active, 404);

        $product->load(['category', 'variants' => fn ($q) => $q->where('is_active', true)]);

        return response()->json(['data' => new ProductResource($product)]);
    }
}
