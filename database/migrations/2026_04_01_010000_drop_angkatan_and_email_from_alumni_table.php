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

        if (Schema::hasIndex('alumni', 'alumni_jurusan_angkatan_index')) {
            Schema::table('alumni', function (Blueprint $table): void {
                $table->dropIndex('alumni_jurusan_angkatan_index');
            });
        }

        if (Schema::hasIndex('alumni', 'alumni_email_unique')) {
            Schema::table('alumni', function (Blueprint $table): void {
                $table->dropUnique('alumni_email_unique');
            });
        }

        $dropColumns = [];

        if (Schema::hasColumn('alumni', 'angkatan')) {
            $dropColumns[] = 'angkatan';
        }

        if (Schema::hasColumn('alumni', 'email')) {
            $dropColumns[] = 'email';
        }

        if ($dropColumns !== []) {
            Schema::table('alumni', function (Blueprint $table) use ($dropColumns): void {
                $table->dropColumn($dropColumns);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('alumni')) {
            return;
        }

        if (! Schema::hasColumn('alumni', 'email')) {
            Schema::table('alumni', function (Blueprint $table): void {
                $table->string('email')->nullable()->unique()->after('nim');
            });
        }

        if (! Schema::hasColumn('alumni', 'angkatan')) {
            Schema::table('alumni', function (Blueprint $table): void {
                $table->unsignedSmallInteger('angkatan')->nullable()->after('jurusan');
            });
        }

        if (
            Schema::hasColumn('alumni', 'jurusan')
            && Schema::hasColumn('alumni', 'angkatan')
            && ! Schema::hasIndex('alumni', 'alumni_jurusan_angkatan_index')
        ) {
            Schema::table('alumni', function (Blueprint $table): void {
                $table->index(['jurusan', 'angkatan'], 'alumni_jurusan_angkatan_index');
            });
        }
    }
};
