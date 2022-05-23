<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceOnAddNewsAboutAddPair extends Model
{
    use HasFactory;

    protected $fillable = [
        'base_coin',
        'quote_coin',
        'date_point_1',
        'date_point_1_percent',
        'date_point_2',
        'date_point_2_percent',
        'date_point_3',
        'date_point_3_percent',
        'date_point_4',
        'date_point_4_percent',
    ];
}
