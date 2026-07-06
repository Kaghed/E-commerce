<?php

namespace App\Services;
 

use App\Http\Requests\CreateReportRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\Report;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function __construct(
        private WalletService $walletService,
        private FirebaseNotificationService $notificationService,
        private OrderService $orderService,
    ) {}

   

    public function reportOrder(CreateReportRequest $request, $orderId)
    {
        $customer = Auth::user();

        $order = Order::where('id', $orderId)
            ->where('customer_id', $customer->id)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        if ($order->status == 'complete') {
            return response()->json(['message' => 'Cannot report a completed order.'], 422);
        }


        $alreadyReported = Report::where('reporter_id', $customer->id)
            ->where('reportable_id', $order->id)
            ->where('reportable_type', Order::class)
            ->exists();

        if ($alreadyReported) {
            return response()->json(['message' => 'You have already reported this order.'], 422);
        }

        $report = Report::create([
            'reporter_id'     => $customer->id,
            'reportable_id'   => $order->id,
            'reportable_type' => Order::class,
            'description'     => $request->description,
            'status'          => 'pending',
        ]);

          $this->notificationService->sendToUser(
                userId: 1,
                title: 'New Order Report',
                body: "A report has been submitted for order ID: {$order->id}. Please review it.",
            );
        return $report;
    }

   

    public function reportProduct(CreateReportRequest $request, $productId)
    {
        $reporter = Auth::user();
        $product  = Product::findOrFail($productId);

        $report = Report::create([
            'reporter_id'     => $reporter->id,
            'reportable_id'   => $product->id,
            'reportable_type' => Product::class,
            'description'     => $request->description,
            'status'          => 'pending',
        ]); 

         $this->notificationService->sendToUser(
                userId: 1,
                title: 'New Product Report',
                body: "A report has been submitted for product ID: {$product->id}. Please review it.",
            );

        return $report;
    }


   
/////////////////////////


     public function ProductReports(Request $request){

        return Report::where('reportable_type', Product::class)
            ->where('status', $request->status)
            ->with('reporter', 'reportable')
            ->latest()
            ->get();
    }


    public function dismissProductReport($reportId){

        $report = Report::findOrFail($reportId);

        if ($report->reportable_type !== Product::class) {
            return response()->json(['message' => 'This is not a product report.'], 422);
        }

        if ($report->status !== 'pending') {
            return response()->json(['message' => 'This report has already been resolved.'], 422);
        }

        $report->delete();

        return response()->json(['message' => 'Product report dismissed successfully.']);
    }




     public function deleteReportedProduct($reportId)
    {
        $report = Report::with('reportable')->findOrFail($reportId);

        if ($report->reportable_type !== Product::class) {
            return response()->json(['message' => 'This is not a product report.'], 422);
        }

        if ($report->status !== 'pending') {
            return response()->json(['message' => 'This report has already been resolved.'], 422);
        }

        $product = $report->reportable ;

        if (!$product) {
            return response()->json(['message' => 'Product no longer exists.'], 404);
        }

        return DB::transaction(function () use ($report, $product) {

            $this->notificationService->sendToUser(
                userId: $product->seller_id,
                title: 'Product Removed',
                body: "Your product \"{$product->title}\" has been removed due to a violation report.",
            );

            $report->update(['status' => 'accepted']);

          
            $product_id = $report->reportable_id ;
            $this_product = Product::findOrFail($product_id);
            $this_product->delete();

            return response()->json(['message' => 'Product deleted and seller notified.']);
        });
    }




////////////////////////////




    public function OrderReports(Request $request)
    {
        return Report::where('reportable_type', Order::class)
            ->where('status', $request->status)
            ->with('reporter', 'reportable.product', 'reportable.customer')
            ->latest()
            ->get();
    }

   

    public function acceptReport($reportId)
    {
        $report = Report::with('reportable')->findOrFail($reportId);

        if ($report->reportable_type !== Order::class) {
            return response()->json(['message' => 'Only order reports can be resolved this way.'], 422);
        }

        if ($report->status !== 'pending') {
            return response()->json(['message' => 'This report has already been resolved.'], 422);
        }

        $order   = $report->reportable;
        $product = $order->product;

        return DB::transaction(function () use ($report, $order, $product) {

         
        $customerWallet = Wallet::firstOrCreate(
                ['user_id' => $order->customer_id],
                ['balance' => 0]
            );

            $this->walletService->credit(
                wallet: $customerWallet,
                amount: $order->total_price,
                type: 'refund',
                description: "Refund for accepted report on product: {$product->title}",
            );


            $product->increment('quantity', $order->quantity);

            $report->update(['status' => 'accepted']);


            $this->notificationService->sendToUser(
                userId: $order->customer_id,
                title: 'Report Accepted',
                body: "Your report on {$product->title} has been accepted and your amount has been refunded.",
            );


            $this->notificationService->sendToUser(
                userId: $product->seller_id,
                title: 'Order Cancelled',
                body: "Your order for {$product->title} has been cancelled due to a buyer report.",
            );
 

            $order_id = $report->reportable_id;
            $this_order = Order::findOrFail($order_id);
            $this_order->delete();

            return $report->fresh();
        });
    }

    

    public function rejectReport($reportId)
    {
        $report = Report::findOrFail($reportId);

        if ($report->reportable_type !== Order::class) {
            return response()->json(['message' => 'Only order reports can be resolved this way.'], 422);
        }

        if ($report->status !== 'pending') {
            return response()->json(['message' => 'This report has already been resolved.'], 422);
        }

        $order   = $report->reportable;
        $product = $order->product;

        return DB::transaction(function () use ($report, $order, $product) {


        $this->orderService->completeOrder($order);

            $report->delete();


            $this->notificationService->sendToUser(
                userId: $order->customer_id,
                title: 'Report Rejected',
                body: "Your report on {$product->title} has been rejected. The order has been completed.",
            );

            return  $report->fresh();
        });
    }
}
