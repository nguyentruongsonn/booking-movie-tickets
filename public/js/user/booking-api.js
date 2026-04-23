/**
 * booking-api.js
 * Lớp API client: xử lý auth token, refresh token, và fetch.
 */

(function (global) {
    'use strict';

    // ─── Token Store (memory-first, sessionStorage fallback) ─────────────────
    // Không dùng localStorage để tránh XSS đánh cắp token.
    let _memoryToken = null;

    const TokenStore = {
        // Token giờ nằm trong HttpOnly Cookie, JS không thể đọc trực tiếp.
        // Chỉ lưu user_name để hiển thị UI.
        getUserName() {
            return localStorage.getItem('user_name');
        },
        setUserName(name) {
            localStorage.setItem('user_name', name);
        },
        clear() {
            localStorage.removeItem('user_name');
        },
        // Migration dọn dẹp cũ
        migrate() {
            sessionStorage.removeItem('auth_token');
            localStorage.removeItem('auth_token');
        },
    };

    TokenStore.migrate();

    // ─── CSRF Helper ──────────────────────────────────────────────────────────
    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    // ─── apiFetch ─────────────────────────────────────────────────────────────
    /**
     * Wrapper quanh fetch:
     * 1. Luôn gửi credentials: 'include' để truyền HttpOnly Cookies.
     * 2. Tự động đính kèm X-CSRF-TOKEN cho các request thay đổi dữ liệu.
     * 3. Refresh token tự động khi gặp 401.
     */
    async function apiFetch(url, options = {}) {
        let finalUrl = url;
        if (!url.startsWith('http')) {
            const cleanUrl = url.startsWith('/') ? url.substring(1) : url;
            if (!cleanUrl.startsWith('api/v1')) {
                finalUrl = `/api/v1/${cleanUrl.startsWith('api/') ? cleanUrl.substring(4) : cleanUrl}`;
            } else {
                finalUrl = `/${cleanUrl}`;
            }
        }

        const headers = { 
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest', // Chỉ dấu cho server biết đây là AJAX
            ...options.headers 
        };

        // Đính kèm CSRF cho các phương thức không an toàn
        const method = (options.method || 'GET').toUpperCase();
        if (!['GET', 'HEAD', 'OPTIONS'].includes(method)) {
            const csrf = getCsrfToken();
            if (csrf) headers['X-CSRF-TOKEN'] = csrf;
        }

        const fetchOptions = { 
            ...options, 
            headers, 
            credentials: 'include' // Quan trọng: Để trình duyệt gửi Cookie
        };

        let res = await fetch(finalUrl, fetchOptions);

        // 401 Unauthorized → Thử refresh token
        if (res.status === 401 && !finalUrl.includes('/auth/refresh-token')) {
            const refreshed = await apiFetch('/auth/refresh-token', { method: 'POST' });
            if (refreshed.ok) {
                // Retry request ban đầu
                return fetch(finalUrl, fetchOptions);
            } else {
                // Refresh thất bại → Cần đăng nhập lại
                TokenStore.clear();
                // Tùy chọn: chuyển hướng hoặc thông báo
                return res;
            }
        }

        return res;
    }

    // ─── Expose ───────────────────────────────────────────────────────────────
    global.BookingApi = { apiFetch, TokenStore };

}(window));
