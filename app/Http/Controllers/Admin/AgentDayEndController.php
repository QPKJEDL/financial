<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Desk;

class AgentDayEndController extends Controller
{
    public function index(Request $request){
        $map = array();
        $data = Desk::where($map)->paginate(10)->appends($request->all());
        return view('agentDay.list',['list'=>$data,'min'=>config('admin.min_date')]);
    }
}