<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Czrecord extends Model
{
    protected $table = 'czrecord';

    /**
     * 插入记录
     * @param $name
     * @param $userId
     * @param $serviceId
     * @param $score
     * @param $codeType
     * @return bool
     */
    public static function insertRecord($name,$userId,$serviceId,$score,$codeType)
    {
        $data['name']=$name;
        $data['user_id']=$userId;
        $data['admin_kefu_id']=$serviceId;
        $data['score']=$score;
        $data['pay_type']=4;
        $data['code_type']=$codeType;
        $data['status']=1;
        $data['creatime']=time();
        return Czrecord::insert($data);
    }
}