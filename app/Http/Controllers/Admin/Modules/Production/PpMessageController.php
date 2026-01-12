<?php

namespace App\Http\Controllers\Admin\Modules\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionLog;
use App\Models\PpMessage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class PpMessageController extends Controller
{
    public function uploadMessage(Request $request)
    {
        try {

            // Validate request
            $validated = $request->validate([
                'image'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'message'    => 'nullable|string|max:5000',
                'pp_id'      => 'required|integer|exists:production_products,id',
                'department' => 'nullable|string|max:100',
            ], [
                'image.mimes' => 'Only JPG, JPEG, PNG, and PDF files are allowed.',
            ]);


            $attachment = new PpMessage();

            // Save text message
            $attachment->message = $request->message ?? null;

            // Save image (if uploaded)
            if ($request->hasFile('image')) {

                $file = $request->file('image');
                $randomName = rand(10000000, 99999999);
                $fileName = time() . '_' . $randomName . '.' . $file->getClientOriginalExtension();

                $file->move(public_path('uploads/production/message'), $fileName);

                $attachment->image = '/uploads/production/message/' . $fileName;
            }

            // Other fields
            $attachment->pp_id      = $validated['pp_id'];
            $attachment->department_id = $request->department_id;
            $attachment->user_id  = auth()->id();

            $attachment->save();
            $attachment->refresh()->load('user');


            return response()->json([
                'message'    => 'Message uploaded successfully.',
                'data'       => $attachment
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'error'   => 'Failed to upload message.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
