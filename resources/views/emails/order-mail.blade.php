<!-- filepath: c:\laragon\www\datn_backend\resources\views\emails\order.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Thông tin đơn hàng</title>
    <style>
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

    </style>
</head>
<body>
    <h1>Xin chào {{ $order->fullname }},</h1>
    <p>Cảm ơn bạn đã đặt hàng tại cửa hàng của chúng tôi. Dưới đây là thông tin chi tiết đơn hàng của bạn:</p>

    <h3>Thông tin đơn hàng:</h3>
    <ul>

        <li><strong>Mã đơn hàng:</strong> {{ $order->code }}</li>
        <li><strong>Họ và tên:</strong> {{ $order->fullname }}</li>
        <li><strong>Email:</strong> {{ $order->email }}</li>
        <li><strong>Số điện thoại:</strong> {{ $order->phone_number }}</li>
        <li><strong>Địa chỉ:</strong> {{ $order->address }}</li>
        <li><strong>Tổng tiền:</strong> {{ number_format($order->total_amount, 0, ',', '.') }} VND</li>
        <li><strong>Phương thức thanh toán:</strong> {{ $order->payment->name ?? 'Không xác định' }}</li>
        <li><strong>Trạng thái:</strong> {{ $order->status->name ?? 'Không xác định' }}</li>
    </ul>

    <h3>Chi tiết sản phẩm:</h3>
    <ul>
        @foreach ($order->orderItems as $item)
            <li>
                <strong>Sản phẩm:</strong> {{ $item->product->name ?? 'Không xác định' }}<br>
                <strong>Số lượng:</strong> {{ $item->quantity }}<br>
                <strong>Giá:</strong> {{ number_format($item->sell_price, 0, ',', '.') }} VND
            </li>
        @endforeach
    </ul>
    <div class="button">
        <a href="http://localhost:5173/detail/{{$order->id}}">Đơn hàng của bạn</a>
    </div>

    <p>Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi.</p>
    <p>Trân trọng,</p>
    <p>Đội ngũ hỗ trợ khách hàng</p>
</body>
</html>