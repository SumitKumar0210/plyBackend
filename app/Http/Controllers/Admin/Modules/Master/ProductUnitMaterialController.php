<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductUnitMaterial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProductUnitMaterialController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $unitMaterials = ProductUnitMaterial::with('product','material')->orderBy('id','desc')->paginate(10);
            return response()->json($unitMaterials);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch product unit materials'], 500);
        }
        
    }

    public function search(Request $request)
    {
        try {
            $query = ProductUnitMaterial::with('product', 'material')->orderBy('id', 'desc');

            if ($request->filled('material')) {
                $query->whereHas('material', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->material . '%'); 
                });
            }

            if ($request->filled('product')) {
                $query->whereHas('product', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->product . '%'); 
                });
            }

            if ($request->filled('size')) {
                $query->where('size', 'ILIKE', '%' . $request->size . '%');
            }

            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }

            if ($request->filled('qty')) {
                $query->where('qty', $request->qty);
            }

            if ($request->filled('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            $unitMaterials = $query->paginate(10);
            return response()->json($unitMaterials);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch product unit materials',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {
        try{
            
            $request->validate([
                'product_id' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('product_unit_materials', 'product_id')->whereNull('deleted_at'),
                ],
            ]);

            $unitMaterial = new ProductUnitMaterial();
            $unitMaterial->product_id = $request->product_id;
            $unitMaterial->material_id = $request->material_id;
            $unitMaterial->qty = $request->qty;
            $unitMaterial->size = $request->size;
            $unitMaterial->product_type = $request->product_type;
            $unitMaterial->rate = round($request->rate, 2);
            $unitMaterial->total_amount = round($request->total_amount, 2);
            $unitMaterial->total_amount = round($request->total_amount, 2);
            $unitMaterial->status = $request->status ?? 0;
            $unitMaterial->save();
            return response()->json(['message' => 'Product unit material created successfully',
                'data' => $unitMaterial]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch product unit material', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $unitMaterial =ProductUnitMaterial::find($id);

            if(!$unitMaterial){
                return response()->json(['error' => 'Product unit material not found'], 404);
            }
            return response()->json(['message' => 'Product unit material fetch  successfully',
                'data' => $unitMaterial]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch product unit material', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id)
    {
        try{
            $request->validate([
                'product_id' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('product_unit_materials', 'product_id')
                        ->ignore($id) 
                        ->whereNull('deleted_at'), 
                ],
                'status' => 'nullable|in:0,1',
            ]);
            $unitMaterial =ProductUnitMaterial::find($id);

            if(!$unitMaterial){
                return response()->json(['error' => 'Product unit material not found'], 404);
            }
            // $unitMaterial->product_id = $request->product_id;
            $unitMaterial->material_id = $request->material_id;
            $unitMaterial->qty = $request->qty;
            $unitMaterial->size = $request->size;
            $unitMaterial->product_type = $request->product_type;
            $unitMaterial->rate = round($request->rate, 2);
            $unitMaterial->total_amount = round($request->total_amount, 2);
            $unitMaterial->status = $request->status ?? $unitMaterial->status;
            $unitMaterial->save();

            return response()->json(['message' => 'Product unit material updated  successfully',
                'data' => $unitMaterial]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch product unit material', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{
            $unitMaterial =ProductUnitMaterial::find($id);

            if(!$unitMaterial){
                return response()->json(['error' => 'Product unit material not found'], 404);
            }

            $unitMaterial->delete();
            return response()->json(['message' => 'Product unit material deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch product unit material', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $unitMaterial =ProductUnitMaterial::find($id);

            if(!$unitMaterial){
                return response()->json(['error' => 'Product unit material not found'], 404);
            }
            $unitMaterial->status= !$unitMaterial->status;
            $unitMaterial->save();

            return response()->json(['message' => 'Product unit material status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch user product unit material', $e->getMessage()], 500);
        }
        
    }
}
