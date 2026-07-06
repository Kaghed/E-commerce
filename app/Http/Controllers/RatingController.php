<?php

namespace App\Http\Controllers;

use App\Http\Requests\RateSellerRequest;
use App\Services\RatingService;
use Illuminate\Http\Request;

class RatingController extends Controller
{
public function __construct(private RatingService $ratingService) {}

    
    public function store(RateSellerRequest $request) {

        $result = $this->ratingService->rate($request);

        return response()->json([
            'message' => 'Rating submitted successfully.',
            'data'    => $result,
        ]);
    }

    public function sellerAverage($sellerId) {
        
        return response()->json(
            $this->ratingService->sellerAverage($sellerId)
        );
    }
}
