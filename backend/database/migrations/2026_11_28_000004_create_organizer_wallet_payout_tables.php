<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizer_payout_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organizer_id')->unique();
            $table->unsignedBigInteger('account_id');
            $table->string('bank_name', 150);
            $table->string('account_number', 64);
            $table->string('account_name', 150);
            $table->string('currency', 10)->default('NGN');
            $table->timestamps();

            $table->index('account_id');
        });

        Schema::create('organizer_payout_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organizer_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('amount', 14, 2);
            $table->string('currency', 10)->default('NGN');
            $table->string('status', 24)->default('PENDING'); // PENDING | PAID | REJECTED
            $table->string('bank_name', 150);
            $table->string('account_number', 64);
            $table->string('account_name', 150);
            $table->string('transfer_reference', 150)->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['organizer_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizer_payout_requests');
        Schema::dropIfExists('organizer_payout_accounts');
    }
};
