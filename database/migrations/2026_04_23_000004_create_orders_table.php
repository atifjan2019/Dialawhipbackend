<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('reference')->unique();
            $table->foreignUlid('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignUlid('address_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('assigned_driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', [
                'pending', 'confirmed', 'in_prep', 'out_for_delivery',
                'delivered', 'failed', 'cancelled', 'refunded',
            ])->default('pending');
            $table->integer('subtotal_pence');
            $table->integer('delivery_fee_pence')->default(0);
            $table->integer('vat_pence')->default(0);
            $table->integer('total_pence');
            $table->string('stripe_session_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->timestampTz('scheduled_for')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('driver_notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['assigned_driver_id', 'status']);
            $table->index('stripe_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
