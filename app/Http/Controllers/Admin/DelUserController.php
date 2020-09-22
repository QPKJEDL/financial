<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Czrecord;
use App\Models\Desk;
use App\Models\HqUser;
use Illuminate\Http\Request;

class DelUserController extends Controller
{
    /**
     * 数据列表
     */
    public function index(Request $request){
        $map = array();
        $map['user.del_flag']=1;
        $sql = HqUser::query();
        if (true==$request->has('account')){
            $map['user.account']=$request->input('account');
        }
        if (true==$request->has('user_type'))
        {
            $map['user.user_type']=$request->input('user_type');
        }
        $sql->leftJoin('user_account','user.user_id','=','user_account.user_id')
            ->select('user.*','user_account.balance')->where($map);
        if (true==$request->has('nickname')){
            $sql->where('user.nickname','like','%'.$request->input('nickname').'%');
        }
        if (true==$request->has('limit'))
        {
            $limit = $request->input('limit');
        }
        else
        {
            $limit = config('admin.limit');
        }
        $data = $sql->orderBy('creatime','desc')->paginate($limit)->appends($request->all());
        foreach ($data as $key=>$datum){
            $data[$key]['fee']=json_decode($datum['fee'],true);
            $data[$key]['creatime']=date('Y-m-d H:i:s',$datum['creatime']);
            $data[$key]['cz']=$this->getUserCzCord($datum['user_id']);
        }
        return view('deluser.list',['list'=>$data,'input'=>$request->all(),'limit'=>$limit]);
    }

    /**
     * 获取用户最近充值记录
     */
    public function getUserCzCord($userId){
        $data = Czrecord::where('user_id',$userId)->orderBy('creatime','desc')->first();
        return $data;
    }
}