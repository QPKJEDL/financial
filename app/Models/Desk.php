<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Desk extends Model{
    protected $table = "desk";

    public static function getDeskList(){
        return Desk::get();
    }

    public static function getDeskInfo($deskId){
        $data = $deskId?Desk::find($deskId):[];
        return $data;
    }
}