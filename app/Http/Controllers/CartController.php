<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
     public function __construct(private CartService $cartService) {}


     public function index(){

        $cart = $this->cartService->showCart();
        return response()->json($cart);
    }

    public function addItem(AddCartItemRequest $request){

        $result = $this->cartService->addItem($request);

        return response()->json([
            'message' => 'Item added to cart successfully.',
            'data'    => $result,
        ], 201);
    }


    public function updateItem(UpdateCartItemRequest $request, $cartItemId) {

        $result = $this->cartService->updateItem($request, $cartItemId);

        return response()->json([
            'message' => 'Cart item updated successfully.',
            'data'    => $result,
        ]);
    }


    public function removeItem($cartItemId) {

       $this->cartService->removeItem($cartItemId);

        return response()->json(['message' => 'Item removed from cart successfully.']);
    }
}
