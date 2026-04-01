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

    public function bookings($id)
    {
        return view('user.booking.booking', compact('id'));
    }
}
