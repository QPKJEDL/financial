<?php


namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequest;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class IndexController extends Controller
{
    public function index()
    {
        $data = Menu::getMenuList();
        return view('admin.index',['list'=>$data]);
    }

    /**
     * 获取菜单
     * @return array
     */
    public function getMenuList()
    {
        $data = Menu::getMenuList();
        return ['list'=>$data,'msg'=>'获取成功'];
    }

    /**
     * 保存修改密码
     * @param StoreRequest $request
     */
    public function updatePwd(StoreRequest $request)
    {
        if (empty(Auth::user()))
        {
            return ['msg'=>'没有权限','status'=>0];
        }
        $oldPwd = HttpFilter($request->input('oldpwd'));
        $password = HttpFilter($request->input('pwd'));
        $pwdConfirmation = HttpFilter($request->input('pwd_confirmation'));
        $userId = Auth::id();
        $user = User::where('id','=',$userId)->first();
        if(!App::make('hash')->check($oldPwd,$user['password']))return ['status'=>0,'msg'=>trans('fzs.users.pwd_false')];
        if ($password==$oldPwd)
        {
            return ['msg'=>'新密码不能与旧密码一致','status'=>0];
        }
        if ($password!=$pwdConfirmation)
        {
            return ['msg'=>'确认密码不一致','status'=>0];
        }
        $count = User::where('id','=',$userId)->update(['password'=>bcrypt($password)]);
        if (!$count) return ['msg'=>'操作失败','status'=>0];
        return ['msg'=>'操作成功','status'=>1];
    }
}