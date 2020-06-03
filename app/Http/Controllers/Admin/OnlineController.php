<?php
namespace APP\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Desk;
use Illuminate\Http\Request;

class OnlineController extends Controller
{
    /**
     * 数据列表
     */
    public function index(Request $request){
        $map = array();
        $data = Desk::where($map)->paginate(10)->appends($request->all());
        return view('online.list',['list'=>$data]);
    }
}