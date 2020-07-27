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
use Illuminate\Support\Facades\Redis;

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
        $redisLock = $this->redissionLock($id);
        if ($redisLock){
            DB::beginTransaction();
            try {
                $bool = Draw::where('id','=',$id)->lockForUpdate()->first();
                if ($bool['status']==2){
                    DB::rollBack();
                    $this->unRedissLock($id);
                    return ['msg'=>'该数据已经被操作过了','status'=>0];
                }else{
                    $count = Draw::where('id','=',$id)->update($data);
                    if ($count){
                        $result = DB::table('user_account')->increment('balance',$bool['money']);
                        if ($result){
                            $this->insertBillFlowByToDay($bool['user_id'],$bool['money']);
                            DB::commit();
                            $this->unRedissLock($id);
                            return ['msg'=>'操作成功','status'=>1];
                        }else{
                            DB::rollBack();
                            $this->unRedissLock($id);
                            return ['msg'=>'操作失败','status'=>0];
                        }
                    }else{
                        DB::rollBack();
                        $this->unRedissLock($id);
                        return ['msg'=>'操作失败','status'=>0];
                    }
                }
            }catch (\Exception $e)
            {
                DB::rollBack();
                $this->unRedissLock($id);
                return ['msg'=>'操作失败','status'=>0];
            }
        }else{
            return ['msg'=>'请忽频繁提交','status'=>0];
        }
    }

    /**
     * redis队列锁
     * @param $userId
     * @return bool
     */
    public function redissionLock($userId){
        $code=time().rand(100000,999999);
        //锁入列
        Redis::rPush('zf_cw_hq_user_draw_lock_'.$userId,$code);

        //锁出列
        $codes = Redis::LINDEX('zf_cw_hq_user_draw_lock_'.$userId,0);
        if ($code!=$codes){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 解锁
     * @param $userId
     */
    public function unRedissLock($userId)
    {
        Redis::del('zf_cw_hq_user_draw_lock_'.$userId);
    }

    /**
     * 插入流水
     * @param $userId
     * @param $score
     */
    public function insertBillFlowByToDay($userId,$score){
        $tableName = date('Ymd',time());
        $bill = new Billflow();
        $bill->setTable('user_billflow_'.$tableName);
        $data['user_id']=$userId;
        $data['order_sn']=$this->getrequestId();
        $data['score']=$score;
        //获取用户现在余额
        $info = UserAccount::where('user_id','=',$userId)->lockForUpdate()->first();
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