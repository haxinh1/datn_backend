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

            $point = ($order->total_amount * 2) / 100;

            $rankPoints = ($order->total_amount * 2) / 100;

        
            // Lấy user
            $user = User::where('id', $order->user_id)->first();

            if ($user) {
                // Cộng điểm mới vào điểm hiện tại
                $user->loyalty_points += $point;

                $user->rank_points += $rankPoints;

                // Cộng thêm số tiền đơn hàng vào tổng đã chi
                $user->total_spent += $order->total_amount;

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

        if ($order->status_id == 9) {

            $user = User::where('id', $order->user_id)->first();

            if ($user) {

                $point = ($order->total_amount * 2) / 100;


                $user->loyalty_points -= $point;
                $user->rank_points -= $point;
                $user->total_spent -= $order->total_amount;


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
