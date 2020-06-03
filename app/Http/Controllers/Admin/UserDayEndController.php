<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Desk;
use Illuminate\Http\Request;

class UserDayEndController extends Controller
{
    public function index(Request $request){
        $map = array();
        $data = Desk::where($map)->paginate(10)->appends($request->all());
        return view('userDay.list',['list'=>$data,'min'=>config('admin.min_date')]);
    }
}