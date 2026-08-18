<?php

namespace App\Services;

use App\Http\Requests\RateSellerRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RatingService
{
    public function rate(RateSellerRequest $request)
    {
        $customer = Auth::user();

        return DB::transaction(function () use ($request, $customer) {
            $order = Order::query()
                ->whereKey($request->integer('order_id'))
                ->where('customer_id', $customer->id)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw ValidationException::withMessages([
                    'order_id' => ['This order does not belong to the authenticated customer.'],
                ]);
            }

            if ($order->status !== 'complete') {
                throw ValidationException::withMessages([
                    'order_id' => ['Only completed orders can be rated.'],
                ]);
            }

            $product = Product::query()
                ->whereKey($order->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($customer->id === $product->seller_id) {
                throw ValidationException::withMessages([
                    'order_id' => ['You cannot rate yourself.'],
                ]);
            }

            $alreadyRated = Rating::query()
                ->where('customer_id', $customer->id)
                ->where('product_id', $product->id)
                ->exists();

            if ($alreadyRated) {
                throw ValidationException::withMessages([
                    'order_id' => ['This product has already been rated.'],
                ]);
            }

            return Rating::create([
                'customer_id' => $customer->id,
                'seller_id' => $product->seller_id,
                'product_id' => $product->id,
                'value' => $request->integer('value'),
            ]);
        });
    }

    public function sellerAverage($sellerId)
    {
        User::findOrFail($sellerId);

        $ratings = Rating::query()
            ->where('seller_id', $sellerId)
            ->whereNotNull('product_id');

        $mostCommonRating = (clone $ratings)
            ->selectRaw('value, COUNT(*) as rating_count')
            ->groupBy('value')
            ->orderByDesc('rating_count')
            ->orderByDesc('value')
            ->first();

        return [
            'seller_id' => (int) $sellerId,
            'most_common_rating' => $mostCommonRating
                ? (int) $mostCommonRating->value
                : null,
            'rating_count' => (clone $ratings)->count(),
            // Temporary compatibility field for clients that still read the old key.
            'average_rating' => $mostCommonRating
                ? (int) $mostCommonRating->value
                : null,
        ];
    }
}
