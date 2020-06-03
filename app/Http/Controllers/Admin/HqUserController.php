<?php

namespace App\Http\Controllers\Admin;

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
        if(true==$request->has('account')){
            $map['account']=$request->input('account');
        }
        
        $sql = HqUser::where($map);
        if(true==$request->has('nickname')){
            $sql->orWhere('nickname','like','%'.$request->input('nickname').'%');
        }
        $data = $sql->paginate(5)->appends($request->all());
        foreach($data as $key=>&$value){
            $data[$key]['creatime']=date("Y-m-d H:m:s",$value['creatime']);
            $data[$key]['userAccount']=UserAccount::getUserAccountInfo($value['user_id']);
        }
        return view('hquser.list',['list'=>$data,'input'=>$request->all()]);
    }
}