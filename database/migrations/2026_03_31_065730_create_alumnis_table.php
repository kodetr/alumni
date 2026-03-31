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
        Schema::create('alumni', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('nim', 30)->unique();
            $table->string('email')->nullable()->unique();
            $table->string('no_telepon', 30)->nullable();
            $table->string('jurusan');
            $table->unsignedSmallInteger('angkatan');
            $table->unsignedSmallInteger('tahun_lulus')->nullable();
            $table->string('pekerjaan')->nullable();
            $table->string('instansi')->nullable();
            $table->text('alamat')->nullable();
            $table->timestamps();

            $table->index(['jurusan', 'angkatan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alumni');
    }
};
