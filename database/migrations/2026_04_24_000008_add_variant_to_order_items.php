<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->ulid('product_variant_id')->nullable()->after('product_id');
            $table->string('variant_label', 120)->nullable()->after('product_variant_id');

            $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->index('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->dropIndex(['product_variant_id']);
            $table->dropColumn(['product_variant_id', 'variant_label']);
        });
    }
};
