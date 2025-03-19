<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
/* 
        .header {
            background-color: #4267b2;
            color: white;
            padding: 20px;
            font-size: 24px;
            border-radius: 10px 10px 0 0;
        } */

        .content {
            margin-top: 20px;
            text-align: left;
            font-size: 16px;
            color: #333;
        }

        .content p {
            line-height: 1.6;
        }

        .button {
            margin-top: 30px;
        }

        .button a {
            background-color: #4267b2;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            font-size: 18px;
            border-radius: 5px;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .button a:hover {
            background-color: #365899;
        }

        .footer {
            margin-top: 30px;
            font-size: 14px;
            color: #666;
        }

        .footer a {
            color: #4267b2;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>  Đặt lại mật khẩu</h2>
          
        </div>

        <div class="content">
            <p>Xin chào!</p>
            <p>Bạn nhận được email này vì chúng tôi đã nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.</p>

            <div class="button">
                <a href="http://localhost:5173/resetad/{{ $token }}">Đặt lại mật khẩu</a>
            </div>

            
        </div>

        <div class="footer">
            <p>Trân trọng</p>
        </div>
    </div>
</body>

</html>
