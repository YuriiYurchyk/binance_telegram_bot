<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradePair extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'status',
        'new',
    ];

    public function scopeActive($q)
    {
        return $q->where('status', 1);
    }

    public function scopeNew($q)
    {
        return $q->where('new', 1);
    }
}
