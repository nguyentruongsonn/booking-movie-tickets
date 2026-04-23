<div class="modal fade" id="authModal" tabindex="-1" role="dialog" aria-labelledby="authModalLabel">
    <div class="modal-dialog modal-dialog-centered " role="document">
        <div class="modal-content auth-modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <div class="auth-tabs w-100 d-flex radius-md bg-slate-50 p-1">
                    <button class="btn flex-fill fw-bold auth-tab-btn active" id="tab-login" onclick="switchTab('login')">Đăng nhập</button>
                    <button class="btn flex-fill fw-bold auth-tab-btn text-muted" id="tab-register" onclick="switchTab('register')">Đăng ký</button>
                </div>
            </div>

            <div class="modal-body auth-modal-body">
                {{-- Alert lỗi/thành công --}}
                <div id="auth-alert" class="auth-alert d-none"></div>

                <div id="pane-login" class="auth-pane">
                    <form action="" id="form-login" onsubmit="handleLogin(event)">
                        @csrf
                        <div class="form-group mb-3 auth-form-group">
                            <label class="form-label" for="login-email">Email</label>
                            <input type="email" id="login-email" class="form-control auth-input" placeholder="abc@email.com"  required>
                        </div>
                        <div class="form-group mb-4 auth-form-group">
                            <label class="form-label" for="login-password">Mật khẩu</label>
                            <input type="password" id="login-password" class="form-control auth-input" placeholder="Nhập mật khẩu" required>
                        </div>
                        <button class="btn btn-primary w-100 radius-md py-2 shadow-sm fw-bold" id="btn-login" type="submit" >Đăng nhập</button>
                    </form>
                    <div class="d-flex align-items-center my-3 text-muted small">
                        <hr class="flex-fill m-0">
                        <span class="px-3">hoặc đăng nhập với</span>
                        <hr class="flex-fill m-0">
                    </div>
                    <button class="btn btn-outline-secondary w-100 radius-md py-2 fw-medium d-flex align-items-center justify-content-center gap-2" 
                            id="btn-google-login" 
                            data-url="{{ route('auth.redirect', ['provider' => 'google']) }}" 
                            onclick="handleGoogleSignIn(this)">
                        <i class="fa fa-google text-danger"></i> Google
                    </button>
                    <p class="mt-4 text-center text-slate-500 mb-0">Bạn chưa có tài khoản? <a href="#" class="text-primary fw-bold text-decoration-none" onclick="switchTab('register')">Đăng ký ngay</a></p>
                </div>

                <div id="pane-register" class="auth-pane d-none">
                  <form action="" id="form-register" onsubmit="handleRegister(event)">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6 form-group mb-3">
                            <label class="form-label" for="register-name">Họ tên</label>
                            <input type="text" id="register-name" class="form-control auth-input" placeholder="Nhập họ tên" required>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="form-label" for="register-phone">Số điện thoại</label>
                            <input type="text" id="register-phone" class="form-control auth-input" placeholder="Nhập số điện thoại" required>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="form-label" for="register-gender">Giới tính</label>
                            <select id="register-gender" class="form-select" required>
                                <option value="">Chọn giới tính</option>
                                <option value="Nam">Nam</option>
                                <option value="Nữ">Nữ</option>
                                <option value="Khác">Khác</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="form-label" for="register-birthday">Ngày sinh</label>
                            <input type="date" id="register-birthday" class="form-control auth-input" required>
                        </div>
                        <div class="col-12 form-group mb-3">
                            <label class="form-label" for="register-email">Email</label>
                            <input type="email" id="register-email" class="form-control auth-input" placeholder="abc@email.com" required>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="form-label" for="register-password">Mật khẩu</label>
                            <input type="password" id="register-password" class="form-control auth-input" placeholder="Nhập mật khẩu" required>
                        </div>
                        <div class="col-md-6 form-group mb-4">
                            <label class="form-label" for="register-confirm-password">Xác nhận</label>
                            <input type="password" id="register-confirm-password" class="form-control auth-input" placeholder="Nhập lại mật khẩu" required>
                        </div>
                    </div>
                    <button class="btn btn-primary w-100 radius-md py-2 shadow-sm fw-bold" id="btn-register" type="submit">Đăng ký tài khoản</button>
                  </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.auth-alert {
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 13px;
    margin-bottom: 16px;
    border: none;
}
.auth-alert.success {
    background: rgba(40,167,69,0.2);
    color: #155724;
    border: 1px solid rgba(40,167,69,0.3);
}
.auth-alert.error {
    background: rgba(220,53,69,0.2);
    color: #721c24;
    border: 1px solid rgba(220,53,69,0.3);
}
</style>
