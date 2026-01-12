<?php

namespace App\Http\Controllers\Admin\Modules\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShippingAddress;
use DB;

class ShippingAddressController extends Controller
{
   
   public function getData(Request $request, $customer_id)
    {
        try {
            
            if (!\App\Models\Customer::where('id', $customer_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Customer not found',
                ], 404);
            }
    
            // Fetch addresses
            $addresses = ShippingAddress::where('customer_id', $customer_id)
                ->orderByDesc('id')
                ->get();
    
            return response()->json([
                'success' => true,
                'message' => 'Shipping addresses fetched successfully',
                'data'    => $addresses,
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Failed to fetch shipping addresses',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



   
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'state_id'    => 'required|exists:states,id',
                'city'        => 'required|string|max:255',
                'zip_code'    => 'required|string|max:20',
                'address'     => 'required|string|max:500',
            ]);

            $shipping = ShippingAddress::create([
                'customer_id' => $request->customer_id,
                'state_id'    => $request->state_id,
                'city'        => $request->city,
                'zip_code'    => $request->zip_code,
                'address'     => $request->address,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Shipping address saved successfully',
                'data'    => $shipping
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Failed to store shipping address',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

   
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'state_id'    => 'required|exists:states,id',
                'city'        => 'required|string|max:255',
                'zip_code'    => 'required|string|max:20',
                'address'     => 'required|string|max:500',
            ]);

            $shipping = ShippingAddress::find($id);

            if (!$shipping) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shipping address not found'
                ], 404);
            }

            $shipping->update([
                'customer_id' => $request->customer_id,
                'state_id'    => $request->state_id,
                'city'        => $request->city,
                'zip_code'    => $request->zip_code,
                'address'     => $request->address,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Shipping address updated successfully',
                'data'    => $shipping
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Failed to update shipping address',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function delete(Request $request, $id)
    {
        try {
            $shipping = ShippingAddress::find($id);

            if (!$shipping) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shipping address not found'
                ], 404);
            }

            $shipping->delete();

            return response()->json([
                'success' => true,
                'message' => 'Shipping address deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Failed to delete shipping address',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
