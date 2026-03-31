<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alumni extends Model
{
    protected $table = 'alumni';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'nim',
        'email',
        'photo_url',
        'no_telepon',
        'jurusan',
        'angkatan',
        'tahun_lulus',
        'pekerjaan',
        'organisasi',
        'fakultas',
        'instansi',
        'alamat',
        'integration_payload',
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
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'angkatan' => 'integer',
        'tahun_lulus' => 'integer',
        'integration_payload' => 'array',
        'tanggal_lahir' => 'date',
        'ipk' => 'decimal:2',
        'status_bekerja' => 'boolean',
    ];
}
