@extends('layouts.app')

@section('content')
    <section class="hero-wrap hero-wrap-2 position-relative" id="movie-banner">
        <div class="overlay bg-dark position-absolute w-100 h-100" style="opacity: 0.6; top: 0; left: 0;"></div>
        <div class="container-fluid position-relative z-1">
            <div class="row no-gutters slider-text align-items-center justify-content-center" style="min-height: 60vh;">
                <div class="col-md-9 text-center ftco-animate">
                    <div class="video-play-button d-inline-block rounded-circle bg-white shadow-lg" id="btn-watch-trailer"
                        style="cursor: pointer; width: 90px; height: 90px; transition: transform 0.2s;">
                        <span
                            class="fa fa-play text-primary d-flex align-items-center justify-content-center h-100 fs-1"></span>
                    </div>
                    <div class="mt-5">
                        <a href="#booking-section"
                            class="btn btn-primary btn-lg radius-md px-5 py-3 fw-bold shadow-lg text-uppercase tracking-wider">
                            Đặt vé ngay
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ftco-section bg-slate-50">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-8 col-lg-9">
                    <div class="saas-card mb-5">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <img src="" id="det-poster" class="img-fluid radius-md shadow-sm w-100" alt="Poster">
                            </div>
                            <div class="col-md-8">
                                <h2 id="det-name-detail" class="text-slate-900 fw-bold mb-3"></h2>
                                <div class="d-flex flex-column gap-2 text-slate-700">
                                    <div><strong class="text-slate-900">Thể loại:</strong> <span id="det-categories"
                                            class="badge bg-slate-200 text-slate-800 fs-6 fw-medium px-3 py-2 radius-md"></span>
                                    </div>
                                    <div><strong class="text-slate-900">Đạo diễn:</strong> <span id="det-director"></span>
                                    </div>
                                    <div><strong class="text-slate-900">Diễn viên:</strong> <span id="det-actors"></span>
                                    </div>
                                    <div><strong class="text-slate-900">Thời lượng:</strong> <span id="det-duration"></span>
                                    </div>
                                    <div><strong class="text-slate-900">Ngày khởi chiếu:</strong> <span
                                            id="det-start-date"></span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="saas-card mb-5">
                        <h4 class="text-slate-900 fw-bold mb-4 d-flex align-items-center gap-2">Nội dung phim</h4>
                        <p id="det-desc" class="text-slate-600 fs-6 lh-lg text-justify"></p>
                    </div>

                    <div class="saas-card" id="booking-section">
                        <h4 class="text-slate-900 fw-bold mb-4 d-flex align-items-center gap-2">Lịch chiếu</h4>

                        <div id="date-picker" class="d-flex mb-4 overflow-auto pb-2 gap-2"></div>

                        <div id="showtime-container"></div>
                    </div>
                </div>

                <div class="col-md-4 col-lg-3">
                    <div class="saas-card sticky-top" style="top: 100px;">
                        <h5 class="fw-bold text-slate-800 mb-4">Gợi ý phim</h5>
                        <div class="text-slate-500 small text-center p-4">Tính năng đang cập nhật</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="trailerModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content bg-slate-900 border-0 shadow-lg radius-md overflow-hidden">
                <div class="modal-header border-0 pb-0 pt-3 pe-3 position-absolute w-100 z-1 d-flex justify-content-end">
                    <button type="button" class="btn-close btn-close-white" data-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="embed-responsive embed-responsive-16by9 bg-black">
                        <iframe class="embed-responsive-item w-100" style="min-height: 500px;" src="" id="video-frame"
                            allowfullscreen allow="autoplay"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const movieSlug = window.location.pathname.split('/').pop();
    </script>
    <script src="{{ asset('js/user/movies.js') }}"></script>
@endsection