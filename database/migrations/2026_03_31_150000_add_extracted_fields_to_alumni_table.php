<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumni', function (Blueprint $table) {
            $table->dropColumn('angkatan');
        });

        Schema::table('alumni', function (Blueprint $table) {
            $table->string('organisasi')->nullable()->after('pekerjaan');
            $table->string('fakultas')->nullable()->after('organisasi');
            $table->string('email_kampus')->nullable()->after('email');
            $table->string('email_pribadi')->nullable()->after('email_kampus');
            $table->string('tempat_lahir')->nullable()->after('alamat');
            $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
            $table->string('agama')->nullable()->after('tanggal_lahir');
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable()->after('agama');
            $table->string('no_ktp', 30)->nullable()->after('jenis_kelamin');
            $table->decimal('ipk', 3, 2)->nullable()->after('no_ktp');
            $table->string('predikat')->nullable()->after('ipk');
            $table->text('judul_skripsi')->nullable()->after('predikat');
            $table->string('pembimbing_1')->nullable()->after('judul_skripsi');
            $table->string('pembimbing_2')->nullable()->after('pembimbing_1');
            $table->string('ukuran_toga')->nullable()->after('pembimbing_2');
            $table->boolean('status_bekerja')->nullable()->after('ukuran_toga');
            $table->string('nama_ayah')->nullable()->after('status_bekerja');
            $table->string('nama_ibu')->nullable()->after('nama_ayah');
            $table->string('no_telepon_orang_tua', 30)->nullable()->after('nama_ibu');
            $table->string('link_dokumen_tambahan')->nullable()->after('no_telepon_orang_tua');
        });
    }

    public function down(): void
    {
        Schema::table('alumni', function (Blueprint $table) {
            $table->dropColumn([
                'organisasi',
                'fakultas',
                'email_kampus',
                'email_pribadi',
                'tempat_lahir',
                'tanggal_lahir',
                'agama',
                'jenis_kelamin',
                'no_ktp',
                'ipk',
                'predikat',
                'judul_skripsi',
                'pembimbing_1',
                'pembimbing_2',
                'ukuran_toga',
                'status_bekerja',
                'nama_ayah',
                'nama_ibu',
                'no_telepon_orang_tua',
                'link_dokumen_tambahan',
            ]);
            $table->unsignedSmallInteger('angkatan')->nullable();
        });
    }
};
