<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Merchandise extends Model
{
    protected $fillable = [
        'code',
        'name',
        'points_cost',
        'stock',
        'meta',
    ];
    protected $casts = [
        'meta' => 'array',
    ];
}
