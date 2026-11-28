<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_brandings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name', 120)->default('CortDevs Tickets');
            $table->string('primary_color', 20)->default('#cd58dd');
            $table->string('logo_url', 500)->nullable();
            $table->string('dark_logo_url', 500)->nullable();
            $table->string('favicon_url', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_brandings');
    }
};
