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
        $data['fee']=json_decode($data['fee'],true);
        $data['nnbets_fee']=json_decode($data['nnbets_fee'],true);
        $data['lhbets_fee']=json_decode($data['lhbets_fee'],true);
        $data['a89bets_fee']=json_decode($data['a89bets_fee'],true);
        $data['sgbets_fee']=json_decode($data['sgbets_fee'],true);
        $data['bjlbets_fee']=json_decode($data['bjlbets_fee'],true);
        return $data;
    }
}