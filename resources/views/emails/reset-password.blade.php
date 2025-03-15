<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
</head>
<body>
    <h1>Đổi mật khẩu của bạn</h1>
    <p>Để thay đổi mật khẩu của bạn, vui lòng nhấn vào liên kết dưới đây:</p>
    <a href="{{ url('/password/reset/'.$token) }}" style="background-color:black ; color:white" >Đổi mật khẩu</a>
</body>
</html>
