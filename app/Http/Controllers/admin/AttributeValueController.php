<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\AttributeValue;
use App\Http\Requests\StoreAttributeValueRequest;
use App\Http\Requests\UpdateAttributeValueRequest;
use App\Models\Attribute;

class AttributeValueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $attributeValues = AttributeValue::with('attribute')->get();
            return response()->json([
                'success' => true,
                'data' => $attributeValues,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAttributeValueRequest $request)
    {
        try {
            // Lấy dữ liệu từ form
            $attributes = $request->input('attributes');

            foreach ($attributes as $attribute) {
                $attributeId = $attribute['id'];
                $values = $attribute['values'] ?? [];

                foreach ($values as $value) {
                    if (!empty($value)) {
                        // Kiểm tra xem giá trị đã tồn tại chưa
                        $exists = AttributeValue::where('attribute_id', $attributeId)
                            ->where('value', $value)
                            ->exists();

                        if (!$exists) {
                            // Tạo mới giá trị thuộc tính
                            AttributeValue::create([
                                'attribute_id' => $attributeId,
                                'value' => $value,
                                'is_active' => 1,
                            ]);
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Thêm thành công giá trị thuộc tính.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(AttributeValue $AttributeValue)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AttributeValue $AttributeValue)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAttributeValueRequest $request, AttributeValue $AttributeValue)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AttributeValue $AttributeValue)
    {
        //
    }
}
