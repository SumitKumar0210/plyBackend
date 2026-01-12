<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProductTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');

        $this->middleware('permission:product_types.read')->only([
            'getData'
        ]);

        $this->middleware('permission:product_types.create')->only([
            'store'
        ]);

        $this->middleware('permission:product_types.update')->only([
            'edit', 'update', 'statusUpdate'
        ]);

        $this->middleware('permission:product_types.delete')->only([
            'delete'
        ]);
    }
    
    public function getData(Request $request)
    {
        try {
            $query = ProductType::orderByDesc('id');
    
            
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
    
            $productTypes = $query->get();
    
            return response()->json([
                'data' => $productTypes,
                'message' => 'Product type fetched successfully!'
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to store product types',
                'details' => $e->getMessage(),
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
                    Rule::unique('product_types', 'name')->whereNull('deleted_at'),
                ],
            ]);

            $productType = new ProductType();
            $productType->name = $request->name;
           
            $productType->status = $request->status ?? 1;
            $productType->save();
          
            return response()->json(['message' => 'Product type created successfully',
                'data' => $productType]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store product type', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{

            $productType =ProductType::find($id);

            if(!$productType){
                return response()->json(['error' => 'Product type not found'], 404);
            }
            return response()->json(['message' => 'Product type fetch  successfully',
                'data' => $productType]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store product type', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id){
        try{

            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('product_types', 'name')->ignore($id)->whereNull('deleted_at'),
                ],
            ]);

            $productType =ProductType::find($id);

            if(!$productType){
                return response()->json(['error' => 'Product type not found'], 404);
            }

            $productType->name = $request->name;
            
            $productType->status = $request->status ?? $productType->status;
            $productType->save();
            return response()->json(['message' => 'Product updated  successfully',
                'data' => $productType]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to update product type', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id)
    {
        try{

            $productType =ProductType::find($id);

            if(!$productType){
                return response()->json(['error' => 'Product type not found'], 404);
            }
            $productType->delete();

            return response()->json(['message' => 'Product type deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to delete product', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $productType =ProductType::find($id);

            if(!$productType){
                return response()->json(['error' => 'Product type not found'], 404);
            }
            $productType->status= !$productType->status;
            $productType->save();

            return response()->json(['message' => 'Product type status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  product', $e->getMessage()], 500);
        }
        
    }
}
