<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('alumni')) {
            return;
        }

        if (! Schema::hasColumn('alumni', 'angkatan')) {
            Schema::table('alumni', function (Blueprint $table): void {
                $table->unsignedSmallInteger('angkatan')->nullable()->after('jurusan');
            });
        }

        if (! Schema::hasIndex('alumni', 'alumni_jurusan_angkatan_index')) {
            Schema::table('alumni', function (Blueprint $table): void {
                $table->index(['jurusan', 'angkatan'], 'alumni_jurusan_angkatan_index');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('alumni') || ! Schema::hasColumn('alumni', 'angkatan')) {
            return;
        }

        try {
            Schema::table('alumni', function (Blueprint $table): void {
                $table->dropIndex('alumni_jurusan_angkatan_index');
            });
        } catch (Throwable) {
        }

        Schema::table('alumni', function (Blueprint $table): void {
            $table->dropColumn('angkatan');
        });
    }
};
