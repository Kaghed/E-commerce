<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function addToFavorites($id)
    {
        $product = Product::findOrFail($id);
        $user = Auth::user();
        $user->favoriteProducts()->syncWithoutDetaching([$product->id]);
        return response()->json(['message'=>'Product added to favorites']);
   
    }
public function removeFromFavorites($id)
    {
        $product = Product::findOrFail($id);
        $user = Auth::user();
        $user->favoriteProducts()->detach($product->id);
        return response()->json(['message'=>'Product removed from favorites']);
    }
    public function getFavoriteTasks()
    {
        $user = Auth::user();
        $favoriteProducts = $user->favoriteProducts()->paginate(10);
        return response()->json([
            'message' => 'Favorite products retrieved successfully',
            'pagination' => [
                'current_page' => $favoriteProducts->currentPage(),
                'last_page' => $favoriteProducts->lastPage(),
                'per_page' => $favoriteProducts->perPage(),
                'total' => $favoriteProducts->total(),
            ],
            'products' => $favoriteProducts
        ], 200);
    }
}
