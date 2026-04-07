const AUTH_STORAGE_KEY = 'auth_token';
const AUTH_USER_NAME_KEY = 'user_name';
let refreshPromise = null;

function getAuthToken() {
    const token = localStorage.getItem(AUTH_STORAGE_KEY);
    return token && token !== 'undefined' ? token : null;
}

function setAuthState(token, userName = '') {
    localStorage.setItem(AUTH_STORAGE_KEY, token);

    if (userName) {
        localStorage.setItem(AUTH_USER_NAME_KEY, userName);
    } else {
        localStorage.removeItem(AUTH_USER_NAME_KEY);
    }

    updateNavigationAuthUI(true, userName);
}

function clearAuthState() {
    localStorage.removeItem(AUTH_STORAGE_KEY);
    localStorage.removeItem(AUTH_USER_NAME_KEY);
    updateNavigationAuthUI(false);
}

function updateNavigationAuthUI(isLoggedIn, userName = '') {
    const guestEl = document.getElementById('nav-guest-item');
    const userEl = document.getElementById('nav-user-item');
    const nameEl = document.getElementById('nav-user-name');

    if (guestEl && userEl) {
        guestEl.style.setProperty('display', isLoggedIn ? 'none' : 'block', 'important');
        userEl.style.setProperty('display', isLoggedIn ? 'block' : 'none', 'important');
    }

    if (nameEl) {
        nameEl.textContent = isLoggedIn ? userName || localStorage.getItem(AUTH_USER_NAME_KEY) || '' : '';
    }
}

async function parseJsonResponse(response) {
    const contentType = response.headers.get('content-type') || '';

    if (!contentType.includes('application/json')) {
        return null;
    }

    try {
        return await response.json();
    } catch (error) {
        console.error('Khong the parse JSON response', error);
        return null;
    }
}

function buildApiUrl(url) {
    return url.startsWith('/api') ? url : `/api${url.startsWith('/') ? '' : '/'}${url}`;
}

async function refreshAccessToken() {
    if (!refreshPromise) {
        refreshPromise = (async () => {
            const response = await fetch('/api/auth/refresh-token', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                },
            });

            const result = await parseJsonResponse(response);

            if (!response.ok || result?.status !== 'success' || !result?.token) {
                throw new Error(result?.message || 'Khong the lam moi token');
            }

            const currentName = localStorage.getItem(AUTH_USER_NAME_KEY) || '';
            setAuthState(result.token, currentName); // Lưu token và cập nhật UI

            return result.token;
        })();

        refreshPromise.finally(() => {
            refreshPromise = null;
        });
    }

    return refreshPromise;
}

async function myFetch(url, options = {}) {
    const finalUrl = buildApiUrl(url);
    const token = getAuthToken();
    const headers = {
        'Accept': 'application/json',
        ...options.headers, // lấy tất cả các cặp key - value trong options.header
    };

    //Kiểm tra dữ liệ gửi đi có phải lầ formdate ?
    if (!(options.body instanceof FormData) && !headers['Content-Type']) {
        headers['Content-Type'] = 'application/json';
    }

    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    const requestOptions = {
        ...options,
        headers,
        credentials: 'include',
    };

    let response = await fetch(finalUrl, requestOptions);

    //Nếu token còn hạn
    if (response.status !== 401 || finalUrl.includes('refresh-token')) {
        return response;
    }

    try {
        const newToken = await refreshAccessToken();
        const retryHeaders = {
            ...headers,
            Authorization: `Bearer ${newToken}`,
        };

        response = await fetch(finalUrl, {
            ...requestOptions,
            headers: retryHeaders,
        });
    } catch (error) {
        console.error('Refresh token that bai', error);
        handleLogoutForce();
        throw error;
    }

    return response;
}

//Kiểm tra phiên đăng nhập
async function syncAuthState() {
    const token = getAuthToken();

    if (!token) {
        updateNavigationAuthUI(false);
        return;
    }

    try {
        const response = await myFetch('/auth/me', {
            method: 'GET',
        });

        if (!response.ok) {
            throw new Error('Khong the xac thuc phien dang nhap');
        }

        const result = await parseJsonResponse(response);
        const userName = result?.data?.ho_ten || localStorage.getItem(AUTH_USER_NAME_KEY) || '';
        setAuthState(getAuthToken(), userName);
    } catch (error) {
        console.error('Dong bo trang thai dang nhap that bai', error);
        clearAuthState();
    }
}



async function handleRegister(event) {
    event.preventDefault(); // Xử lý sự kiện mà không bị load
    const $btn = $('#btn-register');
    const formData = {
        email: $('#register-email').val(),
        mat_khau: $('#register-password').val(),
        mat_khau_confirmation: $('#register-confirm-password').val(),
        ho_ten: $('#register-name').val(),
        so_dien_thoai: $('#register-phone').val(),
        gioi_tinh: $('#register-gender').val(),
        ngay_sinh: $('#register-birthday').val(),
        _token: $('input[name="_token"]').val(),
    };

    $btn.prop('disabled', true).text('Dang dang ky ...');

    try {
        const res = await fetch('/api/auth/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': formData._token,
            },
            body: JSON.stringify(formData),
        });

        const result = await parseJsonResponse(res);

        if (res.ok && result?.status === 'success') {
            switchTab('login');
            $('#login-email').val(formData.email);
            $('#register-form')[0].reset();
            return;
        }

        //dữ liệu sai hoặc thiếu
        if (res.status === 422 && result?.errors) {
            const errorMessage = Object.values(result.errors).flat().join('\n');
            alert(errorMessage);
            return;
        }

        alert(result?.message || 'Dang ky that bai, vui long kiem tra lai.');
    } catch (error) {
        console.error('Error:', error);
        alert('Co loi xay ra, vui long thu lai sau.');
    } finally {
        $btn.prop('disabled', false).text('Dang ky');
    }
}

async function handleLogin(event) {
    event.preventDefault();
    const $btn = $('#btn-login');
    const formData = {
        email: $('#login-email').val(),
        mat_khau: $('#login-password').val(),
        device_name: navigator.userAgent,
        _token: $('input[name="_token"]').val(),
    };

    $btn.prop('disabled', true).text('Dang dang nhap ...');

    try {
        const res = await fetch('/api/auth/login', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': formData._token,
            },
            body: JSON.stringify(formData),
        });

        const result = await parseJsonResponse(res);

        if (res.ok && result?.status === 'success' && result?.token) {
            setAuthState(result.token, result.user?.ho_ten || '');
            $('#authModal').modal('hide');
            alert('Dang nhap thanh cong');
            return;
        }

        alert(result?.message || 'Dang nhap that bai.');
    } catch (error) {
        console.error('Error:', error);
        alert('Co loi xay ra, vui long thu lai sau.');
    } finally {
        $btn.prop('disabled', false).text('Dang nhap');
    }
}

async function handleLogout(event) {
    event.preventDefault();

    if (!confirm('Ban co chac chan muon dang xuat?')) {
        return;
    }

    const $btn = $('#btn-logout');
    const token = getAuthToken();

    $btn.prop('disabled', true).text('Dang dang xuat ...');

    try {
        const res = await fetch('/api/auth/logout', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': token ? `Bearer ${token}` : '',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
        });

        if (!res.ok && res.status !== 401) {
            const result = await parseJsonResponse(res);
            alert(result?.message || 'Dang xuat that bai, vui long thu lai.');
            return;
        }

        handleLogoutForce();
    } catch (error) {
        console.error('Logout error:', error);
        alert('Khong the dang xuat luc nay, vui long thu lai.');
    } finally {
        $btn.prop('disabled', false).text('Dang xuat');
    }
}

function handleGoogleSignIn(element) {
    const url = $(element).data('url');
    const strWindowFeatures = 'toolbar=no, menubar=no, width=600, height=700, top=100, left=100';

    window.open(url, 'GoogleLogin', strWindowFeatures);

    window.addEventListener('message', function (event) {
        if (event.origin !== window.location.origin) {
            return;
        }

        const result = event.data;

        if (result?.status === 'success' && result?.token) {
            setAuthState(result.token, result.user?.ho_ten || '');
            $('#authModal').modal('hide');
            alert('Dang nhap Google thanh cong!');
        }
    }, { once: true });
}

function handleLogoutForce() {
    clearAuthState();
    window.location.replace('/');
}

function switchTab(tab) {
    if (tab === 'login') {
        $('#tab-login').addClass('active');
        $('#tab-register').removeClass('active');
        $('#pane-login').removeClass('d-none');
        $('#pane-register').addClass('d-none');
    } else if (tab === 'register') {
        $('#tab-register').addClass('active');
        $('#tab-login').removeClass('active');
        $('#pane-register').removeClass('d-none');
        $('#pane-login').addClass('d-none');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    updateNavigationAuthUI(Boolean(getAuthToken()), localStorage.getItem(AUTH_USER_NAME_KEY) || '');
    syncAuthState();
});

setInterval(async () => {
    if (!getAuthToken()) {
        return;
    }

    try {
        await myFetch('/auth/me', {
            method: 'GET',
        });
    } catch (error) {
        console.error('Kiem tra phien dang nhap that bai', error);
    }
}, 900000);
