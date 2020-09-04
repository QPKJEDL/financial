<?php


namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Czrecord;
use App\Models\HqUser;
use App\Models\Pay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentTripartiteController extends Controller
{
    /**
     * 数据列表
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $map = array();
        $map['czrecord.status']=1;
        if (true==$request->has('business_id'))
        {
            $businessId = $request->input('business_id');
            $pay = Pay::where('business_id','=',$businessId)->first();
            $map['czrecord.business_id']=$businessId;
            $request->offsetSet('business_name',$pay['service_name']);
        }
        else
        {
            $request->offsetSet('business_name','全部');
        }
        if (true==$request->has('begin')){
            $begin = strtotime($request->input('begin'));
        }
        else
        {
            $begin = strtotime(date('Y-m-d',time()));
            $request->offsetSet('begin',date('Y-m-d',time()));
        }
        if (true==$request->has('end'))
        {
            $end = strtotime('+1day',strtotime($request->input('end')))-1;
        }else{
            $end = strtotime('+1day',strtotime(date('Y-m-d',time())))-1;
            $request->offsetSet('end',date('Y-m-d',time()));
        }
        if (true==$request->has('account'))
        {
            $map['agent_users.username']=$request->input('account');
        }
        if (true==$request->has('user_type'))
        {
            $map['agent_users.userType']=$request->input('user_type');
        }
        $sql = Czrecord::query();
        $sql->leftJoin('user','user.user_id','=','czrecord.user_id')
            ->leftJoin('agent_users','user.agent_id','=','agent_users.id')
            ->select('czrecord.user_id','czrecord.status',DB::raw('SUM(score) as score'),'agent_users.nickname','agent_users.username','agent_users.parent_id','agent_users.proportion','agent_users.userType');
        $data = $sql->where($map)->where('czrecord.type','=',0)->where('czrecord.business_id','!=',0)->whereBetween('czrecord.creatime',[$begin,$end])->orderBy('score','desc')->groupBy('agent_users.id')->get()->toArray();
        foreach ($data as $key=>&$datum)
        {
            $user = $datum['user_id']?HqUser::find($datum['user_id']):[];
            $sj = $user['agent_id']?Agent::find($user['agent_id']):[];
            if ($sj['parent_id']==0)
            {
                $datum['zs']=$sj;
            }
            else
            {
                $ancestors = explode(',',$sj['ancestors']);
                $datum['zs']=$ancestors[1]?Agent::find($ancestors[1]):[];
            }
        }
        if (true==$request->has('excel'))
        {
            $head = array('商户名称','直属代理账户','一级代理账户','充值金额');
            $excel = array();
            foreach ($data as $key=>&$datum)
            {
                $arr = array();
                $arr['service_name']=$request->input('business_name');
                $arr['sj']=$datum['username'].'['.$datum['nickname'].']【'.$datum['proportion'].'%】';
                if ($datum['parent_id']==0)
                {
                    $arr['zs']=$arr['sj'];
                }
                else
                {
                    $ancestors = explode(',',$sj['ancestors']);
                    $arr['zs']=$ancestors[1]?Agent::find($ancestors[1]):[];
                }
                $arr['score']=number_format($datum['score']/100,2);
                $excel[] = $arr;
            }
            try {
                exportExcel($head, $excel, date('Y-m-d H:i:s',time()).'代理第三方支付统计', '', true);
            } catch (\PHPExcel_Reader_Exception $e) {
            } catch (\PHPExcel_Exception $e) {
            }
        }
        return view('tripartite.list',['list'=>$data,'input'=>$request->all(),'business'=>Pay::getAllPayList()]);
    }
}