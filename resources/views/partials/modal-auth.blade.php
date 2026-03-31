<div class="modal fade" id="authModal" tabindex="-1" role="dialog" aria-labelledby="authModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered " role="document">
        <div class="modal-content auth-modal-content">
            <div class="modal-header auth-modal-header">
                <div class="auth-tabs">
                    <button class="auth-tab-btn active" id="tab-login" onclick="switchTab('login')">Đăng nhập</button>
                    <button class="auth-tab-btn" id="tab-register" onclick="switchTab('register')">Đăng ký</button>
                </div>

            </div>

            <div class="modal-body auth-modal-body">
                <div id="pane-login" class="auth-pane">
                    <form action="" id="form-login" onsubmit="handleLogin(event)">
                        @csrf
                        <div class="form-group auth-form-group">
                            <label for="login-email">Email</label>
                            <input type="email" id="login-email" class="form-control auth-input" placeholder="abc@email.com"  required>
                        </div>
                        <div class="form-group auth-form-group">
                            <label for="login-password">Mật khẩu</label>
                            <input type="password" id="login-password" class="form-control auth-input" placeholder="Nhập mật khẩu" required>
                        </div>
                        <button class="btn auth-btn-primary btn-block" id="btn-login" type="submit" >Đăng nhập</button>
                    </form>
                    <div class="auth-divider"><span>hoặc</span></div>
                    <button class="btn auth-btn-google" 
                            id="btn-google-login" 
                            data-url="{{ route('auth.redirect', ['provider' => 'google']) }}" 
                            onclick="handleGoogleSignIn(this)">
                        Đăng nhập với Google
                    </button>
                    <p class="auth-switch-text mt-3 text-center">Bạn chưa có tài khoản? <a href="#" onclick="switchTab('register')">Đăng ký</a></p>
                </div>

                <div id="pane-register" class="auth-pane d-none">
                  <form action="" id="form-register" onsubmit="handleRegister(event)">
                    @csrf
                    <div class="form-group auth-form-group">
                        <label for="register-name">Họ tên</label>
                        <input type="text" id="register-name" class="form-control auth-input" placeholder="Nhập họ tên" required>
                    </div>
                    <div class="form-group auth-form-group">
                        <label for="register-phone">Số điện thoại</label>
                        <input type="text" id="register-phone" class="form-control auth-input" placeholder="Nhập số điện thoại" required>
                    </div>
                    <div class="form-group auth-form-group">
                        <label for="register-gender">Giới tính</label>
                        <select id="register-gender" class="form-control auth-input" required>
                            <option value="">Chọn giới tính</option>
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                        </select>
                    </div>
                    <div class="form-group auth-form-group">
                        <label for="register-birthday">Ngày sinh</label>
                        <input type="date" id="register-birthday" class="form-control auth-input" required>
                    </div>
                    <div class="form-group auth-form-group">
                        <label for="register-email">Email</label>
                        <input type="email" id="register-email" class="form-control auth-input" placeholder="abc@email.com" required>
                    </div>
                    <div class="form-group auth-form-group">
                        <label for="register-password">Mật khẩu</label>
                        <input type="password" id="register-password" class="form-control auth-input" placeholder="Nhập mật khẩu" required>
                    </div>
                    <div class="form-group auth-form-group">
                        <label for="register-confirm-password">Xác nhận mật khẩu</label>
                        <input type="password" id="register-confirm-password" class="form-control auth-input" placeholder="Xác nhận mật khẩu" required>
                    </div>
                    <button class="btn auth-btn-primary btn-block" id="btn-register" type="submit">Đăng ký</button>
                  </form>
                </div>
            </div>
        </div>
    </div>
</div>