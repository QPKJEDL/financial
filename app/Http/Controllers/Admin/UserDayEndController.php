<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Desk;
use App\Models\HqUser;
use App\Models\LiveReward;
use App\Models\Maintain;
use App\Models\Order;
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
                DB::raw('SUM(washMoney) as washMoney'),DB::raw('SUM(betMoney) as betMoney'),DB::raw('SUM(getMoney) as getMoney'),DB::raw('SUM(feeMoney) as feeMoney'),'user_rebate.userType')->groupBy('user_rebate.user_id')->paginate($limit)->appends($request->all());
        foreach ($data as $key=>$datum)
        {
            $data[$key]['reward']=LiveReward::getSumMoney($datum['user_id'],$begin,$end);
        }
        return view('userDay.list',['list'=>$data,'input'=>$request->all(),'limit'=>$limit]);
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
        $request->offsetSet('begin',$begin);
        $request->offsetSet('end',$end);
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
                DB::raw('SUM(washMoney) as washMoney'),DB::raw('SUM(betMoney) as betMoney'),DB::raw('SUM(getMoney) as getMoney'),DB::raw('SUM(feeMoney) as feeMoney'),'user_rebate.userType')->groupBy('user_rebate.user_id')->paginate($limit)->appends($request->all());
        foreach ($data as $key=>$datum)
        {
            $data[$key]['reward']=LiveReward::getSumMoney($datum['user_id'],$begin,$end);
        }
        return view('userDay.list',['list'=>$data,'input'=>$request->all(),'limit'=>$limit]);
    }
}