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
        'no_telepon',
        'jurusan',
        'angkatan',
        'tahun_lulus',
        'pekerjaan',
        'instansi',
        'alamat',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'angkatan' => 'integer',
        'tahun_lulus' => 'integer',
    ];
}
