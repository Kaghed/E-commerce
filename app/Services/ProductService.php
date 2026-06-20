<?php 

namespace App\Services; 
use App\Http\Requests\ProductRequest;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProductService
{
    public function create(CreateProductRequest $request)
    {
        $user = Auth::user();
        return Product::create([
            'seller_id' => $user->id,
            'category_id' => $request->category_id,
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
            'quantity' => $request->quantity,
            'governorate' => $request->governorate,
            'product_image_url' => $request->file('product_image_url')
                ->store('products', 'public'),
            'product_url' => Str::uuid()
        ]);

    }

    public function update(UpdateProductRequest $request, $id)
    {
        $product = Product::findOrFail($id);
        $user = Auth::user();
        if ($product->seller_id !== $user->id) {
            return response()->json(['message'=>'Unauthorized'],403);
        }
                $data = $request->validated();

        if($request->hasFile('product_image_url')){
            if($product->product_image_url){

         Storage::disk('public')->delete($product->product_image_url);
        } 

         $path=$request->file('product_image_url')->store('products','public');
            $data['product_image_url'] = $path;
        }
        $product->update($data);
       

        return $product;
    }

    public function delete($id)
    {
        $product = Product::findOrFail($id);
        $user = Auth::user();
        if ($product->seller_id !== $user->id) {
            return response()->json(['message'=>'Unauthorized'],403);
        }
        $product->delete();
    }

}