<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderOrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderMail;

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

        $orderInfo = "Thanh toan don hang " . $order->code;
        $redirectUrl = route('momo.callback');
        $ipnUrl = $redirectUrl;
        $requestId = time() . "";
        $requestType = "payWithATM";
        $extraData = "";

        $rawHash = "accessKey={$accessKey}&amount={$amount}&extraData={$extraData}&ipnUrl={$ipnUrl}&orderId={$order->code}&orderInfo={$orderInfo}&partnerCode={$partnerCode}&redirectUrl={$redirectUrl}&requestId={$requestId}&requestType={$requestType}";
        $signature = hash_hmac("sha256", $rawHash, $secretKey);

        $data = [
            'partnerCode' => $partnerCode,
            'partnerName' => "Test",
            'storeId' => "MomoTestStore",
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $order->code,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature
        ];

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->timeout(30)->withOptions(['verify' => false])->post($endpoint, $data);

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
        Log::info('MoMo Callback Received:', $data);

        $order = Order::where('code', $data['orderId'])->first();
        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại'], 404);
        }

        // Bỏ qua phần kiểm tra chữ ký
        // Nếu không muốn kiểm tra chữ ký, xóa đoạn sau:

        // Kiểm tra chữ ký
        // $rawHash = "accessKey=klm05TvNBzhg7h7j&amount={$data['amount']}&message={$data['message']}&orderId={$data['orderId']}&orderInfo={$data['orderInfo']}&orderType={$data['orderType']}&partnerCode={$data['partnerCode']}&requestId={$data['requestId']}&responseTime={$data['responseTime']}&resultCode={$data['resultCode']}&transId={$data['transId']}";
        // $signature = hash_hmac("sha256", $rawHash, 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa');
        
        // if ($signature !== $data['signature']) {
        //     Log::error('Chữ ký MoMo không hợp lệ');
        //     return response()->json(['message' => 'Chữ ký không hợp lệ'], 400);
        // }

        if ($data['resultCode'] == 0) {
            // Thành công
            if ($order->status_id !== 1) {
                return redirect()->away("http://localhost:5173/thanks?" . http_build_query([
                    'success' => 'true',
                    'order_id' => $order->id,
                    'message' => 'Đơn hàng đã được xử lý trước đó'
                ]));
            }

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

                if ($order->user_id == null) {
                    Mail::to($order->email)->send(new OrderMail($order));
                }

                return redirect()->away("http://localhost:5173/thanks?" . http_build_query([
                    'success' => 'true',
                    'order_id' => $order->id,
                    'order_code' => $order->code,
                    'momo_OrderInfo' => "Thanh toan don hang " . $order->code,
                    'momo_Amount' => $data['amount'],
                    'momo_ResponseCode' => '0',
                    'momo_PaymentType' => 'ATM'
                ]));
            } catch (\Exception $e) {
                Log::error('Lỗi khi xử lý đơn hàng MoMo:', ['error' => $e->getMessage()]);
                return response()->json(['message' => 'Lỗi hệ thống'], 500);
            }
        } else {
            $order->update(['status_id' => 1]); // Thanh toán thất bại
            return redirect()->away("http://localhost:5173/thanks?" . http_build_query([
                'success' => 'false',
                'order_id' => $order->id,
                'momo_ResponseCode' => $data['resultCode']
            ]));
        }
    }
}
