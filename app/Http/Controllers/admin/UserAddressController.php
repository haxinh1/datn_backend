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
            'id_default' => 'boolean',
        ]);

        $user = Auth::user();

        if ($request->id_default) {
            UserAddress::where('user_id', $user->id)->update(['id_default' => false]);
        }

        $address = UserAddress::create([
            'user_id' => $user->id,
            'address' => $request->address,
            'detail_address' => $request->detail_address,
            'id_default' => $request->id_default ?? false,
        ]);

        return response()->json($address, 201);
    }
    public function show($user_id)
    {
        $address = UserAddress::where('user_id', $user_id)->get();

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
            'id_default' => 'boolean',
        ]);

        $user = Auth::user();
        $address = UserAddress::where('user_id', $user->id)->where('id', $id)->first();

        if ($request->id_default) {
            UserAddress::where('user_id', $user->id)->update(['id_default' => false]);
        }

        $address->update($request->only(['address', 'id_default']));

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
