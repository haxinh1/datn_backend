<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;

use App\Models\UserAddress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


class UserAddressController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $addresses = UserAddress::where('user_id', $user->id)->get();
        return response()->json($addresses, 200);
    }

    /**
     * Show the form for creating a new resource.fssfdgdgd
     */


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'address' => 'required|string',
            'detail_address' => 'nullable|string',
            'ProvinceID' => 'nullable|numeric',
            'DistrictID' => 'nullable|numeric',
            'WardCode' => 'nullable|string',
            'id_default' => 'boolean',
        ]);
    
        $user = Auth::user();

        $Address = UserAddress::where('user_id', $user->id)->exists();

        $idDefault = !$Address ? true : ($request->id_default ?? false);
    

        if ($idDefault) {
            UserAddress::where('user_id', $user->id)->update(['id_default' => false]);
        }
    
        // Tạo địa chỉ mới
        $address = UserAddress::create([
            'user_id' => $user->id,
            'address' => $request->address,
            'detail_address' => $request->detail_address,
            'ProvinceID' => $request->ProvinceID,
            'DistrictID' => $request->DistrictID,
            'WardCode' => $request->WardCode,
            'id_default' => $idDefault,
        ]);
    
        return response()->json($address, 201);
    }
    public function showidAdress($id)
    {
        $address = UserAddress::find($id);

        if (!$address) {
            return response()->json(['message' => 'Không tìm thấy địa chỉ.'], 404);
        }

        return response()->json($address, 200);
    }

    public function show($user_id)
    {
        $address = UserAddress::where('user_id', $user_id)->orderBy('id_default', 'desc')->get();
        // mặc định lên đầu

        if ($address->isEmpty()) {
            return response()->json(['message' => 'Không tìm thấy địa chỉ cho người dùng với ID này.'], 404);
        }

        return response()->json($address, 200);
    }

    public function update(Request $request,  $id)
    {
        $request->validate([
            'address' => 'sometimes|required|string',
            'detail_address' => 'sometimes|required|string',
            'ProvinceID' => 'sometimes|required|string',
            'DistrictID' => 'sometimes|required|string',
            'WardCode' => 'sometimes|required|string',
            'id_default' => 'boolean',
        ]);

        $user = Auth::user();
        $address = UserAddress::where('user_id', $user->id)->where('id', $id)->first();

        if ($request->id_default) {
            UserAddress::where('user_id', $user->id)->update(['id_default' => false]);
        }

        $address->update($request->only(['address',  'ProvinceID' , 'DistrictID' , 'WardCode' , 'detail_address' , 'id_default']));

        return response()->json($address, 200);
    }

    /**
     * Remove the specified resource from storage.egdgdg
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $address = UserAddress::where('user_id', $user->id)->where('id', $id)->first();

        if (!$address) {
            return response()->json(['message' => 'Không tìm thấy địa chỉ'], 404);
        }

        $address->delete();

        return response()->json(['message' => 'Địa chỉ đã được xóa'], 200);
    }
}
