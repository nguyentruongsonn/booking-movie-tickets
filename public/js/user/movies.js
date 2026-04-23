const MOVIE_TYPES = {
    NOW_SHOWING: 'dang-chieu',
    COMING_SOON: 'sap-chieu'
};

const TITLE_BY_TYPE = {
    [MOVIE_TYPES.NOW_SHOWING]: 'Phim Đang Chiếu',
    [MOVIE_TYPES.COMING_SOON]: 'Phim Sắp Chiếu'
};

const VI_DAYS = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
const DAY_PICKER_STYLE_ID = 'day-picker-style';
const SHOWTIME_BUFFER_MS = 20 * 60 * 1000;

function getCustomerToken() {
    // Token hiện nằm hoàn toàn trong HttpOnly Cookie. 
    // Trả về 'session_active' nếu có user_name để UI biết trạng thái.
    return BookingApi.TokenStore.getUserName() ? 'session_active' : null;
}

function requireCustomerLogin() {
    if (getCustomerToken()) {
        return true;
    }

    $('#authModal').modal('show');
    return false;
}

function getMovieListEndpoint(type) {
    if (type === MOVIE_TYPES.NOW_SHOWING) {
        return '/movies/now-showing';
    }

    if (type === MOVIE_TYPES.COMING_SOON) {
        return '/movies/coming-soon';
    }

    return '/movies/now-showing';
}

function getMovieStatusLabel(movie) {
    if (movie.is_showing) {
        return 'Đang chiếu';
    }
    if (movie.is_coming_soon) {
        return 'Sắp chiếu';
    }
    return 'Đã chiếu';
}

function getMovieCategories(movie) {
    if (!Array.isArray(movie.categories) || movie.categories.length === 0) {
        return 'Chưa phân loại';
    }
    return movie.categories
        .map(category => category.name || category.ten)
        .filter(Boolean)
        .join(', ');
}

// Chuyển đổi đối tượng Date thành chuỗi định dạng YYYY-MM-DD theo múi giờ địa phương
function toLocalDateKey(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}


function getYoutubeEmbedUrl(url) {
    if (!url) {
        return '';
    }

    try {
        const parsedUrl = new URL(url);
        const host = parsedUrl.hostname.replace('www.', '');

        if (host.includes('youtube.com')) {
            const videoId = parsedUrl.searchParams.get('v');
            return videoId ? `https://www.youtube.com/embed/${videoId}?autoplay=1` : '';
        }

        if (host.includes('youtu.be')) {
            const videoId = parsedUrl.pathname.split('/').filter(Boolean)[0];
            return videoId ? `https://www.youtube.com/embed/${videoId}?autoplay=1` : '';
        }
    } catch (error) {
        console.error('URL trailer không hợp lệ:', error);
    }

    return '';
}

function renderLoadingState(container) {
    let skeletons = '';
    for (let i = 0; i < 4; i++) {
        skeletons += `
        <div class="col-md-3 mb-4">
            <div class="saas-card p-0 overflow-hidden h-100 border-0">
                <div class="skeleton-box w-100" style="height: 320px; border-radius: 0;"></div>
                <div class="p-3">
                    <div class="skeleton-box w-75 mb-2" style="height: 20px;"></div>
                    <div class="skeleton-box w-50 mb-3" style="height: 14px;"></div>
                    <div class="skeleton-box w-100" style="height: 40px; border-radius: 20px;"></div>
                </div>
            </div>
        </div>`;
    }
    container.innerHTML = skeletons;
}

function renderEmptyState(container) {
    container.innerHTML = `
        <div class="col-12 text-center py-5">
            <h4>Không tìm thấy bộ phim nào!</h4>
            <p class="text-muted">Vui lòng thử lại sau hoặc chọn danh mục khác.</p>
        </div>
    `;
}

function renderErrorState(container) {
    container.innerHTML = `
        <div class="col-12 text-center py-5 text-danger">
            <i class="fa fa-exclamation-triangle fa-3x mb-3"></i>
            <p>Lỗi kết nối máy chủ! Không thể tải danh sách phim.</p>
        </div>
    `;
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

function renderMovieCard(movie) {
    const duration = movie.duration || 'Chưa rõ';
    const statusLabel = getMovieStatusLabel(movie);
    const categories = getMovieCategories(movie);
    const movieUrl = `/movie/${movie.slug}`;
    const posterUrl = getPosterUrl(movie.poster_url);

    return `
        <div class="col-md-3 mb-4 ftco-animate fadeInUp ftco-animated">
            <div class="saas-card p-0 overflow-hidden h-100 d-flex flex-column border-0">
                <a href="${movieUrl}" class="position-relative d-block w-100" style="height: 320px; background-image: url('${posterUrl}'); background-size: cover; background-position: center;">
                    <span class="position-absolute top-0 end-0 m-2 badge bg-primary text-white px-3 py-1 radius-md shadow-sm">${statusLabel}</span>
                </a>

                <div class="p-4 d-flex flex-column flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-slate-100 text-slate-700 fw-medium">${duration}</span>
                        <div class="text-primary small fw-bold"><i class="fa fa-star me-1"></i> 4.5</div>
                    </div>
                    <h5 class="fw-bold text-slate-900 mb-2 lh-base"><a href="${movieUrl}" class="text-decoration-none text-reset">${movie.title}</a></h5>
                    <p class="text-slate-500 small mb-4 flex-grow-1"><i class="fa fa-tag me-1"></i> ${categories}</p>
                    
                    <a href="${movieUrl}" class="btn btn-outline-primary w-100 radius-md py-2 fw-bold">Mua vé ngay</a>
                </div>
            </div>
        </div>
    `;
}

function updateActiveButton(activeType) {
    ['btn-dang-chieu', 'btn-sap-chieu'].forEach(id => {
        document.getElementById(id)?.classList.remove('active');
    });

    const activeButtonId = `btn-${activeType}`;
    document.getElementById(activeButtonId)?.classList.add('active');
}


async function loadMovies(type = MOVIE_TYPES.NOW_SHOWING) {
    const container = document.getElementById('movie-list-container');
    if (!container) {
        return;
    }
    updateActiveButton(type);
    renderLoadingState(container);

    try {
        const response = await BookingApi.apiFetch(getMovieListEndpoint(type));
        const result = await response.json();

        if (result.status !== 'success') {
            throw new Error(result.message || 'Lỗi không xác định');
        }

        const movies = result.data;
        if (!Array.isArray(movies) || movies.length === 0) {
            renderEmptyState(container);
            return;
        }

        container.innerHTML = movies.map(renderMovieCard).join('');
    } catch (error) {
        console.error('Lỗi khi tải dữ liệu phim:', error);
        renderErrorState(container);
    }
}

async function fetchMovieDetail() {
    if (typeof movieSlug === 'undefined' || !movieSlug) {
        console.error('Không tìm thấy ID phim từ URL.');
        return;
    }

    try {
        const response = await BookingApi.apiFetch(`/movies/${movieSlug}`);
        const result = await response.json();

        if (result.status !== 'success') {
            throw new Error(result.message || 'Lỗi không xác định');
        }

        updateMovieDetail(result.data);
    } catch (error) {
        console.error('Lỗi khi tải dữ liệu phim:', error);
        const detailName = document.getElementById('det-name-detail');
        if (detailName) {
            detailName.innerText = 'Không thể tải thông tin phim.';
        }
    }
}

function ensureDayPickerStyles() {
    if (document.getElementById(DAY_PICKER_STYLE_ID)) {
        return;
    }

    const style = document.createElement('style');
    style.id = DAY_PICKER_STYLE_ID;
    style.textContent = `
        .day-btn {
            min-width: 70px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            margin-right: 10px;
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
        .day-btn.today .day-fulldate { color: #f15d30; }
        .day-btn.today { border-color: #fb9983ff; background: #ffe9dfff; }
        .day-btn.today .day-name { color: #fd390dff; }
        .day-btn.active { border-color: #fd390dff !important; background: #f15d30; }
        .day-btn.active .day-name,
        .day-btn.active .day-date,
        .day-btn.active .day-fulldate { color: #fff !important; }
        .day-btn:disabled,
        .day-btn.disabled { opacity: .45; cursor: not-allowed; pointer-events: none; }
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

function renderShowtimesByFormat(showtimes, container) {
    if (!container) {
        return;
    }

    if (!Array.isArray(showtimes) || showtimes.length === 0) {
        container.innerHTML = `<p class="text-muted mt-3">Không có suất chiếu trong ngày này.</p>`;
        return;
    }

    const now = Date.now();
    const groupedByFormat = showtimes.reduce((accumulator, showtime) => {
        const formatName = showtime.screen?.name || showtime.format?.name || 'Mặc định';
        if (!accumulator[formatName]) {
            accumulator[formatName] = [];
        }
        accumulator[formatName].push(showtime);
        return accumulator;
    }, {});

    const html = Object.entries(groupedByFormat).map(([formatName, items]) => {
        const buttons = items
            .sort((a, b) => new Date(a.scheduled_at) - new Date(b.scheduled_at))
            .filter(showtime => new Date(showtime.scheduled_at).getTime() - now > SHOWTIME_BUFFER_MS)
            .map(showtime => {
                const time = new Date(showtime.scheduled_at).toLocaleTimeString('vi-VN', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                });
                return `<a href="/bookings/${showtime.id}" class="showtime-btn" data-showtime-id="${showtime.id}">${time}</a>`;
            })
            .join('');

        if (!buttons) {
            return '';
        }

        return `
            <div class="format-section">
                <h6>${formatName}</h6>
                <div>${buttons}</div>
            </div>
        `;
    }).join('');

    container.innerHTML = html || `<p class="text-muted mt-3">Không còn suất chiếu phù hợp trong ngày này.</p>`;
}

function setupShowtimeAuthGuard() {
    document.addEventListener('click', function (event) {
        const showtimeButton = event.target.closest('.showtime-btn');
        if (!showtimeButton) {
            return;
        }

        if (requireCustomerLogin()) {
            return;
        }

        event.preventDefault();
    });
}

function setupWeeklyDatePicker(showtimes) {
    const datePicker = document.getElementById('date-picker');
    const container = document.getElementById('showtime-container');

    if (!datePicker || !container) {
        return;
    }

    ensureDayPickerStyles();

    const groupedByDate = (Array.isArray(showtimes) ? showtimes : []).reduce((accumulator, showtime) => {
        const key = toLocalDateKey(new Date(showtime.scheduled_at));
        if (!accumulator[key]) {
            accumulator[key] = [];
        }
        accumulator[key].push(showtime);
        return accumulator;
    }, {});

    const today = new Date();
    const todayKey = toLocalDateKey(today);
    const nextSevenDays = Array.from({ length: 7 }, (_, index) => {
        const date = new Date(today);
        date.setDate(today.getDate() + index);
        return date;
    });

    let firstAvailableKey = null;
    datePicker.innerHTML = '';

    nextSevenDays.forEach(date => {
        const key = toLocalDateKey(date);
        const hasShowtime = Array.isArray(groupedByDate[key]) && groupedByDate[key].length > 0;
        const button = document.createElement('button');

        button.type = 'button';
        button.className = `day-btn me-2${key === todayKey ? ' today' : ''}${hasShowtime ? '' : ' disabled'}`;
        button.disabled = !hasShowtime;
        button.innerHTML = `
            <span class="day-name">${VI_DAYS[date.getDay()]}</span>
            <span class="day-date">${date.getDate()}</span>
            <span class="day-fulldate">tháng ${date.getMonth() + 1}</span>
        `;

        if (hasShowtime) {
            if (!firstAvailableKey) {
                firstAvailableKey = key;
            }

            button.addEventListener('click', () => {
                datePicker.querySelectorAll('.day-btn').forEach(item => item.classList.remove('active'));
                button.classList.add('active');
                renderShowtimesByFormat(groupedByDate[key], container);
            });
        }

        datePicker.appendChild(button);
    });

    if (!firstAvailableKey) {
        container.innerHTML = `<p class="text-muted mt-3">Phim chưa có lịch chiếu trong tuần này.</p>`;
        return;
    }

    const firstButton = datePicker.querySelector('.day-btn:not(.disabled)');
    firstButton?.classList.add('active');
    renderShowtimesByFormat(groupedByDate[firstAvailableKey], container);
}

function setupTrailerModal(movie) {
    const button = document.getElementById('btn-watch-trailer');
    const videoFrame = document.getElementById('video-frame');

    if (!button || !videoFrame) {
        return;
    }

    button.onclick = () => {
        const embedUrl = getYoutubeEmbedUrl(movie.trailer_url);
        if (!embedUrl) {
            alert('Phim này hiện chưa cập nhật trailer chính thức.');
            return;
        }

        videoFrame.src = embedUrl;
        $('#trailerModal').modal('show');
    };

    $('#trailerModal').off('hidden.bs.modal').on('hidden.bs.modal', () => {
        videoFrame.src = '';
    });
}

function updateMovieDetail(movie) {
    document.getElementById('det-name-detail').innerText = movie.title;
    document.getElementById('det-director').innerText = movie.director || 'Đang cập nhật';
    document.getElementById('det-actors').innerText = movie.cast || 'Đang cập nhật';
    document.getElementById('det-desc').innerHTML = movie.description || 'Thông tin mô tả đang được cập nhật...';
    document.getElementById('det-duration').innerText = movie.duration || 'Đang cập nhật';
    document.getElementById('det-start-date').innerText = movie.release_date
        ? new Date(movie.release_date).toLocaleDateString('vi-VN')
        : 'Đang cập nhật';

    const categoriesElement = document.getElementById('det-categories');
    if (categoriesElement) {
        categoriesElement.innerText = getMovieCategories(movie);
    }

    const imageUrl = getPosterUrl(movie.poster_url);
    const posterImage = document.getElementById('det-poster');
    const bannerSection = document.getElementById('movie-banner');

    if (posterImage) {
        posterImage.src = imageUrl;
    }
    if (bannerSection) {
        bannerSection.style.backgroundImage = `url('${imageUrl}')`;
    }

    setupWeeklyDatePicker(movie.showtimes || []);
    setupTrailerModal(movie);
}


document.addEventListener('DOMContentLoaded', () => {
    setupShowtimeAuthGuard();

    if (document.getElementById('movie-list-container')) {
        loadMovies(MOVIE_TYPES.NOW_SHOWING);
    }

    if (document.getElementById('det-name-detail') && typeof movieSlug !== 'undefined') {
        fetchMovieDetail();
    }
});

window.loadMovies = loadMovies;


