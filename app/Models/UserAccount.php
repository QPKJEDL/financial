<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAccount extends Model
{
    protected $table = "user_account";

    /** 
     * 根据userId获取用户余额
     * */   
    public static function getUserAccountInfo($userId)
    {
        $userAccount = UserAccount::where('user_id','=',$userId)->first();
        return $userAccount;
    }
}
