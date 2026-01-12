<?php

namespace App\Http\Controllers\Admin\Modules\Quotation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quotation;
use App\Models\QuotationProduct;
use App\Models\ProductionOrder;
use App\Models\ProductionProduct;
use App\Models\GeneralSetting;
use App\Models\Customer;
use App\Models\PublicLink;
use App\Models\ViewType;
use Illuminate\Validation\Rule;
use DB;

class QuotationOrderController extends Controller
{
    // public function getData(Request $request)
    // {
    //     try{
    //         $orders = Quotation::with('customer:id,name')->whereIn('status', ['0','1'])->select('id','product_ids','grand_total','created_at','status','priority','customer_id', 'batch_no')->orderBy('id','desc')->paginate($request->limit);
    //         return response()->json($orders);
    //     }catch(\Exception $e){
    //         return response()->json(['error' => 'Failed to fetch Quotation orders'], 500);
    //     }
        
    // }
    
    public function getData(Request $request)
    {
        try {
            $limit = $request->limit ?? 10;
    
            $query = Quotation::with('customer:id,name')
                ->select('id','product_ids','grand_total','created_at','status','priority','customer_id','batch_no','remark')
                ->orderBy('id', 'desc');
                
            $status = ['0', '1','3'];
           
            if ($request->approved == 'true') {
                $query->where('status','2');
            } else{
                $query->whereIn('status',$status);
            }
    
            // SEARCH
            if (!empty($request->search)) {
                $search = trim($request->search);
    
                $query->where(function ($q) use ($search) {
                    $q->where('batch_no', 'ILIKE', "%{$search}%")
                      ->orWhereHas('customer', function ($cust) use ($search) {
                          $cust->where('name', 'ILIKE', "%{$search}%");
                      });
                });
            }
    
          
    
            $orders = $query->paginate($limit);
    
            return response()->json($orders);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch Quotation orders',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    
    public function getQuotationData(Request $request)
    {
        try{
            $orders = Quotation::with('customer:id,name,address,city,zip_code,state_id','customer.state:id,name')->where('status', '2')->select('id','product_ids','grand_total','created_at','batch_no','status','priority','customer_id')->orderBy('id','desc')->get();
        $arr = ['data' => $orders];
            return response()->json($arr);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch Quotation orders'], 500);
        }
        
    }

    public function store(Request $request)
    {
       
        try{
            
            $request->validate([
                'priority'           => 'nullable|string|max:20',
                'customer_id'        => 'nullable|exists:customers,id',
                'commencement_date'  => 'nullable|date',
                'delivery_date'      => 'nullable|date',
                'sale_user_id'       => 'nullable|exists:sales_users,id',
                'unique_code'        => 'nullable|string|max:150|unique:quotations,unique_code',
                'image'              => 'nullable|string|max:225',
                'revised'            => 'nullable|in:0,1',
                'status'             => 'nullable|in:0,1',
            ]);
                // dd($request->all());
                DB::beginTransaction();

            $prefix = "Q_";
            $last = Quotation::latest()->first();
            $count = 0;
            
            if ($last && !empty($last->batch_no)) {
                $postCount = explode('_', $last->batch_no);
                $count = isset($postCount[1]) ? (int)$postCount[1] + 1 : 1;
            } else {
                $count = 1;
            }
            
            $batch_no = $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);
            $order = new Quotation();
            $order->batch_no          = $batch_no;
            $order->product_ids       = is_array($request->items) ? json_encode($request->items) : $request->items;
            $order->priority          = $request->priority;
            $order->customer_id       = $request->customer_id;
            $order->delivery_date     = $request->delivery_date;
            $order->sale_user_id      = $request->sale_user_id;
            $order->unique_code       = $request->unique_code;
            
            $order->order_terms        = $request->order_terms;
            $order->discount           = $request->discount ?? 0;
            $order->additional_charges = $request->additional_charges ?? 0;
            $order->gst_rate           = $request->gst_rate ?? 0;
            $order->sub_total          = $request->sub_total ?? 0;
            $order->grand_total        = $request->grand_total ?? 0;
            
            $order->revised           = $request->revised ?? 0;
            $order->status            = $request->is_draft == '0' ? 1 : 0;
            $order->save();

            $products = $order->product_ids ? json_decode($order->product_ids, true) : [];

            // Create an array to store updated product data (with image paths)
            $updatedProducts = [];

                foreach ($products as $index => $prod) {
                    $product = new QuotationProduct();
                    $product->quotation_id   = $order->id;
                    $product->product_id     = $prod['product_id'] ?? null;
                    $product->size           = $prod['size'] ?? null;
                    $product->qty            = $prod['qty'] ?? null;
                    $product->item_name      = $prod['name'] ?? null;
                    $product->model_no       = $prod['model'] ?? null;
                    $product->group_name    = $prod['group'] ?? null;
                    $product->unique_code    = $prod['unique_code'] ?? null;
                
                    // Default image path empty
                    $imagePath = null;
                
                    // Handle image upload
                    if ($request->hasFile("items.{$index}.document")) {
                        $image = $request->file("items.{$index}.document");
                        $randomName = rand(10000000, 99999999);
                        $imageName = time() . '_' . $randomName . '.' . $image->getClientOriginalExtension();
                        $image->move(public_path('uploads/quotation/'), $imageName);
                        $imagePath = '/uploads/quotation/' . $imageName;
                        $product->image = $imagePath;
                    } else {
                        $product->image = $prod['document'] ??'-';
                    }
                
                    $product->revised = 0;
                    $product->status = 1;
                    $product->save();
                
                    // Update the product data (for storing back in quotation.product_ids)
                    $prod['document'] = $imagePath ?? ($prod['document'] ?? null);
                    $updatedProducts[] = $prod;
                }
                
                // Update quotation’s product_ids with new image paths
                $order->product_ids = json_encode($updatedProducts);
                $order->save();
            
             DB::commit();

            return response()->json(['message' => 'Quotation order created successfully',
                'data' => $order]);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['error' => 'Failed to store quotation order', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $order =Quotation::find($id);
            // $order->status = '1';
            // $order->save();

            if(!$order){
                return response()->json(['error' => 'Quotation order not found'], 404);
            }
            return response()->json(['message' => 'Quotation order fetch  successfully',
                'data' => $order]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch quotation order', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id)
    {
        try {
            //  Log raw request data for debugging
            \Log::info('=== Update Quotation Request ===', [
                'id' => $id,
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'all_keys' => array_keys($request->all()),
            ]);
    
            $request->validate([
                'priority'           => 'nullable|string|max:20',
                'customer_id'        => 'nullable|exists:customers,id',
                'commencement_date'  => 'nullable|date',
                'delivery_date'      => 'nullable|date',
                'unique_code'        => 'nullable|string|max:150|unique:quotations,unique_code,' . $id,
                'image'              => 'nullable|string|max:225',
                'revised'            => 'nullable|in:0,1',
                'status'             => 'nullable|in:0,1',
            ]);
    
            DB::beginTransaction();
    
            $order = Quotation::find($id);
    
            if (!$order) {
                return response()->json(['error' => 'Quotation order not found'], 404);
            }
    
            // Update main quotation fields
            $order->priority          = $request->priority;
            $order->customer_id       = $request->customer_id;
            $order->commencement_date = $request->commencement_date ?? $request->quote_date;
            $order->delivery_date     = $request->delivery_date;
            $order->sale_user_id      = $request->sale_user_id ?? null;
            $order->unique_code       = $request->unique_code;
            $order->order_terms       = $request->order_terms;
            $order->discount          = $request->discount ?? 0;
            $order->additional_charges = $request->additional_charges ?? 0;
            $order->gst_rate          = $request->gst_rate ?? 0;
            $order->sub_total         = $request->sub_total ?? 0;
            $order->grand_total       = $request->grand_total ?? 0;
            $order->revised           = $request->revised ?? 0;
            if($order->revised == '1'){
                $order->status = '1';
            }else{
                $order->status            = $request->is_draft == '0' ? 1 : 0;
            }
            
    
            // Handle new image (if any)
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $randomName = rand(10000000, 99999999);
                $imageName = time() . '_' . $randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/quotation/'), $imageName);
                $order->image = '/uploads/quotation/' . $imageName;
            }
    
            //  Get items using Laravel's input() method which handles nested arrays
            $items = $request->input('items', []);
            
            \Log::info('Items received:', [
                'items_type' => gettype($items),
                'items_count' => is_array($items) ? count($items) : 0,
                'first_item' => is_array($items) && !empty($items) ? $items[0] : null,
            ]);
    
            //  Validate items
            if (empty($items) || !is_array($items)) {
                \Log::error('No valid items array', [
                    'items' => $items,
                    'request_all' => $request->all(),
                ]);
                
                return response()->json([
                    'error' => 'No items provided for quotation',
                    'debug' => [
                        'received_keys' => array_keys($request->all()),
                        'items_value' => $items,
                    ]
                ], 400);
            }
    
            // Delete old products
            QuotationProduct::where('quotation_id', $order->id)->delete();
    
            // Array to hold updated product data
            $updatedProducts = [];
            
            if (!function_exists('getRelativeUploadPath')) {
                function getRelativeUploadPath($url)
                {
                    $path = parse_url($url, PHP_URL_PATH);
                    $path = preg_replace('#/+#', '/', $path);
                    return preg_replace('#^.*(/uploads/[^?]+)$#', '$1', $path);
                }
            }
    
            foreach ($items as $index => $item) {
                \Log::info("Processing item {$index}", ['item' => $item]);
    
                $product = new QuotationProduct();
                $product->quotation_id = $order->id;
                $product->product_id   = $item['product_id'] ?? null;
                $product->size         = $item['size'] ?? null;
                $product->qty          = $item['qty'] ?? null;
                $product->item_name    = $item['name'] ?? null;
                $product->model_no     = $item['model'] ?? null;
                $product->group_name   = $item['group'] ?? null;
                $product->unique_code  = $item['unique_code'] ?? null;
    
                $imagePath = null;

                // New document (string)
                if (!empty($item['document'])) {
                    $imagePath = getRelativeUploadPath($item['document']);
                }
                // Keep previous document
                elseif (!empty($item['existing_document'])) {
                    $imagePath = getRelativeUploadPath($item['existing_document']);
                }
                
                $product->image = $imagePath;
                $product->revised = 0;
                $product->status  = 1;
                $product->save();
    
                // Add to updated products array
                $item['document'] = $imagePath;
                $updatedProducts[] = $item;
            }
    
            // Update quotation's product_ids JSON
            $order->product_ids = json_encode($updatedProducts);
            $order->save();
    
            DB::commit();
    
            \Log::info('Quotation updated successfully', ['id' => $order->id]);
    
            return response()->json([
                'message' => 'Quotation order updated successfully',
                'data' => $order
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Update quotation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'error' => 'Failed to update quotation order',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    public function delete(Request $request, $id){
        try{
            $order =Quotation::find($id);

            if(!$order){
                return response()->json(['error' => 'Quotation order not found'], 404);
            }
            
            $products = QuotationProduct::where('quotation_id', $order->id)->get();
            
            foreach($products as $product){
                $product->delete();
                $product->save();
            }

            $order->delete();
            return response()->json(['message' => 'Quotation order deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch quotation order', $e->getMessage()], 500);
        }
        
    }

    // public function statusUpdate(Request $request)
    // {
    //     try{
    //         $id = $request->id;
    //         $message = $request->message;
    //         $order =Quotation::find($id);

    //         if(!$order){
    //             return response()->json(['error' => 'Quotation order not found'], 404);
    //         }
    //         $order->status= $request->status?? '2';
    //         if($message){
    //             $order->remark = $message;
    //         }
    //          DB::beginTransaction();
            
    //             $error = ProductionOrder::where('batch_no',$order->batch_no)->first();
    //             if($error){
    //                 return response()->json(['error' => 'Production order with this batch no already exists'], 400);
    //             }
    //             $productionOrder = new ProductionOrder();
    //             $productionOrder->quotation_id      = $order->id;
    //             $productionOrder->batch_no          = $order->batch_no;
    //             $productionOrder->product_ids       = $order->product_ids;
    //             $productionOrder->priority          = $order->priority;
    //             $productionOrder->customer_id       = $order->customer_id;
    //             $productionOrder->commencement_date = $order->commencement_date;
    //             $productionOrder->delivery_date     = $order->delivery_date;
    //             $productionOrder->sale_user_id      = $order->sale_user_id;
    //             $productionOrder->unique_code       = $order->unique_code;
    //             $productionOrder->image             = $order->image;
    //             $productionOrder->revised           =  0;
    //             $productionOrder->status            =  0;
    //             $productionOrder->save();

    //             $products = $order->product_ids ? json_decode($order->product_ids, true) : [];
    //             foreach($products as $prod){
    //                 $product = new ProductionProduct();
    //                 $product->po_id                 = $productionOrder->id;
    //                 $product->product_id            = $prod['product_id'];
    //                 $product->size                  = $prod['size'];
    //                 $product->qty                   = $prod['qty'];
    //                 $product->item_name             = $prod['name'];
    //                 // $product->model_no              = $prod['model'];
    //                 // $product->view_type             = $prod['product_type'];
    //                 $product->start_date            = $order->commencement_date;
    //                 $product->delivery_date         = $order->delivery_date;
    //                 $product->revised               =  0;
    //                 $product->status                = 1;
    //                 $product->save();


    //                 // $viewtype = new ViewType();
    //                 // $viewtype->po_id = $productionOrder->id ?? null;
    //                 // $viewtype->view_type = $prod['product_type'] ?? null;
    //                 // $viewtype->product_id = $prod['product_id'] ?? null;
    //                 // $viewtype->status = 1;
    //                 // $viewtype->save();
    //             }

            
    //         $order->save();
    //          DB::commit();

    //         return response()->json(['message' => 'Quotation order status updated  successfully']);
    //     }catch(\Exception $e){
    //          DB::rollBack();
    //         return response()->json(['error' => 'Failed to fetch  Quotation order', $e->getMessage()], 500);
    //     }
        
    // }
    
    public function statusUpdate(Request $request)
    {
        try {
            $id = $request->id;
            $quotation = Quotation::find($id);
    
            if (!$quotation) {
                return response()->json([
                    'error' => 'Quotation not found.'
                ], 404);
            }
    
            $quotation->status = 2;
            $quotation->save();
    
            return response()->json([
                'message' => 'Quotation status updated successfully.',
                'data' => $quotation
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update quotation status.',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    public function reviseQuotation(Request $request)
    {
        try{
            $id = $request->quotation_id;
            $message = $request->reason;
            $order =Quotation::find($id);

            if(!$order){
                return response()->json(['error' => 'Quotation order not found'. $id], 404);
            }
            $order->revised= '1';
            $order->status= '3';
            if($message){
                $order->remark = $message;
            }
            $order->save();

            return response()->json(['message' => 'Your quotation order is currently under revision']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  Quotation order', $e->getMessage()], 500);
        }
        
    }
    
}
