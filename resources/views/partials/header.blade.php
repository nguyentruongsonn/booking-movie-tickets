<nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar">
    <div class="container d-flex align-items-center">

        <!-- Logo -->
        <a class="navbar-brand" href="index.html">NTS<span>Cinema</span></a>

        <!-- Toggle mobile -->
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav">
            <span class="oi oi-menu"></span> Menu
        </button>

        <!-- Navbar -->
        <div class="collapse navbar-collapse" id="ftco-nav">

            <!-- Menu (center) -->
            <ul class="navbar-nav mx-auto text-center">
                <li class="nav-item active"><a href="{{ route('home') }}" class="nav-link">Trang chủ</a></li>
                <li class="nav-item"><a href="{{ route('about') }}" class="nav-link">Giới thiệu</a></li>
                <li class="nav-item"><a href="{{ route('blog') }}" class="nav-link">Sự kiện</a></li>
                <li class="nav-item"><a href="{{ route('contact') }}" class="nav-link">Liên hệ</a></li>
                 <li class="nav-item"><a href="{{ route('contact') }}" class="nav-link">Rạp/ Giá vé</a></li>
            </ul>

            <!-- Right side (login / user) -->
            <ul class="navbar-nav ml-auto align-items-center">

                <li class="nav-item" id="nav-guest-item" style="display: none !important;">
                    <a href="#" class="nav-link btn-login-nav" data-toggle="modal" data-target="#authModal">
                        Đăng Nhập
                    </a>
                </li>

                <li class="nav-item dropdown" id="nav-user-item" style="display: none !important;">
                    <a href="#" class="nav-link dropdown-toggle" id="userDropdown" data-toggle="dropdown">
                        <i class="fa fa-user-circle mr-1"></i>
                        <span id="nav-user-name"></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="#">
                            <i class="fa fa-user mr-2"></i>Hồ sơ
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="handleLogout(event)">
                            <i class="fa fa-sign-out mr-2"></i>Đăng xuất
                        </a>
                    </div>
                </li>

            </ul>

        </div>
    </div>
</nav>
