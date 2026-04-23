@extends('layouts.app')

@section('content')
    <div class="container-fluid bg-light py-4 booking-page">
        <div class="container booking-layout">
            <div class="row mb-4">
                <div class="col-12 text-center">
                    <ul class="list-inline d-flex justify-content-center gap-4 text-muted small fw-bold booking-steps">
                        <li class="list-inline-item active" id="choose-seat">Chọn ghế</li>
                        <li class="list-inline-item" id="choose-product">Chọn thực ăn</li>
                        <li class="list-inline-item" id="choose-promotion">Khuyến mãi</li>
                        <li class="list-inline-item" id="choose-confirm">Xác nhận</li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8" id="main-booking-col">
                    <div class="saas-card mb-4 booking-panel" id="card-seat">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="d-flex align-items-center">
                                <span class="me-2 fw-semibold text-muted">Suất chiếu:</span>
                                <button class="btn btn-primary px-4 radius-md" id="current-time-display"
                                    type="button">--:--</button>
                            </div>
                        </div>

                        <div class="text-center mb-4">
                            <div
                                class="w-75 mx-auto border-bottom border-4 border-primary pb-2 text-primary fw-bold small text-uppercase tracking-wider">
                                Màn hình</div>
                        </div>

                        <div id="seat-map" class="mx-auto w-100"></div>

                        <div
                            class="d-flex justify-content-center mt-5 gap-4 small booking-seat-legend fw-medium text-slate-600">
                            <div class="d-flex align-items-center gap-2"><span
                                    class="d-inline-block bg-secondary rounded-circle"
                                    style="width:16px;height:16px;"></span> Ghế đã bán</div>
                            <div class="d-flex align-items-center gap-2"><span
                                    class="d-inline-block border border-2 border-slate-300 rounded-circle bg-white"
                                    style="width:16px;height:16px;"></span> Ghế trống</div>
                            <div class="d-flex align-items-center gap-2"><span
                                    class="d-inline-block bg-primary rounded-circle" style="width:16px;height:16px;"></span>
                                Đang chọn</div>
                        </div>
                    </div>

                    <div class="saas-card mb-4 booking-panel" id="card-product" style="display:none;">
                        <div class="mb-4">
                            <h5 class="fw-bold text-slate-800">Chọn combo / sản phẩm</h5>
                            <p class="text-slate-500 small">Các tùy chọn ăn uống tại rạp giúp bạn có trải nghiệm tuyệt vời
                                hơn.</p>
                        </div>
                        <div id="product-map" class="mx-auto w-100"></div>
                    </div>

                    {{-- Step 3: Promotion --}}
                    <div class="saas-card mb-4 booking-panel" id="card-promotion" style="display:none;">
                        <div class="mb-4">
                            <h5 class="fw-bold text-slate-800">Khuyến mãi & Ưu đãi</h5>
                        </div>
                        <div id="promotion-map" class="mx-auto w-100">
                            <div class="row g-4">

                                {{-- Đăng ký mã (chỉ cần nhập mã, không cần mật khẩu) --}}
                                <div class="col-12">
                                    <div class="bg-slate-50 radius-md p-4 border border-slate-200">
                                        <div class="fw-bold text-slate-800 mb-3">Đăng ký mã khuyến mãi mới</div>
                                        <div class="row g-3">
                                            <div class="col-md-5">
                                                <input type="text" id="coupon-code-input" autocomplete="off"
                                                    class="form-control" placeholder="Nhập mã khuyến mãi">
                                            </div>
                                            <div class="col-md-4">
                                                <input type="password" id="coupon-password-input"
                                                    autocomplete="new-password" class="form-control"
                                                    placeholder="Mật khẩu tài khoản">
                                            </div>
                                            <div class="col-md-3">
                                                <button class="btn btn-primary w-100 h-100" type="button"
                                                    id="btn-register-coupon">
                                                    Lưu mã
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Danh sách mã đã đăng ký --}}
                                <div class="col-12">
                                    <div class="border border-slate-200 radius-md p-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="fw-bold text-slate-800">Kho Voucher của bạn</span>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle mb-0 booking-coupon-table">
                                                <thead class="bg-slate-50 text-slate-600 small">
                                                    <tr>
                                                        <th class="fw-semibold border-0 rounded-start">Mã voucher</th>
                                                        <th class="fw-semibold border-0">Nội dung</th>
                                                        <th class="fw-semibold border-0">Hết hạn</th>
                                                        <th class="fw-semibold border-0 text-center rounded-end">Thao tác
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody id="registered-coupon"></tbody>
                                            </table>
                                        </div>
                                        <div id="applied-coupon" class="fw-semibold text-success mt-3 small"></div>
                                    </div>
                                </div>


                                {{-- Điểm tích lũy --}}
                                <div class="col-12">
                                    <div class="bg-slate-50 radius-md p-4 border border-slate-200">
                                        <div class="fw-bold text-slate-800 mb-3">Đổi điểm tích lũy</div>
                                        <div class="input-group">
                                            <input type="number" id="points-start" class="form-control border-end-0" min="0"
                                                placeholder="Nhập số điểm muốn đổi">
                                            <button class="btn btn-primary px-4" type="button" id="btn-apply-points">
                                                Đổi điểm
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- Step 4: Confirm / Payment result --}}
                    <div class="saas-card mb-4 booking-panel" id="card-confirm" style="display:none;">
                        <div id="payment-success-content" class="d-none"></div>
                        <div id="payment-cancel-content" class="d-none"></div>
                    </div>

                </div>

                {{-- Sidebar --}}
                <div class="col-md-4" id="booking-sidebar">
                    <div class="saas-card sticky-top" style="top: 100px;">
                        <div class="row align-items-center mb-3">
                            <div class="col-4">
                                <img id="book-poster" src="" class="img-fluid radius-md shadow-sm" alt="Poster phim"
                                    loading="lazy">
                            </div>
                            <div class="col-8">
                                <h5 id="book-movie-name" class="fw-bold mb-1 text-slate-800" style="line-height:1.2"></h5>
                                <p class="mb-0 mt-2 small text-slate-500">
                                    <span class="badge bg-slate-200 text-slate-800 me-1 fw-semibold">T13</span>
                                    2D Phụ đề
                                </p>
                            </div>
                        </div>
                        <div class="bg-slate-50 radius-md p-3 mb-3 border border-slate-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="fa fa-map-marker text-primary"></i>
                                <span class="text-slate-800 fw-semibold" id="book-cinema"></span>
                                <span class="text-slate-400 mx-1">•</span>
                                <span class="text-slate-700" id="book-room"></span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa fa-clock-o text-primary"></i>
                                <span class="text-slate-800 fw-semibold" id="book-time"></span>
                                <span class="text-slate-400 mx-1">•</span>
                                <span class="text-slate-700" id="book-date"></span>
                            </div>
                        </div>

                        {{-- Ghế đã chọn --}}
                        <div class="small mb-3 text-slate-700 fw-medium" id="selected-seats-list"></div>

                        {{-- Sản phẩm đã chọn --}}
                        <div class="small mb-4 text-slate-700" id="selected-products-list"></div>

                        <div class="d-flex justify-content-between align-items-end mb-4 border-top border-slate-100 pt-3">
                            <span class="fw-bold text-slate-500">Tổng cộng</span>
                            <h3 class="text-primary fw-bold mb-0" id="total-price">0 đ</h3>
                        </div>

                        {{-- Kết quả đặt vé (Sẽ được JS hiển thị khi thất bại/Thành công) --}}
                        <div id="sidebar-result-container" class="d-none mb-4"></div>

                        <div class="row g-2" id="sidebar-default-actions">
                            <div class="col-5">
                                <button class="btn btn-outline-secondary w-100 py-2 radius-md fw-bold" id="btn-back"
                                    type="button">
                                    Quay lại
                                </button>
                            </div>
                            <div class="col-7">
                                <button class="btn btn-primary w-100 py-2 radius-md fw-bold shadow-sm" id="btn-continue"
                                    type="button">
                                    Tiếp tục
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/user/booking-toast.js') }}"></script>
    <script src="{{ asset('js/user/booking.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
@endsection