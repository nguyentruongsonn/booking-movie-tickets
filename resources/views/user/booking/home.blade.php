@extends('layouts.app')

@push('styles')
<style>
  .movie-hero {
    position: relative;
  }

  .movie-hero .overlay {
    background: linear-gradient(90deg, rgba(19, 18, 29, 0.82) 0%, rgba(19, 18, 29, 0.45) 100%);
    z-index: 1;
  }

  .movie-hero-slider,
  .movie-hero-slider .owl-stage-outer,
  .movie-hero-slider .owl-stage,
  .movie-hero-slider .owl-item {
    height: 100%;
  }

  .movie-hero-slider .slider-item {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background-position: center center;
    background-repeat: no-repeat;
    background-size: cover;
  }

  .movie-hero-slider .slide-play-btn {
    position: relative;
    z-index: 2;
    width: 110px;
    height: 110px;
    border: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.14);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.28);
    backdrop-filter: blur(10px);
    transition: transform .25s ease, background .25s ease;
  }

  .movie-hero-slider .slide-play-btn i {
    margin-left: 6px;
    font-size: 34px;
  }

  .movie-hero-slider .slide-play-btn:hover {
    transform: scale(1.08);
    background: #f15d30;
  }

  .movie-hero-slider .owl-nav {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    transform: translateY(-50%);
    z-index: 2;
    pointer-events: none;
  }

  .movie-hero-slider .owl-nav button {
    position: absolute;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: 1px solid rgba(255, 255, 255, 0.24) !important;
    background: rgba(255, 255, 255, 0.08) !important;
    color: #fff !important;
    font-size: 26px !important;
    pointer-events: auto;
    transition: all .25s ease;
  }

  .movie-hero-slider .owl-nav .owl-prev {
    left: 28px;
  }

  .movie-hero-slider .owl-nav .owl-next {
    right: 28px;
  }

  .movie-hero-slider .owl-nav button:hover {
    background: #f15d30 !important;
    border-color: #f15d30 !important;
  }

  .movie-hero-slider .owl-dots {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 5%;
    z-index: 2;
    text-align: center;
  }

  .movie-trailer-modal .modal-content {
    background: #070709;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 24px;
    overflow: hidden;
  }

  .movie-trailer-modal .modal-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  }

  .movie-trailer-modal .modal-title,
  .movie-trailer-modal .close {
    color: #fff;
    opacity: 1;
  }

  .movie-trailer-modal .modal-body {
    padding: 0;
    background: #000;
  }

  @media (max-width: 767.98px) {
    .movie-hero-slider .slider-item {
      min-height: 72vh;
    }

    .movie-hero-slider .slide-play-btn {
      width: 88px;
      height: 88px;
    }

    .movie-hero-slider .owl-nav .owl-prev {
      left: 14px;
    }

    .movie-hero-slider .owl-nav .owl-next {
      right: 14px;
    }
  }
</style>
@endpush

@section('content')
  <section class="hero-wrap js-fullheight movie-hero">
    <div class="overlay"></div>
        <div class="movie-hero-slider owl-carousel">
        <div class="slider-item" style="background-image: url('{{ asset('images/movie_bg.jpg') }}');">
            <button type="button" class="slide-play-btn" data-toggle="modal" data-target="#heroTrailerModal" aria-label="Play trailer">
            <i class="fa fa-play"></i>
            </button>
        </div>
        <div class="slider-item" style="background-image: linear-gradient(rgba(18,18,28,.28), rgba(18,18,28,.28)), url('{{ asset('images/bg_3.jpg') }}');">
            <button type="button" class="slide-play-btn" data-toggle="modal" data-target="#heroTrailerModal" aria-label="Play trailer">
            <i class="fa fa-play"></i>
            </button>
        </div>
        <div class="slider-item" style="background-image: linear-gradient(rgba(18,18,28,.3), rgba(18,18,28,.3)), url('{{ asset('images/bg_5.jpg') }}');">
            <button type="button" class="slide-play-btn" data-toggle="modal" data-target="#heroTrailerModal" aria-label="Play trailer">
            <i class="fa fa-play"></i>
            </button>
        </div>
        </div>
  </section>

  <div class="modal fade movie-trailer-modal" id="heroTrailerModal" tabindex="-1" role="dialog" aria-labelledby="heroTrailerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="heroTrailerModalLabel">Trailer</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="embed-responsive embed-responsive-16by9">
            <iframe class="embed-responsive-item" id="hero-trailer-frame" src="" allow="autoplay; encrypted-media" allowfullscreen></iframe>
          </div>
        </div>
      </div>
    </div>
  </div>

  <section class="ftco-section ftco-no-pt ftco-no-pb booking-search-shell">
    <div class="container">
      <div class="booking-search-card ftco-animate">
        <div class="row">
          <div class="col-md-12 nav-link-wrap p-4 pb-0">
            <div class="nav nav-pills" id="booking-tab" role="tablist" aria-orientation="horizontal">
              <a class="nav-link active" id="booking-tab-now" data-toggle="pill" href="#booking-now" role="tab" aria-selected="true">Đặt Vé Nhanh</a>
              <a class="nav-link" id="booking-tab-guide" data-toggle="pill" href="#booking-guide" role="tab" aria-selected="false">Hướng Dẫn</a>
            </div>
          </div>
          <div class="col-md-12 tab-wrap">
            <div class="tab-content" id="booking-tabContent">
              <div class="tab-pane fade show active" id="booking-now" role="tabpanel" aria-labelledby="booking-tab-now">
                <form action="#" class="search-property-1">
                  <div class="row no-gutters">
                    <div class="col-lg d-flex">
                      <div class="form-group p-4 border-0">
                        <label for="movie-keyword">Tên phim</label>
                        <div class="form-field">
                          <div class="icon"><span class="fa fa-search"></span></div>
                          <input id="movie-keyword" type="text" class="form-control" placeholder="Tìm phim đang chiếu hoặc sắp chiếu">
                        </div>
                      </div>
                    </div>
                    <div class="col-lg d-flex">
                      <div class="form-group p-4">
                        <label for="watch-date">Ngày xem</label>
                        <div class="form-field">
                          <div class="icon"><span class="fa fa-calendar"></span></div>
                          <input id="watch-date" type="text" class="form-control checkin_date" placeholder="Chọn ngày ra rạp">
                        </div>
                      </div>
                    </div>
                    <div class="col-lg d-flex">
                      <div class="form-group p-4">
                        <label for="category-filter">Thể loại</label>
                        <div class="form-field">
                          <div class="select-wrap">
                            <div class="icon"><span class="fa fa-chevron-down"></span></div>
                            <select name="category" id="category-filter" class="form-control">
                              <option value="">Tất cả thể loại</option>
                              <option value="hanh-dong">Hành động</option>
                              <option value="lang-man">Lãng mạn</option>
                              <option value="kinh-di">Kinh dị</option>
                              <option value="hoat-hinh">Hoạt hình</option>
                            </select>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-lg d-flex">
                      <div class="form-group d-flex w-100 border-0">
                        <div class="form-field w-100 align-items-center d-flex">
                          <input type="submit" value="Tìm lịch chiếu" class="align-self-stretch form-control btn btn-primary">
                        </div>
                      </div>
                    </div>
                  </div>
                </form>
              </div>

              <div class="tab-pane fade" id="booking-guide" role="tabpanel" aria-labelledby="booking-tab-guide">
                <div class="row no-gutters">
                  <div class="col-md-4 d-flex">
                    <div class="form-group p-4 border-0 w-100">
                      <label>Bước 1</label>
                      <p class="mb-0 text-muted">Chọn phim theo trạng thái đang chiếu, sắp chiếu hoặc xem toàn bộ danh mục.</p>
                    </div>
                  </div>
                  <div class="col-md-4 d-flex">
                    <div class="form-group p-4 w-100">
                      <label>Bước 2</label>
                      <p class="mb-0 text-muted">Mở chi tiết phim để chọn ngày, định dạng và suất chiếu phù hợp lịch trình của bạn.</p>
                    </div>
                  </div>
                  <div class="col-md-4 d-flex">
                    <div class="form-group p-4 w-100">
                      <label>Bước 3</label>
                      <p class="mb-0 text-muted">Chọn ghế, thêm combo bắp nước và thanh toán để nhận vé nhanh chóng.</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="ftco-section movie-catalog-section">
    <div class="container">

      <div class="movie-filter-bar text-center">
        <button type="button" class="movie-filter-pill active" id="btn-dang-chieu" onclick="loadMovies('dang-chieu')">Đang Chiếu</button>
        <button type="button" class="movie-filter-pill" id="btn-sap-chieu" onclick="loadMovies('sap-chieu')">Sắp Chiếu</button>
      </div>

      <div class="row" id="movie-list-container">
        <div class="col-12">
          <div class="movie-empty-state text-center">
            <i class="fa fa-spinner fa-spin fa-3x text-primary mb-3"></i>
            <p class="text-muted mb-0">Đang tải danh sách phim...</p>
          </div>
        </div>
      </div>

      <div class="row mt-5">
        <div class="col text-center">
          <div class="block-27">
            <ul id="pagination"></ul>
          </div>
        </div>
      </div>
    </div>
  </section>

@endsection

@section('scripts')
<script src="{{ asset('js/user/movies.js') }}"></script>
<script>
  $(function () {
    const $slider = $('.movie-hero-slider');
    const trailerFrame = document.getElementById('hero-trailer-frame');
    const trailerUrl = 'https://www.youtube.com/embed/TcMBFSGVi1c?autoplay=1';

    if ($slider.length && !$slider.hasClass('owl-loaded')) {
      $slider.owlCarousel({
        items: 1,
        loop: true,
        nav: true,
        dots: true,
        autoplay: true,
        autoplayTimeout: 6000,
        autoplayHoverPause: true,
        smartSpeed: 900,
        navText: ['<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>']
      });
    }

    $('.slide-play-btn').on('click', function () {
      if (trailerFrame) {
        trailerFrame.src = trailerUrl;
      }
    });

    $('#heroTrailerModal').on('hidden.bs.modal', function () {
      if (trailerFrame) {
        trailerFrame.src = '';
      }
    });
  });
</script>
@endsection
