<?php

namespace App\Http\Controllers\Admin\Modules\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionQualityCheck;
use Illuminate\Validation\Rule;

class ProductionQualityCheckController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $quality_checks = ProductionQualityCheck::with('productionOrder')->orderBy('id','desc')->paginate(10);
            return response()->json($quality_checks);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch product quality data'], 500);
        }
        
    }

    public function search(Request $request)
    {
        try{
            
            $query = ProductionQualityCheck::with('productionOrder')->orderBy('id','desc');

            if($request->filled('unique_code')){
                $query->whereHas('productionOrder', function ($q) use ($request){
                    $q->where('unique_code', 'ILIKE', '%'. $request->unique_code  .'%');
                });
            }

            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }

            $quality_checks = $query->paginate(10);

            return response()->json($quality_checks);

        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch product quality data'], 500);
        }
    }

    public function store(Request $request)
    {
        try{
            
            $request->validate([
                'po_id' => ['required', Rule::unique('production_quality_checks', 'po_id')->whereNull('deleted_at'),],
            ]);
            $imgArray = [];
            $quality_check = new ProductionQualityCheck();

            $quality_check->po_id = $request->po_id;
            foreach ($request->file('image') as $key => $img) {
                $randomName = rand(10000000, 99999999);
                $imageName = time() . '_' . $randomName . '.' . $img->getClientOriginalExtension();
                $img->move(public_path('uploads/production_quality'), $imageName);

                $img_path = '/uploads/production_quality/' . $imageName;
                $message = $request->image_message[$key] ?? '';

                $imgArray[] = [
                    'path' => $img_path,
                    'message' => $message
                ];
            }

            $quality_check->image = !empty($imgArray) ? json_encode($imgArray) : null;
            if($request->comment)
            {
                $quality_check->comment = $request->comment;
            }
            $quality_check->status = $request->status ?? 0;
            $quality_check->save();
            return response()->json(['message' => 'Product quality  created successfully',
                'data' => $quality_check]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store product quality data', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $quality_check = ProductionQualityCheck::find($id);

            if(!$quality_check){
                return response()->json(['error' => 'Product quality data not found'], 404);
            }
            return response()->json(['message' => 'Product quality data fetch  successfully',
                'data' => $quality_check]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch product quality data', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id)
    {
        try{
            $request->validate([
                'po_id' => ['required',
                        Rule::unique('production_quality_checks', 'po_id')
                        ->ignore($id) 
                        ->whereNull('deleted_at'),]
            ]);
            $quality_check = ProductionQualityCheck::find($id);

            if(!$quality_check){
                
            $qc = new ProductionQualityCheck();
            $qc->po_id = $request->po_id;
            foreach ($request->file('image') as $key => $img) {
                $randomName = rand(10000000, 99999999);
                $imageName = time() . '_' . $randomName . '.' . $img->getClientOriginalExtension();
                $img->move(public_path('uploads/production_quality'), $imageName);

                $img_path = '/uploads/production_quality/' . $imageName;
                $message = $request->image_message[$key] ?? '';

                $imgArray[] = [
                    'path' => $img_path,
                    'message' => $message
                ];
            }

            $quality_check->image = !empty($imgArray) ? json_encode($imgArray) : null;
            if($request->comment)
            {
                $qc->comment = $request->comment;
            }
            $qc->status = $request->status ?? 0;
            $qc->save();
            return response()->json(['message' => 'Product quality  created successfully',
                'data' => $qc]);
            }

            $quality_check->po_id = $request->po_id;
            foreach ($request->file('image') as $key => $img) {
                $randomName = rand(10000000, 99999999);
                $imageName = time() . '_' . $randomName . '.' . $img->getClientOriginalExtension();
                $img->move(public_path('uploads/production_quality'), $imageName);

                $img_path = '/uploads/production_quality/' . $imageName;
                $message = $request->image_message[$key] ?? '';

                $imgArray[] = [
                    'path' => $img_path,
                    'message' => $message
                ];
            }

            $quality_check->image = !empty($imgArray) ? json_encode($imgArray) : null;
            if($request->comment)
            {
                $quality_check->comment = $request->comment;
            }

            $quality_check->status = $request->status ?? $quality_check->status;
            $quality_check->save();

            return response()->json(['message' => 'Product quality  updated  successfully',
                'data' => $quality_check]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch Pproduct quality data', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{
            $quality_check = ProductionQualityCheck::find($id);

            if(!$quality_check){
                return response()->json(['error' => 'Product quality data not found'], 404);
            }

            $quality_check->delete();
            return response()->json(['message' => 'Product quality  deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch product quality data', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $quality_check = ProductionQualityCheck::find($id);

            if(!$quality_check){
                return response()->json(['error' => 'Product quality data  not found'], 404);
            }
            $quality_check->status= !$quality_check->status;
            $quality_check->save();

            return response()->json(['message' => 'Product quality  status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  product quality data', $e->getMessage()], 500);
        }
        
    }
}
