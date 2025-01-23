<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\AttributeValue;
use App\Http\Requests\StoreAttributeValueRequest;
use App\Http\Requests\UpdateAttributeValueRequest;
use App\Models\Attribute;
use Illuminate\Http\Request;

class AttributeValueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // try {
        //     $attributeValues = AttributeValue::with('attribute')->get();
        //     return response()->json([
        //         'success' => true,
        //         'data' => $attributeValues,
        //     ], 200);
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
        //     ], 500);
        // }
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
    public function store(Request $request)
    {
        $request->validate([
            'attribute_id' => 'required|exists:attributes,id',
            'value' => 'required|string|max:255',
        ]);

        try {
            $data = $request->only(['attribute_id', 'value']);
            $data['is_active'] = 1;

            $attributeValue = AttributeValue::create($data);
            return response()->json([
                'success' => true, 
                'message' => 'Thêm giá trị thuộc tính thành công!',
                 'data' => $attributeValue]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra khi thêm giá trị thuộc tính. Vui lòng thử lại sau!']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $attributeValues = AttributeValue::where('attribute_id', $id)->get();
        return response()->json($attributeValues);
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
