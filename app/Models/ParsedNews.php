<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParsedNews extends Model
{
    protected $fillable = [
        'title',
        'url',
        'site_about',
        'site_source',
        'published_date',
        'is_new',
    ];

    protected $casts = [
        'published_date' => 'datetime',
    ];
}
