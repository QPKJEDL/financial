<?php

namespace App\Http\Controllers\Admin;

use App\Models\Czrecord;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\HqUser;
use App\Models\UserAccount;

class HqUserController extends Controller
{
    /**
     * 数据列表
     */
    public function index(Request $request){
        $map = array();
        $map['user.del_flag']=0;
        if(true==$request->has('account')){
            $map['user.account']=$request->input('account');
        }
        $sql = HqUser::where($map);
        $sql->leftJoin('agent_users','agent_users.id','=','user.agent_id')
            ->select('user.*','agent_users.username','agent_users.nickname as agentName')
            ->where($map);
        if(true==$request->has('nickname')){
            $sql->where('user.nickname','like','%'.$request->input('nickname').'%');
        }
        $data = $sql->paginate(10)->appends($request->all());
        foreach($data as $key=>&$value){
            $data[$key]['creatime']=date("Y-m-d H:m:s",$value['creatime']);
            $data[$key]['fee']=json_decode($value['fee'],true);
            $data[$key]['userAccount']=UserAccount::getUserAccountInfo($value['user_id']);
            $data[$key]['cz']=$this->getUserCzCord($value['user_id']);
        }
        return view('hquser.list',['list'=>$data,'input'=>$request->all()]);
    }
    /**
     * 获取用户最近充值记录
     */
    public function getUserCzCord($userId){
        $data = Czrecord::where('user_id',$userId)->orderBy('creatime','desc')->first();
        return $data;
    }
}