<?php


namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\AgentBill;
use Illuminate\Http\Request;

class AgentDrawAndCzController extends Controller
{
    public function index(Request $request)
    {
        $map = array();
        if (true==$request->has('account'))
        {
            $map['agent_users.username']=$request->input('account');
        }
        $sql = AgentBill::query();
        $sql->leftJoin('agent_users','agent_billflow.agent_id','=','agent_users.id')
            ->leftJoin('user','user.user_id','=','agent_billflow.user_id')
            ->select('agent_billflow.*','agent_users.nickname as agentName','agent_users.username','user.nickname as uName','user.account');
        if (true==$request->has('begin'))
        {
            $start=strtotime($request->input('begin'));
            $end=strtotime('+1day',$start);
            $sql->whereBetween('agent_billflow.creatime',[$start,$end]);
        }
        $data = $sql->where($map)->orderBy('agent_billflow.creatime','desc')->paginate(10)->appends($request->all());
        foreach ($data as $key=>$value)
        {
            $data[$key]['creatime']=date('Y-m-d H:i:s',$value['creatime']);
        }
        return view('agentBill.list',['list'=>$data,'input'=>$request->all(),'min'=>config('admin.min_date')]);
    }
}