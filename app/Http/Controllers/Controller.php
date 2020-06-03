<?php

namespace App\Http\Controllers;

use App\Models\DeskLog;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    //台桌操作日志类型
    public static $operaType = 1;
    //台桌操作结果类型
    public static $operaResultType = 2;

    /**
     * redis队列锁  加锁
     * @param $gameSn
     * @return bool
     */
    public function redisLock($gameSn)
    {
        $code=time().rand(100000,999999);
        //锁入列
        Redis::rPush('gameRecord_lock_'.$gameSn,$code);

        //锁出列
        $codes = Redis::LINDEX('gameRecord_lock_'.$gameSn,0);
        if ($code!=$codes){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 解锁
     * @param $gameSn
     */
    public function unRedisLock($gameSn)
    {
        Redis::del('gameRecord_lock_'.$gameSn);
    }

    /**
     * 添加台桌操作日志
     * @param $deskId 台桌id
     * @param $action 操作动作
     */
    public function insertDeskLogOperaType($deskId,$action)
    {
        $data = array();
        $data['desk_id']=$deskId;
        $data['log_type']=self::$operaType;
        $data['action']=$action;
        $data['create_by']=$this->getLoginUser();
        $data['create_time']=time();
        DeskLog::insert($data);
    }

    /**
     * 添加台桌修改结果日志
     * @param $deskId 台桌id
     * @param $record 游戏记录编号
     * @param $beforeResult 修改前结果
     * @param $afterResult 修改后结果
     */
    public function insertDeskLogOperaResultType($deskId,$record,$beforeResult,$afterResult)
    {
        $data = array();
        $data['desk_id']=$deskId;
        $data['log_type']=self::$operaResultType;
        $data['record_sn']= $record;
        $data['before_result']=$beforeResult;
        $data['after_result']=$afterResult;
        $data['create_by']=$this->getLoginUser();
        $data['create_time']=time();
        DeskLog::insert($data);
    }

    /**
     * 获取当前登录用户的用户名
     */
    public function getLoginUser()
    {
        $user = Auth::user();
        return $user['username'];
    }
}
