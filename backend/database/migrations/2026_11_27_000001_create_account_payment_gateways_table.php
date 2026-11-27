<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('provider', 32); // 'paystack' | 'bachs'
            $table->boolean('is_enabled')->default(false);
            $table->string('mode', 16)->default('test'); // 'test' | 'live'
            $table->text('test_public_key')->nullable();
            $table->text('test_secret_key')->nullable();
            $table->text('test_webhook_secret')->nullable();
            $table->text('live_public_key')->nullable();
            $table->text('live_secret_key')->nullable();
            $table->text('live_webhook_secret')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'provider']);
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_payment_gateways');
    }
};
