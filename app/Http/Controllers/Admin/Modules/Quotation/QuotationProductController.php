<?php

namespace App\Http\Controllers\Admin\Modules\Quotation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quotation;
use App\Models\QuotationProduct;
use Illuminate\Validation\Rule;

class QuotationProductController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $orders = Quotation::with('customer:id,name,address,state_id,city,zip_code','customer.state:id,name')->where('status', '2')->select('id','product_ids','grand_total','created_at','status','priority','customer_id')->orderBy('id','desc')->paginate($request->limit);
            return response()->json($orders);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch quotation products'], 500);
        }
        
    }

    public function store(Request $request)
    {
        try{
            
            $request->validate([
                'batch_no'           => 'nullable|string|max:50',
                'product_ids'        => 'nullable',
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

            $product = new QuotationProduct();
            $product->quotation_id          = $request->quotation_id;
            $product->product_id            = $request->product_id;
            $product->size                  = $request->size;
            $product->qty                   = $request->qty;
            $product->item_name             = $request->item_name;
            $product->modal_no              = $request->modal_no;
            $product->grade_id              = $request->grade_id;
            $product->view_type             = $request->view_type;
            $product->start_date            = $request->start_date;
            $product->delivery_date         = $request->delivery_date;
            $product->unique_code           = $request->unique_code;
            $product->revised           = $request->revised ?? 0;
            $product->status            = $request->status ?? 0;
            $product->save();

            return response()->json(['message' => 'Quotation product created successfully',
                'data' => $product]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store quotation product', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $product =QuotationProduct::find($id);

            if(!$product){
                return response()->json(['error' => 'Quotation product not found'], 404);
            }
            return response()->json(['message' => 'Quotation product fetch  successfully',
                'data' => $product]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch quotation product', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id)
    {
        try{
            $request->validate([
                'batch_no'           => 'nullable|string|max:50',
                'product_ids'        => 'nullable',
                'priority'           => 'nullable|string|max:20',
                'customer_id'        => 'nullable|exists:customers,id',
                'commencement_date'  => 'nullable|date',
                'delivery_date'      => 'nullable|date',
                'sale_user_id'       => 'nullable|exists:sales_users,id',
                'unique_code'        => 'nullable|string|max:150|unique:quotations,unique_code,' . $id,
                'image'              => 'nullable|string|max:225',
                'revised'            => 'nullable|in:0,1',
                'status'             => 'nullable|in:0,1',
            ]);
            
            $product = QuotationProduct::find($id);
            
            if(!$product){
                return response()->json(['error' => 'Quotation product not found'], 404);
            }

            $product = new QuotationProduct();
            $product->quotation_id          = $request->quotation_id;
            $product->product_id            = $request->product_id;
            $product->size                  = $request->size;
            $product->qty                   = $request->qty;
            $product->item_name             = $request->item_name;
            $product->modal_no              = $request->modal_no;
            $product->grade_id              = $request->grade_id;
            $product->view_type             = $request->view_type;
            $product->start_date            = $request->start_date;
            $product->delivery_date         = $request->delivery_date;
            $product->unique_code           = $request->unique_code;
            if($request->has('image')){
                $image = $request->file('image');
                $randomName = rand(10000000, 99999999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/quotation/product/'), $imageName);
                $product->image = '/uploads/quotation/product/'.$imageName;

            }
            $product->revised           = $request->revised ?? $product->revised;
            $product->status            = $request->status ?? $product->status;
            $product->save();

            return response()->json(['message' => 'Quotation product updated  successfully',
                'data' => $product]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch quotation product', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{
            $product =QuotationProduct::find($id);

            if(!$product){
                return response()->json(['error' => 'Quotation product not found'], 404);
            }

            $product->delete();
            return response()->json(['message' => 'Quotation product deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch quotation product', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $product =QuotationProduct::find($id);

            if(!$product){
                return response()->json(['error' => 'Quotation product not found'], 404);
            }
            $product->status= !$product->status;
            $product->save();

            return response()->json(['message' => 'Quotation product status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  quotation product', $e->getMessage()], 500);
        }
        
    }
}
