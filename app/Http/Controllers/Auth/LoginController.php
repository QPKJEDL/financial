<?php
/**
 * 用户登陆
 *
 * @author      fzs
 * @Time: 2017/07/14 15:57
 * @version     1.0 版本号
 */
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Log;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */
    use AuthenticatesUsers {authenticated as oriAuthenticated;}
    use AuthenticatesUsers {login as doLogin;}

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/admin/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    public function login(Request $request)
    {

        //判断账号是否存在
        $count = User::where('username','=',$request->input('username'))->first();
        if(!$count){
            return redirect('/admin/login')->withErrors([trans('fzs.login.false_account')]);
        }
        /*$user = new User();
        if(!$this->verifyGooglex($request->input('ggkey'),htmlformat($request->input('username')))){
            return redirect('/admin/login')->withErrors([trans('fzs.login.false_ggkey')]);
        }*/
        if($request->input('verity')==session('code'))return $this->doLogin($request);
        else return redirect('/admin/login')->withErrors([trans('fzs.login.false_verify')]);
    }
    public function username()
    {
        return 'username';
    }

    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        return redirect('/admin/');
    }

    protected function authenticated(Request $request, $user)
    {
        Log::addLogs(trans('fzs.login.login_info'),'/admin/login',$user->id);
        return $this->oriAuthenticated($request, $user);
    }

    protected function verifyGooglex($code,$account){
        $userInfo=User::getUserInfo($account);
        $secret=$code;
        $google2fa = new Google2FA();
        if($google2fa->verifyKey($userInfo["ggkey"], $secret)){
            return true;
        }else{
            return false;
        }
    }
}
