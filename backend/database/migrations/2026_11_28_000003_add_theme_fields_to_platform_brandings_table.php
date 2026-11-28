<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_brandings', function (Blueprint $table) {
            if (! Schema::hasColumn('platform_brandings', 'theme_mode')) {
                $table->string('theme_mode', 16)->default('dark')->after('primary_color');
            }
            if (! Schema::hasColumn('platform_brandings', 'accent_color')) {
                $table->string('accent_color', 20)->default('#8b5cf6')->after('theme_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('platform_brandings', function (Blueprint $table) {
            $table->dropColumn(['theme_mode', 'accent_color']);
        });
    }
};
