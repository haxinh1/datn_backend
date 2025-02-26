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
     * Show the form for creating a new resource.
     */
  

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'address' => 'required|string',
            'id_default' => 'boolean',
        ]);

        $user = Auth::user();
       
        if ($request->id_default) {
            UserAddress::where('user_id', $user->id)->update(['id_default' => false]);
        }

        $address = UserAddress::create([
            'user_id' => $user->id,
            'address' => $request->address,
            'id_default' => $request->id_default ?? false,
        ]);

        return response()->json($address, 201);
    }
   
    public function update(Request $request,  $id)
    {
        $request->validate([
            'address' => 'sometimes|required|string',
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
     * Remove the specified resource from storage.
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
