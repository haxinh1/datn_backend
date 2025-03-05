<!DOCTYPE html>
<html>
<head>
    <title>Xác nhận đăng ký</title>
</head>
<body>
    <h2>Chào bạn {{ $user->fullname  }}!</h2>
    <p>Mã xác nhận của bạn là: <strong>{{ $code }}</strong></p>
    <p>Vui lòng nhập mã này để xác nhận tài khoản.</p>
</body>
</html>