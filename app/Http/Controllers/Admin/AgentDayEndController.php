<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\GameRecord;
use App\Models\HqUser;
use App\Models\LiveReward;
use App\Models\Maintain;
use App\Models\Order;
use App\Models\User;
use App\Models\UserRebate;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Models\Desk;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AgentDayEndController extends Controller
{

    public function index(Request $request)
    {
        $request->offsetSet('type',1);
        $map = array();
        if (false==$request->has('account'))
        {
            $map['agent_users.parent_id']=0;
        }
        $sql = UserRebate::query();
        $sql->leftJoin('agent_users','agent_users.id','=','user_rebate.agent_id')
            ->select('user_rebate.agent_id','agent_users.nickname','agent_users.username','agent_users.fee','agent_users.userType','agent_users.proportion','agent_users.pump',
            DB::raw('SUM(washMoney) as washMoney'),DB::raw('SUM(getMoney) as getMoney'),DB::raw('SUM(betMoney) as betMoney'),DB::raw('SUM(feeMoney) as feeMoney'));
        if (true==$request->has('begin'))
        {
            $begin = strtotime($request->input('begin'))+config('admin.beginTime');
            if (true==$request->has('end'))
            {
                if (date('Y-m-d',time())==$request->has('end'))
                {
                    $end = strtotime('+1day',strtotime($request->input('end'))) + +config('admin.beginTime');
                }
                else
                {
                    $end = strtotime($request->input('end'))+config('admin.beginTime');
                }
            }
            else
            {
                $end = strtotime('+1day',$begin);
                $request->offsetSet('end',date('Y-m-d',$end));
            }
        }
        else
        {
            $begin = strtotime(date('Y-m-d',time())) + config('admin.beginTime');
            $end = strtotime('+1day',$begin);
            $request->offsetSet('begin',date('Y-m-d',$begin));
            $request->offsetSet('end',date('Y-m-d',$begin));
        }
        if (true==$request->has('userType'))
        {
            $map['agent_users.userType']=(int)$request->input('userType');
        }
        if (true==$request->has('account'))
        {
            $map['agent_users.username']=$request->input('account');
        }
        else
        {
            $request->offsetSet('account','');
        }
        $data = $sql->where($map)->whereBetween('user_rebate.creatime',[$begin,$end])->groupBy('user_rebate.agent_id')->get()->toArray();
        //获取要统计的数据id
        $info = UserRebate::query()->select('id','user_id','creatime')->whereBetween('creatime',[$begin,$end])->groupBy('id','creatime','user_id')->get();
        $idArray = array();
        foreach ($info as $key=>$v)
        {
            $idArray[] = $v['id'];
        }
        $sqlDataMoney = UserRebate::query()->whereIn('id',$idArray)->whereBetween('creatime',[$begin,$end])->groupBy('user_id','creatime');
        $sumData['washMoney'] = DB::table(DB::raw("({$sqlDataMoney->toSql()}) as s"))->mergeBindings($sqlDataMoney->getQuery())->sum('washMoney');
        $sumData['pumpSy']=0;
        $sumData['reward']=0;
        $sumData['code']=0;
        $sumData['zg']=0;
        $sumData['sy']=0;
        $sumData['gs']=0;
        $sumData['getMoney']=DB::table(DB::raw("({$sqlDataMoney->toSql()}) as s"))->mergeBindings($sqlDataMoney->getQuery())->sum('getMoney');
        $sumData['betMoney']=0;
        $sumData['feeMoney']=0;
        $bool = $this->checkIsToDay($request->input('begin'),$request->input('end'));
        if ($bool)
        {
            $order = new Order();
            $order->setTable('order_'.date( 'Ymd',time()));
            $orderData = $order->get()->toArray();
            $sumData['washMoney'] = $sumData['washMoney']+$this->getToDaySumBetMoney($orderData);
            $sumData['getMoney'] = $sumData['getMoney'] + $this->getToDayWinMoney($orderData);
            $sumData['betMoney'] = $sumData['betMoney'] + $this->getToDayCode($orderData);
            //打赏金额
            $sumData['reward'] = $sumData['reward'] + $this->getToDayReward($begin,$end);
            if (count($data)!=0)
            {
                foreach ($orderData as $key=>$datum)
                {
                    $betMoneyJson = json_decode($datum['bet_money'],true);
                    $userInfo = $datum['user_id']?HqUser::find($datum['user_id']):[];
                    $zyAgent = $this->getZsYjByAgentId($userInfo['agent_id']);
                    if (true==$request->has('account'))
                    {
                        if ($zyAgent['username']!=$request->input('account'))
                        {
                            continue;
                        }
                    }
                    if (true==$request->has('userType'))
                    {
                        if ($zyAgent['userType']!=$request->input('userType'))
                        {
                            continue;
                        }
                    }
                    $isExist = $this->getAgentIdIsExistInDataByAgentIdAndData($zyAgent['id'],$data);
                    if ($isExist['exist']==1)//存在
                    {
                        $index = $isExist['index'];
                        if ($datum['game_type']==1 || $datum['game_type']==2)
                        {
                            if ($datum['status']==1 || $datum['status']==4)
                            {
                                $data[$index]['washMoney']=$data[$index]['washMoney'] + array_sum($betMoneyJson);
                                if ($datum['game_type']==1)
                                {
                                    $data[$index]['betMoney']=$data[$index]['betMoney'] + $this->getBaccaratBetMoney($datum);
                                    if ($zyAgent['userType']==1)
                                    {
                                        $data[$index]['feeMoney']=$data[$index]['feeMoney'] + $this->bjlPump($datum);
                                    }
                                    else
                                    {
                                        $data[$index]['feeMoney']=$data[$index]['feeMoney'] + $this->xsBaccaratPump($datum);
                                    }
                                }
                                else
                                {
                                    $data[$index]['betMoney']=$data[$index]['betMoney'] + $this->getDragonAndTigerBetMoney($datum);
                                    if ($zyAgent['userType']==2)
                                    {
                                        $data[$index]['feeMoney']=$data[$index]['feeMoney'] + $this->xsDragonAndTigerPump($datum);
                                    }
                                    else
                                    {
                                        $data[$index]['feeMoney']=$data[$index]['feeMoney'] + 0;
                                    }
                                }
                            }
                            else
                            {
                                $data[$index]['washMoney']=$data[$index]['washMoney'] + 0;
                            }
                        }
                        elseif ($datum['game_type']==3)
                        {
                            if ($datum['status']==1 || $datum['status']==4)
                            {
                                $data[$index]['washMoney']=$data[$index]['washMoney'] + array_sum($betMoneyJson);
                                $data[$index]['betMoney']=$data[$index]['betMoney'] + array_sum($betMoneyJson);
                                if ($zyAgent['userType']==2)
                                {
                                    $data[$index]['feeMoney']=$data[$index]['feeMoney'] + $this->xsNiuNiuPump($datum);
                                }
                                else
                                {
                                    $data[$index]['feeMoney']=$data[$index]['feeMoney'] + 0;
                                }
                            }
                            else
                            {
                                $data[$index]['washMoney']=$data[$index]['washMoney'] + 0; + 0;
                            }
                        }
                        elseif ($datum['game_type']==4)
                        {
                            if ($datum['status']==1 || $datum['status']==4)
                            {
                                $data[$index]['washMoney'] = $data[$index]['washMoney'] + array_sum($betMoneyJson);
                                $data[$index]['betMoney'] = $data[$index]['betMoney'] + array_sum($betMoneyJson);
                                if ($zyAgent['userType']==2)
                                {
                                    $data[$index]['feeMoney'] = $data[$index]['feeMoney'] + $this->xsSanGongPump($datum);
                                }
                                else
                                {
                                    $data[$index]['feeMoney']=$data[$index]['feeMoney'] + 0;
                                }
                            }
                            else
                            {
                                $data[$index]['washMoney']=$data[$index]['washMoney'] + 0; + 0;
                            }
                        }
                        elseif ($datum['game_type']==5)
                        {
                            if ($datum['status']==1 || $datum['status']==4)
                            {
                                $data[$index]['washMoney'] = $data[$index]['washMoney'] + array_sum($betMoneyJson);
                                $data[$index]['betMoney'] = $data[$index]['betMoney'] + array_sum($betMoneyJson);
                                if ($zyAgent['userType']==2)
                                {
                                    $data[$index]['feeMoney'] = $data[$index]['feeMoney'] + $this->xsA89Pump($datum);
                                }
                                else
                                {
                                    $data[$index]['feeMoney']=$data[$index]['feeMoney'] + 0;
                                }
                            }
                            else
                            {
                                $data[$index]['washMoney']=$data[$index]['washMoney'] + 0; + 0;
                            }
                        }
                    }
                    else
                    {
                        $arrData = array();
                        $arrData['agent_id']=$zyAgent['id'];
                        $arrData['nickname']=$zyAgent['nickname'];
                        $arrData['username']=$zyAgent['username'];
                        $arrData['userType']=$zyAgent['userType'];
                        $arrData['fee']=$zyAgent['fee'];
                        $arrData['proportion']=$zyAgent['proportion'];
                        $arrData['washMoney']=0;
                        $arrData['betMoney']=0;
                        $arrData['feeMoney']=0;
                        $arrData['getMoney']=$datum['get_money'];
                        if ($datum['game_type']==1 || $datum['game_type']==2)
                        {
                            if ($datum['status']==1 || $datum['status']==4)
                            {
                                $arrData['washMoney']=$arrData['washMoney'] + array_sum($betMoneyJson);
                                if ($datum['game_type']==1)
                                {
                                    $arrData['betMoney']=$arrData['betMoney'] + $this->getBaccaratBetMoney($datum);
                                    if ($zyAgent['userType']==1)
                                    {
                                        $arrData['feeMoney']=$arrData['feeMoney'] + $this->bjlPump($datum);
                                    }
                                    else
                                    {
                                        $arrData['betMoney']=$arrData['betMoney'] + $this->getDragonAndTigerBetMoney($datum);
                                        $arrData['feeMoney'] = $arrData['feeMoney'] + $this->xsBaccaratPump($datum);
                                    }
                                }
                                else
                                {
                                    if ($zyAgent['userType']==1)
                                    {
                                        $arrData['feeMoney']=$arrData['feeMoney']+ 0;
                                    }
                                    else
                                    {
                                        $arrData['feeMoney'] = $arrData['feeMoney'] + $this->xsDragonAndTigerPump($datum);
                                    }
                                }
                            }
                            else
                            {
                                $arrData['washMoney'] = $arrData['washMoney'] + 0;
                            }
                        }
                        elseif ($datum['game_type']==3)
                        {
                            if ($datum['status']==1 || $datum['status']==4)
                            {
                                $arrData['washMoney']=$arrData['washMoney'] + $this->getNiuNiuBetMoney($betMoneyJson);
                                $arrData['betMoney']=$arrData['betMoney'] + $this->getNiuNiuBetMoney($betMoneyJson);
                                if ($zyAgent['userType']==1)
                                {
                                    $arrData['feeMoney']=$arrData['feeMoney']+ 0;
                                }
                                else
                                {
                                    $arrData['feeMoney']= $arrData['feeMoney'] + $this->xsNiuNiuPump($datum);
                                }
                            }
                            else
                            {
                                $arrData['washMoney'] = $arrData['washMoney'] + 0;
                            }
                        }
                        elseif ($datum['game_type']==4)
                        {
                            if ($datum['status']==1 || $datum['status']==4)
                            {
                                $arrData['washMoney'] = $arrData['washMoney'] + $this->getSanGongBetMoney($betMoneyJson);
                                $arrData['betMoney'] = $arrData['betMoney'] + $this->getSanGongBetMoney($betMoneyJson);
                                if ($zyAgent['userType']==1)
                                {
                                    $arrData['feeMoney']=$arrData['feeMoney']+ 0;
                                }
                                else
                                {
                                    $arrData['feeMoney'] = $arrData['feeMoney'] + $this->xsSanGongPump($datum);
                                }
                            }
                            else
                            {
                                $arrData['washMoney'] = $arrData['washMoney'] + 0;
                            }
                        }
                        elseif ($datum['game_type']==5)
                        {
                            if ($datum['status']==1 || $datum['status']==4)
                            {
                                $arrData['washMoney'] = $arrData['washMoney'] + $this->getA89BetMoney($betMoneyJson);
                                $arrData['betMoney'] = $arrData['betMoney'] + $this->getA89BetMoney($betMoneyJson);
                                if ($zyAgent['userType']==1)
                                {
                                    $arrData['feeMoney']=$arrData['feeMoney']+ 0;
                                }
                                else
                                {
                                    $arrData['feeMoney']= $arrData['feeMoney'] + $this->xsA89Pump($datum);
                                }
                            }
                            else
                            {
                                $arrData['washMoney'] = $arrData['washMoney'] + 0;
                            }
                        }
                        $data[] = $arrData;
                        continue;
                    }
                }
            }
            else
            {
                foreach ($orderData as $k=>$v){
                    $user = $v['user_id']?HqUser::find($v['user_id']):[];
                    if ($user['agent_id']!=0)
                    {
                        if (Agent::where('id','=',$user['agent_id'])->exists()){
                            $agent = $user['agent_id']?Agent::find($user['agent_id']):[];
                            $ancestors = explode(',',$agent['ancestors']);
                            $ancestors[] = $agent['id'];
                            $agentInfo = $ancestors[1]?Agent::find($ancestors[1]):[];
                            if (true==$request->has('account'))
                            {
                                if ($agentInfo['username']!=$request->input('account'))
                                {
                                    continue;
                                }
                            }
                            if (true==$request->has('userType'))
                            {
                                if ($agentInfo['userType']!=$request->input('userType'))
                                {
                                    continue;
                                }
                            }
                            $arr = $this->checkAgentIdIsExist($agentInfo['id'],$data);
                            if ($arr['exist']==1)
                            {
                                $a['agent_id']=$agentInfo['id'];
                                $a['nickname']=$agentInfo['nickname'];
                                $a['username']=$agentInfo['username'];
                                $a['userType']=$agentInfo['userType'];
                                if ($agentInfo['userType']==1){
                                    $a['fee']=$agentInfo['fee'];
                                }else{
                                    $a['pump']=$agentInfo['pump'];
                                }
                                $a['proportion']=$agentInfo['proportion'];
                                $a['reward']=0;
                                $betMoney = json_decode($v['bet_money'],true);
                                if($v['game_type']==1 || $v['game_type']==2)
                                {
                                    if ($v['status']==1 || $v['status']==4)
                                    {
                                        $a['washMoney'] = array_sum($betMoney);
                                        if ($v['game_type']==1)
                                        {
                                            $a['betMoney']= $this->getBaccaratBetMoney($v);
                                            if ($agentInfo['userType']==1)
                                            {
                                                $a['feeMoney']= $this->bjlPump($v);
                                            }
                                            else
                                            {
                                                $a['feeMoney'] = $this->xsBaccaratPump($v);
                                            }
                                        }
                                        else
                                        {
                                            $a['betMoney'] = $this->getDragonAndTigerBetMoney($v);
                                            if ($agentInfo['userType']==2)
                                            {
                                                $a['feeMoney'] =$this->xsDragonAndTigerPump($v);
                                            }
                                            else
                                            {
                                                $a['feeMoney'] = 0;
                                            }
                                        }
                                    }else{
                                        $a['feeMoney'] = 0;
                                        $a['betMoney']=0;
                                        $a['washMoney']=0;
                                    }
                                }
                                else if ($v['game_type']==3)
                                {
                                    $a['washMoney'] = array_sum($betMoney);
                                    if ($v['status']==1 || $v['status']==4)
                                    {
                                        $a['betMoney']=array_sum($betMoney);
                                        if ($agentInfo['userType']==2)
                                        {
                                            $a['feeMoney'] = $this->xsNiuNiuPump($v);
                                        }
                                        else
                                        {
                                            $a['feeMoney']=0;
                                        }
                                    }else{
                                        $a['betMoney']=0;
                                        $a['feeMoney']=0;
                                        $a['washMoney']=0;
                                    }
                                }
                                else if($v['game_type']==4)
                                {
                                    $a['washMoney']=array_sum($betMoney);
                                    if ($v['status']==1 || $v['status']==4)
                                    {
                                        $a['betMoney'] =array_sum($betMoney);
                                        if ($agentInfo['userType']==2)
                                        {
                                            $a['feeMoney'] = $this->xsSanGongPump($v);
                                        }
                                        else
                                        {
                                            $a['feeMoney']=0;
                                        }
                                    }else{
                                        $a['betMoney']=0;
                                        $a['feeMoney']=0;
                                        $a['washMoney']=0;
                                    }
                                }
                                else if($v['game_type']==5)
                                {
                                    $a['washMoney']=array_sum($betMoney);
                                    if ($v['status']==1 || $v['status']==4)
                                    {
                                        $a['betMoney'] =array_sum($betMoney);
                                        if ($agentInfo['userType']==2)
                                        {
                                            $a['feeMoney']=$this->xsA89Pump($v);
                                        }
                                        else
                                        {
                                            $a['feeMoney']=0;
                                        }
                                    }else{
                                        $a['betMoney']=0;
                                        $a['feeMoney']=0;
                                        $a['washMoney']=0;
                                    }
                                }
                                $a['getMoney'] =$v['get_money'];
                                $data[] = $a;
                            }
                            else
                            {
                                $data[$arr['index']]['getMoney'] = $data[$arr['index']]['getMoney'] + $v['get_money'];
                                $betMoney = json_decode($v['bet_money'],true);
                                if($v['game_type']==1 || $v['game_type']==2)
                                {
                                    if ($v['status']==1 || $v['status']==4)
                                    {
                                        $data[$arr['index']]['washMoney'] = $data[$arr['index']]['washMoney']+ array_sum($betMoney);
                                        if ($v['game_type']==1)
                                        {
                                            $data[$arr['index']]['betMoney']= $data[$arr['index']]['betMoney'] + $this->getBaccaratBetMoney($v);
                                            if ($agentInfo['userType']==1)
                                            {
                                                $data[$arr['index']]['feeMoney']=$data[$arr['index']]['feeMoney'] + $this->bjlPump($v);
                                            }
                                            else
                                            {
                                                $data[$arr['index']]['feeMoney']=$data[$arr['index']]['feeMoney']+$this->xsBaccaratPump($v);
                                            }
                                        }
                                        else{
                                            $data[$arr['index']]['betMoney']= $data[$arr['index']]['betMoney'] + $this->getDragonAndTigerBetMoney($v);
                                            if ($agentInfo['userType']==2)
                                            {
                                                $data[$arr['index']]['feeMoney']=$data[$arr['index']]['feeMoney'] + $this->xsDragonAndTigerPump($v);
                                            }
                                            else
                                            {
                                                $data[$arr['index']]['feeMoney']=$data[$arr['index']]['feeMoney'] + 0;
                                            }
                                        }
                                    }
                                    else
                                    {
                                        $data[$arr['index']]['feeMoney']=$data[$arr['index']]['feeMoney'] + 0;
                                        $data[$arr['index']]['washMoney'] = $data[$arr['index']]['washMoney']+ 0;
                                    }
                                }else if ($v['game_type']==3){
                                    if ($v['status']==1 || $v['status']==4)
                                    {
                                        $data[$arr['index']]['washMoney'] = $data[$arr['index']]['washMoney'] + array_sum($betMoney);
                                        $data[$arr['index']]['betMoney']=$data[$arr['index']]['betMoney']+ array_sum($betMoney);
                                        if ($agentInfo['userType']==2)
                                        {
                                            $data[$arr['index']]['feeMoney']=$data[$arr['index']]['feeMoney'] + $this->xsNiuNiuPump($v);
                                        }
                                        else
                                        {
                                            $data[$arr['index']]['feeMoney']=$data[$arr['index']]['feeMoney'] + 0;
                                        }
                                    }
                                    else
                                    {
                                        $data[$arr['index']]['feeMoney']=$data[$arr['index']]['feeMoney'] + 0;
                                        $data[$arr['index']]['washMoney'] = $data[$arr['index']]['washMoney']+ 0;
                                    }
                                }else if($v['game_type']==4){
                                    if ($v['status']==1 || $v['status']==4)
                                    {
                                        $data[$arr['index']]['washMoney']=$data[$arr['index']]['washMoney']+ array_sum($betMoney);
                                        $data[$arr['index']]['betMoney'] = $data[$arr['index']]['betMoney']+array_sum($betMoney);
                                        if ($agentInfo['userType']==2)
                                        {
                                            $data[$arr['index']]['feeMoney']=$data[$arr['index']]['feeMoney'] + $this->xsSanGongPump($v);
                                        }
                                        else
                                        {
                                            $data[$arr['index']]['feeMoney']=$data[$arr['index']]['feeMoney'] + 0;
                                        }
                                    }
                                    else
                                    {
                                        $data[$arr['index']]['feeMoney']=$data[$arr['index']]['feeMoney'] + 0;
                                        $data[$arr['index']]['washMoney'] = $data[$arr['index']]['washMoney']+ 0;
                                    }
                                }else if($v['game_type']==5){
                                    if ($v['status']==1 || $v['status']==4)
                                    {
                                        $data[$arr['index']]['washMoney']=$data[$arr['index']]['washMoney']+ array_sum($betMoney);
                                        $data[$arr['index']]['betMoney'] =$data[$arr['index']]['betMoney']+array_sum($betMoney);
                                        if ($agentInfo['userType']==2)
                                        {
                                            $data[$arr['index']]['feeMoney']=$data[$arr['index']]['feeMoney'] + $this->xsA89Pump($v);
                                        }
                                        else
                                        {
                                            $data[$arr['index']]['feeMoney']=$data[$arr['index']]['feeMoney'] + 0;
                                        }
                                    }
                                    else
                                    {
                                        $data[$arr['index']]['feeMoney']=$data[$arr['index']]['feeMoney'] + 0;
                                        $data[$arr['index']]['washMoney'] = $data[$arr['index']]['washMoney']+ 0;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        foreach ($data as $key=>&$datum)
        {
            $sumData['betMoney'] += $datum['betMoney'];
            if ($datum['userType']==1)
            {
                $datum['fee']=json_decode($datum['fee'],true);
                //洗码费
                $datum['code']=$datum['betMoney']*0.009;
                $sumData['code']=$sumData['code'] + $datum['code'];
                //占股收益
                $datum['zg']=-($datum['getMoney'] + $datum['code']) * ($datum['proportion']/100);
                $sumData['zg'] = $sumData['zg'] - $datum['zg'];
                //总收益
                if ($datum['zg']>0)
                {
                    $datum['sy'] = $datum['zg'] + $datum['feeMoney'] + $datum['code'];
                }
                else
                {
                    $datum['sy']=$datum['zg'] + $datum['feeMoney'] + $datum['code'];
                }

                $sumData['sy'] = $sumData['sy'] - $datum['sy'];
                //$sumData['feeMoney']=$sumData['feeMoney'] - $datum['feeMoney'];
                if ($datum['feeMoney']>0)
                {
                    $sumData['feeMoney'] = $sumData['feeMoney'] - $datum['feeMoney'];
                }
                else
                {
                    $sumData['feeMoney'] = $sumData['feeMoney'] + $datum['feeMoney'];
                }
                //总收益
                if ($datum['getMoney']>0)
                {
                    $datum['gs']= -$datum['getMoney'] - $datum['sy'];
                }
                else
                {
                    $datum['gs']= abs($datum['getMoney']) - $datum['sy'];
                }
                $sumData['gs']=$sumData['gs']+$datum['gs'];
            }
            else
            {
                $data[$key]['sy']=$datum['feeMoney'];
                $data[$key]['gs']=-($datum['getMoney'] + $datum['sy']);
                $sumData['sy'] = $sumData['sy'] - $datum['sy'];
                $sumData['gs']=$sumData['gs']+$datum['gs'];
                $sumData['pumpSy']=$sumData['pumpSy'] + $datum['feeMoney'];
            }
            //打赏金额
            //获取当前代理下的会员
            $userData = HqUser::where('agent_id','=',$datum['agent_id'])->select('user_id')->get();
            $money = LiveReward::query()->whereIn('user_id',$userData)->whereBetween('creatime',[$begin,$end])->sum('money');
            $datum['reward']=$money;
            $datum['is_exist']=count($this->isExist($datum['agent_id'],$request->input('begin'),$request->input('end')));
            $datum['is_exist_hqUser']=count($this->isExistHqUser($datum['agent_id'],$request->input('begin'),$request->input('end')));
        }
        if (true==$request->has('excel'))
        {
            $head = array('台类型','名称','账号','总押码','总赢','总洗码','总抽水','打赏金额','百/龙/牛/三/A','洗码费','抽水比例','抽水收益','占股','占股收益','总收益','公司收益');
            $excel = array();
            $d = array();
            $d['desk_type']='全部';
            $d['name']='总公司';
            $d['username']='admin';
            $d['betMoney']=number_format($sumData['betMoney']/100,2);

            if ($sumData['getMoney']>0)
            {
                $d['getMoney']=number_format(-$sumData['getMoney']/100,2);
            }
            else{
                $d['getMoney']=number_format($sumData['getMoney']/100,2);
            }
            $d['washMoney']=number_format(-$sumData['washMoney']/100,2);
            $d['feeMoney'] = number_format(-$sumData['feeMoney']/100,2);
            $d['reward']=number_format($sumData['reward']/100,2);
            $d['fee']='0.9/0.9/0.9/0.9/0.9';
            $d['code']=number_format(-$sumData['code']/100,2);
            $d['pump']='100%';
            $d['puSy']=number_format(-$sumData['pumpSy']/100,2);
            $d['proportion']='100%';
            $d['zg']=number_format($sumData['zg']/100,2);
            $d['sy']=number_format($sumData['sy']/100,2);
            $d['gs']=number_format($sumData['gs']/100,2);
            $excel[] = $d;
            foreach ($data as $key=>&$datum)
            {
                $a = array();
                $a['desk_type']='全部';
                $a['name']=$datum['nickname'];
                $a['username']=$datum['username'];
                $a['washMoney']=number_format($datum['washMoney']/100,2);
                if ($datum['getMoney']>0)
                {
                    $a['getMoney']=number_format(-$datum['getMoney']/100,2);
                }
                else{
                    $a['getMoney']=number_format($datum['getMoney']/100,2);
                }
                $a['betMoney']=number_format($datum['betMoney']/100,2);
                if ($datum['userType']==1){
                    $a['feeMoney']=number_format($datum['feeMoney']/100,2);
                }else{
                    $a['feeMoney']='-';
                }
                $a['reward']=number_format($datum['reward']/100,2);
                if ($datum['userType']==1)
                {
                    $a['fee']=$datum['fee']['baccarat'].'/'.$datum['fee']['dragonTiger'].'/'.$datum['fee']['niuniu'].'/'.$datum['fee']['sangong'].'/'.$datum['fee']['A89'];
                }else
                {
                    $a['fee']='-';
                }

                if ($datum['userType']==1)
                {
                    $a['code']=number_format($datum['code']/100,2);
                    $a['pump']='-';
                    $a['puSy']='-';
                    $a['proportion']=$datum['proportion'].'%';
                    $a['zg'] = number_format($datum['zg']/100,2);
                }else
                {
                    $a['code']='-';
                    $a['pump']=$datum['pump'].'%';
                    $a['puSy']=number_format($datum['feeMoney']/100,2);
                    $a['proportion']='-';
                    $a['zg']=0.00;
                }
                $a['sy'] = number_format($datum['sy']/100,2);
                $a['gs']=number_format($datum['gs']/100,2);
                $excel[] = $a;
            }
            try {
                exportExcel($head, $excel, date('Y-m-d H:i:s',time()).'代理日结', '', true);
            } catch (\PHPExcel_Reader_Exception $e) {
            } catch (\PHPExcel_Exception $e) {
            }
        }
        return view('agentDay.list',['list'=>$data,'input'=>$request->all(),'sum'=>$sumData]);
    }

    //根据当前代理查询是否存在下级
    public function isExist($id,$beginDate,$endDate)
    {
        $begin = strtotime($beginDate)+config('admin.beginTime');
        $end = strtotime($endDate)+config('admin.beginTime');
        $map = array();
        $sql = UserRebate::query();
        $sql->leftJoin('agent_users','agent_users.id','=','user_rebate.agent_id')
            ->select('user_rebate.agent_id','agent_users.id','agent_users.nickname','agent_users.username','agent_users.fee','agent_users.userType','agent_users.proportion','agent_users.pump',
                DB::raw('SUM(washMoney) as washMoney'),DB::raw('SUM(getMoney) as getMoney'),DB::raw('SUM(betMoney) as betMoney'),DB::raw('SUM(feeMoney) as feeMoney'));
        $agentIdArray = Agent::query()->select('id')->where('id','=',$id)->orWhere('parent_id','=',$id)->get()->toArray();
        $data = $sql->whereIn('user_rebate.agent_id',$agentIdArray)->where($map)->whereBetween('user_rebate.creatime',[$begin,$end])->groupBy('user_rebate.agent_id')->get()->toArray();
        $bool = $this->checkIsToDay($beginDate,$endDate);
        if ($bool)
        {
            $order = new Order();
            $order->setTable('order_'.date('Ymd',time()).' as order');
            $orderData = $this->getAncestorsByAgentId($id,$order);
            if(count($data)==0)
            {
                foreach ($orderData as $key=>$datum)
                {
                    if ($datum['count']==1)
                    {
                        $a = array();
                        $a['agent_id']=$datum['id'];
                        $a['nickname']=$datum['nickname'];
                        $a['username']=$datum['username'];
                        $a['fee']=$datum['fee'];
                        $a['userType']=$datum['userType'];
                        $a['proportion']=$datum['proportion'];
                        $a['pump']=$datum['pump'];
                        $a['washMoney'] = $datum['sumMoney'];
                        $a['getMoney']=$datum['getMoney'];
                        $a['betMoney']=$datum['betMoney'];
                        $a['feeMoney']=$datum['feeMoney'];
                        $data[] = $a;
                    }
                }
            }
            else
            {
                foreach ($orderData as $key=>$datum)
                {
                    $arr = $this->checkAgentIdIsExist($datum['id'],$data);
                    if ($arr['exist']==0)
                    {
                        $index = $arr['index'];
                        $data[$index]['washMoney']=$data[$index]['washMoney'] + $datum['sumMoney'];
                        $data[$index]['getMoney']=$data[$index]['getMoney'] + $datum['getMoney'];
                        $data[$index]['betMoney']=$data[$index]['betMoney'] + $datum['betMoney'];
                        $data[$index]['feeMoney']=$data[$index]['feeMoney']+$datum['feeMoney'];
                    }
                }
            }
        }
        return $data;
    }

    //根据当前代理查询是否存在下级会员
    public function isExistHqUser($id,$beginDate,$endDate)
    {
        $bool = $this->checkIsToDay($beginDate,$endDate);
        if ($bool)
        {
            $arr = array();
            $arr['u.agent_id']=$id;
            $order = new Order();
            $order->setTable('order_'.date('Ymd',time()).' as order');
            $sql = $order->leftJoin('user as u','u.user_id','=','order.user_id')
                ->leftJoin('user_account as ua','ua.user_id','=','u.user_id')
                ->select('u.agent_id','u.user_type','u.user_id','u.nickname','u.account','ua.balance',DB::raw('SUM(1) as betNum'),DB::raw('SUM(get_money) as getMoney'));
            $orderData = $sql->where($arr)->groupBy('order.user_id')->get()->toArray();
            foreach ($orderData as $key=>&$datum)
            {
                $datum['washMoney']=0;
                $datum['betMoney']=0;
                $datum['feeMoney']=0;
                $oData = $order->where('order.user_id','=',$datum['user_id'])->get()->toArray();
                foreach ($oData as $k=>$v)
                {
                    $userInfo = $v['user_id']?HqUser::find($v['user_id']):[];
                    $agentInfo = $this->getZsYjByAgentId($userInfo['agent_id']);
                    $betMoney = json_decode($v['bet_money'],true);
                    if ($v['game_type']==1 || $v['game_type']==2)
                    {
                        if ($v['status']==1 || $v['status']==4)
                        {
                            $datum['washMoney']=$datum['washMoney'] + array_sum($betMoney);
                            if ($v['game_type']==1)
                            {
                                $datum['betMoney']=$datum['betMoney'] + $this->getBaccaratBetMoney($v);
                                if ($agentInfo['userType']==1)
                                {
                                    $datum['feeMoney']=$datum['feeMoney'] + $this->bjlPump($v);
                                }
                                else
                                {
                                    $datum['feeMoney']=$datum['feeMoney'] + $this->xsBaccaratPump($v);
                                }
                            }
                            else
                            {
                                $datum['betMoney']=$datum['betMoney'] + $this->getDragonAndTigerBetMoney($v);
                                if ($agentInfo['userType']==2)
                                {
                                    $datum['feeMoney']=$datum['feeMoney'] + $this->xsDragonAndTigerPump($v);
                                }
                            }
                        }
                    }elseif ($v['game_type']==3)
                    {
                        if ($v['status']==1 || $v['status']==4)
                        {
                            $datum['washMoney']=$datum['washMoney']+array_sum($betMoney);
                            $datum['betMoney']=$datum['betMoney'] + array_sum($betMoney);
                            if ($agentInfo['userType']==2)
                            {
                                $datum['feeMoney']=$datum['feeMoney'] + $this->xsNiuNiuPump($v);
                            }
                        }
                    }elseif ($v['game_type']==4)
                    {
                        if ($v['status']==1)
                        {
                            $datum['washMoney']=$datum['washMoney']+array_sum($betMoney);
                            $datum['betMoney']=$datum['betMoney'] + array_sum($betMoney);
                            if ($agentInfo['userType']==2)
                            {
                                $datum['feeMoney']=$datum['feeMoney'] + $this->xsSanGongPump($v);
                            }
                        }
                    }elseif ($v['game_type']==5)
                    {
                        if ($v['status']==1)
                        {
                            $datum['washMoney']=$datum['washMoney']+array_sum($betMoney);
                            $datum['betMoney']=$datum['betMoney'] + array_sum($betMoney);
                            if ($agentInfo['userType']==2)
                            {
                                $datum['feeMoney']=$datum['feeMoney'] + $this->xsA89Pump($v);
                            }
                        }
                    }
                }
            }
        }else{
            $orderData=array();
        }

        $beginTime = strtotime($beginDate)+config('admin.beginTime');
        $endTime = strtotime('+1day',strtotime($endDate))+config('admin.beginTime');
        $map = array();
        $map['user_rebate.agent_id']=$id;
        $sql = UserRebate::query();
        $sql->leftJoin('user','user.user_id','=','user_rebate.user_id')
            ->leftJoin('user_account','user_account.user_id','=','user_rebate.user_id')
            ->select('user_rebate.id')->where($map)->whereBetween('user_rebate.creatime',[$beginTime,$endTime])->groupBy('user_rebate.agent_id','user_rebate.creatime');
        $dataSql = UserRebate::whereIn('user_rebate.id',$sql->get());
        $data = $dataSql->leftJoin('user','user.user_id','=','user_rebate.user_id')
            ->leftJoin('user_account','user_account.user_id','=','user_rebate.user_id')
            ->select('user_rebate.user_id','user.nickname','user.account','user_account.balance',DB::raw('SUM(betNum) as betNum'),
                DB::raw('SUM(washMoney) as washMoney'),DB::raw('SUM(betMoney) as betMoney'),DB::raw('SUM(getMoney) as getMoney'),DB::raw('SUM(feeMoney) as feeMoney'),'user_rebate.userType')->groupBy('user_rebate.user_id')->get()->toArray();
        if (count($data)==0)
        {
            foreach ($orderData as $key=>$v)
            {
                $info = array();
                $info['user_id']=$v['user_id'];
                $info['nickname']=$v['nickname'];
                $info['account']=$v['account'];
                $info['balance']=$v['balance'];
                $info['betNum']=$v['betNum'];
                $info['washMoney']=$v['washMoney'];
                $info['betMoney']=$v['betMoney'];
                $info['getMoney']=$v['getMoney'];
                $info['feeMoney']=0;
                $o = new Order();
                $o->setTable('order_'.date('Ymd',time()));
                $orData = $o->where('user_id','=',$v['user_id'])->get()->toArray();
                foreach ($orData as $ke=>$value)
                {
                    if ($v['user_type']==1)
                    {
                        if ($value['game_type']==1)
                        {
                            $info['feeMoney']=$info['feeMoney'] + $this->bjlPump($value);
                        }
                    }
                    else
                    {
                        if ($value['game_type']==1)
                        {
                            if ($value['status']==1 || $value['status']==4)
                            {
                                $info['feeMoney']= $info['feeMoney'] + $this->xsBaccaratPump($value);
                            }
                        }
                        elseif($value['game_type']==2)
                        {
                            if ($value['status']==1 || $value['status']==4)
                            {
                                $info['feeMoney'] = $info['feeMoney'] + $this->xsDragonAndTigerPump($value);
                            }
                        }
                        elseif($value['game_type']==3)
                        {
                            if ($value['status']==1 || $value['status']==4)
                            {
                                $info['feeMoney'] = $info['feeMoney'] + $this->xsNiuNiuPump($value);
                            }
                        }
                        elseif ($value['game_type']==4)
                        {
                            if ($value['status']==1 || $value['status']==4)
                            {
                                $info['feeMoney'] = $info['feeMoney'] + $this->xsSanGongPump($value);
                            }
                        }
                        elseif ($value['game_type']==5)
                        {
                            if ($value['status']==1 || $value['status']==4)
                            {
                                $info['feeMoney'] = $info['feeMoney'] + $this->xsA89Pump($value);
                            }
                        }
                    }
                }
                $info['userType']=$v['user_type'];
                $data[]=$info;
            }
        }
        else
        {
            foreach ($orderData as $key=>$datum)
            {
                $arr = $this->updateDate($datum['user_id'],$data);
                if ($arr['code']==1)
                {
                    $index= $arr['index'];
                    $data[$index]['feeMoney']=$data[$index]['feeMoney']+$datum['feeMoney'];
                    $data[$index]['betNum']=$data[$index]['betNum'] + $datum['betNum'];
                    $data[$index]['washMoney']=$data[$index]['washMoney']+$datum['washMoney'];
                    $data[$index]['betMoney']=$data[$index]['betMoney']+$datum['betMoney'];
                    $data[$index]['getMoney']=$data[$index]['getMoney']+$datum['getMoney'];
                }
                else
                {
                    $a = array();
                    $a['user_id']=$datum['user_id'];
                    $a['nickname']=$datum['nickname'];
                    $a['account']=$datum['account'];
                    $a['balance']=$datum['balance'];
                    $a['betNum']=$datum['betNum'];
                    $a['washMoney']=$datum['washMoney'];
                    $a['betMoney']=$datum['betMoney'];
                    $a['getMoney']=$datum['getMoney'];
                    $a['feeMoney']=$datum['feeMoney'];
                    $a['userType']=$datum['user_type'];
                    $data[]=$a;
                }
            }
        }
        return $data;
    }
    /**
     * 根据userId效验数组中是否存在该数据
     * @param $userId
     * @param $data
     * @return array
     */
    public function updateDate($userId,$data)
    {
        $arr = array();
        $arr['code']=0;
        if (count($data)!=0)
        {
            foreach ($data as $key=>$datum)
            {
                if ($datum['user_id']==$userId)
                {
                    $arr['code']=1;
                    $arr['index']=$key;
                    break;
                }
            }
        }
        return $arr;
    }
    //判断当前代理是否存在data数组中
    public function getAgentIdIsExistInDataByAgentIdAndData($agentId,$data)
    {
        $arr = array();
        $arr['exist']=0;
        foreach ($data as $key=>$datum)
        {
            if ($agentId==$datum['agent_id'])
            {
                $arr['exist']=1;
                $arr['index']=$key;
                break;
            }
        }
        return $arr;
    }

    public function getAgentDayByAgentId($id,$begin,$end,Request $request)
    {
        $request->offsetSet('type',2);
        $map = array();
        $sql = UserRebate::query();
        $sql->leftJoin('agent_users','agent_users.id','=','user_rebate.agent_id')
            ->select('user_rebate.agent_id','agent_users.id','agent_users.nickname','agent_users.username','agent_users.fee','agent_users.userType','agent_users.proportion','agent_users.pump',
                DB::raw('SUM(washMoney) as washMoney'),DB::raw('SUM(getMoney) as getMoney'),DB::raw('SUM(betMoney) as betMoney'),DB::raw('SUM(feeMoney) as feeMoney'));
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
            $begin = strtotime($begin)+config('admin.beginTime');
            $end = strtotime($end)+config('admin.beginTime');
            $request->offsetSet('begin',date('Y-m-d',$begin));
            $request->offsetSet('end',date('Y-m-d',$end));
        }
        if (true==$request->has('account'))
        {
            $agentIdArray = Agent::query()->select('id')->where('parent_id','=',$id)->where('username','=',$request->input('account'))->get()->toArray();
            $agents =Agent::query()->where('id','=',$id)->first();
            if ($agents['username']==$request->input('account'))
            {
                $d['id']=$agents['id'];
                $agentIdArray[] = $d;
            }
        }
        else
        {
            $request->offsetSet('account','');
            $agentIdArray = Agent::query()->select('id')->where('id','=',$id)->orWhere('parent_id','=',$id)->get()->toArray();
        }
        $data = $sql->whereIn('user_rebate.agent_id',$agentIdArray)->where($map)->whereBetween('user_rebate.creatime',[$begin,$end])->groupBy('user_rebate.agent_id')->get()->toArray();
        $bool = $this->checkIsToDay($request->input('begin'),$request->input('end'));
        if ($bool)
        {
            $order = new Order();
            $order->setTable('order_'.date('Ymd',time()).' as order');
            $orderData = $this->getAncestorsByAgentId($id,$order);
            if(count($data)==0)
            {
                foreach ($orderData as $key=>$datum)
                {
                    if (true==$request->has('account'))
                    {
                        if ($request->has('account')!=$datum['username'])
                        {
                            continue;
                        }
                    }
                    if ($datum['count']==1)
                    {
                        $a = array();
                        $a['agent_id']=$datum['id'];
                        $a['nickname']=$datum['nickname'];
                        $a['username']=$datum['username'];
                        $a['fee']=$datum['fee'];
                        $a['userType']=$datum['userType'];
                        $a['proportion']=$datum['proportion'];
                        $a['pump']=$datum['pump'];
                        $a['washMoney'] = $datum['sumMoney'];
                        $a['getMoney']=$datum['getMoney'];
                        $a['betMoney']=$datum['betMoney'];
                        $a['feeMoney']=$datum['feeMoney'];
                        $data[] = $a;
                    }
                }
            }
            else
            {
                foreach ($orderData as $key=>$datum)
                {
                    $arr = $this->checkAgentIdIsExist($datum['id'],$data);
                    if ($arr['exist']==0)
                    {
                        $index = $arr['index'];
                        $data[$index]['washMoney']=$data[$index]['washMoney'] + $datum['sumMoney'];
                        $data[$index]['getMoney']=$data[$index]['getMoney'] + $datum['getMoney'];
                        $data[$index]['betMoney']=$data[$index]['betMoney'] + $datum['betMoney'];
                        $data[$index]['feeMoney']=$data[$index]['feeMoney']+$datum['feeMoney'];
                    }
                }
            }
        }
        foreach ($data as $key=>&$datum)
        {
            if ($datum['userType']==1)
            {
                //洗码费
                $datum['code']=$datum['betMoney']*0.009;
                $data[$key]['fee']=json_decode($datum['fee'],true);
                $datum['zg']=-($datum['getMoney'] + $datum['code']) * ($datum['proportion']/100);
                //总收益
                if ($datum['zg']>0)
                {
                    $datum['sy'] = $datum['zg'] + $datum['feeMoney'] + $datum['code'];
                }
                else
                {
                    $datum['sy']=$datum['zg'] + $datum['feeMoney'] + $datum['code'];
                }
                //总收益
                if ($datum['getMoney']>0)
                {
                    $datum['gs']= -$datum['getMoney'] - $datum['sy'];
                }
                else
                {
                    $datum['gs']= abs($datum['getMoney']) - $datum['sy'];
                }
            }
            else
            {
                $data[$key]['sy']=$datum['feeMoney'];
                $data[$key]['gs']=-($datum['getMoney'] + $datum['sy']);
            }
            //打赏金额
            //获取当前代理下的会员
            $userData = HqUser::where('agent_id','=',$datum['agent_id'])->select('user_id')->get();
            $money = LiveReward::query()->whereIn('user_id',$userData)->whereBetween('creatime',[$begin,$end])->sum('money');
            $data[$key]['reward']=$money;
            $datum['is_exist']=count($this->isExist($datum['agent_id'],$request->input('begin'),$request->input('end')));
            $datum['is_exist_hqUser']=count($this->isExistHqUser($datum['agent_id'],$request->input('begin'),$request->input('end')));
        }
        if (true==$request->has('excel'))
        {
            $excel = array();
            foreach ($data as $key=>&$datum)
            {
                $a = array();
                $a['desk_type']='全部';
                $a['name']=$datum['nickname'];
                $a['username']=$datum['username'];
                $a['washMoney']=number_format($datum['washMoney']/100,2);
                if ($datum['getMoney']>0)
                {
                    $a['getMoney']=number_format(-$datum['getMoney']/100,2);
                }
                else{
                    $a['getMoney']=number_format($datum['getMoney']/100,2);
                }
                $a['betMoney']=number_format($datum['betMoney']/100,2);
                if ($datum['userType']==1){
                    $a['feeMoney']=number_format($datum['feeMoney']/100,2);
                }else{
                    $a['feeMoney']='-';
                }
                $a['reward']=number_format($datum['reward']/100,2);
                if ($datum['userType']==1)
                {
                    $a['fee']=$datum['fee']['baccarat'].'/'.$datum['fee']['dragonTiger'].'/'.$datum['fee']['niuniu'].'/'.$datum['fee']['sangong'].'/'.$datum['fee']['A89'];
                }else
                {
                    $a['fee']='-';
                }

                if ($datum['userType']==1)
                {
                    $a['code']=number_format($datum['code']/100,2);
                    $a['pump']='-';
                    $a['puSy']='-';
                    $a['proportion']=$datum['proportion'].'%';
                    $a['zg'] = number_format($datum['zg']/100,2);
                }else
                {
                    $a['code']='-';
                    $a['pump']=$datum['pump'].'%';
                    $a['puSy']=number_format($datum['feeMoney']/100,2);
                    $a['proportion']='-';
                    $a['zg']=0.00;
                }
                $a['sy'] = number_format($datum['sy']/100,2);
                $a['gs']=number_format($datum['gs']/100,2);
                $excel[] = $a;
            }
            $head = array('台类型','名称','账号','总押码','总赢','总洗码','总抽水','打赏金额','百/龙/牛/三/A','洗码费','抽水比例','抽水收益','占股','占股收益','总收益','公司收益');
            try {
                exportExcel($head, $excel, date('Y-m-d H:i:s',time()).'代理日结', '', true);
            } catch (\PHPExcel_Reader_Exception $e) {
            } catch (\PHPExcel_Exception $e) {
            }
        }
        return view('agentDay.list',['list'=>$data,'input'=>$request->all(),'id'=>$id]);
    }

    public function getAncestorsByAgentId($id,$order)
    {
        $idArr = Agent::query()->select('id','username','nickname','fee','userType','pump','proportion')->whereRaw(' id=? or parent_id=?',[$id,$id])->get()->toArray();
        foreach ($idArr as $key=>&$value)
        {
            $value['count']=0;
            //总押码
            $value['sumMoney']=0;
            //总赢
            $value['getMoney']=0;
            //总洗码
            $value['betMoney']=0;
            $value['feeMoney']=0;
            $idArray = Agent::query()->select('id')->whereRaw('FIND_IN_SET(?,ancestors)',[$value['id']])->get()->toArray();
            $data = $order->leftJoin('user as u','u.user_id','=','order.user_id')
                ->select('order.user_id','order.record_sn','order.bet_money','order.get_money','order.game_type','order.status')
                ->where('u.agent_id','=',$value['id'])->orWhereIn('u.agent_id',$idArray)->get()->toArray();
            foreach ($data as  $k=>&$datum)
            {
                $userInfo = $datum['user_id']?HqUser::find($datum['user_id']):[];
                $agentInfo = $this->getZsYjByAgentId($userInfo['agent_id']);
                $value['count']=1;
                $value['getMoney'] = $value['getMoney'] + $datum['get_money'];
                $betMoney = json_decode($datum['bet_money'],true);
                if ($datum['game_type']==1 || $datum['game_type']==2)
                {
                    if ($datum['status']==1 || $datum['status']==4)
                    {
                        if ($datum['game_type']==1){
                            $value['sumMoney'] = $value['sumMoney'] + array_sum($betMoney);
                            $value['betMoney'] = $value['betMoney'] + $this->getBaccaratBetMoney($datum);
                            if ($agentInfo['userType']==1)
                            {
                                $value['feeMoney'] = $value['feeMoney']+$this->bjlPump($datum);
                            }
                            else
                            {
                                $value['feeMoney'] = $value['feeMoney'] + $this->xsBaccaratPump($datum);
                            }
                        }
                        else
                        {
                            $value['sumMoney'] = $value['sumMoney'] + array_sum($betMoney);
                            $value['betMoney'] = $value['betMoney'] + $this->getDragonAndTigerBetMoney($datum);
                            if ($agentInfo['userType']==1)
                            {
                                $value['feeMoney'] = $value['feeMoney'] + 0;
                            }
                            else
                            {
                                $value['feeMoney'] = $value['feeMoney'] + $this->xsDragonAndTigerPump($datum);
                            }
                        }
                    }
                }elseif ($datum['game_type']==3)
                {
                    if ($datum['status']==1 || $datum['status']==4)
                    {
                        $value['sumMoney'] = $value['sumMoney'] + array_sum($betMoney);
                        $value['betMoney'] = $value['betMoney'] + array_sum($betMoney);
                        if ($agentInfo['userType']==1)
                        {
                            $value['feeMoney'] = $value['feeMoney'] + 0;
                        }
                        else
                        {
                            $value['feeMoney'] = $value['feeMoney'] + $this->xsNiuNiuPump($datum);
                        }
                    }
                }elseif ($datum['game_type']==4)
                {
                    if ($datum['status']==1 || $datum['status']==4)
                    {
                        $value['sumMoney'] = $value['sumMoney'] + array_sum($betMoney);
                        $value['betMoney'] = $value['betMoney'] + array_sum($betMoney);
                        if ($agentInfo['userType']==1)
                        {
                            $value['feeMoney']=$value['feeMoney'] + 0;
                        }
                        else
                        {
                            $value['feeMoney']=$value['feeMoney'] + $this->xsSanGongPump($datum);
                        }
                    }
                }elseif ($datum['game_type']==5)
                {
                    if ($datum['status']==1 || $datum['status']==4)
                    {
                        $value['sumMoney'] = $value['sumMoney'] + array_sum($betMoney);
                        $value['betMoney'] = $value['betMoney'] + array_sum($betMoney);
                        if ($agentInfo['userType']==1)
                        {
                            $value['feeMoney'] = $value['feeMoney'] +0;
                        }
                        else
                        {
                            $value['feeMoney'] = $value['feeMoney'] +$this->xsA89Pump($datum);
                        }
                    }
                }
            }
        }
        return $idArr;
    }

    /**
     * 获取百家乐下注金额  开合不算
     * @param $order
     * @return float|int|mixed
     */
    public function getBaccaratBetMoney($order)
    {
        $money = 0;
        $tableName = $this->getGameRecordTableNameByRecordSn($order['record_sn']);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $recordInfo = $game->where('record_sn','=',$order['record_sn'])->first();
        $betMoney = json_decode($order['bet_money'],true);
        $winner = json_decode($recordInfo['winner'],true);
        if ($winner['game']==1)
        {
            if ($betMoney['tie']>0)
            {
                $money = $betMoney['tie'];
            }
            if ($betMoney['bankerPair']>0)
            {
                $money += $betMoney['bankerPair'];
            }
            if ($betMoney['playerPair']>0)
            {
                $money += $betMoney['playerPair'];
            }
        }
        else
        {
            $money = array_sum($betMoney);
        }
        return $money;
    }

    /**
     * 获取龙虎下注金额 游戏结果开合不算
     * @param $order
     * @return float|int|mixed
     */
    public function getDragonAndTigerBetMoney($order)
    {
        $money = 0;
        $tableName = $this->getGameRecordTableNameByRecordSn($order['record_sn']);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $recordInfo = $game->where('record_sn','=',$order['record_sn'])->first();
        $betMoney = json_decode($order['bet_money'],true);
        if ($recordInfo['winner']==1)
        {
            if ($betMoney['tie']>0)
            {
                $money = $betMoney['tie'];
            }
        }
        else
        {
            $money = array_sum($betMoney);
        }
        return $money;
    }

    /**
     * 线上百家乐抽水
     * @param $order
     * @return float|int
     */
    public function xsBaccaratPump($order)
    {
        $money = 0;
        $betMoney = json_decode($order['bet_money'],true);
        $userInfo = $this->getUserInfoByUserId($order['user_id']);
        $agentInfo = $this->getZsYjByAgentId($userInfo['agent_id']);
        $recordSn = $order['record_sn'];
        $tableName = $this->getGameRecordTableNameByRecordSn($recordSn);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $info = $game->where('record_sn','=',$recordSn)->first();
        $winner = json_decode($info['winner'],true);
        if ($winner['game']==4)
        {
            if ($betMoney['player']>0)
            {
                $money =  (1 - $userInfo['bjlbets_fee']['player']/100) * $betMoney['player'] * $agentInfo['pump']/100;
            }
        }
        elseif ($winner['game']==7)
        {
            if ($betMoney['banker']>0)
            {
                $money = (1 - $userInfo['bjlbets_fee']['banker']/100) * $betMoney['banker'] * $agentInfo['pump']/100;
            }
        }
        return $money;
    }

    /**
     * 线上龙虎抽水
     * @param $order
     * @return float|int
     */
    public function xsDragonAndTigerPump($order)
    {
        $money = 0;
        $betMoney = json_decode($order['bet_money'],true);
        $userInfo = $this->getUserInfoByUserId($order['user_id']);
        $agentInfo = $this->getZsYjByAgentId($userInfo['agent_id']);
        $recordSn = $order['record_sn'];
        $tableName = $this->getGameRecordTableNameByRecordSn($recordSn);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $info = $game->where('record_sn','=',$recordSn)->first();
        $winner = $info['winner'];
        if ($winner==4)
        {
            if ($betMoney['tiger']>0)
            {
                $money = (1 - $userInfo['lhbets_fee']['tiger']/100) * $betMoney['tiger'] * $agentInfo['pump']/100;
            }
        }
        elseif ($winner==7)
        {
            if ($betMoney['dragon']>0)
            {
                $money = (1 - $userInfo['lhbets_fee']['dragon']/100) * $betMoney['dragon'] * $agentInfo['pump']/100;
            }
        }
        return $money;
    }

    /**
     * 线上牛牛抽水
     * @param $order
     * @return float|int
     */
    public function xsNiuNiuPump($order)
    {
        $money = 0;
        $betMoney = json_decode($order['bet_money'],true);
        $userInfo = $this->getUserInfoByUserId($order['user_id']);
        $agentInfo = $this->getZsYjByAgentId($userInfo['agent_id']);
        $recordSn = $order['record_sn'];
        $tableName = $this->getGameRecordTableNameByRecordSn($recordSn);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $info = $game->where('record_sn','=',$recordSn)->first();
        $winner = json_decode($info['winner'],true);
        if ($winner['x1result']=="win")
        {
            $x1Num = $this->nConvertNumbers($winner['x1num']);
            if (!empty($betMoney['x1_equal']))
            {
                $money = $money + (1 - $userInfo['nnbets_fee']['Equal']/100) * $betMoney['x1_equal'] * $agentInfo['pump']/100;
            }
            if (!empty($betMoney['x1_double']))
            {
                if ($x1Num > 9)
                {
                    $money = $money + (1 - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x1_double'] * 3 * $agentInfo['pump']/100;
                }elseif ($x1Num>6 && $x1Num<10)
                {
                    $money = $money + (1 - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x1_double'] * 2 * $agentInfo['pump']/100;
                }else{
                    $money = $money + (1 - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x1_double'] * $agentInfo['pump']/100;
                }
            }
            if (!empty($betMoney['x1_Super_Double']))
            {
                if ($x1Num>9)
                {
                    $money = $money + (1 - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x1_Super_Double'] *10 * $agentInfo['pump']/100;
                }elseif ($x1Num>0 && $x1Num<10)
                {
                    $money = $money + (1 - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x1_Super_Double'] * $x1Num * $agentInfo['pump']/100;
                }else
                {
                    $money = $money + (1 - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x1_Super_Double'] * $agentInfo['pump']/100;
                }
            }
        }
        if ($winner['x2result']=="win")
        {
            $x2Num = $this->nConvertNumbers($winner['x2num']);
            if (!empty($betMoney['x2_equal']))
            {
                $money = $money + (1 - $userInfo['nnbets_fee']['Equal']/100) * $betMoney['x2_equal'] * $agentInfo['pump']/100;
            }
            if (!empty($betMoney['x2_double']))
            {
                if ($x2Num > 9)
                {
                    $money = $money + (1 - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x2_double'] * 3 * $agentInfo['pump']/100;
                }elseif ($x2Num>6 && $x2Num<10)
                {
                    $money = $money + (1 - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x2_double'] * 2 * $agentInfo['pump']/100;
                }else{
                    $money = $money + (1 - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x2_double']* $agentInfo['pump']/100;
                }
            }
            if (!empty($betMoney['x2_Super_Double']))
            {
                if ($x2Num>9)
                {
                    $money = $money + (1 - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x2_Super_Double'] *10 * $agentInfo['pump']/100;
                }elseif ($x2Num>0 && $x2Num<10)
                {
                    $money = $money + (1 - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x2_Super_Double'] * $x2Num * $agentInfo['pump']/100;
                }else
                {
                    $money = $money + (1 - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x2_Super_Double']* $agentInfo['pump']/100;
                }
            }
        }
        if ($winner['x3result']=="win")
        {
            $x3Num = $this->nConvertNumbers($winner['x3num']);
            if (!empty($betMoney['x3_equal']))
            {
                $money = $money + (1 - $userInfo['nnbets_fee']['Equal']/100) * $betMoney['x3_equal'] * $agentInfo['pump']/100;
            }
            if (!empty($betMoney['x3_double']))
            {
                if ($x3Num > 9)
                {
                    $money = $money + (1 - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x3_double'] * 3 * $agentInfo['pump']/100;
                }elseif ($x3Num>6 && $x3Num<10)
                {
                    $money = $money + (1 - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x3_double'] * 2 * $agentInfo['pump']/100;
                }else{
                    $money = $money + (1 - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x3_double']* $agentInfo['pump']/100;
                }
            }
            if (!empty($betMoney['x3_Super_Double']))
            {
                if ($x3Num>9)
                {
                    $money = $money + (1 - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x3_Super_Double'] *10 * $agentInfo['pump']/100;
                }elseif ($x3Num>0 && $x3Num<10)
                {
                    $money = $money + (1 - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x3_Super_Double'] * $x3Num * $agentInfo['pump']/100;
                }else
                {
                    $money = $money + (1 - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x3_Super_Double']* $agentInfo['pump']/100;
                }
            }
        }
        return $money;
    }

    /**
     * 线上三公抽水
     * @param $order
     * @return float|int
     */
    public function xsSanGongPump($order)
    {
        $money = 0;
        $betMoney = json_decode($order['bet_money'],true);
        $userInfo = $this->getUserInfoByUserId($order['user_id']);
        $agentInfo = $this->getZsYjByAgentId($userInfo['agent_id']);
        $recordSn = $order['record_sn'];
        $tableName = $this->getGameRecordTableNameByRecordSn($recordSn);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $info = $game->where('record_sn','=',$recordSn)->first();
        $winner = json_decode($info['winner'],true);
        if ($winner['x1result']=="win")
        {
            $x1Num = $this->sConvertNumbers($winner['x1num']);
            if (!empty($betMoney['x1_equal']))
            {
                $money = $money + (1 - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x1_equal'] * $agentInfo['pump']/100;
            }
            if (!empty($betMoney['x1_double']))
            {
                //
                if ($x1Num > 9)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x1_double'] * 3 * $agentInfo['pump']/100;
                }elseif ($x1Num>6 && $x1Num<10)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x1_double'] * 2 * $agentInfo['pump']/100;
                }else{
                    $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x1_double'] * $agentInfo['pump']/100;
                }
            }
            if (!empty($betMoney['x1_Super_Double']))
            {
                if ($x1Num>9)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x1_Super_Double'] *10 * $agentInfo['pump']/100;
                }elseif ($x1Num>0 && $x1Num<10)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x1_Super_Double'] * $x1Num  * $agentInfo['pump']/100;
                }else
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x1_Super_Double'] * $agentInfo['pump']/100;
                }
            }
        }
        if ($winner['x2result']=="win")
        {
            $x2Num = $this->sConvertNumbers($winner['x2num']);
            if (!empty($betMoney['x2_equal']))
            {
                $money = $money + (1 - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x2_equal']  * $agentInfo['pump']/100;
            }
            if (!empty($betMoney['x2_double']))
            {
                //
                if ($x2Num > 9)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x2_double'] * 3 * $agentInfo['pump']/100;
                }elseif ($x2Num>6 && $x2Num<10)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x2_double'] * 2 * $agentInfo['pump']/100;
                }else{
                    $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x2_double'] * $agentInfo['pump']/100;
                }
            }
            if (!empty($betMoney['x2_Super_Double']))
            {
                if ($x2Num>9)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x2_Super_Double'] *10  * $agentInfo['pump']/100;
                }elseif ($x2Num>0 && $x2Num<10)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x2_Super_Double'] * $x2Num * $agentInfo['pump']/100;
                }else
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x2_Super_Double'] * $agentInfo['pump']/100;
                }
            }
        }
        if ($winner['x3result']=="win")
        {
            $x3Num = $this->sConvertNumbers($winner['x3num']);
            if (!empty($betMoney['x3_equal']))
            {
                $money = $money + (1 - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x3_equal'] * $agentInfo['pump']/100;
            }
            if (!empty($betMoney['x3_double']))
            {
                if ($x3Num > 9)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x3_double'] * 3 * $agentInfo['pump']/100;
                }elseif ($x3Num>6 && $x3Num<10)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x3_double'] * 2 * $agentInfo['pump']/100;
                }else{
                    $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x3_double'] * $agentInfo['pump']/100;
                }
            }
            if (!empty($betMoney['x3_Super_Double']))
            {
                if ($x3Num>9)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x3_Super_Double'] *10 * $agentInfo['pump']/100;
                }elseif ($x3Num>0 && $x3Num<10)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x3_Super_Double'] * $x3Num * $agentInfo['pump']/100;
                }else
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x3_Super_Double'] * $agentInfo['pump']/100;
                }
            }
        }
        if ($winner['x4result']=="win")
        {
            $x4Num = $this->sConvertNumbers($winner['x4num']);
            if (!empty($betMoney['x4_equal']))
            {
                $money = $money + (1 - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x4_equal'] * $agentInfo['pump']/100;
            }
            if (!empty($betMoney['x4_double']))
            {
                //
                if ($x4Num > 9)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x4_double'] * 3 * $agentInfo['pump']/100;
                }elseif ($x4Num>6 && $x4Num<10)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x4_double'] * 2 * $agentInfo['pump']/100;
                }else{
                    $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x4_double'] * $agentInfo['pump']/100;
                }
            }
            if (!empty($betMoney['x4_Super_Double']))
            {
                if ($x4Num>9)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x4_Super_Double'] *10 * $agentInfo['pump']/100;
                }elseif ($x4Num>0 && $x4Num<10)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x4_Super_Double'] * $x4Num * $agentInfo['pump']/100;
                }else
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x4_Super_Double'] * $agentInfo['pump']/100;
                }
            }
        }
        if ($winner['x5result']=="win")
        {
            $x5Num = $this->sConvertNumbers($winner['x5num']);
            if (!empty($betMoney['x5_equal']))
            {
                $money = $money + (1 - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x5_equal'] * $agentInfo['pump']/100;
            }
            if (!empty($betMoney['x5_double']))
            {
                //
                if ($x5Num > 9)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x5_double'] * 3 * $agentInfo['pump']/100;
                }elseif ($x5Num>6 && $x5Num<10)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x5_double'] * 2 * $agentInfo['pump']/100;
                }else{
                    $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x5_double'] * $agentInfo['pump']/100;
                }
            }
            if (!empty($betMoney['x5_Super_Double']))
            {
                if ($x5Num>9)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x5_Super_Double'] *10 * $agentInfo['pump']/100;
                }elseif ($x5Num>0 && $x5Num<10)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x5_Super_Double'] * $x5Num * $agentInfo['pump']/100;
                }else
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x5_Super_Double'] * $agentInfo['pump']/100;
                }
            }
        }
        if ($winner['x6result']=="win")
        {
            $x6Num = $this->sConvertNumbers($winner['x6num']);
            if (!empty($betMoney['x6_equal']))
            {
                $money = $money + (1 - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x6_equal'] * $agentInfo['pump']/100;
            }
            if (!empty($betMoney['x6_double']))
            {
                //
                if ($x6Num > 9)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x6_double'] * 3 * $agentInfo['pump']/100;
                }elseif ($x6Num>6 && $x6Num<10)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x6_double'] * 2 * $agentInfo['pump']/100;
                }else{
                    $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x6_double'] * $agentInfo['pump']/100;
                }
            }
            if (!empty($betMoney['x6_Super_Double']))
            {
                if ($x6Num>9)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x6_Super_Double'] *10 * $agentInfo['pump']/100;
                }elseif ($x6Num>0 && $x6Num<10)
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x6_Super_Double'] * $x6Num * $agentInfo['pump']/100;
                }else
                {
                    $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x6_Super_Double'] * $agentInfo['pump']/100;
                }
            }
        }
        return $money;
    }

    /**
     * 线上a89抽水
     * @param $order
     * @return float|int
     */
    public function xsA89Pump($order)
    {
        $money = 0;
        $betMoney = json_decode($order['bet_money'],true);
        $userInfo = $this->getUserInfoByUserId($order['user_id']);
        $agentInfo = $this->getZsYjByAgentId($userInfo['agent_id']);
        $recordSn = $order['record_sn'];
        $tableName = $this->getGameRecordTableNameByRecordSn($recordSn);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $info = $game->where('record_sn','=',$recordSn)->first();
        $winner = json_decode($info['winner'],true);
        if ($winner['Fanresult']=="win")
        {
            $fanNum = $this->aConvertNumbers($winner['FanNum']);
            if (!empty($betMoney['FanMen_equal']))
            {
                $money = $money + (1 - $userInfo['a89bets_fee']['Equal']/100) * $betMoney['FanMen_equal'] * $agentInfo['pump']/100;
            }
            if (!empty($betMoney['FanMen_Super_Double']))
            {
                $money = $money + (1 - $userInfo['a89bets_fee']['SuperDouble']/100) * $betMoney['FanMen_Super_Double'] * $fanNum * $agentInfo['pump']/100;
            }
        }
        if ($winner['Shunresult']=="win")
        {
            $shunMen = $this->aConvertNumbers($winner['ShunNum']);
            if (!empty($betMoney['ShunMen_equal']))
            {
                $money = $money + (1 - $userInfo['a89bets_fee']['Equal']/100) * $betMoney['FanMen_equal']* $agentInfo['pump']/100;
            }
            if (!empty($betMoney['ShunMen_Super_Double']))
            {
                $money = $money + (1- $userInfo['a89bets_fee']['SuperDouble']/100) * $betMoney['ShunMen_Super_Double'] * $shunMen* $agentInfo['pump']/100;
            }
        }
        if ($winner['Tianresult']=="win")
        {
            $fanNum = $this->aConvertNumbers($winner['TianNum']);
            if (!empty($betMoney['TianMen_equal']))
            {
                $money = $money + (1 - $userInfo['a89bets_fee']['Equal']/100) * $betMoney['TianMen_equal']* $agentInfo['pump']/100;
            }
            if (!empty($betMoney['FanMen_Super_Double']))
            {
                $money = $money + (1 - $userInfo['a89bets_fee']['SuperDouble']/100) * $betMoney['FanMen_Super_Double'] * $fanNum* $agentInfo['pump']/100;
            }
        }
        return $money;
    }

    /**
     * 百家乐抽水
     * @param $order
     * @return float|int
     */
    public function bjlPump($order)
    {
        $money = 0;
        $betMoney = json_decode($order['bet_money'],true);
        $userInfo = $this->getUserInfoByUserId($order['user_id']);
        $agentInfo = $this->getZsYjByAgentId($userInfo['agent_id']);
        $recordSn = $order['record_sn'];
        $tableName = $this->getGameRecordTableNameByRecordSn($recordSn);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $info = $game->where('record_sn','=',$recordSn)->first();
        $winner = json_decode($info['winner'],true);
        if ($winner['game']==4)
        {
            if ($betMoney['player']>0)
            {
                $money =  ($agentInfo['bjlbets_fee']['player'] - $userInfo['bjlbets_fee']['player']/100) * $betMoney['player'];
            }
        }
        elseif ($winner['game']==7)
        {
            if ($betMoney['banker']>0)
            {
                $money = ($agentInfo['bjlbets_fee']['banker'] - $userInfo['bjlbets_fee']['banker']/100) * $betMoney['banker'];
            }
        }
        elseif ($winner['game']==1)
        {
            if ($betMoney['tie']>0)
            {
                $money =  ($agentInfo['bjlbets_fee']['tie'] - $userInfo['bjlbets_fee']['tie']/100) * $betMoney['tie'];
            }
        }
        if ($winner['bankerPair']==2)
        {
            if ($betMoney['bankerPair']>0)
            {
                $money = $money + ($agentInfo['bjlbets_fee']['bankerPair'] - $userInfo['bjlbets_fee']['bankerPair']/100) * $betMoney['bankerPair'];
            }
        }
        if ($winner['playerPair']==5)
        {
            if ($betMoney['playerPair']>0)
            {
                $money = $money + ($agentInfo['bjlbets_fee']['playerPair'] - $userInfo['bjlbets_fee']['playerPair']/100)*$betMoney['playerPair'];
            }
        }
        return $money;
    }

    /**
     * 下级百家乐抽水
     * @param $id
     * @param $order
     * @return float|int
     */
    public function xjBjlPump($id,$order)
    {
        $money = 0;
        $betMoney = json_decode($order['bet_money'],true);
        $userInfo = $this->getUserInfoByUserId($order['user_id']);
        $agentInfo = $this->getAgentInfoById($id);
        $recordSn = $order['record_sn'];
        $tableName = $this->getGameRecordTableNameByRecordSn($recordSn);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $info = $game->where('record_sn','=',$recordSn)->first();
        $winner = json_decode($info['winner'],true);
        if ($winner['game']==4)
        {
            if ($betMoney['player']>0)
            {
                $money =  ($agentInfo['bjlbets_fee']['player'] - $userInfo['bjlbets_fee']['player']/100) * $betMoney['player'];
            }
        }
        elseif ($winner['game']==7)
        {
            if ($betMoney['banker']>0)
            {
                $money = ($agentInfo['bjlbets_fee']['banker'] - $userInfo['bjlbets_fee']['banker']/100) * $betMoney['banker'];
            }
        }
        elseif ($winner['game']==1)
        {
            if ($betMoney['tie']>0)
            {
                $money =  ($agentInfo['bjlbets_fee']['tie'] - $userInfo['bjlbets_fee']['tie']/100) * $betMoney['tie'];
            }
        }
        if ($winner['bankerPair']==2)
        {
            if ($betMoney['bankerPair']>0)
            {
                $money = $money + ($agentInfo['bjlbets_fee']['bankerPair'] - $userInfo['bjlbets_fee']['bankerPair']/100) * $betMoney['bankerPair'];
            }
        }
        if ($winner['playerPair']==5)
        {
            if ($betMoney['playerPair']>0)
            {
                $money = $money + ($agentInfo['bjlbets_fee']['playerPair'] - $userInfo['bjlbets_fee']['playerPair']/100)*$betMoney['playerPair'];
            }
        }
        return $money;
    }

    /**
     * 龙虎抽水
     * @param $order
     * @return float|int
     */
    public function lhPump($order)
    {
        $money = 0;
        $betMoney = json_decode($order['bet_money'],true);
        $userInfo = $this->getUserInfoByUserId($order['user_id']);
        $agentInfo = $this->getZsYjByAgentId($userInfo['agent_id']);
        $recordSn = $order['record_sn'];
        $tableName = $this->getGameRecordTableNameByRecordSn($recordSn);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $info = $game->where('record_sn','=',$recordSn)->first();
        $winner = $info['winner'];
        if ($winner==1)
        {
            if ($betMoney['tie']>0)
            {
                $money = ($agentInfo['lhbets_fee']['tie'] - $userInfo['lhbets_fee']['tie']/100) * $betMoney['tie'];
            }
        }
        elseif ($winner==4)
        {
            if ($betMoney['tiger']>0)
            {
                $money = ($agentInfo['lhbets_fee']['tiger'] - $userInfo['lhbets_fee']['tiger']/100) * $betMoney['tiger'];
            }
        }
        elseif ($winner==7)
        {
            if ($betMoney['dragon']>0)
            {
                $money = ($agentInfo['lhbets_fee']['dragon'] - $userInfo['lhbets_fee']['dragon']/100) * $betMoney['dragon'];
            }
        }
        return $money;
    }

    /**
     * 下级龙虎抽水
     * @param $id
     * @param $order
     * @return float|int
     */
    public function xjLhPump($id,$order)
    {
        $money = 0;
        $betMoney = json_decode($order['bet_money'],true);
        $userInfo = $this->getUserInfoByUserId($order['user_id']);
        $agentInfo = $this->getAgentInfoById($id);
        $recordSn = $order['record_sn'];
        $tableName = $this->getGameRecordTableNameByRecordSn($recordSn);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $info = $game->where('record_sn','=',$recordSn)->first();
        $winner = $info['winner'];
        if ($winner==1)
        {
            if ($betMoney['tie']>0)
            {
                $money = ($agentInfo['lhbets_fee']['tie'] - $userInfo['lhbets_fee']['tie']/100) * $betMoney['tie'];
            }
        }
        elseif ($winner==4)
        {
            if ($betMoney['tiger']>0)
            {
                $money = ($agentInfo['lhbets_fee']['tiger'] - $userInfo['lhbets_fee']['tiger']/100) * $betMoney['tiger'];
            }
        }
        elseif ($winner==7)
        {
            if ($betMoney['dragon']>0)
            {
                $money = ($agentInfo['lhbets_fee']['dragon'] - $userInfo['lhbets_fee']['dragon']/100) * $betMoney['dragon'];
            }
        }
        return $money;
    }

    /**
     * 牛牛抽水
     * @param $order
     * @return float|int
     */
    public function nnPump($order)
    {
        $money = 0;
        $betMoney = json_decode($order['bet_money'],true);
        $userInfo = $this->getUserInfoByUserId($order['user_id']);
        $agentInfo = $this->getZsYjByAgentId($userInfo['agent_id']);
        $recordSn = $order['record_sn'];
        $tableName = $this->getGameRecordTableNameByRecordSn($recordSn);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $info = $game->where('record_sn','=',$recordSn)->first();
        $winner = json_decode($info['winner'],true);
        if ($winner['x1result']=="win")
        {
            $x1Num = $this->nConvertNumbers($winner['x1num']);
            if (!empty($betMoney['x1_equal']))
            {
                $money = $money + ($agentInfo['nnbets_fee']['Equal'] - $userInfo['nnbets_fee']['Equal']/100) * $betMoney['x1_equal'];
            }
            if (!empty($betMoney['x1_double']))
            {
                //
                if ($x1Num > 9)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x1_double'] * 3;
                }elseif ($x1Num>6 && $x1Num<10)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x1_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x1_double'];
                }
            }
            if (!empty($betMoney['x1_Super_Double']))
            {
                if ($x1Num>9)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Super_Double'] - $userInfo['nnbets_fee']['Super_Double']/100) * $betMoney['x1_Super_Double'] *10;
                }elseif ($x1Num>0 && $x1Num<10)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Super_Double'] - $userInfo['nnbets_fee']['Super_Double']/100) * $betMoney['x1_Super_Double'] * $x1Num;
                }else
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Super_Double'] - $userInfo['nnbets_fee']['Super_Double']/100) * $betMoney['x1_Super_Double'];
                }
            }
        }
        if ($winner['x2result']=="win")
        {
            $x2Num = $this->nConvertNumbers($winner['x2num']);
            if (!empty($betMoney['x2_equal']))
            {
                $money = $money + ($agentInfo['nnbets_fee']['Equal'] - $userInfo['nnbets_fee']['Equal']/100) * $betMoney['x2_equal'];
            }
            if (!empty($betMoney['x2_double']))
            {
                if ($x2Num > 9)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x2_double'] * 3;
                }elseif ($x2Num>6 && $x2Num<10)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x2_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x2_double'];
                }
            }
            if (!empty($betMoney['x2_Super_Double']))
            {
                if ($x2Num>9)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Super_Double'] - $userInfo['nnbets_fee']['Super_Double']/100) * $betMoney['x2_Super_Double'] *10;
                }elseif ($x2Num>0 && $x2Num<10)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Super_Double'] - $userInfo['nnbets_fee']['Super_Double']/100) * $betMoney['x2_Super_Double'] * $x2Num;
                }else
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Super_Double'] - $userInfo['nnbets_fee']['Super_Double']/100) * $betMoney['x2_Super_Double'];
                }
            }
        }
        if ($winner['x3result']=="win")
        {
            $x3Num = $this->nConvertNumbers($winner['x3num']);
            if (!empty($betMoney['x3_equal']))
            {
                $money = $money + ($agentInfo['nnbets_fee']['Equal'] - $userInfo['nnbets_fee']['Equal']/100) * $betMoney['x3_equal'];
            }
            if (!empty($betMoney['x3_double']))
            {
                if ($x3Num > 9)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x3_double'] * 3;
                }elseif ($x3Num>6 && $x3Num<10)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x3_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x3_double'];
                }
            }
            if (!empty($betMoney['x3_Super_Double']))
            {
                if ($x3Num>9)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Super_Double'] - $userInfo['nnbets_fee']['Super_Double']/100) * $betMoney['x3_Super_Double'] *10;
                }elseif ($x3Num>0 && $x3Num<10)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Super_Double'] - $userInfo['nnbets_fee']['Super_Double']/100) * $betMoney['x3_Super_Double'] * $x3Num;
                }else
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Super_Double'] - $userInfo['nnbets_fee']['Super_Double']/100) * $betMoney['x3_Super_Double'];
                }
            }
        }
        return $money;
    }

    /**
     * 下级牛牛抽水
     * @param $id
     * @param $order
     * @return float|int
     */
    public function xjNnPump($id,$order)
    {
        $money = 0;
        $betMoney = json_decode($order['bet_money'],true);
        $userInfo = $this->getUserInfoByUserId($order['user_id']);
        $agentInfo = $this->getAgentInfoById($id);
        $recordSn = $order['record_sn'];
        $tableName = $this->getGameRecordTableNameByRecordSn($recordSn);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $info = $game->where('record_sn','=',$recordSn)->first();
        $winner = json_decode($info['winner'],true);
        if ($winner['x1result']=="win")
        {
            $x1Num = $this->nConvertNumbers($winner['x1num']);
            if (!empty($betMoney['x1_equal']))
            {
                $money = $money + ($agentInfo['nnbets_fee']['Equal'] - $userInfo['nnbets_fee']['Equal']/100) * $betMoney['x1_equal'];
            }
            if (!empty($betMoney['x1_double']))
            {
                //
                if ($x1Num > 9)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x1_double'] * 3;
                }elseif ($x1Num>6 && $x1Num<10)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x1_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x1_double'];
                }
            }
            if (!empty($betMoney['x1_Super_Double']))
            {
                if ($x1Num>9)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Super_Double'] - $userInfo['nnbets_fee']['Super_Double']/100) * $betMoney['x1_Super_Double'] *10;
                }elseif ($x1Num>0 && $x1Num<10)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Super_Double'] - $userInfo['nnbets_fee']['Super_Double']/100) * $betMoney['x1_Super_Double'] * $x1Num;
                }else
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Super_Double'] - $userInfo['nnbets_fee']['Super_Double']/100) * $betMoney['x1_Super_Double'];
                }
            }
        }
        if ($winner['x2result']=="win")
        {
            $x2Num = $this->nConvertNumbers($winner['x2num']);
            if (!empty($betMoney['x2_equal']))
            {
                $money = $money + ($agentInfo['nnbets_fee']['Equal'] - $userInfo['nnbets_fee']['Equal']/100) * $betMoney['x2_equal'];
            }
            if (!empty($betMoney['x2_double']))
            {
                if ($x2Num > 9)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x2_double'] * 3;
                }elseif ($x2Num>6 && $x2Num<10)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x2_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x2_double'];
                }
            }
            if (!empty($betMoney['x2_Super_Double']))
            {
                if ($x2Num>9)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Super_Double'] - $userInfo['nnbets_fee']['Super_Double']/100) * $betMoney['x2_Super_Double'] *10;
                }elseif ($x2Num>0 && $x2Num<10)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Super_Double'] - $userInfo['nnbets_fee']['Super_Double']/100) * $betMoney['x2_Super_Double'] * $x2Num;
                }else
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Super_Double'] - $userInfo['nnbets_fee']['Super_Double']/100) * $betMoney['x2_Super_Double'];
                }
            }
        }
        if ($winner['x3result']=="win")
        {
            $x3Num = $this->nConvertNumbers($winner['x3num']);
            if (!empty($betMoney['x3_equal']))
            {
                $money = $money + ($agentInfo['nnbets_fee']['Equal'] - $userInfo['nnbets_fee']['Equal']/100) * $betMoney['x3_equal'];
            }
            if (!empty($betMoney['x3_double']))
            {
                if ($x3Num > 9)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x3_double'] * 3;
                }elseif ($x3Num>6 && $x3Num<10)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x3_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x3_double'];
                }
            }
            if (!empty($betMoney['x3_Super_Double']))
            {
                if ($x3Num>9)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Super_Double'] - $userInfo['nnbets_fee']['Super_Double']/100) * $betMoney['x3_Super_Double'] *10;
                }elseif ($x3Num>0 && $x3Num<10)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Super_Double'] - $userInfo['nnbets_fee']['Super_Double']/100) * $betMoney['x3_Super_Double'] * $x3Num;
                }else
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Super_Double'] - $userInfo['nnbets_fee']['Super_Double']/100) * $betMoney['x3_Super_Double'];
                }
            }
        }
        return $money;
    }
    /**
     * 三公抽水
     * @param $order
     * @return float|int
     */
    public function sgPump($order)
    {
        $money = 0;
        $betMoney = json_decode($order['bet_money'],true);
        $userInfo = $this->getUserInfoByUserId($order['user_id']);
        $agentInfo = $this->getZsYjByAgentId($userInfo['agent_id']);
        $recordSn = $order['record_sn'];
        $tableName = $this->getGameRecordTableNameByRecordSn($recordSn);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $info = $game->where('record_sn','=',$recordSn)->first();
        $winner = json_decode($info['winner'],true);
        if ($winner['x1result']=="win")
        {
            $x1Num = $this->sConvertNumbers($winner['x1num']);
            if (!empty($betMoney['x1_equal']))
            {
                $money = $money + ($agentInfo['sgbets_fee']['Equal'] - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x1_equal'];
            }
            if (!empty($betMoney['x1_double']))
            {
                //
                if ($x1Num > 9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x1_double'] * 3;
                }elseif ($x1Num>6 && $x1Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x1_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x1_double'];
                }
            }
            if (!empty($betMoney['x1_Super_Double']))
            {
                if ($x1Num>9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x1_Super_Double'] *10;
                }elseif ($x1Num>0 && $x1Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x1_Super_Double'] * $x1Num;
                }else
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x1_Super_Double'];
                }
            }
        }
        if ($winner['x2result']=="win")
        {
            $x2Num = $this->sConvertNumbers($winner['x2num']);
            if (!empty($betMoney['x2_equal']))
            {
                $money = $money + ($agentInfo['sgbets_fee']['Equal'] - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x2_equal'];
            }
            if (!empty($betMoney['x2_double']))
            {
                //
                if ($x2Num > 9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x2_double'] * 3;
                }elseif ($x2Num>6 && $x2Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x2_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x2_double'];
                }
            }
            if (!empty($betMoney['x2_Super_Double']))
            {
                if ($x2Num>9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x2_Super_Double'] *10;
                }elseif ($x2Num>0 && $x2Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x2_Super_Double'] * $x2Num;
                }else
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x2_Super_Double'];
                }
            }
        }
        if ($winner['x3result']=="win")
        {
            $x3Num = $this->sConvertNumbers($winner['x3num']);
            if (!empty($betMoney['x3_equal']))
            {
                $money = $money + ($agentInfo['sgbets_fee']['Equal'] - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x3_equal'];
            }
            if (!empty($betMoney['x3_double']))
            {
                if ($x3Num > 9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x3_double'] * 3;
                }elseif ($x3Num>6 && $x3Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x3_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x3_double'];
                }
            }
            if (!empty($betMoney['x3_Super_Double']))
            {
                if ($x3Num>9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x3_Super_Double'] *10;
                }elseif ($x3Num>0 && $x3Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x3_Super_Double'] * $x3Num;
                }else
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x3_Super_Double'];
                }
            }
        }
        if ($winner['x4result']=="win")
        {
            $x4Num = $this->sConvertNumbers($winner['x4num']);
            if (!empty($betMoney['x4_equal']))
            {
                $money = $money + ($agentInfo['sgbets_fee']['Equal'] - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x4_equal'];
            }
            if (!empty($betMoney['x4_double']))
            {
                //
                if ($x4Num > 9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x4_double'] * 3;
                }elseif ($x4Num>6 && $x4Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x4_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x4_double'];
                }
            }
            if (!empty($betMoney['x4_Super_Double']))
            {
                if ($x4Num>9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x4_Super_Double'] *10;
                }elseif ($x4Num>0 && $x4Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x4_Super_Double'] * $x4Num;
                }else
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x4_Super_Double'];
                }
            }
        }
        if ($winner['x5result']=="win")
        {
            $x5Num = $this->sConvertNumbers($winner['x5num']);
            if (!empty($betMoney['x5_equal']))
            {
                $money = $money + ($agentInfo['sgbets_fee']['Equal'] - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x5_equal'];
            }
            if (!empty($betMoney['x5_double']))
            {
                //
                if ($x5Num > 9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x5_double'] * 3;
                }elseif ($x5Num>6 && $x5Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x5_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x5_double'];
                }
            }
            if (!empty($betMoney['x5_Super_Double']))
            {
                if ($x5Num>9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x5_Super_Double'] *10;
                }elseif ($x5Num>0 && $x5Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x5_Super_Double'] * $x5Num;
                }else
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x5_Super_Double'];
                }
            }
        }
        if ($winner['x6result']=="win")
        {
            $x6Num = $this->sConvertNumbers($winner['x6num']);
            if (!empty($betMoney['x6_equal']))
            {
                $money = $money + ($agentInfo['sgbets_fee']['Equal'] - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x6_equal'];
            }
            if (!empty($betMoney['x6_double']))
            {
                //
                if ($x6Num > 9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x6_double'] * 3;
                }elseif ($x6Num>6 && $x6Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x6_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x6_double'];
                }
            }
            if (!empty($betMoney['x6_Super_Double']))
            {
                if ($x6Num>9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x6_Super_Double'] *10;
                }elseif ($x6Num>0 && $x6Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x6_Super_Double'] * $x6Num;
                }else
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x6_Super_Double'];
                }
            }
        }
        return $money;
    }

    /**
     *下级三公抽水
     * @param $id
     * @param $order
     * @return int
     */
    public function xjSgPump($id,$order)
    {
        $money = 0;
        $betMoney = json_decode($order['bet_money'],true);
        $userInfo = $this->getUserInfoByUserId($order['user_id']);
        $agentInfo = $this->getAgentInfoById($id);
        $recordSn = $order['record_sn'];
        $tableName = $this->getGameRecordTableNameByRecordSn($recordSn);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $info = $game->where('record_sn','=',$recordSn)->first();
        $winner = json_decode($info['winner'],true);
        if ($winner['x1result']=="win")
        {
            $x1Num = $this->sConvertNumbers($winner['x1num']);
            if (!empty($betMoney['x1_equal']))
            {
                $money = $money + ($agentInfo['sgbets_fee']['Equal'] - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x1_equal'];
            }
            if (!empty($betMoney['x1_double']))
            {
                //
                if ($x1Num > 9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x1_double'] * 3;
                }elseif ($x1Num>6 && $x1Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x1_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x1_double'];
                }
            }
            if (!empty($betMoney['x1_Super_Double']))
            {
                if ($x1Num>9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x1_Super_Double'] *10;
                }elseif ($x1Num>0 && $x1Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x1_Super_Double'] * $x1Num;
                }else
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x1_Super_Double'];
                }
            }
        }
        if ($winner['x2result']=="win")
        {
            $x2Num = $this->sConvertNumbers($winner['x2num']);
            if (!empty($betMoney['x2_equal']))
            {
                $money = $money + ($agentInfo['sgbets_fee']['Equal'] - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x2_equal'];
            }
            if (!empty($betMoney['x2_double']))
            {
                //
                if ($x2Num > 9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x2_double'] * 3;
                }elseif ($x2Num>6 && $x2Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x2_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x2_double'];
                }
            }
            if (!empty($betMoney['x2_Super_Double']))
            {
                if ($x2Num>9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x2_Super_Double'] *10;
                }elseif ($x2Num>0 && $x2Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x2_Super_Double'] * $x2Num;
                }else
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x2_Super_Double'];
                }
            }
        }
        if ($winner['x3result']=="win")
        {
            $x3Num = $this->sConvertNumbers($winner['x3num']);
            if (!empty($betMoney['x3_equal']))
            {
                $money = $money + ($agentInfo['sgbets_fee']['Equal'] - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x3_equal'];
            }
            if (!empty($betMoney['x3_double']))
            {
                if ($x3Num > 9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x3_double'] * 3;
                }elseif ($x3Num>6 && $x3Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x3_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x3_double'];
                }
            }
            if (!empty($betMoney['x3_Super_Double']))
            {
                if ($x3Num>9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x3_Super_Double'] *10;
                }elseif ($x3Num>0 && $x3Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x3_Super_Double'] * $x3Num;
                }else
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x3_Super_Double'];
                }
            }
        }
        if ($winner['x4result']=="win")
        {
            $x4Num = $this->sConvertNumbers($winner['x4num']);
            if (!empty($betMoney['x4_equal']))
            {
                $money = $money + ($agentInfo['sgbets_fee']['Equal'] - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x4_equal'];
            }
            if (!empty($betMoney['x4_double']))
            {
                //
                if ($x4Num > 9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x4_double'] * 3;
                }elseif ($x4Num>6 && $x4Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x4_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x4_double'];
                }
            }
            if (!empty($betMoney['x4_Super_Double']))
            {
                if ($x4Num>9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x4_Super_Double'] *10;
                }elseif ($x4Num>0 && $x4Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x4_Super_Double'] * $x4Num;
                }else
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x4_Super_Double'];
                }
            }
        }
        if ($winner['x5result']=="win")
        {
            $x5Num = $this->sConvertNumbers($winner['x5num']);
            if (!empty($betMoney['x5_equal']))
            {
                $money = $money + ($agentInfo['sgbets_fee']['Equal'] - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x5_equal'];
            }
            if (!empty($betMoney['x5_double']))
            {
                //
                if ($x5Num > 9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x5_double'] * 3;
                }elseif ($x5Num>6 && $x5Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x5_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x5_double'];
                }
            }
            if (!empty($betMoney['x5_Super_Double']))
            {
                if ($x5Num>9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x5_Super_Double'] *10;
                }elseif ($x5Num>0 && $x5Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x5_Super_Double'] * $x5Num;
                }else
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x5_Super_Double'];
                }
            }
        }
        if ($winner['x6result']=="win")
        {
            $x6Num = $this->sConvertNumbers($winner['x6num']);
            if (!empty($betMoney['x6_equal']))
            {
                $money = $money + ($agentInfo['sgbets_fee']['Equal'] - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x6_equal'];
            }
            if (!empty($betMoney['x6_double']))
            {
                //
                if ($x6Num > 9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x6_double'] * 3;
                }elseif ($x6Num>6 && $x6Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x6_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x6_double'];
                }
            }
            if (!empty($betMoney['x6_Super_Double']))
            {
                if ($x6Num>9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x6_Super_Double'] *10;
                }elseif ($x6Num>0 && $x6Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x6_Super_Double'] * $x6Num;
                }else
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Super_Double'] - $userInfo['sgbets_fee']['Super_Double']/100) * $betMoney['x6_Super_Double'];
                }
            }
        }
        return $money;
    }

    /**
     * a89抽水
     * @param $order
     * @return float|int
     */
    public function aPump($order)
    {
        $money = 0;
        $betMoney = json_decode($order['bet_money'],true);
        $userInfo = $this->getUserInfoByUserId($order['user_id']);
        $agentInfo = $this->getZsYjByAgentId($userInfo['agent_id']);
        $recordSn = $order['record_sn'];
        $tableName = $this->getGameRecordTableNameByRecordSn($recordSn);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $info = $game->where('record_sn','=',$recordSn)->first();
        $winner = json_decode($info['winner'],true);
        if ($winner['Fanresult']=="win")
        {
            $fanNum = $this->aConvertNumbers($winner['FanNum']);
            if (!empty($betMoney['FanMen_equal']))
            {
                $money = $money + ($agentInfo['a89bets_fee']['Equal'] - $userInfo['a89bets_fee']['Equal']/100) * $betMoney['FanMen_equal'];
            }
            if (!empty($betMoney['FanMen_Super_Double']))
            {
                $money = $money + ($agentInfo['a89bets_fee']['Super_Double'] - $userInfo['a89bets_fee']['Super_Double']/100) * $betMoney['FanMen_Super_Double'] * $fanNum;
            }
        }
        if ($winner['Shunresult']=="win")
        {
            $shunMen = $this->aConvertNumbers($winner['ShunNum']);
            if (!empty($betMoney['ShunMen_equal']))
            {
                $money = $money + ($agentInfo['a89bets_fee']['Equal'] - $userInfo['a89bets_fee']['Equal']/100) * $betMoney['FanMen_equal'];
            }
            if (!empty($betMoney['ShunMen_Super_Double']))
            {
                $money = $money + ($agentInfo['a89bets_fee']['Super_Double'] - $userInfo['a89bets_fee']['Super_Double']/100) * $betMoney['ShunMen_Super_Double'] * $shunMen;
            }
        }
        if ($winner['Tianresult']=="win")
        {
            $fanNum = $this->aConvertNumbers($winner['TianNum']);
            if (!empty($betMoney['TianMen_equal']))
            {
                $money = $money + ($agentInfo['a89bets_fee']['Equal'] - $userInfo['a89bets_fee']['Equal']/100) * $betMoney['TianMen_equal'];
            }
            if (!empty($betMoney['FanMen_Super_Double']))
            {
                $money = $money + ($agentInfo['a89bets_fee']['Super_Double'] - $userInfo['a89bets_fee']['Super_Double']/100) * $betMoney['FanMen_Super_Double'] * $fanNum;
            }
        }
        return $money;
    }

    /**
     * 下级a89抽水
     * @param $id
     * @param $order
     * @return float|int
     */
    public function xjAPump($id,$order)
    {
        $money = 0;
        $betMoney = json_decode($order['bet_money'],true);
        $userInfo = $this->getUserInfoByUserId($order['user_id']);
        $agentInfo = $this->getAgentInfoById($id);
        $recordSn = $order['record_sn'];
        $tableName = $this->getGameRecordTableNameByRecordSn($recordSn);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $info = $game->where('record_sn','=',$recordSn)->first();
        $winner = json_decode($info['winner'],true);
        if ($winner['Fanresult']=="win")
        {
            $fanNum = $this->aConvertNumbers($winner['FanNum']);
            if (!empty($betMoney['FanMen_equal']))
            {
                $money = $money + ($agentInfo['a89bets_fee']['Equal'] - $userInfo['a89bets_fee']['Equal']/100) * $betMoney['FanMen_equal'];
            }
            if (!empty($betMoney['FanMen_Super_Double']))
            {
                $money = $money + ($agentInfo['a89bets_fee']['Super_Double'] - $userInfo['a89bets_fee']['Super_Double']/100) * $betMoney['FanMen_Super_Double'] * $fanNum;
            }
        }
        if ($winner['Shunresult']=="win")
        {
            $shunMen = $this->aConvertNumbers($winner['ShunNum']);
            if (!empty($betMoney['ShunMen_equal']))
            {
                $money = $money + ($agentInfo['a89bets_fee']['Equal'] - $userInfo['a89bets_fee']['Equal']/100) * $betMoney['FanMen_equal'];
            }
            if (!empty($betMoney['ShunMen_Super_Double']))
            {
                $money = $money + ($agentInfo['a89bets_fee']['Super_Double'] - $userInfo['a89bets_fee']['Super_Double']/100) * $betMoney['ShunMen_Super_Double'] * $shunMen;
            }
        }
        if ($winner['Tianresult']=="win")
        {
            $fanNum = $this->aConvertNumbers($winner['TianNum']);
            if (!empty($betMoney['TianMen_equal']))
            {
                $money = $money + ($agentInfo['a89bets_fee']['Equal'] - $userInfo['a89bets_fee']['Equal']/100) * $betMoney['TianMen_equal'];
            }
            if (!empty($betMoney['FanMen_Super_Double']))
            {
                $money = $money + ($agentInfo['a89bets_fee']['Super_Double'] - $userInfo['a89bets_fee']['Super_Double']/100) * $betMoney['FanMen_Super_Double'] * $fanNum;
            }
        }
        return $money;
    }

    /**
     * 获取直属一级
     * @param $agentId
     * @return Agent|Agent[]|array|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function getZsYjByAgentId($agentId)
    {
        $agent = $this->getAgentInfoById($agentId);
        $ancestors = explode(',',$agent['ancestors']);
        $ancestors[] = $agent['id'];
        return $this->getAgentInfoById($ancestors[1]);
    }

    /**
     * 根据userId获取用户
     * @param $userId
     * @return HqUser|HqUser[]|array|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function getUserInfoByUserId($userId)
    {
        $user = $userId?HqUser::find($userId):[];
        $user['bjlbets_fee']=json_decode($user['bjlbets_fee'],true);
        $user['lhbets_fee']=json_decode($user['lhbets_fee'],true);
        $user['nnbets_fee']=json_decode($user['nnbets_fee'],true);
        $user['sgbets_fee']=json_decode($user['sgbets_fee'],true);
        $user['a89bets_fee']=json_decode($user['a89bets_fee'],true);
        return $user;
    }

    /**
     * 根据agentId获取代理
     * @param $agentId
     * @return Agent|Agent[]|array|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function getAgentInfoById($agentId)
    {
        $agent = $agentId?Agent::find($agentId):[];
        $agent['bjlbets_fee']=json_decode($agent['bjlbets_fee'],true);
        $agent['lhbets_fee']=json_decode($agent['lhbets_fee'],true);
        $agent['nnbets_fee']=json_decode($agent['nnbets_fee'],true);
        $agent['sgbets_fee']=json_decode($agent['sgbets_fee'],true);
        $agent['a89bets_fee']=json_decode($agent['a89bets_fee'],true);
        return $agent;
    }

    public function checkAgentIdIsExist($agentId,$data)
    {
        $arr = array();
        $arr['exist']=1;
        foreach ($data as $key=>$datum)
        {
            if ($agentId==$datum['agent_id']){
                $arr['exist']=0;
                $arr['index']=$key;
            }
        }
        return $arr;
    }

    public function whetherAffiliatedAgent($agentId,$ancestors)
    {
        foreach ($ancestors as $key=>$value)
        {
            if ($agentId==$value){
                return true;
                break;
            }
        }
        return false;
    }

    /**
     * 获取今天的打赏金额
     * @param $begin
     * @param $end
     * @return int|mixed
     */
    public function getToDayReward($begin,$end)
    {
        return LiveReward::query()->whereBetween('creatime',[$begin,$end])->sum('money');
    }

    /**
     * 获取总赢
     * @param $data
     * @return int
     */
    public function getToDayWinMoney($data)
    {
        $money = 0;
        foreach ($data as $key=>$datum)
        {
            $money = $money + $datum['get_money'];
        }
        return $money;
    }

    /**
     * 获取今日的总下注金额
     * @param $data
     * @return float|int
     */
    public function getToDaySumBetMoney($data)
    {
        $money = 0;
        foreach ($data as $key=>$datum)
        {
            $betMoney = json_decode($datum['bet_money'],true);
            if ($datum['game_type']==1)
            {
                if ($datum['status']==1 || $datum['status']==4)
                {
                    $money = $money + $this->getBaccaratBetMoney($datum);
                }
                //$money = $money + array_sum($betMoney);
            }
            else if ($datum['game_type']==2)
            {
                if ($datum['status']==1 || $datum['status']==4)
                {
                    $money = $money + $this->getDragonAndTigerBetMoney($datum);
                }
            }
            else if ($datum['game_type']==3)
            {
                if ($datum['status']==1 || $datum['status']==4)
                {
                    $money = $money + array_sum($betMoney);
                }
            }
            else if($datum['game_type']==4)
            {
                if ($datum['status']==1 || $datum['status']==4)
                {
                    $money = $money + array_sum($betMoney);
                }
            }
            else if($datum['game_type']==5)
            {
                if ($datum['status']==1 || $datum['status']==4)
                {
                    $money = $money + array_sum($betMoney);
                }
            }
        }
        return $money;
    }

    /**
     * 获取总洗码
     * @param $data
     * @return float|int
     */
    public function getToDayCode($data)
    {
        $money =0;
        foreach ($data as $key=>$datum)
        {
            if ($datum['status']!=1)
            {
                continue;
            }
            $betMoney = json_decode($datum['bet_money'],true);
            if ($datum['game_type']==1 || $datum['game_type']==2)
            {
                $money = $money + array_sum($betMoney);
            }
            else if ($datum['game_type']==3)
            {
                $money = $money + array_sum($betMoney);
            }
            else if($datum['game_type']==4)
            {
                $money = $money + array_sum($betMoney);
            }
            else if($datum['game_type']==5)
            {
                $money = $money + array_sum($betMoney);
            }
        }
        return $money;
    }

    /**
     * 计算a89下注金额
     * @param $data
     * @return float|int
     */
    public function getA89BetMoney($data)
    {
        $money = 0;
        if (!empty($data['ShunMen_equal']))
        {
            $money = $money + $data['ShunMen_equal'];
        }
        if (!empty($data['ShunMen_Super_Double']))
        {
            $money = $money + $data['ShunMen_Super_Double'] * 10;
        }
        if (!empty($data['TianMen_equal']))
        {
            $money = $money + $data['TianMen_equal'];
        }
        if (!empty($data['TianMen_Super_Double']))
        {
            $money = $money + $data['TianMen_Super_Double'] * 10;
        }
        if (!empty($data['FanMen_equal']))
        {
            $money = $money + $data['FanMen_equal'];
        }
        if (!empty($data['FanMen_Super_Double']))
        {
            $money = $money + $data['FanMen_Super_Double'] * 10;
        }
        return $money;
    }

    /**
     * 计算三公下注金额
     * @param $data
     * @return float|int
     */
    public function getSanGongBetMoney($data)
    {
        $money = 0;
        if (!empty($data['x1_equal']))
        {
            $money = $money + $data['x1_equal'];
        }
        if (!empty($data['x1_double']))
        {
            $money = $money + $data['x1_double'] * 3;
        }
        if (!empty($data['x1_Super_Double']))
        {
            $money = $money + $data['x1_Super_Double'] * 10;
        }
        if (!empty($data['x2_equal']))
        {
            $money = $money + $data['x2_equal'];
        }
        if (!empty($data['x2_double']))
        {
            $money = $money + $data['x2_double'] * 3;
        }
        if (!empty($data['x2_Super_Double']))
        {
            $money = $money + $data['x2_Super_Double'] * 10;
        }
        if (!empty($data['x3_equal']))
        {
            $money = $money + $data['x3_equal'];
        }
        if (!empty($data['x3_double']))
        {
            $money = $money + $data['x3_double'] * 3;
        }
        if (!empty($data['x3_Super_Double']))
        {
            $money = $money + $data['x3_Super_Double'] * 10;
        }
        if (!empty($data['x4_equal']))
        {
            $money = $money + $data['x4_equal'];
        }
        if (!empty($data['x4_double']))
        {
            $money = $money + $data['x4_double'] * 3;
        }
        if (!empty($data['x4_Super_Double']))
        {
            $money = $money + $data['x4_Super_Double'] * 10;
        }
        if (!empty($data['x5_equal']))
        {
            $money = $money + $data['x5_equal'];
        }
        if (!empty($data['x5_double']))
        {
            $money = $money + $data['x5_double'] *3;
        }
        if (!empty($data['x5_Super_Double']))
        {
            $money = $money + $data['x5_Super_Double'] * 10;
        }
        if (!empty($data['x6_equal']))
        {
            $money = $money + $data['x6_equal'];
        }
        if (!empty($data['x6_double']))
        {
            $money = $money + $data['x6_double'] *3;
        }
        if (!empty($data['x6_Super_Double']))
        {
            $money = $money + $data['x6_Super_Double'] * 10;
        }
        return $money;
    }
    /**
     * 计算牛牛下注金额
     * @param $data
     * @return float|int
     */
    public function getNiuNiuBetMoney($data)
    {
        $money = 0;
        if (!empty($data['x1_equal']))
        {
            $money = $money + $data['x1_equal'];
        }
        if (!empty($data['x1_double']))
        {
            $money = $money + $data['x1_double'] * 3;
        }
        if (!empty($data['x1_Super_Double']))
        {
            $money = $money + $data['x1_Super_Double'] * 10;
        }
        if (!empty($data['x2_equal']))
        {
            $money = $money + $data['x2_equal'];
        }
        if (!empty($data['x2_double']))
        {
            $money = $money + $data['x2_double'] * 3;
        }
        if (!empty($data['x2_Super_Double']))
        {
            $money = $money + $data['x2_Super_Double'] * 10;
        }
        if (!empty($data['x3_equal']))
        {
            $money = $money + $data['x3_equal'];
        }
        if (!empty($data['x3_double']))
        {
            $money = $money + $data['x3_double'] * 3;
        }
        if (!empty($data['x3_Super_Double']))
        {
            $money = $money + $data['x3_Super_Double'] * 10;
        }
        return $money;
    }
    /**
     * 效验是否查询存在今日
     * @param $start
     * @param $end
     * @return bool
     */
    public function checkIsToDay($start,$end)
    {
        $bool = false;
        $data = $this->getDateTimePeriodByBeginAndEnd($start,$end);
        foreach ($data as $key)
        {
            /*if ($key==date('Ymd',time())){
                $bool = true;
                break;
            }*/
            if ($key==date('Ymd',time())){
                $bool = true;
                break;
            }
        }
        return $bool;
    }


    /**
     * 下级代理日结
     * @param $id
     * @param $begin
     * @param $end
     * @param Request $request
     * @return Factory|Application|View
     */
    public function getIndexByParentId($id,$begin,$end,Request $request)
    {
        $request->offsetSet('begin',$begin);
        $request->offsetSet('end',$end);
        $map = array();
        $map['parent_id']=(int)$id;
        if (true == $request->has('account')){
            $map['username']=HttpFilter($request->input('account'));
        }
        $dateArr = $this->getDateTimePeriodByBeginAndEnd($begin,$end);
        $dataSql = '';
        for ($i=0;$i<count($dateArr);$i++)
        {
            if (Schema::hasTable('order_'.$dateArr[$i])){
                if ($dataSql==""){
                    $dataSql = "select * from hq_order_".$dateArr[$i];
                }else{
                    $dataSql = $dataSql.' union all select * from hq_order_'.$dateArr[$i];
                }
            }
        }
        $data = Agent::where($map)->paginate(10)->appends($request->all());
        foreach ($data as $key=>$value){
            $sql = 'select t1.* from (select * from('.$dataSql.') s where s.creatime between '.strtotime($begin).' and '.strtotime($end).') t1 
            left join hq_user u on t1.user_id = u.user_id
            inner join (select id from hq_agent_users where del_flag=0 and (id='.$value['id'].' or id IN (select t.id from hq_agent_users t where FIND_IN_SET('.$value['id'].',ancestors)))) a on a.id=u.agent_id
            ';
            $ssql = 'select IFNULL(SUM(t1.get_money),0) as money,a.id AS agentId from (select * from('.$dataSql.') s where s.creatime between '.strtotime($begin).' and '.strtotime($end).') t1 
            left join hq_user u on t1.user_id = u.user_id
            RIGHT join (select id from hq_agent_users where del_flag=0 and (id='.$value['id'].' or id IN (select t.id from hq_agent_users t where FIND_IN_SET('.$value['id'].',ancestors)))) a on a.id=u.agent_id group by a.id
            ';
            $asql = 'select ifnull(sum(l.money),0) as money from hq_live_reward l
                left join hq_user u on u.user_id = l.user_id
                inner join (select id from hq_agent_users where del_flag=0 and (id='.$value['id'].' or id IN (select t.id from hq_agent_users t where FIND_IN_SET('.$value['id'].',ancestors)))) a on a.id=u.agent_id';
            $data[$key]['reward']=DB::select($asql);
            $data[$key]['fee']=json_decode($value['fee'],true);
            if ($sql!="" || $sql!=null){
                $money=0;
                $moneyData = DB::select($ssql);
                $userData = DB::select($sql);
                $data[$key]['sum_betMoney'] = $this->getSumBetMoney($userData);
                $data[$key]['win_money']=$this->getWinMoney($userData);
                $data[$key]['code']=$this->getSumCode($userData);
                $data[$key]['pump']=$this->getSumPump($userData,$value['id']);
                foreach ($moneyData as $k=>$datum){
                    if ($datum->money<0){
                        $money = $money + $datum->money * $value['proportion']/100;
                    }
                }
                $data[$key]['kesun'] = $money;
            }
        }
        return view('agentDay.list',['list'=>$data,'min'=>config('admin.min_date'),'input'=>$request->all()]);
    }
    public function getWinMoney($data)
    {
        $money=0;
        foreach ($data as $key=>$datum)
        {
            $money = $money + $datum->get_money;
        }
        return $money;
    }

    /**
     * @param $data
     * @param $agentId
     * @return float|int
     */
    public function getSumPump($data,$agentId)
    {
        $money = 0;
        //获取当前代理信息
        $agent = $this->getUserInfoByAgentId($agentId);
        foreach ($data as $key=>$value)
        {
            if ($value->status==1){
                //获取用户信息
                $user = HqUser::getUserInfoByUserId($value->user_id);
                if ($value->game_type==1){//百家乐
                    if ($agent['baccarat']==1){//判断是否具有抽水权限
                        if ($user['bjlbets_fee']!=$agent['bjlbets_fee']){
                            //获取表名
                            $tableName = $this->getGameRecordTableNameByRecordSn($value->record_sn);
                            //获取游戏记录
                            $record = GameRecord::getGameRecordInfo($value->record_sn,$tableName);
                            $orderData = json_decode($value->bet_money,true);//下注相亲数组
                            $jsonArr = json_decode($record['winner'],true);//获取游戏结果
                            $userBetFee = json_decode($user['bjlbets_fee'],true);//获取会员赔率
                            $agentBetFee = json_decode($agent['bjlbets_fee'],true);//获取代理赔率
                            if ($jsonArr['game']==1){//和
                                if ($orderData['tie']>0){
                                    $money = $money + (($agentBetFee['tie'] - $userBetFee['tie'])/100) * $orderData['tie'];
                                }
                            }else if ($jsonArr['game']==7){//庄
                                if ($orderData['banker']>0){
                                    $money = $money + (($agentBetFee['banker'] - $userBetFee['banker'])/100) * $orderData['banker'];
                                }
                            }else if($jsonArr['game']==4){//闲
                                if ($orderData['player']>0){
                                    $money = $money + (($agentBetFee['player'] - $userBetFee['player'])/100) * $orderData['player'];
                                }
                            }
                            if ($jsonArr['bankerPair']!=0){
                                if ($orderData['bankerPair']>0){
                                    $money = $money + (($agentBetFee['bankerPair'] - $userBetFee['bankerPair'])/100) * $orderData['bankerPair'];
                                }
                            }
                            if ($jsonArr['playerPair']!=0){
                                if ($orderData['playerPair']>0){
                                    $money = $money + (($agentBetFee['playerPair'] - $userBetFee['playerPair'])/100) * $orderData['playerPair'];
                                }
                            }
                        }
                    }
                }else if ($value->game_type==2){//龙虎
                    if ($agent['dragon_tiger']==1){//判读是否具有抽水权限
                        if ($user['lhbets_fee']!=$agent['lhbets_fee']){
                            //获取表名
                            $tableName = $this->getGameRecordTableNameByRecordSn($value->record_sn);
                            //获取游戏记录
                            $record = GameRecord::getGameRecordInfo($value->record_sn,$tableName);
                            $orderData = json_decode($value->bet_money,true);//下注相亲数组
                            $userBetFee = json_decode($user['lhbets_fee'],true);//获取会员赔率
                            $agentBetFee = json_decode($agent['lhbets_fee'],true);//获取代理赔率
                            if ($record['winner']==1){//和
                                if ($orderData['tie']>0){
                                    $money = $money + (($agentBetFee['tie'] - $userBetFee['tie'])/100) * $orderData['tie'];
                                }
                            }else if ($record['winner']==4){//龙
                                if ($orderData['dragon']>0){
                                    $money = $money + (($agentBetFee['dragon'] - $userBetFee['dragon'])/100) * $orderData['dragon'];
                                }
                            }else if($record['winner']==7){//虎
                                if ($orderData['tiger']>0){
                                    $money = $money + (($agentBetFee['tiger'] - $userBetFee['tiger'])/100) * $orderData['tiger'];
                                }
                            }
                        }
                    }
                }else if ($value->game_type==3){//牛牛
                    if ($agent['niuniu']==1){//判断是否具有抽水权限
                        if ($user['nnbets_fee']!=$agent['nnbets_fee']){
                            //获取表名
                            $tableName = $this->getGameRecordTableNameByRecordSn($value->record_sn);
                            //获取游戏记录表
                            $record = GameRecord::getGameRecordInfo($value->record_sn,$tableName);
                            $jsonArr = json_decode($record['winner'],true);//获取游戏结果
                            $orderData = json_decode($value->bet_money,true);
                            $userBetFee = json_decode($user['nnbets_fee'],true);
                            $agentBetFee = json_decode($agent['nnbets_fee'],true);
                            if ($jsonArr['x1result']=="win"){
                                $result = $this->nConvertNumbers($jsonArr['x1num']);
                                if ($result>9){
                                    if ($orderData['x1_Super_Double']>0){
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100  * $orderData['x1_Super_Double']) * 10;
                                    }
                                    if ($orderData['x1_double']>0){
                                        $money = $money + (($agentBetFee['Double'] -  $userBetFee['Double'])/100 * $orderData['x1_double']) * 3;
                                    }
                                    if ($orderData['x1_equal']>0){
                                        $money = $money + (($agentBetFee['Equal'] - $userBetFee['Equal'])/100) * $orderData['x1_equal'];
                                    }
                                }else if ($result>0 && $result<10){
                                    if ($orderData['x1_Super_Double']>0){
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100  * $orderData['x1_Super_Double']) * $result;
                                    }
                                    if ($result<10 && $result>6){
                                        if ($orderData['x1_double']>0){
                                            $money = $money + (($agentBetFee['Double'] -  $userBetFee['Double'])/100 * $orderData['x1_double']) * 2;
                                        }
                                    }
                                    if ($orderData['x1_equal']>0){
                                        $money = $money + (($agentBetFee['Equal'] - $userBetFee['Equal'])/100) * $orderData['x1_equal'];
                                    }
                                }else{
                                    if ($orderData['x1_Super_Double']>0){
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100  * $orderData['x1_Super_Double']);
                                    }

                                    if ($orderData['x1_double']>0){
                                        $money = $money + (($agentBetFee['Double'] -  $userBetFee['Double'])/100 * $orderData['x1_double']);
                                    }

                                    if ($orderData['x1_equal']>0){
                                        $money = $money + (($agentBetFee['Equal'] - $userBetFee['Equal'])/100) * $orderData['x1_equal'];
                                    }
                                }
                            }
                            if ($jsonArr['x2result']=='win'){
                                $result = $this->nConvertNumbers($jsonArr['x2num']);
                                if ($result>9){
                                    if ($orderData['x2_Super_Double']>0){
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100  * $orderData['x2_Super_Double']) * 10;
                                    }
                                    if ($orderData['x2_double']>0){
                                        $money = $money + (($agentBetFee['Double'] -  $userBetFee['Double'])/100 * $orderData['x2_double']) * 3;
                                    }
                                    if ($orderData['x2_equal']>0){
                                        $money = $money + (($agentBetFee['Equal'] - $userBetFee['Equal'])/100) * $orderData['x2_equal'];
                                    }
                                }else if ($result>0 && $result<10){
                                    if ($orderData['x2_Super_Double']>0){
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100  * $orderData['x2_Super_Double']) * $result;
                                    }
                                    if ($result<10 && $result>6){
                                        if ($orderData['x2_double']>0){
                                            $money = $money + (($agentBetFee['Double'] -  $userBetFee['Double'])/100 * $orderData['x2_double']) * 2;
                                        }
                                    }
                                    if ($orderData['x2_equal']>0){
                                        $money = $money + (($agentBetFee['Equal'] - $userBetFee['Equal'])/100) * $orderData['x2_equal'];
                                    }
                                }else{
                                    if ($orderData['x2_Super_Double']>0){
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100  * $orderData['x2_Super_Double']);
                                    }

                                    if ($orderData['x2_double']>0){
                                        $money = $money + (($agentBetFee['Double'] -  $userBetFee['Double'])/100 * $orderData['x2_double']);
                                    }

                                    if ($orderData['x2_equal']>0){
                                        $money = $money + (($agentBetFee['Equal'] - $userBetFee['Equal'])/100) * $orderData['x2_equal'];
                                    }
                                }
                            }
                            if ($jsonArr['x3result']=='win'){
                                $result = $this->nConvertNumbers($jsonArr['x3num']);
                                if ($result>9){
                                    if ($orderData['x3_Super_Double']>0){
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100  * $orderData['x3_Super_Double']) * 10;
                                    }
                                    if ($orderData['x3_double']>0){
                                        $money = $money + (($agentBetFee['Double'] -  $userBetFee['Double'])/100 * $orderData['x3_double']) * 3;
                                    }
                                    if ($orderData['x3_equal']>0){
                                        $money = $money + (($agentBetFee['Equal'] - $userBetFee['Equal'])/100) * $orderData['x3_equal'];
                                    }
                                }else if ($result>0 && $result<10){
                                    if ($orderData['x3_Super_Double']>0){
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100  * $orderData['x3_Super_Double']) * $result;
                                    }
                                    if ($result<10 && $result>6){
                                        if ($orderData['x3_double']>0){
                                            $money = $money + (($agentBetFee['Double'] -  $userBetFee['Double'])/100 * $orderData['x3_double']) * 2;
                                        }
                                    }
                                    if ($orderData['x3_equal']>0){
                                        $money = $money + (($agentBetFee['Equal'] - $userBetFee['Equal'])/100) * $orderData['x3_equal'];
                                    }
                                }else{
                                    if ($orderData['x3_Super_Double']>0){
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100  * $orderData['x3_Super_Double']);
                                    }

                                    if ($orderData['x3_double']>0){
                                        $money = $money + (($agentBetFee['Double'] -  $userBetFee['Double'])/100 * $orderData['x3_double']);
                                    }

                                    if ($orderData['x3_equal']>0){
                                        $money = $money + (($agentBetFee['Equal'] - $userBetFee['Equal'])/100) * $orderData['x3_equal'];
                                    }
                                }
                            }
                        }
                    }
                }else if ($value->game_type==4){//三公
                    if ($agent['sangong']==1){
                        if ($user['sgbets_fee']!=$agent['sgbets_fee']){
                            //获取表名
                            $tableName = $this->getGameRecordTableNameByRecordSn($value->record_sn);
                            //获取游戏记录表
                            $record = GameRecord::getGameRecordInfo($value->record_sn,$tableName);
                            $jsonArr = json_decode($record['winner'],true);//获取游戏结果
                            $orderData = json_decode($value->bet_money,true);
                            $userBetFee = json_decode($user['sgbets_fee'],true);
                            $agentBetFee = json_decode($agent['sgbets_fee'],true);
                            if ($jsonArr['x1result']=="win"){
                                $result = $this->sConvertNumbers($jsonArr['x1num']);
                                if ($orderData['x1_Super_Double'] >0){
                                    if ($result>9){
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100 * $orderData['x1_Super_Double']) * 10;
                                    }else if ($result>0 && $result<10){
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100 * $orderData['x1_Super_Double']) * $result;
                                    }else{
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100) * $orderData['x1_Super_Double'];
                                    }
                                }
                                if ($orderData['x1_double'] > 0){
                                    if ($result>9){
                                        $money = $money + (($agentBetFee['Double'] - $userBetFee['Double'])/100 * $orderData['x1_double']) * 3;
                                    }else if ($result>6 && $result<10){
                                        $money = $money + (($agentBetFee['Double'] - $userBetFee['Double'])/100 * $orderData['x1_double']) * 2;
                                    }else{
                                        $money = $money + (($agentBetFee['Double'] - $userBetFee['Double'])/100) * $orderData['x1_double'];
                                    }
                                }
                                if ($orderData['x1_equal']>0){
                                    $money = $money + (($agentBetFee['Equal'] - $userBetFee['Equal'])/100) * $orderData['x1_equal'];
                                }
                            }
                            if ($jsonArr['x2result']=="win"){
                                $result = $this->sConvertNumbers($jsonArr['x2num']);
                                if ($orderData['x2_Super_Double'] >0){
                                    if ($result>9){
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100 * $orderData['x2_Super_Double']) * 10;
                                    }else if ($result>0 && $result<10){
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100 * $orderData['x2_Super_Double']) * $result;
                                    }else{
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100) * $orderData['x2_Super_Double'];
                                    }
                                }
                                if ($orderData['x2_double'] > 0){
                                    if ($result>9){
                                        $money = $money + (($agentBetFee['Double'] - $userBetFee['Double'])/100 * $orderData['x2_double']) * 3;
                                    }else if ($result>6 && $result<10){
                                        $money = $money + (($agentBetFee['Double'] - $userBetFee['Double'])/100 * $orderData['x2_double']) * 2;
                                    }else{
                                        $money = $money + (($agentBetFee['Double'] - $userBetFee['Double'])/100) * $orderData['x2_double'];
                                    }
                                }
                                if ($orderData['x2_equal']>0){
                                    $money = $money + (($agentBetFee['Equal'] - $userBetFee['Equal'])/100) * $orderData['x2_equal'];
                                }
                            }
                            if ($jsonArr['x3result']=="win"){
                                $result = $this->sConvertNumbers($jsonArr['x3num']);
                                if ($orderData['x3_Super_Double'] >0){
                                    if ($result>9){
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100 * $orderData['x3_Super_Double']) * 10;
                                    }else if ($result>0 && $result<10){
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100 * $orderData['x3_Super_Double']) * $result;
                                    }else{
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100) * $orderData['x3_Super_Double'];
                                    }
                                }
                                if ($orderData['x3_double'] > 0){
                                    if ($result>9){
                                        $money = $money + (($agentBetFee['Double'] - $userBetFee['Double'])/100 * $orderData['x3_double']) * 3;
                                    }else if ($result>6 && $result<10){
                                        $money = $money + (($agentBetFee['Double'] - $userBetFee['Double'])/100 * $orderData['x3_double']) * 2;
                                    }else{
                                        $money = $money + (($agentBetFee['Double'] - $userBetFee['Double'])/100) * $orderData['x3_double'];
                                    }
                                }
                                if ($orderData['x3_equal']>0){
                                    $money = $money + (($agentBetFee['Equal'] - $userBetFee['Equal'])/100) * $orderData['x3_equal'];
                                }
                            }
                            if ($jsonArr['x4result']=="win"){
                                $result = $this->sConvertNumbers($jsonArr['x4num']);
                                if ($orderData['x4_Super_Double'] >0){
                                    if ($result>9){
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100 * $orderData['x4_Super_Double']) * 10;
                                    }else if ($result>0 && $result<10){
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100 * $orderData['x4_Super_Double']) * $result;
                                    }else{
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100) * $orderData['x4_Super_Double'];
                                    }
                                }
                                if ($orderData['x4_double'] > 0){
                                    if ($result>9){
                                        $money = $money + (($agentBetFee['Double'] - $userBetFee['Double'])/100 * $orderData['x4_double']) * 3;
                                    }else if ($result>6 && $result<10){
                                        $money = $money + (($agentBetFee['Double'] - $userBetFee['Double'])/100 * $orderData['x4_double']) * 2;
                                    }else{
                                        $money = $money + (($agentBetFee['Double'] - $userBetFee['Double'])/100) * $orderData['x4_double'];
                                    }
                                }
                                if ($orderData['x4_equal']>0){
                                    $money = $money + (($agentBetFee['Equal'] - $userBetFee['Equal'])/100) * $orderData['x4_equal'];
                                }
                            }
                            if ($jsonArr['x5result']=="win"){
                                $result = $this->sConvertNumbers($jsonArr['x5num']);
                                if ($orderData['x5_Super_Double'] >0){
                                    if ($result>9){
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100 * $orderData['x5_Super_Double']) * 10;
                                    }else if ($result>0 && $result<10){
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100 * $orderData['x5_Super_Double']) * $result;
                                    }else{
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100) * $orderData['x5_Super_Double'];
                                    }
                                }
                                if ($orderData['x5_double'] > 0){
                                    if ($result>9){
                                        $money = $money + (($agentBetFee['Double'] - $userBetFee['Double'])/100 * $orderData['x5_double']) * 3;
                                    }else if ($result>6 && $result<10){
                                        $money = $money + (($agentBetFee['Double'] - $userBetFee['Double'])/100 * $orderData['x5_double']) * 2;
                                    }else{
                                        $money = $money + (($agentBetFee['Double'] - $userBetFee['Double'])/100) * $orderData['x5_double'];
                                    }
                                }
                                if ($orderData['x5_equal']>0){
                                    $money = $money + (($agentBetFee['Equal'] - $userBetFee['Equal'])/100) * $orderData['x5_equal'];
                                }
                            }
                            if ($jsonArr['x6result']=="win"){
                                $result = $this->sConvertNumbers($jsonArr['x6num']);
                                if ($orderData['x6_Super_Double'] >0){
                                    if ($result>9){
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100 * $orderData['x6_Super_Double']) * 10;
                                    }else if ($result>0 && $result<10){
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100 * $orderData['x6_Super_Double']) * $result;
                                    }else{
                                        $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100) * $orderData['x6_Super_Double'];
                                    }
                                }
                                if ($orderData['x6_double'] > 0){
                                    if ($result>9){
                                        $money = $money + (($agentBetFee['Double'] - $userBetFee['Double'])/100 * $orderData['x6_double']) * 3;
                                    }else if ($result>6 && $result<10){
                                        $money = $money + (($agentBetFee['Double'] - $userBetFee['Double'])/100 * $orderData['x6_double']) * 2;
                                    }else{
                                        $money = $money + (($agentBetFee['Double'] - $userBetFee['Double'])/100) * $orderData['x6_double'];
                                    }
                                }
                                if ($orderData['x6_equal']>0){
                                    $money = $money + (($agentBetFee['Equal'] - $userBetFee['Equal'])/100) * $orderData['x6_equal'];
                                }
                            }
                        }
                    }
                }else if($value->game_type==5){//A89
                    if ($agent['A89']==1){
                        if ($user['a89bets_fee']!=$agent['a89bets_fee']){
                            //获取表名
                            $tableName = $this->getGameRecordTableNameByRecordSn($value->record_sn);
                            //获取游戏记录
                            $record = GameRecord::getGameRecordInfo($value->record_sn,$tableName);
                            $jsonArr = json_decode($record['winner'],true);//获取游戏结果
                            $orderData = json_decode($value->bet_money,true);
                            $userBetFee = json_decode($user['a89bets_fee'],true);
                            $agentBetFee = json_decode($agent['a89bets_fee'],true);
                            if ($jsonArr['Fanresult']=="win"){
                                $result = $this->aConvertNumbers($jsonArr['FanNum']);
                                if ($orderData['FanMen_Super_Double']>0){
                                    $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100 * $orderData['FanMen_Super_Double']) * $result;
                                }
                                if ($orderData['FanMen_equal']>0){
                                    $money = $money + (($agentBetFee['Equal'] - $userBetFee['Equal'])/100) * $orderData['FanMen_equal'];
                                }
                            }
                            if ($jsonArr['Shunresult']=="win"){
                                $result = $this->aConvertNumbers($jsonArr['FanNum']);
                                if ($orderData['ShunMen_Super_Double']>0){
                                    $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100 * $orderData['ShunMen_Super_Double']) * $result;
                                }
                                if ($orderData['ShunMen_equal']>0){
                                    $money = $money + (($agentBetFee['Equal'] - $userBetFee['Equal'])/100) * $orderData['ShunMen_equal'];
                                }
                            }
                            if ($jsonArr['Tianresult']=="win"){
                                $result = $this->aConvertNumbers($jsonArr['FanNum']);
                                if ($orderData['TianMen_Super_Double']>0){
                                    $money = $money + (($agentBetFee['SuperDouble'] - $userBetFee['SuperDouble'])/100 * $orderData['TianMen_Super_Double']) * $result;
                                }
                                if ($orderData['TianMen_equal']>0){
                                    $money = $money + (($agentBetFee['Equal'] - $userBetFee['Equal'])/100) * $orderData['TianMen_equal'];
                                }
                            }
                        }
                    }
                }
            }
        }
        return $money;
    }

    /**
     * 把牛牛游戏结果转成数字
     * @param $str
     * @return int
     */
    public function nConvertNumbers($str){
        $num=0;
        switch ($str)
        {
            case "炸弹牛":
                $num=12;
                break;
            case "五花牛":
                $num=11;
                break;
            case "牛牛":
                $num=10;
                break;
            case "牛9":
                $num=9;
                break;
            case "牛8":
                $num=8;
                break;
            case "牛7":
                $num=7;
                break;
            case "牛6":
                $num=6;
                break;
            case "牛5":
                $num=5;
                break;
            case "牛4":
                $num=4;
                break;
            case "牛3":
                $num=3;
                break;
            case "牛2":
                $num=2;
                break;
            case "牛1":
                $num=1;
                break;
            default:
                $num=0;
                break;
        }
        return $num;
    }

    /**
     * 三公结果转数字
     * @param $str
     * @return int
     */
    public function sConvertNumbers($str){
        switch ($str)
        {
            case "0点":
                $count = 0;
                break;
	        case "1点":
                $count=1;
                break;
	        case "2点":
                $count=2;
                break;
	        case "3点":
                $count=3;
                break;
	        case "4点":
                $count=4;
                break;
	        case "5点":
                $count=5;
                break;
	        case "6点":
                $count=6;
                break;
	        case "7点":
                $count=7;
                break;
	        case "8点":
                $count=8;
                break;
	        case "9点":
                $count=9;
                break;
	        case "混三公":
                $count=10;
                break;
	        case "小三公":
                $count=11;
                break;
	        default:
                $count=12;
        }
        return $count;
    }
    /**
     * a89结果转数字
     * @param $str
     * @return int
     */
    public function aConvertNumbers($str){
        switch ($str)
        {
            case "0点":
                $count=1;
                break;
            case "1点":
                $count=1;
                break;
            case "2点":
                $count=2;
                break;
            case "3点":
                $count=3;
            case "4点":
                $count=4;
                break;
            case "5点":
                $count=5;
                break;
            case "6点":
                $count=6;
                break;
            case "7点":
                $count=7;
                break;
            case "8点":
                $count=8;
                break;
            case "9点":
                $count=9;
                break;
            default:
                $count=10;
        }
        return $count;
    }

    /**
     * 通过agentId获取代理信息
     * @param $agentId
     * @return User|User[]|array|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function getUserInfoByAgentId($agentId){
        return $agentId?User::find($agentId):[];
    }

    /**
     * 通过userId获取用户信息
     * @param $userId
     * @return HqUser|HqUser[]|array|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function getHqUserByUserId($userId){
        return $userId?HqUser::find($userId):[];
    }

    //根据游戏单号获取表名
    public function getGameRecordTableNameByRecordSn($recordSn)
    {
        return substr($recordSn,0,8);
    }
    /**
     * 总押码
     * @param $data
     * @return float|int|mixed
     */
    public function getSumBetMoney($data){
        $money = 0;
        foreach ($data as $key=>$datum){
            if ($datum->game_type==1){
                $jsonArr = json_decode($datum->bet_money,true);
                $money = $money + array_sum($jsonArr);
            }else if($datum->game_type==2){
                $jsonArr = json_decode($datum->bet_money,true);
                $money = $money + array_sum($jsonArr);
            }else if ($datum->game_type==3){
                $jsonArr = json_decode($datum->bet_money,true);
                if (!empty($jsonArr['x1_Super_Double'])){
                    $money = $money + $jsonArr['x1_Super_Double'] *10;
                }
                if (!empty($jsonArr['x2_Super_Double'])){
                    $money = $money + $jsonArr['x2_Super_Double'] *10;
                }
                if (!empty($jsonArr['x3_Super_Double'])){
                    $money = $money + $jsonArr['x3_Super_Double'] *10;
                }
                if (!empty($jsonArr['x1_double'])){
                    $money = $money + $jsonArr['x1_double'] * 3;
                }
                if (!empty($jsonArr['x2_double'])){
                    $money = $money + $jsonArr['x2_double'] * 3;
                }
                if (!empty($jsonArr['x3_double'])){
                    $money = $money + $jsonArr['x3_double'] * 3;
                }
                if (!empty($jsonArr['x1_equal'])){
                    $money = $money + $jsonArr['x1_equal'];
                }
                if (!empty($jsonArr['x2_equal'])){
                    $money = $money + $jsonArr['x2_equal'];
                }
                if (!empty($jsonArr['x3_equal'])){
                    $money = $money + $jsonArr['x3_equal'];
                }
            }else if($datum->game_type==4){
                $jsonArr = json_decode($datum->bet_money,true);
                if (!empty($jsonArr['x1_Super_Double'])){
                    $money=$money + $jsonArr['x1_Super_Double'] * 10;
                }
                if (!empty($jsonArr['x2_Super_Double'])){
                    $money=$money + $jsonArr['x2_Super_Double'] * 10;
                }
                if (!empty($jsonArr['x3_Super_Double'])){
                    $money=$money + $jsonArr['x3_Super_Double'] * 10;
                }
                if (!empty($jsonArr['x4_Super_Double'])){
                    $money=$money + $jsonArr['x4_Super_Double'] * 10;
                }
                if (!empty($jsonArr['x5_Super_Double'])){
                    $money=$money + $jsonArr['x5_Super_Double'] * 10;
                }
                if (!empty($jsonArr['x6_Super_Double'])){
                    $money=$money + $jsonArr['x6_Super_Double'] * 10;
                }
                if (!empty($jsonArr['x1_double'])){
                    $money = $money + $jsonArr['x1_double'] * 3;
                }
                if (!empty($jsonArr['x2_double'])){
                    $money = $money + $jsonArr['x2_double'] * 3;
                }
                if (!empty($jsonArr['x3_double'])){
                    $money = $money + $jsonArr['x3_double'] * 3;
                }
                if (!empty($jsonArr['x4_double'])){
                    $money = $money + $jsonArr['x4_double'] * 3;
                }
                if (!empty($jsonArr['x5_double'])){
                    $money = $money + $jsonArr['x5_double'] * 3;
                }
                if (!empty($jsonArr['x6_double'])){
                    $money = $money + $jsonArr['x6_double'] * 3;
                }
                if (!empty($jsonArr['x1_equal'])){
                    $money = $money + $jsonArr['x1_equal'];
                }
                if (!empty($jsonArr['x2_equal'])){
                    $money = $money + $jsonArr['x2_equal'];
                }
                if (!empty($jsonArr['x3_equal'])){
                    $money = $money + $jsonArr['x3_equal'];
                }
                if (!empty($jsonArr['x4_equal'])){
                    $money = $money + $jsonArr['x4_equal'];
                }
                if (!empty($jsonArr['x5_equal'])){
                    $money = $money + $jsonArr['x5_equal'];
                }
                if (!empty($jsonArr['x6_equal'])){
                    $money = $money + $jsonArr['x6_equal'];
                }
            }else if($datum->game_type==5){
                $jsonArr = json_decode($datum->bet_money,true);
                if (!empty($jsonArr['ShunMen_Super_Double'])){
                    $money = $money + $jsonArr['ShunMen_Super_Double'] * 10;
                }
                if (!empty($jsonArr['TianMen_Super_Double'])){
                    $money = $money + $jsonArr['TianMen_Super_Double'] * 10;
                }
                if (!empty($jsonArr['FanMen_Super_Double'])){
                    $money = $money + $jsonArr['FanMen_Super_Double'] * 10;
                }
                if (!empty($jsonArr['ShunMen_equal'])){
                    $money = $money + $jsonArr['ShunMen_equal'];
                }
                if (!empty($jsonArr['TianMen_equal'])){
                    $money = $money + $jsonArr['TianMen_equal'];
                }
                if (!empty($jsonArr['FanMen_equal'])){
                    $money = $money + $jsonArr['FanMen_equal'];
                }
            }
        }
        return $money;
    }

    /**
     * 获取总洗码
     * @param $data
     * @return float|int|mixed
     */
    public function getSumCode($data){
        $money = 0;
        foreach ($data as $key=>$datum){
            if ($datum->status==1){
                if ($datum->game_type==1){
                    $jsonArr = json_decode($datum->bet_money,true);
                    $money = $money + array_sum($jsonArr);
                }else if($datum->game_type==2){
                    $jsonArr = json_decode($datum->bet_money,true);
                    $money = $money + array_sum($jsonArr);
                }else if ($datum->game_type==3){
                    $jsonArr = json_decode($datum->bet_money,true);
                    if (!empty($jsonArr['x1_Super_Double'])){
                        $money = $money + $jsonArr['x1_Super_Double'] *10;
                    }
                    if (!empty($jsonArr['x2_Super_Double'])){
                        $money = $money + $jsonArr['x2_Super_Double'] *10;
                    }
                    if (!empty($jsonArr['x3_Super_Double'])){
                        $money = $money + $jsonArr['x3_Super_Double'] *10;
                    }
                    if (!empty($jsonArr['x1_double'])){
                        $money = $money + $jsonArr['x1_double'] * 3;
                    }
                    if (!empty($jsonArr['x2_double'])){
                        $money = $money + $jsonArr['x2_double'] * 3;
                    }
                    if (!empty($jsonArr['x3_double'])){
                        $money = $money + $jsonArr['x3_double'] * 3;
                    }
                    if (!empty($jsonArr['x1_equal'])){
                        $money = $money = $jsonArr['x1_equal'];
                    }
                    if (!empty($jsonArr['x2_equal'])){
                        $money = $money + $jsonArr['x2_equal'];
                    }
                    if (!empty($jsonArr['x3_equal'])){
                        $money = $money + $jsonArr['x3_equal'];
                    }
                }else if($datum->game_type==4){
                    $jsonArr = json_decode($datum->bet_money,true);
                    if (!empty($jsonArr['x1_Super_Double'])){
                        $money=$money + $jsonArr['x1_Super_Double'] * 10;
                    }
                    if (!empty($jsonArr['x2_Super_Double'])){
                        $money=$money + $jsonArr['x2_Super_Double'] * 10;
                    }
                    if (!empty($jsonArr['x3_Super_Double'])){
                        $money=$money + $jsonArr['x3_Super_Double'] * 10;
                    }
                    if (!empty($jsonArr['x4_Super_Double'])){
                        $money=$money + $jsonArr['x4_Super_Double'] * 10;
                    }
                    if (!empty($jsonArr['x5_Super_Double'])){
                        $money=$money + $jsonArr['x5_Super_Double'] * 10;
                    }
                    if (!empty($jsonArr['x6_Super_Double'])){
                        $money=$money + $jsonArr['x6_Super_Double'] * 10;
                    }
                    if (!empty($jsonArr['x1_double'])){
                        $money = $money + $jsonArr['x1_double'] * 3;
                    }
                    if (!empty($jsonArr['x2_double'])){
                        $money = $money + $jsonArr['x2_double'] * 3;
                    }
                    if (!empty($jsonArr['x3_double'])){
                        $money = $money + $jsonArr['x3_double'] * 3;
                    }
                    if (!empty($jsonArr['x4_double'])){
                        $money = $money + $jsonArr['x4_double'] * 3;
                    }
                    if (!empty($jsonArr['x5_double'])){
                        $money = $money + $jsonArr['x5_double'] * 3;
                    }
                    if (!empty($jsonArr['x6_double'])){
                        $money = $money + $jsonArr['x6_double'] * 3;
                    }
                    if (!empty($jsonArr['x1_equal'])){
                        $money = $money + $jsonArr['x1_equal'];
                    }
                    if (!empty($jsonArr['x2_equal'])){
                        $money = $money + $jsonArr['x2_equal'];
                    }
                    if (!empty($jsonArr['x3_equal'])){
                        $money = $money + $jsonArr['x3_equal'];
                    }
                    if (!empty($jsonArr['x4_equal'])){
                        $money = $money + $jsonArr['x4_equal'];
                    }
                    if (!empty($jsonArr['x5_equal'])){
                        $money = $money + $jsonArr['x5_equal'];
                    }
                    if (!empty($jsonArr['x6_equal'])){
                        $money = $money + $jsonArr['x6_equal'];
                    }
                }else if($datum->game_type==5){
                    $jsonArr = json_decode($datum->bet_money,true);
                    if (!empty($jsonArr['ShunMen_Super_Double'])){
                        $money = $money + $jsonArr['ShunMen_Super_Double'] * 10;
                    }
                    if (!empty($jsonArr['TianMen_Super_Double'])){
                        $money = $money + $jsonArr['TianMen_Super_Double'] * 10;
                    }
                    if (!empty($jsonArr['FanMen_Super_Double'])){
                        $money = $money + $jsonArr['FanMen_Super_Double'] * 10;
                    }
                    if (!empty($jsonArr['ShunMen_equal'])){
                        $money = $money + $jsonArr['ShunMen_equal'];
                    }
                    if (!empty($jsonArr['TianMen_equal'])){
                        $money = $money + $jsonArr['TianMen_equal'];
                    }
                    if (!empty($jsonArr['FanMen_equal'])){
                        $money = $money + $jsonArr['FanMen_equal'];
                    }
                }
            }
        }
        return $money;
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
     * 获取昨天的开始时间
     * @return false|int
     */
    public function getYesterdayBeginTime(){
        return strtotime(date("Y-m-d",strtotime("-1 day")));
    }

    /**
     * 根据昨天的开始时间获取到结束时间
     * @param $time 昨天的开始时间
     * @return float|int
     */
    public function getYesterdayEndTime($time){
        return $time+24 * 60 * 60-1;
    }
}