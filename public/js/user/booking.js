document.addEventListener('DOMContentLoaded', function () {
    const pathSegments = window.location.pathname.split('/');
    const showtimeId = pathSegments.filter(Boolean).pop();
    const queryParams = new URLSearchParams(window.location.search);
    const paymentStatus = queryParams.get('paymentStatus');
    const paymentOrderCode = queryParams.get('orderCode');

    if (!showtimeId || Number.isNaN(Number.parseInt(showtimeId, 10))) {
        console.error('ID suat chieu khong hop le');
        return;
    }

    const dom = {
        btnRegisterCoupon: document.getElementById('btn-register-coupon'),
        passwordCoupon: document.getElementById('password-coupon'),
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
        couponCodeInput: document.getElementById('coupon-code-input'),
        pointStart: document.getElementById('points-start'),
        couponMessage: document.getElementById('coupon-message'),
        totalPrice: document.getElementById('total-price'),
        movieName: document.getElementById('book-movie-name'),
        poster: document.getElementById('book-poster'),
        time: document.getElementById('book-time'),
        date: document.getElementById('book-date'),
        currentTimeDisplay: document.getElementById('current-time-display'),
        cinema: document.getElementById('book-cinema'),
        room: document.getElementById('book-room'),
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || ''
    };

    const bookingDiscount = {
        promoId: null,
        promoCode: null,
        promoValue: 0,
        pointAmount: 0
    };

    const stepItems = Array.from(document.querySelectorAll('.list-inline-item'));
    const currencyFormatter = new Intl.NumberFormat('vi-VN');
    const selectedSeats = new Map();
    const selectedProducts = new Map();
    const holdingSeatRequests = new Set();

    let totalSeatPrice = 0;
    let totalProductPrice = 0;
    let productCache = null;
    let promotionCache = null;
    let productLookup = new Map();
    let currentShowtime = null;
    let holdExpirationTimeoutId = null;
    let isRefreshingToken = false;
    let tokenRefreshSubscribers = [];

    function formatCurrency(value) {
        return `${currencyFormatter.format(Number(value) || 0)} d`;
    }

    function toAmount(value) {
        const amount = Number(value);
        return Number.isFinite(amount) ? amount : 0;
    }

    function createElement(tagName, options = {}, children = []) {
        const element = document.createElement(tagName);

        if (options.className) {
            element.className = options.className;
        }

        if (options.text !== undefined) {
            element.textContent = options.text;
        }

        if (options.attributes) {
            Object.entries(options.attributes).forEach(([key, value]) => {
                if (value !== undefined && value !== null) {
                    element.setAttribute(key, String(value));
                }
            });
        }

        if (options.dataset) {
            Object.entries(options.dataset).forEach(([key, value]) => {
                if (value !== undefined && value !== null) {
                    element.dataset[key] = String(value);
                }
            });
        }

        children
            .filter(Boolean)
            .forEach(child => element.append(child));

        return element;
    }

    function replaceContent(target, children = []) {
        if (!target) {
            return;
        }

        target.replaceChildren(...children.filter(Boolean));
    }

    function createInfoLine(label, value, valueClassName = '') {
        return createElement('div', { className: 'small text-muted mt-2' }, [
            document.createTextNode(`${label}: `),
            createElement('span', {
                className: valueClassName,
                text: value
            })
        ]);
    }

    function createSummaryRow(label, value, options = {}) {
        const row = createElement('div', {
            className: `d-flex justify-content-between ${options.className || ''}`.trim()
        }, [
            createElement('span', { className: options.labelClassName || '', text: label }),
            createElement('strong', { className: options.valueClassName || '', text: value })
        ]);

        return row;
    }

    function createEmptyText(text) {
        return createElement('div', {
            className: 'small text-muted',
            text
        });
    }

    function createDetailItem(text) {
        return createElement('div', {
            className: 'mb-1',
            text
        });
    }

    function createSectionCard(title, bodyChildren = [], className = 'border rounded-4 p-3 mb-3') {
        const children = title
            ? [createElement('div', { className: 'fw-bold mb-2', text: title }), ...bodyChildren]
            : bodyChildren;

        return createElement('div', { className }, children);
    }

    function createSummaryInfoCard(title, lines = []) {
        return createElement('div', { className: 'border rounded-4 p-3 h-100' }, [
            createElement('div', { className: 'small text-muted mb-2', text: title }),
            ...lines
        ]);
    }

    function createActionLinkButton(href, text, className) {
        return createElement('a', {
            className,
            text,
            attributes: { href }
        });
    }

    function createActionButton(id, text, className, type = 'button', attributes = {}) {
        return createElement('button', {
            className,
            text,
            attributes: { id, type, ...attributes }
        });
    }

    function formatPromotionExpiry(value) {
        if (!value) {
            return '-';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return '-';
        }

        return date.toLocaleDateString('vi-VN');
    }

    function renderAppliedCouponStatus() {
        if (!dom.appliedCoupon) {
            return;
        }

        if (!bookingDiscount.promoCode) {
            replaceContent(dom.appliedCoupon);
            return;
        }

        replaceContent(dom.appliedCoupon, [
            createElement('div', {
                className: 'small text-primary',
                text: `Ma dang ap dung: ${bookingDiscount.promoCode}`
            })
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

    function createRegisteredVoucherRow(voucher) {
        const isApplied = bookingDiscount.promoCode === voucher.code;
        const actionClassName = isApplied
            ? 'btn btn-sm btn-outline-danger registered-voucher-toggle'
            : 'btn btn-sm btn-outline-primary registered-voucher-toggle';

        return createElement('tr', {}, [
            createElement('td', { className: 'fw-semibold text-dark', text: voucher.code }),
            createElement('td', { text: voucher.name || '-' }),
            createElement('td', { text: formatPromotionExpiry(voucher.expires_at) }),
            createElement('td', { className: 'text-center' }, [
                createActionButton(null, isApplied ? 'Huy' : 'Ap dung', actionClassName, 'button', {
                    'data-code': voucher.code,
                    'data-action': isApplied ? 'cancel' : 'apply'
                })
            ])
        ]);
    }

    function setRegisteredCouponState(text, className = 'small text-muted') {
        replaceContent(dom.registeredCoupon, [
            createElement('tr', {}, [
                createElement('td', {
                    className,
                    text,
                    attributes: {
                        colspan: 4
                    }
                })
            ])
        ]);
    }

    function createProductCard(product) {
        const productId = Number(product.id);
        const quantity = selectedProducts.get(productId)?.qty || 0;

        return createElement('div', { className: 'col-md-12 mb-3' }, [
            createElement('div', { className: 'product-card d-flex align-items-center p-3 border rounded-4 bg-white shadow-sm' }, [
                createElement('div', { className: 'product-img me-3 mr-3' }, [
                    createElement('img', {
                        className: 'rounded-3 shadow-sm',
                        attributes: {
                            src: `/storage/${product.hinh_anh_url}`,
                            alt: product.ten_san_pham,
                            style: 'width: 80px; height: 80px; object-fit: cover;'
                        }
                    })
                ]),
                createElement('div', { className: 'flex-grow-1' }, [
                    createElement('h6', { className: 'mb-1 fw-bold text-dark', text: product.ten_san_pham }),
                    createElement('p', { className: 'text-primary fw-bold mb-0', text: formatCurrency(Number(product.gia_ban)) })
                ]),
                createElement('div', { className: 'quantity-controls d-flex align-items-center gap-3 bg-light rounded-pill p-1 px-2' }, [
                    createActionButton(null, '-', 'btn btn-sm btn-white rounded-circle shadow-sm p-0 product-qty-btn', 'button', {
                        'data-id': productId,
                        'data-delta': -1,
                        style: 'width: 28px; height: 28px; line-height: 28px;'
                    }),
                    createElement('span', {
                        className: 'fw-bold',
                        text: quantity,
                        attributes: {
                            id: `qty-${productId}`,
                            style: 'min-width:20px; text-align:center;'
                        }
                    }),
                    createActionButton(null, '+', 'btn btn-sm btn-warning text-white rounded-circle shadow-sm p-0 product-qty-btn', 'button', {
                        'data-id': productId,
                        'data-delta': 1,
                        style: 'width: 28px; height: 28px; line-height: 28px;'
                    })
                ])
            ])
        ]);
    }

    function createSeatElement(row, seat) {
        const seatId = String(seat.id);
        const isBooked = Boolean(seat.is_booked);
        const seatEl = createElement('div', {
            className: `seat ${isBooked ? 'booked' : 'available'}`,
            text: seat.so_ghe,
            dataset: {
                id: seatId,
                name: `${row}${seat.so_ghe}`,
                price: toAmount(seat.gia_ghe)
            }
        });

        if (selectedSeats.has(seatId)) {
            seatEl.classList.add('selected');
        }

        return seatEl;
    }

    function createSeatRow(row, seats) {
        const seatItems = [...seats].sort((a, b) => Number(a.so_ghe) - Number(b.so_ghe));
        const rowLabel = () => createElement('div', {
            className: 'row-label fw-bold small text-muted',
            text: row
        });

        return createElement('div', {
            className: 'd-flex justify-content-center align-items-center w-100 gap-2 mb-2'
        }, [
            rowLabel(),
            ...seatItems.map(seat => createSeatElement(row, seat)),
            rowLabel()
        ]);
    }



    function subscribeTokenRefresh(callback) {
        tokenRefreshSubscribers.push(callback);
    }

    function flushTokenRefreshSubscribers(token) {
        tokenRefreshSubscribers.forEach(callback => callback(token));
        tokenRefreshSubscribers = [];
    }

    async function refreshAccessToken() {
        const response = await fetch('/api/auth/refresh-token', {
            method: 'POST',
            credentials: 'include',
            headers: {
                Accept: 'application/json'
            }
        });

        const result = await response.json().catch(() => ({}));
        if (!response.ok || result.status !== 'success' || !result.token) {
            return null;
        }

        localStorage.setItem('auth_token', result.token);
        return result.token;
    }

    async function apiFetch(url, options = {}) {
        const finalUrl = url.startsWith('/api') ? url : `/api${url.startsWith('/') ? '' : '/'}${url}`;
        const makeRequest = (token) => {
            const headers = {
                Accept: 'application/json',
                ...options.headers
            };

            if (token) {
                headers.Authorization = `Bearer ${token}`;
            }

            return fetch(finalUrl, {
                ...options,
                headers,
                credentials: 'include'
            });
        };

        let response = await makeRequest(localStorage.getItem('auth_token'));
        if (response.status !== 401 || finalUrl.includes('/auth/refresh-token')) {
            return response;
        }

        if (!isRefreshingToken) {
            isRefreshingToken = true;
            refreshAccessToken()
                .then((newToken) => {
                    isRefreshingToken = false;
                    flushTokenRefreshSubscribers(newToken);
                })
                .catch(() => {
                    isRefreshingToken = false;
                    flushTokenRefreshSubscribers(null);
                });
        }

        return new Promise((resolve) => {
            subscribeTokenRefresh(async (newToken) => {
                if (!newToken) {
                    resolve(response);
                    return;
                }

                response = await makeRequest(newToken);
                resolve(response);
            });
        });
    }

    function getSubtotal() {
        return totalSeatPrice + totalProductPrice;
    }

    function getTotalDiscount(subtotal) {
        const total = bookingDiscount.pointAmount + bookingDiscount.promoValue;
        return Math.min(total, subtotal);
    }

    function getBookingStorageKey(name) {
        return `booking_${name}_${showtimeId}`;
    }

    function clearBookingStorage() {
        localStorage.removeItem(getBookingStorageKey('selected_seats'));
        localStorage.removeItem(getBookingStorageKey('booking_step'));
        localStorage.removeItem(getBookingStorageKey('hold_expires_at'));
        localStorage.removeItem(getBookingStorageKey('return_url'));
    }

    function resetBookingState() {
        selectedSeats.clear();
        selectedProducts.clear();
        holdingSeatRequests.clear();

        totalSeatPrice = 0;
        totalProductPrice = 0;
        bookingDiscount.promoId = null;
        bookingDiscount.promoCode = null;
        bookingDiscount.promoValue = 0;
        bookingDiscount.pointAmount = 0;

        if (holdExpirationTimeoutId) {
            clearTimeout(holdExpirationTimeoutId);
            holdExpirationTimeoutId = null;
        }

        if (dom.couponCodeInput) {
            dom.couponCodeInput.value = '';
        }

        if (dom.pointStart) {
            dom.pointStart.value = '';
        }

        replaceContent(dom.couponMessage);
        renderAppliedCouponStatus();

        renderSelectedSeatsList();
        renderSelectedProductsSidebar();
        updateTotalPrice();
    }

    function getReturnDetailUrl() {
        const savedUrl = localStorage.getItem(getBookingStorageKey('return_url'));
        if (savedUrl) {
            return savedUrl;
        }

        const movieSlug = currentShowtime?.movie?.slug;
        const movieId = currentShowtime?.movie?.id;
        return movieSlug ? `/movie/${movieSlug}` : (movieId ? `/movie/${movieId}` : '/');
    }

    function expireBookingSession() {
        resetBookingState();
        clearBookingStorage();
        window.location.href = getReturnDetailUrl();
    }

    function scheduleHoldExpiration(expiresAt) {
        if (!expiresAt) {
            return;
        }

        const expiresAtMs = new Date(expiresAt).getTime();
        if (Number.isNaN(expiresAtMs)) {
            return;
        }

        localStorage.setItem(getBookingStorageKey('hold_expires_at'), new Date(expiresAtMs).toISOString());

        if (holdExpirationTimeoutId) {
            clearTimeout(holdExpirationTimeoutId);
        }

        const delay = expiresAtMs - Date.now();
        if (delay <= 0) {
            expireBookingSession();
            return;
        }

        holdExpirationTimeoutId = window.setTimeout(function () {
            expireBookingSession();
        }, delay);
    }

    function restoreHoldExpiration() {
        const savedExpiresAt = localStorage.getItem(getBookingStorageKey('hold_expires_at'));
        if (!savedExpiresAt) {
            return;
        }

        scheduleHoldExpiration(savedExpiresAt);
    }

    function saveSelectedSeats() {
        localStorage.setItem(getBookingStorageKey('selected_seats'), JSON.stringify(Array.from(selectedSeats.values())));
    }

    function setActiveStep(activeElement) {
        stepItems.forEach(item => item.classList.toggle('active', item === activeElement));
    }

    function updateTotalPrice() {
        const subtotal = getSubtotal();
        const finalPrice = Math.max(0, subtotal - getTotalDiscount(subtotal));

        if (dom.totalPrice) {
            dom.totalPrice.innerText = formatCurrency(finalPrice);
        }
    }

    function hideBookingFlowCards() {
        if (dom.seatMap) dom.seatMap.style.display = 'none';
        if (dom.cardSeat) dom.cardSeat.style.display = 'none';
        if (dom.cardProduct) dom.cardProduct.style.display = 'none';
        if (dom.cardPromotion) dom.cardPromotion.style.display = 'none';
    }

    function showConfirmStep() {
        hideBookingFlowCards();
        if (dom.cardConfirm) dom.cardConfirm.style.display = 'block';
        if (dom.paymentSuccess) dom.paymentSuccess.classList.add('d-none');
        if (dom.paymentCancel) dom.paymentCancel.classList.add('d-none');
        if (dom.chooseConfirm) setActiveStep(dom.chooseConfirm);
        if (dom.btnContinue) dom.btnContinue.style.display = 'none';
        if (dom.btnBack) dom.btnBack.style.display = 'none';
    }

    function renderPaymentFailed(message) {
        if (!dom.paymentCancel) {
            return;
        }

        showConfirmStep();
        replaceContent(dom.paymentCancel, [
            createElement('div', { className: 'text-center py-4' }, [
                createElement('div', {
                    className: 'mb-3 text-danger',
                    text: '!',
                    attributes: { style: 'font-size: 48px;' }
                }),
                createElement('h4', { className: 'fw-bold mb-2', text: 'Thanh toan that bai' }),
                createElement('p', { className: 'text-muted mb-4', text: message }),
                createElement('div', { className: 'd-flex justify-content-center gap-2 flex-wrap' }, [
                    createActionLinkButton(
                        getReturnDetailUrl(),
                        'Ve trang chi tiet phim',
                        'btn btn-outline-secondary rounded-pill px-4'
                    ),
                    createActionButton(
                        'retry-payment-btn',
                        'Thu lai',
                        'btn btn-warning text-white rounded-pill px-4'
                    )
                ])
            ])
        ]);
        dom.paymentCancel.classList.remove('d-none');

        const retryButton = document.getElementById('retry-payment-btn');
        if (retryButton) {
            retryButton.addEventListener('click', function () {
                dom.paymentCancel.classList.add('d-none');
                if (dom.btnContinue) dom.btnContinue.style.display = '';
                if (dom.btnBack) dom.btnBack.style.display = '';
                showPromotionStep();
            });
        }
    }

    function renderPaymentSuccess(summary) {
        if (!dom.paymentSuccess) {
            return;
        }

        const invoice = summary.invoice || {};
        const tickets = Array.isArray(summary.tickets) ? summary.tickets : [];
        const products = Array.isArray(summary.products) ? summary.products : [];
        const showtime = summary.showtime || {};

        const orderInfoColumn = createElement('div', { className: 'col-md-6' }, [
            createSummaryInfoCard('Don hang', [
                createElement('div', { className: 'fw-bold', text: summary.ma_don_hang || 'Dang cap nhat' }),
                createInfoLine('Order code', summary.order_code || ''),
                createInfoLine('Hoa don', invoice.ma_hoa_don || 'Dang cap nhat')
            ])
        ]);

        const showtimeInfoColumn = createElement('div', { className: 'col-md-6' }, [
            createSummaryInfoCard('Thong tin suat chieu', [
                createElement('div', { className: 'fw-bold', text: showtime.movie_name || 'Dang cap nhat' }),
                createInfoLine('Phong', showtime.room_name || 'Dang cap nhat'),
                createInfoLine(
                    'Thoi gian',
                    showtime.ngay_gio_chieu
                        ? new Date(showtime.ngay_gio_chieu).toLocaleString('vi-VN', { hour12: false })
                        : 'Dang cap nhat'
                )
            ])
        ]);

        const ticketChildren = tickets.length
            ? tickets.map(ticket => createDetailItem(`${ticket.ghe} - ${ticket.ma_ve} - ${formatCurrency(ticket.gia_ban)}`))
            : [createEmptyText('Dang cap nhat thong tin ghe.')];

        const productChildren = products.length
            ? products.map(product => createDetailItem(`${product.ten_san_pham} x${product.so_luong} - ${formatCurrency(product.don_gia)}`))
            : [createEmptyText('Khong co san pham di kem.')];

        const totalSummaryCard = createSectionCard('', [
            createSummaryRow('Tong tien goc', formatCurrency(invoice.tong_tien_goc ?? summary.tong_tien), { className: 'mb-2' }),
            createSummaryRow('Giam gia', formatCurrency(invoice.giam_gia ?? 0), { className: 'mb-2' }),
            createSummaryRow('Diem da dung', String(invoice.diem_su_dung ?? 0), { className: 'mb-2' }),
            createSummaryRow('Diem tich luy moi', String(invoice.diem_tich_luy ?? 0), { className: 'mb-2' }),
            createElement('hr'),
            createSummaryRow('Tong thanh toan', formatCurrency(invoice.tong_tien ?? summary.tong_tien), {
                labelClassName: 'fw-bold',
                valueClassName: 'text-success'
            })
        ], 'border rounded-4 p-3 bg-light mb-4');

        showConfirmStep();
        replaceContent(dom.paymentSuccess, [
            createElement('div', { className: 'py-2' }, [
                createElement('div', { className: 'mb-4' }, [
                    createElement('p', { className: 'small text-success fw-bold mb-2', text: 'THANH TOAN THANH CONG' }),
                    createElement('h4', { className: 'fw-bold mb-2', text: 'Dat ve thanh cong' }),
                    createElement('p', {
                        className: 'text-muted mb-0',
                        text: 'Thong tin thanh toan va don hang cua ban da duoc cap nhat.'
                    })
                ]),
                createElement('div', { className: 'row g-3 mb-4' }, [
                    orderInfoColumn,
                    showtimeInfoColumn
                ]),
                createSectionCard('Ghe da dat', ticketChildren),
                createSectionCard('San pham di kem', productChildren),
                totalSummaryCard,
                createElement('div', { className: 'd-flex gap-2 flex-wrap' }, [
                    createActionLinkButton('/', 'Ve trang chu', 'btn btn-dark rounded-pill px-4'),
                    createActionLinkButton(
                        getReturnDetailUrl(),
                        'Ve trang chi tiet phim',
                        'btn btn-outline-secondary rounded-pill px-4'
                    )
                ])
            ])
        ]);
        dom.paymentSuccess.classList.remove('d-none');
    }

    async function loadPaymentConfirmation(orderCode) {
        if (!orderCode) {
            renderPaymentFailed('Khong tim thay thong tin giao dich.');
            return;
        }

        showConfirmStep();
        if (dom.paymentSuccess) {
            replaceContent(dom.paymentSuccess, [
                createElement('div', {
                    className: 'text-muted',
                    text: 'Dang xac nhan thanh toan va cap nhat du lieu...'
                })
            ]);
            dom.paymentSuccess.classList.remove('d-none');
        }

        for (let attempt = 0; attempt < 8; attempt += 1) {
            try {
                const response = await apiFetch(`/payments/orders/${orderCode}`);
                const result = await response.json().catch(() => ({}));

                if (!response.ok || result.status !== 'success') {
                    throw new Error(result.message || 'Khong the tai thong tin don hang');
                }

                if (result.data?.trang_thai === 'paid' && result.data?.invoice) {
                    clearBookingStorage();
                    renderPaymentSuccess(result.data);
                    return;
                }
            } catch (error) {
                if (attempt === 7) {
                    renderPaymentFailed(error.message || 'Khong the xac nhan thanh toan.');
                    return;
                }
            }

            await new Promise(resolve => setTimeout(resolve, 1500));
        }

        renderPaymentFailed('He thong chua kip cap nhat ket qua thanh toan. Vui long thu lai sau.');
    }

    function renderSelectedSeatsList() {
        if (!dom.selectedSeatsList) {
            return;
        }

        if (selectedSeats.size === 0) {
            dom.selectedSeatsList.textContent = 'Chua chon ghe';
            return;
        }

        const names = Array.from(selectedSeats.values(), seat => seat.name).join(', ');
        replaceContent(dom.selectedSeatsList, [
            document.createTextNode('Ghe: '),
            createElement('span', { className: 'text-dark', text: names })
        ]);
    }

    function renderSelectedProductsSidebar() {
        if (!dom.selectedProductsList) {
            return;
        }

        if (selectedProducts.size === 0) {
            replaceContent(dom.selectedProductsList);
            return;
        }

        replaceContent(dom.selectedProductsList, Array.from(selectedProducts.values(), product => (
            createElement('div', { className: 'd-flex justify-content-between align-items-center mb-1' }, [
                createElement('span', { text: `${product.qty}x ${product.name}` }),
                createElement('span', {
                    className: 'fw-bold',
                    text: formatCurrency(product.price * product.qty)
                })
            ])
        )));
    }

    async function refreshSeatHolds() {
        if (selectedSeats.size === 0) {
            return true;
        }

        const holdRequests = Array.from(selectedSeats.values(), seat => apiFetch(`/showtimes/${showtimeId}/seat-holds`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': dom.csrfToken
                },
                body: JSON.stringify({ ghe_id: seat.id })
            }).then(async (response) => ({
                ok: response.ok,
                data: await response.json().catch(() => ({}))
            }))
        );

        const holdResults = await Promise.all(holdRequests);
        const failedHold = holdResults.find(result => !result.ok || result.data?.status !== 'success');
        if (failedHold) {
            alert(failedHold.data?.message || 'Khong the tiep tuc giu ghe');
            return false;
        }

        const latestExpiresAt = holdResults
            .map(result => result.data?.data?.expires_at)
            .filter(Boolean)
            .sort()
            .pop();

        scheduleHoldExpiration(latestExpiresAt);
        return true;
    }

    //Lấy dữ liệu khuyến mãi từ server
    async function preloadPromotionData() {
        if (promotionCache) {
            return promotionCache;
        }

        const memberRes = await apiFetch('/customers/me/loyalty-points');
        const memberData = await memberRes.json().catch(() => ({ status: 'error', data: { points: 0 } }));

        promotionCache = {
            memberInfo: memberRes.ok && memberData.status === 'success' ? memberData.data : { points: 0 }
        };

        return promotionCache;
    }

    async function updateProductQuantity(productId, delta) {
        const item = selectedProducts.get(productId);

        if (!item) {
            if (delta <= 0 || !productCache) {
                return;
            }

            const product = productLookup.get(productId);
            if (!product) {
                return;
            }

            selectedProducts.set(productId, {
                id: productId,
                name: product.ten_san_pham,
                price: toAmount(product.gia_ban),
                qty: 1
            });
            totalProductPrice += toAmount(product.gia_ban);
        } else {
            item.qty += delta;
            totalProductPrice += item.price * delta;

            if (item.qty <= 0) {
                totalProductPrice -= item.price * item.qty;
                selectedProducts.delete(productId);
            }
        }

        const qtySpan = document.getElementById(`qty-${productId}`);
        if (qtySpan) {
            qtySpan.innerText = selectedProducts.get(productId)?.qty || 0;
        }

        updateTotalPrice();
        renderSelectedProductsSidebar();
        if (bookingDiscount.promoCode) {
        await handleApplyCouponCode(bookingDiscount.promoCode);
    }
    }

    function showSeatStep() {
        if (dom.seatMap) dom.seatMap.style.display = 'block';
        if (dom.cardSeat) dom.cardSeat.style.display = 'block';
        if (dom.cardProduct) dom.cardProduct.style.display = 'none';
        if (dom.cardPromotion) dom.cardPromotion.style.display = 'none';
        if (dom.chooseSeat) setActiveStep(dom.chooseSeat);

        if (dom.btnContinue) {
            dom.btnContinue.innerText = 'Tiep tuc';
            dom.btnContinue.classList.remove('btn-success');
            dom.btnContinue.classList.add('btn-warning');
        }

        localStorage.setItem(getBookingStorageKey('booking_step'), 'seats');
    }

    async function handlePayment() {
        const seats = Array.from(selectedSeats.values(), seat => ({
            id: Number(seat.id)
        }));
        const products = Array.from(selectedProducts.values(), product => ({
            id: Number(product.id),
            qty: Number(product.qty)
        }));

        try {
            const response = await apiFetch('/payments', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': dom.csrfToken
                },
                body: JSON.stringify({
                    suat_chieu_id: Number(showtimeId),
                    seats,
                    products,
                    voucher_id: bookingDiscount.promoId || null,
                    point_used: bookingDiscount.pointAmount || 0
                })
            });
            const result = await response.json();

            if (response.ok && result.status === 'success') {
                window.location.href = result.checkoutUrl;
                return;
            }

            alert(result.message || 'Khong the tao thanh toan');
        } catch (error) {
            console.error('Loi payment:', error);
            alert('Da xay ra loi khi ket noi may chu.');
        }
    }

    async function showProductStep() {
        try {
            if (!productCache) {
                const response = await apiFetch('/products');
                const result = await response.json();

                if (!response.ok || result.status !== 'success') {
                    throw new Error('Khong the tai san pham');
                }

                productCache = result.data;
                productLookup = new Map(productCache.map(product => [Number(product.id), product]));
                renderProduct(productCache);
            }

            if (dom.seatMap) dom.seatMap.style.display = 'none';
            if (dom.cardSeat) dom.cardSeat.style.display = 'none';
            if (dom.cardProduct) dom.cardProduct.style.display = 'block';
            if (dom.cardPromotion) dom.cardPromotion.style.display = 'none';
            if (dom.chooseProduct) setActiveStep(dom.chooseProduct);
            if (dom.btnContinue) dom.btnContinue.innerText = 'Tiep tuc';

            localStorage.setItem(getBookingStorageKey('booking_step'), 'products');
            preloadPromotionData().catch(() => {
                promotionCache = null;
            });
        } catch (error) {
            alert('Khong the tai san pham');
        }
    }

    //Ẩn các phần giao diện khác
    function showPromotionStepLayout() {
        if (dom.seatMap) dom.seatMap.style.display = 'none';
        if (dom.cardSeat) dom.cardSeat.style.display = 'none';
        if (dom.cardProduct) dom.cardProduct.style.display = 'none';
        if (dom.cardPromotion) dom.cardPromotion.style.display = 'block';
        if (dom.choosePromotion) setActiveStep(dom.choosePromotion);

        if (dom.btnContinue) {
            dom.btnContinue.innerText = 'Thanh toan';
            dom.btnContinue.classList.remove('btn-warning');
            dom.btnContinue.classList.add('btn-success');
        }

        localStorage.setItem(getBookingStorageKey('booking_step'), 'promotions');
    }

    //Hiển thị trạng thái đang tải khuyến mãi
    function renderPromotionLoadingState() {
        if (dom.pointStart) {
            dom.pointStart.placeholder = 'Dang tai diem tich luy...';
        }

        setRegisteredCouponState('Dang tai ma da dang ky...');
    }


    async function showPromotionStep() {
        showPromotionStepLayout();
        renderPromotionLoadingState();

        try {
            promotionCache = await preloadPromotionData();
            renderPromotion(promotionCache);
            await renderRegisterVoucher();
        } catch (error) {
            console.error('Loi tai khuyen mai:', error);
            promotionCache = {
                memberInfo: { points: 0 }
            };

            renderPromotion(promotionCache);
            setRegisteredCouponState('Khong the tai danh sach ma da dang ky.');
        }
    }

    async function renderRegisterVoucher() {
        const container = dom.registeredCoupon;
        if (!container) {
            return;
        }

        try {
            setRegisteredCouponState('Dang tai ma da dang ky...');

            const response = await apiFetch('/customer/registered-promotions', {
                method: 'GET'
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok || result.status !== 'success') {
                setRegisteredCouponState(result.message || 'Khong the tai danh sach ma da dang ky.');
                return;
            }

            const vouchers = result.data || [];

            if (vouchers.length === 0) {
                setRegisteredCouponState('Ban chua co ma nao da dang ky.');
                return;
            }

            replaceContent(container, vouchers.map(createRegisteredVoucherRow));
        } catch (error) {
            console.error('Loi render voucher:', error);
            setRegisteredCouponState('Loi tai du lieu ma da dang ky.', 'small text-danger');
        }
    }

    async function handleRegisterVoucher() {
        const code = dom.couponCodeInput?.value?.trim();
        const password = dom.passwordCoupon?.value?.trim();
        const registerButton = dom.btnRegisterCoupon;

        if (!code || !password) {
            alert('Vui long nhap day du thong tin');
            return;
        }

        if (!registerButton) {
            return;
        }

        try {
            registerButton.disabled = true;

            const response = await apiFetch('/customer/register-promotion', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ code, password })
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok || result.status !== 'success') {
                alert(result.message || 'Dang ky ma that bai');
                return;
            }

            await renderRegisterVoucher();

            if (dom.couponCodeInput) {
                dom.couponCodeInput.value = '';
            }

            if (dom.passwordCoupon) {
                dom.passwordCoupon.value = '';
            }

            alert(result.message || 'Dang ky thanh cong');
        } catch (error) {
            console.error('Loi dang ky voucher:', error);
            alert('Khong the xu ly luc nay');
        } finally {
            registerButton.disabled = false;
        }
    }

    if (dom.btnRegisterCoupon) {
        dom.btnRegisterCoupon.addEventListener('click', handleRegisterVoucher);
    }


    function renderPromotion(promotionData) {
        const pointStart = dom.pointStart;
        const { memberInfo } = promotionData;

        if (pointStart) {
            pointStart.placeholder = `Ban co ${memberInfo.points} diem`;
        }
    }

    function setupPromotionEvent() {
        const btnApplyCoupon = document.getElementById('btn-apply-coupon');
        if (btnApplyCoupon) {
            btnApplyCoupon.addEventListener('click', async function () {
                const code = dom.couponCodeInput?.value.trim() || '';
                await handleApplyCouponCode(code);
            });
        }

        if (dom.registeredCoupon) {
            dom.registeredCoupon.addEventListener('click', async function (event) {
                const button = event.target.closest('.registered-voucher-toggle');
                if (!button) {
                    return;
                }

                if (button.dataset.action === 'cancel') {
                    clearAppliedCoupon();
                    await renderRegisterVoucher();
                    return;
                }

                await handleApplyCouponCode(button.dataset.code || '');
            });
        }

        const btnApplyPoints = document.getElementById('btn-apply-points');
        if (btnApplyPoints) {
            btnApplyPoints.addEventListener('click', function () {
                const pointsToUse = Number.parseInt(dom.pointStart?.value.trim() || '', 10);
                const userMaxPoints = promotionCache?.memberInfo?.points || 0;

                if (Number.isNaN(pointsToUse) || pointsToUse <= 0) {
                    alert('Vui long nhap so diem hop le');
                    return;
                }

                if (pointsToUse > userMaxPoints) {
                    alert('Ban khong du diem tich luy');
                    return;
                }

                bookingDiscount.pointAmount = pointsToUse;
                updateTotalPrice();
            });
        }
    }

    async function handleApplyCouponCode(code) {
        if (!code) {
            alert('Vui long nhap ma giam gia');
            return;
        }

        try {
            const response = await apiFetch('/promotions/validate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': dom.csrfToken
                },
                body: JSON.stringify({
                    code,
                    total_amount: getSubtotal()
                })
            });
            const result = await response.json().catch(() => ({}));

            if (!response.ok || result.status !== 'success') {
                alert(result.message || 'Ma khong hop le');
                return;
            }

            const discountValue = toAmount(result.data?.discount_value ?? result.data?.discount);
            const subtotal = getSubtotal();

            if (discountValue >= subtotal) {
                alert('Ma giam gia khong hop le (lon hon hoac bang tong tien)');
                return;
            }

            bookingDiscount.promoId = Number(result.data?.promotion_id) || null;
            bookingDiscount.promoCode = code;
            bookingDiscount.promoValue = discountValue;

            replaceContent(dom.couponMessage, [
                createElement('span', {
                    className: 'text-success',
                    text: `Ap dung ma thanh cong: -${formatCurrency(discountValue)}`
                })
            ]);

            renderAppliedCouponStatus();
            await renderRegisterVoucher();
            updateTotalPrice();
        } catch (error) {
            console.error('Loi check coupon:', error);
            alert('Khong the kiem tra ma giam gia luc nay');
        }
    }

    function renderMovieInfo(showtime) {
        currentShowtime = showtime;
        localStorage.setItem(getBookingStorageKey('return_url'), getReturnDetailUrl());
        if (dom.movieName) dom.movieName.innerText = showtime.movie.ten_phim;
        if (dom.poster) dom.poster.src = `/storage/${showtime.movie.poster_url}`;
        if (dom.time) dom.time.innerText = showtime.gio_chieu;
        if (dom.date) dom.date.innerText = showtime.ngay_chieu;
        if (dom.currentTimeDisplay) dom.currentTimeDisplay.innerText = showtime.gio_chieu;
        if (dom.cinema) dom.cinema.innerText = 'Galaxy Nguyen Du';
        if (dom.room) dom.room.innerText = showtime.room.ten_phong;
    }

    function renderSeats(seatsByRow) {
        if (!dom.seatMap) {
            return;
        }

        const rows = Object.keys(seatsByRow).sort().reverse();
        replaceContent(dom.seatMap, rows.map(row => createSeatRow(row, seatsByRow[row])));
    }

    async function handleSeatSelection(el) {
        const seatId = el.dataset.id;
        if (!seatId || holdingSeatRequests.has(seatId)) {
            return;
        }

        if (selectedSeats.has(seatId)) {
            totalSeatPrice -= selectedSeats.get(seatId).price;
            selectedSeats.delete(seatId);
            el.classList.remove('selected');
            saveSelectedSeats();
            updateTotalPrice();
            renderSelectedSeatsList();
            return;
        }

        const seat = {
            id: seatId,
            name: el.dataset.name,
            price: toAmount(el.dataset.price)
        };

        selectedSeats.set(seatId, seat);
        totalSeatPrice += seat.price;
        el.classList.add('selected');
        saveSelectedSeats();
        updateTotalPrice();
        renderSelectedSeatsList();

        holdingSeatRequests.add(seatId);
        try {
            const response = await apiFetch(`/showtimes/${showtimeId}/seat-holds`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': dom.csrfToken
                },
                body: JSON.stringify({ ghe_id: seatId })
            });
            const data = await response.json();

            if (!response.ok || data.status !== 'success') {
                totalSeatPrice -= seat.price;
                selectedSeats.delete(seatId);
                el.classList.remove('selected');
                saveSelectedSeats();
                updateTotalPrice();
                renderSelectedSeatsList();
                alert(data.message || 'Khong the giu ghe');
                return;
            }

            scheduleHoldExpiration(data.data?.expires_at);
        } catch (error) {
            console.error('Loi giu ghe', error);
            totalSeatPrice -= seat.price;
            selectedSeats.delete(seatId);
            el.classList.remove('selected');
            saveSelectedSeats();
            updateTotalPrice();
            renderSelectedSeatsList();
            alert('Da xay ra loi khi giu ghe. Vui long thu lai.');
        } finally {
            holdingSeatRequests.delete(seatId);
        }
    }

    function renderProduct(products) {
        if (!dom.productMap || !dom.cardProduct) {
            return;
        }

        dom.cardProduct.style.display = 'block';
        dom.productMap.className = 'row g-3';
        replaceContent(dom.productMap, products.map(createProductCard));
    }

    function restoreSavedSeats() {
        const savedSeat = localStorage.getItem(getBookingStorageKey('selected_seats'));
        if (!savedSeat) {
            return;
        }

        try {
            const seats = JSON.parse(savedSeat);
            if (!Array.isArray(seats)) {
                return;
            }

            seats.forEach(seat => {
                if (!seat?.id || selectedSeats.has(String(seat.id))) {
                    return;
                }

                const normalizedSeat = {
                    id: String(seat.id),
                    name: seat.name,
                    price: Number(seat.price) || 0
                };

                selectedSeats.set(normalizedSeat.id, normalizedSeat);
                totalSeatPrice += normalizedSeat.price;
            });
        } catch (error) {
            localStorage.removeItem(getBookingStorageKey('selected_seats'));
        }
    }

    async function init() {
        if (paymentStatus === 'success') {
            await loadPaymentConfirmation(paymentOrderCode);
            return;
        }

        if (paymentStatus === 'cancelled') {
            renderPaymentFailed('Giao dich da bi huy hoac thanh toan chua thanh cong.');
            return;
        }

        restoreSavedSeats();
        setupPromotionEvent();
        restoreHoldExpiration();

        try {
            const response = await apiFetch(`/showtimes/${showtimeId}`);
            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                return;
            }

            renderMovieInfo(result.data.showtime);
            renderSeats(result.data.seats);
            renderSelectedSeatsList();
            renderSelectedProductsSidebar();
            updateTotalPrice();

            const savedStep = localStorage.getItem(getBookingStorageKey('booking_step'));
            if (savedStep === 'promotions') {
                await showProductStep();
                await showPromotionStep();
            } else if (savedStep === 'products') {
                await showProductStep();
            } else {
                showSeatStep();
            }
        } catch (error) {
            console.error('Loi tai thong tin suat chieu', error);
        }
    }

    if (dom.btnContinue) {
        dom.btnContinue.addEventListener('click', async function () {
            const isSeatStepVisible = dom.seatMap && dom.seatMap.style.display !== 'none';
            const isProductStepVisible = dom.cardProduct && dom.cardProduct.style.display !== 'none';

            if (isSeatStepVisible) {
                if (selectedSeats.size === 0) {
                    alert('Vui long chon ghe truoc khi tiep tuc');
                    return;
                }

                await showProductStep();
                refreshSeatHolds().catch(() => false);
                return;
            }

            if (isProductStepVisible) {
                const isHoldRefreshed = await refreshSeatHolds();
                if (!isHoldRefreshed) {
                    return;
                }

                await showPromotionStep();
                return;
            }

            handlePayment();
        });
    }

    if (dom.btnBack) {
        dom.btnBack.addEventListener('click', function () {
            if (dom.cardPromotion && dom.cardPromotion.style.display !== 'none') {
                showProductStep();
                return;
            }

            if (dom.cardProduct && dom.cardProduct.style.display !== 'none') {
                showSeatStep();
                return;
            }

            history.back();
        });
    }

    if (dom.seatMap) {
        dom.seatMap.addEventListener('click', function (event) {
            const seatEl = event.target.closest('.seat.available');
            if (!seatEl) {
                return;
            }

            handleSeatSelection(seatEl);
        });
    }

    if (dom.productMap) {
        dom.productMap.addEventListener('click', function (event) {
            const button = event.target.closest('.product-qty-btn');
            if (!button) {
                return;
            }

            const productId = Number(button.dataset.id);
            const delta = Number(button.dataset.delta);
            updateProductQuantity(productId, delta);
        });
    }

    init();
});
