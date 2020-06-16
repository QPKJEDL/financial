<?php


namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Desk;
use App\Models\LiveReward;
use Illuminate\Http\Request;

class LiveRewardController extends Controller
{
    public function index(Request $request)
    {
        $map = array();
        if (true==$request->has('account'))
        {
            $map['u1.account']=$request->input('account');
        }
        if (true==$request->has('live_acc')){
            $map['u2.account']=$request->input('live_acc');
        }
        if (true==$request->has('boot_num')){
            $map['live_reward.boot_num']=$request->input('boot_num');
        }
        if (true==$request->has('pave_num')){
            $map['live_reward.pave_num'] = $request->input('pave_num');
        }
        if (true==$request->has('deskId')){
            $map['live_reward.desk_id']=$request->input('deskId');
        }
        $sql = LiveReward::query();
        $sql->leftJoin('user as u1','u1.user_id','=','live_reward.user_id')
            ->leftJoin('user as u2','u2.user_id','=','live_user_id')
            ->leftJoin('desk','desk.id','=','live_reward.desk_id')
            ->select('live_reward.*','u1.nickname as userName','u1.agent_id','u1.account as userAcc','u2.nickname as liveName','u2.account as liveAcc','desk.desk_name');
        if (true==$request->has('begin') && true==$request->has('end'))
        {
            $time = strtotime($request->input('begin'));
            $end = strtotime('+1day',strtotime($request->input('end')))-1;
            $sql->whereBetween('live_reward.creatime',[$time,$end]);
        }
        $data = $sql->where($map)->paginate(10)->appends($request->all());
        foreach ($data as $key=>$value){
            $data[$key]['creatime']=date('Y-m-d H:i:s',$value['creatime']);
            $data[$key]['agent']=$this->get_direct_agent($value['agent_id']);
        }
        return view('live.list',['list'=>$data,'desk'=>Desk::getDeskList(),'min'=>config('admin.min_date'),'input'=>$request->all()]);
    }
    /*
         * 递归查询直属一级
         */
    private function get_direct_agent($agent_id){
        $agentlist=Agent::select('id','username','nickname','parent_id','proportion')->get()->toArray();
        foreach ($agentlist as $key=>&$value){
            if ($value['id']==$agent_id){
                if($agentlist[$key]["parent_id"]>0){
                    return $this->get_direct_agent($agentlist[$key]["parent_id"]);
                }
                return $agentlist[$key];
                continue;
            }
        }
    }
}