<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Maintain extends Model
{
    protected $table = "system_maintain";

    /**
     * 获取最后一次维护完成时间
     * @return Maintain|Model|null
     */
    public static function getAtLastOutDate(){
        return Maintain::where('type','=','2')->orderBy("create_time",'desc')->first();
    }

    /**
     * 获取最后一次开始维护时间
     * @return Maintain|Model|null
     */
    public static function getAtLastMaintainDate(){
        return Maintain::where('type','=','1')->orderBy('create_time','desc')->first();
    }
}