<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\GameRecord;
use App\Models\HqUser;
use App\Models\LiveReward;
use App\Models\Maintain;
use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Models\Desk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AgentDayEndController extends Controller
{
    /**
     * 代理日结
     * @param Request $request
     * @return Factory|Application|View
     */
    public function index(Request $request)
    {
        $map = array();
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
            $asql = 'select ifnull(sum(l.money),0) as money from hq_live_reward l
                left join hq_user u on u.user_id = l.user_id
                inner join (select id from hq_agent_users where del_flag=0 and (id='.$value['id'].' or id IN (select t.id from hq_agent_users t where FIND_IN_SET('.$value['id'].',ancestors)))) a on a.id=u.agent_id';
            $data[$key]['reward']=DB::select($asql);
            $data[$key]['fee']=json_decode($value['fee'],true);
            if ($sql!="" || $sql!=null){
                $userData = DB::select($sql);
                $data[$key]['sum_betMoney'] = $this->getSumBetMoney($userData);
                $data[$key]['win_money']=$this->getWinMoney($userData);
                $data[$key]['code']=$this->getSumCode($userData);
                $data[$key]['pump']=$this->getSumPump($userData,$value['id']);
                if ($value['win_money']<0){
                    dump(number_format(abs((($value['win_money']*($value['proportion']/100))/100)),2));
                }
            }
        }
        return view('agentDay.list',['list'=>$data,'min'=>config('admin.min_date'),'input'=>$request->all()]);
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
            $asql = 'select ifnull(sum(l.money),0) as money from hq_live_reward l
                left join hq_user u on u.user_id = l.user_id
                inner join (select id from hq_agent_users where del_flag=0 and (id='.$value['id'].' or id IN (select t.id from hq_agent_users t where FIND_IN_SET('.$value['id'].',ancestors)))) a on a.id=u.agent_id';
            $data[$key]['reward']=DB::select($asql);
            $data[$key]['fee']=json_decode($value['fee'],true);
            if ($sql!="" || $sql!=null){
                $userData = DB::select($sql);
                $data[$key]['sum_betMoney'] = $this->getSumBetMoney($userData);
                $data[$key]['win_money']=$this->getWinMoney($userData);
                $data[$key]['code']=$this->getSumCode($userData);
                $data[$key]['pump']=$this->getSumPump($userData,$value['id']);
                if ($value['win_money']<0){
                    dump(abs(($value['win_money']*($value['proportion']/100))/100));
                }
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