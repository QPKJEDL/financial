<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequest;
use App\Models\Agent;
use App\Models\AgentBill;
use App\Models\AgentRole;
use App\Models\AgentRoleUser;
use App\Models\Czrecord;
use App\Models\Desk;
use App\Models\HqUser;
use App\Models\SysBalance;
use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\View\View;
use const http\Client\Curl\Features\HTTP2;

class AgentListController extends Controller
{
    /**
     * 数据列表
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function index(Request $request){
        $map = array();
        $map['parent_id']=0;
        //$map['userType']=1;
        $map['del_flag']=0;
        if (true==$request->has('username'))
        {
            $map['username']=HttpFilter($request->input('username'));
        }
        if (true==$request->has('userType'))
        {
            $map['userType']=(int)$request->input('userType');
        }
        $sql = Agent::query();
        if (true==$request->has('nickname')) {
           $sql->where('nickname','like','%'.HttpFilter($request->input('nickname')).'%');
        }
        if (true==$request->has('excel') && true==$request->input('excel')){
            $excel = $sql->select('id','username','nickname','balance','ancestors','fee','proportion','pump','created_at')
                ->where($map)->get()->toArray();
            foreach ($excel as $key=>$value){
                $excel[$key]['ancestors'] = $this->getGroupBalance($value['id']);
                $data = json_decode($value['fee'],true);
                $excel[$key]['fee']=$data['baccarat'].'%/'.$data['dragonTiger'].'%/'.$data['niuniu'].'%/'.$data['sangong'].'%/'.$data['A89'].'%';
            }
            $head = array('ID','代理账号','姓名','账户余额','群组余额','百/龙/牛/三/A','占成(%)','抽水百分比','创建时间');
            try {
                exportExcel($head, $excel, '代理列表', '', true);
            } catch (\PHPExcel_Reader_Exception $e) {
            } catch (\PHPExcel_Exception $e) {
            }
        }else{
            if (true==$request->has('limit'))
            {
                $limit = $request->input('limit');
            }
            else
            {
                $limit = 10;
            }
            $data = $sql->where($map)->orderBy('created_at','desc')->paginate($limit)->appends($request->all());
            $sumBalance=0;
            foreach ($data as $key=>$value){
                $data[$key]['agentCount']=$this->getSubordinateCount($value['id']);
                $data[$key]['userCount']=$this->getHqUserCount($value['id']);
                if ($value['userType']==1)
                {
                    $data[$key]['fee']=json_decode($value['fee'],true);
                }
                $data[$key]['groupBalance']=$this->getGroupBalance($value['id']);
                $sumBalance = $sumBalance + $value['groupBalance'];
            }
        }
        $balance = SysBalance::where('id','=',1)->first()['balance'];
        return view('agent.list',['list'=>$data,'input'=>$request->all(),'limit'=>$limit,'balance'=>$balance,'sumBalance'=>$sumBalance]);
    }

    /**
     * 修改密码编辑页
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function resetPwd($id)
    {
        return view('agent.resetpwd',['id'=>$id]);
    }

    /**
     * 修改保存密码
     * @param StoreRequest $request
     * @return array
     */
    public function saveResetPwd(StoreRequest $request)
    {
        $data = $request->all();
        $id = $data['id'];
        if (HttpFilter($data['password'])!=HttpFilter($data['newPwd']))
        {
            return ['msg'=>'两次密码输入不一致','status'=>0];
        }
        else
        {
            $password = bcrypt(HttpFilter($data['password']));
            $count = Agent::where('id','=',$id)->update(['password'=>$password]);
            if ($count!==false)
            {
                return ['msg'=>'操作成功','status'=>1];
            }
            else
            {
                return ['msg'=>'操作失败','status'=>0];
            }
        }
    }

    /**
     * 编辑页
     */
    public function edit($id=0){
        $data = (int)$id?Agent::find((int)$id):[];
        $info = AgentRoleUser::where('user_id','=',$id)->first();
        if($id!=0){
            $data['fee']=json_decode($data['fee'],true);
            $data['limit']=json_decode($data['limit'],true);
            $data['bjlbets_fee']=json_decode($data['bjlbets_fee'],true);
            $data['lhbets_fee']=json_decode($data['lhbets_fee'],true);
            $data['nnbets_fee']=json_decode($data['nnbets_fee'],true);
            $data['a89bets_fee']=json_decode($data['a89bets_fee'],true);
            $data['sgbets_fee']=json_decode($data['sgbets_fee'],true);
        }
        return view('agent.edit',['id'=>$id,'roles'=>AgentRole::all(),'info'=>$data,'userRole'=>$info]);
    }

    public function store(StoreRequest $request)
    {
        $data = $request->all();
        $roleId = 38;
        unset($data['id']);
        unset($data['_token']);
        unset($data['user_role']);
        //判断两次密码是否相同
        if(HttpFilter($data['pwd'])!=HttpFilter($data['pwd_confirmation'])){
            return ['msg'=>'两次密码不同','status'=>0];
        }
        if ((int)$data['limit']['min']>50000 || (int)$data['limit']['min']<10){
            return ['msg'=>'限红错误','status'=>0];
        }
        if ((int)$data['limit']['max']>50000 || (int)$data['limit']['max']<10){
            return ['msg'=>'限红错误','status'=>0];
        }
        if ((int)$data['limit']['tieMin']>5000 || (int)$data['limit']['tieMin']<10){
            return ['msg'=>'限红错误','status'=>0];
        }
        if ((int)$data['limit']['tieMax']>5000 || (int)$data['limit']['tieMax']<10){
            return ['msg'=>'限红错误','status'=>0];
        }
        if ((int)$data['limit']['pairMin']>5000 || (int)$data['limit']['pairMin']<10){
            return ['msg'=>'限红错误','status'=>0];
        }
        if ((int)$data['limit']['pairMax']>5000 || (int)$data['limit']['pairMax']<10){
            return ['msg'=>'限红错误','status'=>0];
        }
        //密码加密
        $data['password']=bcrypt(HttpFilter($data['pwd']));
        unset($data['pwd']);
        unset($data['pwd_confirmation']);
        $data['data_permission']=2;
        $data['userType']=1;
        $data['created_at']=date('Y-m-d H:i:s',time());
        $data['fee']=json_encode($data['fee']);
        $data['limit']=json_encode($data['limit']);
        $data['bjlbets_fee']=json_encode($data['bjlbets_fee']);
        $data['lhbets_fee']=json_encode($data['lhbets_fee']);
        $data['nnbets_fee']=json_encode($data['nnbets_fee']);
        $data['a89bets_fee']=json_encode($data['a89bets_fee']);
        $data['sgbets_fee']=json_encode($data['sgbets_fee']);
        if(!empty($data['is_allow'])){
            $data['is_allow']=1;
        }
        if (!empty($data['is_allow_draw']))
        {
            $data['is_allow_draw']=1;
        }
        if (!empty($data['is_allow_password']))
        {
            $data['is_allow_password']=2;
        }
        $data["ancestors"]=0;
        $count = Agent::insertGetId($data);
        if ($count){
            $this->insertUserRole($count,$roleId);
            return ['msg'=>'操作成功','status'=>1];
        }else{
            return ['msg'=>'操作失败','status'=>0];
        }
    }

    /**
     * 效验代理账户是否存在
     * @param StoreRequest $request
     * @return array
     */
    public function accountUnique(StoreRequest $request)
    {
        $account = HttpFilter($request->input('account'));
        if (Agent::where('username','=',$account)->exists())
        {
            return ['msg'=>$account.'账户已存在','status'=>0];
        }
        return ['msg'=>'可以使用','status'=>1];
    }
    /*
     * 编辑
     */
    public function update(StoreRequest $request){
        DB::beginTransaction();
        try {
            $id = (int)$request->input('id');
            $data = $request->all();
            $roleId = 38;
            unset($data['_token']);
            unset($data['id']);
            unset($data['user_role']);
            if(empty($data["pwd"])){
                unset($data['pwd']);
                unset($data['pwd_confirmation']);
            }
            $data['fee']=json_encode($data['fee']);
            if ((int)$data['limit']['min']>50000 || (int)$data['limit']['min']<10){
                return ['msg'=>'限红错误','status'=>0];
            }
            if ((int)$data['limit']['max']>50000 || (int)$data['limit']['max']<10){
                return ['msg'=>'限红错误','status'=>0];
            }
            if ((int)$data['limit']['tieMin']>5000 || (int)$data['limit']['tieMin']<10){
                return ['msg'=>'限红错误','status'=>0];
            }
            if ((int)$data['limit']['tieMax']>5000 || (int)$data['limit']['tieMax']<10){
                return ['msg'=>'限红错误','status'=>0];
            }
            if ((int)$data['limit']['pairMin']>5000 || (int)$data['limit']['pairMin']<10){
                return ['msg'=>'限红错误','status'=>0];
            }
            if ((int)$data['limit']['pairMax']>5000 || (int)$data['limit']['pairMax']<10){
                return ['msg'=>'限红错误','status'=>0];
            }
            $data['limit']=json_encode($data['limit']);
            $data['bjlbets_fee']=json_encode($data['bjlbets_fee']);
            $data['lhbets_fee']=json_encode($data['lhbets_fee']);
            $data['nnbets_fee']=json_encode($data['nnbets_fee']);
            $data['a89bets_fee']=json_encode($data['a89bets_fee']);
            $data['sgbets_fee']=json_encode($data['sgbets_fee']);
            $data['userType']=1;
            if (!empty($data['is_allow']))
            {
                $data['is_allow']=1;
            }
            else
            {
                $data['is_allow']=2;
                $idAgentIdArr = Agent::query()->select('id')->whereRaw('FIND_IN_SET(?,ancestors)',[$id])->get()->toArray();
                foreach ($idAgentIdArr as $key=>$value)
                {
                    $res = Agent::query()->where('id','=',$value['id'])->update(['is_allow'=>2]);
                    if ($res!==false)
                    {
                        continue;
                    }
                    DB::rollback();
                    return ['msg'=>'操作失败1','status'=>0];
                }
            }
            if (!empty($data['is_allow_draw']))
            {
                $data['is_allow_draw']=1;
            }
            else
            {
                $data['is_allow_draw']=2;
                $idAgentIdArr = Agent::query()->select('id')->whereRaw('FIND_IN_SET(?,ancestors)',[$id])->get()->toArray();
                foreach ($idAgentIdArr as $key=>$value)
                {
                    $res = Agent::query()->where('id','=',$value['id'])->update(['is_allow_draw'=>2]);
                    if ($res!==false)
                    {
                        continue;
                    }
                    DB::rollback();
                    return ['msg'=>'操作失败2','status'=>0];
                }
            }
            if (!empty($data['is_allow_password']))
            {
                $data['is_allow_password']=2;
                $idAgentIdArr = Agent::query()->select('id')->whereRaw('FIND_IN_SET(?,ancestors)',[$id])->get()->toArray();
                foreach ($idAgentIdArr as $key=>$value)
                {
                    $res = Agent::query()->where('id','=',$value['id'])->update(['is_allow_password'=>2]);
                    if ($res!==false)
                    {
                        continue;
                    }
                    DB::rollback();
                    return ['msg'=>'操作失败3','status'=>0];
                }
            }
            else
            {
                $data['is_allow_password']=1;
            }
            $up=Agent::where('id',$id)->update($data);
            if($up!==false){
                 DB::commit();
                 return ['msg'=>'修改成功','status'=>1];
            }else{
                DB::rollback();
                return ['msg'=>'修改失败4','status'=>0];
            }
        }catch (\Exception $exception)
        {
            DB::rollback();
            return ['msg'=>'操作失败5','status'=>0];
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $agentIdArray = array();
            $agentIdArray[] =(int)$id;
            $data = Agent::select('id')->whereRaw('FIND_IN_SET('.$id.',ancestors)')->get();
            foreach ($data as $key=>$value)
            {
                $agentIdArray[]=$value['id'];
            }
            $userIdArr = array();
            $userData = HqUser::select('user_id')->where('del_flag','=',0)->whereIn('agent_id',$agentIdArray)->get();
            foreach ($userData as $key=>$datum)
            {
                $userIdArr[] = $datum['user_id'];
            }
            $count = Agent::whereIn('id',$agentIdArray)->update(['del_flag'=>1]);
            if ($count)
            {
                if (count($userIdArr)>0)
                {
                    $bool = HqUser::whereIn('user_id',$userIdArr)->update(['del_flag'=>1]);
                    if ($bool)
                    {
                        DB::commit();
                        return ['msg'=>'操作成功','status'=>1];
                    }
                    else
                    {
                        DB::rollBack();
                        return ['msg'=>'操作失败','status'=>0];
                    }
                }
                else
                {
                    DB::commit();
                    return ['msg'=>'操作成功','status'=>1];
                }
            }
            else
            {
                DB::rollBack();
                return ['msg'=>'操作失败','status'=>0];
            }
        }catch (\Exception $e)
        {
            DB::rollBack();
            return ['msg'=>'操作失败','status'=>0];
        }
    }

    /*
     * 停用
     */
    public function stop(StoreRequest $request){
        $id=$request->input('id');
        //开启事务
        DB::beginTransaction();
        try {
            //获取所有下级代理id
            $idArr = Agent::query()->select('id')->where('id','=',$id)->orWhereRaw('FIND_IN_SET(?,ancestors)',[$id])->where('status','=',0)->get()->toArray();
            //获取所有下级代理id
            $userIdArr = HqUser::query()->select('user_id as id')->whereIn('agent_id',$idArr)->where('is_over','=',0)->get()->toArray();
            //修改状态
            $count = Agent::query()->whereIn('id',$idArr)->update(['status'=>1]);
            if ($count!=count($idArr)){
                DB::rollback();
                return ['msg'=>'操作失败','status'=>0];
            }
            $result = HqUser::query()->whereIn('user_id',$userIdArr)->update(['is_over'=>1]);
            if ($result!=count($userIdArr))
            {
                DB::rollback();
                return ['msg'=>'操作失败','status'=>0];
            }
            $arr = array();
            $arr['user']=$userIdArr;
            $arr['agent']=$idArr;
            $arr = json_encode($arr);
            $num = Agent::where('id','=',$id)->update(['disable_json'=>$arr]);
            if ($num!==false)
            {
                DB::commit();
                $redis = Redis::connection('redis2');
                foreach ($idArr as $v){
                    $userdata=$redis->get('UserInfo_'.$v["id"]);
                    $userinfo=json_decode($userdata,true);
                    $userinfo["Token"]='11111111111111111111';
                    $new=json_encode($userinfo);
                    $redis->set('UserInfo_'.$id,$new);
                }
                return ['msg'=>'操作成功','status'=>1];
            }
            else
            {
                DB::rollback();
                return ['msg'=>'操作失败','status'=>0];
            }
        }catch (\Exception $exception)
        {
            DB::rollback();
            return ['msg'=>'操作失败1','status'=>0];
        }
    }
    /*
     * 启用
     */
    public function start(StoreRequest $request){
        $id=$request->input('id');
        DB::beginTransaction();
        try {
            $info = $id?Agent::find($id):[];
            $idArr = json_decode($info['disable_json'],true);
            $user = $idArr['user'];
            $agent = $idArr['agent'];
            //修改代理
            $count = Agent::query()->whereIn('id',$agent)->update(['status'=>0]);
            if (count($agent)!=$count)
            {
                DB::rollback();
                return ['msg'=>'操作失败','status'=>0];
            }
            $result = HqUser::query()->whereIn('user_id',$user)->update(['is_over'=>0]);
            if ($result!=count($user))
            {
                DB::rollback();
                return ['msg'=>'操作失败','status'=>0];
            }
            DB::commit();
            return ['msg'=>'操作成功','status'=>1];
        }catch (\Exception $exception)
        {
            DB::rollback();
            return ['msg'=>'操作失败','status'=>0];
        }
    }

    /*
    * 添加代理
    */
    public function insertUserRole($agentId,$roleId){
        $data['user_id']=(int)$agentId;
        $data['role_id']=(int)$roleId;
        AgentRoleUser::insert($data);
    }

    /**
     * 获取下级代理的个数
     * @param $id
     * @return int
     */
    public function getSubordinateCount($id)
    {
        return Agent::where('parent_id','=',(int)$id)->count();
    }

    public function getHqUserCount($id){
        return HqUser::where('agent_id','=',(int)$id)->count();
    }

    /**
     * 代理结构关系
     * @param $id
     * @return Factory|Application|View
     */
    public function getRelationalStruct($id){
        $info = (int)$id?Agent::find((int)$id):[];
        $arr = array();
        if ($info['parent_id']!=0){
            $data = explode(",",$info['ancestors']);
            unset($data[0]);
            foreach ($data as $key=>$value){
                $a = $value?Agent::find($value):[];
                $arr[] = $a;
            }
        }
        return view('agent.agentRelation',['info'=>$info,'parent'=>$arr]);
    }

    /**
     * 下级代理
     * @param $id
     * @param Request $request
     */
    public function getSubordinateAgentList($id,Request $request){
        $request->offsetSet('parent_id',$id);
        $map = array();
        $map['parent_id']=(int)$id;
        $map['del_flag']=0;
        if (true==$request->has('parent_id'))
        {
            $map['parent_id']=(int)$request->input('parent_id');
        }
        if (true==$request->has('username'))
        {
            $map['username']=HttpFilter($request->input('username'));
        }
        $sql = Agent::query();
        if (true==$request->has('nickname')) {
            $sql->where('nickname','like','%'.HttpFilter($request->input('nickname')).'%');
        }
        if (true==$request->has('excel') && true==$request->input('excel')){
            $excel = $sql->select('id','username','nickname','balance','ancestors','fee','proportion','created_at')
                ->where($map)->get()->toArray();
            foreach ($excel as $key=>$value){
                $excel[$key]['ancestors'] = $this->getGroupBalance($value['id']);
                $data = json_decode($value['fee'],true);
                $excel[$key]['fee']=$data['baccarat'].'%/'.$data['dragonTiger'].'%/'.$data['niuniu'].'%/'.$data['sangong'].'%/'.$data['A89'].'%';
            }
            $head = array('ID','代理账号','姓名','账户余额','群组余额','百/龙/牛/三/A','占成(%)','创建时间');
            try {
                exportExcel($head, $excel, '代理列表', '', true);
            } catch (\PHPExcel_Reader_Exception $e) {
            } catch (\PHPExcel_Exception $e) {
            }
        }else{
            if ($request->has('limit'))
            {
                $limit = (int)$request->input('limit');
            }
            else
            {
                $limit = 10;
            }
            $data = $sql->where($map)->paginate($limit)->appends($request->all());
            foreach ($data as $key=>$value){
                $data[$key]['agentCount']=$this->getSubordinateCount($value['id']);
                $data[$key]['userCount']=$this->getHqUserCount($value['id']);
                if ($value['userType']==1)
                {
                    $data[$key]['fee']=json_decode($value['fee'],true);
                }
                $data[$key]['groupBalance']=$this->getGroupBalance($value['id']);
            }
        }
        return view('agent.list',['list'=>$data,'input'=>$request->all(),'limit'=>$limit,'balance'=>0,'sumBalance'=>0]);
    }

    /**
     * 下级会员列表
     * @param $id
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function user($id,Request $request){
        $map = array();
        $map['user.agent_id']=(int)$id;
        $map['user.del_flag']=0;
        if(true==$request->has('account')){
            $map['user.account']=HttpFilter($request->input('account'));
        }
        $user = HqUser::query();
        $sql = $user->leftJoin('agent_users','user.agent_id','=','agent_users.id')
            ->leftJoin('user_account','user.user_id','=','user_account.user_id')
            ->select('user.*','agent_users.nickname as agentName','user_account.balance')->where($map);
        if(true ==$request->has('nickname')){
            $sql->where('user.nickname','like','%'.HttpFilter($request->input('nickname')).'%');
        }
        if (true==$request->has('limit'))
        {
            $limit = (int)$request->input('limit');
        }
        else
        {
            $limit = 10;
        }
        $data = $sql->paginate($limit)->appends($request->all());
        foreach($data as $key=>&$value){
            $data[$key]['cz']=$this->getUserCzCord($value['user_id']);
            $data[$key]['fee']=json_decode($data[$key]['fee'],true);
            $data[$key]['creatime']=date('Y-m-d H:i:s',$value['creatime']);
        }
        return view('agent.userList',['list'=>$data,'input'=>$request->all(),'limit'=>$limit]);
    }

    /**
     * 充值界面
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function czEdit($id){
        $info = (int)$id?Agent::find((int)$id):[];
        $balance = SysBalance::where('id','=',1)->first();
        return view('agent.cz',['info'=>$info,'id'=>$id,'balance'=>$balance['balance']]);
    }

    public function updateBalance(StoreRequest $request)
    {
        $data = $request->all();
        if ((int)$data['balance']<=0)
        {
            return ['msg'=>'金额不能小于0','status'=>0];
        }
        $redisLock = $this->redissionLock((int)$data['id']);
        if ($redisLock){
            unset($data['_token']);
            $id = (int)$data['id'];
            unset($data['id']);
            $data['balance']=(int)$data['balance']*100;
            DB::beginTransaction();
            try {
                if ($data['type']==1)//上分
                {
                    $bool = Agent::where('id','=',$id)->lockForUpdate()->first();
                    if ($bool){
                        $count = Agent::where('id','=',$id)->increment('balance',(int)$data['balance']);
                        if ($count){
                            $result = $this->insertAgentBillFlow($id,0,(int)$data['balance'],$bool['balance'],$bool['balance'] + $data['balance'],$data['type'],$data['payType'],Auth::user()['username']."[点击充值]");
                            if ($result){
                                SysBalance::where('id','=',1)->decrement('balance',$data['balance']);
                                DB::commit();
                                $this->unRedissLock($id);
                                return ['msg'=>'操作成功','status'=>1];
                            }else{
                                DB::rollBack();
                                $this->unRedissLock($id);
                                return ['msg'=>'操作失败','status'=>0];
                            }
                            DB::rollBack();
                            $this->unRedissLock($id);
                            return ['msg'=>'操作失败','status'=>0];
                        }else{
                            DB::rollBack();
                            $this->unRedissLock($id);
                            return ['msg'=>'操作失败','status'=>0];
                        }
                    }else{
                        DB::rollback();
                        $this->unRedissLock($id);
                        return ['msg'=>'操作失败','status'=>0];
                    }
                }
                else //下分
                {

                    $bool = Agent::where('id','=',$id)->lockForUpdate()->first();
                    if ($bool['balance']<$data['balance'])
                    {
                        DB::rollback();
                        $this->unRedisLock($id);
                        return ['msg'=>'余额不足，不能操作','status'=>0];
                    }
                    //修改余额 扣钱
                    $count = Agent::where('id','=',$id)->decrement('balance',$data['balance']);
                    if (!$count)
                    {
                        DB::rollBack();
                        $this->unRedissLock($id);
                        return ['msg'=>'操作失败','status'=>0];
                    }
                    $result = $this->insertAgentBillFlow($id,0,$data['balance'],$bool['balance'],$bool['balance'] - $data['balance'],$data['type'],0,Auth::user()['username'].'[点击提现]');
                    if (!$result)
                    {
                        DB::rollBack();
                        $this->unRedissLock($id);
                        return ['msg'=>'操作失败','status'=>0];
                    }
                    SysBalance::where('id','=',1)->increment('balance',$data['balance']);
                    DB::commit();
                    $this->unRedissLock($id);
                    return ['msg'=>'操作成功','status'=>1];
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
     * 插入代理流水
     * @param $agentId 代理id
     * @param $userId  用户id
     * @param $money   操作金额
     * @param $before  操作前金额
     * @param $after   操作后金额
     * @param $status  操作类型
     * @param $type    充值类型
     * @param $remark  备注
     * @return bool
     */
    public function insertAgentBillFlow($agentId,$userId,$money,$before,$after,$status,$type,$remark){
        $agentInfo = $agentId?Agent::find($agentId):[];
        $data['agent_id']=$agentId;
        $data['agent_name']=$agentInfo['nickname'];
        $data['user_id']=$userId;
        if ($agentInfo['parent_id']==0)
        {
            $data['top_name']=$agentInfo['nickname'];
            $data['fir_name']=$agentInfo['nickname'];
        }
        else
        {
            $sj = $agentInfo['parent_id']?Agent::find($agentInfo['parent_id']):[];
            $data['top_name']=$sj['nickname'];
            if ($sj['parent_id']==0)
            {
                $data['fir_name']=$sj['nickname'];
            }
            else
            {
                $ancestors = explode(',',$sj['ancestors']);
                $zs = $ancestors[1]?Agent::find($ancestors[1]):[];
                $data['fir_name']=$zs['nickname'];
            }
        }
        if ($status==1)
        {
            $data['money']=$money;
        }
        else
        {
            $data['money']=-$money;
        }
        $data['bet_before']=$before;
        $data['bet_after']=$after;
        $data['status']=$status;
        $data['type']=$type;
        $data['remark']=$remark;
        $data['creatime']=time();
        $data['create_by']=Auth::id();
        return AgentBill::insert($data);
    }
    /**
     * redis队列锁
     * @param $userId
     * @return bool
     */
    public function redissionLock($userId){
        $code=time().rand(100000,999999);
        //锁入列
        Redis::rPush('cz_cw_agent_lock_'.$userId,$code);
        Redis::expire('cz_cw_agent_lock_'.$userId,5);
        //锁出列
        $codes = Redis::LINDEX('cz_cw_agent_lock_'.$userId,0);
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
        Redis::del('cz_cw_agent_lock_'.$userId);
    }
    public function getGroupBalance($agentId){
        $agentList = Agent::get();
        $userList = $this->getHqUserList();
        $userMoney = $this->getAgentUserMoney($agentId,$userList);
        $info = $agentId?Agent::find($agentId):[];
        return $info['balance'] + $userMoney + $this->getRecursiveBalance($agentId,$agentList,$userList);
    }

    public function getRecursiveBalance($agentId,$agentList,$userList){
        $money = 0;
        $children = $this->getAgentChildrenList($agentId,$agentList);
        if(count($children) > 0){
            foreach ($children as $key=>$value){
                $money = $money + $value['balance'] + $this->getRecursiveBalance($value['id'],$agentList,$userList) + $this->getAgentUserMoney($value['id'],$userList);
            }
        }
        return $money;
    }

    public function getAgentInfo($agentId,$agentList){
        foreach ($agentList as $key=>$value){
            if ($value['id']=$agentId){
                return $agentList[$key]['balance'];
                break;
            }
        }
    }
    /**获取用户列表
     * @return Builder[]|Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection
     */
    public function getHqUserList(){
        $sql = HqUser::query();
        return $sql->leftJoin('user_account','user_account.user_id','=','user.user_id')
            ->select('user.user_id','user.agent_id','user_account.balance')->get();
    }
    /**
     * 获取用户最近充值记录
     */
    public function getUserCzCord($userId){
        $data = Czrecord::where('user_id',$userId)->orderBy('creatime','desc')->first();
        return $data;
    }

    public function getAgentUserMoney($agentId,$userList){
        $arr = array();
        foreach ($userList as $key=>$value){
            if($agentId==$value['agent_id']){
                $arr[] = $userList[$key];
            }
        }
        return $this->getMoneyByUserList($arr);
    }

    public function getMoneyByUserList($userList){
        $money = 0;
        foreach ($userList as $key=>$value){
            $money = $money + $value['balance'];
        }
        return $money;
    }
    public function getAgentChildrenList($agentId,$agentList){
        $arr = array();
        foreach ($agentList as $key=>$value){
            if ($agentId==$value['parent_id']){
                $arr[] = $agentList[$key];
            }
        }
        return $arr;
    }
}