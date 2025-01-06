<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:customer');
    }

    /**
     * Display a listing of the orders for the current user.
     */
    public function index()
    {
        // Retrieve orders for the currently authenticated user, including soft-deleted ones
        $orders = Order::where('user_id', Auth::id())->withTrashed()->paginate(8);

        return view('customer.orders.index', compact('orders'));
    }

    /**
     * Store a newly created order in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'total_price' => 'required|numeric',
            'status' => 'required|string',
            'shipping_location' => 'required|string',
        ]);

        // Create the new order for the current user
        $order = Order::create([
            'user_id' => Auth::id(), // Automatically assign the current user's ID
            'total_price' => $request->total_price,
            'status' => $request->status,
            'shipping_location' => $request->shipping_location,
            'coupon_id' => $request->coupon_id ?? null, // Optional field
        ]);

        return response()->json(['success' => true, 'order' => $order]);
    }

    /**
     * Display the specified order for the current user.
     */
    public function show($id)
    {
        // Find the order for the current user
        $order = Order::where('user_id', Auth::id())->withTrashed()->findOrFail($id);

        return response()->json(['order' => $order]);
    }

    /**
     * Update the specified order in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'total_price' => 'required|numeric',
            'status' => 'required|string',
            'shipping_location' => 'required|string',
        ]);

        // Find the order for the current user and update it
        $order = Order::where('user_id', Auth::id())->findOrFail($id);
        $order->total_price = $request->total_price;
        $order->status = $request->status;
        $order->shipping_location = $request->shipping_location;
        $order->coupon_id = $request->coupon_id ?? null; // Optional field
        $order->save();

        return response()->json(['success' => true, 'order' => $order]);
    }

    /**
     * Soft delete the specified order for the current user.
     */
    public function softDelete($id)
    {
        $order = Order::where('user_id', Auth::id())->findOrFail($id);
        $order->delete(); // Soft delete

        return response()->json(['success' => true]);
    }

    /**
     * Restore the specified soft-deleted order for the current user.
     */
    public function restore($id)
    {
        $order = Order::where('user_id', Auth::id())->withTrashed()->findOrFail($id);
        $order->restore(); // Restore soft-deleted order

        return response()->json(['success' => true]);
    }

    /**
     * Permanently delete the specified order for the current user.
     */
    public function destroy($id)
    {
        $order = Order::where('user_id', Auth::id())->withTrashed()->findOrFail($id);
        $order->forceDelete(); // Permanently delete

        return response()->json(['success' => true]);
    }
}