<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Log extends Model
{
    protected $table = 'admin_logs';
    public function user()
    {
        return $this->hasOne(config('auth.providers.users.model'), 'id', 'admin_id');
    }
    public static function addLogs($content,$url,$id = ''){
        if(!$id){
            $admin = new Admin();
            $id = $admin->userId();
        }
        $data = [
            'admin_id'=>$id,
            'log_info'=>$content,
            'log_url'=>$url,
            'log_ip'=>$_SERVER['REMOTE_ADDR'],
            'log_time'=>date('Y-m-d H:i:s',time()),
            'type'=>1,
        ];
        Log::insert($data);
    }
    //身份-名字
    public static function getName($id,$admin_id){
        $data=[];
        $type=Log::where(array('id'=>$id,'admin_id'=>$admin_id))->value('type');
        if($type==1){
            $res=Log::getAdmin($admin_id);
            $data['name']=$res['name'];
            $data['shenfen']=$res['shenfen'];
        }else if($type==2){
            $buname=Log::getBus($admin_id);
            $data['name']=$buname;
            $data['shenfen']='商户';
        }else if($type==3){
            $agentname=Log::getAgent($admin_id);
            $data['name']=$agentname;
            $data['shenfen']='代理';
        }else if(empty($type)){
            $data['name']='后台';
            $data['shenfen']='后台';
        }
        return $data;

    }
    //后台名字-身份
    public static function getAdmin($id){
        $admin=[];
        $name= User::where('id',$id)->value('username');
        $role_id=Adminrole::where('user_id',$id)->value('role_id');
        $role_name=Role::where('id',$role_id)->value('display_name');
        $admin['name']=$name;
        $admin['shenfen']=$role_name;
        return $admin;
    }

    //商户帐户名
    public static function getBus($busid){
        return Business::where('business_code',$busid)->value('account');
    }
    //代理帐户名
    public static function getAgent($agentid){
        return Agent::where('id',$agentid)->value('account');
    }
}
