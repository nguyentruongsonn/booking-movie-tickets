async function loadMovies(type = 'dang-chieu') {
    const container = document.getElementById('movie-list-container');
    const pageTitle = document.getElementById('page-title');


    updateActiveButton(type);
    updatePageTitle(type, pageTitle);
    container.innerHTML = `
        <div class="col-12 text-center py-5">
            <i class="fa fa-spinner fa-spin fa-3x text-primary mb-3"></i>
            <p class="text-muted">Đang tải danh sách phim...</p>
        </div>
    `;

    try {
        let url = `/api/movie/${type}`;
        if (type === 'all') url = '/api/movie/all';
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        });
        if (!response.ok) throw new Error("Lỗi kết nối API");
        const movies = await response.json();

        if (!movies || movies.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center py-5">
                    <h4>Không tìm thấy bộ phim nào!</h4>
                    <p class="text-muted">Vui lòng thử lại sau hoặc chọn danh mục khác.</p>
                </div>
            `;
            return;
        }
        let html = '';
        movies.forEach(movie => {
            let statusLabel = movie.dang_chieu ? 'Đang chiếu' : (movie.sap_chieu ? 'Sắp chiếu' : 'Đã chiếu');
            let categoryNames = 'Chưa phân loại';
            if (movie.categories && movie.categories.length > 0) {
                categoryNames = movie.categories.map(c => c.ten_the_loai).join(', ');
            }
            html += `
                <div class="col-md-3 mb-3 ftco-animate fadeInUp ftco-animated">
                    <div class="project-wrap">
                        <a href="/movie/${movie.slug}" class="img" style="background-image: url('/storage/${movie.poster_url}');">
                            <span class="price">${statusLabel}</span>
                        </a>
                        
                        <div class="text p-4">
                            <span class="days">${movie.thoi_luong || 'Chưa rõ'} phút</span>
                            <h3><a href="/movie/${movie.slug}">${movie.ten_phim}</a></h3>
                            <p class="location"><span class="fa fa-tag"></span> ${categoryNames}</p>
                            <ul>
                                <li><span class="fa fa-star text-warning"></span> 4.5</li>
                                <li><span class="fa fa-user"></span> T13</li>
                                <li><a href="/movie/${movie.slug}" class="btn btn-sm btn-primary py-1 px-3 text-white" style="border-radius:20px;">Mua vé</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;

    } catch (error) {
        console.error('Lỗi khi tải dữ liệu phim:', error);
        container.innerHTML = `
            <div class="col-12 text-center py-5 text-danger">
                <i class="fa fa-exclamation-triangle fa-3x mb-3"></i>
                <p>Lỗi kết nối máy chủ! Không thể tải danh sách phim.</p>
            </div>
        `;
    }
}


function getYoutubeEmbedUrl(url) {
    if (!url) return '';
    let videoId = '';


    if (url.includes('v=')) {
        videoId = url.split('v=')[1].split('&')[0];
    } else if (url.includes('youtu.be/')) {
        videoId = url.split('youtu.be/')[1];
    }

    return videoId ? `https://www.youtube.com/embed/${videoId}?autoplay=1` : '';
}


async function fetchMovieDetail() {
    if (typeof movieSlug === 'undefined' || !movieSlug) {
        console.error("Không tìm thấy ID phim từ URL.");
        return;
    }

    try {
        const response = await fetch(`/api/movie/${movieSlug}`);
        if (!response.ok) throw new Error("Lỗi kết nối API");

        const movie = await response.json();
        document.getElementById('det-name-detail').innerText = movie.ten_phim;
        document.getElementById('det-director').innerText = movie.dao_dien || 'Đang cập nhật';
        document.getElementById('det-actors').innerText = movie.dien_vien || 'Đang cập nhật';
        document.getElementById('det-desc').innerHTML = movie.mo_ta || 'Thông tin mô tả đang được cập nhật...';
        document.getElementById('det-duration').innerText = movie.thoi_luong || 'Đang cập nhât';
        document.getElementById('det-start-date').innerText = new Date(movie.ngay_khoi_chieu).toLocaleDateString('vi-VN') || 'Đang cập nhât';
        const cateElem = document.getElementById('det-categories');
        if (cateElem) {
            if (movie.categories && movie.categories.length > 0) {
                cateElem.innerText = movie.categories.map(c => c.ten).join(', ');
            } else {
                cateElem.innerText = 'Chưa phân loại';
            }
        }
        setupWeeklyDatePicker(movie.showtimes || []);
        const posterImg = document.getElementById('det-poster');
        const bannerSection = document.getElementById('movie-banner');
        console.log("URL poster phim:",movie);
        const imageUrl = movie.poster_url ? `/storage/${movie.poster_url}` : '/images/movie_bg.jpg';
        posterImg.src = imageUrl;
        bannerSection.style.backgroundImage = `url('${imageUrl}')`;

        // 3. Xử lý sự kiện nhấn nút Play Trailer
        const btnPlay = document.getElementById('btn-watch-trailer');
        const videoFrame = document.getElementById('video-frame');

        btnPlay.onclick = function () {
            const embedUrl = getYoutubeEmbedUrl(movie.trailer_url);

            if (embedUrl) {
                videoFrame.src = embedUrl;
                // Sử dụng jQuery để mở Modal (Bootstrap mặc định dùng jQuery)
                $('#trailerModal').modal('show');
            } else {
                alert("Phim này hiện chưa cập nhật Trailer chính thức.");
            }
        };

        // 4. Xử lý khi đóng Modal: Phải xóa src để dừng video (tránh tiếng vẫn phát)
        $('#trailerModal').on('hidden.bs.modal', function () {
            videoFrame.src = "";
        });

    } catch (error) {
        console.error("Lỗi khi tải dữ liệu phim:", error);
        document.getElementById('det-name-detail').innerText = "Không thể tải thông tin phim.";
    }
}

//

document.addEventListener('DOMContentLoaded', () => {
    const listContainer = document.getElementById('movie-list-container');
    const detailCheck = document.getElementById('det-name-detail');
    if (listContainer) {
        loadMovies('dang-chieu');
    }
    if (detailCheck && typeof movieSlug !== 'undefined') {
        fetchMovieDetail();
    }
});


function updateActiveButton(activeType) {
    const btnIds = ['btn-dang-chieu', 'btn-sap-chieu', 'btn-all'];

    btnIds.forEach(id => {
        const btn = document.getElementById(id);
        if (btn) btn.classList.remove('active');
    });

    const activeBtnId = `btn-${activeType === 'all' ? 'all' : activeType}`;
    const activeBtn = document.getElementById(activeBtnId);
    if (activeBtn) activeBtn.classList.add('active');
}

function updatePageTitle(type, titleElement) {
    if (!titleElement) return;

    if (type === 'dang-chieu') titleElement.innerText = 'Phim Đang Chiếu';
    else if (type === 'sap-chieu') titleElement.innerText = 'Phim Sắp Chiếu';
    else titleElement.innerText = 'Tất Cả Phim';
}


const VI_DAYS = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
function toLocalDateKey(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}


function setupWeeklyDatePicker(showtimes) {
    const datePicker = document.getElementById('date-picker');
    const container = document.getElementById('showtime-container');
    const byDate = {};
    showtimes.forEach(s => {
        const key = toLocalDateKey(new Date(s.ngay_gio_chieu));
        if (!byDate[key]) byDate[key] = [];
        byDate[key].push(s);
    });


    const today = new Date();



    const weekDays = [];
    for (let i = 0; i < 7; i++) {
        const d = new Date(today);
        d.setDate(today.getDate() + i);
        weekDays.push(d);
    }

    if (!document.getElementById('day-picker-style')) {
        const style = document.createElement('style');
        style.id = 'day-picker-style';
        style.textContent = `
            .day-btn {
                min-width: 70px;
                flex-shrink: 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 3px;
                margin-right:10px;
                padding: 8px 12px;
                border: 2px solid #dee2e6;
                border-radius: 12px;
                background: #fff;
                cursor: pointer;
                transition: all .2s;
                line-height: 1.2;
            }
            .day-btn .day-name { font-size: .75rem; font-weight: 700; text-transform: uppercase; color: #6c757d; }
            .day-btn .day-date { font-size: 1.1rem; font-weight: 800; color: #212529; }
            .day-btn .day-fulldate { font-size: .7rem; color: #adb5bd; }
            .day-btn.today .day-fulldate{color: #f15d30}
            .day-btn.today { border-color: #fb9983ff; background: #ffe9dfff; }
            .day-btn.today .day-name { color: #fd390dff; }
            .day-btn.active { border-color: #fd390dff !important; background: #f15d30; }
            .day-btn.active .day-name,
            .day-btn.active .day-date,
            .day-btn.active .day-fulldate { color: #fff !important; }
            .day-btn:disabled,
            .day-btn.disabled { opacity: .45; cursor: not-allowed; pointer-events: none; }
            /* Format section */
            .format-section { margin-bottom: 24px; }
            .format-section h6 {
                display: inline-block;
                padding: 4px 14px;
                border-radius: 20px;
                background: #f15d30;
                color: #fff;
                font-size: .8rem;
                font-weight: 700;
                margin-bottom: 10px;
                letter-spacing: .5px;
            }
            .showtime-btn {
                display: inline-block;
                margin: 4px;
                padding: 6px 20px;
                border-radius: 30px;
                border: 2px solid #f15d30;
                color: #f15d30;
                font-weight: 600;
                font-size: .9rem;
                text-decoration: none;
                transition: all .2s;
                background: #fff;
            }
            .showtime-btn:hover {
                background: #f15d30;
                color: #fff;
                text-decoration: none;
            }
        `;
        document.head.appendChild(style);
    }

    datePicker.innerHTML = '';
    const todayKey = toLocalDateKey(today);

    let firstAvailableKey = null;

    weekDays.forEach(d => {
        const key = toLocalDateKey(d);
        const hasST = !!byDate[key];
        const isPast = d < today;
        const btn = document.createElement('button');
        btn.className = 'day-btn me-2' +
            (key === todayKey ? ' today' : '') +
            (!hasST ? ' disabled' : '');
        btn.disabled = !hasST;

        if (isPast) btn.style.display = 'none';
        const viDay = VI_DAYS[d.getDay()];
        btn.innerHTML = `
            <span class="day-name">${viDay}</span>
            <span class="day-date">${d.getDate()}</span>
            <span class="day-fulldate">tháng ${d.getMonth() + 1}</span>
        `;

        if (hasST) {
            if (isPast) return;
            if (!firstAvailableKey) firstAvailableKey = key;
            btn.addEventListener('click', () => {
                datePicker.querySelectorAll('.day-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                renderShowtimesByFormat(byDate[key], container);
            });
        }

        datePicker.appendChild(btn);
    });


    if (firstAvailableKey) {
        const firstBtn = datePicker.querySelector('.day-btn:not(.disabled)');
        if (firstBtn) firstBtn.classList.add('active');
        renderShowtimesByFormat(byDate[firstAvailableKey], container);
    } else {
        container.innerHTML = `<p class="text-muted mt-3">Phim chưa có lịch chiếu trong tuần này.</p>`;
    }
}


function renderShowtimesByFormat(showtimes, container) {
    if (!showtimes || showtimes.length === 0) {
        container.innerHTML = `<p class="text-muted mt-3">Không có suất chiếu trong ngày này.</p>`;
        return;
    }


    const byFormat = {};
    showtimes.forEach(s => {
        const formatName = (s.format && s.format.ten) ? s.format.ten : 'Mặc định';
        if (!byFormat[formatName]) byFormat[formatName] = [];
        byFormat[formatName].push(s);
    });


    Object.values(byFormat).forEach(arr => arr.sort((a, b) =>
        new Date(a.ngay_gio_chieu) - new Date(b.ngay_gio_chieu)
    ));

    const now = new Date();
    const twentyMinutes = 20 * 60 * 1000;
    let html = '';
    Object.entries(byFormat).forEach(([formatName, items]) => {
        const buttons = items.filter(s => new Date(s.ngay_gio_chieu) - now > twentyMinutes).map(s => {
            const time = new Date(s.ngay_gio_chieu).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: false });
            return `<a href="/bookings/${s.id}" class="showtime-btn">${time}</a>`;
        }).join('');

        if (buttons)
            html += `
            <div class="format-section">
                <h6>${formatName}</h6>
                <div>${buttons}</div>
            </div>
        `;
    });

    container.innerHTML = html;
}
