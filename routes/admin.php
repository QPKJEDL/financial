<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
//验证码
Route::get('/verify',                   'Admin\HomeController@verify');
//登陆模块
Route::group(['namespace'  => "Auth"], function () {
    Route::get('/register',             'BindController@index');    //绑定谷歌验证码
    Route::post('/valAccount',          'BindController@checkAccount'); //效验账号是否存在
    Route::post('/valUser',             'BindController@checkUserLogin');//效验账号密码的真实性
    Route::post('/sendSMS',             'BindController@sendSMS');//发送验证码
    Route::post('/bindCode',            'BindController@bindCode');//绑定加效验
    Route::get('/login',                'LoginController@showLoginForm')->name('login');//登录
    Route::post('/login',               'LoginController@login');
    Route::get('/logout',               'LoginController@logout')->name('logout');
});
//后台主要模块
Route::group(['namespace' => "Admin",'middleware' => ['auth', 'permission']], function () {
    Route::get('/',                     'HomeController@index');
    Route::get('/gewt',                 'HomeController@configr');
    Route::get('/index',                'HomeController@welcome');
    Route::post('/sort',                'HomeController@changeSort');
    Route::resource('/menus',           'MenuController');
    Route::resource('/logs',            'LogController');
    Route::resource('/users',           'UserController');
    Route::resource('/ucenter',         'UcenterController');
    Route::get('/userinfo',             'UserController@userInfo');
    Route::post('/saveinfo/{type}',     'UserController@saveInfo');
    Route::resource('/roles',           'RoleController');
    Route::resource('/permissions',     'PermissionController');
    Route::resource('/hquser', 'HqUserController');//会员管理
    Route::resource('/desk', 'DeskController');//台桌输赢记录
    Route::resource('/order',   'OrderController');//注单记录
    Route::resource('/gameRecord','GameRecordController');//台桌游戏查询
    Route::resource('/online',      'OnlineController');//在线用户管理
    Route::resource('/agentDay',    'AgentDayEndController');//代理日结表
    Route::resource('/agent',       'AgentListController');//代理列表
    Route::get('/agent/subordinate/{id}','AgentListController@getSubordinateAgentList');//下级代理
    Route::get('/agent/subUser/{id}','AgentListController@user');//下级会员
    Route::resource('/userDay',     'UserDayEndController');//会员日结表
    Route::resource('/daw','DepositAndWithController');//会员提现查询
    Route::resource('/cz','CzController');//会员充值查询
    Route::resource('/deluser','DelUserController');//已删会员
    Route::resource('/delagent','DelAgentController');//已删代理
    Route::resource('/down','DownController');//下分请求
    Route::get('/userOrderList/{id}','UserDayEndController@infoList');//下注详情
    Route::resource('/live','LiveRewardController');//会员打赏记录
});

Route::get('/phpinfo',function (Request $request){
   phpinfo();
});