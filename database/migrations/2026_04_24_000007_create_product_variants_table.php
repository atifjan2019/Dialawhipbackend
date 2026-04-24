<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->string('label', 120);                         // e.g. "2 tanks for £80"
            $table->integer('price_pence');                       // price for this variant (total, not per-unit)
            $table->integer('qty_multiplier')->default(1);        // units represented (e.g. 2, 3, 6)
            $table->integer('stock_count')->nullable();
            $table->string('sku', 80)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'is_active', 'sort_order']);
            $table->unique(['product_id', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
