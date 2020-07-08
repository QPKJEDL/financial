<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequest;
use App\Models\Agent;
use App\Models\Czrecord;
use App\Models\Desk;
use App\Models\HqUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        unset($data['_token']);
        $id = $data['id'];
        unset($data['id']);
        $data['balance']=$data['balance']*100;
        $bool = Agent::where('id','=',$id)->increment('balance',$data['balance']);
        if ($bool!==false){
            return ['msg'=>'操作成功','status'=>1];
        }else{
            return ['msg'=>'操作失败','status'=>0];
        }
    }
    public function getGroupBalance($agentId){
        $agentList = Agent::get();
        $userList = $this->getHqUserList();
        $userMoney = $this->getAgentUserMoney($agentId,$userList);
        $info = $this->getAgentInfo($agentId,$agentList);
        return $info + $userMoney + $this->getRecursiveBalance($agentId,$agentList,$userList);
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