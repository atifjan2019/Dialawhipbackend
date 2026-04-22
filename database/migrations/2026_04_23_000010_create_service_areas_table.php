<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_areas', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('postcode_prefix', 8);
            $table->integer('delivery_fee_pence');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('postcode_prefix');
            $table->index(['is_active', 'postcode_prefix']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_areas');
    }
};
