<?php


namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentBill;
use App\Models\User;
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
        if (true==$request->has('create_by'))
        {
            $map['agent_billflow.create_by']=$request->input('create_by');
        }
        $sql = AgentBill::query();
        $sql->leftJoin('agent_users','agent_billflow.agent_id','=','agent_users.id')
            ->leftJoin('user','user.user_id','=','agent_billflow.user_id')
            ->select('agent_billflow.*','agent_users.username','user.account');
        if (true==$request->has('begin'))
        {
            $begin = strtotime($request->input('begin'))+ config('admin.beginTime');
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
            $end = strtotime('+1day',$begin)+ config('admin.beginTime');
            $request->offsetSet('begin',date('Y-m-d',$begin));
            $request->offsetSet('end',date('Y-m-d',$begin));
        }
        $sql->whereBetween('agent_billflow.creatime',[$begin,$end]);
        if (true==$request->has('excel'))
        {
            $head = array('时间','代理名称[账号]','会员名称[账号]','直属上级[账号]','直属一级[账号]','操作前金额','操作金额','操作后金额','操作类型','操作人');
            $excelData = $sql->where($map)->get()->toArray();
            $excel = array();
            foreach ($excelData as $key=>$datum)
            {
                $arr['creatime']=date('Y-m-d H:i:s',$datum['creatime']);
                $arr['agentName']=$datum['agent_name'].'['.$datum['username'].']';
                $arr['uName']=$datum['user_name'].'['.$datum['account'].']';
                $agentInfo = $datum['agent_id']?Agent::find($datum['agent_id']):[];
                $arr['sj']=$agentInfo['nickname'].'['.$agentInfo['username'].']';
                if ($agentInfo['parent_id']==0)
                {
                    $arr['zsyj']=$agentInfo['nickname'].'['.$agentInfo['username'].']';
                }
                else
                {
                    $ancestors = explode(',',$agentInfo['ancestors']);
                    $agent = $ancestors[1]?Agent::find($ancestors[1]):[];
                    $arr['zsyj'] =$agent['nickname'].'['.$agent['username'].']';
                }
                $arr['bet_before']=number_format($datum['bet_before']/100,2);
                $arr['money']=number_format($datum['money']/100,2);
                $arr['bet_after']=number_format($datum['bet_after']/100,2);
                if ($datum['status']==1)
                {
                    $arr['status']="充值";
                    if ($datum['type']==1)
                    {
                        $arr['status']=$arr['status'].'(到款)';
                    }elseif ($datum['type']==2)
                    {
                        $arr['status']=$arr['status'].'(签单)';
                    }elseif ($datum['type']==3)
                    {
                        $arr['status']=$arr['status'].'(移分)';
                    }elseif ($datum['type']==4){
                        $arr['status']=$arr['status'].'(按比例)';
                    }elseif ($datum['type']==5)
                    {
                        $arr['status']=$arr['status'].'(支付宝)';
                    }elseif ($datum['type']==6)
                    {
                        $arr['status']=$arr['status'].'(微信)';
                    }
                }else{
                    $arr['status']='提现';
                }
                $arr['remark']=$datum['remark'];
                $excel[] = $arr;
            }
            try {
                exportExcel($head, $excel, date('Y-m-d H:i:s',time()).'代理充值提现查询', '', true);
            } catch (\PHPExcel_Reader_Exception $e) {
            } catch (\PHPExcel_Exception $e) {
            }
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
        return view('agentBill.list',['list'=>$data,'input'=>$request->all(),'limit'=>$limit,'user'=>User::getAllUser()]);
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
            $begin = strtotime($request->input('begin'))+ config('admin.beginTime');
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
            $end = strtotime('+1day',$begin)+ config('admin.beginTime');
            $request->offsetSet('begin',date('Y-m-d',$begin));
            $request->offsetSet('end',date('Y-m-d',$begin));
        }
        $sql->whereBetween('agent_billflow.creatime',[$begin,$end]);
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
        return view('agentBill.list',['list'=>$data,'input'=>$request->all(),'limit'=>$limit,'user'=>User::getAllUser()]);
    }
}