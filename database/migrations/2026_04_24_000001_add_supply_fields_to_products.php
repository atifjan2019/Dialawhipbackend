<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('brand', 80)->nullable()->after('name');
            $table->boolean('is_age_restricted')->default(false)->after('is_active');
            $table->json('short_spec')->nullable()->after('options_json');

            $table->index(['brand', 'is_active']);
            $table->index(['is_age_restricted', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['brand', 'is_active']);
            $table->dropIndex(['is_age_restricted', 'is_active']);
            $table->dropColumn(['brand', 'is_age_restricted', 'short_spec']);
        });
    }
};
