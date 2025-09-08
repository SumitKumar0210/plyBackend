<?php

namespace App\Http\Controllers\Admin\Modules\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Billing;
use App\Models\BillingDetail;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $bills = Billing::orderBy('id','desc')->paginate(10);
            return response()->json($bills);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch billig'], 500);
        }
        
    }

    public function store(Request $request)
    {
        try{
            
            $request->validate([
                'bill_no' => [
                    'required',
                    Rule::unique('billings', 'bill_no')->whereNull('deleted_at'),
                ],
                'po_id' => 'required',
            ]);
            // dd($request->all());

            $bill = new Billing();

            $bill->po_id = $request->po_id;
            $bill->bill_no = $request->bill_no;
            $bill->date = $request->date;
            $bill->consignee = $request->consignee;
            $bill->invoice_no = $request->invoice_no;
            $bill->order_no = $request->order_no;
            $bill->dispatch_through = $request->dispatch_through;
            $bill->delivered = $request->delivered;
             
            if($request->has('image')){
                $image = $request->file('image');
                $randomName = rand(10000000, 99999999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/billing'), $imageName);
                $bill->dispatch_doc = '/uploads/billing/'.$imageName;

            }
            $bill->eway_bill = $request->eway_bill;
            $bill->eway_date = $request->eway_date;
            $bill->vehicle_no = $request->vehicle_no;
            $bill->total = $request->total;
            $bill->discount = $request->discount;
            $bill->gst = $request->gst;
            $bill->grand_total = $request->grand_total;
            $bill->status = $request->status ?? 0;
            $bill->save();
            if($request->product_id){
                foreach ($request->product_id as $key => $value){

                    $details = new BillingDetail();
                    $details->bill_id = $bill->id;
                    $details->product_id = $value;
                    $details->qty = $request->qty[$key];
                    $details->rate = $request->rate[$key];
                    $details->amount = $request->amount[$key];
                    $details->save();
                }
            }
            return response()->json(['message' => 'Billig created successfully',
                'data' => $bill]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store billig', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $bill =Billing::find($id);

            if(!$bill){
                return response()->json(['error' => 'Billig not found'], 404);
            }
            return response()->json(['message' => 'Billig fetch  successfully',
                'data' => $bill]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch billig', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id)
    {
        try{
            $request->validate([
                'bill_no' => [
                    'required',
                    Rule::unique('billings', 'bill_no')
                        ->ignore($id) 
                        ->whereNull('deleted_at'), 
                ],
                'status' => 'nullable|in:0,1',
            ]);
            $bill =Billing::find($id);

            if(!$bill){
                return response()->json(['error' => 'Billig not found'], 404);
            }
            $bill->bill_no = $request->bill_no;
            $bill->date = $request->date;
            $bill->consignee = $request->consignee;
            $bill->invoice_no = $request->invoice_no;
            $bill->order_no = $request->order_no;
            $bill->dispatch_through = $request->dispatch_through;
            $bill->delivered = $request->delivered;
             
            if($request->has('image')){
                $image = $request->file('image');
                $randomName = rand(10000000, 99999999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/billing'), $imageName);
                $bill->dispatch_doc = '/uploads/billing/'.$imageName;

            }
            $bill->eway_bill = $request->eway_bill;
            $bill->eway_date = $request->eway_date;
            $bill->vehicle_no = $request->vehicle_no;
            $bill->total = $request->total;
            $bill->discount = $request->discount;
            $bill->gst = $request->gst;
            $bill->grand_total = $request->grand_total;
            $bill->status = $request->status ?? 0;
            $bill->save();
            $details = new BillingDetail();
            $details->bill_id = $bill->id;
            $details->product_id = $request->product_id;
            $details->qty = $request->qty;
            $details->rate = $request->rate;
            $details->amount = $request->amount;
            $details->save();
            $bill->status = $request->status ?? $bill->status;
            $bill->save();

            return response()->json(['message' => 'Billig updated  successfully',
                'data' => $bill]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch billig', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{
            $bill =Billing::find($id);

            if(!$bill){
                return response()->json(['error' => 'Billig not found'], 404);
            }

            $bill->delete();
            return response()->json(['message' => 'Billig deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch billig', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $bill =Billing::find($id);

            if(!$bill){
                return response()->json(['error' => 'Billig not found'], 404);
            }
            $bill->status= !$bill->status;
            $bill->save();

            return response()->json(['message' => 'Billig status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  billig', $e->getMessage()], 500);
        }
        
    }
}
