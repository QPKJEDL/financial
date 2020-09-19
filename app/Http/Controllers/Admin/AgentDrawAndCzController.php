<?php


namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentBill;
use App\Models\Billflow;
use App\Models\HqUser;
use App\Models\Pay;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentDrawAndCzController extends Controller
{
    public function index(Request $request)
    {
        $map = array();
        if (true==$request->has('begin'))
        {
            $startDate = $request->input('begin');
        }
        else
        {
            $startDate = date('Y-m-d',time());
            $request->offsetSet('begin',date('Y-m-d',time()));
        }
        if (true==$request->has('end'))
        {
            $endDate = $request->input('end');
            $endDateTime = date('Y-m-d H:i:s',strtotime('+1day',strtotime($endDate)));
        }
        else
        {
            $endDate = date('Y-m-d',time());
            $endDateTime = date('Y-m-d H:i:s',strtotime('+1day',strtotime($endDate)));
            $request->offsetSet('end',date('Y-m-d',time()));
        }
        if (true==$request->has('status'))
        {
            if ($request->input('status')==1)
            {
                $map['status']=$request->input('status');
            }
        }
        if (true==$request->has('userType'))
        {
            $map['user_type']=$request->input('userType');
        }
        if (true==$request->has('create_by'))
        {
            if ($request->input('create_by')!=0)
            {
                $map['create_by']=$request->input('create_by');
            }
        }
        $dateArr = $this->getDateTimePeriodByBeginAndEnd($startDate,$endDateTime);
        //获取第一天的数据
        $bill = new Billflow();
        $bill->setTable('user_billflow_'.$dateArr[0]);
        $sql = $bill->select('creatime',DB::raw('1 as user_type'),'user_id','nickname','agent_name','fir_name','score as money','bet_before',
        'bet_after','create_by','status','pay_type','business_id','business_name')->whereRaw('status in (1,3)');
        for ($i=1;$i<count($dateArr);$i++)
        {
            $b = new Billflow();
            $b->setTable('user_billflow_'.$dateArr[$i]);
            $d= $b->select('creatime',DB::raw('1 as user_type'),'user_id','nickname','agent_name','fir_name','score as money','bet_before',
                'bet_after','create_by','status','pay_type','business_id','business_name')->whereRaw('status in (1,3)');
            $sql->unionAll($d);
        }
        $agentBill = AgentBill::query()->select('creatime',DB::raw('2 as user_type'),'agent_id as user_id','agent_name as nickname','top_name as agent_name',
            'fir_name','money','bet_before','bet_after','create_by','status','type as pay_type',DB::raw('0 as business_id'),DB::raw('0 as business_name'));
        $sql->unionAll($agentBill);
        if (true==$request->has('limit'))
        {
            $limit = $request->input('limit');
        }
        else
        {
            $limit = 100;
        }
        $dataSql = DB::table(DB::raw("({$sql->toSql()}) as a"))->mergeBindings($sql->getQuery())->where($map);
        if (true==$request->has('create_by'))
        {
            $user = User::query()->select('id')->get()->toArray();
            if ($request->input('create_by')==0)
            {
                $dataSql->whereIn('create_by',$user);
            }
        }
        if (true==$request->has('account'))
        {
            $arr = array();
            if (HqUser::where('account','=',$request->input('account'))->exists())
            {
                $info = HqUser::query()->select('user_id')->where("account",'=',$request->input('account'))->first();
                $arr[]['user_id']=$info['user_id'];
            }
            if (Agent::where('username','=',$request->input('account'))->exists())
            {
                $info = Agent::query()->select('id as user_id')->where('username','=',$request->input('account'))->first();
                $arr[]['user_id']=$info['user_id'];
            }
            $dataSql->whereIn('user_id',$arr);
        }
        if (true==$request->has('business_name'))
        {
            if ($request->input('business_name')==0)
            {
                $dataSql->where('business_id','>',0);
            }
            else
            {
                $dataSql->where('business_id','=',$request->input('business_name'));
            }
        }
        if (true==$request->has('status'))
        {
            if ($request->input('status')==2)
            {
                $dataSql->whereRaw('status in (2,3) and pay_type=0');
            }else if ($request->input('status')==3)
            {
                $dataSql->whereRaw('status in (2,3) and pay_type=1');
            }
        }
        $dataSql->whereRaw('creatime between '.strtotime($startDate).' and '.(strtotime($endDateTime)-1).'');
        if (true==$request->has('excel'))
        {
            $excel = $dataSql->orderBy('creatime','desc')->get()->toArray();
            foreach ($excel as $key=>$value)
            {
                $excel[$key]->creatime = date('Y-m-d H:i:s',$value->creatime);
                if ($value->user_type==1)
                {
                    $excel[$key]->user = $value->user_id?HqUser::find($value->user_id):[];
                    $excel[$key]->sj = $value->user['agent_id']?Agent::find($value->user['agent_id']):[];
                    if ($excel[$key]->sj['parent_id']==0)
                    {
                        $excel[$key]->zs = $value->sj;
                    }
                    else
                    {
                        $ancestors = explode(',',$value->sj['ancestors']);
                        $excel[$key]->zs = $ancestors[1]?Agent::find($ancestors[1]):[];
                    }
                }
                else
                {
                    $excel[$key]->user = $value->user_id?Agent::find($value->user_id):[];
                    if ($value->user['parent_id']==0)
                    {
                        $excel[$key]->sj = $value->user;
                        $excel[$key]->zs = $value->user;
                    }
                    else
                    {
                        $excel[$key]->sj = $value->user['parent_id']?Agent::find($value->user['parent_id']):[];
                        if ($value->sj['parent_id']==0)
                        {
                            $excel[$key]->zs = $value->sj;
                        }
                        else
                        {
                            $ancestors = explode(',',$value->sj['ancestors']);
                            $excel[$key]->zs = $ancestors[1]?Agent::find($ancestors[1]):[];
                        }
                    }
                }
                $excel[$key]->creUser = $value->create_by?User::find($value->create_by):[];
            }
            $excelData = array();
            foreach ($excel as $key=>$value)
            {
                $arr = array();
                $arr['create_time']=$value->creatime;
                if ($value->user_type==1)
                {
                    $arr['user_type']='会员';
                    $arr['userName']=$value->nickname.'['.$value->user['account'].']';
                }
                else
                {
                    $arr['user_type']='代理';
                    $arr['userName']=$value->nickname.'['.$value->user['username'].']';
                }
                $arr['sj']=$value->agent_name.'['.$value->sj['username'].']';
                $arr['zs']=$value->fir_name.'['.$value->zs['username'].']';
                $arr['b']=number_format($value->bet_before/100,2);
                $arr['c']=number_format($value->money/100,2);
                $arr['d']=number_format($value->bet_after/100,2);
                if ($value->business_id==0)
                {
                    if ($value->status==1)
                    {
                        //$arr['e']='充值';
                        if ($value->pay_type==1)
                        {
                            $arr['e']='充值(到款)';
                        }
                        elseif ($value->pay_type==2)
                        {
                            $arr['e']='充值(签单)';
                        }
                        elseif ($value->pay_type==3)
                        {
                            $arr['e']='充值(移分)';
                        }
                        elseif ($value->pay_type==4)
                        {
                            $arr['e']='充值(按比例)';
                        }
                        elseif ($value->pay_type==5)
                        {
                            $arr['e']='充值(支付宝)';
                        }
                        elseif ($value->pay_type==6)
                        {
                            $arr['e']='充值(微信)';
                        }
                    }
                    elseif ($value->status==2 || $value->status==3)
                    {
                        $arr['e']='提现';
                    }
                }
                else
                {
                    $arr['e']=$value->business_name;
                }
                if ($value->creUser!=null)
                {
                    $arr['f']=$value->creUser['username'];
                }
                else
                {
                    $arr['f']='';
                }
                $excelData[] = $arr;
            }
            $head = array('时间','类型','用户名称[账号]','直属上级[账号]','直属一级[账号]','操作前金额','充值提现金额','操作后金额','操作类型','操作人');
            try {
                exportExcel($head, $excelData, '充值提现查询', '', true);
            } catch (\PHPExcel_Reader_Exception $e) {
            } catch (\PHPExcel_Exception $e) {
            }
        }
        $data =$dataSql->orderBy('creatime','desc')->paginate($limit);
        foreach ($data as $key=>$datum)
        {
            $data[$key]->creatime = date('Y-m-d H:i:s',$datum->creatime);
            if ($datum->user_type==1)
            {
                $data[$key]->user = $datum->user_id?HqUser::find($datum->user_id):[];
                $data[$key]->sj = $datum->user['agent_id']?Agent::find($datum->user['agent_id']):[];
                if ($data[$key]->sj['parent_id']==0)
                {
                    $data[$key]->zs = $datum->sj;
                }
                else
                {
                    $ancestors = explode(',',$datum->sj['ancestors']);
                    $data[$key]->zs = $ancestors[1]?Agent::find($ancestors[1]):[];
                }
            }
            else
            {
                $data[$key]->user = $datum->user_id?Agent::find($datum->user_id):[];
                if ($datum->user['parent_id']==0)
                {
                    $data[$key]->sj = $datum->user;
                    $data[$key]->zs = $datum->user;
                }
                else
                {
                    $data[$key]->sj = $datum->user['parent_id']?Agent::find($datum->user['parent_id']):[];
                    if ($datum->sj['parent_id']==0)
                    {
                        $data[$key]->zs = $datum->sj;
                    }
                    else
                    {
                        $ancestors = explode(',',$datum->sj['ancestors']);
                        $data[$key]->zs = $ancestors[1]?Agent::find($ancestors[1]):[];
                    }
                }
            }
            $data[$key]->creUser = $datum->create_by?User::find($datum->create_by):[];
        }
        return view('agentBill.list',['user'=>User::getAllUser(),'list'=>$data,'limit'=>$limit,'input'=>$request->all(),'business'=>Pay::getAllPayList(),'min'=>config('admin.minDate')]);
    }

    /**
     * 根据开始时间结束时间获取中间得时间段
     * @param $startDate
     * @param $endDate
     * @return array
     */
    public function getDateTimePeriodByBeginAndEnd($startDate,$endDate){
        $arr = array();
        $start_date = date("Y-m-d",strtotime($startDate));
        $end_date = date("Y-m-d",strtotime($endDate));
        for ($i = strtotime($start_date); $i <= strtotime($end_date);$i += 86400){
            $arr[] = date('Ymd',$i);
        }
        return $arr;
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
        $sql = AgentBill::query()->select('creatime',DB::raw('2 as user_type'),'agent_id as user_id','agent_name as nickname','top_name as agent_name',
            'fir_name','money','bet_before','bet_after','create_by','status','type as pay_type',DB::raw('0 as business_id'),DB::raw('0 as business_name'));
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
        foreach ($data as $key=>$datum)
        {
            $data[$key]->creatime = date('Y-m-d H:i:s',$datum->creatime);
            if ($datum->user_type==1)
            {
                $data[$key]->user = $datum->user_id?HqUser::find($datum->user_id):[];
                $data[$key]->sj = $datum->user['agent_id']?Agent::find($datum->user['agent_id']):[];
                if ($data[$key]->sj['parent_id']==0)
                {
                    $data[$key]->zs = $datum->sj;
                }
                else
                {
                    $ancestors = explode(',',$datum->sj['ancestors']);
                    $data[$key]->zs = $ancestors[1]?Agent::find($ancestors[1]):[];
                }
            }
            else
            {
                $data[$key]->user = $datum->user_id?Agent::find($datum->user_id):[];
                if ($datum->user['parent_id']==0)
                {
                    $data[$key]->sj = $datum->user;
                    $data[$key]->zs = $datum->user;
                }
                else
                {
                    $data[$key]->sj = $datum->user['parent_id']?Agent::find($datum->user['parent_id']):[];
                    if ($datum->sj['parent_id']==0)
                    {
                        $data[$key]->zs = $datum->sj;
                    }
                    else
                    {
                        $ancestors = explode(',',$datum->sj['ancestors']);
                        $data[$key]->zs = $ancestors[1]?Agent::find($ancestors[1]):[];
                    }
                }
            }
            $data[$key]->creUser = $datum->create_by?User::find($datum->create_by):[];
        }
        return view('agentBill.list',['list'=>$data,'input'=>$request->all(),'min'=>config('admin.minDate'),'limit'=>$limit,'user'=>User::getAllUser(),'business'=>Pay::getAllPayList()]);
    }
}