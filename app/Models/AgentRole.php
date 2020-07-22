<?php


namespace App\Models;


use App\Models\Interfaces\AdminRoleInterface;
use App\Models\Traits\AdminRoleTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AgentRole extends Model implements AdminRoleInterface
{
    use AdminRoleTrait;
    protected $table = 'agent_roles';
    public function isAbleDel($roleid){
        return DB::table('agent_role_user')->where('role_id',$roleid)->get()->toArray()?true:false;
    }
}