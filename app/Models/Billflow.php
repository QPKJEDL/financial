<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Billflow extends Model
{
    protected $table;


    public static function getBillflowByOrderSn($orderSn,$tableName){
        $bill = new Billflow();
        $bill->setTable('user_billflow_'.$tableName);
        $data = $bill->where('order_sn','=',$orderSn)->first();
        return $data;
    }
}