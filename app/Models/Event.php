<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'poster_image_path',
        'event_date',
        'location',
        'registration_url',
        'is_published',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'poster_image_url',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'event_date' => 'date',
        'is_published' => 'boolean',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    protected function posterImageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->poster_image_path
            ? asset('storage/'.$this->poster_image_path)
            : null);
    }
}
