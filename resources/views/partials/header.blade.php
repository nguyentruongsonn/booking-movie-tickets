<nav class="navbar navbar-expand-lg saas-header sticky-top py-3" id="ftco-navbar">
    <div class="container d-flex align-items-center">

        <!-- Logo -->
        <a class="navbar-brand fs-4" href="{{ route('home') }}">NTS<span>Cinema</span></a>

        <!-- Toggle mobile -->
        <button class="navbar-toggler border-0" type="button" data-toggle="collapse" data-target="#ftco-nav">
            <span class="fa fa-bars text-primary" style="font-size: 1.5rem;"></span>
        </button>

        <!-- Navbar -->
        <div class="collapse navbar-collapse" id="ftco-nav">

            <!-- Menu (center) -->
            <ul class="navbar-nav mx-auto text-center gap-2">
                <li class="nav-item"><a href="{{ route('home') }}" class="nav-link fw-semibold">Trang chủ</a></li>
                <li class="nav-item"><a href="{{ route('about') }}" class="nav-link fw-semibold">Giới thiệu</a></li>
                <li class="nav-item"><a href="{{ route('blog') }}" class="nav-link fw-semibold">Sự kiện</a></li>
                <li class="nav-item"><a href="{{ route('contact') }}" class="nav-link fw-semibold">Liên hệ</a></li>
            </ul>

            <!-- Right side (login / user) -->
            <ul class="navbar-nav ml-auto align-items-center">

                <li class="nav-item" id="nav-guest-item" style="display: none !important;">
                    <a href="#" class="btn btn-primary radius-md px-4 fw-bold shadow-sm" data-toggle="modal" data-target="#authModal">
                        Đăng Nhập
                    </a>
                </li>

                <li class="nav-item dropdown" id="nav-user-item" style="display: none !important;">
                    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center gap-2" id="userDropdown" data-toggle="dropdown">
                        <div class="bg-primary-soft text-primary-soft rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                            <i class="fa fa-user"></i>
                        </div>
                        <span id="nav-user-name" class="fw-bold text-dark"></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow-lg border-0 radius-xl mt-2 p-2">
                        <a class="dropdown-item radius-md py-2" href="#">
                            <i class="fa fa-user-circle-o mr-2 text-muted"></i> Hồ sơ của tôi
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger radius-md py-2 fw-semibold" href="javascript:void(0)" onclick="handleLogout(event)">
                            <i class="fa fa-sign-out mr-2"></i> Đăng xuất
                        </a>
                    </div>
                </li>

            </ul>

        </div>
    </div>
</nav>

