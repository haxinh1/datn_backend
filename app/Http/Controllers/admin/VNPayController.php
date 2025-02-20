<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class VNPayController extends Controller
{
    public function createPayment(Request $request)
    {
        $order = Order::findOrFail($request->order_id);
        $vnp_Url = config('services.vnpay.url');
        $vnp_Returnurl = config('services.vnpay.return_url');

        $vnp_TxnRef = $order->code;
        $vnp_Amount = $order->total_amount * 100;

        $query = http_build_query([
            "vnp_Version" => "2.1.0",
            "vnp_Amount" => $vnp_Amount,
            "vnp_TxnRef" => $vnp_TxnRef,
            "vnp_ReturnUrl" => $vnp_Returnurl
        ]);

        return redirect("$vnp_Url?$query");
    }

    public function paymentReturn(Request $request)
    {
        $order = Order::where('code', $request->vnp_TxnRef)->first();
        if ($request->vnp_ResponseCode == "00") {
            $order->update(['is_paid' => true]);
            return response()->json(['message' => 'Thanh toán thành công']);
        }

        return response()->json(['message' => 'Thanh toán thất bại']);
    }
}
