<?php

namespace App\Services;

use App\Http\Requests\CreateOrderRequest;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shipping;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private WalletService $walletService,
        private FirebaseNotificationService $notificationService,
    ) {}


    public function checkout(CreateOrderRequest $request)
    {
        $customer   = Auth::user();
        $product = Product::findOrFail($request->product_id);


        if ($request->quantity > $product->quantity) {
            return response()->json([
                'message' => "Only {$product->quantity} units available in stock.",
            ], 422);
        }
 
        $totalPrice = $product->price * $request->quantity;


        $customerWallet = Wallet::where('user_id', $customer->id)->first();

        if (!$customerWallet || $customerWallet->balance < $totalPrice) {
            return response()->json([
                'message' => 'Insufficient wallet balance.',
            ], 422);
        }

        return DB::transaction(function () use ($customer, $product, $request, $totalPrice, $customerWallet) {


        $product->decrement('quantity', $request->quantity);

            
            $transaction = $this->walletService->debit(
                wallet: $customerWallet,
                amount: $totalPrice,
                type: 'payment',
                description: "Payment for product: {$product->title}",
            );

            
            $order = Order::create([
                'customer_id'    => $customer->id,
                'product_id'     => $product->id,
                'transaction_id' => $transaction->id,
                'quantity'       => $request->quantity,
                'total_price'    => $totalPrice,
                'status'         => 'pending',
            ]);

          

            Shipping::create([
                'order_id' => $order->id,
                'phone'    => $request->phone,
                'address'  => $request->address,
            ]);

            
            CartItem::whereHas('cart', fn($q) => $q->where('user_id', $customer->id))
                ->where('product_id', $product->id)
                ->delete();

            
            $this->notificationService->sendToUser(
                userId: $customer->id,
                title: 'Payment Successful',
                body: "Your wallet was charged for purchasing: {$product->title}.",
            );

            
            $this->notificationService->sendToUser(
                userId: $product->seller_id,
                title: 'New Order Received',
                body: "You have a new order for: {$product->title}.",
            );

            return $order->load('product', 'shipping');
        });
    }




    public function confirmDelivery($orderId)
    {
        $customer = Auth::user();

        $order = Order::where('id', $orderId)
            ->where('customer_id', $customer->id)
            ->with('product')
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        if ($order->status == 'complete') {
            return response()->json(['message' => 'Order is already completed.'], 422);
        }

        return DB::transaction(fn() => $this->completeOrder($order));
    }

  

    public function completeOrder(Order $order){

        $order->loadMissing('product');
        $product = $order->product;

     
        $sellerWallet = Wallet::firstOrCreate(
            ['user_id' => $product->seller_id],
            ['balance' => 0]
        );

        $this->walletService->credit(
            wallet: $sellerWallet,
            amount: $order->total_price,
            type: 'deposit',
            description: "Payment received for selling: {$product->title}",
        );

        $order->update(['status' => 'complete']);

        
        $this->notificationService->sendToUser(
            userId: $product->seller_id,
            title: 'Payment Received',
            body: "An amount has been deposited into your wallet for selling: {$product->title}.",
        );

        return $order->fresh();
    }



    
    public function myOrders(Request $request)
    {
        $customer = Auth::user();

        return Order::where('customer_id', $customer->id)
            ->where('status', $request->status)
            ->with('product', 'shipping')
            ->latest()
            ->get();
    }
}
