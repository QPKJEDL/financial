<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class RoleMenu extends Model
{
    protected $table = "agent_role_menu";

    public $timestamps = false;
    public static function getInfo($roleId,$menuId){
        $data = RoleMenu::where('role_id','=',$roleId)->where('menu_id','=',$menuId)->first();
        return $data;
    }
}