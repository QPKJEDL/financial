<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Desk;
use App\Models\Game;
use App\Models\Order;
use Illuminate\Http\Request;

class DeskController extends Controller
{
    public function index(Request $request)
    {
        if(true == $request->has('begin')){
            $tableName = date('Ymd',strtotime($request->input('begin')));
        }else{
            $tableName = date('Ymd',strtotime('-1day'));
        }
        $list = Order::getOrderDataByTableName($tableName);
        $map = array();
        $data = Desk::where($map)->paginate(10)->appends($request->all());
        foreach($data as $key=>&$value)
        {
            $data[$key]['time']=$tableName;
            $data[$key]['game_id']=Game::getGameNameByGameId($value['game_id']);
            $data[$key]['betMoney']=$this->getDeskAllBetMoney($data[$key]['id'],$list);
            $data[$key]['winAndErr']=$this->getWinAndErrMoney($data[$key]['id'],$list);
        }
        $min=config('admin.min_date');
        return view('desk.list',['list'=>$data,'input'=>$request->all(),'min'=>$min]);
    }


    /**
     * 解析order表中得bet_money获取总金额
     */
    public function getCalculationMoney($orderInfo)
    {
        $sum=0;
        $json = $orderInfo['bet_money'];
        $data = json_decode($json,true);
        foreach($data as $key=>$value){
            $sum += $data[$key];
        }
        return $sum;
    }

    /**
     * 根据deskId获取到每桌得下注总金额
     */
    public function getDeskAllBetMoney($deskId,$data)
    {
        $sum=0;
        foreach($data as $key=>$value){
            if($data[$key]['desk_id']==$deskId){
                $sum += $this->getCalculationMoney($data[$key]);
            }
        }
        return $sum;
    }
    /**
     * 获取游戏输赢情况
     */
    public function getWinAndErrMoney($deskId,$data)
    {
        $num = 0;
        foreach($data as $key=>$value){
            if($data[$key]['desk_id']==$deskId){
                $num -= $data[$key]['get_money'];
            }
        }
        return $num;
    }
}