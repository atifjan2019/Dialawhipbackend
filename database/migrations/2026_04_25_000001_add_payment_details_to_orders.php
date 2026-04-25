<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('stripe_payment_intent_id');
            $table->integer('amount_paid_pence')->nullable()->after('paid_at');
            $table->string('payment_currency', 3)->nullable()->after('amount_paid_pence');
            $table->string('card_brand', 30)->nullable()->after('payment_currency');
            $table->string('card_last4', 4)->nullable()->after('card_brand');
            $table->string('payment_method_type', 30)->nullable()->after('card_last4');
            $table->string('receipt_url', 500)->nullable()->after('payment_method_type');
            $table->string('refund_id', 80)->nullable()->after('receipt_url');
            $table->timestamp('refunded_at')->nullable()->after('refund_id');
            $table->integer('amount_refunded_pence')->nullable()->after('refunded_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'paid_at',
                'amount_paid_pence',
                'payment_currency',
                'card_brand',
                'card_last4',
                'payment_method_type',
                'receipt_url',
                'refund_id',
                'refunded_at',
                'amount_refunded_pence',
            ]);
        });
    }
};
