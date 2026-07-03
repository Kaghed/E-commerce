<?php

namespace App\Http\Controllers;

use App\Http\Resources\SupportResource;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportRequestController extends Controller
{

    public function askQuestion(Request $request)
    {

        $request->validate([
            'subject' => 'required|string|min:1|max:255',
            'question' => 'required|string|min:1|max:255'
        ]);

        $user = Auth::user();
        SupportRequest::create([
            'user_id' => $user->id,
            'subject' => $request->subject,
            'question' => $request->question,
            'answer' => null,
            'status' => 'pending'
        ]);

        return response()->json('Question sent to the support', 200);
    }

    public function getMyQuestionsByStatus(Request $request){

        $request->validate([
            'status' => 'required|in:pending,answered'
        ]);

        $user = Auth::user();

        $questions = SupportRequest::where('user_id', $user->id)->where('status' , $request->status)->paginate(10);

        return response()->json([
            'questions' => ,

            'pagination' => [
                'current_page' => $questions->currentPage(),
                'last_page' => $questions->lastPage(),
                'per_page' => $questions->perPage(),
                'total' => $questions->total(),
            ]
        ]);


    }
}
