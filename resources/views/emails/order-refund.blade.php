<!DOCTYPE html>
<html>
<head>
    <title>Xác nhận hoàn tiền đơn hàng</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7fa;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #4CAF50;
            font-size: 24px;
            text-align: center;
            margin-bottom: 10px;
        }

        p {
            font-size: 16px;
            line-height: 1.6;
            color: #555;
            text-align: center
        }

        h3 {
            color: #333;
            font-size: 20px;
            margin-top: 30px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        ul {
            padding-left: 20px;
        }

        li {
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 10px;
        }

        li strong {
            color: #333;
            font-weight: bold;
        }

        .button {
            margin-top: 30px;
            text-align: center;
        }

        .button a {
            background-color: #4267b2;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            font-size: 18px;
            border-radius: 5px;
            display: inline-block;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .button a:hover {
            background-color: #365899;
            transform: translateY(-2px);
        }

        .footer {
            margin-top: 30px;
            font-size: 14px;
            text-align: center;
            color: #777;
        }

        .footer p {
            margin: 5px 0;
        }

        .footer a {
            color: #4267b2;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Xin chào {{ $order->fullname }},    </h1>

        <p>Đơn hàng <strong>{{ $orderCode }}</strong> của bạn đã được hoàn tiền thành công.</p>

        <h3>Thông tin hoàn tiền:</h3>
        <ul>
            <li><strong>Số tài khoản ngân hàng:</strong> {{ $bankAccount }}</li>
            <li><strong>Tên ngân hàng:</strong> {{ $bankName }}</li>
            <li><strong>Tổng số tiền:</strong> {{ number_format($totalAmount, 0, ',', '.') }} VND</li>
            <li><strong>Minh chứng hoàn tiền:</strong> {{ $refundProof }}</li>
        </ul> 
        
        @if($order->user_id)
        <div class="button">
            <a href="http://localhost:5173/dashboard/cancels/{{$order->user_id}}">Xem chi tiết </a>
        </div>
        @else 
        <div class="button">
            <a href="http://localhost:5173/detail/{{$order->code}}">Xem chi tiết </a>
        </div>
        @endif
        
        <p>Nếu bạn có bất kỳ thắc mắc nào, vui lòng liên hệ với chúng tôi.</p>
        
        <div class="footer">
            <p>Đội ngũ hỗ trợ khách hàng</p>
            <p><strong>Hotline:</strong> 09100204</p>
            <p><strong>Email:</strong> hotro@mollashop.com</p>
            <p>Trân trọng,</p>
            <p><a href="mailto:hotro@mollashop.com">Liên hệ với chúng tôi</a></p>
        </div>
    </div>
</body>
</html>

