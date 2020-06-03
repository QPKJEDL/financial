<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/ket',function (Request $request){

    for($i=0;$i<1;$i++){
        DB::beginTransaction();
        $log=DB::table('users_money')->where("userid",1)->lockForUpdate()->first();
        if($log->money>0){
            echo $log->money."--";
            DB::table('users_money')->where("userid",1)->decrement('money', 5);
            DB::commit();
        }else{
            echo $log->money;
            //DB::rollBack();
        }
    }
});
Route::get('/content',function (Request $request){

    echo file_get_contents("http://114.67.97.70:8032/api/ket");
    echo "<br>";
    echo file_get_contents("http://114.67.97.70:8032/api/ket");
    echo "<br>";
    echo file_get_contents("http://114.67.97.70:8032/api/ket");
    echo "<br>";
    echo file_get_contents("http://114.67.97.70:8032/api/ket");
    echo "<br>";
});
Route::group(['namespace' => "Ucenter"], function () {
    Route::get('/square/publish',"SquareController@publish");
    Route::get('/square/index',"SquareController@index");
    Route::get('/test',"SquareController@test");
    Route::get('/test2',"SquareController@test2");
});
