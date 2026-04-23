<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

use App\Models\Movie;
use Illuminate\Http\Request;

class MoviesController extends Controller
{
    // ================= 1️⃣ Get now showing movies =================
    public function nowShowing()
    {
        $movies = Movie::isShowing()->with(['categories', 'showtimes.screen'])->get();
 
        return $this->ok($movies->map(function ($movie) {
            return [
                'id'             => $movie->id,
                'title'          => $movie->title,
                'slug'           => $movie->slug,
                'is_showing'     => $movie->is_showing,
                'is_coming_soon' => $movie->is_coming_soon,
                'duration'       => $movie->duration,
                'poster_url'     => $movie->poster_url,
                'categories'     => $movie->categories,
                'showtimes'      => $movie->showtimes
            ];
        }));
    }
 
    // ================= 2️⃣ Get coming soon movies =================
    public function comingSoon()
    {
        $movies = Movie::isComingSoon()->with(['categories', 'showtimes.screen'])->get();
 
        return $this->ok($movies->map(function ($movie) {
            return [
                'id'             => $movie->id,
                'title'          => $movie->title,
                'slug'           => $movie->slug,
                'is_showing'     => $movie->is_showing,
                'is_coming_soon' => $movie->is_coming_soon,
                'duration'       => $movie->duration,
                'poster_url'     => $movie->poster_url,
                'categories'     => $movie->categories,
                'showtimes'      => $movie->showtimes
            ];
        }));
    }
 
    // ================= 3️⃣ Get movie details =================
    public function show($slug)
    {
        $movie = Movie::with(['categories', 'showtimes.screen'])->where('slug', $slug)->first();
 
        if (!$movie) {
            return $this->notFound('Phim không tồn tại hoặc đã bị xóa.');
        }
 
        return $this->ok([
            'id'             => $movie->id,
            'title'          => $movie->title,
            'slug'           => $movie->slug,
            'original_title' => $movie->original_title,
            'description'    => $movie->description,
            'duration'       => $movie->duration,
            'release_date'   => $movie->release_date,
            'is_showing'     => $movie->is_showing,
            'is_coming_soon' => $movie->is_coming_soon,
            'age_rating'     => $movie->age_rating,
            'director'       => $movie->director,
            'cast'           => $movie->cast,
            'poster_url'     => $movie->poster_url,
            'trailer_url'    => $movie->trailer_url,
            'categories'     => $movie->categories,
            'showtimes'      => $movie->showtimes
        ]);
    }
}