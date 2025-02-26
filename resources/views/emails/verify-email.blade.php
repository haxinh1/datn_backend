<!DOCTYPE html>
<html>
<head>
    <title>Xác nhận email</title>
</head>
<body>
    <h1>Xin chào, {{ $user->fullname }}</h1>
    <p>Vui lòng nhấp vào liên kết dưới đây để xác nhận email của bạn:</p>
    <a href="{{ url('/api/verify-email?token=' . $token) }}">Xác nhận email</a>
    <p>Nếu bạn không yêu cầu xác nhận email này, vui lòng bỏ qua email này.</p>
</body>
</html>