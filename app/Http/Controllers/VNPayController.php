<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderOrderStatus;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
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

        if ($order->total_amount < 5000) {
            return response()->json(['message' => 'Số tiền giao dịch không hợp lệ. Phải lớn hơn 5,000 VND'], 400);
        }

        $payment = Payment::where('name', 'VNPay')->first();
        if (!$payment) {
            return response()->json(['message' => 'Phương thức thanh toán VNPay không tồn tại'], 400);
        }

        $order->update([
            'payment_id' => $payment->id
        ]);

        $vnp_Url = config('services.vnpay.url');
        $vnp_Returnurl = config('services.vnpay.return_url');
        $vnp_TmnCode = config('services.vnpay.tmn_code');
        $vnp_HashSecret = config('services.vnpay.hash_secret');

        if (!$vnp_HashSecret) {
            Log::error("VNPay Hash Secret is NULL. Kiểm tra config/services.php!");
            return response()->json(['message' => 'Lỗi hệ thống: Hash Secret chưa được cấu hình'], 500);
        }

        // 🔹 Xử lý vnp_OrderInfo
        $vnp_OrderInfo = "Thanh toan don hang " . preg_replace('/[^a-zA-Z0-9 ]/', '', $order->code);

        // 🔹 Xây dựng dữ liệu gửi đến VNPay
        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => (int) $order->total_amount * 100,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date("YmdHis"),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => request()->ip(),
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => $vnp_OrderInfo, // Fix lỗi encoding Unicode
            "vnp_OrderType" => "billpayment",
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => (string) $order->id,
            "vnp_ExpireDate" => date("YmdHis", time() + 1800),
        ];

        if ($request->has('bank_code')) {
            $inputData["vnp_BankCode"] = $request->bank_code;
        }

        // Sắp xếp mảng & tạo query string
        ksort($inputData);
        $queryString = "";
        foreach ($inputData as $key => $value) {
            if ($value !== null && $value !== '') {
                $queryString .= $key . "=" . urlencode($value) . "&"; // Dùng `urlencode()`
            }
        }
        $queryString = rtrim($queryString, "&");

        // Tạo Secure Hash
        $vnp_SecureHash = hash_hmac("sha512", $queryString, $vnp_HashSecret);

        Log::info("VNPay Hash Data (Create Payment):", [$queryString]);
        Log::info("Generated Secure Hash:", [$vnp_SecureHash]);

        // Gửi request sang VNPay
        $queryString .= "&vnp_SecureHash=" . $vnp_SecureHash;
        $vnp_Url .= "?" . $queryString;

        return response()->json([
            "message" => "Chuyển hướng đến VNPay",
            "payment_url" => $vnp_Url
        ], 200);
    }





    /**
     * Xử lý phản hồi từ VNPay
     */
    public function paymentReturn(Request $request)
    {
        $inputData = $request->all();
        Log::info("VNPay Response Data:", $inputData);

        // Kiểm tra nếu thiếu `vnp_SecureHash`
        if (!isset($inputData['vnp_SecureHash'])) {
            return response()->json([
                'message' => 'Thiếu mã bảo mật VNPay',
                'data' => $inputData
            ], 400);
        }

        // Lấy Secure Hash từ VNPay
        $secureHash = trim($inputData['vnp_SecureHash']);
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']); // Loại bỏ để tính toán chính xác

        // Định dạng lại giá trị `vnp_Amount` (VNPay gửi về string nhưng cần convert integer)
        if (isset($inputData['vnp_Amount'])) {
            $inputData['vnp_Amount'] = strval(intval($inputData['vnp_Amount']));
        }

        // Loại bỏ khoảng trắng đầu cuối tất cả giá trị
        array_walk($inputData, function (&$value, $key) {
            $value = trim(strval($value));
        });

        // Sắp xếp mảng dữ liệu đúng chuẩn VNPay (A-Z)
        ksort($inputData);

        // Tạo chuỗi hash đúng chuẩn
        $hashData = [];
        foreach ($inputData as $key => $value) {
            $hashData[] = urlencode($key) . "=" . urlencode($value);
        }
        $hashData = implode("&", $hashData);

        // Lấy `hash_secret` từ config
        $vnp_HashSecret = config('services.vnpay.hash_secret');
        if (!$vnp_HashSecret) {
            Log::error("VNPay Hash Secret is NULL. Kiểm tra config/services.php!");
            return response()->json(['message' => 'Lỗi hệ thống: Hash Secret chưa được cấu hình'], 500);
        }

        // Tạo chữ ký số SHA512
        $computedHash = hash_hmac("sha512", $hashData, $vnp_HashSecret);

        // Log kiểm tra
        Log::info("VNPay Hash Data (Return):", [$hashData]);
        Log::info("Computed Secure Hash:", [$computedHash]);
        Log::info("Secure Hash từ VNPay:", [$secureHash]);

        // So sánh chữ ký số (KHÔNG phân biệt hoa/thường)
        if (strcasecmp($computedHash, $secureHash) !== 0) {
            Log::error("Xác thực thanh toán thất bại", [
                'computed_hash' => $computedHash,
                'secure_hash' => $secureHash,
                'input_data' => $inputData
            ]);
            return response()->json([
                'message' => 'Xác thực thanh toán thất bại',
                'computed_hash' => $computedHash,
                'secure_hash' => $secureHash,
                'input_data' => $inputData
            ], 400);
        }

        // 🔹 Nếu giao dịch thành công (`vnp_ResponseCode == 00`)
        if ($inputData['vnp_ResponseCode'] == '00') {
            $order = Order::findOrFail($inputData['vnp_TxnRef']);
            // Chỉ trừ stock nếu chưa trừ trước đó
            if ($order->status_id != 2) {  // Nếu đơn hàng chưa được thanh toán
                foreach ($order->orderItems as $item) {
                    if ($item->product_variant_id) {
                        ProductVariant::where('id', $item->product_variant_id)->decrement('stock', $item->quantity);
                    } else {
                        Product::where('id', $item->product_id)->decrement('stock', $item->quantity);
                    }
                }

                // Cập nhật trạng thái đơn hàng đã thanh toán
                $order->update([
                    'status_id' => 2,
                    'payment_id' => Payment::where('name', 'VNPay')->value('id')
                ]);
            }


            // Cập nhật trạng thái mới vào `order_order_statuses`
            OrderOrderStatus::create([
                'order_id' => $order->id,
                'order_status_id' => 2, // Trạng thái "Đã thanh toán"
                'note' => 'Thanh toán VNPay thành công.',
            ]);

            return response()->json([
                'message' => 'Thanh toán thành công',
                'order' => $order
            ], 200);
        } else {
            return response()->json([
                'message' => 'Thanh toán không thành công'
            ], 400);
        }
    }
}
