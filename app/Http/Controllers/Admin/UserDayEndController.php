<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Desk;
use App\Models\HqUser;
use App\Models\Order;
use App\Models\UserDayEnd;
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
    public function index(Request $request){
        $map = array();
        $id = Auth::id();
        if (true==$request->has('account')){
            $map['user.account']=$request->input('account');
        }
        $sql = UserDayEnd::query();
        $sql->leftJoin("user",'user.user_id','=','user_day_end.user_id')
            ->leftJoin('user_account','user.user_id','=','user_account.user_id')
            ->select('user_day_end.id','user_day_end.user_id',DB::raw('sum(bets_money) as bets_money'),DB::raw('sum(bets_num) as bets_num'),DB::raw('sum(sum_money) as sum_money'),DB::raw('sum(win_money) as win_money'),DB::raw('sum(pump) as pump'),DB::raw('sum(reward_money) as reward_money'),'user.nickname','user.account','user_account.balance');
        if (true==$request->has('begin')){
            $time = strtotime($request->input('begin'));
            /*$end = strtotime('+1day',strtotime($request->input('begin')))-1;*/
            if (true==$request->has('end')){
                $end = strtotime('+1day',strtotime($request->input('end')))-1;
            }else{
                $end = strtotime('+1day',strtotime($request->input('begin')))-1;
            }
        }else{
            $time = strtotime(date('Y-m-d'),time());
            $end = strtotime('+1day',$time)-1;
        }
        $data = $sql->where($map)->whereBetween('user_day_end.create_time',[$time,$end])->groupBy('user_day_end.user_id')->paginate(10)->appends($request->all());
        return view('userDay.list',['list'=>$data,'min'=>config('admin.min_date'),'input'=>$request->all()]);
    }

    public function infoList($userId,Request $request){
        $request->offsetSet('type',2);
        $map = array();
        $map['user_day_end.user_id']=$userId;
        $sql = UserDayEnd::query();
        $sql->leftJoin("user",'user.user_id','=','user_day_end.user_id')
            ->leftJoin('user_account','user.user_id','=','user_account.user_id')
            ->select('user_day_end.*','user_account.balance','user.nickname','user.account');
        if (true==$request->has('begin')){
            $time = strtotime($request->input('begin'));
            if (true==$request->has('end')){
                $end = strtotime('+1day',strtotime($request->input('end')))-1;
            }else{
                $end = strtotime('+1day',strtotime($request->input('begin')))-1;
            }
        }else{
            $time = strtotime(date('Y-m-d'),time());
            $end = strtotime('+1day',$time)-1;
        }
        $data = $sql->where($map)->whereBetween('user_day_end.create_time',[$time,$end])->paginate(10)->appends($request->all());
        return view('userDay.list',['list'=>$data,'min'=>config('admin.min_date'),'input'=>$request->all()]);
    }
}