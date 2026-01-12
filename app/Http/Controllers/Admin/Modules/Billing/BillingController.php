<?php

namespace App\Http\Controllers\Admin\Modules\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Billing;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Customer;
use App\Models\CustomerPaymentLog;
use App\Models\BillingDetail;
use Illuminate\Validation\Rule;
use DB;

class BillingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');

        $this->middleware('permission:bills.read')->only([
            'getData'
        ]);

        $this->middleware('permission:bills.create')->only([
            'store'
        ]);

        $this->middleware('permission:bills.update')->only([
            'edit', 'update', 'statusUpdate'
        ]);

        $this->middleware('permission:bills.delete')->only([
            'delete'
        ]);
        
        $this->middleware('permission:bills.mark_delivered')->only([
            'markAsDelivered'
        ]);
        
        $this->middleware('permission:bills.create_challan')->only([
            'dispatchProduct'
        ]);
    }
    
    public function getData(Request $request)
    {
        try {
            $search = trim($request->search);
    
            $query = Billing::with('customer')
                ->orderByDesc('id');
                if($request->dispatch == "true"){
                    $query->whereIn("status",['2','3']);
                } else{
                     $query->whereIn("status",['1','0']);
                }
    
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_no', 'ILIKE', "%{$search}%")
                      ->orWhereHas('customer', function ($cq) use ($search) {
                          $cq->where('name', 'ILIKE', "%{$search}%")
                             ->orWhere('mobile', 'ILIKE', "%{$search}%");
                      });
                });
            }
    
            $bills = $query->paginate($request->limit);
    
            return response()->json([
                'success' => true,
                'data' => $bills->items(),
                'total' => $bills->total(),
                'current_page' => $bills->currentPage(),
                'last_page' => $bills->lastPage(),
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch billing',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    
    public function generateInvoiceNumber()
    {
        return DB::transaction(function () {
    
            $lastBill = Billing::whereNotNull('invoice_no')
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();
    
            $prefix = 'INV_';
    
            if (!$lastBill) {
               
                $nextNumber = 1;
            } else {
               
                $lastNumber = (int) str_replace($prefix, '', $lastBill->invoice_no);
                $nextNumber = $lastNumber + 1;
            }
    
            $nextInvoice = $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    
            return $nextInvoice;
        });
    }


    public function store(Request $request)
    {
        try {
    
            $validated = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'delivery_date' => 'required|date',
    
                'sub_total' => 'required|numeric',
                'discount' => 'nullable|numeric',
                'additional_charges' => 'nullable|numeric',
                'gst_rate' => 'required|numeric',
                'grand_total' => 'required|numeric',
    
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.qty' => 'required|numeric|min:1',
                'items.*.unitPrice' => 'required|numeric|min:0',
                'items.*.cost' => 'required|numeric|min:0',
            ]);
            DB::beginTransaction();
    
            $bill = new Billing();
            $bill->customer_id = $request->customer_id;
    
            $bill->date = date('Y-m-d');
            $bill->delivery_date = date('Y-m-d', strtotime($request->delivery_date));
    
            $bill->invoice_no = $this->generateInvoiceNumber();

    
            $bill->total = $request->sub_total;
            $bill->discount = $request->discount ?? 0;
            $bill->additional_charges = $request->additional_charges ?? 0;
            $bill->gst = $request->gst_rate;
            $bill->grand_total = $request->grand_total;
            $bill->term_and_condition = $request->order_terms;
            $bill->status = $request->is_draft ?? 0;
            $bill->save();
    
            foreach ($request->items as $item) {
    
                BillingDetail::create([
                    'bill_id' => $bill->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'rate' => $item['rate'],  
                    'amount' => $item['cost'],     
                ]);
            }
            
            DB::commit();
            return response()->json([
                'message' => 'Billing created successfully',
                'data' => $bill
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to store billing',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function edit(Request $request, $id)
    {
        try{
            
            // $b = Billing::find($id);
            // $b->vehicle_no = null;
            // $b->status = 1;
            // $b->save();
            $bill =Billing::with('customer','product','product.product',
            'shippingAddress','shippingAddress.state')->find($id);

            if(!$bill){
                return response()->json(['error' => 'Billing not found'], 404);
            }
            return response()->json(['message' => 'Billing fetch  successfully',
                'data' => $bill]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch Billing', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
    
        try {
    
            $validated = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'delivery_date' => 'required|date',
    
                'sub_total' => 'required|numeric',
                'discount' => 'nullable|numeric',
                'additional_charges' => 'nullable|numeric',
                'gst_rate' => 'required|numeric',
                'grand_total' => 'required|numeric',
    
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.qty' => 'required|numeric|min:1',
                'items.*.unitPrice' => 'required|numeric|min:0',
                'items.*.cost' => 'required|numeric|min:0',
            ]);
            $bill = Billing::find($id);
    
            if (!$bill) {
                return response()->json([
                    'error' => true,
                    'message' => 'Billing not found'
                ], 404);
            }
            $bill->customer_id = $request->customer_id;
    
            $bill->date = date('Y-m-d');
            $bill->delivery_date = date('Y-m-d', strtotime($request->delivery_date));
    
            // $bill->invoice_no = $bill->invoice_no = $request->invoice_no ?? $this->generateInvoiceNumber();

    
            $bill->total = $request->sub_total;
            $bill->discount = $request->discount ?? 0;
            $bill->additional_charges = $request->additional_charges ?? 0;
            $bill->gst = $request->gst_rate;
            $bill->grand_total = $request->grand_total;
            $bill->term_and_condition = $request->order_terms;
            $bill->status = $request->is_draft ?? 0;
    
            // Upload new image (optional)
            // if ($request->hasFile('image')) {
            //     $image = $request->file('image');
            //     $randomName = rand(10000000, 99999999);
            //     $imageName = time() . '_' . $randomName . '.' . $image->getClientOriginalExtension();
            //     $image->move(public_path('uploads/billing'), $imageName);
            //     $bill->dispatch_doc = '/uploads/billing/' . $imageName;
            // }
    
            $bill->save();
    
            BillingDetail::where('bill_id', $bill->id)->delete();
    
            foreach ($request->items as $item) {
                BillingDetail::create([
                    'bill_id' => $bill->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'rate' => $item['rate'],
                    'amount' => $item['cost'],
                ]);
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Billing updated successfully',
                'data' => $bill
            ]);
    
        } catch (\Exception $e) {
    
            DB::rollBack();
    
            return response()->json([
                'error' => 'Failed to update billing',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function delete(Request $request, $id){
        try{
            $bill =Billing::find($id);

            if(!$bill){
                return response()->json(['error' => 'Billing not found'], 404);
            }
            
            BillingDetail::where('bill_id', $bill->id)->delete();

            $bill->delete();
            return response()->json(['message' => 'Billing deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch Billing', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $bill =Billing::find($id);

            if(!$bill){
                return response()->json(['error' => 'Billing not found'], 404);
            }
            $bill->status= '2';
            $bill->save();

            return response()->json(['message' => 'Billing status updated  successfully',
            'data' =>$bill]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  Billing', $e->getMessage()], 500);
        }
        
    }
    
    public function markAsDelivered(Request $request)
    {
        try {
            
            $request->validate([
                'id' => 'required|exists:billings,id',
            ]);
    
            
            $bill = Billing::find($request->id);
            
            $bill->delivered_date = date('Y-m-d'); 
            $bill->status= '3'; 
            $bill->save();
            
            return response()->json([
                'message' => 'Billing status updated successfully',
                'data' => $bill
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update billing status',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function validateBillingProducts($billingId)
    {
        // Get billing products
        $billingProducts = BillingDetail::where('bill_id', $billingId)->get();
    
        if ($billingProducts->isEmpty()) {
            return [
                'status' => false,
                'message' => 'No products found in this billing.',
                'errors' => []
            ];
        }
    
        $errors = [];
    
        foreach ($billingProducts as $bp) {
    
            $product = Product::find($bp->product_id);
    
            if (!$product) {
                $errors[] = [
                    'product_id' => $bp->product_id,
                    'message'    => 'Product not found.',
                ];
                continue;
            }
    
            if ($product->available_qty < $bp->qty) {
                $errors[] = [
                    'product_id'      => $product->id,
                    'product_name'    => $product->name ?? 'N/A',
                    'required_qty'    => $bp->qty,
                    'available_qty'   => $product->available_qty,
                    'message'         => 'Insufficient stock'
                ];
            }
        }
    
        // If errors, return failure
        if (!empty($errors)) {
            return [
                'status'  => false,
                'message' => 'Insufficient stock for one or more products.',
                'errors'  => $errors
            ];
        }
    
        // All good → success
        return [
            'status' => true,
            'message' => 'All products have sufficient stock.'
        ];
    }

    
    public function dispatchProduct(Request $request)
    {
        try {
            $validated = $request->validate([
                'id'                 => 'required|exists:billings,id',
                'vehicle_number'     => 'required|string|max:50',
                'address_type'       => 'required|in:shipping,customer',
                'shipping_address_id'=> 'nullable|required_if:address_type,shipping|exists:shipping_addresses,id',
            ]);
    
            $bill = Billing::findOrFail($request->id);
    
            // Validate product stock before dispatch
            $stockValidation = $this->validateBillingProducts($bill->id);
            if (!$stockValidation['status']) {
                return response()->json([
                    'success' => false,
                    'message' => $stockValidation['message'],
                    'errors'  => $stockValidation['errors']
                ], 400);
            }
    
            DB::beginTransaction();
    
            //  Update billing details
            $bill->vehicle_no = $request->vehicle_number;
            $bill->shipping_address = $request->address_type;
            $bill->shipping_address_id = $request->address_type === 'shipping'
                ? $request->shipping_address_id
                : null;
            $bill->status = 2;
            $bill->save();
    
            //  Get billing products
            $billedProducts = BillingDetail::where('bill_id', $bill->id)->get();
    
            foreach ($billedProducts as $bp) {
    
                $product = Product::findOrFail($bp->product_id);
    
                
                $newQty = $product->available_qty - $bp->qty;
    
                //  Prevent negative stock
                if ($newQty < 0) {
                    $newQty = 0;
                }
    
    
                // Insert stock log
                Stock::create([
                    'product_id' => $product->id,
                    'out_stock'  => intval($bp->qty),
                    'available_qty' => intval($newQty),
                ]);
                
                $product->available_qty = $newQty;
                $product->save();
            }
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Billing dispatched successfully.',
                'data'    => $bill
            ]);
    
        } catch (\Exception $e) {
    
            DB::rollBack();
    
            return response()->json([
                'success' => false,
                'error'   => 'Failed to dispatch billing.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function storeCustomerPayment(Request $request)
    {
        
        try {
            
            $request->validate([
                'id' => 'required|exists:billings,id',
                'payment_mode' => 'required|string|max:50',
                'amount' => 'required|numeric|min:0',
                'reference_no' => 'nullable|string|max:255',
            ]);
    
           
            $bill = Billing::find($request->id);
            if (!$bill) {
                return response()->json([
                    'error' => 'Purchase inward not found or ID is invalid.',
                ], 404);
            }
           
            $currentDue = $bill->due_amount ?? $bill->grand_total;
            $dueAmount = $currentDue - $request->amount;
    
         
            if ($dueAmount < 0) {
                return response()->json([
                    'error' => 'Paid amount cannot exceed total payable amount.',
                ], 422);
            }
    
            $bill->due_amount = $dueAmount;
            $bill->save();
    
        
            $payment = new CustomerPaymentLog();
            $payment->bill_id = $bill->id;
            $payment->payment_mode = $request->payment_mode;
            $payment->paid_amount = $request->amount;
            $payment->due = $dueAmount;
            $payment->date = date('Y-m-d');
            $payment->added_by = auth()->id();
            $payment->reference_no = $request->reference_no;
            $payment->save();
    
            return response()->json([
                'message' => 'Payment recorded successfully',
                'data' => $bill,
                
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
                'id' => 'required|integer|exists:billings,id',
            ]);
    
          
            $payments = CustomerPaymentLog::where('bill_id', $request->id)
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
    
     public function getLedgerData(Request $request)
    {
        try {
            
            $request->validate([
                'id' => 'required|integer',
            ]);
    
           
            $data = Customer::with(['payments', 'state'])->find($request->id);
    
            if (!$data) {
                return response()->json([
                    'error' => 'Customer not found',
                ], 404);
            }
    
            return response()->json([
                'data' => $data,
                'message' => 'Ledger data fetched successfully',
            ], 200);
    
        } catch (\Exception $e) {
    
            return response()->json([
                'error'   => 'Failed to fetch ledger data',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    

}
