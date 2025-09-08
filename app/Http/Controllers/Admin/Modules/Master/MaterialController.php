<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Material;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


class MaterialController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $materials = Material::with('category', 'group','unitOfMeasurement')->orderBy('id','desc')->paginate(10);
            return response()->json($materials);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch materials'.$e->getMessage()], 500);
        }
        
    }

    public function search(Request $request)
    {
        try {
            $query = Material::with('category', 'group','unitOfMeasurement')->orderBy('id', 'desc');

            if ($request->filled('category')) {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->category . '%'); 
                });
            }

            if ($request->filled('group')) {
                $query->whereHas('group', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->group . '%'); 
                });
            }

            if ($request->filled('unitOfMeasurement')) {
                $query->whereHas('unitOfMeasurement', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->unitOfMeasurement . '%'); 
                });
            }

            if ($request->filled('opening_stock')) {
                $query->where('opening_stock', 'ILIKE', '%' . $request->opening_stock . '%');
            }

            if ($request->filled('urgently_required')) {
                $query->where('urgently_required', 'ILIKE', '%' . $request->urgently_required . '%');
            }
            
            if ($request->filled('size')) {
                $query->where('size', 'ILIKE', '%' . $request->size . '%');
            }

            if ($request->filled('tag')) {
                $query->where('tag', 'ILIKE', '%' . $request->tag . '%');
            }

            if ($request->filled('price')) {
                $query->where('price', 'ILIKE', '%' . $request->price . '%');
            }

            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
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
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('materials', 'name')->whereNull('deleted_at'),
                ],
            ]);

            $material = new Material();
            $material->name = $request->name;
            $material->unit_of_measurement_id = $request->unit_of_measurement_id;
            $material->size = $request->size ;
            $material->price = $request->price;
            $material->remark = $request->remark;
            $material->category_id = $request->category_id;
            $material->group_id = $request->group_id;
            if($request->has('image')){
                $image = $request->file('image');
                $randomName = rand(10000000, 99999999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/materials/'), $imageName);
                $material->image = '/uploads/materials/'.$imageName;

            }
            $material->opening_stock = $request->opening_stock;
            $material->urgently_required = $request->urgently_required;
            $material->tag = $request->tag;
            $material->created_by = auth()->user()->id;
            $material->status = $request->status ?? 0;
            $material->save();
            return response()->json(['message' => 'Material created successfully',
                'data' => $material]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store material', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{

            $material =Material::find($id);

            if(!$material){
                return response()->json(['error' => 'Material not found'], 404);
            }
            return response()->json(['message' => 'Material fetch  successfully',
                'data' => $material]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch material', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id){
        try{

            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('materials', 'name')->ignore($id)->whereNull('deleted_at'),
                ],
            ]);

            $material =Material::find($id);

            if(!$material){
                return response()->json(['error' => 'Material not found'], 404);
            }

            $material->name = $request->name;
            $material->unit_of_measurement_id = $request->unit_of_measurement_id;
            $material->size = $request->size ;
            $material->price = $request->price;
            $material->remark = $request->remark;
            $material->category_id = $request->category_id ?? $material->category_id;
            $material->group_id = $request->group_id;
            if($request->has('image')){
                $image = $request->file('image');
                $randomName = rand(10000000, 99999999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/materials/'), $imageName);
                $material->image = '/uploads/materials/'.$imageName;

            }
            $material->opening_stock = $request->opening_stock;
            $material->urgently_required = $request->urgently_required;
            $material->tag = $request->tag;
            $material->created_by = auth()->user()->id;
            $material->status = $request->status ?? $material->status;
            $material->save();

            return response()->json(['message' => 'Material updated  successfully',
                'data' => $material]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to update material', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{

            $material =Material::find($id);

            if(!$material){
                return response()->json(['error' => 'Material not found'], 404);
            }
            $material->delete();

            return response()->json(['message' => 'Material deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to delete material', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $material =Material::find($id);

            if(!$material){
                return response()->json(['error' => 'Material not found'], 404);
            }
            $material->status= !$material->status;
            $material->save();

            return response()->json(['message' => 'Material status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  material', $e->getMessage()], 500);
        }
        
    }
}

