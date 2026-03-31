<nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.html">Pacific<span>Travel Agency</span></a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav" aria-controls="ftco-nav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="oi oi-menu"></span> Menu
        </button>

        <div class="collapse navbar-collapse" id="ftco-nav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item active"><a href="{{ route('home') }}" class="nav-link">Trang chủ</a></li>
                <li class="nav-item"><a href="{{ route('about') }}" class="nav-link">Giới thiệu</a></li>
                <li class="nav-item"><a href="{{ route('booking') }}" class="nav-link">Phim</a></li>
                <li class="nav-item"><a href="{{ route('blog') }}" class="nav-link">Sự kiện</a></li>
                <li class="nav-item"><a href="{{ route('contact') }}" class="nav-link">Liên hệ</a></li>

                <li class="nav-item" id="nav-guest-item" style="display: none !important;">
                    <a href="#" class="nav-link btn-login-nav" data-toggle="modal" data-target="#authModal">
                        Đăng Nhập
                    </a>
                </li>

                <li class="nav-item dropdown" id="nav-user-item" style="display: none !important;">
                    <a href="#" class="nav-link dropdown-toggle" id="userDropdown" role="button" data-toggle="dropdown">
                        <i class="fa fa-user-circle mr-1"></i>
                        <span id="nav-user-name"></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right user-dropdown-menu">
                        <a class="dropdown-item" href="#"><i class="fa fa-user mr-2"></i>Hồ sơ</a>
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