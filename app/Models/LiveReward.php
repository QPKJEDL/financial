<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class LiveReward extends Model
{
    protected $table = "live_reward";
    /**
     * 根据userId获取当前用户的打赏金额
     * @param $userId
     * @param $begin 开始时间
     * @param $end  结束时间
     * @return int|mixed
     */
    public static function getSumMoney($userId,$begin,$end){
        return LiveReward::where('user_id','=',$userId)->whereBetween('creatime',[$begin,$end])->sum('money');
    }
}