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
        'maintenance_enabled',
        'maintenance_ends_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'api_key' => 'encrypted',
        'maintenance_enabled' => 'boolean',
        'maintenance_ends_at' => 'datetime',
    ];
}
