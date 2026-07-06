<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ShipOrderRequest;
use App\Http\Requests\CreateOrderRequest;
use App\Services\OrderService;
use App\Services\SellerOrderService;
use Illuminate\Http\JsonResponse;


class OrderController extends Controller
{
       public function __construct(private OrderService $orderService , private SellerOrderService $sellerOrderService) {}

   
// Customer


    public function ShowOrderByCustomer(Request $request){
      
    $request->validate([
        'status' => 'string|in:pending,complete',
    ]);

        return response()->json($this->orderService->myOrders($request));
    }



    public function store(CreateOrderRequest $request) {
        
        $result = $this->orderService->checkout($request);


        return response()->json([
            'message' => 'Order placed successfully.',
            'data'    => $result,
        ], 201);
    }

   


    public function confirm($orderId) {

        $result = $this->orderService->confirmDelivery($orderId);

        return response()->json([
            'message' => 'Order confirmed successfully.',
            'data'    => $result,
        ]);
    }


///////////////////////// seller
 

    public function ShowOrderBySeller(Request $request){

         $request->validate([
            'status' => 'string|in:pending,complete',
        ]); 
        return response()->json($this->sellerOrderService->myOrders($request));
    }



    public function ship(ShipOrderRequest $request, $orderId) {

        $result = $this->sellerOrderService->ship($request, $orderId);

        return response()->json([
            'message' => 'Order marked as shipped successfully.',
            'data'    => $result,
        ]);
    }



    public function reject($orderId) { 

        return $this->sellerOrderService->reject($orderId);
    }
 

     
    public function completedCount() {
        
        return response()->json($this->sellerOrderService->completedOrdersCount());
    }



}
