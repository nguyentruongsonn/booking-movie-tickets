@extends('layouts.app')

@section('content')

  <section class="hero-wrap hero-wrap-2 js-fullheight" style="background-image: url('images/movie_bg.jpg');">
    <div class="overlay"></div>
    <div class="container">
      <div class="row no-gutters slider-text js-fullheight align-items-end justify-content-center">
        <div class="col-md-9 ftco-animate pb-5 text-center">
          <p class="breadcrumbs">
            <span class="mr-2"><a href="/">Trang chủ <i class="fa fa-chevron-right"></i></a></span> 
            <span>Danh sách phim <i class="fa fa-chevron-right"></i></span>
          </p>
          <h1 class="mb-0 bread" id="page-title">Phim Đang Chiếu</h1>
        </div>
      </div>
    </div>
  </section>

  <section class="ftco-section ftco-no-pb">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">
          <div class="search-wrap-1 ftco-animate">
            <form action="#" class="search-property-1">
              <div class="row no-gutters">
                <div class="col-lg d-flex">
                  <div class="form-group p-4 border-0">
                    <label for="#">Tên phim</label>
                    <div class="form-field">
                      <div class="icon"><span class="fa fa-search"></span></div>
                      <input type="text" class="form-control" placeholder="Nhập tên phim...">
                    </div>
                  </div>
                </div>
                <div class="col-lg d-flex">
                  <div class="form-group p-4">
                    <label for="#">Ngày xem</label>
                    <div class="form-field">
                      <div class="icon"><span class="fa fa-calendar"></span></div>
                      <input type="text" class="form-control checkin_date" placeholder="Chọn ngày">
                    </div>
                  </div>
                </div>
                <div class="col-lg d-flex">
                  <div class="form-group p-4">
                    <label for="#">Thể loại</label>
                    <div class="form-field">
                      <div class="select-wrap">
                        <div class="icon"><span class="fa fa-chevron-down"></span></div>
                        <select name="category" id="category-filter" class="form-control">
                          <option value="">Tất cả thể loại</option>
                          <option value="1">Hành động</option>
                          <option value="2">Hài hước</option>
                          <option value="3">Kinh dị</option>
                          <option value="4">Hoạt hình</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-lg d-flex">
                  <div class="form-group d-flex w-100 border-0">
                    <div class="form-field w-100 align-items-center d-flex">
                      <input type="submit" value="Tìm kiếm" class="align-self-stretch form-control btn btn-primary">
                    </div>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="container-fluid mt-5 mb-4 text-center">
      <div class="btn-group" role="group" aria-label="Movie Filter">
          <button type="button" class="btn btn-outline-primary active" id="btn-dang-chieu" onclick="loadMovies('dang-chieu')">Đang Chiếu</button>
          <button type="button" class="btn btn-outline-primary" id="btn-sap-chieu" onclick="loadMovies('sap-chieu')">Sắp Chiếu</button>
          <button type="button" class="btn btn-outline-primary" id="btn-all" onclick="loadMovies('all')">Tất Cả Phim</button>
      </div>
  </div>

  <section class="ftco-section pt-0">
    <div class="container-fluid">
      <div class="row" id="movie-list-container">
        <div class="col-12 text-center py-5">
            <i class="fa fa-spinner fa-spin fa-3x text-primary mb-3"></i>
            <p class="text-muted">Đang tải danh sách phim...</p>
        </div>
      </div>
      
      <div class="row mt-5">
        <div class="col text-center">
          <div class="block-27">
            <ul id="pagination">
              </ul>
          </div>
        </div>
      </div>
    </div>
  </section>

@endsection

@section('scripts')
<script src="{{ asset('js/user/movies.js') }}"></script>
@endsection