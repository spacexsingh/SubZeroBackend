<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PointAction;
use App\Models\Merchandise;

class PointsCatalogController extends Controller
{
    public function actions()
    {
        return response()->json([
            'success' => true,
            'data' => PointAction::select('id','code','name','points')->orderBy('name')->get(),
        ]);
    }

    public function merchandise()
    {
        return response()->json([
            'success' => true,
            'data' => Merchandise::select('id','code','name','points_cost','stock')->orderBy('points_cost')->get(),
        ]);
    }
}
