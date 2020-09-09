<?php


namespace App\Http\Controllers\Admin;


use App\Http\Requests\StoreRequest;
use Illuminate\Database\Eloquent\Model;

class AgentMonthEndController extends Model
{
    /**
     * 效验密码
     * @param StoreRequest $request
     * @return array
     */
    public function checkPasswordIsTrue(StoreRequest $request)
    {
        $password = HttpFilter($request->input('password'));
        if (md5(md5($password))!=md5(md5('123456')))
        {
            return ['msg'=>'效验失败','status'=>0];
        }
        return ['msg'=>'效验成功','status'=>1];
    }
}