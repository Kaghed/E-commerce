<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\ProductService;
use Illuminate\Http\Request;

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
            'product' => $product
        ], 201);
    }


    public function updateProduct(UpdateProductRequest $request, $id)
    {
        $product = $this->productService->update($request, $id);
        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product
        ], 200);
    }

    public function deleteProduct($id)
    {
        $this->productService->delete($id);
        return response()->json([
            'message' => 'Product deleted successfully'
        ], 200);
    }

 

 
}
