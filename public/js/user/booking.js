/**
 * booking.js — Booking flow chính
 *
 * Phụ thuộc (load trước file này):
 *   - booking-api.js  → window.BookingApi { apiFetch, TokenStore }
 *   - booking-toast.js → window.Toast { success, error, warning, info }
 *
 * Luồng: Chọn ghế → Chọn sản phẩm → Khuyến mãi → Xác nhận / Thanh toán
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // ─── Shorthand ───────────────────────────────────────────────────────────
    const { apiFetch } = window.BookingApi;
    const toast = window.Toast;

    // ─── Đọc thông tin từ URL ────────────────────────────────────────────────
    const pathSegments = window.location.pathname.split('/');
    const showtimeId = pathSegments.filter(Boolean).pop();
    const queryParams = new URLSearchParams(window.location.search);
    const paymentStatus = queryParams.get('paymentStatus');
    const paymentOrderCode = queryParams.get('orderCode');

    if (!showtimeId || Number.isNaN(Number.parseInt(showtimeId, 10))) {
        console.error('[Booking] ID suất chiếu không hợp lệ');
        return;
    }

    // ─── DOM Refs ────────────────────────────────────────────────────────────
    const dom = {
        btnRegisterCoupon: document.getElementById('btn-register-coupon'),
        couponCodeInput: document.getElementById('coupon-code-input'),
        couponApplyInput: document.getElementById('coupon-apply-input'),

        registeredCoupon: document.getElementById('registered-coupon'),
        appliedCoupon: document.getElementById('applied-coupon'),
        btnContinue: document.getElementById('btn-continue'),
        btnBack: document.getElementById('btn-back'),
        seatMap: document.getElementById('seat-map'),
        cardSeat: document.getElementById('card-seat'),
        cardProduct: document.getElementById('card-product'),
        cardPromotion: document.getElementById('card-promotion'),
        cardConfirm: document.getElementById('card-confirm'),
        paymentSuccess: document.getElementById('payment-success-content'),
        paymentCancel: document.getElementById('payment-cancel-content'),
        chooseSeat: document.getElementById('choose-seat'),
        chooseProduct: document.getElementById('choose-product'),
        choosePromotion: document.getElementById('choose-promotion'),
        chooseConfirm: document.getElementById('choose-confirm'),
        selectedSeatsList: document.getElementById('selected-seats-list'),
        selectedProductsList: document.getElementById('selected-products-list'),
        productMap: document.getElementById('product-map'),
        couponMessage: document.getElementById('coupon-message'),
        pointsInput: document.getElementById('points-start'),
        totalPrice: document.getElementById('total-price'),
        movieName: document.getElementById('book-movie-name'),
        poster: document.getElementById('book-poster'),
        time: document.getElementById('book-time'),
        date: document.getElementById('book-date'),
        currentTimeDisplay: document.getElementById('current-time-display'),
        cinema: document.getElementById('book-cinema'),
        room: document.getElementById('book-room'),
        sidebarResult: document.getElementById('sidebar-result-container'),
        sidebarActions: document.getElementById('sidebar-default-actions'),
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
    };

    // ─── State ───────────────────────────────────────────────────────────────
    const bookingDiscount = { promoId: null, promoCode: null, promoValue: 0, pointAmount: 0 };
    const stepItems = Array.from(document.querySelectorAll('.list-inline-item'));
    const currencyFmt = new Intl.NumberFormat('vi-VN');
    const selectedSeats = new Map();     // seatId → { id, name, price }
    const selectedProducts = new Map();    // productId → { id, name, price, qty }
    const holdingRequests = new Set();     // seatId đang giữ (tránh double-click)

    let totalSeatPrice = 0;
    let totalProductPrice = 0;
    let productCache = null;          // danh sách sản phẩm từ API
    let productLookup = new Map();     // id → product object
    let promotionCache = null;          // điểm tích lũy
    let currentShowtime = null;
    let holdExpireTimer = null;

    // ─── Utilities ───────────────────────────────────────────────────────────

    function formatCurrency(value) {
        return `${currencyFmt.format(Number(value) || 0)} đ`;
    }

    function toAmount(value) {
        const n = Number(value);
        return Number.isFinite(n) ? n : 0;
    }

    function getSubtotal() {
        return totalSeatPrice + totalProductPrice;
    }

    function getTotalDiscount(subtotal) {
        return Math.min(bookingDiscount.pointAmount + bookingDiscount.promoValue, subtotal);
    }

    function getPosterUrl(path) {
        const fallback = '/images/bg_1.jpg';
        if (typeof path !== 'string') return fallback;
        const cleanPath = path.trim();
        if (!cleanPath) return fallback;

        if (cleanPath.startsWith('http') || cleanPath.startsWith('//')) {
            return cleanPath;
        }

        if (cleanPath.startsWith('/storage/') || cleanPath.startsWith('storage/')) {
            return cleanPath.startsWith('/') ? cleanPath : `/${cleanPath}`;
        }

        return `/storage/${cleanPath}`;
    }

    // ─── localStorage helpers (chỉ lưu state booking, KHÔNG lưu token) ───────

    function storageKey(name) {
        return `booking_${name}_${showtimeId}`;
    }

    function clearBookingStorage() {
        ['selected_seats', 'booking_step', 'hold_expires_at', 'return_url'].forEach((k) => {
            localStorage.removeItem(storageKey(k));
        });
    }

    // ─── DOM helpers ─────────────────────────────────────────────────────────

    function createElement(tag, opts = {}, children = []) {
        // Đối với các thẻ SVG, cần sử dụng Namespace chuẩn
        const isSvg = ['svg', 'path', 'rect', 'circle', 'g'].includes(tag);
        const el = isSvg
            ? document.createElementNS("http://www.w3.org/2000/svg", tag)
            : document.createElement(tag);

        if (opts.id) el.id = opts.id;
        if (opts.className) el.className = opts.className;
        if (opts.text !== undefined) el.textContent = opts.text;

        if (opts.attributes) {
            Object.entries(opts.attributes).forEach(([k, v]) => {
                if (v !== undefined && v !== null) {
                    // SVG cần dùng setAttributeNS cho một số thuộc tính nhưng setAttribute vẫn ổn với phần lớn trường hợp
                    el.setAttribute(k, String(v));
                }
            });
        }
        if (opts.dataset) {
            Object.entries(opts.dataset).forEach(([k, v]) => {
                if (v !== undefined && v !== null) el.dataset[k] = String(v);
            });
        }
        children.filter(Boolean).forEach((c) => el.append(c));
        return el;
    }

    function replaceContent(target, children = []) {
        if (!target) return;
        target.replaceChildren(...children.filter(Boolean));
    }

    function createInfoLine(label, value, cls = '') {
        return createElement('div', { className: 'small text-muted mt-2' }, [
            document.createTextNode(`${label}: `),
            createElement('span', { className: cls, text: value }),
        ]);
    }

    function createSummaryRow(label, value, opts = {}) {
        return createElement('div', {
            className: `d-flex justify-content-between ${opts.className || ''}`.trim(),
        }, [
            createElement('span', { className: opts.labelClassName || '', text: label }),
            createElement('strong', { className: opts.valueClassName || '', text: value }),
        ]);
    }

    function createEmptyText(text) {
        return createElement('div', { className: 'small text-muted', text });
    }

    function createDetailItem(text) {
        return createElement('div', { className: 'mb-1', text });
    }

    function createSectionCard(title, bodyChildren = [], cls = 'border radius-md p-3 mb-3') {
        const children = title
            ? [createElement('div', { className: 'fw-bold mb-2', text: title }), ...bodyChildren]
            : bodyChildren;
        return createElement('div', { className: cls }, children);
    }

    function createSummaryInfoCard(title, lines = []) {
        return createElement('div', { className: 'border radius-md p-3 h-100' }, [
            createElement('div', { className: 'small text-muted mb-2', text: title }),
            ...lines,
        ]);
    }

    function createActionLinkButton(href, text, cls) {
        return createElement('a', { className: cls, text, attributes: { href } });
    }

    function createActionButton(id, text, cls, type = 'button', attrs = {}) {
        return createElement('button', { className: cls, text, attributes: { id, type, ...attrs } });
    }

    // ─── Steps ───────────────────────────────────────────────────────────────

    function setActiveStep(activeEl) {
        stepItems.forEach((item) => item.classList.toggle('active', item === activeEl));
    }

    // ─── Price ───────────────────────────────────────────────────────────────

    function updateTotalPrice() {
        const subtotal = getSubtotal();
        const finalPrice = Math.max(0, subtotal - getTotalDiscount(subtotal));
        if (dom.totalPrice) dom.totalPrice.textContent = formatCurrency(finalPrice);
    }

    // ─── Seat hold expiration ─────────────────────────────────────────────────

    function scheduleHoldExpiration(expiresAt) {
        if (!expiresAt) return;
        const ms = new Date(expiresAt).getTime();
        if (Number.isNaN(ms)) return;

        localStorage.setItem(storageKey('hold_expires_at'), new Date(ms).toISOString());
        if (holdExpireTimer) clearTimeout(holdExpireTimer);

        const delay = ms - Date.now();
        if (delay <= 0) { expireBookingSession(); return; }

        holdExpireTimer = window.setTimeout(() => expireBookingSession(), delay);
    }

    function restoreHoldExpiration() {
        const saved = localStorage.getItem(storageKey('hold_expires_at'));
        if (saved) scheduleHoldExpiration(saved);
    }

    function expireBookingSession() {
        resetBookingState();
        clearBookingStorage();
        toast.warning('Phiên giữ ghế đã hết hạn. Vui lòng chọn lại.', 0);
        setTimeout(() => { window.location.href = getReturnDetailUrl(); }, 3000);
    }

    // ─── Booking state ────────────────────────────────────────────────────────

    function resetBookingState() {
        selectedSeats.clear();
        selectedProducts.clear();
        holdingRequests.clear();
        totalSeatPrice = 0;
        totalProductPrice = 0;
        bookingDiscount.promoId = null;
        bookingDiscount.promoCode = null;
        bookingDiscount.promoValue = 0;
        bookingDiscount.pointAmount = 0;

        if (holdExpireTimer) { clearTimeout(holdExpireTimer); holdExpireTimer = null; }
        if (dom.couponCodeInput) dom.couponCodeInput.value = '';
        if (dom.pointsInput) dom.pointsInput.value = '';
        replaceContent(dom.couponMessage);
        renderAppliedCouponStatus();
        renderSelectedSeatsList();
        renderSelectedProductsSidebar();
        updateTotalPrice();
    }

    function getReturnDetailUrl() {
        const saved = localStorage.getItem(storageKey('return_url'));
        if (saved) return saved;
        const slug = currentShowtime?.movie?.slug;
        const id = currentShowtime?.movie?.id;
        return slug ? `/movie/${slug}` : (id ? `/movie/${id}` : '/');
    }

    // ─── Seats — local storage ────────────────────────────────────────────────

    function saveSelectedSeats() {
        localStorage.setItem(
            storageKey('selected_seats'),
            JSON.stringify(Array.from(selectedSeats.values()))
        );
    }

    function restoreSavedSeats() {
        try {
            const raw = localStorage.getItem(storageKey('selected_seats'));
            if (!raw) return;
            const seats = JSON.parse(raw);
            if (!Array.isArray(seats)) return;
            seats.forEach((seat) => {
                if (!seat?.id || selectedSeats.has(String(seat.id))) return;
                const normalized = { id: String(seat.id), name: seat.name, price: Number(seat.price) || 0 };
                selectedSeats.set(normalized.id, normalized);
                totalSeatPrice += normalized.price;
            });
        } catch {
            localStorage.removeItem(storageKey('selected_seats'));
        }
    }

    // ─── Render – sidebar & coupon status ────────────────────────────────────

    function renderSelectedSeatsList() {
        if (!dom.selectedSeatsList) return;
        if (selectedSeats.size === 0) {
            dom.selectedSeatsList.textContent = 'Chưa chọn ghế';
            return;
        }
        const names = Array.from(selectedSeats.values(), (s) => s.name).join(', ');
        replaceContent(dom.selectedSeatsList, [
            document.createTextNode('Ghế: '),
            createElement('span', { className: 'text-dark', text: names }),
        ]);
    }

    function renderSelectedProductsSidebar() {
        if (!dom.selectedProductsList) return;
        if (selectedProducts.size === 0) { replaceContent(dom.selectedProductsList); return; }
        replaceContent(dom.selectedProductsList,
            Array.from(selectedProducts.values(), (p) =>
                createElement('div', { className: 'd-flex justify-content-between align-items-center mb-1' }, [
                    createElement('span', { text: `${p.qty}x ${p.name}` }),
                    createElement('span', { className: 'fw-bold', text: formatCurrency(p.price * p.qty) }),
                ])
            )
        );
    }

    function renderAppliedCouponStatus() {
        if (!dom.appliedCoupon) return;
        if (!bookingDiscount.promoCode) { replaceContent(dom.appliedCoupon); return; }
        replaceContent(dom.appliedCoupon, [
            createElement('div', { className: 'small text-primary', text: `Mã đang áp dụng: ${bookingDiscount.promoCode}` }),
        ]);
    }

    function clearAppliedCoupon() {
        bookingDiscount.promoId = null;
        bookingDiscount.promoCode = null;
        bookingDiscount.promoValue = 0;
        replaceContent(dom.couponMessage);
        renderAppliedCouponStatus();
        updateTotalPrice();
    }

    // ─── Render – seat map ────────────────────────────────────────────────────

    function createSeatElement(row, seat) {
        const seatId = String(seat.id);
        const isBooked = Boolean(seat.is_booked);
        const label = seat.label || `${row}${seat.number}`;
        const el = createElement('div', {
            className: `seat ${isBooked ? 'booked' : 'available'}`,
            text: seat.number,
            dataset: {
                id: seatId,
                name: label,
                price: toAmount(seat.price),
                row: row,
                number: seat.number
            },
        });
        if (selectedSeats.has(seatId)) el.classList.add('selected');
        return el;
    }

    function createSeatRow(row, seats) {
        const sorted = [...seats].sort((a, b) => Number(a.number) - Number(b.number));
        const rowLabel = () => createElement('div', { className: 'row-label fw-bold small text-muted', text: row });
        return createElement('div', {
            className: 'd-flex justify-content-center align-items-center w-100 gap-2 mb-2',
        }, [rowLabel(), ...sorted.map((s) => createSeatElement(row, s)), rowLabel()]);
    }

    function renderSeats(seatsByRow) {
        if (!dom.seatMap) return;
        const rows = Object.keys(seatsByRow).sort().reverse();
        replaceContent(dom.seatMap, rows.map((row) => createSeatRow(row, seatsByRow[row])));
    }

    // ─── Render – products ────────────────────────────────────────────────────

    function createProductCard(product) {
        const productId = Number(product.id);
        const qty = selectedProducts.get(productId)?.qty || 0;

        return createElement('div', { className: 'col-md-6 mb-3' }, [
            createElement('div', { className: 'product-card d-flex align-items-center p-3 border-0 bg-slate-50 saas-card h-100 shadow-sm radius-md' }, [
                createElement('div', { className: 'product-img me-3' }, [
                    createElement('img', {
                        className: 'radius-md shadow-sm bg-white',
                        attributes: {
                            src: `/storage/${product.image_url}`,
                            alt: product.name,
                            style: 'width:90px;height:90px;object-fit:cover;',
                            loading: 'lazy',
                        },
                    }),
                ]),
                createElement('div', { className: 'flex-grow-1' }, [
                    createElement('h6', { className: 'mb-1 fw-bold text-slate-800', text: product.name }),
                    createElement('p', { className: 'text-primary fw-bold mb-2', text: formatCurrency(product.price) }),
                    createElement('div', { className: 'd-flex align-items-center gap-2' }, [
                        createActionButton(null, '−', 'btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center product-qty-btn', 'button', {
                            'data-id': productId, 'data-delta': -1,
                            style: 'width:32px;height:32px;',
                        }),
                        createElement('span', {
                            className: 'fw-bold fs-6 text-slate-700 px-2',
                            text: qty,
                            attributes: { id: `qty-${productId}` },
                        }),
                        createActionButton(null, '+', 'btn btn-primary d-flex align-items-center justify-content-center btn-sm text-white rounded-circle shadow-sm product-qty-btn', 'button', {
                            'data-id': productId, 'data-delta': 1,
                            style: 'width:32px;height:32px;',
                        }),
                    ]),
                ]),
            ]),
        ]);
    }

    function renderProductSkeletons() {
        if (!dom.productMap) return;
        dom.productMap.className = 'row g-3';

        let skeletons = [];
        for (let i = 0; i < 4; i++) {
            skeletons.push(
                createElement('div', { className: 'col-md-6 mb-3' }, [
                    createElement('div', { className: 'saas-card d-flex align-items-center p-3 border-0 bg-slate-50' }, [
                        createElement('div', { className: 'skeleton-box radius-md me-3', attributes: { style: 'width:90px; height:90px;' } }),
                        createElement('div', { className: 'flex-grow-1' }, [
                            createElement('div', { className: 'skeleton-box w-75 mb-2', attributes: { style: 'height:16px;' } }),
                            createElement('div', { className: 'skeleton-box w-50 mb-3', attributes: { style: 'height:14px;' } }),
                            createElement('div', { className: 'd-flex gap-2' }, [
                                createElement('div', { className: 'skeleton-box rounded-circle', attributes: { style: 'width:32px; height:32px;' } }),
                                createElement('div', { className: 'skeleton-box', attributes: { style: 'width:20px; height:32px;' } }),
                                createElement('div', { className: 'skeleton-box rounded-circle', attributes: { style: 'width:32px; height:32px;' } }),
                            ])
                        ])
                    ])
                ])
            );
        }
        replaceContent(dom.productMap, skeletons);
    }

    function renderProducts(products) {
        if (!dom.productMap) return;
        dom.productMap.className = 'row g-3';
        if (!products || products.length === 0) {
            replaceContent(dom.productMap, [
                createElement('div', { className: 'col-12 text-center text-muted py-5' }, [
                    createElement('p', { text: 'Không có sản phẩm nào.' })
                ])
            ]);
            return;
        }
        replaceContent(dom.productMap, products.map(createProductCard));
    }

    // ─── Render – promotion vouchers ──────────────────────────────────────────

    function formatExpiryDate(value) {
        if (!value) return '—';
        const d = new Date(value);
        return Number.isNaN(d.getTime()) ? '—' : d.toLocaleDateString('vi-VN');
    }

    function createVoucherRow(voucher) {
        const isApplied = bookingDiscount.promoCode === voucher.code;
        const btnCls = isApplied
            ? 'btn btn-sm btn-outline-danger registered-voucher-toggle'
            : 'btn btn-sm btn-outline-primary registered-voucher-toggle';

        return createElement('tr', {}, [
            createElement('td', { className: 'fw-semibold text-dark', text: voucher.code }),
            createElement('td', { text: voucher.name || '—' }),
            createElement('td', { text: formatExpiryDate(voucher.expires_at) }),
            createElement('td', { className: 'text-center' }, [
                createActionButton(null, isApplied ? 'Hủy' : 'Áp dụng', btnCls, 'button', {
                    'data-code': voucher.code,
                    'data-action': isApplied ? 'cancel' : 'apply',
                }),
            ]),
        ]);
    }

    function setRegisteredCouponState(text, cls = 'small text-muted') {
        replaceContent(dom.registeredCoupon, [
            createElement('tr', {}, [
                createElement('td', { className: cls, text, attributes: { colspan: 4 } }),
            ]),
        ]);
    }

    // ─── Render – movie info ──────────────────────────────────────────────────

    function renderMovieInfo(showtime) {
        currentShowtime = showtime;
        localStorage.setItem(storageKey('return_url'), getReturnDetailUrl());

        const movie = showtime.movie || {};
        const screen = showtime.screen || {};

        if (dom.movieName) dom.movieName.textContent = movie.title || '';
        if (dom.poster) dom.poster.src = getPosterUrl(movie.poster_url);

        const scheduledAt = new Date(showtime.scheduled_at);
        if (dom.time) dom.time.textContent = scheduledAt.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) || '';
        if (dom.date) dom.date.textContent = scheduledAt.toLocaleDateString('vi-VN') || '';
        if (dom.currentTimeDisplay) dom.currentTimeDisplay.textContent = scheduledAt.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) || '';
        if (dom.room) dom.room.textContent = screen.name || '';

        if (dom.cinema) dom.cinema.textContent = movie.cinema_name || showtime.cinema_name || 'Antigravity Cinema';
    }

    // ─── Render – payment result ──────────────────────────────────────────────

    // ─── Render – payment result ──────────────────────────────────────────────
    
    /**
     * Hằng số định danh để tránh lỗi chính tả
     */
    const TICKET_CONFIG = {
        CONTAINER_ID: 'premium-ticket',
        BARCODE_ID: 'barcode',
        DOWNLOAD_BTN_CLASS: 'btn-download-ticket'
    };

    /**
     * Tiêm CSS cho vé (chạy 1 lần duy nhất)
     */
    function injectTicketStyles() {
        if (document.getElementById('booking-ticket-styles')) return;
        
        const style = createElement('style', { id: 'booking-ticket-styles' });
        style.textContent = `
            .premium-ticket { background: #fff; border-radius: 16px; box-shadow: 0 16px 40px rgba(0,0,0,0.08); position: relative; overflow: hidden; max-width: 700px; margin: 0 auto; }
            .ticket-header { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white; padding: 2.5rem 2rem 3.5rem; position: relative; }
            .ticket-glow { width: 80px; height: 80px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; box-shadow: 0 0 20px rgba(16, 185, 129, 0.4); }
            .ticket-glow i { font-size: 2.5rem; color: white; }
            .ticket-body { padding: 2rem; background: white; }
            .dashed-line { border-top: 2px dashed #e2e8f0; margin: 1.5rem 0; }
            .info-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; font-weight: 600; margin-bottom: 0.25rem; }
            .info-value { font-weight: 500; color: #1e293b; font-size: 0.9rem; }
            .booking-success-barcode { display: flex; justify-content: center; margin-top: 10px; }
            .booking-success-barcode svg { background: #fff; padding: 8px 12px; border-radius: 8px; }
        `;
        document.head.appendChild(style);
    }

    /**
     * Hàm render chính cho màn hình thành công
     */
    function renderPaymentSuccess(summary) {
        if (!dom.paymentSuccess) return;
        injectTicketStyles();

        const tickets = Array.isArray(summary.tickets) ? summary.tickets : [];
        const products = Array.isArray(summary.products) ? summary.products : [];
        const showtime = summary.showtime || {};
        const scheduledTime = showtime.scheduled_at ? new Date(showtime.scheduled_at) : null;

        showConfirmStep();

        replaceContent(dom.paymentSuccess, [
            createElement('div', { className: 'py-2 pb-5 fade-in' }, [
                // Khối vé chính
                createElement('div', { id: TICKET_CONFIG.CONTAINER_ID, className: 'premium-ticket text-start' }, [
                    renderTicketHeader(),
                    renderTicketBody(showtime, scheduledTime, tickets, products, summary)
                ]),
                // Nút hành động
                renderTicketActions()
            ]),
        ]);

        dom.paymentSuccess.classList.remove('d-none');
        generateBarcode(summary.order_number);
        attachDownloadEvent();
    }

    function renderTicketHeader() {
        return createElement('div', { className: 'ticket-header text-center' }, [
            createElement('div', { className: 'ticket-glow' }, [
                createElement('i', { className: 'fa fa-check' })
            ]),
            createElement('h3', { className: 'fw-bold mb-1 text-white', text: 'Thanh toán thành công' }),
            createElement('div', { className: 'booking-success-barcode' }, [
                createElement('svg', { id: TICKET_CONFIG.BARCODE_ID })
            ])
        ]);
    }

    function renderTicketBody(showtime, scheduledTime, tickets, products, summary) {
        const discounts = summary.discounts || {};
        
        return createElement('div', { className: 'ticket-body' }, [
            createElement('div', { className: 'row align-items-center mb-4' }, [
                createElement('div', { className: 'col-12' }, [
                    createElement('h4', { className: 'fw-bold text-slate-800 mb-1', text: showtime.movie?.title || 'Tên Phim' }),
                    createElement('div', { className: 'row g-3' }, [
                        renderInfoItem('Ngày chiếu', scheduledTime ? scheduledTime.toLocaleDateString('vi-VN') : '--/--/----'),
                        renderInfoItem('Giờ chiếu', scheduledTime ? scheduledTime.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) : '--:--'),
                        renderInfoItem('Rạp chiếu', 'Phòng ' + (showtime.screen?.name || '-')),
                        renderInfoItem('Số lượng', tickets.length + ' Vé'),
                        createElement('div', { className: 'col-6' }, [
                            createElement('div', { className: 'info-label', text: 'Ghế' }),
                            createElement('div', { className: 'info-value' }, tickets.map(t => 
                                createElement('span', { className: 'badge bg-slate-100 text-slate-800 border border-slate-200 me-1', text: t.name })
                            ))
                        ]),
                        renderInfoItem('Combo', products.length ? products.map(p => `${p.name} x${p.quantity}`).join(', ') : '---')
                    ])
                ])
            ]),
            createElement('div', { className: 'dashed-line' }),
            renderPriceSummary(summary, discounts)
        ]);
    }

    function renderInfoItem(label, value) {
        return createElement('div', { className: 'col-6' }, [
            createElement('div', { className: 'info-label', text: label }),
            createElement('div', { className: 'info-value', text: value })
        ]);
    }

    function renderPriceSummary(summary, discounts) {
        return createElement('div', { className: 'bg-slate-50 rounded p-3 border border-slate-100' }, [
            createSummaryRow('Tạm tính', formatCurrency(summary.total_amount + (discounts.total_discount || 0))),
            discounts.total_discount > 0 ? createSummaryRow('Giảm giá', '-' + formatCurrency(discounts.total_discount), { valueClassName: 'text-danger' }) : null,
            createElement('div', { className: 'my-2 border-top border-slate-200' }),
            createElement('div', { className: 'text-center mt-2' }, [
                createElement('div', { className: 'info-label', text: 'Tổng thanh toán' }),
                createElement('div', { className: 'text-success fw-bold fs-3 mt-1', text: formatCurrency(summary.total_amount) })
            ])
        ]);
    }

    function renderTicketActions() {
        return createElement('div', { className: 'd-flex justify-content-center gap-3 mt-4 flex-wrap' }, [
            createActionLinkButton('/', 'Trở về trang chủ', 'btn btn-outline-secondary btn-lg radius-md px-4'),
            createActionLinkButton('/profile/tickets', 'Tải vé QR về máy', `btn btn-primary ${TICKET_CONFIG.DOWNLOAD_BTN_CLASS} btn-lg radius-md px-5 fw-bold`)
        ]);
    }

    function generateBarcode(orderNumber) {
        if (!orderNumber) return;
        try {
            JsBarcode(`#${TICKET_CONFIG.BARCODE_ID}`, orderNumber, {
                format: "CODE128", width: 2, height: 50, displayValue: true, margin: 10
            });
        } catch (err) {
            console.error("[Booking] Lỗi tạo Barcode:", err);
        }
    }

    function attachDownloadEvent() {
        const btn = document.querySelector(`.${TICKET_CONFIG.DOWNLOAD_BTN_CLASS}`);
        if (btn) btn.addEventListener('click', downloadTicketAsImage);
    }

    async function downloadTicketAsImage(e) {
        if (e) e.preventDefault();
        const btn = e.currentTarget;
        const ticket = document.getElementById(TICKET_CONFIG.CONTAINER_ID);
        if (!ticket || btn.disabled) return;

        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Đang xử lý...';

        try {
            const canvas = await html2canvas(ticket, { useCORS: true, scale: 2, backgroundColor: null });
            const link = createElement('a', { 
                attributes: { 
                    href: canvas.toDataURL('image/png'), 
                    download: `ticket-${Date.now()}.png` 
                } 
            });
            link.click();
        } catch (error) {
            console.error("[Booking] Lỗi tải vé:", error);
            toast.error('Không thể tải vé lúc này. Vui lòng thử lại.');
        } finally {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    }

    function renderPaymentFailed(message, summary = null) {
        if (!dom.paymentCancel) return;

        showConfirmStep();

        replaceContent(dom.paymentCancel, [
            createElement('div', { className: 'text-center py-4' }, [
                createElement('div', { className: 'mb-3 text-danger display-4', text: '✕' }),
                createElement('h4', { className: 'fw-bold mb-2', text: 'Payment failed' }),
                createElement('p', { className: 'text-muted mb-0', text: message }),
            ])
        ]);
        dom.paymentCancel.classList.remove('d-none');

        if (dom.sidebarResult && dom.sidebarActions) {
            dom.sidebarActions.classList.add('d-none');
            dom.sidebarResult.classList.remove('d-none');

            let countdown = 15;
            const timerEl = createElement('span', { className: 'fw-bold text-primary', text: countdown.toString() });

            replaceContent(dom.sidebarResult, [
                createElement('div', { className: 'alert alert-danger radius-md border-0 mb-3' }, [
                    createElement('div', { className: 'fw-bold mb-1', text: '✕ Payment failed' }),
                    createElement('div', { className: 'small', text: message }),
                ]),
                createElement('div', { className: 'text-center small text-muted mb-3' }, [
                    createElement('span', { text: 'Redirecting back to movie page in ' }),
                    timerEl,
                    createElement('span', { text: ' seconds...' }),
                ]),
                createElement('button', {
                    className: 'btn btn-primary w-100 radius-md py-2 fw-bold mb-2',
                    text: 'Retry / Re-book',
                    attributes: { type: 'button' },
                    dataset: { action: 'retry' }
                })
            ]);

            if (summary) {
                if (summary.showtime) {
                    renderMovieInfo(summary.showtime);
                }

                const discounts = summary.discounts || {};
                bookingDiscount.promoCode = summary.voucher_code || null;
                bookingDiscount.promoValue = Number(discounts.voucher_discount) || 0;
                bookingDiscount.pointAmount = Number(discounts.point_discount) || 0;
                renderAppliedCouponStatus();

                if (summary.tickets) {
                    selectedSeats.clear();
                    totalSeatPrice = 0;
                    summary.tickets.forEach(t => {
                        const sId = String(t.seat_id || t.item_id || t.id);
                        selectedSeats.set(sId, { id: sId, name: t.name, price: Number(t.unit_price) });
                        totalSeatPrice += Number(t.unit_price);
                    });
                    renderSelectedSeatsList();
                }

                if (summary.products) {
                    selectedProducts.clear();
                    totalProductPrice = 0;
                    summary.products.forEach(p => {
                        const pId = Number(p.product_id || p.item_id || p.id);
                        selectedProducts.set(pId, {
                            id: pId,
                            name: p.name,
                            price: Number(p.unit_price),
                            qty: Number(p.quantity)
                        });
                        totalProductPrice += Number(p.unit_price) * Number(p.quantity);
                    });
                    renderSelectedProductsSidebar();
                }

                updateTotalPrice();
            }

            const interval = setInterval(() => {
                countdown--;
                timerEl.textContent = countdown.toString();
                if (countdown <= 0) {
                    clearInterval(interval);
                    window.location.href = getReturnDetailUrl();
                }
            }, 1000);

            // Nút thử lại trong sidebar
            dom.sidebarResult.querySelector('[data-action="retry"]')?.addEventListener('click', () => {
                clearInterval(interval);
                dom.sidebarResult.classList.add('d-none');
                dom.sidebarActions.classList.remove('d-none');
                dom.paymentCancel.classList.add('d-none');
                showPromotionStep();
            });
        }
    }

    // ─── Seat hold – refresh all held seats ───────────────────────────────────

    async function refreshSeatHolds() {
        if (selectedSeats.size === 0) return true;

        const results = await Promise.all(
            Array.from(selectedSeats.values(), (seat) =>
                apiFetch(`/showtimes/${showtimeId}/seat-holds`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': dom.csrfToken },
                    body: JSON.stringify({ seat_id: seat.id }),
                })
                    .then(async (res) => ({ ok: res.ok, data: await res.json().catch(() => ({})) }))
                    .catch(() => ({ ok: false, data: {} }))
            )
        );

        const failed = results.find((r) => !r.ok || r.data?.status !== 'success');
        if (failed) {
            toast.error(failed.data?.message || 'Không thể tiếp tục giữ ghế. Vui lòng chọn lại.');
            return false;
        }

        const latestExpiry = results
            .map((r) => r.data?.data?.expires_at)
            .filter(Boolean).sort().pop();
        scheduleHoldExpiration(latestExpiry);
        return true;
    }

    // ─── Seat selection ───────────────────────────────────────────────────────

    async function handleSeatSelection(el) {
        const seatId = el.dataset.id;
        if (!seatId || holdingRequests.has(seatId)) return;

        // Bỏ chọn ghế
        if (selectedSeats.has(seatId)) {
            totalSeatPrice -= selectedSeats.get(seatId).price;
            selectedSeats.delete(seatId);
            el.classList.remove('selected');
            saveSelectedSeats();
            updateTotalPrice();
            renderSelectedSeatsList();
            return;
        }

        // Chọn ghế
        const seat = { id: seatId, name: el.dataset.name, price: toAmount(el.dataset.price) };
        selectedSeats.set(seatId, seat);
        totalSeatPrice += seat.price;
        el.classList.add('selected');
        saveSelectedSeats();
        updateTotalPrice();
        renderSelectedSeatsList();

        // Giữ ghế qua API
        holdingRequests.add(seatId);
        try {
            const res = await apiFetch(`/showtimes/${showtimeId}/seat-holds`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': dom.csrfToken },
                body: JSON.stringify({ seat_id: seatId }),
            });
            const data = await res.json().catch(() => ({}));

            if (!res.ok || data.status !== 'success') {
                // Rollback
                totalSeatPrice -= seat.price;
                selectedSeats.delete(seatId);
                el.classList.remove('selected');
                saveSelectedSeats();
                updateTotalPrice();
                renderSelectedSeatsList();
                toast.error(data.message || 'Không thể giữ ghế.');
                return;
            }

            scheduleHoldExpiration(data.data?.expires_at);
        } catch (err) {
            console.error('[Booking] Lỗi giữ ghế:', err);
            totalSeatPrice -= seat.price;
            selectedSeats.delete(seatId);
            el.classList.remove('selected');
            saveSelectedSeats();
            updateTotalPrice();
            renderSelectedSeatsList();
            toast.error('Đã xảy ra lỗi khi giữ ghế. Vui lòng thử lại.');
        } finally {
            holdingRequests.delete(seatId);
        }
    }

    // ─── Product quantity ─────────────────────────────────────────────────────

    async function updateProductQuantity(productId, delta) {
        const existing = selectedProducts.get(productId);

        if (!existing) {
            if (delta <= 0 || !productCache) return;
            const product = productLookup.get(productId);
            if (!product) return;
            selectedProducts.set(productId, { id: productId, name: product.name, price: toAmount(product.price), qty: 1 });
            totalProductPrice += toAmount(product.price);
        } else {
            existing.qty += delta;
            totalProductPrice += existing.price * delta;
            if (existing.qty <= 0) {
                totalProductPrice -= existing.price * existing.qty; // Adjust for over-reduction
                selectedProducts.delete(productId);
            }
        }

        const qtySpan = document.getElementById(`qty-${productId}`);
        if (qtySpan) qtySpan.textContent = selectedProducts.get(productId)?.qty || 0;

        updateTotalPrice();
        renderSelectedProductsSidebar();

        // Re-validate coupon nếu đang áp dụng
        if (bookingDiscount.promoCode) {
            await handleApplyCouponCode(bookingDiscount.promoCode);
        }
    }

    // ─── Promotion – load, voucher register, apply ────────────────────────────

    async function preloadLoyaltyPoints() {
        if (promotionCache) return promotionCache;
        try {
            const res = await apiFetch('/customers/me/loyalty-points');
            const data = await res.json().catch(() => ({}));
            promotionCache = {
                memberInfo: res.ok && data.status === 'success' ? data.data : { points: 0 },
            };
        } catch {
            promotionCache = { memberInfo: { points: 0 } };
        }
        return promotionCache;
    }

    function renderPromotionUI(promotionData) {
        if (dom.pointsInput) {
            dom.pointsInput.placeholder = `Bạn có ${promotionData.memberInfo.points} điểm`;
        }
    }

    async function renderRegisteredVouchers() {
        if (!dom.registeredCoupon) return;
        setRegisteredCouponState('Đang tải mã đã đăng ký...', 'small text-muted');

        try {
            const res = await apiFetch('/customer/registered-promotions');
            const data = await res.json().catch(() => ({}));

            if (!res.ok || data.status !== 'success') {
                setRegisteredCouponState(data.message || 'Không thể tải danh sách mã.', 'small text-danger');
                return;
            }

            const vouchers = data.data || [];
            if (vouchers.length === 0) {
                setRegisteredCouponState('Bạn chưa có mã nào đã đăng ký.', 'small text-muted');
                return;
            }
            replaceContent(dom.registeredCoupon, vouchers.map(createVoucherRow));
        } catch (err) {
            console.error('[Booking] Lỗi tải voucher:', err);
            setRegisteredCouponState('Lỗi tải dữ liệu mã đã đăng ký.', 'small text-danger');
        }
    }

    async function handleRegisterVoucher() {
        const code = dom.couponCodeInput?.value?.trim();
        const passwordInput = document.getElementById('coupon-password-input');
        const password = passwordInput?.value?.trim();

        if (!code) { toast.warning('Vui lòng nhập mã khuyến mãi.'); return; }
        if (!password) { toast.warning('Vui lòng nhập mật khẩu xác nhận.'); return; }

        const btn = dom.btnRegisterCoupon;
        if (btn) btn.disabled = true;

        try {
            const res = await apiFetch('/customer/register-promotion', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code, password }),
            });
            const data = await res.json().catch(() => ({}));

            if (!res.ok || data.status !== 'success') {
                toast.error(data.message || 'Đăng ký mã thất bại.');
                return;
            }

            toast.success(data.message || 'Đăng ký mã thành công!');
            if (dom.couponCodeInput) dom.couponCodeInput.value = '';
            if (passwordInput) passwordInput.value = '';
            await renderRegisteredVouchers();
        } catch (err) {
            console.error('[Booking] Lỗi đăng ký voucher:', err);
            toast.error('Không thể xử lý lúc này. Vui lòng thử lại.');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    async function handleApplyCouponCode(code) {
        if (!code) { toast.warning('Vui lòng nhập mã giảm giá.'); return; }

        try {
            const res = await apiFetch('/promotions/validate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': dom.csrfToken },
                body: JSON.stringify({
                    code,
                    showtime_id: showtimeId,
                    items: [
                        ...Array.from(selectedSeats.values(), (s) => ({ type: 'seat', id: s.id, unit_price: s.price })),
                        ...Array.from(selectedProducts.values(), (p) => ({ type: 'product', id: p.id, unit_price: p.price, quantity: p.qty })),
                    ],
                }),
            });
            const data = await res.json().catch(() => ({}));

            if (!res.ok || data.status !== 'success') {
                toast.error(data.message || 'Mã không hợp lệ.');
                return;
            }

            const discountValue = toAmount(data.data?.discount_value);
            if (discountValue >= getSubtotal()) {
                toast.warning('Mã giảm giá không hợp lệ (giá trị lớn hơn hoặc bằng tổng tiền).');
                return;
            }

            bookingDiscount.promoId = Number(data.data?.promotion_id) || null;
            bookingDiscount.promoCode = code;
            bookingDiscount.promoValue = discountValue;

            replaceContent(dom.couponMessage, [
                createElement('span', { className: 'text-success fw-semibold', text: `✓ Áp dụng mã thành công: −${formatCurrency(discountValue)}` }),
            ]);

            renderAppliedCouponStatus();
            await renderRegisteredVouchers();
            updateTotalPrice();
        } catch (err) {
            console.error('[Booking] Lỗi kiểm tra coupon:', err);
            toast.error('Không thể kiểm tra mã giảm giá lúc này.');
        }
    }

    // ─── Steps visibility ─────────────────────────────────────────────────────

    function hideAllCards() {
        if (dom.seatMap) dom.seatMap.style.display = 'none';
        if (dom.cardSeat) dom.cardSeat.style.display = 'none';
        if (dom.cardProduct) dom.cardProduct.style.display = 'none';
        if (dom.cardPromotion) dom.cardPromotion.style.display = 'none';
    }

    function restoreSidebar() {
        const sidebar = document.getElementById('booking-sidebar');
        const mainCol = document.getElementById('main-booking-col');
        if (sidebar) sidebar.style.display = 'block';
        if (mainCol) {
            mainCol.classList.remove('col-md-10', 'offset-md-1', 'col-md-12');
            mainCol.classList.add('col-md-8');
        }
    }

    function showSeatStep() {
        restoreSidebar();
        hideAllCards();
        if (dom.seatMap) dom.seatMap.style.display = 'block';
        if (dom.cardSeat) dom.cardSeat.style.display = 'block';
        if (dom.chooseSeat) setActiveStep(dom.chooseSeat);
        if (dom.btnContinue) {
            dom.btnContinue.textContent = 'Tiếp tục';
            dom.btnContinue.className = dom.btnContinue.className
                .replace('btn-primary', 'btn-primary');
        }
        localStorage.setItem(storageKey('booking_step'), 'seats');
    }

    async function showProductStep() {
        restoreSidebar();
        hideAllCards();
        if (dom.cardProduct) dom.cardProduct.style.display = 'block';
        if (dom.chooseProduct) setActiveStep(dom.chooseProduct);
        if (dom.btnContinue) dom.btnContinue.textContent = 'Tiếp tục';
        localStorage.setItem(storageKey('booking_step'), 'products');

        try {
            if (!productCache) {
                renderProductSkeletons();
                const res = await apiFetch('/products');
                const data = await res.json().catch(() => ({}));
                if (!res.ok || data.status !== 'success') throw new Error('Không thể tải sản phẩm');
                productCache = data.data;
                productLookup = new Map(productCache.map((p) => [Number(p.id), p]));
            }
            renderProducts(productCache);

            preloadLoyaltyPoints().catch(() => { promotionCache = null; });
        } catch (err) {
            console.error('[Booking] Lỗi tải sản phẩm:', err);
            toast.error('Không thể tải danh sách sản phẩm.');
        }
    }

    async function showPromotionStep() {
        restoreSidebar();
        hideAllCards();
        if (dom.cardPromotion) dom.cardPromotion.style.display = 'block';
        if (dom.choosePromotion) setActiveStep(dom.choosePromotion);
        if (dom.btnContinue) {
            dom.btnContinue.textContent = 'Thanh toán';
            dom.btnContinue.className = dom.btnContinue.className
                .replace('btn-primary', 'btn-primary');
        }
        localStorage.setItem(storageKey('booking_step'), 'promotions');

        if (dom.pointsInput) dom.pointsInput.placeholder = 'Đang tải điểm tích lũy...';
        setRegisteredCouponState('Đang tải mã đã đăng ký...', 'small text-muted');

        try {
            promotionCache = await preloadLoyaltyPoints();
            renderPromotionUI(promotionCache);
            await renderRegisteredVouchers();
        } catch (err) {
            console.error('[Booking] Lỗi tải khuyến mãi:', err);
            promotionCache = { memberInfo: { points: 0 } };
            renderPromotionUI(promotionCache);
            setRegisteredCouponState('Không thể tải danh sách mã đã đăng ký.', 'small text-danger');
        }
    }

    function showConfirmStep() {
        hideAllCards();
        if (dom.cardConfirm) dom.cardConfirm.style.display = 'block';
        if (dom.paymentSuccess) dom.paymentSuccess.classList.add('d-none');
        if (dom.paymentCancel) dom.paymentCancel.classList.add('d-none');
        if (dom.chooseConfirm) setActiveStep(dom.chooseConfirm);
        if (dom.btnContinue) dom.btnContinue.style.display = 'none';
        if (dom.btnBack) dom.btnBack.style.display = 'none';

        const sidebar = document.getElementById('booking-sidebar');
        const mainCol = document.getElementById('main-booking-col');
        if (sidebar) sidebar.style.display = 'none';
        if (mainCol) {
            mainCol.classList.remove('col-md-8');
            mainCol.classList.add('col-md-10', 'offset-md-1');
        }
    }

    // ─── Payment ──────────────────────────────────────────────────────────────

    async function handlePayment() {
        const seats = Array.from(selectedSeats.values(), (s) => ({ id: Number(s.id) }));
        const products = Array.from(selectedProducts.values(), (p) => ({ id: Number(p.id), qty: Number(p.qty) }));

        if (dom.btnContinue) {
            dom.btnContinue.disabled = true;
            dom.btnContinue.textContent = 'Đang xử lý...';
        }

        try {
            const items = [
                ...Array.from(selectedSeats.values(), (s) => ({
                    id: s.id,
                    type: 'seat',
                    quantity: 1,
                    unit_price: s.price
                })),
                ...Array.from(selectedProducts.values(), (p) => ({
                    id: p.id,
                    type: 'product',
                    quantity: p.qty,
                    unit_price: p.price
                }))
            ];

            const res = await apiFetch('/payments', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': dom.csrfToken },
                body: JSON.stringify({
                    showtime_id: Number(showtimeId),
                    voucher_code: bookingDiscount.promoCode || null,
                    points_used: bookingDiscount.pointAmount || 0,
                    items: items,
                    payment_gateway: 'payos',
                }),
            });
            const data = await res.json().catch(() => ({}));

            if (res.ok && data.status === 'success') {
                window.location.href = data.data?.checkout_url || data.checkoutUrl;
                return;
            }

            // Hiện lỗi validation nếu có
            if (data.errors) {
                const firstError = Object.values(data.errors).flat()[0];
                toast.error(firstError || data.message || 'Không thể tạo thanh toán.');
            } else {
                toast.error(data.message || 'Không thể tạo thanh toán.');
            }
        } catch (err) {
            console.error('[Booking] Lỗi payment:', err);
            toast.error('Đã xảy ra lỗi khi kết nối máy chủ. Vui lòng thử lại.');
        } finally {
            if (dom.btnContinue) {
                dom.btnContinue.disabled = false;
                dom.btnContinue.textContent = 'Thanh toán';
            }
        }
    }

    // ─── Payment confirmation polling ─────────────────────────────────────────

    async function loadPaymentConfirmation(orderCode) {
        if (!orderCode) { renderPaymentFailed('Không tìm thấy thông tin giao dịch.'); return; }

        showConfirmStep();
        if (dom.paymentSuccess) {
            replaceContent(dom.paymentSuccess, [
                createElement('div', { className: 'text-center py-5' }, [
                    createElement('div', { className: 'spinner-border text-primary mb-3', attributes: { role: 'status' } }, [
                        createElement('span', { className: 'visually-hidden', text: 'Đang tải...' }),
                    ]),
                    createElement('p', { className: 'text-muted', text: 'Đang xác nhận thanh toán và cập nhật dữ liệu...' }),
                ]),
            ]);
            dom.paymentSuccess.classList.remove('d-none');
        }

        const MAX_ATTEMPTS = 8;
        const DELAY_MS = 1500;

        for (let attempt = 0; attempt < MAX_ATTEMPTS; attempt++) {
            try {
                const res = await apiFetch(`/payments/orders/${orderCode}`);
                const data = await res.json().catch(() => ({}));

                if (!res.ok || data.status !== 'success') {
                    throw new Error(data.message || 'Không thể tải thông tin đơn hàng');
                }

                if (data.data?.status === 2 || data.data?.payment_status === 'paid') {
                    clearBookingStorage();
                    renderPaymentSuccess(data.data);
                    return;
                }
            } catch (err) {
                if (attempt === MAX_ATTEMPTS - 1) {
                    renderPaymentFailed(err.message || 'Không thể xác nhận thanh toán.');
                    return;
                }
            }

            await new Promise((r) => setTimeout(r, DELAY_MS));
        }

        renderPaymentFailed('Hệ thống chưa kịp cập nhật kết quả thanh toán. Vui lòng thử lại sau.');
    }

    async function loadPaymentCancellation(orderCode) {
        if (!orderCode) { renderPaymentFailed('Giao dịch đã bị hủy.'); return; }
        showConfirmStep();
        try {
            const res = await apiFetch(`/payments/orders/${orderCode}`);
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.status !== 'success') throw new Error('Không thể tải thông tin đơn hàng');
            renderPaymentFailed('Giao dịch đã bị hũy hoặc thanh toán thất bại.', data.data);
        } catch (err) {
            renderPaymentFailed('Giao dịch đã bị hủy, không thể tải chi tiết hóa đơn (hoặc hóa đơn hẹn giờ đã bị xóa tự động).');
        }
    }

    // ─── Event setup ─────────────────────────────────────────────────────────

    function setupEvents() {
        // Đăng ký mã khuyến mãi
        if (dom.btnRegisterCoupon) {
            dom.btnRegisterCoupon.addEventListener('click', handleRegisterVoucher);
        }

        // Nút áp dụng / hủy voucher trong bảng
        if (dom.registeredCoupon) {
            dom.registeredCoupon.addEventListener('click', async (e) => {
                const btn = e.target.closest('.registered-voucher-toggle');
                if (!btn) return;
                if (btn.dataset.action === 'cancel') {
                    clearAppliedCoupon();
                    await renderRegisteredVouchers();
                } else {
                    await handleApplyCouponCode(btn.dataset.code || '');
                }
            });
        }

        // Nút áp dụng mã nhập tay
        const btnApplyCoupon = document.getElementById('btn-apply-coupon');
        if (btnApplyCoupon) {
            btnApplyCoupon.addEventListener('click', async () => {
                await handleApplyCouponCode(dom.couponApplyInput?.value.trim() || '');
            });
        }



        // Nút áp dụng điểm
        const btnApplyPoints = document.getElementById('btn-apply-points');
        if (btnApplyPoints) {
            btnApplyPoints.addEventListener('click', () => {
                const pts = Number.parseInt(dom.pointsInput?.value.trim() || '', 10);
                const userPoints = promotionCache?.memberInfo?.points || 0;

                if (Number.isNaN(pts) || pts <= 0) { toast.warning('Vui lòng nhập số điểm hợp lệ.'); return; }
                if (pts > userPoints) { toast.error('Bạn không đủ điểm tích lũy.'); return; }

                bookingDiscount.pointAmount = pts;
                updateTotalPrice();
                toast.success(`Đã áp dụng ${pts} điểm (−${formatCurrency(pts)}).`);
            });
        }

        // Click chọn ghế (event delegation)
        if (dom.seatMap) {
            dom.seatMap.addEventListener('click', (e) => {
                const seatEl = e.target.closest('.seat.available');
                if (seatEl) handleSeatSelection(seatEl);
            });
        }

        // Click thay đổi số lượng sản phẩm (event delegation)
        if (dom.productMap) {
            dom.productMap.addEventListener('click', (e) => {
                const btn = e.target.closest('.product-qty-btn');
                if (!btn) return;
                updateProductQuantity(Number(btn.dataset.id), Number(btn.dataset.delta));
            });
        }

        // Nút Tiếp tục / Thanh toán
        if (dom.btnContinue) {
            dom.btnContinue.addEventListener('click', async () => {
                const isSeatStep = dom.seatMap && dom.seatMap.style.display !== 'none';
                const isProductStep = dom.cardProduct && dom.cardProduct.style.display !== 'none';

                if (isSeatStep) {
                    if (selectedSeats.size === 0) { toast.warning('Vui lòng chọn ghế trước khi tiếp tục.'); return; }
                    await showProductStep();
                    refreshSeatHolds().catch(() => false);
                    return;
                }

                if (isProductStep) {
                    const held = await refreshSeatHolds();
                    if (!held) return;
                    await showPromotionStep();
                    return;
                }

                // Promotion step → thanh toán
                await handlePayment();
            });
        }

        // Nút Quay lại
        if (dom.btnBack) {
            dom.btnBack.addEventListener('click', () => {
                if (dom.cardPromotion && dom.cardPromotion.style.display !== 'none') {
                    showProductStep();
                } else if (dom.cardProduct && dom.cardProduct.style.display !== 'none') {
                    showSeatStep();
                } else {
                    history.back();
                }
            });
        }
    }

    // ─── Init ─────────────────────────────────────────────────────────────────

    async function init() {
        // Kết quả callback từ PayOS
        if (paymentStatus === 'success') {
            await loadPaymentConfirmation(paymentOrderCode);
            return;
        }
        if (paymentStatus === 'cancelled') {
            await loadPaymentCancellation(paymentOrderCode);
            return;
        }

        restoreSavedSeats();
        restoreHoldExpiration();
        setupEvents();

        try {
            const res = await apiFetch(`/showtimes/${showtimeId}`);
            const data = await res.json().catch(() => ({}));

            if (!res.ok || data.status !== 'success') {
                toast.error('Không thể tải thông tin suất chiếu. Vui lòng thử lại.');
                return;
            }

            renderMovieInfo(data.data.showtime);
            renderSeats(data.data.seats);
            renderSelectedSeatsList();
            renderSelectedProductsSidebar();
            updateTotalPrice();

            // Khôi phục step đã lưu
            const savedStep = localStorage.getItem(storageKey('booking_step'));
            if (savedStep === 'promotions') {
                await showProductStep();
                await showPromotionStep();
            } else if (savedStep === 'products') {
                await showProductStep();
            } else {
                showSeatStep();
            }
        } catch (err) {
            console.error('[Booking] Lỗi khởi tạo:', err);
            toast.error('Đã xảy ra lỗi. Vui lòng tải lại trang.');
        }
    }

    init();
});


