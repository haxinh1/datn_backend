<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\OrderOrderStatus;
use Illuminate\Http\Request;

class OrderOrderStatusController extends Controller
{
    public function index()
    {
        return response()->json(OrderOrderStatus::all());
    }
}
