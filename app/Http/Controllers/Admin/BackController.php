<?php


namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequest;
use App\Models\Agent;
use App\Models\HqUser;
use App\Models\UserAndAgentBack;
use App\Models\UserBack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BackController extends Controller
{
    public function index(Request $request)
    {
        $map = array();
        if (true==$request->has('account'))
        {
            $map['user_and_agent_back.account']=$request->input('account');
        }
        if (true==$request->has('user_type'))
        {
            $map['user_and_agent_back.user_type']=$request->input('user_type');
        }
        $sql = UserAndAgentBack::where($map);
        $sql->leftJoin('business','business.id','=','user_and_agent_back.create_by')
            ->select('user_and_agent_back.id','user_and_agent_back.user_id','user_and_agent_back.user_type','user_and_agent_back.status','user_and_agent_back.remark',
                'user_and_agent_back.create_time','business.username','business.nickname');
        $data = $sql->paginate(10)->appends($request->all());
        foreach ($data as $key=>$datum)
        {
            $data[$key]['create_time']=date('Y-m-d H:i:s',$datum['create_time']);
            if ($datum['user_type']==1)
            {
                $data[$key]['agent']=$datum['user_id']?Agent::find($datum['user_id']):[];
            }
            else
            {
                $data[$key]['user']=$datum['user_id']?HqUser::find($datum['user_id']):[];
            }
        }
        return view('userBack.list',['list'=>$data,'input'=>$request->all()]);
    }

    /**
     * 编辑
     * @param int $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function edit($id=0)
    {
        return view('userBack.edit',['id'=>$id]);
    }

    /**
     * 保存
     * @param StoreRequest $request
     * @return array
     */
    public function store(StoreRequest $request)
    {
        $data = $request->all();
        if ($data['user_type']==1)
        {
            $info = Agent::where('username','=',$data['account'])->first();
            if ($info)
            {
                if (!UserAndAgentBack::where('user_id','=',$info['id'])->where('user_type','=',1)->where('status','=',$data['status'])->exists())
                {
                    DB::beginTransaction();
                    try {
                        $bool = UserAndAgentBack::insert(['user_id'=>$info['id'],'account'=>$data['account'],'user_type'=>$data['user_type'],'status'=>$data['status'],'remark'=>$data['remark'],'create_by'=>Auth::id(),'create_time'=>time()]);
                        if ($bool)
                        {
                            $agentIdArr = array();
                            $agentIdArr[]=$info['id'];
                            $agentData = Agent::select('id')->whereRaw('FIND_IN_SET('.$info['id'].',ancestors)')->get();
                            foreach ($agentData as $key=>$datum)
                            {
                                $agentIdArr[] = $datum['id'];
                            }
                            $userData = HqUser::select('user_id')->where('del_flag','=',0)->whereIn('agent_id',$agentIdArr)->get();
                            if (count($userData)>0)
                            {
                                $arr = array();
                                foreach ($userData as $key=>$datum)
                                {
                                    if (UserBack::where('user_id','=',$datum['user_id'])->where('status','=',$data['status'])->exists())
                                    {
                                        continue;
                                    }
                                    else
                                    {
                                        $map['user_id']=$datum['user_id'];
                                        $map['status']=$data['status'];
                                        $map['remark']=$data['remark'];
                                        $map['create_by']=Auth::id();
                                        $map['create_time']=time();
                                        $arr[] = $map;
                                    }
                                }
                                $count = UserBack::insert($arr);
                                if ($count)
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
                    }catch (\Exception $exception)
                    {
                        DB::rollBack();
                        return ['msg'=>'操作失败','status'=>0];
                    }
                }
                else
                {
                    return ['msg'=>'该账户的该权限已被拉黑','status'=>0];
                }
            }
            else
            {
                return ['msg'=>'账号不存在','status'=>0];
            }
        }
        else
        {
            $info = HqUser::where('account','=',$data['account'])->first();
            if ($info)
            {
                if (!UserAndAgentBack::where('user_id','=',$info['id'])->where('user_type','=',2)->where('status','=',1)->exists())
                {
                    DB::beginTransaction();
                    try {
                        $bool = UserAndAgentBack::insert(['user_id'=>$info['user_id'],'account'=>$data['account'],'user_type'=>$data['user_type'],'status'=>$data['status'],'remark'=>$data['remark'],'create_by'=>Auth::id(),'create_time'=>time()]);
                        if ($bool)
                        {
                            if (UserBack::where('user_id','=',$info['user_id'])->where('status','=',$data['status'])->exists())
                            {
                                DB::rollBack();
                                return ['msg'=>'该会员的直属代理或上级代理已被拉黑','status'=>0];
                            }
                            else
                            {
                                $count = UserBack::insert(['user_id'=>$info['user_id'],'status'=>$data['status'],'remark'=>$data['remark'],'create_by'=>Auth::id(),'create_time'=>time()]);
                                if ($count)
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
                        }
                        else
                        {
                            DB::rollBack();
                            return ['msg'=>'操作失败','status'=>0];
                        }
                    }
                    catch (\Exception $e)
                    {
                        DB::rollBack();
                        return ['msg'=>'操作失败','status'=>0];
                    }
                }
                else
                {
                    return ['msg'=>'该账户的该权限已被拉黑','status'=>0];
                }
            }
            else
            {
                return ['msg'=>'账户不存在','status'=>0];
            }
        }
    }

    public function destroy($id)
    {
        $info = $id?UserAndAgentBack::find($id):[];
        if ($info['user_type']==1)
        {
            $agentIdArr = array();
            $agentIdArr[]=$info['user_id'];
            $agentData = Agent::select('id')->whereRaw('FIND_IN_SET('.$info['user_id'].',ancestors)')->get();
            if (count($agentData)>0){
                foreach ($agentData as $key=>$datum)
                {
                    $agentIdArr[] = $datum['id'];
                }
            }
            $userData = HqUser::select('user_id')->where('del_flag','=',0)->whereIn('agent_id',$agentIdArr)->get();
            DB::beginTransaction();
            try {
                $bool = UserAndAgentBack::where('id','=',$info['id'])->delete();
                if ($bool)
                {
                    foreach ($userData as $key=>$datum)
                    {
                        $count = UserBack::where('user_id','=',$datum['user_id'])->where('status','=',$info['status'])->delete();
                        if (!$count)
                        {
                            DB::rollBack();
                            return ['msg'=>'操作失败','status'=>0];
                        }
                    }
                    DB::commit();
                    return ['msg'=>'操作成功','status'=>1];
                }
                else
                {
                    DB::rollBack();
                    return ['msg'=>'操作失败','status'=>0];
                }
            }catch (\Exception $exception)
            {
                DB::rollBack();
                return ['msg'=>'操作失败','status'=>0];
            }
        }
        else
        {
            DB::beginTransaction();
            try {
                $bool = UserAndAgentBack::where('id','=',$id)->delete();
                if ($bool)
                {
                    $count = UserBack::where('user_id','=',$info['user_id'])->where('status','=',$info['status'])->delete();
                    if ($count)
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
                    DB::rollBack();
                    return ['msg'=>'操作失败','status'=>0];
                }
            }catch (\Exception $exception)
            {
                DB::rollBack();
                return ['msg'=>'操作失败','status'=>0];
            }
        }
    }
}