<?php

namespace App\Repositories\Statistics;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StatisticRepository
{
    protected $user;
    protected $order;
    protected $orderItems;
    protected $statusOrder;
    protected $product;

    public function __construct(User $user, Order $order, OrderItem $orderItems, OrderStatus $statusOrder, Product $product)
    {
        $this->user = $user;
        $this->order = $order;
        $this->orderItems = $orderItems;
        $this->statusOrder = $statusOrder;
        $this->product = $product;
    }

    public function topUserBought()
    {
        $datas = $this->orderItems->newQuery()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->select(
                'users.fullname',
                'users.gender',
                'users.phone_number',
                'users.rank',
                'users.avatar',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(orders.total_amount) as total_amount')
            )
            ->groupBy('users.id', 'users.fullname', 'users.gender', 'users.phone_number', 'users.rank', 'users.avatar')
            ->orderByDesc('total_quantity')
            ->where('orders.status_id', '=', 7)
            ->limit(10)
            ->get();

        return [
            'datas' => $datas
        ];
    }
    public function topProductBought($filter)
    {
        $datas = $this->orderItems->newQuery()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                'products.thumbnail',
                'products.sell_price',
                'products.sale_price',
                DB::raw('SUM(order_items.quantity) as quantity'),
                DB::raw('SUM(orders.total_amount) as total_amount')
            )
            ->where('orders.status_id', '=', 7)
            ->groupBy('products.id', 'products.name', 'products.thumbnail', 'products.sell_price', 'products.sale_price')
            ->orderByDesc('quantity')
            ->limit(10)
            ->get();
        return [
            'datas' => $datas
        ];
    }
    public function revenue()
    {
        $revenue = $this->order->selectRaw('created_at as date, SUM(total_amount) as revenue')
            ->where('status_id', 7)
            ->groupBy('date')
            ->get();
        return [
            'revenue' => $revenue,
        ];
    }
    public function orderStatistics()
    {
        $orderStats = $this->statusOrder
            ->leftJoin('orders', 'order_statuses.id', '=', 'orders.status_id')
            ->selectRaw('order_statuses.id as status_id, order_statuses.name as status_name, COUNT(orders.id) as total_orders')
            ->groupBy('order_statuses.id', 'order_statuses.name')
            ->get()
            ->toArray();
        return [
            'orderStats' => $orderStats
        ];
    }
    public function topBuyView()
    {
        $dataBuy = $this->orderItems
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.status_id', 7)
            ->selectRaw('order_items.product_id, products.name, products.thumbnail, SUM(order_items.quantity) as total_sold')
            ->groupBy('order_items.product_id', 'products.name', 'products.thumbnail')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();

        $dataView = $this->product->select('id', 'name', 'thumbnail', 'views')
            ->orderBy('views', 'DESC')
            ->limit(10)
            ->get();
        return [
            'dataBuy' => $dataBuy,
            'dataView' => $dataView,
        ];
    }
    public function topRevenueDays()
    {
        $topDays = $this->order
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total_revenue')
            ->where('status_id', 7)
            ->groupBy('date')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();
        return [
            'topDays' => $topDays
        ];
    }

    public function revenueStatistics()
    {
        $statistics = $this->order->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(CASE WHEN status_id = 8 THEN 1 ELSE 0 END) as cancelled_orders'),
            DB::raw('SUM(CASE WHEN status_id = 13 THEN 1 ELSE 0 END) as returned_orders')
        )
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();
        return [
            'statistics' => $statistics
        ];
    }
}
