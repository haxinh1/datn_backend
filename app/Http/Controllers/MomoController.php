<?php

namespace App\Http\Controllers;

use App\Mail\OrderMail;
use App\Models\Order;
use App\Models\OrderOrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MomoController extends Controller
{
    public function momo_payment(Request $request)
    {
        $endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";

        $partnerCode = 'MOMOBKUN20180529';
        $accessKey = 'klm05TvNBzhg7h7j';
        $secretKey = 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa';

        $amount = $request->input('total_momo');
        $orderId = $request->input('order_id');

        if (!$amount || !$orderId) {
            return response()->json(['message' => 'Thiếu total_momo hoặc order_id'], 400);
        }

        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        }

        if ($order->status_id != 1) {
            return response()->json(['message' => 'Đơn hàng không hợp lệ hoặc đã được xử lý'], 400);
        }

        $momoOrderId = $order->code . '-' . now()->timestamp;
        $order->update(['momo_order_id' => $momoOrderId]);

        $orderInfo = "Thanh toán đơn hàng " . $momoOrderId;
        $redirectUrl = route('momo.callback');
        $ipnUrl = $redirectUrl;
        $requestId = (string) time();
        $requestType = "payWithATM";
        $extraData = json_encode(['idOrder' => $order->id]);

        $rawHash = "accessKey={$accessKey}&amount={$amount}&extraData={$extraData}&ipnUrl={$ipnUrl}&orderId={$momoOrderId}&orderInfo={$orderInfo}&partnerCode={$partnerCode}&redirectUrl={$redirectUrl}&requestId={$requestId}&requestType={$requestType}";
        $signature = hash_hmac("sha256", $rawHash, $secretKey);

        $data = [
            'partnerCode' => $partnerCode,
            'partnerName' => "Test",
            'storeId' => "MomoTestStore",
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $momoOrderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature,
        ];

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->withOptions(['verify' => false, 'timeout' => 60, 'connect_timeout' => 60])
                ->post($endpoint, $data);

            if ($response->failed() || !isset($response['payUrl'])) {
                Log::error('MoMo Error:', ['body' => $response->body()]);
                return response()->json(['message' => 'Không thể tạo thanh toán MoMo'], 500);
            }

            return response()->json(['payment_url' => $response['payUrl']], 200);
        } catch (\Exception $e) {
            Log::error('MoMo Exception:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Lỗi kết nối đến MoMo'], 500);
        }
    }

    public function callback(Request $request)
    {
        $data = $request->all();
        Log::info('📥 MoMo Callback Received:', $data);
    
        // Lấy thông tin idOrder từ extraData
        $extraData = json_decode($data['extraData'] ?? '{}', true);
        $idOrder = $extraData['idOrder'] ?? null;
    
        if (!$idOrder) {
            Log::error('❌ MoMo Callback thiếu idOrder');
            return response()->json(['message' => 'Thiếu thông tin đơn hàng'], 400);
        }
    
        $order = Order::find($idOrder);
    
        if (!$order) {
            Log::error("❌ Không tìm thấy đơn hàng với ID: {$idOrder}");
            return response()->json(['message' => 'Đơn hàng không tồn tại'], 404);
        }
    
        if ((int) $data['resultCode'] === 0) {
            Log::info("✅ Kết quả thanh toán thành công cho đơn hàng {$order->code}, status hiện tại: {$order->status_id}");
    
            if ($order->status_id == 1) {
                try {
                    $order->update(['status_id' => 2]);
    
                    foreach ($order->orderItems as $item) {
                        if ($item->product_variant_id) {
                            ProductVariant::where('id', $item->product_variant_id)->decrement('stock', $item->quantity);
                        } else {
                            Product::where('id', $item->product_id)->decrement('stock', $item->quantity);
                        }
                    }
    
                    OrderOrderStatus::create([
                        'order_id' => $order->id,
                        'order_status_id' => 2,
                        'note' => 'Thanh toán MoMo thành công.'
                    ]);
    
                    if (is_null($order->user_id)) {
                        Mail::to($order->email)->send(new OrderMail($order));
                    }
    
                    Log::info("🎉 Đơn hàng {$order->code} đã được xử lý thành công.");
    
                } catch (\Exception $e) {
                    Log::error('❌ Lỗi khi xử lý đơn hàng MoMo:', ['error' => $e->getMessage()]);
                    return response()->json(['message' => 'Lỗi hệ thống'], 500);
                }
            } else {
                Log::warning("⚠️ Đơn hàng {$order->code} đã được xử lý trước đó, bỏ qua xử lý lại.");
            }
    
            return redirect()->away("http://localhost:5173/thanks?" . http_build_query([
                'success' => 'true',
                'order_id' => $order->id,
                'order_code' => $order->code,
                'momo_OrderInfo' => "Thanh toán đơn hàng " . $order->code,
                'momo_Amount' => $data['amount'] ?? '',
                'momo_ResponseCode' => '0',
                'momo_PaymentType' => 'ATM'
            ]));
        }
    
        Log::warning("❌ Thanh toán thất bại cho đơn hàng {$order->code} - resultCode: {$data['resultCode']}");
        return redirect()->away("http://localhost:5173/thanks?" . http_build_query([
            'success' => 'false',
            'order_id' => $order->id,
            'momo_ResponseCode' => $data['resultCode']
        ]));
    }
    
}
