<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StoreRequest;
use App\Models\Billflow;
use App\Models\Czrecord;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\HqUser;
use App\Models\UserAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Mockery\Exception;

class HqUserController extends Controller
{
    /**
     * 数据列表
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function index(Request $request){
        $map = array();
        $map['user.del_flag']=0;
        $map['user.user_type']=1;
        if(true==$request->has('account')){
            $map['user.account']=$request->input('account');
        }
        $sql = HqUser::where($map);
        $sql->leftJoin('agent_users','agent_users.id','=','user.agent_id')
            ->select('user.*','agent_users.username','agent_users.nickname as agentName')
            ->where($map);
        if(true==$request->has('nickname')){
            $sql->where('user.nickname','like','%'.$request->input('nickname').'%');
        }
        $data = $sql->paginate(10)->appends($request->all());
        foreach($data as $key=>&$value){
            $data[$key]['creatime']=date("Y-m-d H:m:s",$value['creatime']);
            $data[$key]['fee']=json_decode($value['fee'],true);
            $data[$key]['userAccount']=UserAccount::getUserAccountInfo($value['user_id']);
            $data[$key]['cz']=$this->getUserCzCord($value['user_id']);
        }
        return view('hquser.list',['list'=>$data,'input'=>$request->all()]);
    }

    /**
     * 获取用户最近充值记录
     * @param $userId
     * @return Czrecord|\Illuminate\Database\Eloquent\Model|null
     */
    public function getUserCzCord($userId){
        $data = Czrecord::where('user_id',$userId)->orderBy('creatime','desc')->first();
        return $data;
    }

    /**
     * 上分页面
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function topCode($id)
    {
        $info = UserAccount::where('user_id','=',$id)->first();
        return view('hquser.code',['info'=>$info,'id'=>$id,'type'=>1]);
    }

    /**
     * 下分
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function underCode($id)
    {
        $info = UserAccount::where('user_id','=',$id)->first();
        return view('hquser.code',['info'=>$info,'id'=>$id,'type'=>2]);
    }

    /**
     * 保存上分操作
     * @param StoreRequest $request
     * @return array
     */
    public function saveTopCode(StoreRequest $request)
    {
        $data = $request->all();
        $id = $data['id'];
        unset($data['_token']);
        unset($data['id']);
        //判断锁
        $bool = $this->redisUserLock($id);
        if ($bool){
            DB::beginTransaction();
            try {
                $info = UserAccount::where('user_id','=',$id)->lockForUpdate()->first();
                $bill = new Billflow();
                $bill->setTable('user_billflow_'.date('Ymd',time()));
                $betBefore = $info['balance'];
                if ($data['type']==1){//上分
                    $res = UserAccount::where('user_id','=',$id)->update(['balance'=>$betBefore +($data['balance'] * 100)]);
                    if ($res){
                        $result = $bill->insert(['user_id'=>$id,'order_sn'=>$this->getrequestId(),'score'=>$data['balance']*100,'bet_before'=>$betBefore,'bet_after'=>$betBefore+($data['balance']*100),'status'=>1,'remark'=>'财务后台直接上分','creatime'=>time()]);
                        if ($result){
                            DB::commit();
                            $this->unRedisUserLock($id);
                            return ['msg'=>'操作成功','status'=>1];
                        }else{
                            DB::rollBack();
                            $this->unRedisUserLock($id);
                            return ['msg'=>'操作失败','status'=>0];
                        }
                    }else{
                        DB::rollBack();
                        $this->unRedisUserLock($id);
                        return ['msg'=>'操作失败','status'=>0];
                    }
                }else{//下分
                    $balance = UserAccount::where('user_id','=',$id)->lockForUpdate()->first();
                    if ($balance['balance'] < abs($data['balance']*100)){
                        return ['msg'=>'金额不足，不能提现','status'=>0];
                    }else{
                        $res = UserAccount::where('user_id','=',$id)->update(['balance'=>$betBefore -$data['balance'] * 100]);
                        if ($res){
                            $result = $bill->insert(['user_id'=>$id,'order_sn'=>$this->getrequestId(),'score'=>$data['balance']*100,'bet_before'=>$betBefore,'bet_after'=>$betBefore-abs($data['balance']*100),'status'=>3,'remark'=>'财务后台直接下分','creatime'=>time()]);
                            if ($result){
                                DB::commit();
                                $this->unRedisUserLock($id);
                                return ['msg'=>'操作成功','status'=>1];
                            }else{
                                DB::rollBack();
                                $this->unRedisUserLock($id);
                                return ['msg'=>'操作失败','status'=>0];
                            }
                        }else{
                            DB::rollBack();
                            $this->unRedisUserLock($id);
                            return ['msg'=>'操作失败','status'=>0];
                        }
                    }
                }
            }catch (Exception $e){
                dump($e);
                DB::rollBack();
                $this->unRedisUserLock($id);
                return ['msg'=>'操作失败','status'=>0];
            }
        }else{
            return ['msg'=>'请忽频繁提交','status'=>0];
        }
    }



    /**
     * redis队列锁  加锁
     * @param $userId
     * @return bool
     */
    public function redisUserLock($userId)
    {
        $code=time().rand(100000,999999);
        //锁入列
        Redis::rPush('hquser_code_lock_'.$userId,$code);

        //锁出列
        $codes = Redis::LINDEX('hquser_code_lock_'.$userId,0);
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
    public function unRedisUserLock($userId)
    {
        Redis::del('hquser_code_lock_'.$userId);
    }

    public function getrequestId(){
        @date_default_timezone_set("PRC");
        $requestId  =	date("YmdHis").rand(11111111,99999999);
        return $requestId;
    }

    public function destroy($id)
    {
        $count = HqUser::where('user_id','=',$id)->update(['del_flag'=>1]);
        if ($count!==false){
            return ['msg'=>'操作成功','status'=>1];
        }else{
            return ['msg'=>'操作失败','status'=>0];
        }
    }
}