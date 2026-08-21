<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Shipping;
use App\Services\FirebaseNotificationService;
use App\Services\OrderService;
use Illuminate\Console\Command;

class AutoConfirmDeliveredOrders extends Command
{
    protected $signature   = 'orders:auto-confirm';
    protected $description = 'Runs every hour: notifies buyers when expected delivery date passes, auto-completes after 1 extra day.';

    public function __construct(
        private OrderService $orderService,
        private FirebaseNotificationService $notificationService,
    ) {
        parent::__construct();
    }

    
    public function handle(): void
    {
        $this->notifyDeliveryDatePassed();
        $this->autoCompleteExpired();
    }

   

    private function notifyDeliveryDatePassed() {

        Shipping::with('order.product')
            ->whereNotNull('period')
            ->whereRaw('period <= NOW()')
            ->whereRaw('DATE_ADD(period, INTERVAL 1 DAY) > NOW()')
            ->get()
            ->filter(fn(Shipping $s) => $s->order->status == 'pending')
            ->each(function (Shipping $s) {
                $this->notificationService->sendToUser(
                    userId: $s->order->customer_id,
                    title: 'Did Your Order Arrive?',
                    body: "Did you receive your shipment of: {$s->order->product->title}?",
                );
            });
    }

 

    private function autoCompleteExpired() {

        Shipping::with('order.reports', 'order.product')
            ->whereNotNull('period')
            // grace day has fully passed
            ->whereRaw('DATE_ADD(period, INTERVAL 1 DAY) <= NOW()')
            ->get()
            ->filter(fn(Shipping $s) =>
                $s->order->status == 'pending' &&
                $s->order->reports->isEmpty()
            )
            ->each(fn(Shipping $s) => $this->orderService->completeOrder($s->order));
    }
}
