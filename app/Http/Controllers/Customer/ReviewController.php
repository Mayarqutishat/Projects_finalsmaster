<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:customer');
    }

    public function index()
    {
        // Fetch reviews for the authenticated user including soft deleted ones
        $reviews = Review::withTrashed()
                         ->where('user_id', auth()->id())
                         ->paginate(2);
        return view('customer.reviews.index', compact('reviews'));
    }

    public function store(Request $request)
    {
        // Validate the input data
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
        ]);

        // Create a new review with validated data
        $review = new Review();
        $review->user_id = auth()->id(); // Set the user_id to the current authenticated user
        $review->product_id = $request->input('product_id');
        $review->rating = $request->input('rating');
        $review->comment = $request->input('comment');
        $review->save();

        return redirect()->route('customer.reviews.index')->with('success', 'Review created successfully');
    }

    public function edit(string $id)
    {
        $review = Review::where('user_id', auth()->id())
                        ->find($id);

        if (!$review) {
            return redirect()->route('customer.reviews.index')->with('error', 'Review not found or you do not have permission to edit it.');
        }

        $products = Product::all();
        $users = User::all();

        return view('customer.reviews.edit', compact('review', 'products', 'users'));
    }

    public function update(Request $request, $id)
    {
        // Validate the input data
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
        ]);

        // Find the review by ID and ensure it belongs to the current user
        $review = Review::where('user_id', auth()->id())
                        ->findOrFail($id);

        // Update the review details
        $review->product_id = $request->input('product_id');
        $review->rating = $request->input('rating');
        $review->comment = $request->input('comment');
        $review->save();

        return redirect()->route('customer.reviews.index')->with('success', 'Review updated successfully');
    }

    public function softDelete($id)
    {
        try {
            $review = Review::where('user_id', auth()->id())
                            ->findOrFail($id);

            if ($review->deleted_at) {
                return response()->json(['error' => 'Review already deleted.'], 400);
            }

            $review->delete(); // Perform the soft delete
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete review. ' . $e->getMessage()], 500);
        }
    }

    public function restore($id)
    {
        $review = Review::withTrashed()
                        ->where('user_id', auth()->id())
                        ->findOrFail($id);

        if ($review->trashed()) {
            $review->restore();
            return response()->json(['success' => true, 'review' => $review]);
        }
        return response()->json(['success' => false]);
    }
}