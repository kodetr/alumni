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
            if (! Schema::hasColumn('alumni', 'tempat_lahir')) {
                $table->string('tempat_lahir')->nullable()->after('alamat');
            }
            if (! Schema::hasColumn('alumni', 'tanggal_lahir')) {
                $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
            }
            if (! Schema::hasColumn('alumni', 'agama')) {
                $table->string('agama')->nullable()->after('tanggal_lahir');
            }
            if (! Schema::hasColumn('alumni', 'jenis_kelamin')) {
                $table->enum('jenis_kelamin', ['L', 'P'])->nullable()->after('agama');
            }
            if (! Schema::hasColumn('alumni', 'no_ktp')) {
                $table->string('no_ktp', 30)->nullable()->after('jenis_kelamin');
            }
            if (! Schema::hasColumn('alumni', 'ipk')) {
                $table->decimal('ipk', 3, 2)->nullable()->after('no_ktp');
            }
            if (! Schema::hasColumn('alumni', 'predikat')) {
                $table->string('predikat')->nullable()->after('ipk');
            }
            if (! Schema::hasColumn('alumni', 'judul_skripsi')) {
                $table->text('judul_skripsi')->nullable()->after('predikat');
            }
            if (! Schema::hasColumn('alumni', 'pembimbing_1')) {
                $table->string('pembimbing_1')->nullable()->after('judul_skripsi');
            }
            if (! Schema::hasColumn('alumni', 'pembimbing_2')) {
                $table->string('pembimbing_2')->nullable()->after('pembimbing_1');
            }
            if (! Schema::hasColumn('alumni', 'ukuran_toga')) {
                $table->string('ukuran_toga')->nullable()->after('pembimbing_2');
            }
            if (! Schema::hasColumn('alumni', 'status_bekerja')) {
                $table->boolean('status_bekerja')->nullable()->after('ukuran_toga');
            }
            if (! Schema::hasColumn('alumni', 'nama_ayah')) {
                $table->string('nama_ayah')->nullable()->after('status_bekerja');
            }
            if (! Schema::hasColumn('alumni', 'nama_ibu')) {
                $table->string('nama_ibu')->nullable()->after('nama_ayah');
            }
            if (! Schema::hasColumn('alumni', 'no_telepon_orang_tua')) {
                $table->string('no_telepon_orang_tua', 30)->nullable()->after('nama_ibu');
            }
            if (! Schema::hasColumn('alumni', 'link_dokumen_tambahan')) {
                $table->string('link_dokumen_tambahan')->nullable()->after('no_telepon_orang_tua');
            }
        });
    }

    public function down(): void {}
};
