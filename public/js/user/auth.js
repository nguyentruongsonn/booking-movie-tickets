const AUTH_USER_NAME_KEY = 'user_name';

function getAuthToken() {
    return localStorage.getItem(AUTH_USER_NAME_KEY) ? 'session_active' : null;
}

function showAuthAlert(message, type = 'error') {
    const alertEl = document.getElementById('auth-alert');
    if (!alertEl) return;
    
    alertEl.textContent = message;
    alertEl.className = `auth-alert ${type}`;
    alertEl.classList.remove('d-none');
    
    setTimeout(() => {
        alertEl.classList.add('d-none');
    }, 5000);
}
 
function setAuthState(token, userName = '') {
    if (userName) {
        BookingApi.TokenStore.setUserName(userName);
    } else {
        BookingApi.TokenStore.clear();
    }
    updateNavigationAuthUI(true, userName);
}

function clearAuthState() {
    BookingApi.TokenStore.clear();
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
        nameEl.textContent = isLoggedIn ? userName || BookingApi.TokenStore.getUserName() || '' : '';
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

async function myFetch(url, options = {}) {
    return BookingApi.apiFetch(url, options);
}

//Kiểm tra phiên đăng nhập
async function syncAuthState() {
    const token = getAuthToken();
 
    if (!token) {
        updateNavigationAuthUI(false);
        return;
    }
 
    try {
        const response = await myFetch('/auth/me');
        const result = await response.json();
 
        if (result.status === 'success') {
            const userName = result.data?.full_name || '';
            setAuthState('session_active', userName);
        } else {
            clearAuthState();
        }
    } catch (error) {
        console.error('Dong bo trang thai dang nhap that bai', error);
        clearAuthState();
    }
}



async function handleRegister(event) {
    event.preventDefault(); // Xử lý sự kiện mà không bị load
    const $btn = $('#btn-register');
    
    // Thu thập dữ liệu theo English Schema
    const formData = {
        email:                 $('#register-email').val(),
        password:              $('#register-password').val(),
        password_confirmation: $('#register-confirm-password').val(),
        full_name:             $('#register-name').val(),
        phone:                 $('#register-phone').val(),
        gender:                $('#register-gender').val(),
        birthday:              $('#register-birthday').val(),
        _token:                $('input[name="_token"]').val(),
    };

    $btn.prop('disabled', true).text('Dang dang ky ...');

    try {
        const res = await myFetch('/auth/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData),
        });

        const result = await parseJsonResponse(res);

        if (res.ok && result?.status === 'success') {
            switchTab('login');
            $('#login-email').val(formData.email);
            $('#form-register')[0].reset();
            showAuthAlert('Đăng ký thành công! Vui lòng đăng nhập.', 'success');
            return;
        }

        // dữ liệu sai hoặc thiếu (Validation error)
        if (res.status === 422 && result?.errors) {
            const errorList = Object.values(result.errors).flat();
            showAuthAlert("Lỗi: " + errorList.join(', '));
            return;
        }
 
        showAuthAlert(result?.message || 'Đăng ký thất bại.');
    } catch (error) {
        console.error('Error:', error);
        showAuthAlert('Có lỗi kết nối máy chủ.');
    } finally {
        $btn.prop('disabled', false).text('Tao Tai Khoan');
    }
}

async function handleLogin(event) {
    event.preventDefault();
    const $btn = $('#btn-login');
    const formData = {
        email:       $('#login-email').val(),
        password:    $('#login-password').val(),
        device_name: navigator.userAgent,
        _token:      $('input[name="_token"]').val(),
    };

    $btn.prop('disabled', true).text('Dang dang nhap ...');

    try {
        const res = await myFetch('/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData),
        });

        const result = await parseJsonResponse(res);

        if (res.ok && result?.status === 'success') {
            const userName = result.data?.user?.full_name || '';
            setAuthState('session_active', userName);
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
        const res = await myFetch('/auth/logout', {
            method: 'POST',
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
            setAuthState(result.token, result.user?.full_name || '');
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
    
    // Tự động mở modal đăng nhập nếu có tham số ?login=1
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('login') === '1') {
        if (document.getElementById('authModal')) {
            $('#authModal').modal('show');
            setTimeout(() => {
                showAuthAlert('Vui lòng đăng nhập để tiếp tục.', 'error');
            }, 300);
        }
    }
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
