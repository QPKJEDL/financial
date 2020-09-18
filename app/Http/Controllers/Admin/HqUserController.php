<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StoreRequest;
use App\Models\Agent;
use App\Models\Billflow;
use App\Models\Czrecord;
use App\Models\SysBalance;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\HqUser;
use App\Models\UserAccount;
use Illuminate\Support\Facades\Auth;
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
        if (true==$request->has('limit'))
        {
            $limit = $request->input('limit');
        }
        else
        {
            $limit = 10;
        }
        if (""==$request->input('account') && ""==$request->input('nickname') && ""==$request->input('user_type'))
        {
            $data = HqUser::where('user_id','<',0)->paginate($limit)->appends($request->all());
        }else{
            if(true==$request->has('account')){
                $map['user.account']=$request->input('account');
            }
            if (true==$request->has('user_type'))
            {
                if ($request->input('user_type')==1 || $request->input('user_type')==2)
                {
                    $map['user.user_type']=$request->input('user_type');
                }
            }
            $sql = HqUser::where($map);
            $sql->leftJoin('agent_users','agent_users.id','=','user.agent_id')
                ->select('user.*','agent_users.username','agent_users.nickname as agentName')
                ->where($map);
            if(true==$request->has('nickname')){
                $sql->where('user.nickname','like','%'.$request->input('nickname').'%');
            }

            $data = $sql->orderBy('creatime','desc')->paginate($limit)->appends($request->all());
            foreach($data as $key=>&$value){
                $data[$key]['creatime']=date("Y-m-d H:m:s",$value['creatime']);
                if ($value['user_type']==1){
                    $data[$key]['fee']=json_decode($value['fee'],true);
                }
                $data[$key]['userAccount']=UserAccount::getUserAccountInfo($value['user_id']);
                $data[$key]['cz']=$this->getUserCzCord($value['user_id']);
            }
        }
        return view('hquser.list',['list'=>$data,'input'=>$request->all(),'limit'=>$limit]);
    }

    /**
     * 获取用户最近充值记录
     * @param $userId
     * @return Czrecord|\Illuminate\Database\Eloquent\Model|null
     */
    public function getUserCzCord($userId){
        $bill = new Billflow();
        $bill->setTable('user_billflow_'.date('Ymd',time()));
        $data = $bill->where('user_id','=',$userId)->orderBy('creatime','desc')->where('status','=',1)->first();
        return $data['score'];
    }

    /**
     * 状态封禁
     * @param StoreRequest $request
     * @return array
     */
    public function changeStatus(StoreRequest $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');
        $count = HqUser::where('user_id','=',$id)->update(['is_over'=>$status]);
        if ($count)
        {
            return ['msg'=>'操作成功','status'=>1];
        }
        else
        {
            return ['msg'=>'操作失败','status'=>0];
        }
    }

    /**
     * 编辑
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function edit($id)
    {
        $data = $id?HqUser::find($id):[];
        $data['limit']=json_decode($data['limit'],true);
        $data['fee']=json_decode($data['fee'],true);
        $data['nnbets_fee'] = json_decode($data['nnbets_fee'],true);
        $data['lhbets_fee'] = json_decode($data['lhbets_fee'],true);
        $data['bjlbets_fee'] = json_decode($data['bjlbets_fee'],true);
        $data['a89bets_fee'] = json_decode($data['a89bets_fee'],true);
        $data['sgbets_fee'] = json_decode($data['sgbets_fee'],true);
        return view('hquser.edit',['info'=>$data,'id'=>$id]);
    }

    /**
     * 编辑保存
     * @param StoreRequest $request
     * @return array
     */
    public function updateSave(StoreRequest $request)
    {
        $data = $request->all();
        $id = $data['id'];
        $map['nickname']=$data['nickname'];
        /*$bjl['player']=intval($data['bjlbets_fee']['player'] * 100);
        $bjl['playerPair']=intval($data['bjlbets_fee']['playerPair'] * 100);
        $bjl['tie']=intval($data['bjlbets_fee']['tie'] * 100);
        $bjl['banker']=intval($data['bjlbets_fee']['banker'] *100);
        $bjl['bankerPair']=intval($data['bjlbets_fee']['bankerPair'] * 100);
        $data['bjlbets_fee']=json_encode($bjl);
        $lh['dragon']=intval($data['lhbets_fee']['dragon'] * 100);
        $lh['tie']=intval($data['lhbets_fee']['tie'] *100);
        $lh['tiger']=intval($data['lhbets_fee']['tiger']*100);
        $data['lhbets_fee']=json_encode($lh);
        $nn['Equal']=intval($data['nnbets_fee']['Equal'] *100);
        $nn['Double']=intval($data['nnbets_fee']['Double'] *100);
        $nn['SuperDouble']=intval($data['nnbets_fee']['SuperDouble']*100);
        $data['nnbets_fee']=json_encode($nn);
        $sg['Equal']=intval($data['sgbets_fee']['Equal']*100);
        $sg['Double']=intval($data['sgbets_fee']['Double']*100);
        $sg['SuperDouble']=intval($data['sgbets_fee']['SuperDouble']*100);
        $data['sgbets_fee']=json_encode($sg);
        $a89['Equal']=intval($data['a89bets_fee']['Equal']*100);
        $a89['Double']=95;
        $a89['SuperDouble']=intval($data['a89bets_fee']['SuperDouble']*100);
        $data['a89bets_fee']=json_encode($a89);
        $map['bjlbets_fee']=json_encode($data['bjlbets_fee']);
        $map['lhbets_fee']=json_encode($data['lhbets_fee']);
        $map['nnbets_fee']=json_encode($data['nnbets_fee']);
        $map['sgbets_fee']=json_encode($data['sgbets_fee']);
        $map['a89bets_fee']=json_encode($data['a89bets_fee']);*/
        $count = HqUser::where('user_id','=',$id)->update($map);
        if (!$count)
        {
            return ['msg'=>'操作失败','status'=>0];
        }
        return ['msg'=>'操作成功','status'=>1];
    }

    /**
     * 上分页面
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function topCode($id)
    {
        $info = UserAccount::where('user_id','=',$id)->first();
        $balance = SysBalance::where('id','=',1)->first();
        return view('hquser.code',['info'=>$info,'id'=>$id,'balance'=>$balance['balance'],'user'=>$id?HqUser::find($id):[]]);
    }

    /**
     * 修改密码
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function resetPwd($id)
    {
        return view('hquser.resetPwd',['id'=>$id]);
    }

    /**
     * 保存修改密码
     * @param StoreRequest $request
     * @return array
     */
    public function updatePassword(StoreRequest $request)
    {
        $data = $request->all();
        if ($data['password']!=$data['newPwd'])
        {
            return ['msg'=>'两次密码不一致','status'=>0];
        }
        $info = $data['id']?HqUser::find($data['id']):[];
        if (md5($data['password'])==$info['password'])
        {
            return ['msg'=>'不能与之前的密码一直','status'=>0];
        }
        $count = HqUser::where('user_id','=',$data['id'])->update(['password'=>md5($data['password'])]);
        if (!$count)
        {
            return ['msg'=>'操作失败','status'=>0];
        }
        return ['msg'=>'操作成功','status'=>1];
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
        if ($data['balance']<=0){
            return ['msg'=>'金额必须大于0','status'=>0];
        }
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
                $userInfo = $id?HqUser::find($id):[];
                $bill->setTable('user_billflow_'.date('Ymd',time()));
                $betBefore = $info['balance'];
                if ($data['type']==1){//上分
                    $res = UserAccount::where('user_id','=',$id)->update(['balance'=>$betBefore +($data['balance'] * 100)]);
                    if ($res){
                        $sj = $userInfo['agent_id']?Agent::find($userInfo['agent_id']):[];
                        $ancestors = explode(',',$sj['ancestors']);
                        $ancestors[] = $sj['id'];
                        $zs = $ancestors[1]?Agent::find($ancestors[1]):[];
                        $result = $bill->insert(['user_id'=>$id,'nickname'=>$userInfo['nickname'],'agent_name'=>$sj['nickname'],'fir_name'=>$zs['nickname'],'order_sn'=>$this->getrequestId(),'score'=>$data['balance']*100,'bet_before'=>$betBefore,'bet_after'=>$betBefore+($data['balance']*100),'status'=>1,'pay_type'=>$data['payType'],'remark'=>Auth::user()['username'].'[财务后台直接上分]','creatime'=>time(),'create_by'=>Auth::id()]);
                        if ($result){
                            $b = SysBalance::where('id','=',1)->decrement('balance',$data['balance']*100);
                            if (!$b){
                                $this->unRedisUserLock($id);
                                DB::rollBack();
                                return  ['msg'=>'操作失败','status'=>0];
                            }
                            $userInfo = $id?HqUser::find($id):[];
                            $count = Czrecord::insertRecord($userInfo['draw_name'],$id,Auth::id(),$data['balance']*100,$data['payType']);
                            if (!$count)
                            {
                                DB::rollBack();
                                $this->unRedisUserLock($id);
                                return ['msg'=>'操作失败','status'=>0];
                            }
                            DB::commit();
                            $this->unRedisUserLock($id);
                            $this->dataByHttpsPost($id,$data['balance']*100,1);
                            return ['msg'=>'操作成功','status'=>1,'type'=>1,'userName'=>$id?HqUser::find($id):[]['nickname']];
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
                    if ($balance['balance'] < $data['balance']*100){
                        return ['msg'=>'金额不足，不能提现','status'=>0];
                    }else{
                        $res = UserAccount::where('user_id','=',$id)->update(['balance'=>$betBefore -$data['balance'] * 100]);
                        if ($res){
                            $sj = $userInfo['agent_id']?Agent::find($userInfo['agent_id']):[];
                            $ancestors = explode(',',$sj['ancestors']);
                            $ancestors[] = $sj['id'];
                            $zs = $ancestors[1]?Agent::find($ancestors[1]):[];
                            $result = $bill->insert(['user_id'=>$id,'nickname'=>$userInfo['nickname'],'agent_name'=>$sj['nickname'],'fir_name'=>$zs['nickname'],'order_sn'=>$this->getrequestId(),'score'=>$data['balance']*100,'bet_before'=>$betBefore,'bet_after'=>$betBefore-abs($data['balance']*100),'status'=>3,'pay_type'=>0,'remark'=>Auth::user()['username'].'[财务后台直接下分]','creatime'=>time(),'create_by'=>Auth::id()]);
                            if ($result){
                                $b = SysBalance::where('id','=',1)->increment('balance',$data['balance']*100);
                                if (!$b)
                                {
                                    DB::rollBack();
                                }
                                DB::commit();
                                $this->unRedisUserLock($id);
                                $this->dataByHttpsPost($id,$data['balance']*100,2);
                                return ['msg'=>'操作成功','status'=>1,'type'=>2,'userName'=>$id?HqUser::find($id):[]['nickname']];
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

        Redis::rPush('hq_user_code_lock_'.$userId,$code);
        Redis::expire('hq_user_code_lock_'.$userId,5);
        //锁出列
        $codes = Redis::LINDEX('hq_user_code_lock_'.$userId,0);
        if ($code!=$codes){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 推送消息
     * @param $userId
     * @param $balance
     * @param $type
     */
    public function dataByHttpsPost($userId,$balance,$type)
    {
        $url = "http://119.28.60.221:8210/postpeermessage";
        $money = UserAccount::where('user_id','=',$userId)->first();
        $arr['uid']=$userId;
        $arr['appid']=(int)1;
        $data['Cmd']=(int)31;
        $data['Money']=(float)$balance/100;
        $data['Balance']=(float)$money['balance']/100;
        $data['Type']=(int)$type;
        $arr['content']=json_encode($data);
        $this->https_post_kf($url,$arr);
    }

    //http 请求
    private function https_post_kf($url, $data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        //curl_setopt($curl, CURLOPT_USERAGENT, "Dalvik/1.6.0 (Linux; U; Android 4.1.2; DROID RAZR HD Build/9.8.1Q-62_VQW_MR-2)");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        if (curl_errno($curl)) {
            return 'Errno' . curl_error($curl);
        }
        curl_close($curl);
        return $result;
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

    /**
     * 会员关系结构
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function userRelation($id)
    {
        $user = (int)$id?HqUser::find((int)$id):[];
        $agentInfo = $user['agent_id']?Agent::find($user['agent_id']):[];
        $ancestors = explode(',',$agentInfo['ancestors']);
        unset($ancestors[0]);
        $ancestors[]=$agentInfo['id'];
        $data = Agent::query()->whereIn('id',$ancestors)->get();
        return view('hquser.userRelation',['info'=>$user,'parent'=>$data]);
    }
}