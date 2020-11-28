<?php
/**
 * 权限检查
 *
 * @author      fzs
 * @Time: 2017/07/14 15:57
 * @version     1.0 版本号
 */
namespace App\Http\Middleware;
use Illuminate\Http\JsonResponse;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Auth;
class AuthCheck
{
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  Guard  $auth
     * @return void
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if($request->path() == 'logout') {
            $this->auth->logout();
            return redirect('/admin/');
        }
        if ($this->auth->guest()) {
            if ($request->ajax()) {
                return new JsonResponse(['msg'=>trans('fzs.common.no_permission'),'status'=>0], 200);
            } else {
                return redirect()->guest('/admin/login');
            }
        }
        $agent=$this->check();
        if($agent->login_time!=session("AuthTime")){
            $this->auth->logout();
            return redirect('/admin/login');
        }

        return $next($request);
    }

    private function check(){


        $agent=DB::table('business')->where('id',Auth::user()['id'])->first();
        //print_r($agent);
        return $agent;
    }
}
