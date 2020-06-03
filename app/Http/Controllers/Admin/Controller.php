<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Redis;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

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
}
