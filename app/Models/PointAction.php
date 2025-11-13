<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointAction extends Model
{
    protected $fillable = [
        'code',
        'name',
        'points',
        'meta',
    ];

    protected $casts = [
        'meta' => 'json',
    ];
}
