@extends('layouts.app')

@section('content')
<div class="container-fluid bg-light py-4 booking-page">
    <div class="container booking-layout">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <ul class="list-inline d-flex justify-content-center gap-4 text-muted small fw-bold booking-steps">
                    <li class="list-inline-item active" id="choose-seat">Chon ghe</li>
                    <li class="list-inline-item" id="choose-product">Chon thuc an</li>
                    <li class="list-inline-item" id="choose-promotion">Khuyen mai</li>
                    <li class="list-inline-item" id="choose-confirm">Xac nhan</li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm p-4 border-0 mb-4 booking-panel" id="card-seat" style="border-radius: 15px;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="d-flex align-items-center">
                            <span class="me-2">Doi suat chieu:</span>
                            <button class="btn btn-primary px-4" id="current-time-display">21:00</button>
                        </div>
                    </div>

                    <div class="text-center mb-5">
                        <div class="w-75 mx-auto border-bottom border-4 border-danger pb-2 text-muted small">Man hinh</div>
                    </div>

                    <div id="seat-map" class="mx-auto" style="width:100%;"></div>

                    <div class="d-flex justify-content-center mt-5 gap-4 small booking-seat-legend">
                        <div><span class="d-inline-block bg-secondary rounded" style="width:15px; height:15px;"></span> Ghe da ban</div>
                        <div><span class="d-inline-block border rounded" style="width:15px; height:15px;"></span> Ghe trong</div>
                        <div><span class="d-inline-block bg-primary rounded" style="width:15px; height:15px;"></span> Ghe dang chon</div>
                    </div>
                </div>

                <div class="card shadow-sm p-4 border-0 mb-4 booking-panel" id="card-product" style="border-radius:15px; display:none;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="d-flex align-items-center">
                            <span class="me-2 fw-bold">Chon combo / san pham</span>
                        </div>
                    </div>
                    <div id="product-map" class="mx-auto" style="width:100%;"></div>
                    <div class="selected-products-list small mt-3" id="selected-products-list"></div>
                </div>

                <div class="card shadow-sm p-4 border-0 mb-4 booking-panel" id="card-promotion" style="border-radius:15px; display:none;">
                    <div class="mb-4">
                        <span class="fw-bold">Khuyen mai</span>
                    </div>
                    <div id="promotion-map" class="mx-auto w-100">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="border rounded-4 p-3 bg-light">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted">Ma giam gia</label>
                                            <input type="text"
                                                id="coupon-code-input"
                                                autocomplete="off"
                                                class="form-control"
                                                placeholder="Nhap ma giam gia">
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label small text-muted">Mat khau</label>
                                            <input type="password"
                                                id="password-coupon"
                                                autocomplete="new-password"
                                                class="form-control"
                                                placeholder="Nhap mat khau">
                                        </div>

                                        <div class="col-md-3 d-flex align-items-end">
                                            <button class="btn btn-primary text-white w-100"
                                                    type="button"
                                                    id="btn-register-coupon">
                                                Dang ky
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="border rounded-4 p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-semibold">Ma da dang ky</span>
                                    </div>

                                    <div class="table-responsive mt-2">
                                        <table class="table table-sm align-middle mb-0 booking-coupon-table">
                                            <thead>
                                                <tr>
                                                    <td>Ma khuyến mãi</td>
                                                    <td>Nội dung</td>
                                                    <td>Ngày hết hạn</td>
                                                    <td class="text-center">Thao tác</td>
                                                </tr>
                                            </thead>
                                            <tbody id="registered-coupon"></tbody>
                                        </table>
                                    </div>


                                    <div id="applied-coupon" class="small text-success mt-2"></div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="border rounded-4 p-3 bg-light">
                                    <div class="fw-semibold mb-2">Diem tich luy</div>

                                    <div class="input-group">
                                        <input type="text"
                                            id="points-start"
                                            class="form-control"
                                            placeholder="Nhap so diem muon dung">

                                        <button class="btn btn-primary"
                                                type="button"
                                                id="btn-apply-points">
                                            Ap dung
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div id="coupon-message" class="small"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm p-4 border-0 mb-4 booking-panel" id="card-confirm" style="border-radius:15px; display:none;">
                    <div id="payment-success-content" class="d-none">

                    </div>

                    <div id="payment-cancel-content" class="d-none">

                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-0 booking-sidebar" style="border-radius: 15px;">
                    <div class="p-4">
                        <div class="row">
                            <div class="col-4">
                                <img id="book-poster" src="" class="img-fluid rounded shadow-sm" alt="Poster">
                            </div>
                            <div class="col-8">
                                <h5 id="book-movie-name" class="fw-bold mb-1 small text-uppercase"></h5>
                                <p class="mb-0 small"><span class="badge bg-warning text-dark me-1">T13</span> 2D Phu de</p>
                            </div>
                        </div>
                        <hr>
                        <div class="small">
                            <p class="mb-1 fw-bold text-muted"><span class="text-dark" id="book-cinema"></span> - <span class="text-dark" id="book-room"></span></p>
                            <p class="mb-1 fw-bold text-muted">Suat: <span id="book-time" class="text-dark"></span> - <span id="book-date" class="text-dark"></span></p>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Tong cong</span>
                            <h4 class="text-danger fw-bold mb-0" id="total-price">0 d</h4>
                        </div>
                        <div class="row mt-4">
                            <div class="col-6">
                                <button class="btn btn-secondary w-100 py-2 fw-bold shadow-sm" id="btn-back">Quay lai</button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-warning text-white w-100 py-2 fw-bold shadow-sm" id="btn-continue">Tiep tuc</button>
                            </div>
                            <div class="small" id="selected-products-sidebar"></div>
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
