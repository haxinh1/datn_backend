<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;

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
    public function store(StoreCartItemRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CartItem $CartItem)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CartItem $CartItem)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCartItemRequest $request, CartItem $CartItem)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CartItem $CartItem)
    {
        //
    }
}
