<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\State;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StateController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $customers = State::all();
            $arr = [ 'data' => $customers ];
            return response()->json($arr);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch customers'], 500);
        }
        
    }
}
