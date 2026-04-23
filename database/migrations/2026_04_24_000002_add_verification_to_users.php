<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('verification_status', ['unverified', 'pending', 'verified', 'rejected'])
                ->default('unverified')
                ->after('role');
            $table->timestamp('verified_at')->nullable()->after('verification_status');

            $table->index('verification_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['verification_status']);
            $table->dropColumn(['verification_status', 'verified_at']);
        });
    }
};
