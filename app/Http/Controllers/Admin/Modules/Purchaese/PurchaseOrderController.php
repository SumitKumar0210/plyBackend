<?php

namespace App\Http\Controllers\Admin\Modules\Purchaese;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\PurchaseInwardLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');

        $this->middleware('permission:purchase_order.read')->only([
            'getData', 'search'
        ]);

        $this->middleware('permission:purchase_order.create')->only([
            'store'
        ]);

        $this->middleware('permission:purchase_order.update')->only([
            'edit', 'update', 'statusUpdate'
        ]);

        $this->middleware('permission:purchase_order.delete')->only([
            'delete'
        ]);
        
        $this->middleware('permission:purchase_order.approve')->only([
            'getApprovePOData','approvePO'
        ]);
        
    }
    
    public function getData(Request $request)
    {
        
        try {
            $search = $request->search;
    
            $purchaseOrders = PurchaseOrder::with(['vendor', 'department'])
                ->whereIn('status', ['0', '1'])
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($query) use ($search) {
                        $query->where('purchase_no', 'ILIKE', "%{$search}%")
                              ->orWhereHas('vendor', function ($vendorQuery) use ($search) {
                                  $vendorQuery->where('name', 'ILIKE', "%{$search}%");
                              });
                    });
                })
                ->orderBy('id', 'desc')
                ->paginate($request->per_page ?? 10);
    
            // Decode material_items safely
            $purchaseOrders->getCollection()->transform(function ($order) {
                $order->material_items = is_string($order->material_items)
                    ? json_decode($order->material_items, true)
                    : $order->material_items;
                return $order;
            });
    
            return response()->json($purchaseOrders);
        } catch (\Exception $e) {
            \Log::error("Error fetching purchase orders: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch purchase orders'], 500);
        }
    }
    
    public function getApprovePOData(Request $request)
    {
        try {
            $query = PurchaseOrder::with(['vendor', 'department', 'inward'])
                ->where('status', '3');
    
            if ($request->search) {
                $search = $request->search;
    
                $query->where(function ($q) use ($search) {
                    $q->where('purchase_no', 'ILIKE', "%{$search}%")
                      ->orWhere('order_date', 'ILIKE', "%{$search}%")
                      ->orWhereHas('vendor', function ($v) use ($search) {
                          $v->where('name', 'ILIKE', "%{$search}%");
                      });
                });
            }
            $purchaseOrders = $query
                ->orderBy('id', 'desc')
                ->paginate($request->per_page ?? 10);
    
            $purchaseOrders->getCollection()->transform(function ($order) {
                $order->material_items = is_string($order->material_items)
                    ? json_decode($order->material_items, true)
                    : $order->material_items;
    
                return $order;
            });
    
            return response()->json($purchaseOrders);
    
        } catch (\Exception $e) {
            \Log::error("Error fetching purchase orders: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch purchase orders'], 500);
        }
    }


    public function search(Request $request)
    {
        try {
            $query = PurchaseOrder::with('vendor', 'department')->orderBy('id', 'desc');

            if ($request->filled('vendor')) {
                $query->whereHas('vendor', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->vendor . '%');
                });
            }

            if ($request->filled('department')) {
                $query->whereHas('department', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->department . '%');
                });
            }

            if ($request->filled('purchase_no')) {
                $query->where('purchase_no', 'ILIKE', '%' . $request->purchase_no . '%');
            }

            if ($request->filled('order_date')) {
                $query->whereDate('order_date', $request->order_date);
            }

            if ($request->filled('expected_delivery_date')) {
                $query->whereDate('expected_delivery_date', $request->expected_delivery_date);
            }

            // Search by Status
            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }

            $purchaseOrders = $query->paginate(10);

            return response()->json($purchaseOrders);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch purchase orders',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try{
            
            $request->validate([
                'vendor_id' => 'required|integer|exists:vendors,id',
                // 'total' => 'required|numeric|min:0',
                'gst_percentage' => 'required|numeric|min:0|max:100',
                'gst_amount' => 'required|numeric|min:0',
                'cariage_amount' => 'nullable|numeric|min:0',
                'subtotal' => 'required|numeric|min:0',
                'grand_total' => 'required|numeric|min:0',
                'edd_date' => 'required|date|after_or_equal:today',
               
                // 'credit_days' => 'required|integer|min:0',
                // 'material_items' => 'required|array|min:1',
                // 'material_items.*.item_id' => 'required|integer|exists:items,id',
                // 'material_items.*.description' => 'required|string|max:255',
                // 'material_items.*.quantity' => 'required|numeric|min:1',
                // 'material_items.*.rate' => 'required|numeric|min:0',
                // 'material_items.*.amount' => 'required|numeric|min:0',
                // 'term_and_conditions' => 'nullable|string',
                // 'purchase_no' => 'required|string|max:50|unique:purchase_orders,purchase_no',
                // 'department_id' => 'required|integer|exists:departments,id',
                // 'status' => 'nullable|integer|in:0,1',
            ]);
            $prefix = "PO";
            $lastPO = PurchaseOrder::latest('id')->first();
            
            if (!$lastPO) {
                $po_no = 1;
            } else {
                
                $lastNumber = (int) str_replace($prefix, '', $lastPO->purchase_no);
                $po_no = $lastNumber + 1;
            }

            $purchaseOrder = new PurchaseOrder();

            $purchaseOrder->vendor_id = $request->vendor_id;
            // $purchaseOrder->total = $request->total;
            $purchaseOrder->gst_per = $request->gst_percentage;
            $purchaseOrder->gst_amount = $request->gst_amount;
            $purchaseOrder->cariage_amount = $request->additional_charges;
            $purchaseOrder->subtotal = $request->subtotal;
            $purchaseOrder->grand_total = $request->grand_total;
            $purchaseOrder->expected_delivery_date = date('Y-m-d',strtotime($request->edd_date));
            $purchaseOrder->order_date = date('Y-m-d');
            $purchaseOrder->credit_days = $request->credit_days;
            $purchaseOrder->material_items = json_encode($request->items);
            $purchaseOrder->term_and_conditions = $request->order_terms;
            $purchaseOrder->discount = $request->discount;
            $purchaseOrder->purchase_no = $prefix . str_pad($po_no, 3, '0', STR_PAD_LEFT);
            // $purchaseOrder->department_id = $request->department_id;
            $purchaseOrder->created_by = Auth::id();
            $purchaseOrder->status = ($request->is_draft == 'true')? '0':'1';
            $purchaseOrder->save();
            return response()->json(['message' => 'Purchase order created successfully',
                'data' => $purchaseOrder]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store purchase order', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $purchaseOrder =PurchaseOrder::with(['vendor', 'department', 'inward'])
                ->find($id);

            if(!$purchaseOrder){
                return response()->json(['error' => 'Purchase order not found'], 404);
            }
            
    
            // Decode material_items JSON safely
            $purchaseOrder->material_items = is_string($purchaseOrder->material_items)
                ? json_decode($purchaseOrder->material_items, true)
                : $purchaseOrder->material_items;
            return response()->json(['message' => 'Purchase order fetch  successfully',
                'data' => $purchaseOrder]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch purchase order', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id)
    {
       
        try{
            $request->validate([
                'vendor_id' => 'required|integer|exists:vendors,id',
                // 'total' => 'required|numeric|min:0',
                'gst_percentage' => 'required|numeric|min:0|max:100',
                'gst_amount' => 'required|numeric|min:0',
                'additional_charges' => 'nullable|numeric|min:0',
                'subtotal' => 'required|numeric|min:0',
                'grand_total' => 'required|numeric|min:0',
                'edd_date' => 'required|date|after_or_equal:today'
            ]);
            $purchaseOrder =PurchaseOrder::find($id);
            
            if(!$purchaseOrder){
                return response()->json(['error' => 'Purchase order not found'], 404);
            }
             $purchaseOrder->vendor_id = $request->vendor_id;
            // $purchaseOrder->total = $request->total;
            $purchaseOrder->gst_per = $request->gst_percentage;
            $purchaseOrder->gst_amount = $request->gst_amount;
            $purchaseOrder->cariage_amount = $request->additional_charges;
            $purchaseOrder->subtotal = $request->subtotal;
            $purchaseOrder->grand_total = $request->grand_total;
            $purchaseOrder->expected_delivery_date = date('Y-m-d',strtotime($request->edd_date));
            $purchaseOrder->order_date = date('Y-m-d');
            $purchaseOrder->credit_days = $request->credit_days;
            $purchaseOrder->material_items = json_encode($request->items);
            $purchaseOrder->term_and_conditions = $request->order_terms;
            $purchaseOrder->discount = $request->discount;
            $purchaseOrder->created_by = Auth::id();
            $purchaseOrder->status = ($request->is_draft == 'true')? '0':'1';
            $purchaseOrder->save();

            return response()->json(['message' => 'Purchase order updated  successfully',
                'data' => $purchaseOrder]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch purchase order', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{
            $purchaseOrder =PurchaseOrder::find($id);

            if(!$purchaseOrder){
                return response()->json(['error' => 'Purchase order not found'], 404);
            }
            $inward = PurchaseInwardLog::where('purchase_order_id',$id)->first();
            if($inward)$inward->delete();
            $purchaseOrder->delete();
            return response()->json(['message' => 'Purchase order deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch purchase order', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $purchaseOrder =PurchaseOrder::find($id);

            if(!$purchaseOrder){
                return response()->json(['error' => 'Purchase order not found'], 404);
            }
            $purchaseOrder->status= !$purchaseOrder->status;
            $purchaseOrder->save();

            return response()->json(['message' => 'Purchase order status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  purchase order', $e->getMessage()], 500);
        }
        
    }
    
    public function approvePO(Request $request)
    {
        try{
            $id = $request->id;
            $purchaseOrder =PurchaseOrder::find($id);

            if(!$purchaseOrder){
                return response()->json(['error' => 'Purchase order not found'], 404);
            }
            $purchaseOrder->status= '3';
            $purchaseOrder->save();

            return response()->json(['message' => 'The purchase order has been approved successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  purchase order', $e->getMessage()], 500);
        }
        
    }
}
