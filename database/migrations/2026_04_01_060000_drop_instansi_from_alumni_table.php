<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('alumni') || ! Schema::hasColumn('alumni', 'instansi')) {
            return;
        }

        Schema::table('alumni', function (Blueprint $table): void {
            $table->dropColumn('instansi');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('alumni') || Schema::hasColumn('alumni', 'instansi')) {
            return;
        }

        Schema::table('alumni', function (Blueprint $table): void {
            $table->string('instansi')->nullable()->after('pekerjaan');
        });
    }
};
