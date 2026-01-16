<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kpay_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('entity');
            $table->decimal('amount', 15, 2);
            $table->decimal('price', 15, 2)->nullable();
            $table->string('description')->nullable();
            $table->enum('status', ['pending', 'paid', 'cancelled', 'failed'])->default('pending');
            $table->string('currency')->default('AOA');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->json('api_response')->nullable();
            $table->string('user_id')->nullable();
            $table->string('order_id')->nullable();
            $table->timestamps();

            $table->index('reference');
            $table->index('entity');
            $table->index('status');
            $table->index('user_id');
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpay_transactions');
    }
};
