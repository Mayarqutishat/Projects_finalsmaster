<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Order;
use App\Models\CartItem;
use App\Models\Review;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:customer');
    }

    public function index()
    {
        // الحصول على العميل الحالي
        $user = auth()->user();

        // إجمالي المشتريات للعميل الحالي
        $totalPurchases = Payment::whereHas('order', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('status', 'complete')->sum('amount');

        // عدد الطلبات الجديدة للعميل الحالي
        $newOrdersCount = Order::where('user_id', $user->id)
                               ->where('status', 'complete')
                               ->count();

        // عدد العناصر في سلة التسوق للعميل الحالي
        $cartItemsCount = CartItem::whereHas('cart', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->count();

        // عدد التقييمات للعميل الحالي
        $reviewsCount = Review::where('user_id', $user->id)->count();

        // بيانات المبيعات للعميل الحالي (بدون مدة زمنية)
        $salesData = Payment::selectRaw('YEAR(processed_at) as year, MONTH(processed_at) as month, SUM(amount) as total')
                            ->whereHas('order', function ($query) use ($user) {
                                $query->where('user_id', $user->id);
                            })
                            ->where('status', 'complete')
                            ->groupBy('year', 'month')
                            ->orderBy('year', 'asc')
                            ->orderBy('month', 'asc')
                            ->get();

        // بيانات الطلبات للعميل الحالي (بدون مدة زمنية)
        $ordersData = Order::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
                           ->where('user_id', $user->id)
                           ->groupBy('year', 'month')
                           ->orderBy('year', 'asc')
                           ->orderBy('month', 'asc')
                           ->get();

        // بيانات المدفوعات الأخيرة للعميل الحالي
        $paymentsData = Payment::whereHas('order', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with('order.user')
          ->latest()
          ->take(10)
          ->get()
          ->map(function ($payment) {
              $payment->processed_at = Carbon::parse($payment->processed_at);
              return $payment;
          });

        // تمرير البيانات إلى العرض
        return view('customer.dashboard', compact(
            'totalPurchases',
            'newOrdersCount',
            'cartItemsCount',
            'reviewsCount',
            'salesData',
            'ordersData',
            'paymentsData'
        ));
    }
}