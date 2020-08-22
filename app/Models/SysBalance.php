<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class SysBalance extends Model
{
    protected $table = 'sys_balance';
    public $timestamps = false;
    /**
     * 获取余额
     * @return mixed
     */
    public static function getBalance(){
        return SysBalance::where('id','=',1)->first()['balance'];
    }
}