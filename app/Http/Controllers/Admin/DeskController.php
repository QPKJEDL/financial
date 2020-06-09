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
            $data[$key]['betMoney']=$this->getCalculationMoney($data[$key]['id'],$tableName);
            $data[$key]['winAndErr']=$this->getWinAndErrMoney($data[$key]['id'],$tableName);
        }
        $min=config('admin.min_date');
        return view('desk.list',['list'=>$data,'input'=>$request->all(),'min'=>$min]);
    }


    /**
     * 解析order表中得bet_money获取总金额
     * @param $deskId
     * @param $tableName
     * @return float|int
     */
    public function getCalculationMoney($deskId,$tableName)
    {
        $sum=0;
        $order = new Order();
        $order->setTable('order_'.$tableName);
        $data = $order->where('desk_id','=',$deskId)->where('status','=',1)->get();
        foreach ($data as $key=>$datum){
            $bet = json_decode($datum['bet_money'],true);
            $sum = $sum + array_sum($bet);
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
     * @param $deskId
     * @param $tableName
     * @return int|mixed
     */
    public function getWinAndErrMoney($deskId,$tableName)
    {
        $order = new Order();
        $order->setTable('order_'.$tableName);
        return $order->where('desk_id','=',$deskId)->sum('get_money');
    }
}