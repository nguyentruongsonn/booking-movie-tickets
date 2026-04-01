<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

use App\Models\Orders;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            
        ]);
    }
}
