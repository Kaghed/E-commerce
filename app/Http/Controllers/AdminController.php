<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdvertismentResource;
use App\Http\Resources\SupportResource;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\UserResource;
use App\Models\Advertisment;
use App\Models\Product;
use App\Models\SupportRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AdminService;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

use function Illuminate\Support\minutes;

class AdminController extends Controller
{

    public function __construct(
        protected AdminService $adminService ,
        private FirebaseNotificationService $notificationService,

    ) {
    }
    public function showUsers()
    {
        $users = User::paginate(10);

        return response()->json([
            'users' => UserResource::collection($users),

            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ]);
    }

    public function blockUser(Request $request)
    {

        $data = $this->adminService->blockUser($request);

        return response()->json([
            'message' => $data['message'],
            'ban_reason' => $data['ban_reason'],
            'banned_until' => $data['banned_until'],
        ], $data['status']);

    }

    public function unBlockUser(int $user_id)
    {

        $cur_user = Auth::user();
        if ($cur_user->id == $user_id) {
            return response()->json('This is an admin id', 403);
        }
        $user = User::where('id', $user_id)->firstOrFail();

        if (!$user->banned_until) {
            return response()->json('This user is not banned', 403);
        }
        $user->update([
            'ban_reason' => null,
            'banned_until' => null
        ]);

        return response()->json([
            'message' => 'User Unbanned successfully',
        ], 200);
    }

    public function getBlockedUsers()
    {

        $users = User::whereNotNull('ban_reason')->paginate(10);

        return response()->json([
            'users' => UserResource::collection($users),

            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ]);

    }

    public function checkIfUserBlocked(int $user_id)
    {

        $user = User::where('id', $user_id)->firstOrFail();

        if ($user->banned_until > now()) {
            return response()->json([
                'message' => 'This user is blocked',
                'banned until' => $user->banned_until,
                'ban_reason' => $user->ban_reason
            ], 200);
        }

        if (!$user->banned_until && $user->ban_reason != null) {
            $user->update([
                'ban_reason' => null
            ]);
        }

        return response()->json('This user is not banned', 403);

    }

    public function deleteProduct(Request $request)
    {

        $data = $request->validate([
            'user_id' => 'required|string',
            'product_id' => 'required|string',
            'delete_reason' => 'required|string|min:5'
        ]);

        $product = Product::where('id', $request->product_id)->firstOrFail();

        if ($product->seller_id != $request->user_id) {
            return response()->json('this product is not for this user', 403);

        }

    }

    public function getTransactionsByType(Request $request)
    {

        $request->validate([
            'type' => 'required|in:deposit,withdraw'
        ]);

        $transactions = Transaction::where('type', $request->type)->paginate(10);

        return response()->json([
            'transactions' => TransactionResource::collection($transactions),

            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ]
        ]);
    }


    public function getTransactionsByStatus(Request $request)
    {

        $request->validate([
            'status' => 'required|in:pending,completed'
        ]);

        $transactions = Transaction::where('status', $request->status)->paginate(10);

        return response()->json([
            'transactions' => TransactionResource::collection($transactions),

            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ]
        ]);
    }

    public function handleDepositTransaction(Request $request, int $transaction_id)
    {
        $request->validate([
            'status' => 'required|string|in:approved,cancelled',
            'amount' => 'required|string'
        ]);

        $transaction = Transaction::findOrFail($transaction_id);
            
          $wallet = $transaction->wallet;
          $user_id = $wallet->user_id;
          
        if ($transaction->type !== 'deposit') {
            return response()->json([
                'message' => 'This is not a deposit transaction'
            ], 400);
        }

        if ($transaction->status !== 'pending') {
            return response()->json([
                'message' => 'Transaction already processed'
            ], 400);
        }

        if ($request->status === 'approved') {

            $wallet->increment('balance', $request->amount);

            $transaction->update([
                'status' => 'completed',
                'amount' => $request->amount
            ]);


             $this->notificationService->sendToUser(
                userId:  $user_id,
                title: 'DepositTransaction',
                body: "the desposit transaction ID : {$transaction->id} was accepted by the admin  ",
            );


            return response()->json([
                'message' => 'Deposit approved successfully'
            ]);
        }

     if ($request->status === 'cancelled') {
    
         $transaction->delete();
         $this->notificationService->sendToUser(
                userId:  $user_id,
                title: 'DepositTransaction',
                body: "the desposit transaction ID : {$transaction->id} was unaccepted by the admin  ",
            );

        return response()->json([
            'message' => 'Deposit cancelled '
        ]);
 }       
    }

    public function handleWithdrawTransaction(Request $request, int $transaction_id)
    {
        $request->validate([
            'status' => 'required|in:approved,cancelled'
        ]);

        $transaction = Transaction::findOrFail($transaction_id);

        if ($transaction->type !== 'withdraw') {
            return response()->json([
                'message' => 'This is not a deposit transaction'
            ], 400);
        }

        if ($transaction->status !== 'pending') {
            return response()->json([
                'message' => 'Transaction already processed'
            ], 400);
        }

         $wallet = $transaction->wallet;
         $user_id = $wallet->user_id;

        if ($request->status === 'approved') {

            $user = Auth::user();

            $tax =$transaction->amount * 0.01;
            $user->wallet->balance += $tax ;

            $user->wallet->save();

            $transaction->update([
                'status' => 'completed'
            ]);

             $this->notificationService->sendToUser(
                userId: $user_id,
                title: 'WithdrawTransaction',
                body: "the withdraw transaction ID : {$transaction->id} was accepted by the admin",
            );

            return response()->json([
                'message' => 'Withdraw approved successfully'
            ]);

        }

        // $wallet->increment('balance', $transaction->amount);
        // $wallet->save();
 
    if ($request->status === 'cancelled') {
        $transaction->delete();
      
         $this->notificationService->sendToUser(
                userId: $user_id,
                title: 'WithdrawTransaction',
                body: "the withdraw transaction ID : {$transaction->id} was unaccepted by the admin",
            );
        return response()->json([
            'message' => 'Deposit cancelled '
        ]);
 }

    }


    public function handleAdvertisment(Request $request, int $ad_id)
    {

        return DB::transaction(function () use ($request, $ad_id) {

            $request->validate([
                'status' => 'required|in:approved,declined',
                'reason' => 'required|string'
            ]);

            $ad = Advertisment::findOrFail($ad_id);


            if ($ad->status != 'pending') {
                return response()->json(
                    'This is not a pending ad'
                    ,
                    403
                );
            }

            $amount = $ad->transaction->amount;
            $user_id =$ad->transaction->user_id;

            if ($request->status == 'approved') {

                $ad->update([
                    'status' => 'approved'
                ]);

                $admin = Auth::user();
                $admin->wallet->increment('balance', $amount);
        
            $this->notificationService->sendToUser(
                userId: $user_id,
                title: 'AcceptedAdvertisment',
                body: "the Advertisment ID : {$ad->id} was accepted by the admin",
            );

                return response()->json('Ad now is approved', 200);
            }


            $user = $ad->user;

            $user->wallet->increment('balance', $amount);

            $ad->update([
                'status' => 'declined'
            ]);

            Transaction::create([
                'wallet_id' => $user->wallet->id,
                'type' => 'refund',
                'amount' => $amount,
                'status' => 'completed',
                'description' => $request->reason,
            ]);

            $this->notificationService->sendToUser(
                userId: $user_id,
                title: 'UnacceptedAdvertisment',
                body: "the Advertisment ID : {$ad->id} was accepted by the admin",
            );

            return response()->json('Add declined', 201);
        });
    }


    public function getAdsByStatus(Request $request)
    {

        $request->validate([
            'status' => 'required|string|in:pending,approved,declined'
        ]);

        $ads = Advertisment::where('status', $request->status)
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

    public function getQuestionsByStatus(Request $request)
    {

        $request->validate([
            'status' => 'required|in:pending,answered'
        ]);

        $questions = SupportRequest::where('status', $request->status)->paginate(10);

        return response()->json([
            'questions' => SupportResource::collection($questions),

            'pagination' => [
                'current_page' => $questions->currentPage(),
                'last_page' => $questions->lastPage(),
                'per_page' => $questions->perPage(),
                'total' => $questions->total(),
            ]
        ]);
    }

    public function handleQuestion(Request $request , int $question_id){

        $request->validate([
            'answer' => 'required|string|min:1|max:255'
        ]);

        $question = SupportRequest::findOrFail($question_id);
        $user_id = $question->user_id;
        if($question->status !='pending'){
            return response()->json('question already answered', 401);
        }

        $question->status = 'answered';
        $question->answer = $request->answer;
        $question->save();
        
        $this->notificationService->sendToUser(
                userId: $user_id,
                title: 'AnswerToQuestion',
                body: "you have been answered by your question ID : {$question->id}  by the admin",
            );

        return response()->json('question answered successfully', 200);
    }

}
