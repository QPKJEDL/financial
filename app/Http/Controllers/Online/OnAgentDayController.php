<?php


namespace App\Http\Controllers\Online;


use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\GameRecord;
use App\Models\HqUser;
use App\Models\LiveReward;
use App\Models\Maintain;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class OnAgentDayController extends Controller
{
    public function index(Request $request)
    {
        $map = array();
        $map['agent_users.userType']=2;
        $map['agent_users.parent_id']=0;
        if (true==$request->has('account')){
            $map['user.username']=$request->input('account');
        }
        //获取开始时间和结束时间
        if (true==$request->has('begin'))
        {
            $begin = strtotime($request->input('begin'));
            $startDate = $request->input('begin');
            if (true==$request->has('end')){
                $endTime = strtotime($request->input('end'));
                $endDate = $request->input('end');
                $request->offsetSet('end',$request->input('end'));
            }else{
                $endTime = time();
                $endDate = date('Y-m-d H:i:s',$endTime);
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
                $startDate = date('Y-m-d H:i:s',$begin);
                $endDate = date('Y-m-d H:i:s',$endTime);
                $request->offsetSet('begin',date('Y-m-d H:i:s',$begin));
                $request->offsetSet('end',date('Y-m-d H:i:s',$endTime));
            }else{
                $begin = $start['create_time'];
                $endTime = $end['create_time'];
                $startDate = date('Y-m-d H:i:s',$start['create_time']);
                $endDate = date('Y-m-d H:i:s',$end['create_time']);
                $request->offsetSet('begin',date('Y-m-d H:i:s',$begin));
                $request->offsetSet('end',date('Y-m-d H:i:s',$endTime));
            }
        }
        //获取到开始时间和结束时间的时间段数组
        $dateArr = $this->getDateTimePeriodByBeginAndEnd($startDate,$endDate);
        $dateSql = '';
        for ($i=0;$i<count($dateArr);$i++)
        {
            if (Schema::hasTable('order_'.$dateArr[$i])){
                if ($dateSql==""){
                    $dateSql = "select * from hq_order_".$dateArr[$i];
                }else{
                    $dateSql = $dateSql.' union all select * from hq_order_'.$dateArr[$i];
                }
            }
        }
        $data = Agent::where($map)->paginate(10)->appends($request->all());
        foreach ($data as $key=>$value){
            $sql = 'select t1.* from (select * from('.$dateSql.') s where s.creatime between '.strtotime($startDate).' and '.strtotime($endDate).') t1 
            left join hq_user u on t1.user_id = u.user_id
            inner join (select id from hq_agent_users where del_flag=0 and (id='.$value['id'].' or id IN (select t.id from hq_agent_users t where FIND_IN_SET('.$value['id'].',ancestors)))) a on a.id=u.agent_id
            ';
            $ssql = 'select IFNULL(SUM(t1.get_money),0) as money,a.id AS agentId from (select * from('.$dateSql.') s where s.creatime between '.strtotime($startDate).' and '.strtotime($endDate).') t1 
            left join hq_user u on t1.user_id = u.user_id
            RIGHT join (select id from hq_agent_users where del_flag=0 and (id='.$value['id'].' or id IN (select t.id from hq_agent_users t where FIND_IN_SET('.$value['id'].',ancestors)))) a on a.id=u.agent_id group by a.id
            ';
            $asql = 'select ifnull(sum(l.money),0) as money from hq_live_reward l
                left join hq_user u on u.user_id = l.user_id
                inner join (select id from hq_agent_users where del_flag=0 and (id='.$value['id'].' or id IN (select t.id from hq_agent_users t where FIND_IN_SET('.$value['id'].',ancestors)))) a on a.id=u.agent_id';
            $data[$key]['reward']=DB::select($asql);
            if ($dateSql!="" || $dateSql!=null){
                $money=0;
                $moneyData = DB::select($ssql);
                $userData = DB::select($sql);
                $data[$key]['sum_betMoney'] = $this->getSumBetMoney($userData);
                $data[$key]['win_money']=$this->getWinMoney($userData);
                $data[$key]['code']=$this->getSumCode($userData);
                $data[$key]['pumpMoney']=$this->getSumPumpMoney($value,$userData);
                foreach ($moneyData as $k=>$datum){
                    //$money = $money + $datum->money;
                    if ($datum->money<0){
                        $money = $money + $datum->money * $value['proportion']/100;
                    }
                }
                $data[$key]['kesun'] = $money;
            }
        }
        return view('onAgent.agentDay.list',['list'=>$data,'min'=>config('admin.min_date'),'input'=>$request->all()]);
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
        $map['parent_id']=$id;
        if (true == $request->has('account')){
            $map['username']=$request->input('account');
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
                //$data[$key]['pump']=$this->getSumPump($userData,$value['id']);
                foreach ($moneyData as $k=>$datum){
                    if ($datum->money<0){
                        $money = $money + $datum->money * $value['proportion']/100;
                    }
                }
                $data[$key]['kesun'] = $money;
            }
        }
        return view('onAgent.agentDay.list',['list'=>$data,'min'=>config('admin.min_date'),'input'=>$request->all()]);
    }

    /**
     * 获取抽水收益
     * @param $userInfo
     * @param $data
     * @return float|int
     */
    public function getSumPumpMoney($userInfo,$data)
    {
        $money =0;
        foreach ($data as $key=>$datum)
        {
            if ($datum->status==1){
                $tableName = $this->getGameRecordTableNameByRecordSn($datum->record_sn);
                $recordInfo = $this->getGameRecordInfo($tableName,$datum->record_sn);
                if ($recordInfo['status']==1){
                    //{"bankerPair":0,"game":7,"playerPair":0} 百家乐
                    $jsonData = json_decode($recordInfo['winner'],true);
                    $betMoney = json_decode($datum->bet_money,true);
                    $user = HqUser::getUserInfoByUserId($datum->user_id);
                    if ($datum->game_type==1){//百家乐
                        if ($jsonData['game']=="7"){    //{"banker":0,"bankerPair":0,"player":0,"playerPair":0,"tie":20000}
                            if ($betMoney['banker']>0){
                                $money = $money + ($betMoney['banker'] - ($betMoney['banker'] * $user['bjlbets_fee']['banker']/100)) * ($userInfo['pump']/100);
                            }
                            if ($user['player']/100 != 1){
                                $money = $money + ($betMoney['player'] - ($betMoney['player'] * $user['bjlbets_fee']['player']/100)) * ($userInfo['pump']/100);
                            }
                        }
                        continue;
                    }elseif ($datum->game_type==2){//龙虎
                        if ($recordInfo['winner']=="4"){//龙 {"dragon":0,"tie":0,"tiger":10000}
                            if ($betMoney['tiger']>0){
                                $money = $money + ($betMoney['tiger'] - ($betMoney['tiger'] * $user['lhbets_fee']['tiger']/100)) * ($userInfo['pump']/100);
                            }
                        }elseif ($recordInfo['winner']=="7"){
                            if ($betMoney['dragon']>0){
                                $money = $money + ($betMoney['dragon'] - ($betMoney['dragon'] * $user['lhbets_fee']['dragon']/100)) * ($userInfo['pump']/100);
                            }
                        }
                        continue;
                    }elseif ($datum->game_type==3){//牛牛 {"bankernum":"没牛","x1num":"没牛","x1result":"","x2num":"没牛","x2result":"win","x3num":"牛4","x3result":"win"}
                        if ($jsonData['x1result']!=""){
                            $result = $this->nConvertNumbers($jsonData['x1num']);
                            if (!empty($betMoney['x1_Super_Double'])){
                                if ($result>9){
                                    $money = $money + (($betMoney['x1_Super_Double'] - ($betMoney['x1_Super_Double'] * $user['nnbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100)) * 10;
                                }elseif ($result>0 && $result<10){
                                    $money = $money + (($betMoney['x1_Super_Double'] - ($betMoney['x1_Super_Double'] * $user['nnbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100)) * $result;
                                }else{
                                    $money = $money + ($betMoney['x1_Super_Double'] - ($betMoney['x1_Super_Double'] * $user['nnbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100);
                                }
                            }
                            if (!empty($betMoney['x1_double'])){
                                if ($result>9){
                                    $money = $money + (($betMoney['x1_double'] - ($betMoney['x1_double'] * $user['nnbets_fee']['Double']/100)) * ($userInfo['pump']/100)) * 3;
                                }else if ($result>6 && $result<10){
                                    $money = $money + (($betMoney['x1_double'] - ($betMoney['x1_double'] * $user['nnbets_fee']['Double']/100)) * ($userInfo['pump']/100)) * 2;
                                }else{
                                    $money = $money + ($betMoney['x1_double'] - ($betMoney['x1_double'] * $user['nnbets_fee']['Double']/100)) * ($userInfo['pump']/100);
                                }
                            }
                            if (!empty($betMoney['x1_equal'])){
                                $money = $money + ($betMoney['x1_equal'] - ($betMoney['x1_equal'] * $user['nnbets_fee']['Equal']/100)) * ($userInfo['pump']/100);
                            }
                        }
                        if ($jsonData['x2result']!=""){
                            $result = $this->nConvertNumbers($jsonData['x2num']);
                            if (!empty($betMoney['x2_Super_Double'])){
                                if ($result>9){
                                    $money = $money + (($betMoney['x2_Super_Double'] - ($betMoney['x2_Super_Double'] * $user['nnbets_fee']['nnbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100)) * 10;
                                }elseif ($result>0 && $result<10){
                                    $money = $money + (($betMoney['x2_Super_Double'] - ($betMoney['x2_Super_Double'] * $user['nnbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100)) * $result;
                                }else{
                                    $money = $money + ($betMoney['x2_Super_Double'] - ($betMoney['x2_Super_Double'] * $user['nnbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100);
                                }
                            }
                            if (!empty($betMoney['x2_double'])){
                                if ($result>9){
                                    $money = $money + (($betMoney['x2_double'] - ($betMoney['x2_double'] * $user['nnbets_fee']['Double']/100)) * ($userInfo['pump']/100)) * 3;
                                }else if ($result>6 && $result<10){
                                    $money = $money + (($betMoney['x2_double'] - ($betMoney['x2_double'] * $user['nnbets_fee']['Double']/100)) * ($userInfo['pump']/100)) * 2;
                                }else{
                                    $money = $money + ($betMoney['x2_double'] - ($betMoney['x2_double'] * $user['nnbets_fee']['Double']/100)) * ($userInfo['pump']/100);
                                }
                            }
                            if (!empty($betMoney['x2_equal'])){
                                $money = $money + ($betMoney['x2_equal'] - ($betMoney['x2_equal'] * $user['nnbets_fee']['Equal']/100)) * ($userInfo['pump']/100);
                            }
                        }
                        if ($jsonData['x3result']!=""){
                            $result = $this->nConvertNumbers($jsonData['x3num']);
                            if (!empty($betMoney['x3_Super_Double'])){
                                if ($result>9){
                                    $money = $money + (($betMoney['x3_Super_Double'] - ($betMoney['x3_Super_Double'] * $user['nnbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100)) * 10;
                                }elseif ($result>0 && $result<10){
                                    $money = $money + (($betMoney['x3_Super_Double'] - ($betMoney['x3_Super_Double'] * $user['nnbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100)) * $result;
                                }else{
                                    $money = $money + ($betMoney['x3_Super_Double'] - ($betMoney['x3_Super_Double'] * $user['nnbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100);
                                }
                            }
                            if (!empty($betMoney['x3_double'])){
                                if ($result>9){
                                    $money = $money + (($betMoney['x3_double'] - ($betMoney['x3_double'] * $user['nnbets_fee']['Double']/100)) * ($userInfo['pump']/100)) * 3;
                                    dump('牛牛-闲3-翻倍'.(($betMoney['x3_double'] - ($betMoney['x3_double'] * $user['nnbets_fee']['Double']/100)) * ($userInfo['pump']/100)) * 3);
                                }else if ($result>6 && $result<10){
                                    $money = $money + (($betMoney['x3_double'] - ($betMoney['x3_double'] * $user['nnbets_fee']['Double']/100)) * ($userInfo['pump']/100)) * 2;
                                }else{
                                    $money = $money + ($betMoney['x3_double'] - ($betMoney['x3_double'] * $user['nnbets_fee']['Double']/100)) * ($userInfo['pump']/100);
                                }
                            }
                            if (!empty($betMoney['x3_equal'])){
                                $money = $money + ($betMoney['x3_equal'] - ($betMoney['x3_equal'] * $user['nnbets_fee']['Equal']/100)) * ($userInfo['pump']/100);
                            }
                        }
                        continue;
                    }elseif ($datum->game_type==4){//三公 {"bankernum":"2点","x1num":"9点","x1result":"win","x2num":"1点","x2result":"","x3num":"1点","x3result":"","x4num":"1点","x4result":"","x5num":"3点","x5result":"win","x6num":"9点","x6result":"win"}
                        if ($jsonData['x1result']!=""){
                            $result = $this->sConvertNumbers($jsonData['x1num']);
                            if (!empty($betMoney['x1_Super_Double'])){
                                if ($result>9){
                                    $money = $money + (($betMoney['x1_Super_Double'] - ($betMoney['x1_Super_Double'] * $user['sgbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100)) * 10;
                                }elseif ($result>0 && $result<10){
                                    $money = $money + (($betMoney['x1_Super_Double'] - ($betMoney['x1_Super_Double'] * $user['sgbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100)) * $result;
                                }else{
                                    $money = $money + ($betMoney['x1_Super_Double'] - ($betMoney['x1_Super_Double'] * $user['sgbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100);
                                }
                            }
                            if (!empty($betMoney['x1_double'])){
                                if ($result>9){
                                    $money = $money + (($betMoney['x1_double'] - ($betMoney['x1_double'] * $user['sgbets_fee']['Double']/100)) * ($userInfo['pump']/100)) * 3;
                                }elseif ($result>6 && $result<10){
                                    $money = $money + (($betMoney['x1_double'] - ($betMoney['x1_double'] * $user['sgbets_fee']['Double']/100)) * ($userInfo['pump']/100)) * 2;
                                }else{
                                    $money = $money + ($betMoney['x1_double'] - ($betMoney['x1_double'] * $user['sgbets_fee']['Double']/100)) * ($userInfo['pump']/100);
                                }
                            }
                            if (!empty($betMoney['x1_equal'])){
                                $money = $money + ($betMoney['x1_equal'] - ($betMoney['x1_equal'] * $user['sgbets_fee']['Equal']/100)) * ($userInfo['pump']/100);
                            }
                        }
                        if ($jsonData['x2result']!=""){
                            $result = $this->sConvertNumbers($jsonData['x2num']);
                            if (!empty($betMoney['x2_Super_Double'])){
                                if ($result>9){
                                    $money = $money + (($betMoney['x2_Super_Double'] - ($betMoney['x2_Super_Double'] * $user['sgbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100)) * 10;
                                }elseif ($result>0 && $result<10){
                                    $money = $money + (($betMoney['x2_Super_Double'] - ($betMoney['x2_Super_Double'] * $user['sgbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100)) * $result;
                                }else{
                                    $money = $money + ($betMoney['x2_Super_Double'] - ($betMoney['x2_Super_Double'] * $user['sgbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100);
                                }
                            }
                            if (!empty($betMoney['x2_double'])){
                                if ($result>9){
                                    $money = $money + (($betMoney['x2_double'] - ($betMoney['x2_double'] * $user['sgbets_fee']['Double']/100)) * ($userInfo['pump']/100)) * 3;
                                }elseif ($result>6 && $result<10){
                                    $money = $money + (($betMoney['x2_double'] - ($betMoney['x2_double'] * $user['sgbets_fee']['Double']/100)) * ($userInfo['pump']/100)) * 2;
                                }else{
                                    $money = $money + ($betMoney['x2_double'] - ($betMoney['x2_double'] * $user['sgbets_fee']['Double']/100)) * ($userInfo['pump']/100);
                                }
                            }
                            if (!empty($betMoney['x2_equal'])){
                                $money = $money + ($betMoney['x2_equal'] - ($betMoney['x2_equal'] * $user['sgbets_fee']['Equal']/100)) * ($userInfo['pump']/100);
                            }
                        }
                        if ($jsonData['x3result']!=""){
                            $result = $this->sConvertNumbers($jsonData['x3num']);
                            if (!empty($betMoney['x3_Super_Double'])){
                                if ($result>9){
                                    $money = $money + (($betMoney['x3_Super_Double'] - ($betMoney['x3_Super_Double'] * $user['sgbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100)) * 10;
                                }elseif ($result>0 && $result<10){
                                    $money = $money + (($betMoney['x3_Super_Double'] - ($betMoney['x3_Super_Double'] * $user['sgbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100)) * $result;
                                }else{
                                    $money = $money + ($betMoney['x3_Super_Double'] - ($betMoney['x3_Super_Double'] * $user['sgbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100);
                                }
                            }
                            if (!empty($betMoney['x3_double'])){
                                if ($result>9){
                                    $money = $money + (($betMoney['x3_double'] - ($betMoney['x3_double'] * $user['sgbets_fee']['Double']/100)) * ($userInfo['pump']/100)) * 3;
                                }elseif ($result>6 && $result<10){
                                    $money = $money + (($betMoney['x3_double'] - ($betMoney['x3_double'] * $user['sgbets_fee']['Double']/100)) * ($userInfo['pump']/100)) * 2;
                                }else{
                                    $money = $money + ($betMoney['x3_double'] - ($betMoney['x3_double'] * $user['sgbets_fee']['Double']/100)) * ($userInfo['pump']/100);
                                }
                            }
                            if (!empty($betMoney['x3_equal'])){
                                $money = $money + ($betMoney['x3_equal'] - ($betMoney['x3_equal'] * $user['sgbets_fee']['Equal']/100)) * ($userInfo['pump']/100);
                            }
                        }
                        if ($jsonData['x4result']!=""){
                            $result = $this->sConvertNumbers($jsonData['x4num']);
                            if (!empty($betMoney['x4_Super_Double'])){
                                if ($result>9){
                                    $money = $money + (($betMoney['x4_Super_Double'] - ($betMoney['x4_Super_Double'] * $user['sgbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100)) * 10;
                                }elseif ($result>0 && $result<10){
                                    $money = $money + (($betMoney['x4_Super_Double'] - ($betMoney['x4_Super_Double'] * $user['sgbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100)) * $result;
                                }else{
                                    $money = $money + ($betMoney['x4_Super_Double'] - ($betMoney['x4_Super_Double'] * $user['sgbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100);
                                }
                            }
                            if (!empty($betMoney['x4_double'])){
                                if ($result>9){
                                    $money = $money + (($betMoney['x4_double'] - ($betMoney['x4_double'] * $user['sgbets_fee']['Double']/100)) * ($userInfo['pump']/100)) * 3;
                                }elseif ($result>6 && $result<10){
                                    $money = $money + (($betMoney['x4_double'] - ($betMoney['x4_double'] * $user['sgbets_fee']['Double']/100)) * ($userInfo['pump']/100)) * 2;
                                }else{
                                    $money = $money + ($betMoney['x4_double'] - ($betMoney['x4_double'] * $user['sgbets_fee']['Double']/100)) * ($userInfo['pump']/100);
                                }
                            }
                            if (!empty($betMoney['x4_equal'])){
                                $money = $money + ($betMoney['x4_equal'] - ($betMoney['x4_equal'] * $user['sgbets_fee']['Equal']/100)) * ($userInfo['pump']/100);
                            }
                        }
                        if ($jsonData['x5result']!=""){
                            $result = $this->sConvertNumbers($jsonData['x5num']);
                            if (!empty($betMoney['x5_Super_Double'])){
                                if ($result>9){
                                    $money = $money + (($betMoney['x5_Super_Double'] - ($betMoney['x5_Super_Double'] * $user['sgbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100)) * 10;
                                }elseif ($result>0 && $result<10){
                                    $money = $money + (($betMoney['x5_Super_Double'] - ($betMoney['x5_Super_Double'] * $user['sgbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100)) * $result;
                                }else{
                                    $money = $money + ($betMoney['x5_Super_Double'] - ($betMoney['x5_Super_Double'] * $user['sgbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100);
                                }
                            }
                            if (!empty($betMoney['x5_double'])){
                                if ($result>9){
                                    $money = $money + (($betMoney['x5_double'] - ($betMoney['x5_double'] * $user['sgbets_fee']['Double']/100)) * ($userInfo['pump']/100)) * 3;
                                }elseif ($result>6 && $result<10){
                                    $money = $money + (($betMoney['x5_double'] - ($betMoney['x5_double'] * $user['sgbets_fee']['Double']/100)) * ($userInfo['pump']/100)) * 2;
                                }else{
                                    $money = $money + ($betMoney['x5_double'] - ($betMoney['x5_double'] * $user['sgbets_fee']['Double']/100)) * ($userInfo['pump']/100);
                                }
                            }
                            if (!empty($betMoney['x5_equal'])){
                                $money = $money + ($betMoney['x5_equal'] - ($betMoney['x5_equal'] * $user['sgbets_fee']['Equal']/100)) * ($userInfo['pump']/100);
                            }
                        }
                        if ($jsonData['x6result']!=""){
                            $result = $this->sConvertNumbers($jsonData['x6num']);
                            if (!empty($betMoney['x6_Super_Double'])){
                                if ($result>9){
                                    $money = $money + (($betMoney['x6_Super_Double'] - ($betMoney['x6_Super_Double'] * $user['sgbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100)) * 10;
                                }elseif ($result>0 && $result<10){
                                    $money = $money + (($betMoney['x6_Super_Double'] - ($betMoney['x6_Super_Double'] * $user['sgbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100)) * $result;
                                }else{
                                    $money = $money + ($betMoney['x6_Super_Double'] - ($betMoney['x6_Super_Double'] * $user['sgbets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100);
                                }
                            }
                            if (!empty($betMoney['x6_double'])){
                                if ($result>9){
                                    $money = $money + (($betMoney['x6_double'] - ($betMoney['x6_double'] * $user['sgbets_fee']['Double']/100)) * ($userInfo['pump']/100)) * 3;
                                }elseif ($result>6 && $result<10){
                                    $money = $money + (($betMoney['x6_double'] - ($betMoney['x6_double'] * $user['sgbets_fee']['Double']/100)) * ($userInfo['pump']/100)) * 2;
                                }else{
                                    $money = $money + ($betMoney['x6_double'] - ($betMoney['x6_double'] * $user['sgbets_fee']['Double']/100)) * ($userInfo['pump']/100);
                                }
                            }
                            if (!empty($betMoney['x6_equal'])){
                                $money = $money + ($betMoney['x6_equal'] - ($betMoney['x6_equal'] * $user['sgbets_fee']['Equal']/100)) * ($userInfo['pump']/100);
                            }
                        }
                        continue;
                    }elseif ($datum->game_type==5){//A89  {"BankerNum":"7点","FanNum":"3点","Fanresult":"","ShunNum":"0点","Shunresult":"","TianNum":"9点","Tianresult":"win"}
                        if ($jsonData['Fanresult'] != ""){
                            $result = $this->aConvertNumbers($jsonData['FanNum']);
                            if (!empty($betMoney['FanMen_Super_Double'])){
                                if ($result>9){
                                    $money = $money + (($betMoney['FanMen_Super_Double'] - ($betMoney['FanMen_Super_Double'] * $user['a89bets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100)) * 10;
                                }elseif ($result>0 && $result<10){
                                    $money = $money + (($betMoney['FanMen_Super_Double'] - ($betMoney['FanMen_Super_Double'] * $user['a89bets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100)) * $result;
                                }else{
                                    $money = $money + ($betMoney['FanMen_Super_Double'] - ($betMoney['FanMen_Super_Double'] * $user['a89bets_fee']['SuperDouble']/100)) * ($userInfo['pump']/100);
                                }
                            }
                            if (!empty($betMoney['FanMen_equal'])){
                                $money = $money + ($betMoney['FanMen_equal'] - ($betMoney['FanMen_equal'] * $user['a89bets_fee']['Equal']/100)) * ($userInfo['pump']/100);
                            }
                        }
                        if ($jsonData['Shunresult'] != ""){
                            $result = $this->aConvertNumbers($jsonData['ShunNum']);
                            if (!empty($betMoney['ShunMen_Super_Double'])){
                                if ($result > 9){
                                    $money = $money + (($betMoney['ShunMen_Super_Double'] - ($betMoney['ShunMen_Super_Double'] * $user['a89bets_fee']['SuperDouble'])) * ($userInfo['pump']/100)) * 10;
                                }elseif ($result>0 && $result<10){
                                    $money = $money + (($betMoney['ShunMen_Super_Double'] - ($betMoney['ShunMen_Super_Double'] * $user['a89bets_fee']['SuperDouble'])) * ($userInfo['pump']/100)) * $result;
                                }else{
                                    $money = $money + ($betMoney['ShunMen_Super_Double'] - ($betMoney['ShunMen_Super_Double'] * $user['a89bets_fee']['SuperDouble'])) * ($userInfo['pump']/100);
                                }
                            }
                            if (!empty($betMoney['ShunMen_equal'])){
                                $money = $money + ($betMoney['ShunMen_Super_Double'] - ($betMoney['ShunMen_Super_Double'] * $user['a89bets_fee']['Equal'])) * ($userInfo['pump']/100);
                            }
                        }
                        if ($jsonData['Tianresult'] != ""){
                            $result = $this->aConvertNumbers($jsonData['TianNum']);
                            if (!empty($betMoney['TianMen_Super_Double'])){
                                if ($result>9){
                                    $money = $money + (($betMoney['TianMen_Super_Double'] - ($betMoney['TianMen_Super_Double'] * $user['a89bets_fee']['SuperDouble'])) * ($userInfo['pump']/100)) * 10;
                                }elseif($result > 0 && $result<10){
                                    $money = $money + (($betMoney['TianMen_Super_Double'] - ($betMoney['TianMen_Super_Double'] * $user['a89bets_fee']['SuperDouble'])) * ($userInfo['pump']/100)) * $result;
                                }else{
                                    $money = $money + ($betMoney['TianMen_Super_Double'] - ($betMoney['TianMen_Super_Double'] * $user['a89bets_fee']['SuperDouble'])) * ($userInfo['pump']/100);
                                }
                            }
                            if (!empty($betMoney['TianMen_equal'])){
                                $money = $money + ($betMoney['TianMen_equal'] - ($betMoney['TianMen_equal'] * $user['a89bets_fee']['Equal'])) * ($userInfo['pump']/100);
                            }
                        }
                        continue;
                    }
                }else{
                    continue;
                }
            }
        }
        return $money;
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
     * 根据订单号获取表名
     * @param $recordSn
     * @return false|string
     */
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
        return $game->where('record_sn','=',$recordSn)->first();
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