<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    

    public function  showAllProducts()
    {
        $products = Product::query()->paginate(10);
       
        return response()->json([
            'message' => 'Products retrieved successfully',
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
            'products' => $products
        ], 200);
    }


    public function getProductByCategory($id)
    {
        $products = Product::where('category_id', $id)->where('is_active', true)->paginate(10);
        return response()->json([
            'message' => 'Products retrieved successfully',
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
            'products' => $products
        ], 200);
    
    }

    public function filterProducts(Request $request){

    $query = Product::query()->where('is_active', true);

    if ($request->filled('category_id')) {
        $query->where('category_id', $request->category_id);
    }
    if ($request->filled('min_price') && $request->filled('max_price')) {
        $query->whereBetween('price', [$request->min_price, $request->max_price]);  

    }
    if ($request->filled('governorate')) {
        $query->where('governorate', $request->governorate);    
    }

    $products = $query->paginate(10);
    return response()->json([
        'message' => 'Products filtered successfully',
        'pagination' => [
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
        ],
        'products' => $products
    ], 200);
    }

    
    public function searchProductsByProductUrl(Request $request)
    {
        $request->validate([
            'query' => 'required|string',
        ]);

        $query = $request->input('query');

        $products = Product::where('product_url', 'like', "%{$query}%")->get();

        return response()->json([
            'message' => 'Products search results retrieved successfully',
            'products' => $products
        ], 200);
    }

}
