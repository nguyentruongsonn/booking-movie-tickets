/**
 * auth.js — Xác thực người dùng (Đăng nhập, Đăng ký, Đăng xuất, Google)
 *
 * Phụ thuộc (load trước file này):
 *   - booking-api.js → window.BookingApi { apiFetch, TokenStore }
 *
 * Chức năng: Đăng nhập → Đăng ký → Đăng xuất → Đồng bộ phiên
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // ─── Shorthand ───────────────────────────────────────────────────────────
    const { apiFetch, TokenStore } = window.BookingApi;

    // ─── DOM Refs ────────────────────────────────────────────────────────────
    const dom = {
        // Alert
        authAlert:         document.getElementById('auth-alert'),

        // Login
        loginEmail:        document.getElementById('login-email'),
        loginPassword:     document.getElementById('login-password'),
        btnLogin:          document.getElementById('btn-login'),

        // Register
        registerEmail:     document.getElementById('register-email'),
        registerPassword:  document.getElementById('register-password'),
        registerConfirm:   document.getElementById('register-confirm-password'),
        registerName:      document.getElementById('register-name'),
        registerPhone:     document.getElementById('register-phone'),
        registerGender:    document.getElementById('register-gender'),
        registerBirthday:  document.getElementById('register-birthday'),
        btnRegister:       document.getElementById('btn-register'),
        formRegister:      document.getElementById('form-register'),

        // Logout
        btnLogout:         document.getElementById('btn-logout'),

        // Navigation
        navGuestItem:      document.getElementById('nav-guest-item'),
        navUserItem:       document.getElementById('nav-user-item'),
        navUserName:       document.getElementById('nav-user-name'),

        // Modal
        authModal:         document.getElementById('authModal'),

        // Tabs
        tabLogin:          document.getElementById('tab-login'),
        tabRegister:       document.getElementById('tab-register'),
        paneLogin:         document.getElementById('pane-login'),
        paneRegister:      document.getElementById('pane-register'),

        // CSRF
        csrfToken:         document.querySelector('meta[name="csrf-token"]')?.content || '',
    };

    // ─── State ───────────────────────────────────────────────────────────────
    const AUTH_SESSION_KEY = 'user_name';
    const SESSION_PING_INTERVAL_MS = 900_000; // 15 phút

    // ─── Utilities ───────────────────────────────────────────────────────────

    function isLoggedIn() {
        return Boolean(localStorage.getItem(AUTH_SESSION_KEY));
    }

    function showAlert(message, type = 'error') {
        const el = dom.authAlert;
        if (!el) return;

        el.textContent = message;
        el.className = `auth-alert ${type}`;
        el.classList.remove('d-none');

        setTimeout(() => el.classList.add('d-none'), 5000);
    }

    async function parseJson(response) {
        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) return null;

        try {
            return await response.json();
        } catch (err) {
            console.error('[Auth] Không thể parse JSON:', err);
            return null;
        }
    }

    function setButtonLoading(btn, isLoading, loadingText, defaultText) {
        if (!btn) return;
        btn.disabled = isLoading;
        btn.textContent = isLoading ? loadingText : defaultText;
    }

    // ─── Auth State ──────────────────────────────────────────────────────────

    function updateNavUI(loggedIn, userName = '') {
        const { navGuestItem, navUserItem, navUserName } = dom;

        if (navGuestItem && navUserItem) {
            navGuestItem.style.setProperty('display', loggedIn ? 'none'  : 'block', 'important');
            navUserItem.style.setProperty( 'display', loggedIn ? 'block' : 'none',  'important');
        }

        if (navUserName) {
            navUserName.textContent = loggedIn
                ? (userName || TokenStore.getUserName() || '')
                : '';
        }
    }

    function setAuthSession(userName = '') {
        if (userName) TokenStore.setUserName(userName);
        updateNavUI(true, userName);
    }

    function clearAuthSession() {
        TokenStore.clear();
        updateNavUI(false);
    }

    // ─── Tab Switcher ────────────────────────────────────────────────────────

    function switchTab(tab) {
        const isLogin = tab === 'login';
        const { tabLogin, tabRegister, paneLogin, paneRegister } = dom;

        tabLogin?.classList.toggle('active', isLogin);
        tabRegister?.classList.toggle('active', !isLogin);
        paneLogin?.classList.toggle('d-none', !isLogin);
        paneRegister?.classList.toggle('d-none', isLogin);
    }

    // ─── Handlers ────────────────────────────────────────────────────────────

    async function handleRegister(event) {
        event.preventDefault();

        const formData = {
            email:                 dom.registerEmail?.value   || '',
            password:              dom.registerPassword?.value || '',
            password_confirmation: dom.registerConfirm?.value  || '',
            full_name:             dom.registerName?.value     || '',
            phone:                 dom.registerPhone?.value    || '',
            gender:                dom.registerGender?.value   || '',
            birthday:              dom.registerBirthday?.value || '',
        };

        setButtonLoading(dom.btnRegister, true, 'Đang đăng ký...', 'Tạo tài khoản');

        try {
            const res = await apiFetch('/auth/register', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(formData),
            });

            const result = await parseJson(res);

            if (res.ok && result?.status === 'success') {
                if (dom.loginEmail) dom.loginEmail.value = formData.email;
                dom.formRegister?.reset();
                switchTab('login');
                showAlert('Đăng ký thành công! Vui lòng đăng nhập.', 'success');
                return;
            }

            if (res.status === 422 && result?.errors) {
                const errorMessages = Object.values(result.errors).flat();
                showAlert('Lỗi: ' + errorMessages.join(', '));
                return;
            }

            showAlert(result?.message || 'Đăng ký thất bại.');
        } catch (err) {
            console.error('[Auth] Lỗi đăng ký:', err);
            showAlert('Có lỗi kết nối máy chủ.');
        } finally {
            setButtonLoading(dom.btnRegister, false, '', 'Tạo tài khoản');
        }
    }

    async function handleLogin(event) {
        event.preventDefault();

        const formData = {
            email:       dom.loginEmail?.value    || '',
            password:    dom.loginPassword?.value || '',
            device_name: navigator.userAgent,
        };

        setButtonLoading(dom.btnLogin, true, 'Đang đăng nhập...', 'Đăng nhập');

        try {
            const res = await apiFetch('/auth/login', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(formData),
            });

            const result = await parseJson(res);

            if (res.ok && result?.status === 'success') {
                const userName = result.data?.user?.full_name || '';
                setAuthSession(userName);

                if (dom.authModal) {
                    const modal = bootstrap.Modal.getInstance(dom.authModal);
                    modal?.hide();
                }

                showAlert('Đăng nhập thành công!', 'success');
                return;
            }

            showAlert(result?.message || 'Đăng nhập thất bại.');
        } catch (err) {
            console.error('[Auth] Lỗi đăng nhập:', err);
            showAlert('Có lỗi xảy ra, vui lòng thử lại sau.');
        } finally {
            setButtonLoading(dom.btnLogin, false, '', 'Đăng nhập');
        }
    }

    async function handleLogout(event) {
        event.preventDefault();

        if (!confirm('Bạn có chắc chắn muốn đăng xuất?')) return;

        setButtonLoading(dom.btnLogout, true, 'Đang đăng xuất...', 'Đăng xuất');

        try {
            const res = await apiFetch('/auth/logout', { method: 'POST' });

            // 401 vẫn tính là logout thành công (session đã hết)
            if (!res.ok && res.status !== 401) {
                const result = await parseJson(res);
                showAlert(result?.message || 'Đăng xuất thất bại, vui lòng thử lại.');
                return;
            }

            clearAuthSession();
            window.location.replace('/');
        } catch (err) {
            console.error('[Auth] Lỗi đăng xuất:', err);
            showAlert('Không thể đăng xuất lúc này, vui lòng thử lại.');
        } finally {
            setButtonLoading(dom.btnLogout, false, '', 'Đăng xuất');
        }
    }

    function handleGoogleSignIn(element) {
        const url = element?.dataset?.url;
        if (!url) return;

        const windowFeatures = 'toolbar=no,menubar=no,width=600,height=700,top=100,left=100';
        window.open(url, 'GoogleLogin', windowFeatures);

        window.addEventListener('message', function onMessage(event) {
            if (event.origin !== window.location.origin) return;

            const result = event.data;
            if (result?.status === 'success' && result?.token) {
                setAuthSession(result.user?.full_name || '');

                if (dom.authModal) {
                    const modal = bootstrap.Modal.getInstance(dom.authModal);
                    modal?.hide();
                }

                showAlert('Đăng nhập Google thành công!', 'success');
            }

            window.removeEventListener('message', onMessage);
        });
    }

    // ─── Session Sync ────────────────────────────────────────────────────────

    async function syncAuthState() {
        if (!isLoggedIn()) {
            updateNavUI(false);
            return;
        }

        try {
            const res    = await apiFetch('/auth/me');
            const result = await parseJson(res);

            if (result?.status === 'success') {
                setAuthSession(result.data?.full_name || '');
            } else {
                clearAuthSession();
            }
        } catch (err) {
            console.error('[Auth] Đồng bộ phiên đăng nhập thất bại:', err);
            clearAuthSession();
        }
    }

    async function pingSession() {
        if (!isLoggedIn()) return;

        try {
            await apiFetch('/auth/me');
        } catch (err) {
            console.error('[Auth] Kiểm tra phiên thất bại:', err);
        }
    }

    // ─── Events ──────────────────────────────────────────────────────────────

    function setupEvents() {
        // Form đăng nhập
        dom.btnLogin?.closest('form')?.addEventListener('submit', handleLogin);

        // Form đăng ký
        dom.formRegister?.addEventListener('submit', handleRegister);

        // Nút đăng xuất
        dom.btnLogout?.addEventListener('click', handleLogout);

        // Tab switcher
        dom.tabLogin?.addEventListener('click', () => switchTab('login'));
        dom.tabRegister?.addEventListener('click', () => switchTab('register'));

        // Google Sign-In (delegation vì nút có thể render sau)
        document.addEventListener('click', (e) => {
            const googleBtn = e.target.closest('[data-action="google-signin"]');
            if (googleBtn) handleGoogleSignIn(googleBtn);
        });
    }

    // ─── Init ────────────────────────────────────────────────────────────────

    function init() {
        // Cập nhật UI ngay dựa trên dữ liệu local (không chờ API)
        updateNavUI(isLoggedIn(), localStorage.getItem(AUTH_SESSION_KEY) || '');

        // Đồng bộ với server
        syncAuthState();

        // Tự động mở modal đăng nhập nếu có ?login=1
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('login') === '1' && dom.authModal) {
            const modal = new bootstrap.Modal(dom.authModal);
            modal.show();
            setTimeout(() => showAlert('Vui lòng đăng nhập để tiếp tục.'), 300);
        }

        setupEvents();

        // Ping server định kỳ để giữ phiên
        setInterval(pingSession, SESSION_PING_INTERVAL_MS);
    }

    // Expose handleGoogleSignIn ra ngoài vì được gọi từ HTML (onclick attribute)
    window.handleGoogleSignIn = handleGoogleSignIn;

    init();
});
