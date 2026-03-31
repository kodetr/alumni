<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumni', function (Blueprint $table) {
            if (Schema::hasColumn('alumni', 'angkatan')) {
                $table->dropColumn('angkatan');
            }
        });

        Schema::table('alumni', function (Blueprint $table) {
            if (! Schema::hasColumn('alumni', 'organisasi')) {
                $table->string('organisasi')->nullable()->after('pekerjaan');
            }
            if (! Schema::hasColumn('alumni', 'fakultas')) {
                $table->string('fakultas')->nullable()->after('organisasi');
            }
            if (! Schema::hasColumn('alumni', 'email_kampus')) {
                $table->string('email_kampus')->nullable()->after('email');
            }
            if (! Schema::hasColumn('alumni', 'email_pribadi')) {
                $table->string('email_pribadi')->nullable()->after('email_kampus');
            }
            if (! Schema::hasColumn('alumni', 'link_dokumen_tambahan')) {
                $table->string('link_dokumen_tambahan')->nullable()->after('no_telepon_orang_tua');
            }
        });
    }

    public function down(): void {}
};
