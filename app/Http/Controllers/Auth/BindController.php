<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequest;
use App\Models\User;
use App\Models\Verificat;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Redis;
use PragmaRX\Google2FA\Google2FA;
class BindController extends Controller
{
    public function index(){

//        $google2fa = new Google2FA();
//        //生成二维码
//        $qrCodeUrl = $google2fa->getQRCodeUrl("EPayPlusAdmin",18338017626,'UMWVWP7C2QBTXUHZ');
//        dump($qrCodeUrl);
        return view('auth.register');
    }
    /**
     * 效验用户名是否存在
     */
    public function checkAccount(StoreRequest $request){
        $count = User::where('username','=',htmlformat($request->input('account')))->count();
        if($count==0){
            return ['msg'=>'该账号不存在！请输入正确的账号！','status'=>1];
        }
    }
    /**
     * 效验用户名密码是否正确
     */
    public function checkUserLogin(StoreRequest $request){
        $count = User::where('username','=',htmlformat($request->input('account')))->count();
        if($count>0){
            //获取到账号密码
            $account = $request->input('account');
            $password = $request->input('password');
            //获取到用户信息
            $user = User::where('username','=',htmlformat($account))->first();
            if(!App::make('hash')->check(htmlformat($password),$user['password'])){
                return ['msg'=>'旧密码不正确！','status'=>0];
            }else{
                return ['msg'=>'验证成功！','status'=>1];
            }
        }else{
            return ['msg'=>'该账号不存在！请输入正确的账号！','status'=>0];
        }
    }
    /**
     * 发送短信验证码
     */
    public function sendSMS(StoreRequest $request){
        //获取账号
        $account = htmlformat($request->input('account'));
        //获取手机号
        $mobile = htmlformat($request->input('mobile'));
        //获取ip
        $ip = $request->ip();
        $user = User::where('username','=',$account)->first();
        //验证码
        $code = mt_rand(100000,999999);
        if($mobile!=$user['mobile']){
            return ['msg'=>'手机号不是您开户时的手机号','status'=>0];
        }else{
            Redis::set('bind_code'.$mobile,(int)$code,300);

            //发送短信
            $res = Verificat::yxtsend($mobile,(int)$code,$ip);

            if($res=="0"){
                Verificat::insertsendcode((int)$code,$mobile,6,$ip,1,'发送成功！');
                return ['msg'=>'发送成功！','status'=>1];
            }elseif($res=="10001"){
                Verificat::insertsendcode((int)$code,$mobile,6,$ip,0,'一分钟只能发送一条！');
                return ['msg'=>'请勿频繁发送！','status'=>0];
            }else{
                Verificat::insertsendcode((int)$code,$mobile,6,$ip,0,$res);
                return ['msg'=>'发送失败！','status'=>0];
            }
        }
    }
    /**
     * 绑定+效验验证码
     */
    public function bindCode(StoreRequest $request){
        //获取账号
        $account = htmlformat($request->input('account'));
        //获取手机号
        $mobile = htmlformat($request->input('mobile'));
        //获取验证码
        $code = htmlformat($request->input('code'));
        //从redis中获取验证码
        $codes = Redis::get('bind_code'.$mobile);
        if($codes==null){
            return ['msg'=>'验证码已失效！','status'=>0];
        }else if($code!=$codes){
            return ['msg'=>'验证码不正确！','status'=>0];
        }else if($codes==$codes){
            $user = User::where('username','=',$account)->first();
            $google2fa = new Google2FA();
            //生成二维码
            $qrCodeUrl = $google2fa->getQRCodeUrl("EPayPlusAdmin",$mobile,$user['ggkey']);
            return ['msg'=>'验证通过！','status'=>1,"url"=>$qrCodeUrl];
        }
    }
}
