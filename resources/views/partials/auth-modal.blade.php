{{-- Modal Đăng Nhập & Đăng Ký --}}
<div class="modal fade" id="authModal" tabindex="-1" role="dialog" aria-labelledby="authModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content auth-modal-content">

            {{-- Header --}}
            <div class="modal-header auth-modal-header border-0">
                <div class="auth-tabs">
                    <button class="auth-tab-btn active" id="tab-login" onclick="switchTab('login')">
                        <i class="fa fa-sign-in mr-1"></i> Đăng Nhập
                    </button>
                    <button class="auth-tab-btn" id="tab-register" onclick="switchTab('register')">
                        <i class="fa fa-user-plus mr-1"></i> Đăng Ký
                    </button>
                </div>
                <button type="button" class="close auth-close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body auth-modal-body">

                {{-- Alert lỗi/thành công --}}
                <div id="auth-alert" class="auth-alert d-none"></div>

                {{-- ===== TAB ĐĂNG NHẬP ===== --}}
                <div id="pane-login" class="auth-pane">
                    <p class="auth-subtitle">Chào mừng trở lại! Đăng nhập để tiếp tục.</p>

                    <form id="form-login" onsubmit="handleLogin(event)">
                        @csrf
                        <div class="form-group auth-form-group">
                            <label for="login-email"><i class="fa fa-envelope"></i> Email</label>
                            <input type="email" id="login-email" class="form-control auth-input"
                                   placeholder="example@email.com" required>
                            <div class="auth-field-error" id="err-login-email"></div>
                        </div>
                        <div class="form-group auth-form-group">
                            <label for="login-password"><i class="fa fa-lock"></i> Mật khẩu</label>
                            <div class="auth-input-wrapper">
                                <input type="password" id="login-password" class="form-control auth-input"
                                       placeholder="Nhập mật khẩu" required>
                                <span class="auth-eye" onclick="togglePassword('login-password', this)">
                                    <i class="fa fa-eye"></i>
                                </span>
                            </div>
                            <div class="auth-field-error" id="err-login-password"></div>
                        </div>
                        <button type="submit" class="btn auth-btn-primary btn-block" id="btn-login">
                            <span class="btn-text">Đăng Nhập</span>
                            <span class="btn-loading d-none"><i class="fa fa-spinner fa-spin"></i></span>
                        </button>
                    </form>

                    <div class="auth-divider"><span>hoặc</span></div>

                    <button class="btn auth-btn-google btn-block" id="btn-google-login" onclick="handleGoogleSignIn()">
                        <img src="https://www.google.com/favicon.ico" width="18" height="18" class="mr-2" alt="Google">
                        Đăng nhập với Google
                    </button>

                    <p class="auth-switch-text mt-3">
                        Chưa có tài khoản?
                        <a href="#" onclick="switchTab('register')">Đăng ký ngay</a>
                    </p>
                </div>

                {{-- ===== TAB ĐĂNG KÝ ===== --}}
                <div id="pane-register" class="auth-pane d-none">
                    <p class="auth-subtitle">Tạo tài khoản để đặt vé nhanh chóng.</p>

                    <form id="form-register" onsubmit="handleRegister(event)">
                        @csrf
                        <div class="form-group auth-form-group">
                            <label for="reg-name"><i class="fa fa-user"></i> Họ và tên</label>
                            <input type="text" id="reg-name" class="form-control auth-input"
                                   placeholder="Nguyễn Văn A" required>
                            <div class="auth-field-error" id="err-reg-name"></div>
                        </div>
                        <div class="form-group auth-form-group">
                            <label for="reg-email"><i class="fa fa-envelope"></i> Email</label>
                            <input type="email" id="reg-email" class="form-control auth-input"
                                   placeholder="example@email.com" required>
                            <div class="auth-field-error" id="err-reg-email"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group auth-form-group">
                                    <label for="reg-dob"><i class="fa fa-birthday-cake"></i> Ngày sinh</label>
                                    <input type="date" id="reg-dob" class="form-control auth-input">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group auth-form-group">
                                    <label for="reg-gender"><i class="fa fa-venus-mars"></i> Giới tính</label>
                                    <select id="reg-gender" class="form-control auth-input">
                                        <option value="">-- Chọn --</option>
                                        <option value="Nam">Nam</option>
                                        <option value="Nữ">Nữ</option>
                                        <option value="Khác">Khác</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group auth-form-group">
                            <label for="reg-password"><i class="fa fa-lock"></i> Mật khẩu</label>
                            <div class="auth-input-wrapper">
                                <input type="password" id="reg-password" class="form-control auth-input"
                                       placeholder="Ít nhất 6 ký tự" required>
                                <span class="auth-eye" onclick="togglePassword('reg-password', this)">
                                    <i class="fa fa-eye"></i>
                                </span>
                            </div>
                            <div class="auth-field-error" id="err-reg-password"></div>
                        </div>
                        <div class="form-group auth-form-group">
                            <label for="reg-confirm"><i class="fa fa-lock"></i> Xác nhận mật khẩu</label>
                            <div class="auth-input-wrapper">
                                <input type="password" id="reg-confirm" class="form-control auth-input"
                                       placeholder="Nhập lại mật khẩu" required>
                                <span class="auth-eye" onclick="togglePassword('reg-confirm', this)">
                                    <i class="fa fa-eye"></i>
                                </span>
                            </div>
                            <div class="auth-field-error" id="err-reg-confirm"></div>
                        </div>
                        <button type="submit" class="btn auth-btn-primary btn-block" id="btn-register">
                            <span class="btn-text"><i class="fa fa-user-plus mr-1"></i> Tạo Tài Khoản</span>
                            <span class="btn-loading d-none"><i class="fa fa-spinner fa-spin"></i></span>
                        </button>
                    </form>

                    <div class="auth-divider"><span>hoặc</span></div>

                    <button class="btn auth-btn-google btn-block" onclick="handleGoogleSignIn()">
                        <img src="https://www.google.com/favicon.ico" width="18" height="18" class="mr-2" alt="Google">
                        Đăng ký với Google
                    </button>

                    <p class="auth-switch-text mt-3">
                        Đã có tài khoản?
                        <a href="#" onclick="switchTab('login')">Đăng nhập</a>
                    </p>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
/* ========== AUTH MODAL STYLES ========== */
.auth-modal-content {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 16px;
    color: #fff;
    box-shadow: 0 25px 60px rgba(0,0,0,0.5);
}

.auth-modal-header {
    padding: 20px 24px 0;
    align-items: center;
}

.auth-tabs {
    display: flex;
    gap: 4px;
    background: rgba(255,255,255,0.05);
    border-radius: 10px;
    padding: 4px;
    flex: 1;
    margin-right: 12px;
}

.auth-tab-btn {
    flex: 1;
    background: transparent;
    border: none;
    color: rgba(255,255,255,0.5);
    font-size: 14px;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    letter-spacing: 0.3px;
}

.auth-tab-btn.active {
    background: linear-gradient(135deg, #e84393, #f96d00);
    color: #fff;
    box-shadow: 0 4px 15px rgba(232,67,147,0.3);
}

.auth-close {
    color: rgba(255,255,255,0.6);
    font-size: 22px;
    line-height: 1;
    opacity: 1;
}
.auth-close:hover { color: #fff; }

.auth-modal-body {
    padding: 20px 28px 28px;
}

.auth-subtitle {
    color: rgba(255,255,255,0.55);
    font-size: 13px;
    margin-bottom: 20px;
    text-align: center;
}

/* Form fields */
.auth-form-group label {
    font-size: 13px;
    font-weight: 600;
    color: rgba(255,255,255,0.75);
    margin-bottom: 6px;
    letter-spacing: 0.3px;
}
.auth-form-group label i {
    margin-right: 5px;
    color: #e84393;
}

.auth-input {
    background: rgba(255,255,255,0.07) !important;
    border: 1px solid rgba(255,255,255,0.12) !important;
    color: #fff !important;
    border-radius: 10px !important;
    padding: 10px 14px !important;
    font-size: 14px !important;
    transition: all 0.3s ease !important;
}
.auth-input:focus {
    background: rgba(255,255,255,0.12) !important;
    border-color: #e84393 !important;
    box-shadow: 0 0 0 3px rgba(232,67,147,0.15) !important;
    outline: none !important;
}
.auth-input::placeholder { color: rgba(255,255,255,0.3) !important; }
.auth-input option { background: #16213e; color: #fff; }

/* Password eye toggle */
.auth-input-wrapper {
    position: relative;
}
.auth-eye {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: rgba(255,255,255,0.4);
    transition: color 0.2s;
    z-index: 10;
}
.auth-eye:hover { color: #e84393; }

/* Buttons */
.auth-btn-primary {
    background: linear-gradient(135deg, #e84393, #f96d00);
    border: none;
    color: #fff;
    font-weight: 700;
    font-size: 15px;
    padding: 12px;
    border-radius: 10px;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    box-shadow: 0 6px 20px rgba(232,67,147,0.35);
}
.auth-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(232,67,147,0.5);
    color: #fff;
}
.auth-btn-primary:active { transform: translateY(0); }

.auth-btn-google {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    color: #fff;
    font-weight: 600;
    font-size: 14px;
    padding: 11px;
    border-radius: 10px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}
.auth-btn-google:hover {
    background: rgba(255,255,255,0.15);
    border-color: rgba(255,255,255,0.3);
    color: #fff;
    transform: translateY(-1px);
}

/* Divider */
.auth-divider {
    text-align: center;
    margin: 16px 0;
    position: relative;
}
.auth-divider::before, .auth-divider::after {
    content: '';
    position: absolute;
    top: 50%;
    width: 40%;
    height: 1px;
    background: rgba(255,255,255,0.1);
}
.auth-divider::before { left: 0; }
.auth-divider::after { right: 0; }
.auth-divider span {
    font-size: 12px;
    color: rgba(255,255,255,0.35);
    padding: 0 8px;
    background: transparent;
}

/* Switch text */
.auth-switch-text {
    text-align: center;
    font-size: 13px;
    color: rgba(255,255,255,0.45);
    margin-bottom: 0;
}
.auth-switch-text a {
    color: #e84393;
    font-weight: 600;
    text-decoration: none;
}
.auth-switch-text a:hover { color: #f96d00; text-decoration: underline; }

/* Field errors */
.auth-field-error {
    font-size: 12px;
    color: #ff6b6b;
    margin-top: 4px;
    min-height: 16px;
}

/* Alert */
.auth-alert {
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 13px;
    margin-bottom: 16px;
    border: none;
}
.auth-alert.success {
    background: rgba(40,167,69,0.2);
    color: #a8f0b8;
    border: 1px solid rgba(40,167,69,0.3);
}
.auth-alert.error {
    background: rgba(220,53,69,0.2);
    color: #ffb3b3;
    border: 1px solid rgba(220,53,69,0.3);
}
</style>
