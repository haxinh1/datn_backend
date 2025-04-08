<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User;
use App\Models\UserPointTransaction;

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

                 $reason = 'Hoàn tất đơn hàng #' . $order->id;
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
        // Kiểm tra nếu trạng thái đơn hàng là 9 
        if ($order->status_id == 9) {
    
         
            $user = User::where('id', $order->user_id)->first();
    
            if ($user) {
              
                $totalOrderValue = $order->total_product_amount;
                
                $totalPointsUsed = ($totalOrderValue * 2) / 100;
    
             
                $returnedProductValue = $order->order_returns->sum('price');  
                

                $productReturnRatio = $returnedProductValue / $totalOrderValue;
  
                $refundPoints = $productReturnRatio * $totalPointsUsed;
    
                // Cập nhật điểm người dùng
                $user->loyalty_points -= $refundPoints;
                $user->rank_points -= $refundPoints;
                $user->total_spent -= $order->total_product_amount;
    
            
                $reason = 'Trả điểm hoàn hàng #' . $order->id;
                UserPointTransaction::create([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'points' => $refundPoints,
                    'type' => 'add',
                    'reason' => $reason,
                ]);
    
                // Đảm bảo các giá trị không bị âm
                if ($user->loyalty_points < 0) {
                    $user->loyalty_points = 0;
                }
                if ($user->rank_points < 0) {
                    $user->rank_points = 0;
                }
                if ($user->total_spent < 0) {
                    $user->total_spent = 0;
                }
    
                // Cập nhật lại hạng người dùng
                $rank = 'Thành Viên';  // Cấp độ mặc định
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
