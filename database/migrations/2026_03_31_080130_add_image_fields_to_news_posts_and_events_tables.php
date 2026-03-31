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
        Schema::table('news_posts', function (Blueprint $table) {
            $table->string('cover_image_path')->nullable()->after('content');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->string('poster_image_path')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('poster_image_path');
        });

        Schema::table('news_posts', function (Blueprint $table) {
            $table->dropColumn('cover_image_path');
        });
    }
};
