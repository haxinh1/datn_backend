<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;

class VNPayController extends Controller
{
    public function createPayment(Request $request)
{
    $request->validate([
        'order_id' => 'required|exists:orders,id',
        'payment_method' => 'required|in:vnpay,cod'
    ]);

    $order = Order::findOrFail($request->order_id);

    // Xử lý thanh toán VNPay
    if ($request->payment_method === 'vnpay') {
        $vnp_Url = env('VNP_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
        $vnp_Returnurl = env('VNP_RETURN_URL', 'http://your-website.com/payment/vnpay/return');
        $vnp_TmnCode = env('VNP_TMN_CODE', 'FOQ8B80U');
        $vnp_HashSecret = env('VNP_HASH_SECRET', 'E2XRZHWXVAC2XGJXO1M51MRRQXNDN3U8');

        $vnp_TxnRef = $order->id; // ID đơn hàng
        $vnp_OrderInfo = "Thanh toán đơn hàng #" . $order->code;
        $vnp_OrderType = "billpayment";
        $vnp_Amount = $order->total_amount * 100; // VNPay yêu cầu nhân 100
        $vnp_Locale = "vn";
        $vnp_BankCode = $request->bank_code ?? "NCB";
        $vnp_IpAddr = request()->ip();
        $vnp_ExpireDate = date('YmdHis', strtotime('+15 minutes'));

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date("YmdHis"),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
            "vnp_ExpireDate" => $vnp_ExpireDate,
        ];

        if (!empty($vnp_BankCode)) {
            $inputData["vnp_BankCode"] = $vnp_BankCode;
        }

        ksort($inputData);
        $query = http_build_query($inputData);
        $hashdata = urldecode($query);
        $vnp_SecureHash = hash_hmac("sha512", $hashdata, $vnp_HashSecret);
        $query .= "&vnp_SecureHash=" . $vnp_SecureHash;
        $vnp_Url .= "?" . $query;

        return response()->json(["code" => "00", "message" => "success", "data" => $vnp_Url]);
    }

    // Xử lý thanh toán khi nhận hàng (COD)
    if ($request->payment_method === 'cod') {
        $order->update(['is_paid' => false, 'payment_id' => 2]); // 2 là ID phương thức COD trong bảng payments

        return response()->json(["message" => "Đơn hàng đã được tạo, thanh toán khi nhận hàng"]);
    }

    return response()->json(["message" => "Phương thức thanh toán không hợp lệ"], 400);
}

}
