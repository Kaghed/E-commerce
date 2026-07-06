<?php

namespace App\Services;

use App\Http\Requests\RateSellerRequest;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RatingService
{
     public function rate(RateSellerRequest $request)
    {
        $user = Auth::user();

        $seller = User::findOrFail($request->seller_id);

        
        if ($user->id === $seller->id) {
            return response()->json(['message' => 'You cannot rate yourself.'], 422);
        }

        $rating = Rating::updateOrCreate(
            [
                'customer_id' => $user->id,
                'seller_id'   => $seller->id,
            ],
            [
                'value' => $request->value,
            ]
        );

        return $rating;
    }

    
    public function sellerAverage($sellerId) {

        $averageRating = Rating::where('seller_id', $sellerId)->avg('value');

        return [
            'seller_id'      => $sellerId,
            'average_rating' => round($averageRating, 2),
        ];
    }
}
