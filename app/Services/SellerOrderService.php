<?php

namespace App\Services;

use App\Http\Requests\ShipOrderRequest;
use App\Models\Order;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SellerOrderService
{
    public function __construct(
        private WalletService $walletService,
        private FirebaseNotificationService $notificationService,
    ) {}

  

    public function ship(ShipOrderRequest $request, $orderId)
    {
        $seller = Auth::user();

        $order = Order::where('id', $orderId)
            ->with('product', 'shipping')
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        if ($order->product->seller_id !== $seller->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($order->status == 'complete') {
            return response()->json(['message' => 'Order is already completed.'], 422);
        }


        $order->shipping->update([
            'image'      => $request->file('image')->store('shipping', 'public'),
            'period'     => $request->period,
        ]);


        
        $this->notificationService->sendToUser(
            userId: $order->customer_id,
            title: 'Order Shipped',
            body: "Your order for {$order->product->title} has been shipped.",
        );

        return $order->fresh()->load('product', 'shipping');
    }


 


    public function reject($orderId)
    {
        $seller = Auth::user();

        $order = Order::where('id', $orderId)
            ->with('product')
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $product = $order->product;

        if ($product->seller_id !== $seller->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($order->status == 'complete') {
            return response()->json(['message' => 'Order is already completed.'], 422);
        }

        return DB::transaction(function () use ($order, $product) {


        $customerWallet = Wallet::firstOrCreate(
                ['user_id' => $order->customer_id],
                ['balance' => 0]
            );

            $this->walletService->credit(
                wallet: $customerWallet,
                amount: $order->total_price,
                type:'refund',
                description: "Refund: order rejected by seller for product: {$product->title}",
            );

           
            $product->increment('quantity', $order->quantity);

            
            $this->notificationService->sendToUser(
                userId: $order->customer_id,
                title: 'Order Rejected',
                body: "Your order for {$product->title} was rejected by the seller. Your amount has been refunded.",
            );

            $order->delete();

            return response()->json(['message' => 'Order rejected and buyer refunded successfully.']);
        });
    }


   

    public function myOrders(Request $request)
    {
        $seller = Auth::user();

        $query = Order::whereHas('product', fn($q) => $q->where('seller_id', $seller->id))
            ->with('product', 'shipping', 'customer.profile')
            ->latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return $query->get();
    }

    

    public function completedOrdersCount()
    {
        $seller = Auth::user();

        $count = Order::whereHas('product', fn($q) => $q->where('seller_id', $seller->id))
            ->where('status', 'complete')
            ->count();

        return ['completed_orders' => $count];
    }
}
