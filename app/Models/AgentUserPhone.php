<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class AgentUserPhone extends Model
{
    protected $table = 'agent_users_phone';

    public static function checkExistByAgentId($id)
    {
        if (AgentUserPhone::where('agent_id','=',$id)->exists())
        {
            return false;
        }else{
            return true;
        }
    }
}