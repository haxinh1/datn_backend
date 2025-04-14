<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User;
use App\Models\UserPointTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;
use App\Models\BannedHistory;

class OrderObserver
{

    /**
     * Handle the Order "saved" event.
     *
     * @param \App\Models\Order $order
     * @return void
     */

    public function saved(Order $order)
    {

        if ($order->status_id == 7) {

            $point = ($order->total_product_amount * 2) / 100;

            $rankPoints = ($order->total_product_amount * 2) / 100;


            // Lấy user
            $user = User::where('id', $order->user_id)->first();

            if ($user) {
                // Cộng điểm mới vào điểm hiện tại
                $user->loyalty_points += $point;

                $user->rank_points += $rankPoints;

                // Cộng thêm số tiền đơn hàng vào tổng đã chi
                $user->total_spent += $order->total_product_amount;
                Log::info('diem: ' . $point);
                $reason = 'Hoàn tất đơn hàng : ' . $order->code;
                UserPointTransaction::create([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'points' => $point,
                    'type' => 'add',
                    'reason' => $reason,
                ]);

                // Cập nhật hạng theo tổng đã chi mới
                $rankPoints = $user->rank_points;
                $rank = 'Thành Viên';

                if ($rankPoints >= 400000) {
                    $rank = 'Kim Cương';
                } elseif ($rankPoints >= 200000) {
                    $rank = 'Vàng';
                } elseif ($rankPoints >= 100000) {
                    $rank = 'Bạc';
                } elseif ($rankPoints >= 40000) {
                    $rank = 'Đồng';
                }

                $user->rank = $rank;
                $user->save();
            }
        }
    }

    public function updated(Order $order)
    {


        $old = $order->getOriginal('status_id');
        $new = $order->status_id;
        Log::info("Status cũ: $old -> mới: $new");
        $user = User::where('id', $order->user_id)->first();

        if ($order->status_id == 10) {


            Log::info('User ID: ' . $user->id);

            if ($user) {


                $totalPointsUsed = $order->used_points;
                Log::info('Điểm sử dụng: ' . $totalPointsUsed);

                $totalOrder = $order->total_product_amount;
                Log::info('Tổng hóa đơn: ' . $totalOrder);

                $order->load('order_returns');
                $returnedProductValue = $order->order_returns->sum('sell_price');
                Log::info('Sp: ' . $returnedProductValue);


                $productReturnRatio = $returnedProductValue / $totalOrder;
                Log::info('Sp / Tổng hóa đơn : ' . $productReturnRatio);

                $refundPoints = $productReturnRatio * $totalPointsUsed;
                Log::info('Điểm hoàn trả: ' . $refundPoints);

                $user->total_spent -= $returnedProductValue;


                $pointsDeducted = ($returnedProductValue * 2) / 100;
                $user->loyalty_points -= $pointsDeducted;
                $user->rank_points -= $pointsDeducted;


                $reason = 'Tính lại hóa đơn khi trả hàng : ' . $order->code;
                UserPointTransaction::create([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'points' => -$pointsDeducted,
                    'type' => 'subtract',
                    'reason' => $reason,
                ]);


                $user->loyalty_points += $refundPoints;

                if ($totalPointsUsed > 0) {

                    log::info('Điểm hoàn trả status == 10: ' . $refundPoints);
                    $reason = 'Trả điểm hoàn hàng : ' . $order->code;
                    UserPointTransaction::create([
                        'user_id' => $user->id,
                        'order_id' => $order->id,
                        'points' => $refundPoints,
                        'type' => 'add',
                        'reason' => $reason,
                    ]);
                }



                if ($user->loyalty_points < 0) {
                    $user->loyalty_points = 0;
                }
                if ($user->rank_points < 0) {
                    $user->rank_points = 0;
                }
                if ($user->total_spent < 0) {
                    $user->total_spent = 0;
                }


                $rank = 'Thành Viên';
                if ($user->rank_points >= 400000) {
                    $rank = 'Kim Cương';
                } elseif ($user->rank_points >= 200000) {
                    $rank = 'Vàng';
                } elseif ($user->rank_points >= 100000) {
                    $rank = 'Bạc';
                } elseif ($user->rank_points >= 40000) {
                    $rank = 'Đồng';
                }

                $user->rank = $rank;
                $user->save();
            }
        }

        if ($user) {
            if ($order->status_id == 8) {
                if ($order->used_points > 0) {
                    Log::info('User ID: ' . $user->id);
                    $totalPointsUsed = $order->used_points;
                    Log::info('Điểm sử dụng : ' . $totalPointsUsed);
                    $user->loyalty_points += $totalPointsUsed;


                    // $refundPoints = ($order->total_product_amount * 2) / 100;
                    // Log::info('Điểm hoàn trả status == 8: ' . $refundPoints);
                    // $user->loyalty_points -= $refundPoints;
                    // $user->rank_points -= $refundPoints;

                    // $user->total_spent -= $order->total_product_amount;
                    // Log::info('Tổng hóa đơn status == 8: ' . $order->total_product_amount);

                    $reason = 'Hoàn trả điểm hủy hàng : ' . $order->code;
                    UserPointTransaction::create([
                        'user_id' => $user->id,
                        'order_id' => $order->id,
                        'points' => $totalPointsUsed,
                        'type' => 'add',
                        'reason' => $reason,
                    ]);

                    $user->save();
                }

                try {
                    $today = Carbon::today();

                    $cancelledOrdersToday = \App\Models\Order::where('user_id', $user->id)
                        ->where('status_id', 8)
                        ->whereDate('updated_at', $today)
                        ->count();

                    if ($cancelledOrdersToday >= 5) {

                        $latestBan = BannedHistory::where('user_id', $user->id)
                        ->latest('banned_at')
                        ->first();

                        if (!$latestBan || ($latestBan->unbanned_at && $user->status === 'active')) {
                            BannedHistory::create([
                                'user_id' => $user->id,
                                'banned_by' => auth()->check() ? auth()->id() : 1,
                                'reason' => 'Hủy quá 5 đơn trong 1 ngày',
                                'banned_at' => Carbon::now(),
                                'ban_expires_at' => Carbon::now()->addDay(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            $user->status = 'banned';
                            $user->save();


                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Lỗi khi hủy đơn: ' . $e->getMessage());
                }
            }
        }
    }

    public function created(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "updated" event.
     */


    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
