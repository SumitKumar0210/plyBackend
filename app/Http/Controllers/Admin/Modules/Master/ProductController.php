<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');

        $this->middleware('permission:product.read')->only([
            'getData', 'search'
        ]);

        $this->middleware('permission:product.create')->only([
            'store'
        ]);

        $this->middleware('permission:product.update')->only([
            'edit', 'update', 'statusUpdate'
        ]);

        $this->middleware('permission:product.delete')->only([
            'delete'
        ]);
    }
    
    // public function getData(Request $request)
    // {
    //     try {
    //         $query = Product::with('group')->orderByDesc('id');
    
            
    //         if ($request->filled('status')) {
    //             $query->where('status', $request->status);
    //         }
    
    //         $products = $query->get();
    
    //         return response()->json([
    //             'data' => $products,
    //             'message' => 'Products fetched successfully!'
    //         ], 200);
    
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'error' => 'Failed to fetch products',
    //             'details' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    
    public function getData(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $query = Product::with('group')->orderByDesc('id');
    
            // Status filter
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
    
            // Search filter
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('model', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('size', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('product_type', 'LIKE', "%{$searchTerm}%")
                      ->orWhereHas('group', function ($subQuery) use ($searchTerm) {
                          $subQuery->where('name', 'LIKE', "%{$searchTerm}%");
                      });
                });
            }
    
            $products = $query->paginate($perPage);
    
            return response()->json([
                'data' => $products->items(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ],
                'message' => 'Products fetched successfully!'
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch products',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $query = Product::with('group')->orderBy('id', 'desc');

            if ($request->filled('group')) {
                $query->whereHas('group', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->group . '%'); 
                });
            }

            if ($request->filled('name')) {
                $query->where('name', 'ILIKE', '%' . $request->name . '%');
            }

            if ($request->filled('model')) {
                $query->where('model', 'ILIKE', '%' . $request->model . '%');
            }

            if ($request->filled('size')) {
                $query->where('size', 'ILIKE', '%' . $request->size . '%');
            }

            if ($request->filled('color')) {
                $query->where('color', 'ILIKE', '%' . $request->color . '%');
            }

            if ($request->filled('hsn_code')) {
                $query->where('hsn_code', 'ILIKE', '%' . $request->hsn_code . '%');
            }

            if ($request->filled('product_type')) {
                $query->where('product_type', 'ILIKE', '%' . $request->product_type . '%');
            }

            if ($request->filled('color')) {
                $query->where('color', 'ILIKE', '%' . $request->color . '%');
            }

            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }


            $products = $query->paginate(10);
            return response()->json($products);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch products',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try{
            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('products', 'name')->whereNull('deleted_at'),
                ],
            ]);

            $product = new Product();
            $product->name = $request->name;
            $product->model = $request->model;
            $product->size = $request->size ;
            $product->color = $request->color;
            $product->hsn_code = $request->hsn_code?? null;
            $product->rrp = round($request->rrp,2);
            $product->product_type = $request->product_type;
            $product->narations = $request->narations;
            $product->minimum_qty = $request->minimum_qty?? 0;
            $product->group_id = $request->group_id;
            if($request->has('image')){
                $image = $request->file('image');
                $randomName = rand(10000000, 99999999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/products/'), $imageName);
                $product->image = '/uploads/products/'.$imageName;

            }
           
            $product->status = $request->status ?? 1;
            $product->save();
          
            return response()->json(['message' => 'Product created successfully',
                'data' => $product]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store product', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{

            $product =Product::find($id);

            if(!$product){
                return response()->json(['error' => 'Product not found'], 404);
            }
            return response()->json(['message' => 'Product fetch  successfully',
                'data' => $product]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch product', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id){
        try{

            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('products', 'name')->ignore($id)->whereNull('deleted_at'),
                ],
            ]);

            $product =Product::find($id);

            if(!$product){
                return response()->json(['error' => 'Product not found'], 404);
            }

            $product->name = $request->name;
            $product->model = $request->model;
            $product->size = $request->size ;
            $product->color = $request->color;
            $product->hsn_code = $request->hsn_code ?? null;
            $product->rrp = round($request->rrp,2);
            $product->product_type = $request->product_type;
            $product->group_id = $request->group_id;
            $product->minimum_qty = $request->minimum_qty?? 0;
            $product->narations = $request->narations;
            if ($request->has('image') && $request->file('image')) {
                $image = $request->file('image');
                $randomName = rand(1000, 9999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/products/'), $imageName);
                $product->image = '/uploads/products/'.$imageName;

            }
            $product->status = $request->status ?? $product->status;
            $product->save();
            $product->load('group');
            return response()->json(['message' => 'Product updated  successfully',
                'data' => $product]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to update product', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id)
    {
        try{

            $product =Product::find($id);

            if(!$product){
                return response()->json(['error' => 'Product not found'], 404);
            }
            $product->delete();

            return response()->json(['message' => 'Product deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to delete product', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $product =Product::find($id);

            if(!$product){
                return response()->json(['error' => 'Product not found'], 404);
            }
            $product->status= !$product->status;
            $product->save();

            return response()->json(['message' => 'Product status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  product', $e->getMessage()], 500);
        }
        
    }
}
