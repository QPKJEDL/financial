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
        if (true==$request->has('begin'))
        {
            $begin = strtotime($request->input('begin')) + config('admin.beginTime');
            if (true==$request->has('end'))
            {
                $end = strtotime('+1day',strtotime($request->input('end'))) + config('admin.beginTime');
            }
            else
            {
                $end = strtotime('+1day',$begin)+ config('admin.beginTime');
            }
        }
        else
        {
            $begin = strtotime(date('Y-m-d',time()))+ config('admin.beginTime');
            $end = strtotime('+1day',$begin);
            $request->offsetSet('begin',date('Y-m-d',time()));
            $request->offsetSet('end',date('Y-m-d',time()));
        }
        $sql->whereBetween('live_reward.creatime',[$begin,$end]);
        if (true==$request->has('excel'))
        {
            $head = array('账号','昵称','直属一级','台桌号','靴','铺','主播账号','主播名称','打赏金额','打赏时间');
            $excelData = $sql->where($map)->orderBy('live_reward.creatime','desc')->get()->toArray();
            $excel = array();
            foreach ($excelData as $key=>$datum)
            {
                $a = array();
                $a['account']=$datum['userAcc'];
                $a['userName']=$datum['userName'];
                $agent = $this->get_direct_agent($datum['agent_id']);
                $a['agent']=$agent['nickname'].'['.$agent['username'].']';
                $a['desk_name'] = $datum['desk_name'];
                $a['boot_num']=$datum['boot_num'];
                $a['pave_num']=$datum['pave_num'];
                $a['liveAcc']=$datum['liveAcc'];
                $a['liveName']=$datum['liveName'];
                $a['money']=number_format($datum['money']/100,2);
                $a['creatime']=date('Y-m-d H:i:s',$datum['creatime']);
                $excel[] = $a;
            }
            try {
                exportExcel($head, $excel, date('Y-m-d H:i:s',time()).'会员打赏记录', '', true);
            } catch (\PHPExcel_Reader_Exception $e) {
            } catch (\PHPExcel_Exception $e) {
            }
        }
        if (true==$request->has('limit'))
        {
            $limit = $request->input('limit');
        }
        else
        {
            $limit = config('admin.limit');
        }
        $data = $sql->where($map)->orderBy('live_reward.creatime','desc')->paginate($limit)->appends($request->all());
        $money = $sql->where($map)->sum('money');
        foreach ($data as $key=>$value){
            $data[$key]['creatime']=date('Y-m-d H:i:s',$value['creatime']);
            $data[$key]['agent']=$this->get_direct_agent($value['agent_id']);
        }
        return view('live.list',['list'=>$data,'money'=>$money,'desk'=>Desk::getDeskList(),'limit'=>$limit,'input'=>$request->all()]);
    }

    /**
     * 获取直属一级代理
     * @param $agent_id
     * @return mixed
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