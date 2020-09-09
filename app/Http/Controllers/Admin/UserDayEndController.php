<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Desk;
use App\Models\GameRecord;
use App\Models\HqUser;
use App\Models\LiveReward;
use App\Models\Maintain;
use App\Models\Order;
use App\Models\UserAccount;
use App\Models\UserDayEnd;
use App\Models\UserRebate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 会员日结表
 * Class UserDayEndController
 * @package App\Http\Controllers\Admin
 */
class UserDayEndController extends Controller
{
    public function index(Request $request)
    {
        $map = array();
        $sql = UserRebate::query();
        $sql->leftJoin('user','user.user_id','=','user_rebate.user_id')
            ->leftJoin('user_account','user_account.user_id','=','user_rebate.user_id')
            ->select('user_rebate.id');
        if (true==$request->has('userType'))
        {
            $map['user_rebate.userType']=$request->input('userType');
        }
        if (true==$request->has('account'))
        {
            //根据逗号分割字符串转成数组
            $accountArr = explode(',',$request->input('account'));
            $sql->whereIn('user.account',$accountArr);
        }
        else
        {
            $sql->where('user_rebate.id','=',0);
        }
        if (true==$request->has('begin'))
        {
            $begin = strtotime($request->input('begin'));
            if (true==$request->has('end'))
            {
                $end = strtotime('+1day',strtotime($request->input('end')))-1;
            }
            else
            {
                $end = strtotime('+1day',$begin)-1;
                $request->offsetSet('end',date('Y-m-d',$end));
            }
            $sql->where($map)->whereBetween('user_rebate.creatime',[$begin,$end])->groupBy('user_rebate.user_id','user_rebate.creatime');
        }
        else
        {
            $request->offsetSet('begin',date('Y-m-d',time()));
            $request->offsetSet('end',date('Y-m-d',time()));
        }
        $bool = $this->checkIsToDay($request->input('begin'),$request->input('end'));
        if ($bool)
        {
            if (true==$request->has('account'))
            {
                $account = explode(',',$request->input('account'));
                $userIdArr = array();
                foreach ($account as $key)
                {
                    $userIdArr[] = HqUser::where('account','=',$key)->first()['user_id'];
                }
                $tableName = date('Ymd',time());
                $order = new Order();
                $order->setTable('order_'.$tableName);
                $orderData = $order->select('user_id',DB::raw('SUM(1) as count'),DB::raw('SUM(get_money) as get_money'))->whereIn('user_id',$userIdArr)->groupBy('user_id')->get()->toArray();
                foreach ($orderData as $key=>$datum)
                {
                    $user = $datum['user_id']?HqUser::find($datum['user_id']):[];
                    $userBalance = UserAccount::where('user_id','=',$datum['user_id'])->first();
                    //用户
                    $orderData[$key]['nickname']=$user['nickname'];
                    $orderData[$key]['account']=$user['account'];
                    $orderData[$key]['balance']=$userBalance['balance'];
                    $orderData[$key]['user_type']=$user['user_type'];
                    $orderDataByUserId = $order->select('user_id','record_sn','bet_money','status','game_type')->where('user_id','=',$datum['user_id'])->get();
                    foreach ($orderDataByUserId as $k=>$d)
                    {
                        $orderData[$key]['feeMoney'] = $this->getPump($d);
                    }
                    //总下注金额
                    $orderData[$key]['sumMoney']=$this->getSumBetMoney($orderDataByUserId);
                    //有效下注金额
                    $orderData[$key]['money']=$this->getSumMoney($orderDataByUserId);
                    //打赏金额
                    $money = LiveReward::where('user_id','=',$datum['user_id'])->sum('money');
                    $orderData[$key]['reward']=$money;

                }
            }else{
                $orderData=array();
            }
        }else{
            $orderData=array();
        }
        //对应分页插件初始化每页显示条数
        if (true==$request->has('limit'))
        {
            $limit = $request->input('limit');
        }
        else
        {
            $limit = 10;
        }
        $dataSql = UserRebate::whereIn('user_rebate.id',$sql->get());
        $data = $dataSql->leftJoin('user','user.user_id','=','user_rebate.user_id')
            ->leftJoin('user_account','user_account.user_id','=','user_rebate.user_id')
            ->select('user_rebate.user_id','user.nickname','user.account','user_account.balance',DB::raw('SUM(betNum) as betNum'),
                DB::raw('SUM(washMoney) as washMoney'),DB::raw('SUM(betMoney) as betMoney'),DB::raw('SUM(getMoney) as getMoney'),DB::raw('SUM(feeMoney) as feeMoney'),'user_rebate.userType')->groupBy('user_rebate.user_id')->get()->toArray();
        foreach ($data as $key=>$datum)
        {
            $data[$key]['reward']=LiveReward::getSumMoney($datum['user_id'],$begin,$end);
        }
        foreach ($orderData as $key=>$datum)
        {
            $arr = $this->updateDate($datum['user_id'],$data);
            if ($arr['code']==0)
            {
                $a = array();
                $a['user_id']=$datum['user_id'];
                $a['nickname']=$datum['nickname'];
                $a['account']=$datum['account'];
                $a['balance']=$datum['balance'];
                $a['betNum']=$datum['count'];
                $a['washMoney']=$datum['sumMoney'];
                $a['betMoney']=$datum['money'];
                $a['feeMoney']=$datum['feeMoney'];
                $a['userType']=$datum['user_type'];
                $a['getMoney']=$datum['get_money'];
                $a['reward']=$datum['reward'];
                $data[]=$a;
            }else{
                $index = $arr['index'];
                $data[$index]['feeMoney']=$data[$index]['feeMoney']+$datum['feeMoney'];
                $data[$index]['betNum']=$data[$index]['betNum'] + $datum['count'];
                $data[$index]['washMoney']=$data[$index]['washMoney']+$datum['sumMoney'];
                $data[$index]['betMoney']=$data[$index]['betMoney']+$datum['money'];
                $data[$index]['getMoney']=$data[$index]['getMoney']+$datum['get_money'];
                $data[$index]['reward']=$data[$index]['reward']+$datum['reward'];
            }
        }
        if (true==$request->has('excel'))
        {
            $head = array('台类型','名称','账号','当前金额','下注次数','下注金额','总洗码','派彩所赢','抽水','码佣总额','打赏金额');
            $excel = array();
            foreach ($data as $key=>$datum)
            {
                $a = array();
                $a['type']='全部';
                $a['name']=$datum['nickname'];
                $a['account']=$datum['account'];
                $a['balance']=number_format($datum['balance']/100,2);
                $a['betNum']=$datum['betNum'];
                $a['washMoney']=number_format($datum['washMoney']/100,2);
                $a['betMoney']=number_format($datum['betMoney']/100,2);
                $a['getMoney']=number_format($datum['getMoney']/100,2);
                $a['feeMoney']=number_format($datum['feeMoney']/100,2);
                if ($datum['userType']==1)
                {
                    $a['fee']=number_format($datum['betMoney']/100 * 0.009,2);
                }else
                {
                    $a['fee']='-';
                }
                $a['reward']=number_format($datum['reward']/100,2);
                $excel[] = $a;
            }
            try {
                exportExcel($head, $excel, date('Y-m-d H:i:s',time()).'会员日结', '', true);
            } catch (\PHPExcel_Reader_Exception $e) {
            } catch (\PHPExcel_Exception $e) {
            }
        }
        return view('userDay.list',['list'=>$data,'input'=>$request->all(),'limit'=>$limit]);
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

    /**
     * 根据代理查询会员日结
     * @param $id
     * @param $begin
     * @param $end
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function getUserDayEndByAgentId($id,$begin,$end,Request $request)
    {
        if (true==$request->has('begin'))
        {
            $begin = $request->input('begin');
        }else{
            $request->offsetSet('begin',$begin);
        }
        if (true==$request->has('end'))
        {
            $end = $request->input('end');
        }
        else
        {
            $request->offsetSet('end',$end);
        }
        $bool = $this->checkIsToDay($request->input('begin'),$request->input('end'));
        if ($bool)
        {
            $arr = array();
            $arr['u.agent_id']=$id;
            $order = new Order();
            $order->setTable('order_'.date('Ymd',time()).' as order');
            $sql = $order->leftJoin('user as u','u.user_id','=','order.user_id')
                ->leftJoin('user_account as ua','ua.user_id','=','u.user_id')
                ->select('u.agent_id','u.user_type','u.user_id','u.nickname','u.account','ua.balance',DB::raw('SUM(1) as betNum'),DB::raw('SUM(get_money) as getMoney'));
            if (true==$request->has('account'))
            {
                $arr['u.account']=$request->input('account');
            }
            $orderData = $sql->where($arr)->groupBy('order.user_id')->get()->toArray();
            foreach ($orderData as $key=>&$datum)
            {
                $datum['washMoney']=0;
                $datum['betMoney']=0;
                $datum['feeMoney']=0;
                $oData = $order->where('order.user_id','=',$datum['user_id'])->get()->toArray();
                foreach ($oData as $k=>$v)
                {
                    $betMoney = json_decode($v['bet_money'],true);
                    $datum['feeMoney']=$datum['feeMoney'] + $this->getPump($v);
                    if ($v['game_type']==1 || $v['game_type']==2)
                    {
                        $datum['washMoney']=$datum['washMoney'] + array_sum($betMoney);
                        if ($v['status']==1)
                        {
                            $datum['betMoney']=$datum['betMoney'] + array_sum($betMoney);
                        }
                    }elseif ($v['game_type']==3)
                    {
                        $datum['washMoney']=$datum['washMoney']+$this->getNiuNiuBetMoney($betMoney);
                        if ($v['status']==1)
                        {
                            $datum['betMoney']=$datum['betMoney'] + $this->getNiuNiuBetMoney($betMoney);
                        }
                    }elseif ($v['game_type']==4)
                    {
                        $datum['washMoney']=$datum['washMoney']+$this->getSanGongBetMoney($betMoney);
                        if ($v['status']==1)
                        {
                            $datum['betMoney']=$datum['betMoney'] + $this->getSanGongBetMoney($betMoney);
                        }
                    }elseif ($v['game_type']==5)
                    {
                        $datum['washMoney']=$datum['washMoney']+$this->getA89BetMoney($betMoney);
                        if ($v['status']==1)
                        {
                            $datum['betMoney']=$datum['betMoney'] + $this->getA89BetMoney($betMoney);
                        }
                    }
                }
            }
        }else{
            $orderData=array();
        }
        //对应分页插件初始化分页参数
        if (true==$request->has('limit'))
        {
            $limit = $request->input('limit');
        }
        else
        {
            $limit = 10;
        }
        $beginTime = strtotime($begin);
        $endTime = strtotime('+1day',strtotime($end))-1;
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
                //$info['feeMoney']=$this->getPump($v);
                $sql = $order->leftJoin('user as u','u.user_id','=','order.user_id')
                    ->leftJoin('user_account as ua','ua.user_id','=','u.user_id')
                    ->select('u.agent_id','u.user_type','u.user_id','u.nickname','u.account','ua.balance');
                $oData = $order->where('order.user_id','=',$v['user_id'])->get()->toArray();
                foreach ($oData as $k=>$va)
                {
                    $feeMoney = 0;
                    $info['feeMoney']=$feeMoney + $this->getPump($va);
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
                    $data[$index]['betNum']=$data[$index]['betNum'] + $datum['betNum'];
                    $data[$index]['washMoney']=$data[$index]['washMoney']+$datum['washMoney'];
                    $data[$index]['betMoney']=$data[$index]['betMoney']+$datum['betMoney'];
                    $data[$index]['getMoney']=$data[$index]['getMoney']+$datum['getMoney'];
                }
            }
        }
        foreach ($data as $key=>$datum)
        {
            $data[$key]['reward']=LiveReward::getSumMoney($datum['user_id'],$begin,$end);
        }
        if (true==$request->has('excel'))
        {
            $head = array('台类型','名称','账号','当前金额','下注次数','下注金额','总洗码','派彩所赢','抽水','码佣总额','打赏金额');
            $excel = array();
            foreach ($data as $key=>$datum)
            {
                $a = array();
                $a['desk_name']='全部';
                $a['nickname']=$datum['nickname'];
                $a['account']=$datum['account'];
                $a['balance']=number_format($datum['balance']/100,2);
                $a['betNum']=$datum['betNum'];
                $a['washMoney']=number_format($datum['washMoney']/100,2);
                $a['betMoney']=number_format($datum['betMoney']/100,2);
                $a['getMoney']=number_format($datum['getMoney']/100,2);
                $a['feeMoney']=number_format($datum['feeMoney']/100,2);
                if ($datum['userType']==1)
                {
                    $a['my']=number_format($datum['betMoney']/100 * 0.009,2);
                }else
                {
                    $a['my']='-';
                }
                $a['reward']=number_format($datum['reward']/100,2);
                $excel[] = $a;
            }
            try {
                exportExcel($head, $excel, date('Y-m-d',time()).'会员日结', '', true);
            } catch (\PHPExcel_Reader_Exception $e) {
            } catch (\PHPExcel_Exception $e) {
            }
        }
        return view('userDay.list',['list'=>$data,'input'=>$request->all(),'limit'=>$limit]);
    }

    public function getPump($order)
    {
        $money = 0;
        $userInfo = HqUser::getUserInfoByUserId($order['user_id']);
        $agentInfo = $this->getAgentInfoByAgentId($userInfo['agent_id']);
        $tableName = $this->getGameRecordTableNameByRecordSn($order['record_sn']);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $gameInfo = $game->where('record_sn','=',$order['record_sn'])->first();
        $betMoney = json_decode($order['bet_money'],true);
        if ($order['game_type']==1)
        {
            $winner = json_decode($gameInfo['winner'],true);
            if ($winner['game']==4)
            {
                if ($betMoney['player']>0)
                {
                    $money = $money + ($agentInfo['bjlbets_fee']['player'] - $userInfo['bjlbets_fee']['player']/100) * $betMoney['player'];
                }
            }
            elseif ($winner['game']==7)
            {
                if ($betMoney['banker']>0)
                {
                    $money = $money + ($agentInfo['bjlbets_fee']['player'] - $userInfo['bjlbets_fee']['player']/100) * $betMoney['banker'];
                }
            }
            elseif ($winner['game']==1)
            {
                if ($betMoney['tie']>0)
                {
                    $money = $money + ($agentInfo['bjlbets_fee']['tie'] - $userInfo['bjlbets_fee']['tie']/100) * $betMoney['tie'];
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
                    $money = $money + ($agentInfo['bjlbets_fee']['playerPair'] - $userInfo['bjlbets_fee']['playerPair']/100) * $betMoney['playerPair'];
                }
            }
        }
        elseif ($order['game_type']==2)
        {
            if ($gameInfo['winner']==4)
            {
                if ($betMoney['tiger']>0)
                {
                    $money = $money + ($agentInfo['lhbets_fee']['player'] - $userInfo['lhbets_fee']['player']/100) * $betMoney['tiger'];
                }
            }
            elseif ($gameInfo['winner']==7)
            {
                if ($betMoney['dragon']>0)
                {
                    $money = $money + ($agentInfo['lhbets_fee']['dragon'] - $userInfo['lhbets_fee']['dragon']/100) * $betMoney['dragon'];
                }
            }
            elseif ($gameInfo['winner']==1)
            {
                if ($betMoney['tie']>0)
                {
                    $money = $money + ($agentInfo['lhbets_fee']['tie'] - $userInfo['lhbets_fee']['tie']/100) * $betMoney['tie'];
                }
            }
        }
        elseif ($order['game_type']==3)
        {
            $winner = json_decode($gameInfo['winner'],true);
            if ($winner['x1result']=="win")
            {
                $x1Num = $this->nConvertNumbers($winner['x1num']);
                if (!empty($betMoney['x1_equal']))
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Equal'] - $userInfo['nnbets_fee']['Equal']/100) * $betMoney['x1_equal'];
                }
                if (!empty($betMoney['x1_double']))
                {
                    if ($x1Num>9)
                    {
                        $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x1_double'] * 3;
                    }
                    elseif ($x1Num>6 && $x1Num<10)
                    {
                        $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x1_double'] * 2;
                    }
                    else
                    {
                        $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x1_double'];
                    }
                }
                if (!empty($betMoney['x1_Super_Double']))
                {
                    if ($x1Num>9)
                    {
                        $money = $money + ($agentInfo['nnbets_fee']['SuperDouble'] - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x1_Super_Double'] * 10;
                    }
                    elseif ($x1Num>0 && $x1Num<10)
                    {
                        $money = $money + ($agentInfo['nnbets_fee']['SuperDouble'] - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x1_Super_Double'] * $x1Num;
                    }
                    else
                    {
                        $money = $money + ($agentInfo['nnbets_fee']['SuperDouble'] - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x1_Super_Double'];
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
                    if ($x2Num>9)
                    {
                        $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x2_double'] * 3;
                    }
                    elseif ($x2Num>6 && $x2Num<10)
                    {
                        $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x2_double'] * 2;
                    }
                    else
                    {
                        $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x2_double'];
                    }
                }
                if (!empty($betMoney['x2_Super_Double']))
                {
                    if ($x2Num>9)
                    {
                        $money = $money + ($agentInfo['nnbets_fee']['SuperDouble'] - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x2_Super_Double'] * 10;
                    }
                    elseif ($x2Num>0 && $x2Num<10)
                    {
                        $money = $money + ($agentInfo['nnbets_fee']['SuperDouble'] - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x2_Super_Double'] * $x2Num;
                    }
                    else
                    {
                        $money = $money + ($agentInfo['nnbets_fee']['SuperDouble'] - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x2_Super_Double'];
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
                    if ($x3Num>9)
                    {
                        $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x3_double'] * 3;
                    }
                    elseif ($x3Num>6 && $x3Num<10)
                    {
                        $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x3_double'] * 2;
                    }
                    else
                    {
                        $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x3_double'];
                    }
                }
                if (!empty($betMoney['x3_Super_Double']))
                {
                    if ($x3Num>9)
                    {
                        $money = $money + ($agentInfo['nnbets_fee']['SuperDouble'] - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x3_Super_Double'] * 10;
                    }
                    elseif ($x3Num>0 && $x3Num<10)
                    {
                        $money = $money + ($agentInfo['nnbets_fee']['SuperDouble'] - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x3_Super_Double'] * $x3Num;
                    }
                    else
                    {
                        $money = $money + ($agentInfo['nnbets_fee']['SuperDouble'] - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x3_Super_Double'];
                    }
                }
            }
        }
        elseif ($order['game_type']==4)
        {
            $winner = json_decode($gameInfo['winner'],true);
            if ($winner['x1result']=="win")
            {
                $x1Num = $this->sConvertNumbers($winner['x1num']);
                if (!empty($betMoney['x1_equal']))
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Equal'] - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x1_equal'];
                }
                if (!empty($betMoney['x1_double']))
                {
                    if ($x1Num>9)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x1_double'] * 3;
                    }
                    elseif ($x1Num>6 && $x1Num<10)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x1_double'] * 2;
                    }
                    else
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x1_double'];
                    }
                }
                if (!empty($betMoney['x1_Super_Double']))
                {
                    if ($x1Num>9)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x1_Super_Double'] * 10;
                    }
                    elseif ($x1Num>0 && $x1Num<10)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x1_Super_Double'] * $x1Num;
                    }
                    else
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x1_Super_Double'];
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
                    if ($x2Num>9)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x2_double'] * 3;
                    }
                    elseif ($x2Num>6 && $x2Num<10)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x2_double'] * 2;
                    }
                    else
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x2_double'];
                    }
                }
                if (!empty($betMoney['x2_Super_Double']))
                {
                    if ($x2Num>9)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x2_Super_Double'] * 10;
                    }
                    elseif ($x2Num>0 && $x2Num<10)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x2_Super_Double'] * $x2Num;
                    }
                    else
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x2_Super_Double'];
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
                    if ($x3Num>9)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x3_double'] * 3;
                    }
                    elseif ($x3Num>6 && $x3Num<10)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x3_double'] * 2;
                    }
                    else
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x3_double'];
                    }
                }
                if (!empty($betMoney['x3_Super_Double']))
                {
                    if ($x3Num>9)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x3_Super_Double'] * 10;
                    }
                    elseif ($x3Num>0 && $x3Num<10)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x3_Super_Double'] * $x3Num;
                    }
                    else
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x3_Super_Double'];
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
                    if ($x4Num>9)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x4_double'] * 3;
                    }
                    elseif ($x4Num>6 && $x4Num<10)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x4_double'] * 2;
                    }
                    else
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x4_double'];
                    }
                }
                if (!empty($betMoney['x4_Super_Double']))
                {
                    if ($x4Num>9)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x4_Super_Double'] * 10;
                    }
                    elseif ($x4Num>0 && $x4Num<10)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x4_Super_Double'] * $x4Num;
                    }
                    else
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x4_Super_Double'];
                    }
                }
            }
            if ($winner['x5result']=="win")
            {
                $x5Num = $this->sConvertNumbers($winner['x5num']);
                if (!empty($betMoney['x4_equal']))
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Equal'] - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x5_equal'];
                }
                if (!empty($betMoney['x4_double']))
                {
                    if ($x5Num>9)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x5_double'] * 3;
                    }
                    elseif ($x5Num>6 && $x5Num<10)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x5_double'] * 2;
                    }
                    else
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x5_double'];
                    }
                }
                if (!empty($betMoney['x5_Super_Double']))
                {
                    if ($x5Num>9)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x5_Super_Double'] * 10;
                    }
                    elseif ($x5Num>0 && $x5Num<10)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x5_Super_Double'] * $x5Num;
                    }
                    else
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x5_Super_Double'];
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
                    if ($x6Num>9)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x6_double'] * 3;
                    }
                    elseif ($x6Num>6 && $x6Num<10)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x6_double'] * 2;
                    }
                    else
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x6_double'];
                    }
                }
                if (!empty($betMoney['x4_Super_Double']))
                {
                    if ($x6Num>9)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x6_Super_Double'] * 10;
                    }
                    elseif ($x6Num>0 && $x6Num<10)
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x6_Super_Double'] * $x6Num;
                    }
                    else
                    {
                        $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x6_Super_Double'];
                    }
                }
            }
        }
        else
        {
            $winner = json_decode($gameInfo['winner'],true);
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
    public function getAgentInfoByAgentId($agentId)
    {
        $info = $agentId?Agent::find($agentId):[];
        $info['bjlbets_fee']=json_decode($info['bjlbets_fee'],true);
        $info['lhbets_fee']=json_decode($info['lhbets_fee'],true);
        $info['nnlbets_fee']=json_decode($info['nnlbets_fee'],true);
        $info['sglbets_fee']=json_decode($info['sglbets_fee'],true);
        $info['a89lbets_fee']=json_decode($info['a89lbets_fee'],true);
        return $info;
    }
    //根据游戏单号获取表名
    public function getGameRecordTableNameByRecordSn($recordSn)
    {
        return substr($recordSn,0,8);
    }
    /**
     * 效验查询是否存在今天
     * @param $startDate
     * @param $endDate
     * @return bool
     */
    public function checkIsToDay($startDate,$endDate)
    {
        $bool = false;
        $data = $this->getDateTimePeriodByBeginAndEnd($startDate,$endDate);
        foreach ($data as $key)
        {
            if ($key==date('Ymd',time()))
            {
                $bool = true;
                break;
            }
        }
        return $bool;
    }

    /**
     * 总洗码
     * @param $data
     * @return float|int|void
     */
    public function getSumBetMoney($data)
    {
        $money = 0;
        foreach ($data as $key=>$datum)
        {
            $betMoney = json_decode($datum['bet_money'],true);
            if ($datum['game_type']==1 || $datum['game_type']==2){
                $money = $money + array_sum($betMoney);
            }
            else if ($datum['game_type']==3)
            {
                $money = $money + $this->getNiuNiuBetMoney($betMoney);
            }
            else if ($datum['game_type']==4)
            {
                $money = $money + $this->getSanGongBetMoney($betMoney);
            }
            else if ($datum['game_type']==5)
            {
                $money = $money + $this->getA89BetMoney($betMoney);
            }
        }
        return $money;
    }

    /**
     * 获取有效下注
     * @param $data
     * @return float|int|void
     */
    public function getSumMoney($data)
    {
        $money = 0;
        foreach ($data as $key=>$datum)
        {
            if ($datum['status']!=1)
            {
                continue;
            }
            $betMoney = json_decode($datum['bet_money'],true);
            if ($datum['game_type']==1 || $datum['game_type']==2){
                $money = $money + array_sum($betMoney);
            }
            else if ($datum['game_type']==3)
            {
                $money = $money + $this->getNiuNiuBetMoney($betMoney);
            }
            else if ($data['game_type']==4)
            {
                $money = $money + $this->getSanGongBetMoney($betMoney);
            }
            else if ($data['game_type']==5)
            {
                $money = $money + $this->getA89BetMoney($betMoney);
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
     * 计算牛牛下注总金额
     * @param $data
     * @return int
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
     * 获取时间数组
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
}