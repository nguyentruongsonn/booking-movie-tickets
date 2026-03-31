<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

use App\Models\Movies;
use Illuminate\Http\Request;

class MoviesController extends Controller
{
    // ================= 1️⃣ Lấy danh sách phim đang chiếu =================
    public function dangChieu()
    {
        $phims = Movies::dangChieu()->with(['categories', 'showtimes.format'])->get();

        return response()->json(
            $phims->map(function ($phim) {
            return [
                'id' => $phim->id,
                'ten_phim' => $phim->ten_phim,
                'slug' => $phim->slug,
                'dang_chieu' => $phim->dang_chieu,
                'sap_chieu' => $phim->sap_chieu,
                'thoi_luong' => $phim->getThoiLuong(),
                'categories' => $phim->categories,
                'showtimes' => $phim->showtimes
            ];
        })
        );
    }

    // ================= 2️⃣ Lấy danh sách phim sắp chiếu =================
    public function sapChieu()
    {
        $phims = Movies::sapChieu()->with(['categories', 'showtimes.format'])->get();

        return response()->json(
            $phims->map(function ($phim) {
            return [
                'id' => $phim->id,
                'ten_phim' => $phim->ten_phim,
                'slug' => $phim->slug,
                'dang_chieu' => $phim->dang_chieu,
                'sap_chieu' => $phim->sap_chieu,
                'thoi_luong' => $phim->getThoiLuong(),
                'categories' => $phim->categories,
                'showtimes' => $phim->showtimes
            ];
        })
        );
    }

    // ================= 3️⃣ Lấy chi tiết 1 phim =================
    public function show($slug)
    {
        $phim = Movies::with(['categories', 'showtimes.format'])->where('slug', $slug)->first();

        if (!$phim) {
            return response()->json([
                'message' => 'Không tìm thấy phim'
            ], 404);
        }
        return response()->json([
            'id' => $phim->id,
            'ten_phim' => $phim->ten_phim,
            'slug' => $phim->slug,
            'ten_goc' => $phim->ten_goc,
            'mo_ta' => $phim->mo_ta,
            'thoi_luong' => $phim->getThoiLuong(),
            'ngay_khoi_chieu' => $phim->ngay_khoi_chieu,
            'dang_chieu' => $phim->dang_chieu,
            'ngay_chieu' => $phim->ngay_chieu,
            'sap_chieu' => $phim->sap_chieu,
            'do_tuoi' => $phim->do_tuoi,
            'dao_dien' => $phim->dao_dien,
            'dien_vien' => $phim->dien_vien,
            'poster_url' => $phim->poster_url,
            'trailer_url' => $phim->trailer_url,
            'categories' => $phim->categories,
            'showtimes' => $phim->showtimes
        ]);
    }

    // ================= 4️⃣ Lấy tất cả phim =================
    public function allMovies()
    {
        $phims = Movies::with(['categories', 'showtimes.format'])->get();

        return response()->json(
            $phims->map(function ($phim) {
            return [
                'id' => $phim->id,
                'ten_phim' => $phim->ten_phim,
                'slug' => $phim->slug,
                'poster_url'=> $phim->poster_url,
                'dang_chieu' => $phim->dang_chieu,
                'sap_chieu' => $phim->sap_chieu,
                'thoi_luong' => $phim->getThoiLuong(),
                'categories' => $phim->categories,
                'showtimes' => $phim->showtimes
            ];
        })
        );
    }
}