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
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderMail;

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

        // Nếu giao dịch thành công (`vnp_ResponseCode == 00`)
        if ($inputData['vnp_ResponseCode'] == 00) {
            $order = Order::find($inputData['vnp_TxnRef']);
          
       
      
            // Kiểm tra nếu không tìm thấy đơn hàng
            if (!$order) {
                Log::error("Order not found for TxnRef: {$inputData['vnp_TxnRef']}");
                return response()->json(['message' => 'Đơn hàng không tồn tại'], 404);
            }

            // Thêm log để kiểm tra trạng thái đơn hàng trước khi cập nhật
            Log::info("Order ID: {$order->id}, Current Status: {$order->status_id}");

            // Kiểm tra nếu trạng thái của đơn hàng chưa phải là đã thanh toán (status_id = 1)
            if ($order->status_id !== 1) {
                Log::info("Order status is already updated. Current status: {$order->status_id}");
                return response()->json(['message' => 'Đơn hàng đã được cập nhật trạng thái'], 200);
            }

            try {
                // Cập nhật trạng thái đơn hàng thành 2 (Đã thanh toán)
                $order->update(['status_id' => 2]);


                // Log sau khi cập nhật
                Log::info("Order ID: {$order->id}, Updated Status: {$order->status_id}");

                // Trừ stock cho các sản phẩm trong đơn hàng
                foreach ($order->orderItems as $item) {
                    if ($item->product_variant_id) {
                        // Nếu có variant, trừ stock từ product_variant
                        ProductVariant::where('id', $item->product_variant_id)
                            ->decrement('stock', $item->quantity);
                        Log::info("Decreased stock for ProductVariant ID: {$item->product_variant_id}, Quantity: {$item->quantity}");
                    } else {
                        // Nếu không có variant, trừ stock từ product
                        Product::where('id', $item->product_id)
                            ->decrement('stock', $item->quantity);
                        Log::info("Decreased stock for Product ID: {$item->product_id}, Quantity: {$item->quantity}");
                    }
                }

                // Lưu lịch sử trạng thái đơn hàng
                OrderOrderStatus::create([
                    'order_id' => $order->id,
                    'order_status_id' => 2, // Trạng thái "Đã thanh toán"
                    'note' => 'Thanh toán VNPay thành công.',
                ]);
             if($order->user_id == null){
                Mail::to($order->email)->send(new OrderMail($order));   
             }
                return redirect()->away(
                    'http://localhost:5173/thanks?' . http_build_query([
                        'success' => 'true',
                        'order_id' => $order->id,
                        'vnp_OrderInfo' => 'Thanh toan don hang ' . $order->code,
                        'vnp_Amount' => $order->total_amount * 100,
                        'vnp_ResponseCode' => '00',
                        'vnp_CardType' => 'ATM'
                    ])
                );
            } catch (\Exception $e) {
                Log::error("Error updating order status for Order ID: {$order->id}, Error: " . $e->getMessage());
                return response()->json(['message' => 'Lỗi hệ thống khi cập nhật trạng thái'], 500);
            }
        } else {
            return redirect()->away(
                'http://localhost:5173/thanks?' . http_build_query([
                    'success' => 'false',
                    'order_id' => $order->id ?? '',
                    'vnp_ResponseCode' => $inputData['vnp_ResponseCode'] ?? 'unknown'
                ])
            );
        }
    }
}
