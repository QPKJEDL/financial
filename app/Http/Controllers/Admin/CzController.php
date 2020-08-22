<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Czrecord;
use App\Models\HqUser;
use Illuminate\Http\Request;

/**
 * 会员充值查询
 * Class CzController
 * @package App\Http\Controllers\Admin
 */
class CzController extends Controller
{
    public function index(Request $request){
        $map = array();
        if (true==$request->has('pay_type')){
            $map['czrecord.pay_type']=$request->input('pay_type');
        }
        $sql = Czrecord::query();
        $sql->leftJoin('user','user.user_id','=','czrecord.user_id')
            ->leftJoin('agent_users','agent_users.id','=','user.agent_id')
            ->select('czrecord.creatime','czrecord.pay_type','user.nickname','user.account','agent_users.username','agent_users.nickname as agentName','czrecord.score')
            ->where($map);
        if (true==$request->has('begin')){
            $begin = strtotime($request->input('begin'));
            if (true==$request->has('end')){
                $end = strtotime('+1day',strtotime($request->input('end')))-1;
            }else{
                $end = strtotime('+1day',time())-1;
            }
            $sql->whereBetween('czrecord.creatime',[$begin,$end]);
        }
        if (true==$request->has('account')){
            $sql->where('user.account','=',$request->input('account'));
        }
        if (true==$request->has('limit'))
        {
            $limit = $request->input('limit');
        }
        else
        {
            $limit = 10;
        }
        $sql->orderBy('czrecord.creatime','desc');
        $data = $sql->paginate($limit)->appends($request->all());
        foreach ($data as $key=>$value){
            $data[$key]['creatime']=date('Y-m-d H:i:s',$value['creatime']);
        }
        return view('czrecord.list',['list'=>$data,'input'=>$request->all(),'limit'=>$limit]);
    }

    /**
     * 获取会员的充值记录
     * @param $id
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function getCzRecordList($id,Request $request)
    {
        $userInfo = $id?HqUser::find($id):[];
        $request->offsetSet('account',$userInfo['account']);
        $map = array();
        $sql = Czrecord::query();
        $sql->leftJoin('user','user.user_id','=','czrecord.user_id')
            ->leftJoin('agent_users','agent_users.id','=','user.agent_id')
            ->select('czrecord.creatime','czrecord.pay_type','user.nickname','user.account','agent_users.username','agent_users.nickname as agentName','czrecord.score')
            ->where($map);
        if (true==$request->has('begin')){
            $begin = strtotime($request->input('begin'));
            if (true==$request->has('end')){
                $end = strtotime('+1day',strtotime($request->input('end')))-1;
            }else{
                $end = strtotime('+1day',time())-1;
            }
            $sql->whereBetween('czrecord.creatime',[$begin,$end]);
        }
        if (true==$request->has('limit'))
        {
            $limit = $request->input('limit');
        }
        else
        {
            $limit = 10;
        }
        $sql->orderBy('czrecord.creatime','desc');
        $data = $sql->paginate($limit)->appends($request->all());
        foreach ($data as $key=>$value){
            $data[$key]['creatime']=date('Y-m-d H:i:s',$value['creatime']);
        }
        return view('czrecord.list',['list'=>$data,'input'=>$request->all(),'limit'=>$limit]);
    }
}