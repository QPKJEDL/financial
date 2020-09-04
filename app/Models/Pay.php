<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Pay extends Model
{
    protected $table = 'pay';

    public static function getAllPayList()
    {
        return Pay::query()->select('business_id','service_name')->where(['status'=>0])->get()->toArray();
    }
}