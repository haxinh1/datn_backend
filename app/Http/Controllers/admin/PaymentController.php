<?php

namespace App\Http\Controllers\admin;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

use App\Http\Requests\StorePaymentRequest;


use App\Http\Requests\UpdatePaymentRequest;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $payments = Payment::all();
        return response()->json($payments);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //adaadad
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    // Kiểm tra quyền (chỉ admin mới có thể tạo)
    if (!auth()->user() || !auth()->user()->is_admin) {
        return response()->json(['message' => 'Bạn không có quyền tạo phương thức thanh toán'], 403);
    }

    $validated = $request->validate([
        'parent_id' => 'nullable|integer|exists:payments,id',
        'name' => 'required|string|max:255',
        'logo' => 'nullable|string|max:255',
        'is_active' => 'required|boolean',
    ]);

    $payment = Payment::create($validated);

    return response()->json([
        'message' => 'Phương thức thanh toán đã được tạo thành công.',
        'data' => $payment,
    ], 201);
}


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json(['message' => 'Phương thức thanh toán không tồn tại'], 404);
        }

        return response()->json($payment);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payment $payment)
    {
        //
    }

    /**ư
     * Update the specified resource in storage.
     */

    public function update(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);
    
        // Kiểm tra quyền (chỉ admin mới có thể cập nhật)
        if (!auth()->user() || !auth()->user()->is_admin) {
            return response()->json(['message' => 'Bạn không có quyền cập nhật phương thức thanh toán'], 403);
        }
    
        $validated = $request->validate([
            'parent_id' => 'nullable|integer|exists:payments,id',
            'name' => 'required|string|max:255',
            'logo' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ]);
    
        $payment->update($validated);
    
        return response()->json([
            'message' => 'Phương thức thanh toán đã được cập nhật thành công.',
            'data' => $payment,
        ]);
    }
    

    
}
