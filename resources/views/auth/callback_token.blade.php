<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đang đăng nhập...</title>

</head>
<body>


    <script>
        // 1. Lấy Token và tên User từ Laravel truyền sang
        const token = "{{ $token }}";
        const userName = "{{ $userName ?? '' }}";

        // 2. Lưu vào túi (LocalStorage) của trình duyệt
        if (token) {
            localStorage.setItem('auth_token', token);
            if (userName) {
                localStorage.setItem('user_name', userName);
            }
            console.log("Token đã được cất vào túi!");
        }

        window.location.replace("{{ route('home') }}");
    </script>
</body>
</html>