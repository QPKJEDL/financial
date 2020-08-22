<?php


namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentBill;
use Illuminate\Http\Request;

class AgentDrawAndCzController extends Controller
{
    public function index(Request $request)
    {
        $map = array();
        if (true==$request->has('account'))
        {
            $map['agent_users.username']=HttpFilter($request->input('account'));
        }
        if (true==$request->has('status'))
        {
            $map['agent_billflow.status']=(int)$request->input('status');
        }
        if (true==$request->has('userType'))
        {
            $map['agent_users.userType']=(int)$request->input('userType');
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
        if (true==$request->has('limit'))
        {
            $limit = (int)$request->input('limit');
        }
        else
        {
            $limit = 10;
        }
        $data = $sql->where($map)->orderBy('agent_billflow.creatime','desc')->paginate($limit)->appends($request->all());
        foreach ($data as $key=>$value)
        {
            $agentInfo = $value['agent_id']?Agent::find($value['agent_id']):[];
            if ($agentInfo['parent_id']==0)
            {
                $data[$key]['zsyj']=$agentInfo;
                $data[$key]['sj']=$agentInfo;
            }
            else
            {
                $idArr = explode(',',$agentInfo['ancestors']);
                $data[$key]['zsyj']=$idArr[1]?Agent::find($idArr[1]):[];
                //获取直属上级
                $data[$key]['sj']=$agentInfo['parent_id']?Agent::find($agentInfo['parent_id']):[];
            }
            $data[$key]['creatime']=date('Y-m-d H:i:s',$value['creatime']);
        }
        return view('agentBill.list',['list'=>$data,'input'=>$request->all(),'limit'=>$limit]);
    }

    /**
     * 根据代理id查询记录
     * @param $id
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function getRecordByAgentId($id,Request $request)
    {
        $map = array();
        $map['agent_billflow.agent_id']=(int)$id;
        if (true==$request->has('account'))
        {
            $map['agent_users.username']=HttpFilter($request->input('account'));
        }
        if (true==$request->has('status'))
        {
            $map['agent_billflow.status']=(int)$request->input('status');
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
        if (true==$request->has('limit'))
        {
            $limit = (int)$request->input('limit');
        }
        else
        {
            $limit = 10;
        }
        $data = $sql->where($map)->orderBy('agent_billflow.creatime','desc')->paginate($limit)->appends($request->all());
        foreach ($data as $key=>$value)
        {
            $agentInfo = $value['agent_id']?Agent::find($value['agent_id']):[];
            if ($agentInfo['parent_id']==0)
            {
                $data[$key]['zsyj']=$agentInfo;
                $data[$key]['sj']=$agentInfo;
            }
            else
            {
                $idArr = explode(',',$agentInfo['ancestors']);
                $data[$key]['zsyj']=$idArr[1]?Agent::find($idArr[1]):[];
                //获取直属上级
                $data[$key]['sj']=$agentInfo['parent_id']?Agent::find($agentInfo['parent_id']):[];
            }
            $data[$key]['creatime']=date('Y-m-d H:i:s',$value['creatime']);
        }
        return view('agentBill.list',['list'=>$data,'input'=>$request->all(),'limit'=>$limit]);
    }
}