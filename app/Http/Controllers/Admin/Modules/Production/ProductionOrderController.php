<?php

namespace App\Http\Controllers\Admin\Modules\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionOrder;
use App\Models\ProductionProduct;
use App\Models\Product;
use App\Models\Stock;
use App\Models\ProductionLog;
use App\Models\Department;
use App\Models\FailedQc;
use App\Models\MaterialRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Helpers\ProductionHelper;


class ProductionOrderController extends Controller
{
    public function getData(Request $request)
    {
        try {
            $query = ProductionOrder::with('customer', 'products:id,status')
                ->withCount([
                    'products as production_product_count' => function ($q) {
                        $q->where('status', 1);
                    }
                ])
                ->orderByDesc('id');
                // ->whereIn('status', ['0', '1']);
    
            if ($request->boolean('ownData')) {
                $query->whereNull('quotation_id');
            } else {
                $query->whereNotNull('quotation_id');
            }
             if (!empty($request->search)) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {
              
                $q->whereHas('customer', function ($c) use ($search) {
                    $c->where('name', 'ILIKE', "%{$search}%");
                });

                // $q->orWhereHas('products', function ($p) use ($search) {
                //     $p->where('name', 'LIKE', "%{$search}%");
                // });

                $q->orWhere('batch_no', 'ILIKE', "%{$search}%");
            });
        }
    
            $orders = $query->paginate($request->input('limit', 10));
    
            return response()->json([
                'success' => true,
                'data' => $orders->items(),
                'total' => $orders->total(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch Production orders',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // public function getData(Request $request)
    // {
    //     try {
    //         $query = ProductionOrder::with('customer')
    //             ->orderByDesc('id')
    //             ->whereIn('status', ['0', '1']);
    
    //         if ($request->boolean('ownData')) {
    //             $query->whereNull('quotation_id');
    //         }else{
    //             $query->whereNotNull('quotation_id');
    //         }
    
    //         $orders = $query->paginate($request->input('limit', 10));
    
    //         return response()->json([
    //             'success' => true,
    //             'data' => $orders->items(),
    //             'total' => $orders->total(),
    //             'current_page' => $orders->currentPage(),
    //             'last_page' => $orders->lastPage(),
    //         ]);
    
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'error' => 'Failed to fetch Production orders',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }


    public function store(Request $request)
    {
        try{
            
            $request->validate([
                'quotation_id'       => 'nullable|exists:quotations,id',
                'batch_no'           => 'nullable|string|max:50',
                'product_ids'        => 'nullable',
                'priority'           => 'nullable|string|max:20',
                'customer_id'        => 'nullable|exists:customers,id',
                'commencement_date'  => 'nullable|date',
                'delivery_date'      => 'nullable|date',
                'sale_user_id'       => 'nullable|exists:sales_users,id',
                'unique_code'        => 'nullable|string|max:150|unique:production_orders,unique_code',
                'image'              => 'nullable|string|max:225',
                'revised'            => 'nullable|in:0,1',
                'status'             => 'nullable|in:0,1',
            ]);
            
            DB::beginTransaction();
            
            $prefix = "PO_";
            $last = ProductionOrder::latest()->first();
            $count = 0;
            
            if ($last && !empty($last->batch_no)) {
                $postCount = explode('_', $last->batch_no);
                $count = isset($postCount[1]) ? (int)$postCount[1] + 1 : 1;
            } else {
                $count = 1;
            }
            
            $batch_no = $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);

            $order = new ProductionOrder();
            $order->quotation_id      = $request->quotation_id;
            $order->batch_no          = $batch_no;
            $order->product_ids       = is_array($request->items) ? json_encode($request->items) : $request->items;
            $order->priority          = $request->priority;
            $order->customer_id       = $request->customer_id;
            $order->commencement_date = date('Y-m-d', strtotime($request->project_start_date));
            $order->delivery_date     = date('Y-m-d', strtotime($request->edd));
            // $order->sale_user_id      = $request->supervisor_id;
            $order->unique_code       = $request->unique_code;
            $order->image             = $request->image;
            $order->revised           = $request->revised ?? 0;
            $order->status            = $request->status ?? 0;
            $order->save();

            $products = $order->product_ids ? json_decode($order->product_ids, true) : [];
        
            foreach($products as $prod){
                if($prod['production_qty'] && $prod['production_qty'] > 0){
                    $product = new ProductionProduct();
                    $product->po_id                 = $order->id;
                    $product->group            = $prod['group'];
                    $product->product_id            = $prod['product_id'];
                    $product->size                  = $prod['size'];
                    $product->qty                   = $prod['production_qty'];
                    $product->item_name             = $prod['name'];
                    // $product->modal_no              = $prod['modal'];
                    // $product->view_type             = $prod['product_type'];
                    $product->start_date            = $order->commencement_date;
                    $product->delivery_date         = $order->delivery_date;
                    $product->revised               =  0;
                    $product->status                = 0;
                    $product->save();
                }
                 $log = new ProductionLog();
                $log->po_id = $order->id;
                $log->	production_product_id = $product->id;
                $log->from_stage = null;
                $log->to_stage = null;
                $log->action_by = auth()->id();
                $log->save();
            }
           
           
            
            DB::commit();

            return response()->json(['message' => 'Production order created successfully',
                'data' => $order]);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['error' => 'Failed to store production order', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            // $r = ProductionOrder::find($id);
            // if($r){
            //     $r->status = 0;
            //     $r->save();
            // }
            // $ree = ProductionProduct::where('po_id',$r->id)->get();
            // foreach($ree as $re){
            //     $re->status = 0;
            //     $re->save();
            // }
            $order =ProductionOrder::with('customer','customer.state','products:id,po_id,group,product_id,status')->find($id);
            
            // $order->status = 0;
            // $order->save();

            if(!$order){
                return response()->json(['error' => 'Production order not found'], 404);
            }
            
            
            return response()->json(['message' => 'Production order fetch  successfully',
                'data' => $order]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch production order', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id)
    {
        try{
            $request->validate([
                'quotation_id'       => 'nullable|exists:quotations,id',
                'batch_no'           => 'nullable|string|max:50',
                'product_ids'        => 'nullable',
                'priority'           => 'nullable|string|max:20',
                'customer_id'        => 'nullable|exists:customers,id',
                'commencement_date'  => 'nullable|date',
                'delivery_date'      => 'nullable|date',
                'sale_user_id'       => 'nullable|exists:sales_users,id',
                'unique_code'        => 'nullable|string|max:150|unique:production_orders,unique_code,' . $id,
                'image'              => 'nullable|string|max:225',
                'revised'            => 'nullable|in:0,1',
                'status'             => 'nullable|in:0,1',
            ]);
            
            DB::beginTransaction();
            
            $order = ProductionOrder::find($id);
            
            if(!$order){
                return response()->json(['error' => 'Production order not found'], 404);
            }

            $order->quotation_id      = $request->quotation_id;
            $order->batch_no          = $request->batch_no;
            $order->product_ids       = is_array($request->items) ? json_encode($request->items) : $request->items;
            $order->priority          = $request->priority;
            $order->customer_id       = $request->customer_id;
            $order->commencement_date = $request->commencement_date;
            $order->delivery_date     = $request->delivery_date;
            // $order->sale_user_id      = $request->supervisor_id;
            $order->unique_code       = $request->unique_code;
            $order->image             = $request->image;
            $order->revised           = $request->revised ?? $order->revised;
            $order->status            = $request->status ?? $order->status;
            $order->save();

            $products = $order->product_ids ? json_decode($order->product_ids, true) : [];
            $old_products = ProductionProduct::where('po_id', $order->id)->get();
            foreach($old_products as $old_prod){
                $old_prod->delete();
            }
            foreach($products as $prod){
                if($prod['production_qty'] && $prod['production_qty'] > 0){
                    $product = new ProductionProduct();
                    $product->po_id          = $order->id;
                    $product->group            = $prod['group'];
                    $product->product_id            = $prod['product_id'];
                    $product->size                  = $prod['size'];
                    $product->qty                   = $prod['production_qty'];
                    $product->item_name             = $prod['name'];
                    // $product->modal_no              = $prod['modal'];
                    // $product->view_type             = $prod['product_type'];
                    $product->start_date            = $order->commencement_date;
                    $product->delivery_date         = $request->delivery_date;
                    $product->revised               =  0;
                    $product->status                = 0;
                    $product->save();
                }
            }
            // $old_request = MaterialRequest::where('po_id', $order->id)->get();
            // foreach($old_request as $old_r){
            //     $old_r->delete();
            // }
            // foreach ($products as $prod) {
            //     if($prod['production_qty'] && $prod['production_qty'] > 0){
            //         MaterialRequest::create([
            //             'po_id'         => $order->id,
            //             'product_id'    => $prod['product_id'],
            //             "size"  => $prod['size'],
            //             'qty'           => $prod['production_qty'],
            //             'status'        => 0,
            //         ]);
            //     }
                
            // }
            
            DB::commit();

            return response()->json(['message' => 'Production order updated  successfully',
                'data' => $order]);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['error' => 'Failed to fetch production order', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{
            $order =ProductionOrder::find($id);

            if(!$order){
                return response()->json(['error' => 'Production order not found'], 404);
            }

            $order->delete();
            return response()->json(['message' => 'Production order deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch production order', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $order =ProductionOrder::find($id);

            if(!$order){
                return response()->json(['error' => 'Production order not found'], 404);
            }
            $order->status= !$order->status;
            $order->save();

            return response()->json(['message' => 'Production order status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  production order', $e->getMessage()], 500);
        }
        
    }
    
    public function ApproveAllProduct(Request $request)
    {
        try {
            $id = $request->id;
            $order = ProductionOrder::find($id);
    
            if (!$order) {
                return response()->json(['error' => 'Production order not found'], 404);
            }
    
            DB::beginTransaction();
    
            
            $order->product_ids = is_array($request->items)
                ? json_encode($request->items)
                : $request->items;
    
            $order->status = 1;
            $order->save();
    
            $products = ProductionProduct::where('po_id', $order->id)->get();
            $department = Department::orderBy('sequence', 'asc')->first();
            foreach ($products as $prod) {
                foreach ($request->items as $item) {
                    
                    if (
                        isset($item['group'], $item['product_id'], $item['production_qty']) &&
                        trim($prod->group) === $item['group'] &&
                        $prod->product_id == $item['product_id']
                    ) {
                        $prod->qty = $item['production_qty'];
                        $prod->status = 1;
                        $prod->department_id = $department->id;
                        
                        $prod->save();
                    }
                }
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Production product status updated successfully'
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to update production order',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function ApproveSingleProduct(Request $request)
    {
        try {
            $productId = $request->product_id;
            $poId = $request->po_id;
            $productionQty = $request->production_qty;
            $group = $request->group ?? null;
            
            
            $department = Department::orderBy('sequence', 'asc')->first();
            $product = ProductionProduct::where('po_id', $poId)
                ->where('product_id', $productId)
                ->when($group, function ($query) use ($group) {
                    $query->where('group', $group);
                })
                ->first();
    
            if (!$product) {
                return response()->json(['error' => 'Production product not found'], 404);
            }
    
            $product->qty = $productionQty;
            $product->status = 1;
            $product->department_id = $department->id;
            $product->save();
    
            $productionOrder = ProductionOrder::find($poId);
            if ($productionOrder && $productionOrder->product_ids) {
                $products = json_decode($productionOrder->product_ids, true) ?? [];
    
                foreach ($products as &$pro) {
                    if (
                        isset($pro['product_id']) &&
                        $pro['product_id'] == $productId &&
                        (!isset($group) || $pro['group'] == $group)
                    ) {
                        $pro['production_qty'] = $productionQty;
                        $pro['status'] = 1;
                    }
                }
    
                $productionOrder->product_ids = json_encode($products);
                
                $productionOrder->status = 2;
                $productionOrder->save();
            }
    
            return response()->json([
                'message' => 'Production product approved and updated successfully',
                'data' => $product
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to approve production product',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    
    public function getPreviousPO(Request $request)
    {
        try {
            $id = $request->id;
            $order_id = $request->orderId;
    
            $poQuery = ProductionOrder::where('quotation_id', $id);
    
            if ($order_id) {
                $poQuery->where('id', '!=', $order_id);
            }
    
            $po_ids = $poQuery->pluck('id');
    
            if ($po_ids->isEmpty()) {
                return response()->json([
                    'message' => 'No previous production orders found for this quotation',
                    'data' => []
                ], 200);
            }
    
            $products = ProductionProduct::select(
                    'product_id',
                    'group',
                    'status',
                    DB::raw('SUM(qty) as total_qty')
                )
                ->whereIn('po_id', $po_ids)
                ->groupBy('product_id', 'group','status')
                ->get();
    
            return response()->json([
                'message' => 'Previous production products fetched successfully',
                'data' => $products
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch production order',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    
    public function storeOwnProductionProduct(Request $request)
    {
        // return response()->json([
        //         'success' => true,
        //         'data' => $request->all(),
        //     ]);
        try{
            
            $request->validate([
                'quotation_id'       => 'nullable|exists:quotations,id',
                'batch_no'           => 'nullable|string|max:50',
                'product_ids'        => 'nullable',
                'priority'           => 'nullable|string|max:20',
                'customer_id'        => 'nullable|exists:customers,id',
                'commencement_date'  => 'nullable|date',
                'delivery_date'      => 'nullable|date',
                'sale_user_id'       => 'nullable|exists:sales_users,id',
                'unique_code'        => 'nullable|string|max:150|unique:production_orders,unique_code',
                'image'              => 'nullable|string|max:225',
                'revised'            => 'nullable|in:0,1',
                'status'             => 'nullable|in:0,1',
            ]);
            
            DB::beginTransaction();
            
            $prefix = "PO_";
            $last = ProductionOrder::latest()->first();
            $count = 0;
            
            if ($last && !empty($last->batch_no)) {
                $postCount = explode('_', $last->batch_no);
                $count = isset($postCount[1]) ? (int)$postCount[1] + 1 : 1;
            } else {
                $count = 1;
            }
            
            $batch_no = $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);

            $order = new ProductionOrder();
            $order->quotation_id      = $request->quotation_id;
            $order->batch_no          = $batch_no;
            $order->product_ids       = is_array($request->items) ? json_encode($request->items) : $request->items;
            $order->priority          = $request->priority;
            $order->customer_id       = $request->customer_id;
            $order->commencement_date = date('Y-m-d', strtotime($request->project_start_date));
            $order->delivery_date     = date('Y-m-d', strtotime($request->edd));
            // $order->sale_user_id      = $request->supervisor_id;
            $order->unique_code       = $request->unique_code;
            $order->image             = $request->image;
            $order->revised           = $request->revised ?? 0;
            $order->status            = $request->status ?? 0;
            $order->save();

            $products = $order->product_ids ? json_decode($order->product_ids, true) : [];
        
            foreach($products as $prod){
                if($prod['production_qty'] && $prod['production_qty'] > 0){
                    $product = new ProductionProduct();
                    $product->po_id                 = $order->id;
                    $product->group            = $prod['group'];
                    $product->product_id            = $prod['product_id'];
                    $product->size                  = $prod['size'];
                    $product->qty                   = $prod['production_qty'];
                    $product->item_name             = $prod['name'];
                    $product->start_date            = $order->commencement_date;
                    $product->delivery_date         = $order->delivery_date;
                    $product->revised               =  0;
                    $product->status                = 0;
                    $product->save();
                    
                $log = new ProductionLog();
                $log->po_id = $order->id;
                $log->	production_product_id = $product->id;
                $log->from_stage = null;
                $log->to_stage = null;
                $log->action_by = auth()->id();
                $log->save();
                }
                
            }
            // foreach ($products as $prod) {
            //     if($prod['production_qty'] && $prod['production_qty'] > 0){
            //         MaterialRequest::create([
            //             'po_id'         => $order->id,
            //             'product_id'    => $prod['product_id'],
            //             "size"  => $prod['size'],
            //             'qty'           => $prod['production_qty'],
            //             'status'        => 0,
            //         ]);
            //     }
                
            // }
            
            DB::commit();

            return response()->json(['message' => 'Production order created successfully',
                'data' => $order]);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['error' => 'Failed to store production order', $e->getMessage()], 500);
        }
        
    }


  public function productionBatches(Request $request)
    {
        try {
            // Fetch production orders
            $orders = ProductionOrder::with('customer:id,name')
                ->whereIn('status', ['1', '2'])
                ->orderBy('created_at', 'desc')
                ->get();
    
            
            $groupedOrders = $orders->groupBy(function ($item) {
                return $item->quotation_id ?? 'unknown';
            });
    
            
            $finalData = $groupedOrders->map(function ($batches, $quotationId) {
    
                $firstBatch = $batches->first();
                $customer = $firstBatch->customer ?? (object)[
                    'id'   => null,
                    'name' => 'In House'
                ];
    
                return [
                    'quotation_id'       => $quotationId === 'unknown' ? 'Unknown' : $quotationId,
                    'customer_id'        => $firstBatch->customer_id,
                    'customer'           => $customer ,
    
                    // List of batches under this quotation
                    'batches' => $batches->map(function ($batch) {
                        return [
                            'id'                => $batch->id,
                            'batch_no'          => $batch->batch_no,
                            'quotation_id'      => $batch->quotation_id ?? 'Unknown',
                            'priority'          => $batch->priority,
                            'status'            => $batch->status,
                            // // 'commencement_date' => $batch->commencement_date,
                            // 'delivery_date'     => $batch->delivery_date,
                        ];
                    })->values(),
                ];
            })->values();
    
            return response()->json([
                'success' => true,
                'data'    => $finalData
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Error fetching production orders',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    
   public function batchProducts(Request $request)
    {
        // $pp = ProductionOrder::whereIn('id',['8','16','18','19','20',])->get();
        // foreach($pp as $p){
        //     $p->status = 3;
        //     $p->save();
        // }
        try {
           
            $request->validate([
                'id' => 'required|integer|exists:production_orders,id'
            ]);
    
            $poId = $request->id;
    
            // Fetch products grouped by department
            $products = ProductionProduct::with([
                    'attachments',
                    'messages',
                    'messages.user',
                    'materialRequest',
                    'tentativeItems',
                    'tentativeItems.material',
                    'product'
                ])
                ->where('po_id', $poId)
                ->where('status', 1)
                ->orderBy('department_id') 
                ->get()
                ->groupBy('department_id'); 
    
            return response()->json([
                'success'  => true,
                'data'     => $products
            ]);
    
        } catch (\Exception $e) {
    
            return response()->json([
                'success' => false,
                'error'   => 'Error fetching batch products.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    

    public function changeDepartment(Request $request)
    {
        $validated = $request->validate([
            'pp_id'         => 'required|exists:production_products,id',
            'department_id' => 'required|exists:departments,id',
            'remark'        => 'nullable|string|max:255',
        ]);
    
        DB::beginTransaction();
    
        try {
            $product = ProductionProduct::findOrFail($validated['pp_id']);
    
            $oldDepartment = $product->department_id;
            $newDepartment = $validated['department_id'];
    
            $product->department_id = $newDepartment;
            $product->save();
    
            // Log Activity
            ProductionHelper::logProductionActivity(
                product: $product,
                fromStage: $oldDepartment,
                toStage: $newDepartment,
                remark: $validated['remark'] ?? 'Department Changed'
            );
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Department updated successfully',
            ]);
    
        } catch (\Exception $e) {
    
            DB::rollBack();
    
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function markReadyForDelivery(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:production_products,id',
        ]);
    
        DB::beginTransaction();
    
        try {
    
            $product = ProductionProduct::findOrFail($request->id);
    
            // Update production product status
            $product->status = 2;
            $product->save();
            
            // Update Product Inventory
            $prod = Product::findOrFail($product->product_id);
    
            // Insert stock entry
            Stock::create([
                'in_stock'   => intval($product->qty),
                'product_id' => $product->product_id,
                'available_qty' => intval($prod->available_qty) + intval($product->qty)

            ]);
            $prod->available_qty += $product->qty;
            $prod->save();
    
    
            // Check if all PO items are completed
            $allProducts = ProductionProduct::where('po_id', $product->po_id)->get();
            $allComplete = $allProducts->every(fn ($p) => $p->status == 2);
    
            if ($allComplete) {
                $batch = ProductionOrder::find($product->po_id);
    
                if ($batch) {
                    $batch->status = 3;
                    $batch->save();
                }
            }
            
             $log = new ProductionLog();
                $log->po_id = $product->po_id;
                $log->	production_product_id = $product->id;
                $log->from_stage = $product->department_id;
                $log->to_stage = null;
                $log->status = 2;
                $log->action_by = auth()->id();
                $log->save();
            
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Product marked as ready for delivery successfully.',
                'data'    => $product
            ]);
    
        } catch (\Exception $e) {
    
            DB::rollBack();
    
            return response()->json([
                'success' => false,
                'message' => 'Error updating delivery status.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }




    public function setUpdatedValue(Request $request)
    {
        try {
            $validated = $request->validate([
                'pp_id'         => 'required|exists:production_products,id',
                'supervisor_id' => 'nullable|exists:users,id',
                'priority'      => 'nullable|in:High,Medium,Low',
            ]);
    
            $product = ProductionProduct::findOrFail($validated['pp_id']);
    
            
            $storeData = [];
    
            if ($request->filled('supervisor_id')) {
                $storeData['supervisor_id'] = $request->supervisor_id;
                $product->supervisor_id = $request->supervisor_id;
            }
    
            if ($request->filled('priority')) {
                $storeData['priority'] = $request->priority;
                $product->priority = $request->priority; 
            }
    
          
            if (!empty($storeData)) {
                $product->save();
            }
    
            return response()->json([
                'success' => true,
                'data'    => $storeData
            ]);
    
        } catch (\Exception $e) {
    
            return response()->json([
                'success' => false,
                'error'   => 'Failed to update values.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function failedQc(Request $request)
    {
        try {
            $validated = $request->validate([
                'pp_id'   => 'required|exists:production_products,id',
                'reason'  => 'required|string|max:500',
                'doc'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            ]);
    
            $qc = new FailedQc();
            $qc->pp_id = $validated['pp_id'];
            $qc->reason = $validated['reason'];
            $qc->action_by = auth()->id();
    
            // File Upload
            if ($request->hasFile('doc')) {
                $file = $request->file('doc');
                $filename = time().'_'.rand(100000,999999).'.'.$file->getClientOriginalExtension();
                $path = 'uploads/production/failedQc/';
                $file->move(public_path($path), $filename);
                $qc->doc = "/$path$filename";
            }
    
            $product = ProductionProduct::findOrFail($validated['pp_id']);
            $oldDepartment = $product->department_id;
    
            $firstDepartment = Department::orderBy('sequence', 'asc')->first();
            $product->department_id = $firstDepartment->id;
            $product->save();
    
            // Log movement
            ProductionHelper::logProductionActivity(
                product: $product,
                fromStage: $oldDepartment,
                toStage: $firstDepartment->id,
                remark: 'QC Failed - Sent to first department'
            );
    
           
            $qc->save();
    
            return response()->json([
                'success' => true,
                'message' => 'QC failed report saved successfully.',
                'data'    => $qc,
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Failed to save QC failed data.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function productProductionLog(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|integer|exists:production_products,id',
            ]);
    
            
            $logs = ProductionLog::with('fromStage:id,name','toStage:id,name','user:id,name')->where('production_product_id', $request->id)
                ->latest()
                ->get();
    
            return response()->json([
                'success' => true,
                'message' => 'Product production logs fetched successfully.',
                'data'    => $logs,
            ], 200);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request data.',
                'errors'  => $e->errors(),
            ], 422);
    
        } catch (\Exception $e) {
            \Log::error('Product Production Log Error', [
                'request_id' => $request->id ?? null,
                'error' => $e->getMessage(),
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product production logs.',
            ], 500);
        }
    }

}
