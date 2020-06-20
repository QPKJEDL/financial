<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequest;
use App\Models\Agent;
use App\Models\Billflow;
use App\Models\Desk;
use App\Models\Draw;
use App\Models\UserAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * 下分请求
 */
class DownController extends Controller
{
    /**
     * 数据列表
     */
    public function index(Request $request){
        $map = array();
        if (true==$request->has('account')){
            $map['user.account']=$request->input('account');
        }
        if (true==$request->has('bank_card')){
            $map['user_draw.bank_card']=$request->input('bank_card');
        }
        $sql = Draw::query();
        $sql->leftJoin('user','user.user_id','=','user_draw.user_id')
            ->leftJoin('agent_users','user.agent_id','agent_users.id')
            ->select('user_draw.*','user.account','user.agent_id','user.nickname','agent_users.username','agent_users.nickname as agentName')->where($map);
        if (true==$request->has('begin')){
            $time = strtotime($request->input('begin'));
            if (true==$request->has('end')){
                $end = strtotime('+1day',strtotime($request->input('end')))-1;
            }else{
                $end = strtotime('+1day',strtotime($request->input('begin')))-1;
            }
            $sql->whereBetween('user_draw.creatime',[$time,$end]);
        }
        $data = $sql->paginate(10)->appends($request->all());
        foreach ($data as $key=>$value){
            $data[$key]['creatime']=date('Y-m-d H:i:s',$value['creatime']);
            $data[$key]['agent']=$this->get_direct_agent($value['agent_id']);
        }
        $min=config('admin.min_date');
        return view('down.list',['list'=>$data,'input'=>$request->all(),'min'=>$min]);
    }

    /**
     * 锁定数据
     * @param StoreRequest $request
     * @return array
     */
    public function lockDataById(StoreRequest $request){
        $id = $request->input('id');
        $user = Auth::user();
        $count = Draw::where('id','=',$id)->update(['lock_by'=>$user['username']]);
        if ($count){
            return ['msg'=>'操作成功','status'=>1];
        }else{
            return ['msg'=>'操作失败','status'=>0];
        }
    }
    /**
     * 确认数据
     */
    public function approveData(StoreRequest $request){
        $id = $request->input('id');
        $count = Draw::where('id','=',$id)->update(['status'=>1,'endtime'=>time()]);
        if ($count){
            return ['msg'=>'确认成功','status'=>1];
        }else{
            return ['msg'=>'确认失败','status'=>0];
        }
    }
    /**
     * 作废数据
     * @param StoreRequest $request
     * @return array
     */
    public function obsoleteData(StoreRequest $request){
        $id = $request->input('id');
        $data['status']=2;
        $user = Auth::user();
        $data['lock_by']=$user['username'];
        $count = Draw::where('id','=',$id)->update($data);
        if ($count){
            $info = $id?Draw::find($id):[];
            DB::table('user_account')->increment('balance',$info['money']);
            $this->insertBillFlowByToDay($info['user_id'],$info['money']);
            return ['msg'=>'操作成功','status'=>1];
        }else{
            return ['msg'=>'操作失败','status'=>0];
        }
    }
    /**
     * 插入流水
     */
    public function insertBillFlowByToDay($userId,$score){
        $tableName = date('Ymd',time());
        $bill = new Billflow();
        $bill->setTable('user_billflow_'.$tableName);
        $data['user_id']=$userId;
        $data['order_sn']=$this->getrequestId();
        $data['score']=$score;
        //获取用户现在余额
        $info = $userId?UserAccount::find($userId):[];
        $data['bet_before']=$info['balance'];
        $data['bet_after']=$info['balance'] + $score;
        $data['status']=3;
        $data['remark']="提现申请作废";
        $data['creatime']=time();
        $bill->insert($data);
    }
    /**无缓存的唯一订单号
     * @param $paycode 支付类型
     * @param $business_code 商户id
     * @param $tablesuf 订单表后缀
     * @return string
     */
    public function getrequestId(){
        @date_default_timezone_set("PRC");
        $requestId =date("YmdHis").rand(11111111,99999999);
        return $requestId;
    }
    /**
     * 获取直属一级代理
     * @param $agent_id
     * @return mixed
     */
    private function get_direct_agent($agent_id){
        $agentlist=Agent::select('id','username','nickname','parent_id','proportion')->get()->toArray();
        foreach ($agentlist as $key=>&$value){
            if ($value['id']==$agent_id){
                if($agentlist[$key]["parent_id"]>0){
                    return $this->get_direct_agent($agentlist[$key]["parent_id"]);
                }
                return $agentlist[$key];
                continue;
            }
        }
    }
}