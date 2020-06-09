<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Desk;
use App\Models\HqUser;
use App\Models\Order;
use Illuminate\Http\Request;

class UserDayEndController extends Controller
{
    public function index(Request $request){
        $map = array();
        if(true==$request->has('begin')){
            $tableName = date('Ymd',strtotime($request->input('begin')));
        }else{
            $tableName = date('Ymd',time());
        }
        if (true==$request->has('account')){
            $info = HqUser::where('account','=',$request->input('account'))->first();
            $map['user.user_id']=$info['user_id'];
        }
        $order = new Order();
        $order->setTable('order_'.$tableName);
        $sql = $order->leftJoin('user','user.user_id','=','order_'.$tableName.'.user_id')
            ->leftJoin('user_account','user_account.user_id','=','user.user_id')
            ->select('order_'.$tableName.'.*','user.account','user.nickname','user.fee','user_account.balance')
            ->where($map);
        $data = $sql->groupBy('order_'.$tableName.'.user_id')->paginate(10)->appends($request->all());
        foreach ($data as $key=>$value){
            $data[$key]['betCount']=$this->getBetCountByOrderAndUserId($order,$value['user_id']);
            $data[$key]['betMoney']=$this->getBetMoneyByOrderAndUserId($order,$value['user_id']);
            $data[$key]['betCode']=$this->getBetCodeByOrderAndUserId($order,$value['user_id']);
            $data[$key]['money']=$this->getWinMoneyAndLoseMoney($order,$value['user_id']);
            $data[$key]['maid']=$this->getCodeMaidByUserId($order,$value['user_id']);
        }
        return view('userDay.list',['list'=>$data,'min'=>config('admin.min_date'),'input'=>$request->all()]);
    }

    /**
     * 根据userId获取当前用户下注的次数
     * @param $order
     * @param $userId
     * @return mixed
     */
    public function getBetCountByOrderAndUserId($order,$userId){
        return $order->where('user_id',$userId)->count();
    }

    /**
     * 根据userId获取下注总金额
     * @param $order
     * @param $userId
     * @return float|int
     */
    public function getBetMoneyByOrderAndUserId($order,$userId){
        $money = 0;
        $data = $order->where('user_id','=',$userId)->get()->toArray();
        foreach ($data as $key=>$value){
            $bet = json_decode($value['bet_money'],true);
            $money = $money + array_sum($bet);
        }
        return $money;
    }

    /**
     * 根据userId获取当前用户的总下注金额
     * @param $order
     * @param $userId
     * @return float|int
     */
    public function getBetCodeByOrderAndUserId($order,$userId){
        $money = 0;
        $map = array();
        $map['user_id']=$userId;
        $map['status']=1;
        $data = $order->where($map)->get()->toArray();
        foreach ($data as $key=>$datum){
            $bet = json_decode($datum['bet_money'],true);
            $money = $money + array_sum($bet);
        }
        return $money;
    }

    /**
     * 根据userId获取派彩所赢
     * @param $order
     * @param $userId
     * @return mixed
     */
    public function getWinMoneyAndLoseMoney($order,$userId){
        return $order->where(['user_id'=>$userId,'status'=>1])->sum('get_money');
    }

    public function getCodeMaidByUserId($order,$userId){
        $money = 0;
        $map = array();
        $map['user_id']=$userId;
        $map['status']=1;
        $user = $userId?HqUser::find($userId):[];
        $user['fee']=json_decode($user['fee'],true);
        $data = $order->where($map)->get()->toArray();
        foreach ($data as $key=>$datum){
            $bet = json_decode($datum['bet_money'],true);
            //$money = $money + array_sum($bet);
            //{"baccarat":"0.9","dragonTiger":"0.9","niuniu":"0.9","sangong":"0.9","A89":"0.9"}
            if ($datum['game_type']==1){//百家乐
                $money = $money + (array_sum($bet) * ($user['fee']['baccarat']/100));
            }else if($datum['game_type']==2){//龙虎
                $money = $money + (array_sum($bet) * ($user['fee']['dragonTiger']/100));
            }else if($datum['game_type']==3){//牛牛
                $money = $money + (array_sum($bet) * ($user['fee']['niuniu']/100));
            }else if($datum['game_type']==4){//三公
                $money = $money + (array_sum($bet) * ($user['fee']['sangong']/100));
            }else if($datum['game_type']==5){//A89
                $money = $money + (array_sum($bet) * ($user['fee']['A89']/100));
            }
        }
        return $money;
    }
}