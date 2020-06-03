<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Desk;
use Illuminate\Http\Request;

/**
 * 下分请求
 */
class DownController extends Controller
{
    /**
     * 数据列表
     */
    public function index(Request $request){
        $map = array();
        $data = Desk::where($map)->paginate(10)->appends($request->all());
        $min=config('admin.min_date');

        return view('down.list',['list'=>$data,'input'=>$request->all(),'min'=>$min]);
    }
}