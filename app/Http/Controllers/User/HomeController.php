<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index()
    {
        return view('user.booking.home');
    }
    public function about()
    {
        return view('user.about');
    }
    public function contact()
    {
        return view('user.contact');
    }
    public function blog()
    {
        return view('user.blog');
    }

    public function detail($slug)
    {
        return view('user.booking.detail', compact('slug'));
    }

    public function bookings(\Illuminate\Http\Request $request, $id)
    {
        // Chặn người dùng chưa đăng nhập (không có token trong cookie)
        if (!$request->hasCookie('token')) {
            return redirect('/?login=1');
        }

        return view('user.booking.booking', compact('id'));
    }
}
