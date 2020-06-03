<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
class BaseController extends Controller
{
    /**
     * 返回自定义标准json格式
     *
     * @access protected
     * @param string $lang 语言包
     * @param number $res 结果code
     * @return json
     */
    protected function resultJson($lang,$res)
    {
        return strstr($lang,'fzs')?['status'=>$res,'msg'=>trans($lang)]:['status'=>$res,'msg'=>$lang];
    }
}
