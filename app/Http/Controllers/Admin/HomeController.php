<?php
/**
 * 用户登陆过后首页以及一些公共方法
 *
 * @author      fzs
 * @Time: 2017/07/14 15:57
 * @version     1.0 版本号
 */
namespace App\Http\Controllers\Admin;
use App\Models\Admin;
use App\Models\Buscount;
use App\Models\Order;

use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Auth;
//use App\Http\Controllers\Controller;
class HomeController extends BaseController
{
    /**
     * 后台首页
     */
    public function index() {
        $menu = new Admin();
        //dump($menu->menus());
        return view('admin.index',['menus'=>$menu->menus(),'mid'=>$menu->getMenuId(),'parent_id'=>$menu->getParentMenuId()]);
    }
    /**
     * 验证码
     */
    public function verify(){
        $phrase = new PhraseBuilder;
        $code = $phrase->build(4);
        $builder = new CaptchaBuilder($code, $phrase);
        $builder->setBackgroundColor(255, 255, 255);
        $builder->build(130,40);
        $phrase = $builder->getPhrase();
        Session::flash('code', $phrase); //存储验证码
        return response($builder->output())->header('Content-type','image/jpeg');
    }


    /**
     * 欢迎首页
     */
    public function welcome(){
        $rid=getrole(Auth::id());
        if($rid==4){
            return '';
        }
        //今天
        $start= strtotime(date('Y-m-d'));
        $end=strtotime('+1day',$start);

        $weeksuf = computeWeek(time(),false);
        $order=new Order;
        $order->setTable('order_'.$weeksuf);

        //top-count
        /*$total=$order->whereBetween('creatime',[$start,$end])->count('order_sn');//今日全部订单
        $done=$order->whereBetween('creatime',[$start,$end])->where('status','=',1)->count('order_sn');//今日成功订单
        $none=$order->whereBetween('creatime',[$start,$end])->where('status','=',0)->count('order_sn');//今日未支付订单

        $done_money=$order->whereBetween('creatime',[$start,$end])->where('status','=',1)->sum('sk_money');//今日成交金额
        $all_done_money=Buscount::sum('tol_sore');//累计成交金额*/

       /* $data['total']=$total;
        $data['done']=$done;
        $data['none']=$none;
        $data['done_money']=$done_money/100;
        $data['all_done_money']=$all_done_money/100;
        if($total==0){
            $data['done_rate']=0;
        }else{
            $data['done_rate']=round($done/$total*100,2);
        }*/

        //chart

        //x轴-近七天
       /* $week=$this->get_weeks(time(),"m-d");
        $x=array_values($week);
        $data['x']=json_encode($x);

        //近七天时间戳数组
        $date=$this->get_weeks(time(),'Y-m-d');
        for ($i=1; $i<=7; $i++){
            $date[$i] = strtotime($date[$i]);
        }
        //y1全部订单
        $all_order[1]=$order->whereBetween('creatime',[$date[1],$date[2]])->count('order_sn');
        $all_order[2]=$order->whereBetween('creatime',[$date[2],$date[3]])->count('order_sn');
        $all_order[3]=$order->whereBetween('creatime',[$date[3],$date[4]])->count('order_sn');
        $all_order[4]=$order->whereBetween('creatime',[$date[4],$date[5]])->count('order_sn');
        $all_order[5]=$order->whereBetween('creatime',[$date[5],$date[6]])->count('order_sn');
        $all_order[6]=$order->whereBetween('creatime',[$date[6],$date[7]])->count('order_sn');
        $all_order[7]=$total;
        $y1=array_values($all_order);
        $data['y1']=json_encode($y1);
        //y2成功订单
        $done_order[1]=$order->whereBetween('creatime',[$date[1],$date[2]])->where('status','=',1)->count('order_sn');
        $done_order[2]=$order->whereBetween('creatime',[$date[2],$date[3]])->where('status','=',1)->count('order_sn');
        $done_order[3]=$order->whereBetween('creatime',[$date[3],$date[4]])->where('status','=',1)->count('order_sn');
        $done_order[4]=$order->whereBetween('creatime',[$date[4],$date[5]])->where('status','=',1)->count('order_sn');
        $done_order[5]=$order->whereBetween('creatime',[$date[5],$date[6]])->where('status','=',1)->count('order_sn');
        $done_order[6]=$order->whereBetween('creatime',[$date[6],$date[7]])->where('status','=',1)->count('order_sn');
        $done_order[7]=$done;
        $y2=array_values($done_order);
        $data['y2']=json_encode($y2);*/
        $sysinfo=$this->getSysInfo();
        return view('admin.welcome',['sysinfo'=>$sysinfo]);
    }
    /**
     * 排序
     */
    public function changeSort(Request $request){
        $data = $request->all();
        if(is_numeric($data['id'])){
            $res = DB::table('admin_'.$data['name'])->where('id',$data['id'])->update(['order'=>$data['val']]);
            if($res)return $this->resultJson('fzs.common.success', 1);
            else return $this->resultJson('fzs.common.fail', 0);
        }else{
            return $this->resultJson('fzs.common.wrong', 0);
        }
    }
    /**
     * 获取系统信息
     */
    protected function getSysInfo(){
        $sys_info['ip'] 			= GetHostByName($_SERVER['SERVER_NAME']);
        $sys_info['phpv']           = phpversion();
        $sys_info['web_server']     = $_SERVER['SERVER_SOFTWARE'];
        $sys_info['time']           = date("Y-m-d H:i:s");
        $sys_info['domain'] 		= $_SERVER['HTTP_HOST'];
        $mysqlinfo = DB::select("SELECT VERSION() as version");
        $sys_info['mysql_version']  = $mysqlinfo[0]->version;
        return $sys_info;
    }
    /**
     * 最近7日
     */
    public function get_weeks($time, $format){
        $time = $time != '' ? $time : time();
        //组合数据
        $date = [];
        for ($i=1; $i<=7; $i++){
            $date[$i] = date($format ,strtotime( '+' . $i-7 .' days', $time));
        }
        return $date;
    }
}
