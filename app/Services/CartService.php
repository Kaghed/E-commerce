<?php

namespace App\Services;

use App\Http\Requests\AddCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartService
{

 
   public function addItem(AddCartItemRequest $request){

        $user = Auth::user();
        $product = Product::findOrFail($request->product_id);

        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

       $existingItem = $cart->items()->where('product_id', $product->id)->first();
       
       if ($existingItem) {
           $alreadyInCart = $existingItem->quantity;
     } else 
     {
            $alreadyInCart = 0;
     }
       
        $quantityAfterAdd  = $alreadyInCart + $request->quantity;

        if ($quantityAfterAdd > $product->quantity) {
            return response()->json([
                'message' => "Only {$product->quantity} units available. You already have {$alreadyInCart} in your cart.",
            ], 422);
        }

        return DB::transaction(function () use ($cart, $product, $existingItem, $request, $quantityAfterAdd) {

        if ($existingItem) {
                $existingItem->update([
                    'quantity'    => $quantityAfterAdd,
                    'total_price' => $product->price * $quantityAfterAdd,
                ]);
                return $existingItem->fresh()->load('product');
            }


            return $cart->items()->create([
                'product_id'  => $product->id,
                'quantity'    => $request->quantity,
                'total_price' => $product->price * $request->quantity,
            ])->load('product');
        });
    }



 
    public function updateItem(UpdateCartItemRequest $request, $cartItemId) {

        $user = Auth::user();

        $cart = Cart::where('user_id', $user->id)->firstOrFail();
        $cartItem = $cart->items()->where('id', $cartItemId)->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Item not found in your cart.'], 404);
        }

        $product = $cartItem->product;

        if ($request->quantity > $product->quantity) {
            return response()->json([
                'message' => "Only {$product->quantity} units available in stock.",
            ], 422);
        }

        $cartItem->update([
            'quantity'    => $request->quantity,
            'total_price' => $product->price * $request->quantity,
        ]);

        return $cartItem->fresh()->load('product');
    }
 
 
 
    public function removeItem($cartItemId)
    {
        $user = Auth::user();

        $cart = Cart::where('user_id', $user->id)->firstOrFail();
        $cartItem = $cart->items()->where('id', $cartItemId)->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Item not found in your cart.'], 404);
        }

        $cartItem->delete();
    }

    public function showCart(){

    $user = Auth::user();

    $cart = Cart::with('items.product')
                ->where('user_id', $user->id)
                ->first();

    return response()->json([
        'cart_id' => $cart->cart_id,
        'items'   => $cart->items,
    ]);
    
    }
}