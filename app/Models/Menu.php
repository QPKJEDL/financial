<?php

namespace App\Models;
use Auth;
use Illuminate\Database\Eloquent\Model;
use App\Models\Interfaces\AdminMenuInterface;
use App\Models\Traits\AdminMenuTrait;
class Menu extends Model implements AdminMenuInterface
{
    use AdminMenuTrait;

    protected $table = 'business_menus';

    protected $primaryKey = 'id';

    /**
     * 根据当前用户来获取菜单
     * @return array|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getMenuList()
    {
        //得到当前用户
        $user = \Illuminate\Support\Facades\Auth::id();
        //判断当前用户是不是管理员
        //获取到全部菜单
        $menuAllList = Menu::query()->orderBy('order','asc')->get();
        if ($user==1){
            return $menuAllList;
        }else{
            //根据userId来查询角色
            $role = Adminrole::where('user_id','=',$user)->first();
            //根据角色获取到当前角色菜单
            $menuList = AgentRoleMenu::where('role_id','=',$role['role_id'])->get();
            $menu = array();
            foreach ($menuList as $key=>$value){
                foreach ($menuAllList as $k=>$v){
                    if ($value['menu_id']==$v['id']){
                        $menu[] = $menuAllList[$k];
                        continue;
                    }
                }
            }
            return $menu;
        }
    }
}
