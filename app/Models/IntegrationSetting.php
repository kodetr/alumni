<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'endpoint',
        'api_key',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'api_key' => 'encrypted',
    ];
}
