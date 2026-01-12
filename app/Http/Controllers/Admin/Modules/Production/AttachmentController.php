<?php

namespace App\Http\Controllers\Admin\Modules\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionLog;
use App\Models\Attachment;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;


class AttachmentController extends Controller
{
    public function uploadDocument(Request $request)
    {
        try {
            $validated = $request->validate([
                'attachments' => 'required|mimes:jpeg,jpg,png,gif,svg,webp,bmp,tiff,pdf|max:5120',
                'pp_id'      => 'required|integer|exists:production_products,id',
                'department' => 'nullable|string|max:100',
            ], [
                'attachments.required' => 'Please upload a file.',
                'attachments.mimes' => 'Only JPG, JPEG, PNG, GIF, SVG, WEBP, TIFF, BMP and PDF files are allowed.',
    
            ]);
            $uploadPath = public_path('uploads/production/image/');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
    
            $image = $request->file('attachments');

            $originalName = $image->getClientOriginalName();
            
            $extension = $image->getClientOriginalExtension();
            $baseName  = pathinfo($originalName, PATHINFO_FILENAME);
            
            $baseName = str_replace(' ', '', $baseName);
            
            $trimmedName = substr($baseName, 0, 30);
            
            $uniquePrefix = time() . '_' . bin2hex(random_bytes(4));
            $fileName     = $uniquePrefix . '_' . $trimmedName . '.' . $extension;
            
            $image->move($uploadPath, $fileName);
    
            // File path to store in DB
            $docPath = '/uploads/production/image/' . $fileName;
    
            $attachment = new Attachment();
            $attachment->doc        = $docPath;
            $attachment->pp_id      = $validated['pp_id'];
            $attachment->department = $request->department ?? null;
            $attachment->action_by  = auth()->id();
            $attachment->save();
    
            return response()->json([
                'message'    => 'Attachment uploaded successfully.',
                'data' => $attachment,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Failed to upload attachment',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}