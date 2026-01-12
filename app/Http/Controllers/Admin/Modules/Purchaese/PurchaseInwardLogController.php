<?php

namespace App\Http\Controllers\Admin\Modules\Purchaese;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseInwardLog;
use App\Models\InwardPaymentLog;
use App\Models\MaterialLog;
use App\Models\Material;
use App\Models\PurchaseMaterial;
use App\Models\PurchaseOrder;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\DB;
use Auth;
class PurchaseInwardLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');

        $this->middleware('permission:qc_po.read')->only([
            'getData', 'search'
        ]);

        $this->middleware('permission:qc_po.create')->only([
            'store'
        ]);

        $this->middleware('permission:roles.update')->only([
            'edit', 'update'
        ]);
        $this->middleware('permission:qc_po.approve_qc')->only([
             'statusUpdate'
        ]);

        $this->middleware('permission:qc_po.delete')->only([
            'delete'
        ]);

        $this->middleware('permission:qc_po.upload_invoice')->only([
            'uploadInvoice'
        ]);
        
        $this->middleware('permission:vendor_invoices.collect_payment')->only([
            'storeInwardPayment'
        ]);
        
    }
    
    public function getData(Request $request)
    {
        try {
            $search = $request->search;
    
            $logs = PurchaseInwardLog::with(['purchaseOrder', 'purchaseOrder.vendor'])
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('vendor_invoice_no', 'ILIKE', "%{$search}%")  // Search invoice number
                          ->orWhereHas('purchaseOrder', function ($q2) use ($search) {
                              $q2->where('purchase_no', 'ILIKE', "%{$search}%")  // Search purchase number
                                 ->orWhereHas('vendor', function ($q3) use ($search) {
                                     $q3->where('name', 'ILIKE', "%{$search}%"); // Search vendor name
                                 });
                          });
                    });
                })
                ->orderBy('id', 'desc')
                ->paginate($request->per_page ?? 10);
    
            return response()->json($logs);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch Purchase inward logs',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    public function search(Request $request)
    {
        try {
            $query = PurchaseInwardLog::with('purchaseOrder','material')->orderBy('id', 'desc');

            if ($request->filled('purchase_no')) {
                $query->whereHas('purchaseOrder', function ($q) use ($request) {
                    $q->where('purchase_no', 'ILIKE', '%' . $request->purchase_no . '%');
                });
            }

            if ($request->filled('material')) {
                $query->whereHas('material', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->material . '%');
                });
            }

            if ($request->filled('qty')) {
                $query->where('qty', 'ILIKE', '%' . $request->qty . '%');
            }

            if ($request->filled('price')) {
                $query->where('price', 'ILIKE', '%' . $request->price . '%');
            }

            if ($request->filled('rate')) {
                $query->where('rate', 'ILIKE', '%' . $request->rate . '%');
            }

            if ($request->filled('invoice_no')) {
                $query->where('invoice_no', 'ILIKE', '%' . $request->invoice_no . '%');
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
        try {
            $validated = $request->validate([
               'id' => 'required|unique:purchase_inward_logs,purchase_order_id,' . $request->id,
                'vendor_id'          => 'required|exists:vendors,id',
                // 'credit_days'        => 'nullable|integer|min:0',
                // 'items'              => 'required|array|min:1',
                // 'items.*.material_id'=> 'required|exists:materials,id',
                // 'items.*.qty'        => 'required|numeric|min:1',
                // 'items.*.rate'       => 'required|numeric|min:0',
                // 'items.*.total'      => 'required|numeric|min:0',
                'subtotal'           => 'required|numeric|min:0',
                'discount'           => 'nullable|numeric|min:0',
                'additional_charges' => 'nullable|numeric|min:0',
                'gst_percentage'     => 'nullable|numeric|min:0',
                'gst_amount'         => 'nullable|numeric|min:0',
                'grand_total'        => 'required|numeric|min:0',
                'vendor_invoice_no'  => 'nullable|string|max:100',
                'vendor_invoice_date'=> 'nullable|date',
            ],
            [
                'id.required'                      => 'The purchase order ID is required.',
                'id.unique'                        => 'This purchase order has already been processed.',
                
            ]
        );
        
        $purchase = PurchaseOrder::find($validated['id']);
    
           
            $inward = new PurchaseInwardLog();
            $inward->purchase_order_id   = $validated['id'];
            $inward->vendor_id           = $validated['vendor_id'];
            $inward->credit_days         = $validated['credit_days'] ?? 0;
            $inward->subtotal            = $validated['subtotal'];
            $inward->discount            = $validated['discount'] ?? 0;
            $inward->carriage_amount     = $validated['additional_charges'] ?? 0;
            $inward->gst_per             = $validated['gst_percentage'] ?? 0;
            $inward->gst_amount          = $validated['gst_amount'] ?? 0;
            $inward->grand_total         = $validated['grand_total'];
            $inward->material_items      = $request->items;
            $inward->vendor_invoice_no   = $validated['vendor_invoice_no'] ?? null;
            $inward->receiving_date	  = date('Y-m-d');
            $inward->credit_days = $purchase->credit_days;
            $inward->expected_delivery_date = $purchase->expected_delivery_date	;
            
            $inward->vendor_invoice_date = $validated['vendor_invoice_date'] ?? null;
            $inward->created_by          = auth()->id();
            $inward->status              = $request->status ?? 0;
            $inward->save(); 
        
        
            $items = is_string($request->items)
                ? json_decode($request->items, true)
                : $request->items;
        
            if (!is_array($items)) {
                return response()->json(['error' => 'Invalid items format'], 400);
            }
        
            // Collect all material IDs
            $materialIds = array_column($items, 'material_id');
        
            // Load materials in ONE query
            $materials = Material::whereIn('id', $materialIds)->get()->keyBy('id');
        
        
            foreach ($items as $item) {
        
                $materialId = $item['material_id'] ?? null;
                $qty        = (int)($item['qty'] ?? 0);
        
                $pm = new PurchaseMaterial();
                $pm->purchase_order_id = $inward->purchase_order_id;
                $pm->material_id       = $materialId;
                $pm->qty               = $qty;
                $pm->size              = $item['size'] ?? null;
                $pm->rate              = $item['rate'] ?? 0;
                $pm->save();
        
                $m = $materials[$materialId] ?? null;
        
                $previous = $m ? (int)($m->available_qty ?? $m->opening_stock) : 0;
                $new      = $previous + $qty;
        
                $log = new MaterialLog();
                $log->material_id    = $materialId;
                $log->type           = "IN";
                $log->qty            = $qty;
                $log->previous_qty   = $previous;
                $log->new_qty        = $new;
                $log->reference_type = "Purchase order";
                $log->reference_id   = $inward->purchase_order_id;
                $log->action_by      = auth()->id();
                $log->save();
        
                if ($m) {
                    $m->available_qty = $new;
                    $m->save();
                }
            }
        
            
            if ($purchase) {
                $purchase->quality_status = "1";
                $purchase->save();
            }
        
            DB::commit();
            return response()->json([
                'message' => 'Purchase inward log created successfully',
                'data'    => $inward,
            ]);
        }
        catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error'   => 'Failed to store Purchase inward log',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function edit(Request $request, $id)
    {
        try {
            $log = PurchaseInwardLog::with(['vendor','vendor.state', 'purchaseOrder'])
                ->where('purchase_order_id', $id)
                ->first();
    
            if (!$log) {
               return response()->json([
                'message' => 'Purchase inward log fetched successfully',
                'data' => [],
            ]);
            }
    
            $materialItems = [];
            if ($log->material_items) {
                $materialItems = json_decode($log->material_items, true);
            };
    
            // Append material items as a new property to the response
            $log->material_items = $materialItems;
    
            return response()->json([
                'message' => 'Purchase inward log fetched successfully',
                'data' => $log,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch Purchase inward log',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try{
            $request->validate([
                'purchase_order_id' => 'required|exists:purchase_orders,id',
                'material_id'       => 'required|exists:materials,id',
                'qty'               => 'required|integer|min:1',
                'price'             => 'nullable|numeric',
                'rate'              => 'nullable|numeric',
                'invoice_no'        => 'nullable|string|max:50',
            ]);
            
            $log =PurchaseInwardLog::find($id);
            
            if(!$log){
                return response()->json(['error' => 'Purchase inward log not found'], 404);
            }
            $log->purchase_order_id = $request->purchase_order_id;
            $log->material_id       = $request->material_id;
            $log->qty               = $request->qty;
            $log->price             = $request->price;
            $log->rate              = $request->rate;
            $log->invoice_no        = $request->invoice_no;
            $log->status = $request->status ?? $log->status;
            $log->save();

            return response()->json(['message' => 'Purchase inward log updated  successfully',
                'data' => $log]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch purchase inward log', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{
            $log =PurchaseInwardLog::find($id);

            if(!$log){
                return response()->json(['error' => 'Purchase inward log not found'], 404);
            }

            $log->delete();
            return response()->json(['message' => 'Purchase inward log deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch purchase inward log', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $log =PurchaseInwardLog::find($id);

            if(!$log){
                return response()->json(['error' => 'Purchase inward log not found'], 404);
            }
            $log->status= !$log->status;
            $log->save();

            return response()->json(['message' => 'Purchase inward log status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  purchase inward log', $e->getMessage()], 500);
        }
        
    }
    
     public function storeInwardPayment(Request $request)
    {
        try {
            
            $request->validate([
                'invoice_id' => 'required|exists:purchase_inward_logs,id',
                'payment_mode' => 'required|string|max:50',
                'amount' => 'required|numeric|min:0',
                'reference_no' => 'nullable|string|max:255',
            ]);
    
           
            $inward = PurchaseInwardLog::find($request->invoice_id);
            if (!$inward) {
                return response()->json([
                    'error' => 'Purchase inward not found or ID is invalid.',
                ], 404);
            }
           
            $currentDue = $inward->due_amount ?? $inward->grand_total;
            $dueAmount = $currentDue - $request->amount;
    
         
            if ($dueAmount < 0) {
                return response()->json([
                    'error' => 'Paid amount cannot exceed total payable amount.',
                ], 422);
            }
    
            $inward->due_amount = $dueAmount;
            $inward->status = ($dueAmount == 0) ? 2 : 1;
            $inward->save();
    
        
            $payment = new InwardPaymentLog();
            $payment->inward_id = $inward->id;
            $payment->payment_mode = $request->payment_mode;
            $payment->paid_amount = $request->amount;
            $payment->due = $dueAmount;
            $payment->date = date('Y-m-d');
            $payment->added_by = auth()->id();
            $payment->reference_no = $request->reference_no;
            $payment->save();
    
            return response()->json([
                'message' => 'Payment recorded successfully',
                'data' => $inward,
                
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to record payment',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function getPaymentData(Request $request)
    {
        try {
           
            $request->validate([
                'id' => 'required|integer|exists:purchase_inward_logs,id',
            ]);
    
          
            $payments = InwardPaymentLog::where('inward_id', $request->id)
                ->orderBy('id', 'desc')
                ->get();
    
            
            return response()->json([
                'status' => true,
                'message' => 'Payment data fetched successfully!',
                'data' => $payments,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => 'Failed to fetch payment data',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
    
    
    public function uploadInvoice(Request $request, $id)
    {
        try {
            $request->validate([
                'id' => 'required|integer|exists:purchase_inward_logs,id',
                'invoice' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            ]);
    
            // Find related record
            $inward = PurchaseInwardLog::find($id);
    
            if (!$inward) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inward record not found.',
                ], 404);
            }
    
            // Handle file upload
            if ($request->hasFile('invoice')) {
                $file = $request->file('invoice');
                $fileName = time() . '_' . rand(10000000, 99999999) . '.' . $file->getClientOriginalExtension();
                $uploadPath = public_path('uploads/purchaseInvoice/');
    
                // Ensure directory exists
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
    
                $file->move($uploadPath, $fileName);
                $filePath = '/uploads/purchaseInvoice/' . $fileName;
    
                // Save file path in DB
                $inward->document = $filePath;
                $inward->save();
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Invoice uploaded successfully.',
                'file_url' => asset($inward->document),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload invoice.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


}
