<?php


namespace App\Http\Controllers\Online;


use App\Http\Controllers\Controller;
use App\Models\LiveReward;
use App\Models\Maintain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OnUserDayController extends Controller
{
    public function index(Request $request){
        if (true==$request->has('pageNum')){
            $curr = $request->input('pageNum');
        }else{
            $curr = 1;
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
            $request->offsetSet('begin',$request->input('begin'));
        }else{
            //获取上次维护完成时间
            $start = Maintain::getAtLastOutDate();
            $end = Maintain::getAtLastMaintainDate();
            if ($start['create_time']!="" && $end['create_time']==""){
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
            }else{
                $begin = $this->getYesterdayBeginTime();
                $endTime = $this->getYesterdayEndTime($begin);
                $startDate = date('Y-m-d',$begin);
                $endDate = date('Y-m-d',$endTime);
                $request->offsetSet('begin',date('Y-m-d H:i:s',$begin));
                $request->offsetSet('end',date('Y-m-d H:i:s',$endTime));
            }
        }
        //获取到开始时间和结束时间的时间段数组
        $dataArr = $this->getDateTimePeriodByBeginAndEnd($startDate,$endDate);
        $sql = '';
        for ($i=0;$i<count($dataArr);$i++){
            //效验数据表是否存在
            if (Schema::hasTable('order_'.$dataArr[$i])){
                if ($sql==""){
                    $sql = "select * from hq_order_".$dataArr[$i];
                }else{
                    $sql = $sql.' union all select * from hq_order_'.$dataArr[$i];
                }
            }
        }
        if ($sql!="" || $sql!=null){
            if (true==$request->has('account')){
                $dataSql = 'select t1.user_id,sum(t1.get_money) as getMoney,count(t1.user_id) as `count`,u.nickname,u.account,ua.balance from ( select * from ('.$sql.') t where t.creatime between '.$begin.' and '.$endTime.' ) t1 
        left join hq_user u on t1.user_id = u.user_id
        left join hq_user_account ua on ua.user_id = u.user_id
        where u.account = "'.$request->input('account').'" and u.user_type=1
        group by t1.user_id
        LIMIT '.(($curr - 1) * 10).',10
        ';
                //查询分页总页数
                $dataPages = 'select t1.user_id,sum(t1.get_money) as getMoney,count(t1.user_id) as `count`,u.nickname,u.account,ua.balance from ( select * from ('.$sql.') t where t.creatime between '.$begin.' and '.$endTime.' ) t1 
        left join hq_user u on t1.user_id = u.user_id
        left join hq_user_account ua on ua.user_id = u.user_id
        where u.account = "'.$request->input('account').'" and u.user_type=1
        group by t1.user_id';
            }else{
                $dataSql = 'select t1.user_id,sum(t1.get_money) as getMoney,count(t1.user_id) as `count`,u.nickname,u.account,ua.balance from ( select * from ('.$sql.') t where t.creatime between '.$begin.' and '.$endTime.' ) t1 
        left join hq_user u on t1.user_id = u.user_id
        left join hq_user_account ua on ua.user_id = u.user_id
        where u.user_type=1
        group by t1.user_id
        LIMIT '.(($curr - 1) * 10).',10
        ';
                $dataPages = 'select t1.user_id,sum(t1.get_money) as getMoney,count(t1.user_id) as `count`,u.nickname,u.account,ua.balance from ( select * from ('.$sql.') t where t.creatime between '.$begin.' and '.$endTime.' ) t1 
        left join hq_user u on t1.user_id = u.user_id
        left join hq_user_account ua on ua.user_id = u.user_id
        where u.user_type=1
        group by t1.user_id';
            }
            $pages = Db::select($dataPages);
            $data = DB::select($dataSql);
            foreach ($data as $key=>&$value){
                $userDataSql = "";
                for ($i=0;$i<count($dataArr);$i++){
                    if (Schema::hasTable('order_'.$dataArr[$i])){
                        if ($userDataSql==""){
                            $userDataSql = "select user_id,bet_money,game_type,status,creatime from hq_order_".$dataArr[$i];
                        }else{
                            $userDataSql = $userDataSql.' union all select user_id,bet_money,game_type,status,creatime from hq_order_'.$dataArr[$i];
                        }
                    }
                }
                $userData = DB::select('select * from (select * from ('.$userDataSql.') t where t.creatime between '.$begin.' and '.$endTime.') t1 where t1.user_id=?',[$value->user_id]);
                $data[$key]->betMoney=$this->getSumBetMoney($userData);
                $data[$key]->code = $this->getWashCode($userData);
                $data[$key]->reward = LiveReward::getSumMoney($value->user_id,$begin,$endTime);//count($data)/10==0?count($data)/10:count($data)/10 +1]
            }
            return view('onAgent.userDay.list',['list'=>$data,'min'=>config('admin.min_date'),'input'=>$request->all(),'curr'=>$curr,'pages'=>ceil(count($pages)/10)]);
        }else{
            $data= array();
            return view('onAgent.userDay.list',['list'=>$data,'min'=>config('admin.min_date'),'input'=>$request->all(),'curr'=>$curr,'pages'=>1]);
        }
    }

    public function getUserDayEndByAgentId($id,$begin,$end,Request $request)
    {
        $request->offsetSet('begin',$begin);
        $request->offsetSet('end',$end);
        if (true==$request->has('pageNum'))
        {
            $curr = $request->input('pageNum');
        }else{
            $curr=1;
        }
        $dateArr = $this->getDateTimePeriodByBeginAndEnd($begin,$end);
        $sql='';
        for ($i=0;$i<count($dateArr);$i++){
            //效验数据表是否存在
            if (Schema::hasTable('order_'.$dateArr[$i])){
                if ($sql==""){
                    $sql = "select * from hq_order_".$dateArr[$i];
                }else{
                    $sql = $sql.' union all select * from hq_order_'.$dateArr[$i];
                }
            }
        }
        if ($sql!="" || $sql!=null){
            if (true==$request->has('account')){
                $dataSql = 'select t1.user_id,sum(t1.get_money) as getMoney,count(t1.user_id) as `count`,u.nickname,u.account,ua.balance from ( select * from ('.$sql.') t where t.creatime between '.strtotime($begin).' and '.strtotime($end).' ) t1 
        left join hq_user u on t1.user_id = u.user_id
        left join hq_user_account ua on ua.user_id = u.user_id
        where u.account = "'.$request->input('account').'" and u.agent_id='.$id.'
        group by t1.user_id
        LIMIT '.(($curr - 1) * 10).',10
        ';
                //查询分页总页数
                $dataPages = 'select t1.user_id,sum(t1.get_money) as getMoney,count(t1.user_id) as `count`,u.nickname,u.account,ua.balance from ( select * from ('.$sql.') t where t.creatime between '.strtotime($begin).' and '.strtotime($end).' ) t1 
        left join hq_user u on t1.user_id = u.user_id
        left join hq_user_account ua on ua.user_id = u.user_id
        where u.account = "'.$request->input('account').'" and u.agent_id='.$id.'
        group by t1.user_id';
            }else{
                $dataSql = 'select t1.user_id,sum(t1.get_money) as getMoney,count(t1.user_id) as `count`,u.nickname,u.account,ua.balance from ( select * from ('.$sql.') t where t.creatime between '.strtotime($begin).' and '.strtotime($end).' ) t1 
        left join hq_user u on t1.user_id = u.user_id
        left join hq_user_account ua on ua.user_id = u.user_id
        where u.agent_id = '.$id.'
        group by t1.user_id
        LIMIT '.(($curr - 1) * 10).',10
        ';
                $dataPages = 'select t1.user_id,sum(t1.get_money) as getMoney,count(t1.user_id) as `count`,u.nickname,u.account,ua.balance from ( select * from ('.$sql.') t where t.creatime between '.strtotime($begin).' and '.strtotime($end).' ) t1 
        left join hq_user u on t1.user_id = u.user_id
        left join hq_user_account ua on ua.user_id = u.user_id
        where u.agent_id = '.$id.'
        group by t1.user_id';
            }
            $pages = Db::select($dataPages);
            $data = DB::select($dataSql);
            foreach ($data as $key=>&$value){
                $userDataSql = "";
                for ($i=0;$i<count($dateArr);$i++){
                    if (Schema::hasTable('order_'.$dateArr[$i])){
                        if ($userDataSql==""){
                            $userDataSql = "select user_id,bet_money,game_type,status,creatime from hq_order_".$dateArr[$i];
                        }else{
                            $userDataSql = $userDataSql.' union all select user_id,bet_money,game_type,status,creatime from hq_order_'.$dateArr[$i];
                        }
                    }
                }
                $userData = DB::select('select * from (select * from ('.$userDataSql.') t where t.creatime between '.strtotime($begin).' and '.strtotime($end).') t1 where t1.user_id=?',[$value->user_id]);
                $data[$key]->betMoney=$this->getSumBetMoney($userData);
                $data[$key]->code = $this->getWashCode($userData);
                $data[$key]->reward = LiveReward::getSumMoney($value->user_id,strtotime($begin),strtotime($end));//count($data)/10==0?count($data)/10:count($data)/10 +1]
            }
            return view('onAgent.userDay.list',['list'=>$data,'min'=>config('admin.min_date'),'input'=>$request->all(),'curr'=>$curr,'pages'=>ceil(count($pages)/10)]);
        }else{
            $data= array();
            return view('onAgent.userDay.list',['list'=>$data,'min'=>config('admin.min_date'),'input'=>$request->all(),'curr'=>$curr,'pages'=>1]);
        }
    }

    /**
     * 获取有效下注金额
     * @param $data
     * @return float|int|mixed
     */
    public function getWashCode($data){
        $money = 0;
        foreach ($data as $key=>$value){
            if ($value->status==1){
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
            }
        }
        return $money;
    }

    /**
     * 获取总下注金额
     * @param $data
     * @return float|int|mixed
     */
    public function getSumBetMoney($data){
        $money = 0;
        foreach ($data as $key=>$value){
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