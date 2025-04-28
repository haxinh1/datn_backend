<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\Carbon;

class ClearExpiredSale extends Command
{
    protected $signature = 'clear:expired-sale';
    protected $description = 'Xóa giá khuyến mãi của sản phẩm và biến thể đã hết hạn sale.';

    public function handle()
    {
        $now = Carbon::now();

        $expiredProducts = Product::whereNotNull('sale_price_end_at')
            ->where('sale_price_end_at', '<', $now)
            ->update([
                'sale_price' => null,
                'sale_price_start_at' => null,
                'sale_price_end_at' => null,
            ]);

        $expiredVariants = ProductVariant::whereNotNull('sale_price_end_at')
            ->where('sale_price_end_at', '<', $now)
            ->update([
                'sale_price' => null,
                'sale_price_start_at' => null,
                'sale_price_end_at' => null,
            ]);

        if ($expiredProducts || $expiredVariants) {
            $this->info("✅ Đã xóa giá sale cho sản phẩm và biến thể hết hạn!");
        } else {
            $this->info("🔔 Không có sản phẩm hoặc biến thể nào cần xóa giá sale hết hạn.");
        }
    }
}
