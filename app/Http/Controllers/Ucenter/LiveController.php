<?php

namespace App\Http\Controllers\Ucenter;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PragmaRX\Google2FA\Google2FA;
class LiveController extends Controller
{

    //展示直播
    public function index(){

    }
    //保存直播信息
    public function saveMessage(Request $request){
        $input=$request->all();
    }
    //public function

    public function testgoogle(){
        $google2fa = new Google2FA();
        /*$qrCodeUrl = $google2fa->getQRCodeUrl(
            $companyName,
            $companyEmail,
            $secretKey
        );*/
        echo $google2fa->generateSecretKey();


    }
}
