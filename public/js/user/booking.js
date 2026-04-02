document.addEventListener('DOMContentLoaded', function () {
    const pathSegments = window.location.pathname.split('/');
    const showtimeId = pathSegments.filter(Boolean).pop();

    if (!showtimeId || Number.isNaN(Number.parseInt(showtimeId, 10))) {
        console.error('ID suat chieu khong hop le');
        return;
    }

    const dom = {
        btnContinue: document.getElementById('btn-continue'),
        btnBack: document.getElementById('btn-back'),
        seatMap: document.getElementById('seat-map'),
        cardSeat: document.getElementById('card-seat'),
        cardProduct: document.getElementById('card-product'),
        cardPromotion: document.getElementById('card-promotion'),
        chooseSeat: document.getElementById('choose-seat'),
        chooseProduct: document.getElementById('choose-product'),
        choosePromotion: document.getElementById('choose-promotion'),
        selectedSeatsList: document.getElementById('selected-seats-list'),
        selectedProductsList: document.getElementById('selected-products-list'),
        productMap: document.getElementById('product-map'),
        formUserPromotions: document.getElementById('form-user-promotions'),
        couponCodeInput: document.getElementById('coupon-code-input'),
        pointStart: document.getElementById('points-start'),
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
        promoCode: null,
        promoValue: 0,
        selectedVoucher: null,
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

    function getVoucherDiscount(subtotal) {
        if (!bookingDiscount.selectedVoucher) {
            return 0;
        }

        const voucher = bookingDiscount.selectedVoucher;
        return voucher.type === 'phan_tram'
            ? (subtotal * voucher.value) / 100
            : voucher.value;
    }

    function getTotalDiscount(subtotal) {
        return getVoucherDiscount(subtotal) + bookingDiscount.pointAmount + bookingDiscount.promoValue;
    }

    function getBookingStorageKey(name) {
        return `booking_${name}_${showtimeId}`;
    }

    function clearBookingStorage() {
        localStorage.removeItem('selected_seats');
        localStorage.removeItem('booking_step');
        localStorage.removeItem(getBookingStorageKey('hold_expires_at'));
        localStorage.removeItem(getBookingStorageKey('return_url'));
    }

    function resetBookingState() {
        selectedSeats.clear();
        selectedProducts.clear();
        holdingSeatRequests.clear();

        totalSeatPrice = 0;
        totalProductPrice = 0;
        bookingDiscount.promoCode = null;
        bookingDiscount.promoValue = 0;
        bookingDiscount.selectedVoucher = null;
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

        const couponMessage = document.getElementById('coupon-message');
        if (couponMessage) {
            couponMessage.innerHTML = '';
        }

        if (dom.formUserPromotions) {
            dom.formUserPromotions
                .querySelectorAll('input[name="user-voucher"]')
                .forEach(input => {
                    input.checked = false;
                });
        }

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
            alert('Het 10 phut giu ghe. He thong se dua ban ve trang chi tiet.');
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
        localStorage.setItem('selected_seats', JSON.stringify(Array.from(selectedSeats.values())));
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

    function renderSelectedSeatsList() {
        if (!dom.selectedSeatsList) {
            return;
        }

        if (selectedSeats.size === 0) {
            dom.selectedSeatsList.textContent = 'Chua chon ghe';
            return;
        }

        const names = Array.from(selectedSeats.values(), seat => seat.name).join(', ');
        dom.selectedSeatsList.innerHTML = `Ghe: <span class="text-dark">${names}</span>`;
    }

    function renderSelectedProductsSidebar() {
        if (!dom.selectedProductsList) {
            return;
        }

        if (selectedProducts.size === 0) {
            dom.selectedProductsList.innerHTML = '';
            return;
        }

        dom.selectedProductsList.innerHTML = Array.from(selectedProducts.values(), product => `
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span>${product.qty}x ${product.name}</span>
                <span class="fw-bold">${formatCurrency(product.price * product.qty)}</span>
            </div>
        `).join('');
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

    async function preloadPromotionData() {
        if (promotionCache) {
            return promotionCache;
        }

        const [voucherRes, memberRes] = await Promise.all([
            apiFetch('/customers/me/vouchers'),
            apiFetch('/customers/me/loyalty-points')
        ]);

        const voucherData = await voucherRes.json().catch(() => ({ status: 'error', data: [] }));
        const memberData = await memberRes.json().catch(() => ({ status: 'error', data: { points: 0 } }));

        promotionCache = {
            vouchers: voucherRes.ok && voucherData.status === 'success' ? voucherData.data : [],
            memberInfo: memberRes.ok && memberData.status === 'success' ? memberData.data : { points: 0 }
        };

        return promotionCache;
    }

    function updateProductQuantity(productId, delta) {
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

        localStorage.setItem('booking_step', 'seats');
    }

    async function handlePayment() {
        const subtotal = getSubtotal();
        const amount = Math.max(0, subtotal - getTotalDiscount(subtotal));

        try {
            const response = await apiFetch('/payments', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': dom.csrfToken
                },
                body: JSON.stringify({ amount })
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

            localStorage.setItem('booking_step', 'products');

            preloadPromotionData().catch(() => {
                promotionCache = null;
            });
        } catch (error) {
            alert('Khong the tai san pham');
        }
    }

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

        localStorage.setItem('booking_step', 'promotions');
    }

    function renderPromotionLoadingState() {
        if (dom.formUserPromotions) {
            dom.formUserPromotions.innerHTML = '<div class="text-muted small">Dang tai khuyen mai...</div>';
        }

        if (dom.pointStart) {
            dom.pointStart.placeholder = 'Dang tai diem tich luy...';
        }
    }

    async function showPromotionStep() {
        showPromotionStepLayout();
        renderPromotionLoadingState();

        try {
            promotionCache = await preloadPromotionData();
            renderPromotion(promotionCache);
        } catch (error) {
            console.error('Loi tai khuyen mai:', error);
            promotionCache = {
                vouchers: [],
                memberInfo: { points: 0 }
            };

            renderPromotion(promotionCache);
        }
    }

    function renderPromotion(promotionData) {
        const userPromo = dom.formUserPromotions;
        const pointStart = dom.pointStart;
        const { vouchers, memberInfo } = promotionData;

        if (userPromo) {
            userPromo.innerHTML = renderVoucherList(vouchers);
        }

        if (pointStart) {
            pointStart.placeholder = `Ban co ${memberInfo.points} diem`;
        }
    }

    function renderVoucherList(vouchers) {
        if (!vouchers || vouchers.length === 0) {
            return '<div class="text-muted small">Khong co voucher kha dung.</div>';
        }

        return vouchers.map(voucher => `
            <div class="form-check mb-3">
                <input
                    class="form-check-input"
                    type="checkbox"
                    name="user-voucher"
                    id="user-voucher-${voucher.id}"
                    value="${voucher.id}"
                    data-type="${voucher.discount_type}"
                    data-value="${voucher.discount_value}"
                >
                <label class="form-check-label" for="user-voucher-${voucher.id}">
                    ${voucher.name} - ${voucher.discount_type === 'phan_tram' ? `${voucher.discount_value}%` : formatCurrency(voucher.discount_value)}
                </label>
            </div>
        `).join('');
    }

    function setupPromotionEvent() {
        if (dom.formUserPromotions) {
            dom.formUserPromotions.addEventListener('change', function (event) {
                const target = event.target;
                if (target.name !== 'user-voucher') {
                    return;
                }

                const voucherInputs = dom.formUserPromotions.querySelectorAll('input[name="user-voucher"]');
                voucherInputs.forEach(input => {
                    if (input !== target) {
                        input.checked = false;
                    }
                });

                if (!target.checked) {
                    bookingDiscount.selectedVoucher = null;
                    updateTotalPrice();
                    return;
                }

                bookingDiscount.selectedVoucher = {
                    id: Number(target.value),
                    type: target.dataset.type,
                    value: toAmount(target.dataset.value)
                };
                updateTotalPrice();
            });
        }

        const btnApplyCoupon = document.getElementById('btn-apply-coupon');
        if (btnApplyCoupon) {
            btnApplyCoupon.addEventListener('click', async function () {
                const code = dom.couponCodeInput?.value.trim() || '';
                await handleApplyCouponCode(code);
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
            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                alert(result.message || 'Ma khong hop le');
                return;
            }

            bookingDiscount.promoCode = code;
            bookingDiscount.promoValue = toAmount(result.data.discount_value);

            const couponMessage = document.getElementById('coupon-message');
            if (couponMessage) {
                couponMessage.innerHTML = `<span class="text-success">Ap dung ma thanh cong: -${formatCurrency(result.data.discount_value)}</span>`;
            }

            updateTotalPrice();
        } catch (error) {
            console.error('Loi check coupon:', error);
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

        const fragment = document.createDocumentFragment();
        const rows = Object.keys(seatsByRow).sort().reverse();

        rows.forEach(row => {
            const rowDiv = document.createElement('div');
            rowDiv.className = 'd-flex justify-content-center align-items-center w-100 gap-2 mb-2';

            const leftLabel = document.createElement('div');
            leftLabel.className = 'row-label fw-bold small text-muted';
            leftLabel.innerText = row;
            rowDiv.appendChild(leftLabel);

            const seatsInRow = [...seatsByRow[row]].sort((a, b) => Number(a.so_ghe) - Number(b.so_ghe));
            seatsInRow.forEach(seat => {
                const seatEl = document.createElement('div');
                const isBooked = Boolean(seat.is_booked);
                const seatId = String(seat.id);

                seatEl.className = `seat ${isBooked ? 'booked' : 'available'}`;
                seatEl.innerText = seat.so_ghe;
                seatEl.dataset.id = seatId;
                seatEl.dataset.name = `${row}${seat.so_ghe}`;
                seatEl.dataset.price = toAmount(seat.gia_ghe);

                if (selectedSeats.has(seatId)) {
                    seatEl.classList.add('selected');
                }

                rowDiv.appendChild(seatEl);
            });

            const rightLabel = document.createElement('div');
            rightLabel.className = 'row-label fw-bold small text-muted';
            rightLabel.innerText = row;
            rowDiv.appendChild(rightLabel);

            fragment.appendChild(rowDiv);
        });

        dom.seatMap.innerHTML = '';
        dom.seatMap.appendChild(fragment);
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
        dom.productMap.innerHTML = products.map(product => {
            const productId = Number(product.id);
            const quantity = selectedProducts.get(productId)?.qty || 0;

            return `
                <div class="col-md-12 mb-3">
                    <div class="product-card d-flex align-items-center p-3 border rounded-4 bg-white shadow-sm">
                        <div class="product-img me-3 mr-3">
                            <img src="/storage/${product.hinh_anh_url}"
                                alt="${product.ten_san_pham}"
                                class="rounded-3 shadow-sm"
                                style="width: 80px; height: 80px; object-fit: cover;">
                        </div>

                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-bold text-dark">${product.ten_san_pham}</h6>
                            <p class="text-primary fw-bold mb-0">${formatCurrency(Number(product.gia_ban))}</p>
                        </div>

                        <div class="quantity-controls d-flex align-items-center gap-3 bg-light rounded-pill p-1 px-2">
                            <button class="btn btn-sm btn-white rounded-circle shadow-sm p-0 product-qty-btn"
                                    type="button"
                                    data-id="${productId}"
                                    data-delta="-1"
                                    style="width: 28px; height: 28px; line-height: 28px;">-</button>

                            <span id="qty-${productId}" class="fw-bold" style="min-width:20px; text-align:center;">${quantity}</span>

                            <button class="btn btn-sm btn-warning text-white rounded-circle shadow-sm p-0 product-qty-btn"
                                    type="button"
                                    data-id="${productId}"
                                    data-delta="1"
                                    style="width: 28px; height: 28px; line-height: 28px;">+</button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function restoreSavedSeats() {
        const savedSeat = localStorage.getItem('selected_seats');
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
            localStorage.removeItem('selected_seats');
        }
    }

    async function init() {
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

            const savedStep = localStorage.getItem('booking_step');
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
