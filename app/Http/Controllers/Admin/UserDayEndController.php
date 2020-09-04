<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Desk;
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
                    $orderDataByUserId = $order->select('bet_money','status','game_type')->where('user_id','=',$datum['user_id'])->get();
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
                $a['feeMoney']=0;
                $a['userType']=$datum['user_type'];
                $a['getMoney']=$datum['get_money'];
                $a['reward']=$datum['reward'];
                $data[]=$a;
            }else{
                $index = $arr['index'];
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
                $oData = $order->where('order.user_id','=',$datum['user_id'])->get()->toArray();
                foreach ($oData as $k=>$v)
                {
                    $betMoney = json_decode($v['bet_money'],true);
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
                $info['feeMoney']=0;
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