<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Draw;
use App\Models\User;
use Illuminate\Http\Request;

class DepositAndWithController extends Controller
{
    public function index(Request $request){
        $map = array();
        $draw = Draw::query();
        $sql = $draw->leftJoin('user','user.user_id','=','user_draw.user_id')
            ->leftJoin('agent_users','agent_users.id','=','user.agent_id')
            ->select('user_draw.creatime','user.nickname','user.account','user.agent_id','agent_users.username','agent_users.nickname as agentName','user_draw.bet_before','user_draw.money','user_draw.bet_after')->where($map);
        if (true==$request->has('begin')){
            $begin = strtotime($request->input('begin'));
            if (true==$request->has('end')){
                $end = strtotime('+1day',strtotime($request->input('end')))-1;
            }else{
                $end = strtotime('+1day',time())-1;
            }
            $sql->whereBetween('user_draw.creatime',[$begin,$end]);
        }
        if (true==$request->has('account')){
            $sql->where('user.account','=',$request->input('account'));
        }
        $sql->orderBy('user_draw.creatime','desc');
        if (true==$request->input('excel') && true==$request->has('excel')){
            $excel = $sql->get()->toArray();
            $head = array('时间','用户名称[账号]','直属上级[账号]','操作前金额(元)','提现金额(元)','操作后金额(元)');
            foreach ($excel as $key=>$value){
                $excel[$key]['creatime']=date('Y-m-d H:i:s',$value['creatime']);
                $excel[$key]['nickname']=$value['nickname'].'['.$value['account'].']';
                $excel[$key]['agentName']=$value['agentName'].'['.$value['username'].']';
                $excel[$key]['bet_before']=$value['bet_before']/100;
                $excel[$key]['money']=$value['money']/100;
                $excel[$key]['bet_after']=$value['bet_after']/100;
                unset($excel[$key]['account']);
                unset($excel[$key]['username']);
            }
            try {
                exportExcel($head, $excel, '会员提现记录' . date('YmdHis', time()), '', true);
            } catch (\PHPExcel_Reader_Exception $e) {
            } catch (\PHPExcel_Exception $e) {
            }
        }else{
            $data = $sql->paginate(10)->appends($request->all());
            foreach($data as $key=>&$value){
                $data[$key]['creatime']=date('Y-m-d H:i:s',$value['creatime']);
            }
        }
        return view('daw.list',['list'=>$data,'input'=>$request->all(),'min'=>config('admin.min_date')]);
    }

    /**
     * 获取全部代理
     * @return User[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getAgentAllList(){
        return User::get();
    }

    /**
     * 根据代理id获取数据
     * @param $agentId
     * @param $data
     * @return mixed
     */
    public function getAgentInfoByAgentId($agentId,$data){
        foreach ($data as $key=>$value){
            if($agentId==$value['id']){
                return $data[$key];
                continue;
            }
        }
    }

    public function getDirectlyAgent($agentId){
        $agentList = $this->getAgentAllList();
        return $this->getRecursiveAgent($agentId,$agentList);
    }

    public function getRecursiveAgent($agentId,$agentList){
        $info = $this->getAgentInfoByAgentId($agentId,$agentList);
        if ($info['parent_id']==0){
            return $info;
        }else{
            return $this->getRecursiveAgent($info['parent_id'],$agentList);
        }
    }
}