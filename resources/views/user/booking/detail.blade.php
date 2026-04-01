@extends('layouts.app')

@section('content')
<section class="hero-wrap hero-wrap-2" id="movie-banner" >
    <div class="overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5);"></div>
    <div class="container-fluid">
        <div class="row no-gutters slider-text align-items-center justify-content-center" style="min-height: 600px;">
            <div class="col-md-9 text-center ftco-animate">
                <div class="video-play-button" id="btn-watch-trailer" style="cursor: pointer;">
                    <span class="fa fa-play" style="font-size: 50px; color: #f1592a; padding: 30px; "></span>
                </div>
                <div class="mt-4">
                    <a href="#booking-section" class="btn btn-primary py-3 px-5" style="border-radius: 30px; font-weight: bold;">
                        <i class="fa fa-ticket mr-2"></i> ĐẶT VÉ NGAY
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="ftco-section bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-9">
                <div class="row">
                    <div class="col-md-4">
                        <div class="project-wrap shadow-sm">
                            <img src="" id="det-poster" class="img-fluid" style="border-radius: 10px;" alt="Poster">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <h2 id="det-name-detail" class="text-dark font-weight-bold"></h2>
                        <hr>
                        <p><strong class="text-primary">Thể loại:</strong> <span id="det-categories"></span></p>
                        <p><strong class="text-primary">Đạo diễn:</strong> <span id="det-director"></span></p>
                        <p><strong class="text-primary">Diễn viên:</strong> <span id="det-actors"></span></p>
                        <p><strong class="text-primary">Thời lượng:</strong> <span id="det-duration"></span></p>
                        <p><strong class="text-primary">Ngày khởi chiếu:</strong> <span id="det-start-date"></span></p>

                    </div>
                </div>
                <div class="mt-4">
                    <h4 class="text-primary ">Nội dung phim</h4>
                    <hr>
                    <p id="det-desc" class="text-justify " style="line-height: 1.8;"></p>
                </div>

                <div class="mt-5" id="booking-section">
                    <h4 class="text-primary ">Lịch chiếu</h4>
                    <hr>
                    <div id="date-picker" class="d-flex mb-4 overflow-auto pb-2">
                </div>


                <div id="format-filter" class="mb-3 d-none">
                    <span class="text-muted small fw-bold me-3">ĐỊNH DẠNG:</span>
                    <div id="format-list" class="d-inline-flex gap-2"></div>
                </div>

                <div id="showtime-container"></div>

                </div>
            </div>
            <div class="col-md-3">
                gợi ý phim
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="trailerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="background: #000; border: none;">
            <div class="modal-header" style="border: none; padding: 10px;">
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 0;">
                <div class="embed-responsive embed-responsive-16by9">
                    <iframe class="embed-responsive-item" src="" id="video-frame" allowfullscreen allow="autoplay"></iframe>
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
