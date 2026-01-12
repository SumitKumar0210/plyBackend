<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeneralSetting;


class GeneralSettingController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth:api');

        // $this->middleware('permission:general_settings.read')->only([
        //     'getData'
        // ]);

        $this->middleware('permission:general_settings.update')->only([
             'update'
        ]);

        
    }
    
    public function getData(Request $request)
    {
        try{
            $setting = GeneralSetting::orderBy('id','desc')->get();
            $arr = [ 'data' => $setting];
            return response()->json($arr);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch general setting' . $e->getMessage()], 500);
        }
        
    }


    public function update(Request $request, $id)
    {
        try{
            
            $validated = $request->validate([
                'app_name' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'contact' => 'nullable|string|max:20|regex:/^[0-9+\-\s]+$/',
                'gst_no' => 'nullable|string|max:50',
                'address' => 'nullable|string|max:1000',
                // 'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', 
                // 'favicon' => 'nullable|image|mimes:jpg,jpeg,png,ico,webp|max:1024', 
            ]);
        
            $setting = GeneralSetting::orderBy('id', 'asc')->first();

            if(!$setting){
               $setting = new GeneralSetting();
            }
            $setting->app_name = $request->app_name ?? $setting->app_name;
            $setting->email = $request->email ?? $setting->email;
            $setting->contact = $request->contact ?? $setting->contact;
            $setting->gst_no = $request->gst_no ?? $setting->gst_no;
            $setting->address = $request->address ?? $setting->address;
            if($setting->is_powered_by === null){
                $setting->is_powered_by = 1 ;
            }
            $config = ['false' => 0, 'true' => 1];

            if (
                isset($request->is_powered_by) &&
                $setting->is_powered_by != $config[$request->is_powered_by]
            ) {
                $setting->is_powered_by = $config[$request->is_powered_by];
            }

            $setting->powered_by_link = $request->powered_by_link ?? $setting->powered_by_link;
            $setting->powered_by_name = $request->powered_by_name ?? $setting->powered_by_name;
            if($request->has('logo') && $request->file('logo')){
                $image = $request->file('logo');
                $randomName = rand(10000000, 99999999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/setting/'), $imageName);
                $setting->logo = '/uploads/setting/'.$imageName;
            }
            if($request->has('horizontal_logo') && $request->file('horizontal_logo')){
                $image = $request->file('horizontal_logo');
                $randomName = rand(10000000, 99999999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/setting/'), $imageName);
                $setting->horizontal_logo = '/uploads/setting/'.$imageName;
            }
            if($request->has('favicon') && $request->file('favicon')){
                $image = $request->file('favicon');
                $randomName = rand(10000000, 99999999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/setting/'), $imageName);
                $setting->favicon = '/uploads/setting/'.$imageName;
            }
            $setting->save();

            return response()->json(['message' => 'General setting updated  successfully',
                'data' => $setting]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch general setting', $e->getMessage()], 500);
        }
        
    }

}
