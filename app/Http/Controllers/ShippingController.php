<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class ShippingController extends Controller
{
    public function calculateShippingFee($address)
    {
        // Lấy Access Token và Shop ID từ file .env
        $accessToken = env('GHN_ACCESS_TOKEN'); // Lấy từ .env
        $shopId = env('GHN_SHOP_ID'); // Lấy từ .env

        // Thông tin khác cần thiết cho API GHN
        $recipientDistrict = $address['district_id']; // ID Quận/Huyện
        $recipientWard = $address['ward_id']; // ID Phường/Xã
        $weight = 1000; // Trọng lượng gói hàng (tính bằng gram)
        $length = 20; // Chiều dài gói hàng (tính bằng cm)
        $width = 20;  // Chiều rộng gói hàng (tính bằng cm)
        $height = 10; // Chiều cao gói hàng (tính bằng cm)

        // URL API GHN
        $url = 'https://online-gateway.ghn.vn/shiip/public-api/v2/shipping-order/fee';

        // Gửi yêu cầu tính phí vận chuyển
        $response = Http::withHeaders([
            'Token' => $accessToken, // Sử dụng Access Token từ .env
        ])->post($url, [
            'from_district' => 1, // ID Quận/Huyện gửi hàng (thường là kho của bạn)
            'to_district' => $recipientDistrict, // ID Quận/Huyện nhận hàng
            'to_ward' => $recipientWard, // ID Phường/Xã nhận hàng
            'weight' => $weight,
            'length' => $length,
            'width' => $width,
            'height' => $height,
            'service_id' => 53329, // Mã dịch vụ giao hàng (thay bằng dịch vụ bạn chọn)
        ]);

        if ($response->successful()) {
            return $response->json()['data']['service_fee']; // Trả về phí giao hàng
        } else {
            return 0; // Nếu có lỗi thì trả về 0 (không tính phí)
        }
    }
}
