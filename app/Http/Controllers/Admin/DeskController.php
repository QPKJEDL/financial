<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Desk;
use App\Models\Game;
use App\Models\GameRecord;
use App\Models\Order;
use Illuminate\Http\Request;

class DeskController extends Controller
{
    public function index(Request $request)
    {
        if(true == $request->has('begin')){
            $tableName = date('Ymd',strtotime($request->input('begin')));
        }else{
            $tableName = date('Ymd',strtotime('-1day'));
        }
        $list = Order::getOrderDataByTableName($tableName);
        $map = array();
        $data = Desk::where($map)->paginate(10)->appends($request->all());
        foreach($data as $key=>$value)
        {
            $data[$key]['time']=$tableName;
            if($value['game_id']==1){//百家乐
                $data[$key]['money'] = $this->getBaccaratMoney($value['id'],$value['game_id'],$tableName);
                $data[$key]['betMoney']=$this->getCalculationMoney($data[$key]['id'],$tableName);
            }else if($value['game_id']==2){//龙虎
                $data[$key]['betMoney']=$this->getCalculationMoney($data[$key]['id'],$tableName);
                $data[$key]['money'] = $this->getDragonTieTigerMoney($value['id'],$value['game_id'],$tableName);
            }else if($value['game_id']==3){//牛牛
                $data[$key]['betMoney'] = $this->getNiuNiuMoney($value['id'],$value['game_id'],$tableName);
                $data[$key]['money']=$value['betMoney'];
            }else if($value['game_id']==4){//三公
                $data[$key]['betMoney']=$this->getSanGongMoney($value['id'],$value['game_id'],$tableName);
                $data[$key]['money']=$value['betMoney'];
            }else if($value['game_id']==5){//A89

            }
            $data[$key]['game_id']=Game::getGameNameByGameId($value['game_id']);
            $data[$key]['winAndErr']=$this->getWinAndErrMoney($data[$key]['id'],$tableName);
        }
        //dump($this->getDragonTieTigerMoney(16,2,"20200602"));
        $min=config('admin.min_date');
        return view('desk.list',['list'=>$data,'input'=>$request->all(),'min'=>$min]);
    }

    /**
     * 获取龙虎台桌的总下注金额
     * @param $deskId 台桌id
     * @param $gameId 游戏类型
     * @param $tableName 表名
     * @return float|int|mixed
     */
    public function getDragonTieTigerMoney($deskId,$gameId,$tableName){
        $money = 0;
        $order = new Order();
        $order->setTable('order_'.$tableName);
        $data = $order->where(['desk_id'=>$deskId,'game_type'=>$gameId,'status'=>1])->get();
        foreach ($data as $key=>$datum){
            //获取游戏记录表表名
            $recordTName = $this->getGameRecordTableName($datum['record_sn']);
            //获取游戏详情
            $recordInfo = GameRecord::getGameRecordInfo($datum['record_sn'],$recordTName);
            //判断游戏结果
            if ($recordInfo['winner']!=1){//结果不为和
                $money = $money + array_sum(json_decode($datum['bet_money'],true));
            }else{
                //如果这把游戏结果为和
                $betMoney = json_decode($datum['bet_money'],true);
                $money = $money + $betMoney['tie'];
            }
        }
        return $money;
    }

    /**
     * 获取百家乐台桌的总下注金额
     * @param $deskId 台桌id
     * @param $gameId 游戏id
     * @param $tableName 表名
     * @return float|int|mixed
     */
    public function getBaccaratMoney($deskId,$gameId,$tableName){
        $money = 0;
        $order = new Order();
        $order->setTable('order_'.$tableName);
        $data = $order->where(['desk_id'=>$deskId,'game_type'=>$gameId,'status'=>1])->get();
        foreach ($data as $key=>$datum){
            //获取游戏记录表名
            $recordTName = $this->getGameRecordTableName($datum['record_sn']);
            //获取游戏详情
            $recordInfo = GameRecord::getGameRecordInfo($datum['record_sn'],$recordTName);
            //把游戏结果转成数组
            $winner = json_decode($recordInfo['winner'],true);
            if ($winner['game']!=1){//游戏结果不为和
                $money = $money + array_sum(json_decode($datum['bet_money'],true));
            }else{
                $betMoney = json_decode($datum['bet_money'],true);
                $money = $money + $betMoney['tie'];
            }
        }
        return $money;
    }

    /**
     * 获取牛牛全部下注金额
     * @param $deskId
     * @param $gameId
     * @param $tableName
     * @return int
     */
    public function getNiuNiuMoney($deskId,$gameId,$tableName){
        $money = 0;
        $order = new Order();
        $order->setTable('order_'.$tableName);
        $data = $order->where(['desk_id'=>$deskId,'game_type'=>$gameId,'status'=>1])->get();
        foreach ($data as $key=>$datum){
            //获取游戏记录表名
            $recordTName = $this->getGameRecordTableName($datum['record_sn']);
            //获取游戏详情
            $recordInfo = GameRecord::getGameRecordInfo($datum['record_sn'],$recordTName);
            //把游戏结果转成数组
            $winner = json_decode($recordInfo['winner'],true);
            //{"x3_Super_Double":2000,"x3_double":2000} {"x3_equal":5000}
            if (!empty($winner['x1_Super_Double'])){
                $money = $money + ($winner['x1_Super_Double']*3);
            }
            if (!empty($winner['x2_Super_Double'])){
                $money = $money + ($winner['x2_Super_Double']*3);
            }
            if (!empty($winner['x3_Super_Double'])){
                $money = $money + ($winner['x3_Super_Double']*3);
            }
            if (!empty($winner['x1_double'])){
                $money = $money + ($winner['x1_double']*2);
            }
            if (!empty($winner['x2_double'])){
                $money = $money + ($winner['x2_double']*2);
            }
            if (!empty($winner['x3_double'])){
                $money = $money + ($winner['x3_double']*2);
            }
            if (!empty($winner['x1_equal'])){
                $money = $money + $winner['x1_equal'];
            }
            if (!empty($winner['x2_equal'])){
                $money = $money + $winner['x2_equal'];
            }
            if (!empty($winner['x3_equal'])){
                $money = $money + $winner['x3_equal'];
            }
        }
        return $money;
    }

    /**
     * 获取三公总下注
     * @param $deskId
     * @param $gameId
     * @param $tableName
     * @return int
     */
    public function getSanGongMoney($deskId,$gameId,$tableName)
    {
        $money = 0;
        $order = new Order();
        $order->setTable('order_'.$tableName);
        $data = $order->where(['desk_id'=>$deskId,'game_type'=>$gameId,'status'=>1])->get();
        foreach ($data as $key=>$value)
        {
            //获取游戏记录表名
            $recordTName = $this->getGameRecordTableName($value['record_sn']);
            //获取游戏详情
            $recordInfo = GameRecord::getGameRecordInfo($value['record_sn'],$recordTName);
            //把游戏结果转成数组
            $winner = json_decode($recordInfo['winner'],true);
            if (!empty($winner['x1_Super_Double'])){
                $money = $money + $winner['x1_Super_Double']*10;
            }
            if (!empty($winner['x2_Super_Double'])){
                $money = $money + $winner['x2_Super_Double']*10;
            }
            if (!empty($winner['x3_Super_Double'])){
                $money = $money + $winner['x3_Super_Double']*10;
            }
            if (!empty($winner['x4_Super_Double'])){
                $money = $money + $winner['x4_Super_Double']*10;
            }
            if (!empty($winner['x5_Super_Double'])){
                $money = $money + $winner['x5_Super_Double']*10;
            }
            if (!empty($winner['x6_Super_Double'])){
                $money = $money + $winner['x6_Super_Double']*10;
            }
            if (!empty($winner['x1_double'])){
                $money = $money + $winner['x1_double'] * 3;
            }
            if (!empty($winner['x2_double'])){
                $money = $money + $winner['x2_double'] * 3;
            }
            if (!empty($winner['x3_double'])){
                $money = $money + $winner['x3_double'] * 3;
            }
            if (!empty($winner['x4_double'])){
                $money = $money + $winner['x4_double'] * 3;
            }
            if (!empty($winner['x5_double'])){
                $money = $money + $winner['x5_double'] * 3;
            }
            if (!empty($winner['x6_double'])){
                $money = $money + $winner['x6_double'] * 3;
            }
            if (!empty($winner['x1_equal'])){
                $money = $money + $winner['x1_equal'];
            }
            if (!empty($winner['x2_equal'])){
                $money = $money + $winner['x2_equal'];
            }
            if (!empty($winner['x3_equal'])){
                $money = $money + $winner['x3_equal'];
            }
            if (!empty($winner['x4_equal'])){
                $money = $money + $winner['x4_equal'];
            }
            if (!empty($winner['x5_equal'])){
                $money = $money + $winner['x5_equal'];
            }
            if (!empty($winner['x6_equal'])){
                $money = $money + $winner['x6_equal'];
            }
        }
        return $money;
    }

   /**
     * 解析order表中得bet_money获取总金额
     * @param $deskId
     * @param $tableName
     * @return float|int
     */
    public function getCalculationMoney($deskId,$tableName)
    {
        $sum=0;
        $order = new Order();
        $order->setTable('order_'.$tableName);
        $data = $order->where('desk_id','=',$deskId)->where('status','=',1)->get();
        foreach ($data as $key=>$datum){
            $bet = json_decode($datum['bet_money'],true);
            $sum = $sum + array_sum($bet);
        }
        return $sum;
    }

    /**
     * 根据deskId获取到每桌得下注总金额
     */
    public function getDeskAllBetMoney($deskId,$data)
    {
        $sum=0;
        foreach($data as $key=>$value){
            if($data[$key]['desk_id']==$deskId){
                $sum += $this->getCalculationMoney($data[$key]);
            }
        }
        return $sum;
    }

    /**
     * 获取游戏输赢情况
     * @param $deskId
     * @param $tableName
     * @return int|mixed
     */
    public function getWinAndErrMoney($deskId,$tableName)
    {
        $order = new Order();
        $order->setTable('order_'.$tableName);
        return $order->where('desk_id','=',$deskId)->sum('get_money');
    }

    /**
     * 获取游戏记录表名
     * @param $str 游戏记录编号
     * @return false|string
     */
    public function getGameRecordTableName($str){
        return substr($str,0,8);
    }
}