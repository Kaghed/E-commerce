<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAdRequest;
use App\Http\Resources\AdvertismentResource;
use App\Models\Advertisment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\FirebaseNotificationService;

use Illuminate\Support\Facades\DB;

class AdvertismentController extends Controller
{

public function __construct(
        private FirebaseNotificationService $notificationService ) {}

    public function createAd(CreateAdRequest $request ){

        return DB::transaction(function () use ($request) {

            $data = $request->validated();

            $user = Auth::user();

            $wallet = $user->wallet;

            if ($wallet->balance < $data['amount']) {
                return response()->json('You don\'t have enough money', 403);
            }

            $wallet->decrement('balance', $data['amount']);

            $transaction = Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'payment',
                'status' => 'completed',
                'description' => 'payment for an ad',
                'amount' => $data['amount']
            ]);
            Advertisment::create([
                'user_id' => $user->id,
                'title'=> $data['title'],
                'phone_number'=> $data['phone_number'],
                'description' => $data['description'],
                'governorate'=> $data['governorate'],
                'status' => 'pending',
                'transaction_id' => $transaction->id
            ]);

            $this->notificationService->sendToUser(
                userId: 1,
                title: 'NewAdvertisment',
                body: "there is a request added new advertisment",
            );  

            return response()->json('Ad created and now it\'s pending', 201);
        });
    }

    public function deleteAd(int $ad_id){

        $user = Auth::user();

        $ad = Advertisment::findOrFail($ad_id);

        if($user->id != $ad->user_id){
            return response()->json('You can only delete your ads', 400);
        }

        $ad->delete();

        return response()->json('Ad deleted successfully', 200);

    }

    public function getMyAdsByStatus(Request $request){

        $request->validate([
            'status' => 'required|string|in:pending,approved,declined'
        ]);
        $user = Auth::user();
        $ads = Advertisment::where('status', $request->status)
            ->where('user_id', $user->id)
            ->paginate(10);


        return response()->json([
            'transactions' => AdvertismentResource::collection($ads),

            'pagination' => [
                'current_page' => $ads->currentPage(),
                'last_page' => $ads->lastPage(),
                'per_page' => $ads->perPage(),
                'total' => $ads->total(),
            ]
        ]);

    }
}
