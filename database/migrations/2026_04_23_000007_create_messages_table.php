<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('customer_id')->constrained('users')->cascadeOnDelete();
            $table->enum('channel', ['email', 'sms']);
            $table->enum('direction', ['outbound', 'inbound']);
            $table->string('template_key')->nullable();
            $table->text('content');
            $table->string('provider_id')->nullable();
            $table->enum('status', ['queued', 'sent', 'delivered', 'failed'])->default('queued');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['customer_id', 'created_at']);
            $table->index(['order_id', 'created_at']);
            $table->index('provider_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
