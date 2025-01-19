<?php

namespace App\Http\Controllers\admin;
use App\Http\Controllers\Controller;
use App\Models\OrderStatus;
use Illuminate\Http\Request;
use App\Http\Requests\StoreOrderStatusRequest;
use App\Http\Requests\UpdateOrderStatusRequest;

class OrderStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $statuses = OrderStatus::all();
        return response()->json($statuses, 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'ordinal' => 'nullable|integer',
        ]);

        try {
            $status = OrderStatus::create($validated);
            return response()->json($status, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $status = OrderStatus::find($id);

        if (!$status) {
            return response()->json(['message' => 'Trạng thái đơn hàng không tồn tại'], 404);
        }

        return response()->json($status, 200);

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OrderStatus $OrderStatus)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'ordinal' => 'nullable|integer',
        ]);

        $status = OrderStatus::find($id);

        if (!$status) {
            return response()->json(['message' => 'Trạng thái đơn hàng không tồn tại'], 404);
        }
        $status->update($validated);
        return response()->json($status, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $status = OrderStatus::find($id);

        if (!$status) {
            return response()->json(['message' => 'Trạng thái đơn hàng không tồn tại'], 404);
        }

        $status->delete();
        return response()->json(['message' => 'Trạng thái đơn hàng đã được xóa thành công'], 200);

    }
}
