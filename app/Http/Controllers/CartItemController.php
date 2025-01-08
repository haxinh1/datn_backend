<?php

namespace App\Http\Controllers;

use App\Models\Cart_item;
use App\Http\Requests\StoreCart_itemRequest;
use App\Http\Requests\UpdateCart_itemRequest;

class CartItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(StoreCart_itemRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Cart_item $cart_item)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cart_item $cart_item)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCart_itemRequest $request, Cart_item $cart_item)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cart_item $cart_item)
    {
        //
    }
}
