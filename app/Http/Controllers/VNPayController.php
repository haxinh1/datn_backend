<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class VNPayController extends Controller
{
    /**
     * Xử lý thanh toán VNPay
     */
    public function createPayment(Request $request)
    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');

        // Kiểm tra đầu vào
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|in:vnpay,cod'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Lỗi dữ liệu đầu vào',
                'errors' => $validator->errors()
            ], 400);
        }

        $order = Order::findOrFail($request->order_id);

        // Kiểm tra trạng thái đơn hàng
        if (!in_array($order->status_id, [1, 2])) {
            return response()->json([
                'message' => 'Đơn hàng không hợp lệ để thanh toán',
                'order_id' => $order->id,
                'status_id' => $order->status_id
            ], 400);
        }

        // Kiểm tra tổng tiền hợp lệ (> 5,000 VND)
        if ($order->total_amount < 5000) {
            return response()->json(['message' => 'Số tiền giao dịch không hợp lệ. Phải lớn hơn 5,000 VND'], 400);
        }

        // Xử lý thanh toán VNPay
        if ($request->payment_method === 'vnpay') {
            $vnp_Url = env('VNP_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
            $vnp_Returnurl = env('VNP_RETURN_URL', 'http://127.0.0.1:8000/api/payments/vnpay/return');
            $vnp_TmnCode = env('VNP_TMN_CODE', 'FOQ8B80U');
            $vnp_HashSecret = env('VNP_HASH_SECRET', 'E2XRZHWXVAC2XGJXO1M51MRRQXNDN3U8');

            $vnp_TxnRef = (string) $order->id;
            $vnp_OrderInfo = "Thanh toán đơn hàng #" . $order->code;
            $vnp_OrderType = "billpayment";
            $vnp_Amount = (int) $order->total_amount * 100;
            $vnp_Locale = "vn";
            $vnp_BankCode = $request->bank_code ?? "NCB";
            $vnp_IpAddr = request()->ip();
            $vnp_ExpireDate = date('YmdHis', time() + 1800);

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
            $query = [];
            foreach ($inputData as $key => $value) {
                if ($value !== null && $value !== '') {
                    $query[] = urlencode($key) . "=" . urlencode($value);
                }
            }
            $queryString = implode("&", $query);
            $vnp_SecureHash = hash_hmac("sha512", $queryString, $vnp_HashSecret);

            // Log dữ liệu gửi đi để kiểm tra
            Log::info("VNPay Request: ", $inputData);
            Log::info("Generated Secure Hash: " . $vnp_SecureHash);

            $queryString .= "&vnp_SecureHash=" . $vnp_SecureHash;
            $vnp_Url .= "?" . $queryString;

            return response()->json(["message" => "Chuyển hướng đến VNPay", "payment_url" => $vnp_Url], 200);
        }

        return response()->json(["message" => "Phương thức thanh toán không hợp lệ"], 400);
    }
}
