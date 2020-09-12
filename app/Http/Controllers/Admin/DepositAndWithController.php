<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Billflow;
use App\Models\Draw;
use App\Models\HqUser;
use App\Models\Pay;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepositAndWithController extends Controller
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
        if (true==$request->has('account'))
        {
            $map['account']=HttpFilter($request->input('account'));
        }
        if (true==$request->has('user_type'))
        {
            $map['user_type']=HttpFilter($request->input('user_type'));
        }
        if (true==$request->has('create_by'))
        {
            $map['create_by']=$request->input('create_by');
        }
        $dateArr = $this->getDateTimePeriodByBeginAndEnd($startDate,$endDateTime);
        //获取第一天的数据
        $bill = new Billflow();
        $bill->setTable('user_billflow_'.$dateArr[0]);
        $sql = $bill->leftJoin('user','user_billflow_'.$dateArr[0].'.user_id','=','user.user_id')
            ->select('user_billflow_'.$dateArr[0].'.*','user.account','user.agent_id','user.user_type');
        for ($i=1;$i<count($dateArr);$i++)
        {
            $b = new Billflow();
            $b->setTable('user_billflow_'.$dateArr[$i]);
            $d = $b->leftJoin('user','user_billflow_'.$dateArr[$i].'.user_id','=','user.user_id')
                ->select('user_billflow_'.$dateArr[$i].'.*','user.account','user.agent_id','user.user_type');
            $sql->unionAll($d);
        }
        if (true==$request->has('business_name'))
        {
            $map['business_id']=$request->input('business_name');
        }
        if (true==$request->has('excel'))
        {
            $head = array('时间','用户名称[账号]','直属上级[账号]','直属一级[账号]','操作前金额','充值提现金额','操作后金额','操作类型','操作人');
            $excelData = DB::table(DB::raw("({$sql->toSql()}) as a"))->mergeBindings($sql->getQuery())->where($map)->get()->toArray();
            $excel = array();
            foreach ($excelData as $key=>$datum)
            {
                $a['creatime']=date('Y-m-d H:i:s',$datum->creatime);
                $a['user'] = $datum->nickname.'['.$datum->account.']';
                $user = $datum->user_id?HqUser::find($datum->user_id):[];
                $sj = $user['agent_id']?Agent::find($user['agent_id']):[];
                $a['sj'] = $sj['nickname'].'['.$sj['username'].']';
                if ($sj['parent_id']==0)
                {
                    $a['zs'] = $sj['nickname'].'['.$sj['username'].']';
                }else{
                    $ancestors = explode(',',$sj['ancestors']);
                    $zs = $ancestors[1]?Agent::find($ancestors[1]):[];
                    $a['zs'] = $zs['nickname'].'['.$zs['username'].']';
                }

                $a['bet_before']=number_format($datum->bet_before/100,2);
                $a['money']=number_format($datum->score/100,2);
                $a['bet_after']=number_format($datum->bet_after/100,2);
                if ($datum->business_id==0)
                {
                    if ($datum->status==1)
                    {
                        $a['status']="充值";
                        if ($datum->pay_type==1)
                        {
                            $a['status']=$a['status'].'(到款)';
                        }elseif ($datum->pay_type==2)
                        {
                            $a['status']=$a['status'].'(签单)';
                        }elseif ($datum->pay_type==3)
                        {
                            $a['status']=$a['status'].'(移分)';
                        }elseif ($datum->pay_type==4){
                            $a['status']=$a['status'].'(按比例)';
                        }elseif ($datum->pay_type==5)
                        {
                            $a['status']=$a['status'].'(支付宝)';
                        }elseif ($datum->pay_type==6)
                        {
                            $a['status']=$a['status'].'(微信)';
                        }
                    }else{
                        $a['status']='提现';
                    }
                }
                else
                {
                    $a['status']=$datum->business_name;
                }
                $a['remark']=$datum->remark;
                $excel[] = $a;
            }
            try {
                exportExcel($head, $excel, date('Y-m-d H:i:s',time()).'会员充值提现查询', '', true);
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
        $data = DB::table(DB::raw("({$sql->toSql()}) as a"))->mergeBindings($sql->getQuery())->where($map)->whereIn('status',[1,3])->orderBy('creatime','desc')->paginate($limit)->appends($request->all());
        foreach ($data as $key=>$datum)
        {
            //获取直属上级
            $userInfo = $datum->user_id?HqUser::find($datum->user_id):[];
            $agent=$userInfo['agent_id']?Agent::find($userInfo['agent_id']):[];
            $data[$key]->sj['nickname']=$agent['nickname'];
            $data[$key]->sj['username']=$agent['username'];
            if ($agent['parent_id']==0)
            {
                $data[$key]->zsyj['nickname']=$agent['nickname'];
                $data[$key]->zsyj['username']=$agent['username'];
            }
            else
            {
                $idArr = explode(',',$agent['ancestors']);
                $zsyj = $idArr[1]?Agent::find($idArr[1]):[];
                $data[$key]->zsyj['nickname']=$zsyj['nickname'];
                $data[$key]->zsyj['username']=$zsyj['username'];
            }
            $data[$key]->creatime=date('Y-m-d H:i:s',$datum->creatime);
        }
        return view('daw.list',['list'=>$data,'limit'=>$limit,'input'=>$request->all(),'business'=>Pay::getAllPayList(),'user'=>User::getAllUser()]);
    }

    /**
     * 根据会员id查询充值提现记录
     * @param $userId
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function getRecordByUserId($userId,Request $request)
    {
        $map = array();
        $user = $userId?HqUser::find($userId):[];
        $request->offsetSet('account',$user['account']);
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
        if (true==$request->has('account'))
        {
            $map['account']=$request->input('account');
        }
        if (true==$request->has('business_name'))
        {
            $map['business_id']=$request->input('business_name');
        }
        if (true==$request->has('create_by'))
        {
            $map['create_by']=$request->input('create_by');
        }
        $dateArr = $this->getDateTimePeriodByBeginAndEnd($startDate,$endDateTime);
        //获取第一天的数据
        $bill = new Billflow();
        $bill->setTable('user_billflow_'.$dateArr[0]);
        $sql = $bill->leftJoin('user','user_billflow_'.$dateArr[0].'.user_id','=','user.user_id')
            ->select('user_billflow_'.$dateArr[0].'.*','user.account','user.agent_id');
        //dump($sql->get());
        for ($i=1;$i<count($dateArr);$i++)
        {
            $b = new Billflow();
            $b->setTable('user_billflow_'.$dateArr[$i]);
            $d = $b->leftJoin('user','user_billflow_'.$dateArr[$i].'.user_id','=','user.user_id')
                ->select('user_billflow_'.$dateArr[$i].'.*','user.account','user.agent_id');
            $sql->unionAll($d);
        }
        if (true==$request->has('limit'))
        {
            $limit = $request->input('limit');
        }
        else
        {
            $limit = 10;
        }
        $data = DB::table(DB::raw("({$sql->toSql()}) as a"))->mergeBindings($sql->getQuery())->where($map)->whereIn('status',[1,3])->orderBy('creatime','desc')->paginate($limit)->appends($request->all());
        foreach ($data as $key=>$datum)
        {
            //获取直属上级
            $userInfo = $datum->user_id?HqUser::find($datum->user_id):[];
            $agent=$userInfo['agent_id']?Agent::find($userInfo['agent_id']):[];
            $data[$key]->sj['nickname']=$agent['nickname'];
            $data[$key]->sj['username']=$agent['username'];
            if ($agent['parent_id']==0)
            {
                $data[$key]->zsyj['nickname']=$agent['nickname'];
                $data[$key]->zsyj['username']=$agent['username'];
            }
            else
            {
                $idArr = explode(',',$agent['ancestors']);
                $zsyj = $idArr[1]?Agent::find($idArr[1]):[];
                $data[$key]->zsyj['nickname']=$zsyj['nickname'];
                $data[$key]->zsyj['username']=$zsyj['username'];
            }
            $data[$key]->creatime=date('Y-m-d H:i:s',time());
        }
        return view('daw.list',['list'=>$data,'limit'=>$limit,'input'=>$request->all(),'business'=>Pay::getAllPayList(),'user'=>User::getAllUser()]);
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
     * 获取全部代理
     * @return User[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getAgentAllList(){
        return User::get();
    }

    /**
     * 根据代理id获取数据
     * @param $agentId
     * @param $data
     * @return mixed
     */
    public function getAgentInfoByAgentId($agentId,$data){
        foreach ($data as $key=>$value){
            if($agentId==$value['id']){
                return $data[$key];
                continue;
            }
        }
    }

    public function getDirectlyAgent($agentId){
        $agentList = $this->getAgentAllList();
        return $this->getRecursiveAgent($agentId,$agentList);
    }

    public function getRecursiveAgent($agentId,$agentList){
        $info = $this->getAgentInfoByAgentId($agentId,$agentList);
        if ($info['parent_id']==0){
            return $info;
        }else{
            return $this->getRecursiveAgent($info['parent_id'],$agentList);
        }
    }
}