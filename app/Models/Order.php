<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use function PHPSTORM_META\map;

class Order extends Model
{
    protected $table;

    /**
     * 根据时间获取那天所有游戏的下注记录
     */
    public static function getOrderDataByTableName($tableName)
    {
        //获取所有的记录
        $data = DB::table('order_'.$tableName.' as t1')->join('game_record_'.$tableName.' as t2','t1.record_sn','=','t2.record_sn')->select('t1.*')->get()->map(function ($value){
            return (array)$value;
        })->toArray();
        return $data;
    }

    /**
     * 注单查询
     */
    public static function getOrderListByTableName($tableName,$request)
    {
        //获取记录
        $data = DB::table('order_'.$tableName.' AS t1')
            ->leftJoin('game AS t2','t1.game_type','=','t2.id')
            ->leftJoin('user AS u','t1.user_id','=','u.user_id')
            ->select('t1.*','t2.game_name','u.account','u.nickname')->paginate(10)->appends($request->all())
            ->get()
            ->map(function ($value){
                return (array)$value;
            })->toArray();
        return $data;
    }
}