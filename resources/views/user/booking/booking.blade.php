@extends('layouts.app')

@section('content')
<div class="container-fluid bg-light py-4">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <ul class="list-inline d-flex justify-content-center gap-4 text-muted small fw-bold">
                    <li class="list-inline-item active" id="choose-seat">Chọn ghế</li>
                    <li class="list-inline-item" id="choose-product">Chọn thức ăn</li>
                    <li class="list-inline-item" id="choose-promotion">Khuyến mãi</li>
                    <li class="list-inline-item" id="choose-confirm">Xác nhận</li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm p-4 border-0 mb-4" id="card-seat" style="border-radius: 15px;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="d-flex align-items-center">
                            <span class="me-2">Đổi suất chiếu:</span>
                            <button class="btn btn-primary px-4" id="current-time-display">21:00</button>
                        </div>
                    </div>

                    <div class="text-center mb-5">
                        <div class="w-75 mx-auto border-bottom border-4 border-danger pb-2 text-muted small">Màn hình</div>
                    </div>

                    <div id="seat-map" class="mx-auto" style="width:100%;">
                    </div>

                    <div class="d-flex justify-content-center mt-5 gap-4 small" >
                        <div><span class="d-inline-block bg-secondary rounded" style="width:15px; height:15px;"></span> Ghế đã bán</div>
                        <div><span class="d-inline-block border rounded" style="width:15px; height:15px;"></span> Ghế trống</div>
                        <div><span class="d-inline-block bg-primary rounded" style="width:15px; height:15px;"></span> Ghế đang chọn</div>
                    </div>
                </div>
                <div class="card shadow-sm p-4 border-0 mb-4" id="card-product" style="border-radius:15px; display:none;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="d-flex align-items-center">
                            <span class="me-2 fw-bold">Chọn Combos/ Sản phẩm</span>
                        </div>
                    </div>
                    <div id = "product-map" class ="mx-auto" style="width:100%; ">
                    </div>
                    <div class = "selected-products-list small mt-3" id = "selected-products-list">

                    </div>
                </div>

                <div class="card shadow-sm p-4 border-0 mb-4" id="card-promotion" style="border-radius:15px;">
                    <div class="mb-4">
                        <span class="fw-bold">Khuyến mãi</span>
                    </div>

                    <div id="promotion-map" class="mx-auto w-100">
                        <div class="col-md-12">

                            <!-- Mã giảm giá -->
                            <div class="mb-3">
                                <label for="coupon-code-input" class="form-label">Mã giảm giá</label>
                                <input type="text" id="coupon-code-input" class="form-control" placeholder="Nhập mã giảm giá">
                                <button class="btn btn-primary rounded-3 text-white mt-2" type="button" id="btn-apply-coupon">
                                    Áp dụng
                                </button>
                            </div>

                            <!-- Danh sách khuyến mãi -->
                            <div id="user-promotions" class="d-flex flex-column gap-2 mt-2 mb-3"></div>

                            <!-- Điểm -->
                            <div class="mb-3">
                                <label for="points-start" class="form-label">Áp dụng điểm</label>
                                <input type="text" id="points-start" class="form-control" placeholder="Nhập số điểm muốn sử dụng">
                                <button class="btn btn-primary rounded-4 text-white mt-2" type="button" id="btn-apply-points">
                                    Áp dụng
                                </button>
                            </div>

                            <div id="coupon-message" class="small"></div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="p-4">
                        <div class="row">
                            <div class="col-4">
                                <img id="book-poster" src="" class="img-fluid rounded shadow-sm" alt="Poster">
                            </div>
                            <div class="col-8">
                                <h5 id="book-movie-name" class="fw-bold mb-1 small text-uppercase"></h5>
                                <p class="mb-0 small"><span class="badge bg-warning text-dark me-1">T13</span> 2D Phụ Đề</p>
                            </div>
                        </div>
                        <hr>
                        <div class="small">
                            <p class="mb-1 fw-bold text-muted"><span class="text-dark" id="book-cinema"></span> - <span class="text-dark" id="book-room"></span></p>
                            <p class="mb-1 fw-bold text-muted">Suất: <span id="book-time" class="text-dark"></span> - <span id="book-date" class="text-dark"></span></p>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Tổng cộng</span>
                            <h4 class="text-danger fw-bold mb-0" id="total-price">0 đ</h4>
                        </div>
                        <div class="row mt-4">
                            <div class="col-6">
                                <button class="btn btn-secondary w-100 py-2 fw-bold shadow-sm" id="btn-back" ">Quay lại</button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-warning text-white w-100 py-2 fw-bold shadow-sm" id = "btn-continue">Tiếp tục</button>
                            </div>
                            <div class="small" id = "selected-products-sidebar">

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@section('scripts')
<script src="{{ asset('js/user/booking.js') }}"></script>
@endsection
@endsection
