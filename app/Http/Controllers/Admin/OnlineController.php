<?php
namespace APP\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Desk;
use App\Models\HqUser;
use Illuminate\Http\Request;

class OnlineController extends Controller
{
    /**
     * 数据列表
     */
    public function index(Request $request){
        $map = array();
        $map['user.is_online']=1;
        if(true==$request->has('username')){
            $map['agent_users.username']=$request->input('username');
        }
        if(true==$request->has('account')){
            $map['user.account'] = $request->input('account');
        }
        if(true==$request->has('deskId')){
            $map['user.desk_id']=$request->input('deskId');
        }
        if(true==$request->has('online_type')){
            $map['user.online_type']=$request->input('online_type');
        }
        $sql = HqUser::query();

        $data = $sql->leftJoin('agent_users','user.agent_id','=','agent_users.id')
            ->leftJoin('user_account','user.user_id','=','user_account.user_id')
            ->leftJoin('desk','desk.id','=','user.desk_id')
            ->select('user.*','agent_users.username','desk.desk_name','user_account.balance')->where($map)->orderBy('user.savetime','desc')->paginate(10)->appends($request->all());
        foreach ($data as $key=>$value){
            $url = "http://whois.pconline.com.cn/ipJson.jsp?ip=".$value['last_ip']."'&json=true";
            $result = file_get_contents($url);
            $result = iconv('gb2312','utf-8//IGNORE',$result);
            $result = json_decode($result,true);
            $data[$key]['address']=$result['addr'];
            $data[$key]['savetime']=date('Y-m-d H:i:s');
        }
        return view('online.list',['list'=>$data,'input'=>$request->all(),'desk'=>$this->getAllDeskList()]);
    }

    /**
     * 获取所有台桌
     * @return Desk[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getAllDeskList(){
        return Desk::where('status','=',0)->get();
    }
}