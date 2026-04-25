<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::with(['category', 'variants'])
            ->when($request->query('filter.category'), fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->orderBy('name')
            ->paginate(min((int) $request->query('limit', 25), 100));

        return ProductResource::collection($products);
    }

    /**
     * Look up a product by ULID (ignoring the slug route-key) so the admin panel
     * can edit products by their stable id even if the slug changes.
     */
    public function show(string $id): JsonResponse
    {
        $product = Product::with(['category', 'variants'])
            ->where('id', $id)
            ->firstOrFail();

        return response()->json(['data' => new ProductResource($product)]);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $variants = $data['variants'] ?? null;
        $data['options_json'] = $data['options'] ?? null;
        unset($data['options'], $data['variants']);

        $product = DB::transaction(function () use ($data, $variants) {
            $product = Product::create($data);
            if (is_array($variants)) {
                $this->syncVariants($product, $variants);
            }
            return $product;
        });

        return response()->json([
            'data' => new ProductResource($product->load(['category', 'variants'])),
        ], 201);
    }

    public function update(ProductRequest $request, string $product): JsonResponse
    {
        $model = Product::where('id', $product)->firstOrFail();

        $data = $request->validated();
        $variants = array_key_exists('variants', $data) ? $data['variants'] : null;
        $data['options_json'] = $data['options'] ?? null;
        unset($data['options'], $data['variants']);

        DB::transaction(function () use ($model, $data, $variants) {
            $model->update($data);
            if (is_array($variants)) {
                $this->syncVariants($model, $variants);
            }
        });

        return response()->json([
            'data' => new ProductResource($model->load(['category', 'variants'])),
        ]);
    }

    public function destroy(string $product): JsonResponse
    {
        Product::where('id', $product)->firstOrFail()->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /**
     * Upload a product image (featured or gallery).
     *
     * Body (multipart):
     *   file  : image file (required, ≤ 6 MB)
     *
     * Response:
     *   { data: { url, path, disk } }
     *
     * The frontend uses the returned `url` either as the product's
     * `image_url` (featured) or appends it to `gallery_urls`.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:6144', 'mimes:jpg,jpeg,png,webp,gif'],
        ]);

        $file = $request->file('file');
        $path = $file->store('products', 'public');
        $url = Storage::disk('public')->url($path);

        return response()->json([
            'data' => [
                'url' => $url,
                'path' => $path,
                'disk' => 'public',
            ],
        ], 201);
    }

    /**
     * Upsert variants for a product.  Any existing variant not represented in
     * $rows (by id) is deleted.  New rows (no id) are created.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncVariants(Product $product, array $rows): void
    {
        $keepIds = [];

        foreach ($rows as $index => $row) {
            $payload = [
                'label' => (string) $row['label'],
                'price_pence' => (int) $row['price_pence'],
                'qty_multiplier' => (int) ($row['qty_multiplier'] ?? 1),
                'stock_count' => isset($row['stock_count']) && $row['stock_count'] !== '' ? (int) $row['stock_count'] : null,
                'sku' => $row['sku'] ?? null,
                'sort_order' => (int) ($row['sort_order'] ?? $index),
                'is_active' => array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true,
            ];

            if (! empty($row['id'])) {
                $variant = ProductVariant::where('product_id', $product->id)->where('id', $row['id'])->first();
                if ($variant) {
                    $variant->update($payload);
                    $keepIds[] = $variant->id;
                    continue;
                }
            }

            $created = $product->variants()->create($payload);
            $keepIds[] = $created->id;
        }

        // Remove variants the admin has deleted.
        ProductVariant::where('product_id', $product->id)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
}
