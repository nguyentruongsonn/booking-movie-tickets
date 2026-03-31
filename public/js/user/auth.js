async function handleRegister(event) {
    event.preventDefault();
    const $btn = $('#btn-register');
    const formData = {
        email: $('#register-email').val(),
        mat_khau: $('#register-password').val(),
        mat_khau_confirmation: $('#register-confirm-password').val(),
        ho_ten: $('#register-name').val(),
        so_dien_thoai: $('#register-phone').val(),
        gioi_tinh: $('#register-gender').val(),
        ngay_sinh: $('#register-birthday').val(),
        _token: $('input[name="_token"]').val()
    }
    $btn.prop('disabled', true).text('Đang đăng ký ...');

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
        const result = await res.json();
        if (res.ok && result.status === 'success') {
            switchTab('login');
            $('#login-email').val(formData.email);
            $('#register-form')[0].reset();
        }
        else {
            if (res.status === 422 && result.errors) {
                // Gom tất cả các thông báo lỗi lại thành 1 chuỗi
                let errorMessage = Object.values(result.errors).flat().join('\n');
                alert(errorMessage);
            } else {
                alert(result.message || 'Đăng ký thất bại, vui lòng kiểm tra lại.');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra, vui lòng thử lại sau.');
    } finally {
        $btn.prop('disabled', false).text('Đăng ký');
    }
}

async function handleLogin(event) {
    event.preventDefault();
    const $btn = $('#btn-login');
    const formData = {
        email: $('#login-email').val(),
        mat_khau: $('#login-password').val(),
        device_name: navigator.userAgent, //lấy tên thiết bị
        _token: $('input[name="_token"]').val()
    }

    // Vô hiệu hóa nút đăng nhập khi spam liên tục
    $btn.prop('disabled', true).text('Đang đăng nhập ....');

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
        const result = await res.json();
        if (res.ok && result.status === 'success') {
            localStorage.setItem('auth_token', result.token);
            if (result.user?.ho_ten) {
                localStorage.setItem('user_name', result.user.ho_ten);
            }
            $('#authModal').modal('hide');

            const guestEl = document.getElementById('nav-guest-item');
            const userEl = document.getElementById('nav-user-item');
            const nameEl = document.getElementById('nav-user-name');

            if (guestEl && userEl) {
                guestEl.style.setProperty('display', 'none', 'important');
                userEl.style.setProperty('display', 'block', 'important');
            }
            if (nameEl && result.user?.ho_ten) {
                nameEl.textContent = result.user.ho_ten;
            }
            alert('Đăng nhập thành công');
        }
        else {
            alert(result.message);
        }

    }
    catch (error) {
        // Xử lý lỗi kết nối hoặc lỗi hệ thống
        console.error('Error:', error);
        alert('Có lỗi xảy ra, vui lòng thử lại sau.');
    } finally {
        // Luôn chạy dòng này dù thành công hay thất bại (giống .complete)
        $btn.prop('disabled', false).text('Đăng nhập');
    }
}

async function handleLogout(event) {
    event.preventDefault();

    // Thêm xác nhận để tránh bấm nhầm
    if (!confirm('Bạn có chắc chắn muốn đăng xuất?')) return;

    const $btn = $('#btn-logout');
    const token = localStorage.getItem('auth_token')
    $btn.prop('disabled', true).text('Đang đăng xuất ...');
    try {
        const res = await fetch('/api/auth/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + token,
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        const result = await res.json();
        console.log(result);
    } catch (error) {
        console.log(error);
    }
    finally {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_name');
        window.location.replace('/');
    }
}

function handleGoogleSignIn(element) {
    const url = $(element).data('url'); // Route('auth.redirect')

    // Mở một cửa sổ popup
    const strWindowFeatures = "toolbar=no, menubar=no, width=600, height=700, top=100, left=100";
    const authWindow = window.open(url, "GoogleLogin", strWindowFeatures);

    // Lắng nghe tin nhắn gửi về từ cửa sổ Popup
    window.addEventListener("message", function (event) {
        // Kiểm tra nguồn gốc tin nhắn (tùy chọn bảo mật)
        if (event.origin !== window.location.origin) return;

        const result = event.data;

        if (result.status === 'success') {
            // ĐOẠN NÀY GIỐNG HỆT handleLogin CỦA BẠN
            localStorage.setItem('auth_token', result.token);
            localStorage.setItem('user_name', result.user.ho_ten);

            $('#authModal').modal('hide');

            const guestEl = document.getElementById('nav-guest-item');
            const userEl = document.getElementById('nav-user-item');
            const nameEl = document.getElementById('nav-user-name');

            if (guestEl && userEl) {
                guestEl.style.setProperty('display', 'none', 'important');
                userEl.style.setProperty('display', 'block', 'important');
            }
            if (nameEl) {
                nameEl.textContent = result.user.ho_ten;
            }
            alert('Đăng nhập Google thành công!');
        }
    }, { once: true }); // Chỉ nghe một lần duy nhất
}

let isRefreshing = false;
let refreshSubscribers = [];

function subscribeTokenRefresh(cb) {
    refreshSubscribers.push(cb);
}

function onTokenRefreshed(newToken) {
    refreshSubscribers.map(cb => cb(newToken));
    refreshSubscribers = [];
}

async function myFetch(url, options = {}) {
    const token = localStorage.getItem('auth_token');
    const finalUrl = url.startsWith('/api') ? url : `/api${url.startsWith('/') ? '' : '/'}${url}`;

    options.headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        ...options.headers,
    };

    if (token) options.headers['Authorization'] = `Bearer ${token}`;
    options.credentials = 'include';

    let response = await fetch(finalUrl, options);

    if (response.status === 401 && !finalUrl.includes('refresh-token')) {
        if (!isRefreshing) {
            isRefreshing = true;
            console.log("Đang làm mới token...");

            fetch('/api/auth/refresh-token', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Accept': 'application/json' }
            }).then(async res => {
                const result = await res.json();
                if (res.ok && result.status === 'success') {
                    localStorage.setItem('auth_token', result.token);
                    isRefreshing = false;
                    onTokenRefreshed(result.token);
                } else {
                    handleLogoutForce();
                }
            });
        }

        // Xếp hàng đợi các request khác
        return new Promise((resolve) => {
            subscribeTokenRefresh((newToken) => {
                options.headers['Authorization'] = `Bearer ${newToken}`;
                resolve(fetch(finalUrl, options));
            });
        });
    }
    return response;
}


function handleLogoutForce() {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_name');
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
    const token = localStorage.getItem('auth_token');
    const userName = localStorage.getItem('user_name');
    const guestEl = document.getElementById('nav-guest-item');
    const userEl = document.getElementById('nav-user-item');
    const nameEl = document.getElementById('nav-user-name');

    if (guestEl && userEl) {
        if (token && token !== 'undefined') {
            userEl.style.setProperty('display', 'block', 'important');
            guestEl.style.setProperty('display', 'none', 'important');
            if (userName && nameEl) {
                nameEl.textContent = userName;
            }
        } else {
            guestEl.style.setProperty('display', 'block', 'important');
            userEl.style.setProperty('display', 'none', 'important');
        }
    }
});

setInterval(async () => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        console.log("Đang kiểm tra phiên đăng nhập định kỳ...");
        await myFetch('/api/auth/me');
    }
}, 900000); // 15 phút