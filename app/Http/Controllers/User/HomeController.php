<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('user.home');
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
    public function booking()
    {
        return view('user.booking.index');
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
