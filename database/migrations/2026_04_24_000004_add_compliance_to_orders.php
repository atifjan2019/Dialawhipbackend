<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('delivery_tier', ['standard', 'priority', 'super'])
                ->default('standard')
                ->after('delivery_fee_pence');
            $table->boolean('statement_of_use_accepted')->default(false)->after('delivery_tier');
            $table->boolean('n2o_agreement_accepted')->default(false)->after('statement_of_use_accepted');

            $table->index(['delivery_tier', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['delivery_tier', 'status']);
            $table->dropColumn(['delivery_tier', 'statement_of_use_accepted', 'n2o_agreement_accepted']);
        });
    }
};
