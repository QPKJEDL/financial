<?php


namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequest;
use App\Models\AgentMenu;
use App\Models\AgentRole;
use App\Models\AgentRoleMenu;
use App\Models\RoleMenu;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\View\View;

class AgentRoleController extends Controller
{
    /**
     * 列表
     */
    public function index(){
        return view('agentRole.list',['list'=>AgentRole::get()->toArray()]);
    }
    public function store(StoreRequest $request)
    {
        $data = $request->all();
        $menus = $request->input('menus');
        $menusData = json_decode($menus,true);
        unset($data['id']);
        unset($data['menus']);
        unset($data['_token']);
        $data['created_at']=date('Y-m-d H:i:s',time());
        $count = AgentRole::insertGetId($data);
        if ($count){
            $this->insertAgentRole($menusData,$count);
            return ['msg'=>'添加成功','status'=>1,'id'=>$count];
        }else{
            return ['msg'=>'添加失败','status'=>0];
        }
    }

    /**
     * 角色编辑
     * @param int $id
     * @return Factory|Application|View
     */
    public function edit($id=0)
    {
        $info = $id?AgentRole::find($id):[];
        if ($info!=null)
        {
            return view('agentRole.edit', ['id'=>$id,'info'=>$info,'tree'=>AgentMenu::editTreeList($id)]);
        }else{
            return view('agentRole.edit', ['id'=>$id,'info'=>$info,'tree'=>AgentMenu::tree()]);
        }
    }

    public function update(StoreRequest $request)
    {
        $data = $request->all();
        $menus = $request->input('menus');
        $id = $request->input('id');
        $menuData = json_decode($menus,true);
        unset($data['id']);
        unset($data['menus']);
        unset($data['_token']);
        $data['updated_at']=date('Y-m-d H:i:s',time());
        $count = AgentRole::where('id',$id)->update($data);
        if ($count!==false){
            if ($menuData!=null){
                $this->deleteAgentRoleMenu($id);
            }
            $this->insertAgentRole($menuData,$id);
            return ['msg'=>'操作成功','status'=>1];
        }else{
            return ['msg'=>'操作失败','status'=>0];
        }
    }

    public function destroy($id)
    {
        $count = AgentRole::where('id','=',$id)->delete();
        if($count){
            $this->deleteAgentRoleMenu($id);
            return ['msg'=>'删除成功','status'=>1];
        }else{
            return ['msg'=>'删除失败','status'=>0];
        }
    }

    public function deleteAgentRoleMenu($roleId){
        RoleMenu::where('role_id','=',$roleId)->delete();
    }

    public function insertAgentRole($data,$roleId){
        if(is_array($data)){
            if (count($data)>0){
                foreach ($data as $key=>$value)
                {
                    RoleMenu::insert(['role_id'=>$roleId,'menu_id'=>$data[$key]['id']]);
                }
            }
        }
    }
}