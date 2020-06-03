<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class HqUser extends Model{
    protected $table = "user";
    protected $primaryKey="user_id";
    public $timestamps = false;

    /**
     * 根据用户id获取信息
     */
    public static function getUserInfoByUserId($userId){
        $data = $userId?HqUser::find($userId):[];
        return $data;
    }
}