<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billflow;
use App\Models\Desk;
use App\Models\Game;
use App\Models\GameRecord;
use App\Models\HqUser;
use App\Models\Maintain;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderController extends Controller
{
    /**
     * 数据列表
     */
    public function index(Request $request){
        if (true==$request->has('pageNum')){
            $curr = $request->input('pageNum');
        }else{
            $curr =1;
        }
        //获取开始时间和结束时间
        if (true==$request->has('begin')){
            $begin = strtotime($request->input('begin'));
            $startDate = $request->input('begin');
            if (true==$request->has('end')){
                $endTime = strtotime($request->input('end'));
                $endDate = $request->input('end');
                $request->offsetSet('end',$request->input('end'));
            }else{
                $endTime = time();
                $endDate = date('Y-m-d',$endTime);
                $request->offsetSet('end',date('Y-m-d H:i:s',$endTime));
            }
        }else{
            //获取上次维护完成时间
            $start = Maintain::getAtLastOutDate();
            $end = Maintain::getAtLastMaintainDate();
            if ($start['create_time'] > $end['create_time']){   //如果最后一次维护完成时间大于最后一个得维护开始时间 那么默认找昨天得数据
                //获取昨天的开始和结束的时间戳
                $begin = $this->getYesterdayBeginTime();
                $endTime = $this->getYesterdayEndTime($begin);
                $startDate = date('Y-m-d',$begin);
                $endDate = date('Y-m-d',$endTime);
                $request->offsetSet('begin',date('Y-m-d H:i:s',$begin));
                $request->offsetSet('end',date('Y-m-d H:i:s',$endTime));
            }else{
                $begin = $start['create_time'];
                $endTime = $end['create_time'];
                $startDate = date('Y-m-d',$start['create_time']);
                $endDate = date('Y-m-d',$end['create_time']);
                $request->offsetSet('begin',date('Y-m-d H:i:s',$begin));
                $request->offsetSet('end',date('Y-m-d H:i:s',$endTime));
            }
        }
        //获取到开始时间和结束时间的时间段数组
        $dateArr = $this->getDateTimePeriodByBeginAndEnd($startDate,$endDate);
        $sql = '';
        for ($i=0;$i<count($dateArr);$i++)
        {
            if (Schema::hasTable('order_'.$dateArr[$i])){
                if ($sql==""){
                    $sql = "select * from hq_order_".$dateArr[$i];
                }else{
                    $sql = $sql.' union all select * from hq_order_'.$dateArr[$i];
                }
                if (true==$request->has('desk_id')){
                    $sql = $sql.' and desk_id ='.$request->input('desk_id');
                }
                if (true==$request->has('type')){
                    $sql = $sql.' and game_type='.$request->input('type');
                }
                if (true==$request->has('status')){
                    $sql = $sql.' and status='.$request->input('status');
                }
                if (true==$request->has('boot_num')){
                    $sql = $sql.' and boot_num='.$request->input('boot_num');
                }
                if (true==$request->has('pave_num')){
                    $sql = $sql.' and pave_num='.$request->input('pave_num');
                }
                if (true==$request->has('orderSn')){
                    $sql = $sql.' and order_sn='.$request->input('orderSn');
                }
            }
        }
        $dataSql = 'select * from ('.$sql.') t where t.creatime between '.$begin.' and '.$endTime.' limit '.(($curr-1) * 10).',10';
        $countSql = 'select * from ('.$sql.') t where t.creatime between '.$begin.' and '.$endTime;
        $count = DB::select($countSql);
        $data = DB::select($dataSql);
        foreach ($data as $key=>$value){
            $data[$key]->user = HqUser::getUserInfoByUserId($value->user_id);
            $data[$key]->money = $this->getSumBetMoney($value);
            //获取表名
            $tableName = $this->getGameRecordTableNameByRecordSn($value->record_sn);
            $winner = $this->getGameRecordInfo($tableName,$value->record_sn);
            $data[$key]->bill=Billflow::getBillflowByOrderSn($value->order_sn,$tableName);
            if ($data[$key]->game_type==1){
                $data[$key]->result=$this->getBaccaratParseJson($winner);
                $data[$key]->bet_money=$this->getBaccaratBetMoney($value->bet_money);
            }else if($data[$key]->game_type==2){
                $data[$key]->result=$this->getDragonTigerJson($winner);
                $data[$key]->bet_money=$this->getDragonTieTiger($value->bet_money);
            }else if($data[$key]->game_type==3){
                $data[$key]->result=$this->getFullParseJson($winner);
                $data[$key]->bet_money=$this->getNiuNiuBetMoney($value->bet_money);
            }else if($data[$key]->game_type==4){
                $data[$key]->result = $this->getSanGongResult($winner);
                $data[$key]->bet_money=$this->getSanGongMoney($value->bet_money);
            }else if($data[$key]->game_type==5){
                $data[$key]->result=$this->getA89Result($winner);
                $data[$key]->bet_money=$this->getA89BetMoney($value->bet_money);
            }
            $data[$key]->creatime = date('Y-m-d H:i:s',$value->creatime);
        }
        $min = config('admin.min_date');
        return view('order.list',['list'=>$data,'desk'=>$this->getDeskList(),'curr'=>$curr,'game'=>Game::getGameByType(),'input'=>$request->all(),'min'=>$min,'pages'=>ceil(count($count)/10)]);
    }
    public function getOrderListByUserId($id,$begin,$end,Request $request){
        if (true==$request->has('pageNum')){
            $curr = $request->input('pageNum');
        }else{
            $curr =1;
        }
        $dateArr = $this->getDateTimePeriodByBeginAndEnd($begin,$end);
        $sql ='';
        for ($i=0;$i<count($dateArr);$i++)
        {
            if (Schema::hasTable('order_'.$dateArr[$i])){
                if ($sql==""){
                    $sql = "select * from hq_order_".$dateArr[$i].' where user_id='.$id;
                }else{
                    $sql = $sql.' union all select * from hq_order_'.$dateArr[$i].' where user_id='.$id;
                }
                if (true==$request->has('desk_id')){
                    $sql = $sql.' and desk_id ='.$request->input('desk_id');
                }
                if (true==$request->has('type')){
                    $sql = $sql.' and game_type='.$request->input('type');
                }
                if (true==$request->has('status')){
                    $sql = $sql.' and status='.$request->input('status');
                }
                if (true==$request->has('boot_num')){
                    $sql = $sql.' and boot_num='.$request->input('boot_num');
                }
                if (true==$request->has('pave_num')){
                    $sql = $sql.' and pave_num='.$request->input('pave_num');
                }
                if (true==$request->has('orderSn')){
                    $sql = $sql.' and order_sn='.$request->input('orderSn');
                }
            }
        }
        if ($sql!='' || $sql!=null){
            $dataSql = 'select * from ('.$sql.') t where t.creatime between '.strtotime($begin).' and '.strtotime($end).' limit '.(($curr-1) * 10).',10';
            $countSql = 'select * from ('.$sql.') t where t.creatime between '.strtotime($begin).' and '.strtotime($end);
            $count = DB::select($countSql);
            $data = DB::select($dataSql);
            foreach ($data as $key=>$value){
                $data[$key]->user = HqUser::getUserInfoByUserId($value->user_id);
                $data[$key]->money = $this->getSumBetMoney($value);
                //获取表名
                $tableName = $this->getGameRecordTableNameByRecordSn($value->record_sn);
                $winner = $this->getGameRecordInfo($tableName,$value->record_sn);
                $data[$key]->bill=Billflow::getBillflowByOrderSn($value->order_sn,$tableName);
                if ($data[$key]->game_type==1){
                    $data[$key]->result=$this->getBaccaratParseJson($winner);
                    $data[$key]->bet_money=$this->getBaccaratBetMoney($value->bet_money);
                }else if($data[$key]->game_type==2){
                    $data[$key]->result=$this->getDragonTigerJson($winner);
                    $data[$key]->bet_money=$this->getDragonTieTiger($value->bet_money);
                }else if($data[$key]->game_type==3){
                    $data[$key]->result=$this->getFullParseJson($winner);
                    $data[$key]->bet_money=$this->getNiuNiuBetMoney($value->bet_money);
                }else if($data[$key]->game_type==4){
                    $data[$key]->result = $this->getSanGongResult($winner);
                    $data[$key]->bet_money=$this->getSanGongMoney($value->bet_money);
                }else if($data[$key]->game_type==5){
                    $data[$key]->result=$this->getA89Result($winner);
                    $data[$key]->bet_money=$this->getA89BetMoney($value->bet_money);
                }
                $data[$key]->creatime = date('Y-m-d H:i:s',$value->creatime);
            }
            return view('order.list',['list'=>$data,'desk'=>$this->getDeskList(),'curr'=>$curr,'game'=>Game::getGameByType(),'input'=>$request->all(),'min'=>config('admin.min_date'),'pages'=>ceil(count($count)/10)]);
        }else{
            $data=array();
            return view('order.list',['list'=>$data,'desk'=>$this->getDeskList(),'curr'=>$curr,'game'=>Game::getGameByType(),'input'=>$request->all(),'min'=>config('admin.min_date'),'pages'=>1]);
        }
    }

    /**
     * 获取所有台桌
     */
    public function getDeskList(){
        $data = Desk::get();
        return $data;
    }

    //根据游戏单号获取表名
    public function getGameRecordTableNameByRecordSn($recordSn)
    {
        return substr($recordSn,0,8);
    }

    /**
     * 根据表名获取游戏记录
     */
    public function getGameRecordInfo($tableName,$recordSn)
    {
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $data = $game->where('record_sn','=',$recordSn)->first();
        return $data['winner'];
    }

    /**
     * 解析百家乐json数据
     */
    public function getBaccaratParseJson($jsonStr)
    {
        $arr = array();
        //json格式数据
        //{"game":4,"playerPair":5,"bankerPair":2}
        $data = json_decode($jsonStr, true);
        if ($data['game'] == 1) {
            $arr['game'] = "和";
        } else if ($data['game'] == 4) {
            $arr['game'] = "闲";
        } else {
            $arr['game'] = "庄";
        }
        if (empty($data['playerPair'])) {
            $arr['playerPair'] = "";
        } else {
            $arr['playerPair'] = "闲对";
        }
        if (empty($data['bankerPair'])) {
            $arr['bankerPair'] = "";
        } else {
            $arr['bankerPair'] = "庄对";
        }
        return $arr;
    }

    /**
     * 龙虎
     */
    public function getDragonTigerJson($winner)
    {
        if ($winner == 7) {
            $result = "龙";
        } else if ($winner == 4) {
            $result = "虎";
        } else {
            $result = "和";
        }
        return $result;
    }
    /**
     * 牛牛
     */
    public function getFullParseJson($jsonStr)
    {
        $arr = array();
        //解析json
        //{"bankernum":"牛1","x1num":"牛牛","x1result":"win","x2num":"牛2","x2result":"win","x3num":"牛3","x3result":"win"}
        $data = json_decode($jsonStr, true);
        //先判断庄是不是通吃
        if ($data['x1result'] == "" && $data['x2result'] == "" && $data['x3result'] == "") {
            $arr['bankernum'] = "庄";
        } else {
            $arr['bankernum'] = "";
        }
        if ($data['x1result'] == "win") {
            $arr['x1result'] = "闲1";
        } else {
            $arr['x1result'] = "";
        }
        if ($data['x2result'] == "win") {
            $arr['x2result'] = "闲2";
        } else {
            $arr['x2result'] = "";
        }
        if ($data['x3result'] == "win") {
            $arr['x3result'] = "闲3";
        } else {
            $arr['x4result'] = "";
        }
        return $arr;
    }

    /**
     * 三公
     * @param $jsonStr
     * @return array
     */
    public function getSanGongResult($jsonStr){
        $arr = array();
        //解析json
        $data = json_decode($jsonStr,true);
        //{"bankernum":"9点","x1num":"小三公","x1result":"win","x2num":"混三公","x2result":"win","x3num":"大三公","x3result":"win","x4num":"0点","x4result":"", "x5num":"1点", "x5result":"", "x6num":"9点", "x6result":""}
        //判断庄是否通吃
        if ($data['x1result']=='' && $data['x2result']=="" && $data['x3result']=="" && $data['x4result']=="" && $data['x5result']=="" && $data['x6result']==""){
            $arr['bankernum'] = "庄";
        }else{
            $arr['bankernum'] = "";
        }
        if ($data['x1result'] == "win") {
            $arr['x1result'] = "闲1";
        } else {
            $arr['x1result'] = "";
        }
        if ($data['x2result'] == "win") {
            $arr['x2result'] = "闲2";
        } else {
            $arr['x2result'] = "";
        }
        if ($data['x3result'] == "win") {
            $arr['x3result'] = "闲3";
        } else {
            $arr['x3result'] = "";
        }
        if ($data['x4result'] == "win") {
            $arr['x4result'] = "闲4";
        } else {
            $arr['x4result'] = "";
        }
        if ($data['x5result'] == "win") {
            $arr['x5result'] = "闲5";
        } else {
            $arr['x5result'] = "";
        }
        if ($data['x6result'] == "win") {
            $arr['x6result'] = "闲6";
        } else {
            $arr['x6result'] = "";
        }
        return $arr;
    }
    /**
     * A89
     */
    public function getA89Result($jsonStr){
        $data = json_decode($jsonStr,true);
        //{"BankerNum":"5点","FanNum":"0点","Fanresult":"","ShunNum":"8点","Shunresult":"win","TianNum":"5点","Tianresult":"win"}
        //判断庄是否通知
        $arr = array();
        if ($data['Fanresult']=="" && $data['Shunresult']=="" && $data['Tianresult']==""){
            $arr['bankernum'] = "庄";
        }else{
            $arr['bankernum'] = "";
        }
        if ($data['Fanresult'] == "win") {
            $arr['Fanresult'] = "反门";
        } else {
            $arr['Fanresult'] = "";
        }
        if ($data['Shunresult'] == "win") {
            $arr['Shunresult'] = "顺门";
        } else {
            $arr['Shunresult'] = "";
        }
        if ($data['Tianresult']=="win"){
            $arr['Tianresult'] = "天门";
        }else{
            $arr['Tianresult'] = "";
        }
        return $arr;
    }
    /**
     * 百家乐
     */
    public function getBaccaratBetMoney($betMoney){
        $data = json_decode($betMoney,true);
        $str = '';
        foreach ($data as $key=>$value) {
            if ($data['banker']>0) {
                $str = "庄".$data['banker'];
            }
            if ($data['bankerPair']>0) {
                $str = $str."庄对".$data['bankerPair'];
            }
            if ($data['player']>0) {
                $str = $str."闲".$data['player'];
            }
            if ($data['playerPair']>0) {
                $str = $str."庄对".$data['playerPair'];
            }
            if ($data['tie']>0) {
                $str = $str."和".$data['tie'];
            }
        }
        return $str;
    }

    public function getMoney($betMoney)
    {
        $sum = 0;
        $data = json_decode($betMoney,true);
        $data = json_decode($betMoney,true);
        foreach($data as $key=>$value){
            $sum += $data[$key];
        }
    }

    public function getDragonTieTiger($betMoney)
    {
        $data = json_decode($betMoney,true);
        $str = '';
        foreach($data as $key=>$value){
            if($data['dragon']>0){
                $str = "龙".$data['dragon'];
            }
            if($data['tie']>0){
                $str = $str." 和".$data['tie'];
            }
            if($data['tiger']>0){
                $str = $str." 虎".$data['tiger'];
            }
        }
        return $str;
    }

    /**
     * 牛牛
     */
    public function getNiuNiuBetMoney($betMoney)
    {
        $data = json_decode($betMoney,true);
        $str = "";
        if(!empty($data['x1_equal'])){
            $str = "闲一(平倍)".$data['x1_equal']/100;
        }
        if(!empty($data['x1_double'])){
            $str = $str."闲一(翻倍)".$data['x1_double']/100;
        }
        if(!empty($data['x2_equal'])){
            $str = "闲二（平倍）".$data['x2_equal']/100;
        }
        if(!empty($data['x2_double'])){
            $str = $str."闲二(翻倍)".$data['x2_double']/100;
        }
        if(!empty($data['x3_equal'])){
            $str = "闲三（平倍）".$data['x3_equal']/100;
        }
        if(!empty($data['x3_double'])){
            $str = $str."闲三(翻倍)".$data['x3_double']/100;
        }
        return $str;
    }

    /**
     * 三公
     * @param $betMoney
     * @return string
     */
    public function getSanGongMoney($betMoney)
    {
        $data = json_decode($betMoney,true);
        //{"x1_Super_Double":10000,"x1_double":10000,"x1_equal":10000,"x2_Super_Double":10000,"x2_double":10000,"x2_equal":10000,"x3_Super_Double":10000,"x3_double":10000,"x3_equal":10000,"x4_Super_Double":10000,"x4_double":10000,"x4_equal":10000,"x5_Super_Double":10000,"x5_double":10000,"x5_equal":10000,"x6_Super_Double":10000,"x6_double":10000,"x6_equal":10000}
        $str = "";
        if (!empty($data['x1_Super_Double'])){
            $str = "闲一(超倍)".$data['x1_Super_Double']/100;
        }
        if (!empty($data['x1_double'])){
            $str = $str."闲一(翻倍)".$data['x1_double']/100;
        }
        if (!empty($data['x1_equal'])){
            $str = $str."闲一(平倍)".$data['x1_equal']/100;
        }
        if (!empty($data['x2_Super_Double'])){
            $str = $str."闲二(超倍)".$data['x2_Super_Double']/100;
        }
        if (!empty($data['x2_double'])){
            $str = $str."闲二(翻倍)".$data['x2_double']/100;
        }
        if (!empty($data['x2_equal'])){
            $str = $str.'闲二(平倍)'.$data['x2_equal']/100;
        }
        if (!empty($data['x3_Super_Double'])){
            $str = $str."闲三(超倍)".$data['x3_Super_Double']/100;
        }
        if (!empty($data['x3_double'])){
            $str = $str."闲三(翻倍)".$data['x3_double']/100;
        }
        if (!empty($data['x3_equal'])){
            $str = $str.'闲三(平倍)'.$data['x3_equal']/100;
        }
        if (!empty($data['x4_Super_Double'])){
            $str = $str."闲四(超倍)".$data['x4_Super_Double']/100;
        }
        if (!empty($data['x4_double'])){
            $str = $str."闲四(翻倍)".$data['x4_double']/100;
        }
        if (!empty($data['x4_equal'])){
            $str = $str.'闲四(平倍)'.$data['x4_equal']/100;
        }
        if (!empty($data['x5_Super_Double'])){
            $str = $str."闲五(超倍)".$data['x5_Super_Double']/100;
        }
        if (!empty($data['x5_double'])){
            $str = $str."闲五(翻倍)".$data['x5_double']/100;
        }
        if (!empty($data['x5_equal'])){
            $str = $str.'闲五(平倍)'.$data['x5_equal']/100;
        }
        if (!empty($data['x6_Super_Double'])){
            $str = $str."闲六(超倍)".$data['x6_Super_Double']/100;
        }
        if (!empty($data['x6_double'])){
            $str = $str."闲六(翻倍)".$data['x6_double']/100;
        }
        if (!empty($data['x6_equal'])){
            $str = $str.'闲六(平倍)'.$data['x6_equal']/100;
        }
        return $str;
    }
    /**
     * A89
     * @param $betMoney
     * @return string
     */
    public function getA89BetMoney($betMoney){
        $data = json_decode($betMoney,true);
        //{"ShunMen_Super_Double":10000,"TianMen_Super_Double":10000,"FanMen_Super_Double":10000,"ShunMen_equal":10000,"TianMen_equal":10000,"FanMen_equal":10000}
        $str = "";
        if(!empty($data['ShunMen_Super_Double'])){
            $str = "顺门(超倍)".$data['ShunMen_Super_Double']/100;
        }
        if(!empty($data['TianMen_Super_Double'])){
            $str =$str."天门(超倍)".$data['TianMen_Super_Double']/100;
        }
        if(!empty($data['FanMen_Super_Double'])){
            $str = $str.'反门(超倍)'.$data['FanMen_Super_Double']/100;
        }
        if (!empty($data['ShunMen_equal'])){
            $str = $str.'顺们'.$data['ShunMen_equal']/100;
        }
        if(!empty($data['TianMen_equal'])){
            $str = $str.'天门'.$data['TianMen_equal']/100;
        }
        if(!empty($data['FanMen_equal'])){
            $str = $str.'反门'.$data['FanMen_equal']/100;
        }
        return $str;
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
     * 获取总下注金额
     * @param $data
     * @return float|int|mixed
     */
    public function getSumBetMoney($value){
        $money = 0;
        if ($value->game_type==1){//百家乐
            $bjl = json_decode($value->bet_money,true);
            $money = $money + array_sum($bjl);
        }else if ($value->game_type==2){
            $lh = json_decode($value->bet_money,true);
            $money = $money + array_sum($lh);
        }else if($value->game_type==3){
            $nn = json_decode($value->bet_money,true);
            if (!empty($nn['x1_Super_Double'])){
                $money = $money + $nn['x1_Super_Double'] * 10;
            }
            if (!empty($nn['x2_Super_Double'])){
                $money = $money + $nn['x2_Super_Double'] * 10;
            }
            if (!empty($nn['x3_Super_Double'])){
                $money = $money + $nn['x3_Super_Double'] * 10;
            }
            if (!empty($nn['x1_double'])){
                $money = $money + $nn['x1_double'] * 3;
            }
            if (!empty($nn['x2_double'])){
                $money = $money + $nn['x2_double'] * 3;
            }
            if (!empty($nn['x3_double'])){
                $money = $money + $nn['x3_double'] * 3;
            }
            if (!empty($nn['x1_equal'])){
                $money = $money + $nn['x1_equal'];
            }
            if (!empty($nn['x2_equal'])){
                $money = $money + $nn['x2_equal'];
            }
            if (!empty($nn['x3_equal'])){
                $money = $money + $nn['x3_equal'];
            }
        }else if ($value->game_type==4){
            $sg = json_decode($value->bet_money,true);
            //{"x1_Super_Double":10000,"x1_double":10000,"x1_equal":10000,"x2_Super_Double":10000,"x2_double":10000,"x2_equal":10000,"x3_Super_Double":10000,"x3_double":10000,"x3_equal":10000,"x4_Super_Double":10000,"x4_double":10000,"x4_equal":10000,"x5_Super_Double":10000,"x5_double":10000,"x5_equal":10000,"x6_Super_Double":10000,"x6_double":10000,"x6_equal":10000}
            if (!empty($sg['x1_Super_Double'])){
                $money = $money + $sg['x1_Super_Double'] * 10;
            }
            if (!empty($sg['x2_Super_Double'])){
                $money = $money + $sg['x2_Super_Double'] * 10;
            }
            if (!empty($sg['x3_Super_Double'])){
                $money = $money + $sg['x3_Super_Double'] * 10;
            }
            if (!empty($sg['x4_Super_Double'])){
                $money = $money + $sg['x4_Super_Double'] * 10;
            }
            if (!empty($sg['x5_Super_Double'])){
                $money = $money + $sg['x5_Super_Double'] * 10;
            }
            if (!empty($sg['x6_Super_Double'])){
                $money = $money + $sg['x6_Super_Double'] * 10;
            }
            if (!empty($sg['x1_double'])){
                $money = $money + $sg['x1_double'] * 3;
            }
            if (!empty($sg['x2_double'])){
                $money = $money + $sg['x2_double'] * 3;
            }
            if (!empty($sg['x3_double'])){
                $money = $money + $sg['x3_double'] * 3;
            }
            if (!empty($sg['x4_double'])){
                $money = $money + $sg['x4_double'] * 3;
            }
            if (!empty($sg['x5_double'])){
                $money = $money + $sg['x5_double'] * 3;
            }
            if (!empty($sg['x6_double'])){
                $money = $money + $sg['x6_double'] * 3;
            }
            if (!empty($sg['x1_equal'])){
                $money = $money + $sg['x1_equal'];
            }
            if (!empty($sg['x2_equal'])){
                $money = $money + $sg['x2_equal'];
            }
            if (!empty($sg['x3_equal'])){
                $money = $money + $sg['x3_equal'];
            }
            if (!empty($sg['x4_equal'])){
                $money = $money + $sg['x4_equal'];
            }
            if (!empty($sg['x5_equal'])){
                $money = $money + $sg['x5_equal'];
            }
            if (!empty($sg['x6_equal'])){
                $money = $money + $sg['x6_equal'];
            }
        }else if($value->game_type==5){
            $a89 = json_decode($value->bet_money,true);
            if (!empty($a89['ShunMen_Super_Double'])){
                $money = $money + $a89['ShunMen_Super_Double'] * 10;
            }
            if (!empty($a89['TianMen_Super_Double'])){
                $money = $money + $a89['TianMen_Super_Double'] * 10;
            }
            if (!empty($a89['FanMen_Super_Double'])){
                $money = $money + $a89['FanMen_Super_Double'] * 10;
            }
            if (!empty($a89['ShunMen_equal'])){
                $money = $money + $a89['ShunMen_equal'];
            }
            if (!empty($a89['TianMen_equal'])){
                $money = $money + $a89['TianMen_equal'];
            }
            if (!empty($a89['FanMen_equal'])){
                $money = $money + $a89['FanMen_equal'];
            }
        }

        return $money;
    }
}