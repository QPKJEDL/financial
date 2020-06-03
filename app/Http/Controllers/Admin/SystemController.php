<?php


namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequest;
use Illuminate\Support\Facades\Redis;

/**
 * 系统开关
 * Class SystemController
 * @package App\Http\Controllers\Admin
 */
class SystemController extends Controller
{
    /**
     * 主页
     */
    public function index()
    {
        $system = Redis::get('clickSystem');
        $drawOpen = Redis::get('drawOpen');
        return view("system.list",['system'=>$system,'drawOpen'=>$drawOpen]);
    }

    /**
     * 点击系统一键维护
     * @param StoreRequest $request
     * @return array
     */
    public function maintain(StoreRequest $request)
    {
        $bool = $request->input('checked');
        $key = "clickSystem";
        Redis::set($key,$bool);
        return ['msg'=>'操作成功！','status'=>1];
    }

    /**
     * 充提开关
     * @param StoreRequest $request
     * @return array
     */
    public function drawOpen(StoreRequest $request)
    {
        $bool = $request->input("checked");
        $key = "drawOpen";
        Redis::set($key,$bool);
        return ['msg'=>'操作成功！','status'=>1];
    }
}