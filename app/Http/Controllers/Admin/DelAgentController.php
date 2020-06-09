<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Desk;
use Illuminate\Http\Request;

/**
 * 已删代理
 * Class DelAgentController
 * @package App\Http\Controllers\Admin
 */
class DelAgentController extends Controller
{
    public function index(Request $request){
        $map = array();
        $map['del_flag']=1;
        $sql = Agent::query();
        if (true==$request->has('username')){
            $map['username']=$request->input('username');
        }
        if (true==$request->has('nickname')){
            $sql->where('nickname','like','%'.$request->input('nickname').'%');
        }
        $data = $sql->where($map)->paginate(10)->appends($request->all());
        foreach ($data as $key=>$value){
            $data[$key]['fee']=json_decode($value['fee'],true);
        }
        return view('delagent.list',['list'=>$data,'input'=>$request->all()]);
    }
}