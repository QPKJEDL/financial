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
    Route::get('/agentRegister/{id}',        'OnAgentActController@actAgent');//代理激活页面
    Route::post('/actAgent','OnAgentActController@actSave');//代理激活
    Route::get('/register',             'BindController@index');    //绑定谷歌验证码
    Route::post('/valAccount',          'BindController@checkAccount'); //效验账号是否存在
    Route::post('/valUser',             'BindController@checkUserLogin');//效验账号密码的真实性
    Route::post('/sendSMS',             'BindController@sendSMS');//发送验证码
    Route::post('/bindCode',            'BindController@bindCode');//绑定加效验
    Route::get('/login',                'LoginController@showLoginForm')->name('login');//登录
    Route::post('/login',               'LoginController@login');
    Route::get('/logout',               'LoginController@logout')->name('logout');
    Route::get('/userinfo',             'UserController@userInfo');
});
//后台主要模块
Route::group(['namespace' => "Admin",'middleware' => ['auth', 'permission']], function () {
    Route::post('/updatePwd','IndexController@updatePwd');//修改密码
    //菜单获取
    Route::get('/getMenuList',          'IndexController@getMenuList');
    //主页
    Route::get('/',                     'IndexController@index');
    Route::get('/getUnRead','DownController@getUnRead');//获取未读消息
    //首页
    Route::get('/home',                 'HomeController@index');

    //账户管理模块
    Route::resource('/deluser','DelUserController');//已删会员
    Route::resource('/delagent','DelAgentController');//已删代理


    Route::get('/gewt',                 'HomeController@configr');
    Route::get('/index',                'HomeController@welcome');
    Route::post('/sort',                'HomeController@changeSort');
    Route::resource('/menus',           'MenuController');
    Route::resource('/logs',            'LogController');
    Route::resource('/users',           'UserController');
    Route::resource('/ucenter',         'UcenterController');

    Route::post('/saveinfo/{type}',     'UserController@saveInfo');
    Route::resource('/roles',           'RoleController');
    Route::resource('/permissions',     'PermissionController');
    //会员充值提现记录
    Route::get('/getRecordByUserId/{userId}','DepositAndWithController@getRecordByUserId');
    //代理充值提现记录查询
    Route::get('/getRecordByAgentId/{id}','AgentDrawAndCzController@getRecordByAgentId');
    //会员列表
    Route::resource('/hquser', 'HqUserController');//线下会员管理
    Route::get('/userRelation/{id}','HqUserController@userRelation');//会员结构关系
    Route::post('/hquser/changeStatus','HqUserController@changeStatus');//停用启用
    Route::get('/hquser/edit/{id}','HqUserController@edit');//编辑
    Route::post('/hquser/save','HqUserController@updateSave');//编辑保存
    Route::get('/hquser/resetPwd/{id}','HqUserController@resetPwd');//修改密码
    Route::post('/hquser/updatePassword','HqUserController@updatePassword');//保存修改密码
    Route::resource('/onUser','OnHqUserController');//线上会员管理
    Route::get('/hquser/topCode/{id}','HqUserController@topCode');//会员上分页面
    Route::get('/hquser/underCode/{id}','HqUserController@underCode');//会员下分页面
    Route::post('/hquser/saveTopCode','HqUserController@saveTopCode');//会员上下分操作
    Route::resource('/desk', 'DeskController');//台桌输赢记录
    Route::resource('/order',   'OrderController');//注单记录
    Route::resource('/gameRecord','GameRecordController');//台桌游戏查询
    Route::resource('/online',      'OnlineController');//在线用户管理
    Route::post('/retreat','OnlineController@destroy');//踢下线

    //第三方
    Route::resource('/three','ThreeController');//第三方支付流水
    Route::resource('/tripartite','AgentTripartiteController');//代理第三方支付统计

    //代理日结
    Route::resource('/agentDay',    'AgentDayEndController');//代理日结表
    Route::get('/agentDays/{id}/{begin}/{end}','AgentDayEndController@getAgentDayByAgentId');//下级代理日结
    Route::get('/userDays/{id}/{begin}/{end}','UserDayEndController@getUserDayEndByAgentId');//下级会员日结

    //代理列表
    Route::resource('/agent',       'AgentListController');//代理列表
    Route::get('/getRelationalStruct/{id}','AgentListController@getRelationalStruct');//结构关系
    Route::post('/agent/accountUnique','AgentListController@accountUnique');//效验代理账户是否存在

    Route::get('/agent/resetPwd/{id}','AgentListController@resetPwd');//修改密码页面
    Route::post('/agent/saveResetPwd','AgentListController@saveResetPwd');//保存修改密码
    Route::post('/agentUpdate',       'AgentListController@update');//代理账号编辑
    Route::post('/agentStop',       'AgentListController@stop');//代理账号停用
    Route::post('/agentStart',       'AgentListController@start');//代理账号启用
    Route::resource('/agentRole',       'AgentRoleController');//代理角色管理
    Route::post('/agentRole/update','AgentRoleController@update');
    Route::get('/czEdit/{id}','AgentListController@czEdit');//充值界面
    Route::post('/updateBalance','AgentListController@updateBalance');//上分
    Route::get('/agent/subordinate/{id}','AgentListController@getSubordinateAgentList');//下级代理
    Route::get('/agent/subUser/{id}','AgentListController@user');//下级会员
    Route::resource('/userDay',     'UserDayEndController');//会员日结表
    Route::resource('/daw','DepositAndWithController');//会员提现查询


    //会员充值查询
    Route::resource('/cz','CzController');//会员充值查询
    Route::get('/userCz/{id}','CzController@getCzRecordList');//获取会员的充值记录

    Route::resource('/down','DownController');//下分请求
    Route::post('/down/lockDataById','DownController@lockDataById');//锁定数据
    Route::post('/down/approveData','DownController@approveData');//确认数据
    Route::post('/down/obsoleteData','DownController@obsoleteData');//作废数据
    Route::get('/userOrderList/{id}/{begin}/{end}','OrderController@getOrderListByUserId');//下注详情
    Route::resource('/live','LiveRewardController');//会员打赏记录
    Route::resource('/agentDrawAndCz','AgentDrawAndCzController');//代理充值提现查询

    //黑名单
    Route::resource('/userBack','BackController');
});
Route::group(['namespace' => "Online",'middleware' => ['auth','permission']],function (){
    Route::resource('/onAgentDay','OnAgentDayController');//线上代理日结
    Route::get('/onAgentDayEnd/{id}/{begin}/{end}','OnAgentDayController@getIndexByParentId');//下级代理
    Route::resource('/onOrder','OnOrderController');//线上会员注单查询
    Route::resource('/onUserDay','OnUserDayController');//线上会员日结
    Route::get('/onUserDayEnd/{id}/{begin}/{end}','OnUserDayController@getUserDayEndByAgentId');//线上会员日结
    Route::get('/onUserOrderList/{id}/{begin}/{end}','OnOrderController@getOrderListByUserId');//线上会员下注详情
    Route::resource('/onAgent',       'OnAgentListController');//代理列表
    Route::post('/onAgent/update','OnAgentListController@update');//编辑保存
    Route::get('/onAgentList/qrCode/{id}','OnAgentListController@qrCodeShow');//显示未激活代理的二维码
    Route::get('/czOnEdit/{id}','OnAgentListController@czEdit');//充值界面
    Route::post('/updateOnBalance','OnAgentListController@updateBalance');//上分
    Route::get('/onAgent/subordinate/{id}','OnAgentListController@getSubordinateAgentList');//下级代理
    Route::get('/onAgent/subUser/{id}','OnAgentListController@user');//下级会员
});

Route::get('/phpinfo',function (Request $request){
   phpinfo();
});