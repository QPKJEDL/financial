<?php


namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\Desk;
use Illuminate\Http\Request;

//停用台桌
class StopDeskController extends Controller
{
    /**
     * 数据列表
     */
    public function index(Request $request){
        $map = array();
        $map['status']=1;
        if ($request->input('desk_name')!=''||$request->input('desk_name')!=null){
            $data = Desk::where('desk_name',$request->input('desk_name'))->orWhere('desk_name','like','%'.$request->input('desk_name').'%')->where($map)->paginate(5)->appends($request->all());
        }else{
            $data = Desk::where($map)->paginate(5)->appends($request->all());
        }
        foreach ($data as $key=>$value){
            $data[$key]['min_limit'] = json_decode($data[$key]['min_limit'],true);
            $data[$key]['min_tie_limit'] = json_decode($data[$key]['min_tie_limit'],true);
            $data[$key]['min_pair_limit']= json_decode($data[$key]['min_pair_limit'],true);
            $data[$key]['max_limit']=json_decode($data[$key]['max_limit'],true);
            $data[$key]['max_tie_limit']=json_decode($data[$key]['max_tie_limit'],true);
            $data[$key]['max_pair_limit']=json_decode($data[$key]['max_pair_limit'],true);
            $data[$key]['creatime'] = date("Y-m-d H:i:s",$value['creatime']);
        }
        return view('stopDesk.list',['list'=>$data,'input'=>$request->all()]);
    }
}