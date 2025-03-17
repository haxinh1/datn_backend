<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>

    <style>
        .button {
            margin-top: 20px;
            text-align: center;

        }

        .button a {
            background-color: #black;
            border: none;
            color: white;
            padding: 15px 32px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <h1>Đặt lại mật khẩu của bạn</h1>
    <p>Xin chào!
        Bạn nhận được email này vì chúng tôi đã nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn :</p>
    <a href="http://localhost:5173/reset/{{ $token }}">Đặt lại mật khẩu</a>
</body>

</html>
