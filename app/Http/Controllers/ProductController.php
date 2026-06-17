<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    

    public function  showAllProducts()
    {
        $products = Product::paginate(10);
        return response()->json([
            'message' => 'Products retrieved successfully',
            'products' => $products
        ], 200);
    }


    public function getProductByCategory($id)
    {
        $products = Product::where('category_id', $id)->get();
        return response()->json([
            'message' => 'Products retrieved successfully',
            'products' => $products
        ], 200);
    
    }

    public function FilterProductsByPrice(Request $request)
    {
        
      $request->validate([
            'min_price' => 'required|numeric',
            'max_price' => 'required|numeric',
        ]);
        $products = Product::whereBetween('price', [$request->min_price, $request->max_price])->get();

        return response()->json([
            'message' => 'Products filtered by price retrieved successfully',
            'products' => $products
        ], 200);
    }

    public function searchProducts(Request $request)
    {
  

        $request->validate([
            'query' => 'required|string',
        ]);

        $query = $request->input('query');

        $products = Product::where('title', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->get();

        return response()->json([
            'message' => 'Products search results retrieved successfully',
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
