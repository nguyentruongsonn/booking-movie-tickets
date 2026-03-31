document.addEventListener('DOMContentLoaded', function () {

    const pathSegments = window.location.pathname.split('/');
    let showtimeId = pathSegments.filter(Boolean).pop();
    
    if (!showtimeId || isNaN(parseInt(showtimeId))) {
        console.error("ID suất chiếu không hợp lệ");
        return;
    }

    let selectedSeats = [];
    let selectedProducts = [];

    const btnContinue = document.getElementById('btn-continue');

    if (btnContinue) {
        btnContinue.addEventListener('click', async function() {
            const seatMap = document.getElementById('seat-map');
            const cardSeat = document.getElementById('card-seat');
            const screenLabel = document.querySelector('.screen-container');
            
            if (seatMap.style.display !== 'none') {
                if (selectedSeats.length === 0) {
                    alert('Vui lòng chọn ghế trước khi tiếp tục!');
                    return;
                }

                try {
                    const response = await fetch('/api/products');
                    const result = await response.json();
                    
                    if (result.status === 'success') {
                        seatMap.style.display = 'none';
                        cardSeat.style.display = 'none';
                        if (screenLabel) screenLabel.style.display = 'none';
                        
                        renderProduct(result.data); 

                        this.innerText = "THANH TOÁN";
                        this.classList.replace('btn-warning', 'btn-success');
                        
                        const steps = document.querySelectorAll('.step-item');
                        if(steps.length > 2) {
                            steps[1].classList.remove('active');
                            steps[2].classList.add('active');
                        }
                    }
                } catch (error) {
                    alert('Không thể tải danh sách sản phẩm!');
                }
            } else {
                window.location.href = `/booking/${showtimeId}/checkout`;
            }
        });
    }

    async function init() {
        try {
            const response = await fetch(`/api/showtime-info/${showtimeId}`);
            const result = await response.json();

            if (result.status === 'success') {
                renderMovieInfo(result.data.showtime);
                renderSeats(result.data.seats);
            }
        } catch (error) {
            console.error("Lỗi khởi tạo:", error);
        }
    }

    function renderMovieInfo(showtime) {
        document.getElementById('book-movie-name').innerText = showtime.movie.ten_phim;
        document.getElementById('book-poster').src = '/storage/' + showtime.movie.poster_url;
        document.getElementById('book-time').innerText = showtime.gio_chieu;
        document.getElementById('book-date').innerText = showtime.ngay_chieu;
        document.getElementById('current-time-display').innerText = showtime.gio_chieu;
        document.getElementById('book-cinema').innerText = 'Galaxy Nguyễn Du';
        document.getElementById('book-room').innerText = showtime.room.ten_phong;
    }

    // 3. Render sơ đồ ghế
    function renderSeats(seatsByRow) {
        const seatMap = document.getElementById('seat-map');
        seatMap.innerHTML = '';
        const rows = Object.keys(seatsByRow).sort().reverse();

        rows.forEach(row => {
            const rowDiv = document.createElement('div');
            rowDiv.className = 'd-flex justify-content-center align-items-center w-100 gap-2 mb-2';
            
            const leftLabel = `<div class="row-label fw-bold small text-muted">${row}</div>`;
            rowDiv.innerHTML = leftLabel;

            const seatsInRow = [...seatsByRow[row]].sort((a, b) => Number(a.so_ghe) - Number(b.so_ghe));

            seatsInRow.forEach(seat => {
                const seatEl = document.createElement('div');
                const isBooked = seat.is_booked;

                seatEl.className = `seat ${isBooked ? 'booked' : 'available'}`;
                seatEl.innerText = seat.so_ghe;
                seatEl.dataset.id = seat.id;
                seatEl.dataset.name = `${row}${seat.so_ghe}`; 
                seatEl.dataset.price = seat.gia_ghe;

                if (!isBooked) {
                    seatEl.addEventListener('click', () => handleSeatSelection(seatEl));
                }
                rowDiv.appendChild(seatEl);
            });

            const rightLabel = document.createElement('div');
            rightLabel.className = 'row-label fw-bold small text-muted';
            rightLabel.innerText = row;
            rowDiv.appendChild(rightLabel);

            seatMap.appendChild(rowDiv);
        });
    }

   
    async function handleSeatSelection(el) {
        const seatId = el.dataset.id;
        const seatName = el.dataset.name;
        const price = parseFloat(el.dataset.price);

        if (el.classList.contains('selected')) {
            el.classList.remove('selected');
            selectedSeats = selectedSeats.filter(s => s.id !== seatId);
        } else {
            el.classList.add('selected');
            selectedSeats.push({ id: seatId, name: seatName, price: price });
            
            // Gọi API giữ ghế (Hold seat)
            try {
                const response = await fetch('/api/hold-seat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ suat_chieu_id: showtimeId, ghe_id: seatId })
                });
                const data = await response.json();
                if (data.status !== 'holding') {
                    alert(data.message);
                    el.classList.remove('selected');
                    selectedSeats = selectedSeats.filter(s => s.id !== seatId);
                }
            } catch (error) {
                console.error("Lỗi giữ ghế");
            }
        }
        updateTotalPrice();
        renderSelectedSeatsList();
    }


    function renderSelectedSeatsList() {
        const container = document.getElementById('selected-seats-list');
        if (!container) return;
        if (selectedSeats.length === 0) {
            container.innerHTML = 'Chưa chọn ghế';
            return;
        }
        const names = selectedSeats.map(s => s.name).join(', ');
        container.innerHTML = `Ghế: <span class="text-dark">${names}</span>`;
    }

    
    function renderProduct(products) {
        const productMap = document.getElementById('product-map');
        const cardProduct = document.getElementById('card-product');
        const listInlineItems = document.querySelectorAll('.list-inline-item');
        listInlineItems.forEach(item => item.classList.remove('active'));
        document.getElementById('choose-product').classList.add('active');
        cardProduct.style.display = 'block';
        productMap.className = 'row g-3'; 
        productMap.innerHTML = products.map(p => `
            <div class="col-md-12 mb-3">
                <div class="product-card d-flex align-items-center p-3 border rounded-4 bg-white shadow-sm">
                    <div class="product-img me-3 mr-3">
                        <img src="/storage/${p.hinh_anh_url}" 
                            alt="${p.ten_san_pham}" 
                            class="rounded-3 shadow-sm" 
                            style="width: 80px; height: 80px; object-fit: cover;">
                    </div>

                    <div class="flex-grow-1">
                        <h6 class="mb-1 fw-bold text-dark">${p.ten_san_pham}</h6>
                        <p class="text-primary fw-bold mb-0">${Number(p.gia_ban).toLocaleString('vi-VN')} đ</p>
                    </div>

                    <div class="quantity-controls d-flex align-items-center gap-3 bg-light rounded-pill p-1 px-2">
                        <button class="btn btn-sm btn-white rounded-circle shadow-sm p-0" 
                                style="width: 28px; height: 28px; line-height: 28px;"
                                onclick="changeQty(${p.id}, '${p.ten_san_pham}', ${p.gia_ban}, -1)">-</button>
                        
                        <span id="qty-${p.id}" class="fw-bold" style="min-width:20px; text-align:center;">0</span>
                        
                        <button class="btn btn-sm btn-warning text-white rounded-circle shadow-sm p-0" 
                                style="width: 28px; height: 28px; line-height: 28px;"
                                onclick="changeQty(${p.id}, '${p.ten_san_pham}', ${p.gia_ban}, 1)">+</button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    window.changeQty = function(id, name, price, delta) {
        let item = selectedProducts.find(p => p.id === id);
        
        if (!item) {
            if (delta > 0) {
                selectedProducts.push({ id, name, price, qty: 1 });
            }
        } else {
            item.qty += delta;
            if (item.qty <= 0) {
                selectedProducts = selectedProducts.filter(p => p.id !== id);
            }
        }

        const qtySpan = document.getElementById(`qty-${id}`);
        if (qtySpan) {
            const currentItem = selectedProducts.find(p => p.id === id);
            qtySpan.innerText = currentItem ? currentItem.qty : 0;
        }

        updateTotalPrice();
        renderSelectedProductsSidebar();
    };

    function renderSelectedProductsSidebar() {
        const container = document.getElementById('selected-products-list');
        if (!container) return;
        
        container.innerHTML = selectedProducts.map(p => `
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span>${p.qty}x ${p.name}</span>
                <span class="fw-bold">${(p.price * p.qty).toLocaleString('vi-VN')} đ</span>
            </div>
        `).join('');
    }

    function updateTotalPrice() {
        let totalSeat = selectedSeats.reduce((sum, s) => sum + s.price, 0);
        let totalProduct = selectedProducts.reduce((sum, p) => sum + (p.price * p.qty), 0);
        let total = totalSeat + totalProduct;
        document.getElementById('total-price').innerText = total.toLocaleString('vi-VN') + ' đ';
    }

    init();
});