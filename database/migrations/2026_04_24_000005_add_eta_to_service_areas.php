<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_areas', function (Blueprint $table) {
            $table->unsignedSmallInteger('eta_standard_minutes')->default(25)->after('delivery_fee_pence');
            $table->unsignedSmallInteger('eta_priority_minutes')->default(12)->after('eta_standard_minutes');
            $table->unsignedSmallInteger('priority_fee_pence')->default(500)->after('eta_priority_minutes');
            $table->unsignedSmallInteger('super_fee_pence')->default(1500)->after('priority_fee_pence');
        });
    }

    public function down(): void
    {
        Schema::table('service_areas', function (Blueprint $table) {
            $table->dropColumn(['eta_standard_minutes', 'eta_priority_minutes', 'priority_fee_pence', 'super_fee_pence']);
        });
    }
};
