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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

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
        $map['userType']=1;
        $map['del_flag']=0;
        if (true==$request->has('username'))
        {
            $map['username']=$request->input('username');
        }
        $sql = Agent::query();
        if (true==$request->has('nickname')) {
           $sql->where('nickname','like','%'.$request->input('nickname').'%');
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
            $data = $sql->where($map)->paginate(10)->appends($request->all());
            foreach ($data as $key=>$value){
                $data[$key]['agentCount']=$this->getSubordinateCount($value['id']);
                $data[$key]['userCount']=$this->getHqUserCount($value['id']);
                $data[$key]['fee']=json_decode($value['fee'],true);
                $data[$key]['groupBalance']=$this->getGroupBalance($value['id']);
            }
        }
        return view('agent.list',['list'=>$data,'input'=>$request->all()]);
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
        if ($data['password']!=$data['newPwd'])
        {
            return ['msg'=>'两次密码输入不一致','status'=>0];
        }
        else
        {
            $password = bcrypt($data['password']);
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
        $data = $id?Agent::find($id):[];
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
        $roleId = $request->input('user_role');
        unset($data['id']);
        unset($data['_token']);
        unset($data['user_role']);
        //判断两次密码是否相同
        if($data['pwd']!=$data['pwd_confirmation']){
            return ['msg'=>'两次密码不同','status'=>0];
        }
        //密码加密
        $data['password']=bcrypt($data['pwd']);
        unset($data['pwd']);
        unset($data['pwd_confirmation']);
        $data['created_at']=date('Y-m-d H:i:s',time());
        $data['fee']=json_encode($data['fee']);
        $data['limit']=json_encode($data['limit']);
        $data['bjlbets_fee']=json_encode($data['bjlbets_fee']);
        $data['lhbets_fee']=json_encode($data['lhbets_fee']);
        $data['nnbets_fee']=json_encode($data['nnbets_fee']);
        $data['a89bets_fee']=json_encode($data['a89bets_fee']);
        $data['sgbets_fee']=json_encode($data['sgbets_fee']);
        $data["ancestors"]=0;
        $count = Agent::insertGetId($data);
        if ($count){
            $this->insertUserRole($count,$roleId);
            return ['msg'=>'操作成功','status'=>1];
        }else{
            return ['msg'=>'操作失败','status'=>0];
        }

    }
    /*
     * 编辑
     */
    public function update(StoreRequest $request){
        $id = $request->input('id');
        $data = $request->all();
        $roleId = $request->input('user_role');
        unset($data['_token']);
        unset($data['id']);
        unset($data['user_role']);
        if(empty($data["pwd"])){
            unset($data['pwd']);
            unset($data['pwd_confirmation']);
        }
        $data['fee']=json_encode($data['fee']);
        $data['limit']=json_encode($data['limit']);
        $data['bjlbets_fee']=json_encode($data['bjlbets_fee']);
        $data['lhbets_fee']=json_encode($data['lhbets_fee']);
        $data['nnbets_fee']=json_encode($data['nnbets_fee']);
        $data['a89bets_fee']=json_encode($data['a89bets_fee']);
        $data['sgbets_fee']=json_encode($data['sgbets_fee']);
        $data['userType']=1;
        $up=Agent::where('id',$id)->update($data);
        if($up!==false){
            AgentRoleUser::where('user_id',$id)->update(array('role_id'=>$roleId));
            return ['msg'=>'修改成功','status'=>1];
        }else{
            return ['msg'=>'修改失败','status'=>0];
        }
    }

    public function destroy($id)
    {
        /*$count = Agent::where('id','=',$id)->update(['del_flag'=>1]);
        if ($count!==false)
        {
            return ['msg'=>'操作成功','status'=>1];
        }else{
            return ['msg'=>'操作失败','status'=>0];
        }*/
        DB::beginTransaction();
        try {
            $agentIdArray = array();
            $agentIdArray[] =$id;
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
        $stop = Agent::where('id','=',$id)->update(array("status"=>1));
        if($stop){
            return ['msg'=>'停用成功','status'=>1];
        }else{
            return ['msg'=>'停用失败','status'=>0];
        }
    }
    /*
     * 启用
     */
    public function start(StoreRequest $request){
        $id=$request->input('id');
        $stop = Agent::where('id','=',$id)->update(array("status"=>0));
        if($stop){
            return ['msg'=>'启用成功','status'=>1];
        }else{
            return ['msg'=>'启用失败','status'=>0];
        }
    }

    /*
    * 添加代理
    */
    public function insertUserRole($agentId,$roleId){
        $data['user_id']=$agentId;
        $data['role_id']=$roleId;
        AgentRoleUser::insert($data);
    }

    /**
     * 获取下级代理的个数
     * @param $id
     * @return int
     */
    public function getSubordinateCount($id)
    {
        return Agent::where('parent_id','=',$id)->count();
    }

    public function getHqUserCount($id){
        return HqUser::where('agent_id','=',$id)->count();
    }

    /**
     * 下级代理
     * @param $id
     * @param Request $request
     */
    public function getSubordinateAgentList($id,Request $request){
        $map = array();
        $map['parent_id']=$id;
        $map['del_flag']=0;
        if (true==$request->has('username'))
        {
            $map['username']=$request->input('username');
        }
        $sql = Agent::query();
        if (true==$request->has('nickname')) {
            $sql->where('nickname','like','%'.$request->input('nickname').'%');
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
            $data = $sql->where($map)->paginate(10)->appends($request->all());
            foreach ($data as $key=>$value){
                $data[$key]['fee']=json_decode($value['fee'],true);
                $data[$key]['groupBalance']=$this->getGroupBalance($value['id']);
            }
        }
        return view('agent.list',['list'=>$data,'input'=>$request->all()]);
    }

    /**
     * 下级会员列表
     * @param $id
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function user($id,Request $request){
        $map = array();
        $map['agent_id']=$id;
        if(true==$request->has('account')){
            $map['account']=$request->input('account');
        }
        $user = HqUser::query();
        $sql = $user->leftJoin('agent_users','user.agent_id','=','agent_users.id')
            ->leftJoin('user_account','user.user_id','=','user_account.user_id')
            ->select('user.*','agent_users.nickname as agentName','user_account.balance')->where($map);
        if(true ==$request->has('nickname')){
            $sql->where('user.nickname','like','%'.$request->input('nickname').'%');
        }
        $data = $sql->paginate(10)->appends($request->all());
        foreach($data as $key=>&$value){
            $data[$key]['cz']=$this->getUserCzCord($value['user_id']);
            $data[$key]['fee']=json_decode($data[$key]['fee'],true);
            $data[$key]['creatime']=date('Y-m-d H:i:s',$value['creatime']);
        }
        return view('agent.userList',['list'=>$data,'input'=>$request->all()]);
    }

    /**
     * 充值界面
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function czEdit($id){
        $info = $id?Agent::find($id):[];
        return view('agent.cz',['info'=>$info,'id'=>$id]);
    }

    public function updateBalance(StoreRequest $request)
    {
        $data = $request->all();
        $redisLock = $this->redissionLock($data['id']);
        if ($redisLock){
            unset($data['_token']);
            $id = $data['id'];
            unset($data['id']);
            $data['balance']=$data['balance']*100;
            DB::beginTransaction();
            try {
                $bool = Agent::where('id','=',$id)->lockForUpdate()->first();
                if ($bool){
                    $count = Agent::where('id','=',$id)->increment('balance',$data['balance']);
                    if ($count){
                        $result = $this->insertAgentBillFlow($id,0,$data['balance'],$bool['balance'],$bool['balance'] + $data['balance'],$data['type'],$data['payType'],"财务后台直接充值");
                        if ($result){
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
                    DB::rollBack();
                    $this->unRedissLock($id);
                    return ['msg'=>'操作失败','status'=>0];
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
        $data['agent_id']=$agentId;
        $data['user_id']=$userId;
        $data['money']=$money;
        $data['bet_before']=$before;
        $data['bet_after']=$after;
        $data['status']=$status;
        $data['type']=$type;
        $data['remark']=$remark;
        $data['creatime']=time();
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
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection
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