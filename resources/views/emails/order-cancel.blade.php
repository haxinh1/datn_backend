<!DOCTYPE html>
<html>
<head>
    <title>Thông báo hủy đơn hàng</title>
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

        <p>Chúng tôi rất tiếc phải thông báo rằng đơn hàng <strong>{{ $order->code }}</strong> của bạn đã bị hủy. Dưới đây là thông tin chi tiết đơn hàng:</p>

        <p><strong>Lý do hủy:</strong> Đơn hàng của bạn không thể được xử lý do một số lý do. Chúng tôi rất tiếc vì sự bất tiện này.</p>

        <h3>Thông tin đơn hàng:</h3>
        <ul>
            <li><strong>Mã đơn hàng:</strong> {{ $order->code }}</li>
            <li><strong>Họ và tên:</strong> {{ $order->fullname }}</li>
            <li><strong>Email:</strong> {{ $order->email }}</li>
            <li><strong>Số điện thoại:</strong> {{ $order->phone_number }}</li>
            <li><strong>Địa chỉ:</strong> {{ $order->address }}</li>
            <li><strong>Tổng tiền:</strong> {{ number_format($order->total_amount, 0, ',', '.') }} VND</li>
        </ul>

        <h3>Chi tiết sản phẩm:</h3>
        <ul>
            @foreach ($order->orderItems as $item)
                <li>
                    <strong>Sản phẩm:</strong> {{ $item->product->name ?? 'Không xác định' }} (
                    @if ($item->productVariant)
                    {{ $item->productVariant->attributeValues->map(fn($attributeValue) => $attributeValue->value)->implode(' - ') }}
                ) 
                @endif 
                   <br> <strong>Số lượng:</strong> {{ $item->quantity }}<br>
                    <strong>Giá:</strong> {{ number_format($item->sell_price, 0, ',', '.') }} VND
                </li>
            @endforeach
        </ul>

        <div class="button">
            <a href="http://localhost:5173/dashboard/cancels/{{$order->user_id}}">Xem chi tiết đơn hủy</a>
        </div>

        <p>Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi.</p>
        
        <div class="footer">
            <p>Trân trọng,</p>
            <p>Đội ngũ hỗ trợ khách hàng</p>
            <p><a href="mailto:support@company.com">Liên hệ với chúng tôi</a></p>
        </div>
    </div>
   
</body>
</html>