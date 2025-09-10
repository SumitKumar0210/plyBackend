<?php

namespace App\Http\Controllers\Admin\Modules\Purchaese;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $purchaseOrders = PurchaseOrder::with('vendor','department')->orderBy('id','desc')->paginate(10);
            return response()->json($purchaseOrders);
        }catch(\Exception $e){
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
                'total' => 'required|numeric|min:0',
                'gst_per' => 'required|numeric|min:0|max:100',
                'gst_amount' => 'required|numeric|min:0',
                'cariage_amount' => 'nullable|numeric|min:0',
                'subtotal' => 'required|numeric|min:0',
                'grand_total' => 'required|numeric|min:0',
                'expected_delivery_date' => 'required|date|after_or_equal:today',
                // 'order_date' => 'required|date',
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
                // 'quality_status' => 'required|string|in:Pending,Approved,Rejected',
                // 'status' => 'nullable|integer|in:0,1',
            ]);

            $purchaseOrder = new PurchaseOrder();

            $purchaseOrder->vendor_id = $request->vendor_id;
            $purchaseOrder->total = $request->total;
            $purchaseOrder->gst_per = $request->gst_per;
            $purchaseOrder->gst_amount = $request->gst_amount;
            $purchaseOrder->cariage_amount = $request->cariage_amount;
            $purchaseOrder->subtotal = $request->subtotal;
            $purchaseOrder->grand_total = $request->grand_total;
            $purchaseOrder->expected_delivery_date = $request->expected_delivery_date;
            $purchaseOrder->order_date = date('Y-m-d');
            $purchaseOrder->credit_days = $request->credit_days;
            $purchaseOrder->material_items = json_encode($request->material_items);
            $purchaseOrder->term_and_conditions = $request->term_and_conditions;
            $purchaseOrder->purchase_no = $request->purchase_no;
            $purchaseOrder->department_id = $request->department_id;
            $purchaseOrder->quality_status = $request->quality_status;
            $purchaseOrder->created_by = Auth::id();
            $purchaseOrder->status = $request->status ?? 0;
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
            $purchaseOrder =PurchaseOrder::find($id);

            if(!$purchaseOrder){
                return response()->json(['error' => 'Purchase order not found'], 404);
            }
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
                'total' => 'required|numeric|min:0',
                'gst_per' => 'required|numeric|min:0|max:100',
                'gst_amount' => 'required|numeric|min:0',
                'cariage_amount' => 'nullable|numeric|min:0',
                'subtotal' => 'required|numeric|min:0',
                'grand_total' => 'required|numeric|min:0',
                'expected_delivery_date' => 'required|date|after_or_equal:today',
                // 'order_date' => 'required|date',
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
                // 'quality_status' => 'required|string|in:Pending,Approved,Rejected',
                'status' => 'nullable|integer|in:0,1',
            ]);
            $purchaseOrder =PurchaseOrder::find($id);
            
            if(!$purchaseOrder){
                return response()->json(['error' => 'Purchase order not found'], 404);
            }
            $purchaseOrder->vendor_id = $request->vendor_id;
            $purchaseOrder->total = $request->total;
            $purchaseOrder->gst_per = $request->gst_per;
            $purchaseOrder->gst_amount = $request->gst_amount;
            $purchaseOrder->cariage_amount = $request->cariage_amount;
            $purchaseOrder->subtotal = $request->subtotal;
            $purchaseOrder->grand_total = $request->grand_total;
            $purchaseOrder->expected_delivery_date = $request->expected_delivery_date;
            $purchaseOrder->order_date = $request->order_date;
            $purchaseOrder->credit_days = $request->credit_days;
            $purchaseOrder->material_items = json_encode($request->material_items);
            $purchaseOrder->term_and_conditions = $request->term_and_conditions;
            $purchaseOrder->purchase_no = $request->purchase_no;
            $purchaseOrder->department_id = $request->department_id;
            $purchaseOrder->quality_status = $request->quality_status;
            $purchaseOrder->status = $request->status ?? $purchaseOrder->status;
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
}
