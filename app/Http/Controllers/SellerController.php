<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Support\Facades\Auth;

class SellerController extends Controller
{
    public function __construct(protected ProductService $productService)
    {
    }

    public function createProduct(CreateProductRequest $request)
    {
        $product = $this->productService->create($request);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product,
        ], 201);
    }

    public function updateProduct(UpdateProductRequest $request, $id)
    {
        $product = $this->productService->update($request, $id);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product,
        ], 200);
    }

    public function deleteProduct($id)
    {
        $this->productService->delete($id);

        return response()->json([
            'message' => 'Product deleted successfully',
        ], 200);
    }

    public function hideProduct($id)
    {
        $product = Product::findOrFail($id);
        $user = Auth::user();

        if ($product->seller_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $product->update([
            'is_active' => false,
        ]);

        return response()->json([
            'message' => 'Product hidden successfully',
        ], 200);
    }

    public function activeProduct($id)
    {
        $product = Product::findOrFail($id);
        $user = Auth::user();

        if ($product->seller_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $product->update([
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Product is now visible',
        ], 200);
    }

    public function getAllMyProducts()
    {
        $user = Auth::user();

        $products = Product::where('seller_id', $user->id)->paginate(10);

        return response()->json([
            'message' => 'Products retrieved successfully',
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
            'products' => $products,
        ], 200);
    }

    public function getMyInactiveProducts()
    {
        $user = Auth::user();

        $products = Product::where('seller_id', $user->id)
            ->where('is_active', false)
            ->paginate(10);

        return response()->json([
            'message' => 'Inactive products retrieved successfully',
            'products' => $products,
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ], 200);
    }

    public function getMyActiveProducts()
    {
        $user = Auth::user();

        $products = Product::where('seller_id', $user->id)
            ->where('is_active', true)
            ->paginate(10);

        return response()->json([
            'message' => 'Active products retrieved successfully',
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
            'products' => $products,
        ], 200);
    }

    public function countMyActiveProducts()
    {
        $user = Auth::user();

        $count = Product::where('seller_id', $user->id)
            ->where('is_active', true)
            ->count();

        return response()->json([
            'message' => 'Active products count retrieved successfully',
            'count' => $count,
        ], 200);
    }

    public function countMyInactiveProducts()
    {
        $user = Auth::user();

        $count = Product::where('seller_id', $user->id)
            ->where('is_active', false)
            ->count();

        return response()->json([
            'message' => 'Inactive products count retrieved successfully',
            'count' => $count,
        ], 200);
    }
}