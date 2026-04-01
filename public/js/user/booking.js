document.addEventListener('DOMContentLoaded', function () {
    const pathSegments = window.location.pathname.split('/');
    const showtimeId = pathSegments.filter(Boolean).pop();

    if (!showtimeId || Number.isNaN(Number.parseInt(showtimeId, 10))) {
        console.error('ID suất chiếu không hợp lệ');
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
        promotionMap: document.getElementById('promotion-map'),
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

    const stepItems = Array.from(document.querySelectorAll('.list-inline-item'));
    const currencyFormatter = new Intl.NumberFormat('vi-VN');
    const selectedSeats = new Map();
    const selectedProducts = new Map();
    const holdingSeatRequests = new Set();

    let totalSeatPrice = 0;
    let totalProductPrice = 0;
    let productCache = null;
    let productLookup = new Map();

    function formatCurrency(value) {
        return `${currencyFormatter.format(value)} đ`;
    }

    function saveSelectedSeats() {
        localStorage.setItem('selected_seats', JSON.stringify(Array.from(selectedSeats.values())));
    }

    function setActiveStep(activeElement) {
        stepItems.forEach(item => item.classList.toggle('active', item === activeElement));
    }

    function updateTotalPrice() {
        if (dom.totalPrice) {
            dom.totalPrice.innerText = formatCurrency(totalSeatPrice + totalProductPrice);
        }
    }

    function renderSelectedSeatsList() {
        if (!dom.selectedSeatsList) {
            return;
        }

        if (selectedSeats.size === 0) {
            dom.selectedSeatsList.textContent = 'Chưa chọn ghế';
            return;
        }

        const names = Array.from(selectedSeats.values(), seat => seat.name).join(', ');
        dom.selectedSeatsList.innerHTML = `Ghế: <span class="text-dark">${names}</span>`;
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
                price: Number(product.gia_ban),
                qty: 1
            });
            totalProductPrice += Number(product.gia_ban);
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
        if (dom.seatMap) {
            dom.seatMap.style.display = 'block';
        }
        if (dom.cardSeat) {
            dom.cardSeat.style.display = 'block';
        }
        if (dom.cardProduct) {
            dom.cardProduct.style.display = 'none';
        }
        if (dom.chooseSeat) {
            setActiveStep(dom.chooseSeat);
        }
        if (dom.btnContinue) {
            dom.btnContinue.innerText = 'Tiếp tục';
            dom.btnContinue.classList.replace('btn-success', 'btn-warning');
        }

        localStorage.setItem('booking_step', 'seats');
    }

    async function handlePayment() {
        const amount = totalSeatPrice + totalProductPrice;

        try {
            const response = await fetch('/api/payment/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': dom.csrfToken
                },
                body: JSON.stringify({ amount })
            });
            const result = await response.json();

            if (response.ok && result.status === 'success') {
                window.location.href = result.checkoutUrl;
                return;
            }

            alert('Lỗi từ hệ thống: ' + (result.message || 'Không xác định'));
        } catch (error) {
            console.error('Lỗi JS:', error);
            alert('Đã xảy ra lỗi khi kết nối với máy chủ.');
        }
    }

    async function showProductStep() {
        try {
            if (!productCache) {
                const response = await fetch('/api/products');
                const result = await response.json();

                if (result.status !== 'success') {
                    throw new Error('Không thể tải sản phẩm');
                }

                productCache = result.data;
                productLookup = new Map(productCache.map(product => [Number(product.id), product]));
                renderProduct(productCache);
            }

            if (dom.seatMap) {
                dom.seatMap.style.display = 'none';
            }
            if (dom.cardSeat) {
                dom.cardSeat.style.display = 'none';
            }
            if (dom.cardProduct) {
                dom.cardProduct.style.display = 'block';
            }
            if (dom.chooseProduct) {
                setActiveStep(dom.chooseProduct);
            }
            if (dom.btnContinue) {
                dom.btnContinue.innerText = 'TIẾP TỤC';
            }

            localStorage.setItem('booking_step', 'products');
        } catch (error) {
            alert('Không thể tải sản phẩm');
        }
    }

    function renderMovieInfo(showtime) {
        if (dom.movieName) dom.movieName.innerText = showtime.movie.ten_phim;
        if (dom.poster) dom.poster.src = '/storage/' + showtime.movie.poster_url;
        if (dom.time) dom.time.innerText = showtime.gio_chieu;
        if (dom.date) dom.date.innerText = showtime.ngay_chieu;
        if (dom.currentTimeDisplay) dom.currentTimeDisplay.innerText = showtime.gio_chieu;
        if (dom.cinema) dom.cinema.innerText = 'Galaxy Nguyễn Du';
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
                seatEl.dataset.price = seat.gia_ghe;

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

    function renderPromotion()
    {
        if(!dom.promotionMap || !dom.cardPromotion) return;
        dom.cardPromotion.style.display = 'block';
        dom.promotionMap.className = 'row g-3';
        
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
            price: Number.parseFloat(el.dataset.price)
        };

        selectedSeats.set(seatId, seat);
        totalSeatPrice += seat.price;
        el.classList.add('selected');
        saveSelectedSeats();
        updateTotalPrice();
        renderSelectedSeatsList();

        holdingSeatRequests.add(seatId);
        try {
            const response = await fetch('/api/hold-seat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': dom.csrfToken
                },
                body: JSON.stringify({ suat_chieu_id: showtimeId, ghe_id: seatId })
            });
            const data = await response.json();

            if (data.status !== 'holding') {
                totalSeatPrice -= seat.price;
                selectedSeats.delete(seatId);
                el.classList.remove('selected');
                saveSelectedSeats();
                updateTotalPrice();
                renderSelectedSeatsList();
                alert(data.message);
            }
        } catch (error) {
            console.error('Lỗi giữ ghế', error);
            totalSeatPrice -= seat.price;
            selectedSeats.delete(seatId);
            el.classList.remove('selected');
            saveSelectedSeats();
            updateTotalPrice();
            renderSelectedSeatsList();
            alert('Đã xảy ra lỗi khi giữ ghế. Vui lòng thử lại.');
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

        try {
            const response = await fetch(`/api/showtime-info/${showtimeId}`);
            const result = await response.json();

            if (result.status !== 'success') {
                return;
            }

            renderMovieInfo(result.data.showtime);
            renderSeats(result.data.seats);
            renderSelectedSeatsList();
            renderSelectedProductsSidebar();
            updateTotalPrice();

            if (localStorage.getItem('booking_step') === 'products') {
                await showProductStep();
            }
        } catch (error) {
            console.error('Lỗi tải thông tin suất chiếu', error);
        }
    }

    if (dom.btnContinue) {
        dom.btnContinue.addEventListener('click', async function () {
            const isSeatStepVisible = dom.seatMap && dom.seatMap.style.display !== 'none';

            if (isSeatStepVisible) {
                if (selectedSeats.size === 0) {
                    alert('Vui lòng chọn ghế trước khi tiếp tục!');
                    return;
                }

                await showProductStep();
                return;
            }

            handlePayment();
        });
    }

    if (dom.btnBack) {
        dom.btnBack.addEventListener('click', function () {
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
