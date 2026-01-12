<?php

namespace App\Http\Controllers\Admin\Modules\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionOrder;
use App\Models\ProductionProduct;
use App\Models\ProductionLog;
use App\Models\MaterialRequest;
use App\Models\Material;
use App\Models\MaterialLog;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class MaterialRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');

        $this->middleware('permission:material_request.read')->only([
            'getMaterialRequestData', 'getAllMaterialRequestData'
        ]);

        $this->middleware('permission:material_request.create')->only([
            'store'
        ]);
;

        $this->middleware('permission:material_request.approve|material_request.update')->only([
            'approveRequest'
        ]);
        
    }
    
   public function getMaterialRequestData(Request $request)
    {
        try {
           
           $pp_id =  $request->id;
           
           $materials = MaterialRequest::with('material')->where('pp_id',$pp_id)->get();
    
            return response()->json([
                'success' => true,
                'data'    => $materials,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Failed to fetch material request.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
   public function getAllMaterialRequestData(Request $request)
    {
        try {
            $search = $request->input('search');
            $pageLimit = $request->input('pageLimit', 10);
            $pageIndex = $request->input('pageIndex', 0);
    
            
            $uuidQuery = MaterialRequest::select('uuid', 'pp_id', 'created_at')
                ->distinct()
                ->orderBy('created_at', 'desc');
    
            
            if (!empty($search)) {
                $uuidQuery->where(function ($q) use ($search) {
                    $q->where('uuid', 'ILIKE', "%{$search}%")
                      ->orWhereHas('productionProduct', function ($pp) use ($search) {
                          $pp->where('item_name', 'ILIKE', "%{$search}%")
                             ->orWhere('group', 'ILIKE', "%{$search}%");
                      })
                      ->orWhereHas('material', function ($m) use ($search) {
                          $m->where('name', 'ILIKE', "%{$search}%");
                      });
                });
            }
    
            
            $paginatedUuids = $uuidQuery->paginate(
                $pageLimit, 
                ['*'], 
                'page', 
                $pageIndex + 1
            );
    
           
            $uuids = $paginatedUuids->pluck('uuid')->toArray();
    
            $groupedRequests = MaterialRequest::with([
                    'productionProduct.product',
                    'material'
                ])
                ->whereIn('uuid', $uuids)
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('uuid')
                ->map(function ($requests) {
                    $firstRequest = $requests->first();
                    $pp = $firstRequest->productionProduct;
    
                    return [
                        'id' => $firstRequest->id,
                        'uuid' => $firstRequest->uuid,
                        'pp_id' => $firstRequest->pp_id,
                        'item_name' => $pp->item_name ?? null,
                        'size' => $pp->size ?? null,
                        'qty' => $pp->qty ?? null,
                        'start_date' => $pp->start_date ?? null,
                        'delivery_date' => $pp->delivery_date ?? null,
                        'product' => $pp->product ?? null,
                        'material_request' => $requests->map(function ($req) {
                            return [
                                'id' => $req->id,
                                'qty' => $req->qty,
                                'status' => $req->status,
                                'created_at' => $req->created_at,
                                'material' => $req->material ? [
                                    'id' => $req->material->id,
                                    'name' => $req->material->name,
                                    'size' => $req->material->size,
                                    'image' => $req->material->image,
                                    'available_qty' => $req->material->available_qty,
                                    'remark' => $req->material->remark,
                                ] : null
                            ];
                        })->values()->all()
                    ];
                })
                ->values();
    
            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $groupedRequests,
                    'current_page' => $paginatedUuids->currentPage(),
                    'last_page' => $paginatedUuids->lastPage(),
                    'per_page' => $paginatedUuids->perPage(),
                    'total' => $paginatedUuids->total(),
                ]
            ]);
    
        } catch (\Exception $e) {
            \Log::error('Material Request Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch material request.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

   public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'pp_id'        => 'required|exists:production_products,id',
                'material_id'  => 'required|array|min:1',
                'material_id.*'=> 'required|integer|exists:materials,id',
                'qty'          => 'required|array|min:1',
                'qty.*'        => 'required|integer|min:1',
            ]);
            
    
            if (count($validated['material_id']) !== count($validated['qty'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'material_id and qty array length must match.',
                ], 422);
            }
    
            $ppId = $validated['pp_id'];
            $materialIds = $validated['material_id'];
            $quantities  = $validated['qty'];
    
            $inserted = [];
            
            $product = ProductionProduct::find($ppId);
    
            DB::beginTransaction();
            $uuid = Str::uuid();
    
            foreach ($materialIds as $key => $materialId) {
                $data = MaterialRequest::create([
                    'pp_id'       => $ppId,
                    'material_id' => $materialId,
                    'qty'         => $quantities[$key],
                    'department_id'         => $product->department_id,
                    'uuid'         => $uuid,
                ]);
    
                $inserted[] = $data;
            }
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'data'    => $inserted,
            ], 200);
    
        } catch (\Exception $e) {
            DB::rollBack();
    
            return response()->json([
                'success' => false,
                'error'   => 'Failed to create material request.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function approveRequest(Request $request)
    {
        try {
            DB::beginTransaction();
    
            $uuid   = $request->id;
            $status = $request->status;
            $reason = $request->reason ?? null;
    
            $requests = MaterialRequest::where('uuid', $uuid)->get();
    
            foreach ($requests as $req) {
    
                $req->status = $status;
                if ($reason) {
                    $req->reason = $reason;
                }
                $req->save();
    
                $material = Material::find($req->material_id);
                if (!$material) {
                    continue;
                }
    
                $previousQty = $material->available_qty;
                $requestedQty = $req->qty;
    
                $newQty = max(0, $previousQty - $requestedQty);
    
                $material->available_qty = $newQty;
                $material->save();
    
                MaterialLog::create([
                    'material_id'    => $req->material_id,
                    'type'           => "OUT",
                    'qty'            => $requestedQty,
                    'previous_qty'   => $previousQty,
                    'new_qty'        => $newQty,
                    'reference_type' => "Material Request",
                    'reference_id'   => $req->id,
                    'action_by'      => auth()->id(),
                ]);
            }
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'data'    => $requests,
                'message' => 'Material request approved successfully.',
            ], 200);
    
        } catch (\Exception $e) {
    
            DB::rollBack();
    
            return response()->json([
                'success' => false,
                'error'   => 'Failed to approve material request.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



}