<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Desk;
use App\Models\Game;
use App\Models\GameRecord;
use App\Models\HqUser;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeskController extends Controller
{
    public function index(Request $request)
    {
        if(true == $request->has('begin')){
            $tableName = date('Ymd',strtotime($request->input('begin')));
        }else{
            $tableName = date('Ymd',time());
            $request->offsetSet('begin',date('Y-m-d',time()));
        }
        $map = array();
        if (true==$request->has('deskId'))
        {
            $map['id']=$request->input('deskId');
        }
        if (true==$request->has('excel'))
        {
            $head = array('日期','台类型','台桌名称','总下注','下注输赢','洗码费','公司总赢');
            $excelData = Desk::where($map)->get()->toArray();
            $excel = array();
            foreach ($excelData as $key=>&$datum)
            {
                $a = array();
                $excelData[$key]['time']=$tableName;
                $a['time']=$tableName;
                if($datum['game_id']==1){//百家乐
                    $a['gameName']="百家乐";
                    $a['money'] = number_format($this->getBaccaratMoney($datum['id'],$datum['game_id'],0,0,$tableName)/100,2);
                    $a['betMoney']=number_format($this->getCalculationMoney($excelData[$key]['id'],0,0,$tableName)/100,2);
                }else if($datum['game_id']==2){//龙虎
                    $a['gameName']="龙虎";
                    $a['betMoney']=number_format($this->getCalculationMoney($excelData[$key]['id'],0,0,$tableName)/100,2);
                    $a['money'] = number_format($this->getDragonTieTigerMoney($datum['id'],$datum['game_id'],0,0,$tableName)/100,2);
                }else if($datum['game_id']==3){//牛牛
                    $a['gameName']="牛牛";
                    $a['betMoney'] = number_format($this->getNiuNiuMoney($datum['id'],$datum['game_id'],0,0,$tableName)/100,2);
                    $a['money']=$a['betMoney'];
                }else if($datum['game_id']==4){//三公
                    $a['gameName']="三公";
                    $a['betMoney']=number_format($this->getSanGongMoney($datum['id'],$datum['game_id'],0,0,$tableName)/100,2);
                    $a['money']=$a['betMoney'];
                }else if($datum['game_id']==5){//A89
                    $a['gameName']="A89";
                    $a['betMoney']=number_format($this->getA89Money($datum['id'],$datum['game_id'],0,0,$tableName)/100,2);
                    $a['money']=$a['betMoney'];
                }
                $a['desk_name']=$datum['desk_name'];
                $a['game_id']=Game::getGameNameByGameId($datum['game_id']);
                $a['winAndErr']=number_format($this->getWinAndErrMoney($excelData[$key]['id'],0,0,$tableName)/100,2);
                $excel[] = $a;
            }
            try {
                exportExcel($head, $excel, date('Y-m-d H:i:s',time()).'台桌输赢', '', true);
            } catch (\PHPExcel_Reader_Exception $e) {
            } catch (\PHPExcel_Exception $e) {
            }
        }
        if (true==$request->has('limit'))
        {
            $limit = $request->input('limit');
        }
        else
        {
            $limit = 10;
        }
        $data = Desk::where($map)->paginate($limit)->appends($request->all());
        foreach($data as $key=>$value)
        {
            if (true==$request->has('boot_num'))
            {
                $bootNum = $request->input('boot_num');
            }
            else
            {
                $bootNum = 0;
            }
            if (true==$request->has('pave_num'))
            {
                $paveNum = $request->input('pave_num');
            }
            else
            {
                $paveNum = 0;
            }
            $data[$key]['time']=$tableName;
            if($value['game_id']==1){//百家乐
                $data[$key]['betMoney']=$this->getCalculationMoney($data[$key]['id'],$bootNum,$paveNum,$tableName);
            }else if($value['game_id']==2){//龙虎
                $data[$key]['betMoney']=$this->getCalculationMoney($data[$key]['id'],$bootNum,$paveNum,$tableName);
            }else if($value['game_id']==3){//牛牛
                $data[$key]['betMoney'] = $this->getNiuNiuMoney($value['id'],$value['game_id'],$bootNum,$paveNum,$tableName);
            }else if($value['game_id']==4){//三公
                $data[$key]['betMoney']=$this->getSanGongMoney($value['id'],$value['game_id'],$bootNum,$paveNum,$tableName);
            }else if($value['game_id']==5){//A89
                $data[$key]['betMoney']=$this->getA89Money($value['id'],$value['game_id'],$bootNum,$paveNum,$tableName);
            }
            $data[$key]['feeMoney'] = $this->getSumPump($data[$key]['id'],$tableName);
            $data[$key]['getMoney']=$this->getGetMoney($value['id'],$tableName);
            if ($value['getMoney']>0)
            {
                $data[$key]['getMoney']= -$value['getMoney'];
            }
            $data[$key]['code']=$this->getSumCode($value['id'],$tableName);
            $data[$key]['win']=$value['getMoney']-$value['code'];
            $data[$key]['game_id']=Game::getGameNameByGameId($value['game_id']);
        }
        return view('desk.list',['list'=>$data,'desk'=>Desk::getAllDesk(),'input'=>$request->all(),'limit'=>$limit]);
    }

    /**
     * 获取今天台桌线上抽水
     * @param $deskId
     * @param $tableName
     * @return float|int
     */
    public function xSSumPump($deskId,$tableName)
    {
        $money =0;
        $order = new Order();
        $order->setTable('order_'.$tableName);
        $data = $order->where(['desk_id'=>$deskId])->whereIn('status',[1,4])->get()->toArray();
        foreach ($data as $key=>$datum)
        {
            $betMoney = json_decode($datum['bet_money'],true);
            $userInfo = $this->getUserInfoByUserId($datum['user_id']);
            if ($userInfo['user_type']!=2)
            {
                continue;
            }
            $agentInfo = $this->getZsYjByAgentId($userInfo['agent_id']);
            $tName = $this->getGameRecordTableNameByRecordSn($datum['record_sn']);
            $game = new GameRecord();
            $game->setTable('game_record_'.$tName);
            $gameInfo = $game->where('record_sn','=',$datum['record_sn'])->first();
            if ($datum['game_type']==1)
            {
                $winner = json_decode($gameInfo['winner'],true);
                if ($winner['game']=="7")
                {
                    if ($betMoney['banker']>0)
                    {
                        $money = $money + (1 - $userInfo['bjlbets_fee']['banker']/100) * $betMoney['banker'] * $agentInfo['pump']/100;
                    }
                }
                elseif ($winner['game']==4)
                {
                    if ($betMoney['player']>0)
                    {
                        $money = $money + (1 - $userInfo['bjlbets_fee']['player']/100) * $betMoney['player']* $agentInfo['pump']/100;
                    }
                }
                elseif ($winner['game']==1)
                {
                    if ($userInfo['bjlbets_fee']['tie']<100)
                    {
                        if ($betMoney['tie']>0)
                        {
                            $money = $money + (1 - $userInfo['bjlbets_fee']['tie']/100) * $betMoney['player'] * $agentInfo['pump']/100;
                        }
                    }
                }
                if ($winner['bankerPair']==2)
                {
                    if ($userInfo['bjlbets_fee']['bankerPair']<100)
                    {
                        if ($betMoney['bankerPair']>0)
                        {
                            $money = $money + (1 - $userInfo['bjlbets_fee']['bankerPair']/100) * $betMoney['bankerPair'] * $agentInfo['pump']/100;
                        }
                    }
                }
                if ($winner['playerPair']<1)
                {
                    if ($userInfo['bjlbets_fee']['playerPair']<100)
                    {
                        if ($betMoney['playerPair']>0)
                        {
                            $money = $money + (1 - $userInfo['bjlbets_fee']['playerPair']/100) * $betMoney['playerPair'] * $agentInfo['pump']/100;
                        }
                    }
                }
            }
            elseif ($datum['game_type']==2)
            {
                if ($gameInfo['winner']==7)
                {
                    if ($betMoney['dragon']>0)
                    {
                        $money = $money + (1 - $userInfo['lhbets_fee']['dragon']/100) * $betMoney['dragon'] * $agentInfo['pump']/100;
                    }
                }
                elseif ($gameInfo['winner']==4)
                {
                    if ($betMoney['tiger']>0)
                    {
                        $money = $money + (1 - $userInfo['lhbets_fee']['tiger']/100) * $betMoney['tiger'] * $agentInfo['pump']/100;
                    }
                }
                elseif ($gameInfo['winner']==1)
                {
                    if ($userInfo['lhbets_fee']['tie']<100)
                    {
                        if ($betMoney['tie']>0)
                        {
                            $money = $money + (1 - $userInfo['lhbets_fee']['tie']/100) * $betMoney['tie'] * $agentInfo['pump']/100;
                        }
                    }
                }
            }
            elseif ($datum['game_type']==3)
            {
                //{"bankernum":"牛1","x1num":"没牛","x1result":"","x2num":"没牛","x2result":"","x3num":"牛1","x3result":"win"}
                $winner = json_decode($gameInfo['winner'],true);
                if ($winner['x1result']=="win")
                {
                    $x1Num = $this->nConvertNumbers($winner['x1num']);
                    if (!empty($betMoney['x1_Super_Double']))
                    {
                        if ($x1Num>9)
                        {
                            $money = $money + (1 - $userInfo['nnbets_fee']['SuperDouble']) * $betMoney['x1_Super_Double'] * $agentInfo['pump']/100 * 10;
                        }
                        elseif ($x1Num>0 && $x1Num<10)
                        {
                            $money = $money + (1 - $userInfo['nnbets_fee']['SuperDouble']) * $betMoney['x1_Super_Double'] * $agentInfo['pump']/100 * $x1Num;
                        }
                        else
                        {
                            $money = $money + (1 - $userInfo['nnbets_fee']['SuperDouble']) * $betMoney['x1_Super_Double'] * $agentInfo['pump']/100;
                        }
                    }
                    if (!empty($betMoney['x1_double']))
                    {
                        if($x1Num>9)
                        {
                            $money = $money + (1 - $userInfo['nnbets_fee']['Double']) * $betMoney['x1_double'] * $agentInfo['pump']/100 * 3;
                        }
                        elseif ($x1Num>6 && $x1Num<10)
                        {
                            $money = $money + (1 - $userInfo['nnbets_fee']['Double']) * $betMoney['x1_double'] * $agentInfo['pump']/100 * 2;
                        }
                        else
                        {
                            $money = $money + (1 - $userInfo['nnbets_fee']['Double']) * $betMoney['x1_double'] * $agentInfo['pump']/100;
                        }
                    }
                    if (!empty($betMoney['x1_equal']))
                    {
                        $money = $money + (1 - $userInfo['nnbets_fee']['Equal']) * $betMoney['x1_equal'] * $agentInfo['pump']/100;
                    }
                }
                if ($winner['x2result']=="win")
                {
                    $x2Num = $this->nConvertNumbers($winner['x2num']);
                    if (!empty($betMoney['x2_Super_Double']))
                    {
                        if ($x2Num>9)
                        {
                            $money = $money + (1 - $userInfo['nnbets_fee']['SuperDouble']) * $betMoney['x2_Super_Double'] * $agentInfo['pump']/100 * 10;
                        }
                        elseif ($x2Num>0 && $x2Num<10)
                        {
                            $money = $money + (1 - $userInfo['nnbets_fee']['SuperDouble']) * $betMoney['x2_Super_Double'] * $agentInfo['pump']/100 * $x2Num;
                        }
                        else
                        {
                            $money = $money + (1 - $userInfo['nnbets_fee']['SuperDouble']) * $betMoney['x2_Super_Double'] * $agentInfo['pump']/100;
                        }
                    }
                    if (!empty($betMoney['x2_double']))
                    {
                        if($x2Num>9)
                        {
                            $money = $money + (1 - $userInfo['nnbets_fee']['Double']) * $betMoney['x1_double'] * $agentInfo['pump']/100 * 3;
                        }
                        elseif ($x2Num>6 && $x2Num<10)
                        {
                            $money = $money + (1 - $userInfo['nnbets_fee']['Double']) * $betMoney['x1_double'] * $agentInfo['pump']/100 * 2;
                        }
                        else
                        {
                            $money = $money + (1 - $userInfo['nnbets_fee']['Double']) * $betMoney['x1_double'] * $agentInfo['pump']/100;
                        }
                    }
                    if (!empty($betMoney['x2_equal']))
                    {
                        $money = $money + (1 - $userInfo['nnbets_fee']['Equal']) * $betMoney['x1_equal'] * $agentInfo['pump']/100;
                    }
                }
                if ($winner['x3result']=="win")
                {
                    $x3Num = $this->nConvertNumbers($winner['x3num']);
                    if (!empty($betMoney['x3_Super_Double']))
                    {
                        if ($x3Num>9)
                        {
                            $money = $money + (1 - $userInfo['nnbets_fee']['SuperDouble']) * $betMoney['x1_Super_Double'] * $agentInfo['pump']/100 * 10;
                        }
                        elseif ($x3Num>0 && $x3Num<10)
                        {
                            $money = $money + (1 - $userInfo['nnbets_fee']['SuperDouble']) * $betMoney['x1_Super_Double'] * $agentInfo['pump']/100 * $x3Num;
                        }
                        else
                        {
                            $money = $money + (1 - $userInfo['nnbets_fee']['SuperDouble']) * $betMoney['x1_Super_Double'] * $agentInfo['pump']/100;
                        }
                    }
                    if (!empty($betMoney['x3_double']))
                    {
                        if($x3Num>9)
                        {
                            $money = $money + (1 - $userInfo['nnbets_fee']['Double']) * $betMoney['x1_double'] * $agentInfo['pump']/100 * 3;
                        }
                        elseif ($x3Num>6 && $x3Num<10)
                        {
                            $money = $money + (1 - $userInfo['nnbets_fee']['Double']) * $betMoney['x1_double'] * $agentInfo['pump']/100 * 2;
                        }
                        else
                        {
                            $money = $money + (1 - $userInfo['nnbets_fee']['Double']) * $betMoney['x1_double'] * $agentInfo['pump']/100;
                        }
                    }
                    if (!empty($betMoney['x3_equal']))
                    {
                        $money = $money + (1 - $userInfo['nnbets_fee']['Equal']) * $betMoney['x1_equal'] * $agentInfo['pump']/100;
                    }
                }
            }
            elseif ($datum['game_type']==4)
            {
                //{"bankernum":"4点","x1num":"9点","x1result":"win","x2num":"3点","x2result":"","x3num":"6点","x3result":"win","x4num":"1点","x4result":"","x5num":"5点","x5result":"win","x6num":"8点","x6result":"win"}
                $winner = json_decode($gameInfo['winner'],true);
                if ($winner['x1result']=="win")
                {
                    $x1Num = $this->sConvertNumbers($winner['x1num']);
                    if ($userInfo['sgbets_fee']['SuperDouble']<100)
                    {
                        if (!empty($betMoney['x1_Super_Double']))
                        {
                            if ($x1Num>9)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x1_Super_Double'] * $agentInfo['pump']/100 * 10;
                            }
                            elseif ($x1Num>0 && $x1Num<10)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x1_Super_Double'] * $agentInfo['pump']/100 * $x1Num;
                            }
                            else
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x1_Super_Double'] * $agentInfo['pump']/100;
                            }
                        }
                    }
                    if ($userInfo['sgbets_fee']['Double']<100)
                    {
                        if (!empty($betMoney['x1_double']))
                        {
                            if ($x1Num>9)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x1_double'] * $agentInfo['pump']/100 * 3;
                            }
                            elseif ($x1Num>6 && $x1Num<10)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x1_double'] * $agentInfo['pump']/100 * 2;
                            }
                            else
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x1_double'] * $agentInfo['pump']/100;
                            }
                        }
                    }
                    if ($userInfo['sgbets_fee']['Equal']<100)
                    {
                        if (!empty($betMoney['x1_equal']))
                        {
                            $money = $money + (1 - $userInfo['sgbets_fee']['Equal']) * $betMoney['x1_equal'] * $agentInfo['pump']/100;
                        }
                    }
                }
                if ($winner['x2result']=="win")
                {
                    $x2Num = $this->sConvertNumbers($winner['x2num']);
                    if ($userInfo['sgbets_fee']['SuperDouble']<100)
                    {
                        if (!empty($betMoney['x2_Super_Double']))
                        {
                            if ($x2Num>9)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x2_Super_Double'] * $agentInfo['pump']/100 * 10;
                            }
                            elseif ($x2Num>0 && $x2Num<10)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x2_Super_Double'] * $agentInfo['pump']/100 * $x2Num;
                            }
                            else
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x2_Super_Double'] * $agentInfo['pump']/100;
                            }
                        }
                    }
                    if ($userInfo['sgbets_fee']['Double']<100)
                    {
                        if (!empty($betMoney['x2_double']))
                        {
                            if ($x2Num>9)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x2_double'] * $agentInfo['pump']/100 * 3;
                            }
                            elseif ($x2Num>6 && $x2Num<10)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x2_double'] * $agentInfo['pump']/100 * 2;
                            }
                            else
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x2_double'] * $agentInfo['pump']/100;
                            }
                        }
                    }
                    if ($userInfo['sgbets_fee']['Equal']<100)
                    {
                        if (!empty($betMoney['x2_equal']))
                        {
                            $money = $money + (1 - $userInfo['sgbets_fee']['Equal']) * $betMoney['x2_equal'] * $agentInfo['pump']/100;
                        }
                    }
                }
                if ($winner['x3result']=="win")
                {
                    $x3Num = $this->sConvertNumbers($winner['x3num']);
                    if ($userInfo['sgbets_fee']['SuperDouble']<100)
                    {
                        if (!empty($betMoney['x3_Super_Double']))
                        {
                            if ($x3Num>9)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x3_Super_Double'] * $agentInfo['pump']/100 * 10;
                            }
                            elseif ($x3Num>0 && $x3Num<10)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x3_Super_Double'] * $agentInfo['pump']/100 * $x3Num;
                            }
                            else
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x3_Super_Double'] * $agentInfo['pump']/100;
                            }
                        }
                    }
                    if ($userInfo['sgbets_fee']['Double']<100)
                    {
                        if (!empty($betMoney['x3_double']))
                        {
                            if ($x3Num>9)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x3_double'] * $agentInfo['pump']/100 * 3;
                            }
                            elseif ($x3Num>6 && $x3Num<10)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x3_double'] * $agentInfo['pump']/100 * 2;
                            }
                            else
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x3_double'] * $agentInfo['pump']/100;
                            }
                        }
                    }
                    if ($userInfo['sgbets_fee']['Equal']<100)
                    {
                        if (!empty($betMoney['x3_equal']))
                        {
                            $money = $money + (1 - $userInfo['sgbets_fee']['Equal']) * $betMoney['x3_equal'] * $agentInfo['pump']/100;
                        }
                    }
                }
                if ($winner['x4result']=="win")
                {
                    $x4Num = $this->sConvertNumbers($winner['x4num']);
                    if ($userInfo['sgbets_fee']['SuperDouble']<100)
                    {
                        if (!empty($betMoney['x4_Super_Double']))
                        {
                            if ($x4Num>9)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x4_Super_Double'] * $agentInfo['pump']/100 * 10;
                            }
                            elseif ($x4Num>0 && $x4Num<10)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x4_Super_Double'] * $agentInfo['pump']/100 * $x4Num;
                            }
                            else
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x4_Super_Double'] * $agentInfo['pump']/100;
                            }
                        }
                    }
                    if ($userInfo['sgbets_fee']['Double']<100)
                    {
                        if (!empty($betMoney['x4_double']))
                        {
                            if ($x4Num>9)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x4_double'] * $agentInfo['pump']/100 * 3;
                            }
                            elseif ($x4Num>6 && $x4Num<10)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x4_double'] * $agentInfo['pump']/100 * 2;
                            }
                            else
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x4_double'] * $agentInfo['pump']/100;
                            }
                        }
                    }
                    if ($userInfo['sgbets_fee']['Equal']<100)
                    {
                        if (!empty($betMoney['x4_equal']))
                        {
                            $money = $money + (1 - $userInfo['sgbets_fee']['Equal']) * $betMoney['x4_equal'] * $agentInfo['pump']/100;
                        }
                    }
                }
                if ($winner['x5result']=="win")
                {
                    $x5Num = $this->sConvertNumbers($winner['x5num']);
                    if ($userInfo['sgbets_fee']['SuperDouble']<100)
                    {
                        if (!empty($betMoney['x5_Super_Double']))
                        {
                            if ($x5Num>9)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x5_Super_Double'] * $agentInfo['pump']/100 * 10;
                            }
                            elseif ($x5Num>0 && $x5Num<10)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x5_Super_Double'] * $agentInfo['pump']/100 * $x5Num;
                            }
                            else
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x5_Super_Double'] * $agentInfo['pump']/100;
                            }
                        }
                    }
                    if ($userInfo['sgbets_fee']['Double']<100)
                    {
                        if (!empty($betMoney['x5_double']))
                        {
                            if ($x5Num>9)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x5_double'] * $agentInfo['pump']/100 * 3;
                            }
                            elseif ($x5Num>6 && $x5Num<10)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x5_double'] * $agentInfo['pump']/100 * 2;
                            }
                            else
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x5_double'] * $agentInfo['pump']/100;
                            }
                        }
                    }
                    if ($userInfo['sgbets_fee']['Equal']<100)
                    {
                        if (!empty($betMoney['x5_equal']))
                        {
                            $money = $money + (1 - $userInfo['sgbets_fee']['Equal']) * $betMoney['x5_equal'] * $agentInfo['pump']/100;
                        }
                    }
                }
                if ($winner['x6result']=="win")
                {
                    $x6Num = $this->sConvertNumbers($winner['x6num']);
                    if ($userInfo['sgbets_fee']['SuperDouble']<100)
                    {
                        if (!empty($betMoney['x6_Super_Double']))
                        {
                            if ($x6Num>9)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x6_Super_Double'] * $agentInfo['pump']/100 * 10;
                            }
                            elseif ($x6Num>0 && $x6Num<10)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x6_Super_Double'] * $agentInfo['pump']/100 * $x6Num;
                            }
                            else
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x6_Super_Double'] * $agentInfo['pump']/100;
                            }
                        }
                    }
                    if ($userInfo['sgbets_fee']['Double']<100)
                    {
                        if (!empty($betMoney['x6_double']))
                        {
                            if ($x6Num>9)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x6_double'] * $agentInfo['pump']/100 * 3;
                            }
                            elseif ($x6Num>6 && $x6Num<10)
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x6_double'] * $agentInfo['pump']/100 * 2;
                            }
                            else
                            {
                                $money = $money + (1 - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x6_double'] * $agentInfo['pump']/100;
                            }
                        }
                    }
                    if ($userInfo['sgbets_fee']['Equal']<100)
                    {
                        if (!empty($betMoney['x6_equal']))
                        {
                            $money = $money + (1 - $userInfo['sgbets_fee']['Equal']) * $betMoney['x6_equal'] * $agentInfo['pump']/100;
                        }
                    }
                }
            }
            elseif ($datum['game_type']==5)
            {
                $winner = json_decode($gameInfo['winner'],true);
                if ($winner['Fanresult']=="win")
                {
                    $fanNum = $this->aConvertNumbers($winner['FanNum']);
                    if ($userInfo['a89bets_fee']['Equal']<100)
                    {
                        if (!empty($betMoney['FanMen_equal']))
                        {
                            $money = $money + (1 - $userInfo['a89bets_fee']['Equal']/100) * $betMoney['FanMen_equal'] * $agentInfo['pump']/100;
                        }
                    }
                    if ($userInfo['a89bets_fee']['SuperDouble']<100)
                    {
                        if (!empty($betMoney['FanMen_Super_Double']))
                        {
                            if ($fanNum>9)
                            {
                                $money = $money + (1 - $userInfo['a89bets_fee']['SuperDouble']/100) * $betMoney['FanMen_Super_Double'] * $agentInfo['pump']/100 * 10;
                            }
                            elseif ($fanNum>0 && $fanNum<10)
                            {
                                $money = $money + (1 - $userInfo['a89bets_fee']['SuperDouble']/100) * $betMoney['FanMen_Super_Double'] * $agentInfo['pump']/100 * $fanNum;
                            }
                            else
                            {
                                $money = $money + (1 - $userInfo['a89bets_fee']['SuperDouble']/100) * $betMoney['FanMen_Super_Double'] * $agentInfo['pump']/100;
                            }
                        }
                    }
                }
                if ($winner['Shunresult']=="win")
                {
                    $shunNum = $this->aConvertNumbers($winner['ShunNum']);
                    if ($userInfo['a89bets_fee']['Equal']<100)
                    {
                        if (!empty($betMoney['ShunMen_equal']))
                        {
                            $money = $money + (1 - $userInfo['a89bets_fee']['Equal']/100) * $betMoney['ShunMen_equal'] * $agentInfo['pump']/100;
                        }
                    }
                    if ($userInfo['a89bets_fee']['SuperDouble']<100)
                    {
                        if (!empty($betMoney['ShunMen_Super_Double']))
                        {
                            if ($shunNum>9)
                            {
                                $money = $money + (1 - $userInfo['a89bets_fee']['SuperDouble']/100) * $betMoney['ShunMen_Super_Double'] * $agentInfo['pump']/100 * 10;
                            }
                            elseif ($shunNum>0 && $shunNum<10)
                            {
                                $money = $money + (1 - $userInfo['a89bets_fee']['SuperDouble']/100) * $betMoney['ShunMen_Super_Double'] * $agentInfo['pump']/100 * $shunNum;
                            }
                            else
                            {
                                $money = $money + (1 - $userInfo['a89bets_fee']['SuperDouble']/100) * $betMoney['ShunMen_Super_Double'] * $agentInfo['pump']/100;
                            }
                        }
                    }
                }
                if ($winner['Tianresult']=="win")
                {
                    $tianNum = $this->aConvertNumbers($winner['TianNum']);
                    if ($userInfo['a89bets_fee']['Equal']<100)
                    {
                        if (!empty($betMoney['TianMen_equal']))
                        {
                            $money = $money + (1 - $userInfo['a89bets_fee']['Equal']/100) * $betMoney['TianMen_equal'] * $agentInfo['pump']/100;
                        }
                    }
                    if ($userInfo['a89bets_fee']['SuperDouble']<100)
                    {
                        if (!empty($betMoney['TianMen_Super_Double']))
                        {
                            if ($tianNum>9)
                            {
                                $money = $money + (1 - $userInfo['a89bets_fee']['SuperDouble']/100) * $betMoney['TianMen_Super_Double'] * $agentInfo['pump']/100 * 10;
                            }
                            elseif ($tianNum>0 && $tianNum<10)
                            {
                                $money = $money + (1 - $userInfo['a89bets_fee']['SuperDouble']/100) * $betMoney['TianMen_Super_Double'] * $agentInfo['pump']/100 * $tianNum;
                            }
                            else
                            {
                                $money = $money + (1 - $userInfo['a89bets_fee']['SuperDouble']/100) * $betMoney['TianMen_Super_Double'] * $agentInfo['pump']/100;
                            }
                        }
                    }
                }
            }
        }
        return $money;
    }

    /**
     * 获取线下代理客损所得
     * @param $deskId
     * @param $tableName
     * @return float|int
     */
    public function getOfflineAccountedMoney($deskId,$tableName)
    {
        $money = 0;
        $order = new Order();
        $order->setTable('order_'.$tableName);
        $data = $order->select('user_id',DB::raw('SUM(get_money) as getMoney'))->where(['desk_id'=>$deskId])->whereIn('status',[1,4])->groupBy('user_id')->get()->toArray();
        foreach ($data as $key=>$value)
        {
            //获取用户
            $userInfo = $this->getUserInfoByUserId($value['user_id']);
            if ($userInfo['user_type']!=1)
            {
                continue;
            }
            //获取直属一级代理
            $agentInfo = $this->getZsYjByAgentId($userInfo['agent_id']);
            if ($value['getMoney']>0)
            {
                continue;
            }
            $money = $money + $value['getMoney'] * $agentInfo['proportion']/100;
        }
        return $money;
    }
    /**
     * 洗码费
     * @param $deskId
     * @param $tableName
     * @return float|int
     */
    public function getSumCode($deskId,$tableName)
    {
        $money = 0;
        $order = new Order();
        $order->setTable('order_'.$tableName);
        $data = $order->where('desk_id','=',$deskId)->where('status','=',1)->get()->toArray();
        foreach ($data as $key=>$datum)
        {
            $betMoney=0;
            $userInfo = HqUser::getUserInfoByUserId($datum['user_id']);
            $agentInfo = $this->getZsYjByAgentId($userInfo['agent_id']);
            if ($userInfo['user_type']!=1)
            {
                continue;
            }
            $bet = json_decode($datum['bet_money'],true);
            if ($datum['game_type']==1 || $datum['game_type']==2)
            {
                $betMoney = array_sum($bet);
                if ($datum['game_type']==1)
                {
                    $money = $money + $betMoney * $agentInfo['fee']['baccarat']/100;
                }else{
                    $money = $money + $betMoney * $agentInfo['fee']['dragonTiger']/100;
                }
            }
            elseif ($datum['game_type']==3)
            {
                if (!empty($bet['x1_Super_Double'])){
                    $betMoney = $betMoney + ($bet['x1_Super_Double']*10);
                }
                if (!empty($bet['x2_Super_Double'])){
                    $betMoney = $betMoney + ($bet['x2_Super_Double']*10);
                }
                if (!empty($bet['x3_Super_Double'])){
                    $betMoney = $betMoney + ($bet['x3_Super_Double']*10);
                }
                if (!empty($bet['x1_double'])){
                    $betMoney = $betMoney + ($bet['x1_double']*3);
                }
                if (!empty($bet['x2_double'])){
                    $betMoney = $betMoney + ($bet['x2_double']*3);
                }
                if (!empty($bet['x3_double'])){
                    $betMoney = $betMoney + ($bet['x3_double']*3);
                }
                if (!empty($bet['x1_equal'])){
                    $betMoney = $betMoney + $bet['x1_equal'];
                }
                if (!empty($bet['x2_equal'])){
                    $betMoney = $betMoney + $bet['x2_equal'];
                }
                if (!empty($bet['x3_equal'])){
                    $betMoney = $betMoney + $bet['x3_equal'];
                }
                $money = $money + $betMoney * $agentInfo['fee']['niuniu']/100;
            }
            elseif ($datum['game_type']==4)
            {
                if (!empty($bet['x1_Super_Double'])){
                    $betMoney = $betMoney + $bet['x1_Super_Double']*10;
                }
                if (!empty($bet['x2_Super_Double'])){
                    $betMoney = $betMoney + $bet['x2_Super_Double']*10;
                }
                if (!empty($bet['x3_Super_Double'])){
                    $betMoney = $betMoney + $bet['x3_Super_Double']*10;
                }
                if (!empty($bet['x4_Super_Double'])){
                    $betMoney = $betMoney + $bet['x4_Super_Double']*10;
                }
                if (!empty($bet['x5_Super_Double'])){
                    $betMoney = $betMoney + $bet['x5_Super_Double']*10;
                }
                if (!empty($bet['x6_Super_Double'])){
                    $betMoney = $betMoney + $bet['x6_Super_Double']*10;
                }
                if (!empty($bet['x1_double'])){
                    $betMoney = $betMoney + $bet['x1_double'] * 3;
                }
                if (!empty($bet['x2_double'])){
                    $betMoney = $betMoney + $bet['x2_double'] * 3;
                }
                if (!empty($bet['x3_double'])){
                    $betMoney = $betMoney + $bet['x3_double'] * 3;
                }
                if (!empty($bet['x4_double'])){
                    $betMoney = $betMoney + $bet['x4_double'] * 3;
                }
                if (!empty($bet['x5_double'])){
                    $betMoney = $betMoney + $bet['x5_double'] * 3;
                }
                if (!empty($bet['x6_double'])){
                    $betMoney = $betMoney + $bet['x6_double'] * 3;
                }
                if (!empty($bet['x1_equal'])){
                    $betMoney = $betMoney + $bet['x1_equal'];
                }
                if (!empty($bet['x2_equal'])){
                    $betMoney = $betMoney + $bet['x2_equal'];
                }
                if (!empty($bet['x3_equal'])){
                    $betMoney = $betMoney + $bet['x3_equal'];
                }
                if (!empty($bet['x4_equal'])){
                    $betMoney = $betMoney + $bet['x4_equal'];
                }
                if (!empty($bet['x5_equal'])){
                    $betMoney = $betMoney + $bet['x5_equal'];
                }
                if (!empty($bet['x6_equal'])){
                    $betMoney = $betMoney + $bet['x6_equal'];
                }
                $money = $money + $betMoney * $agentInfo['fee']['sangong']/100;
            }
            elseif ($datum['game_type']==5)
            {
                if (!empty($bet['ShunMen_Super_Double'])){
                    $betMoney = $betMoney + $bet['ShunMen_Super_Double']*10;
                }
                if (!empty($bet['TianMen_Super_Double']))
                {
                    $betMoney = $betMoney + $bet['TianMen_Super_Double'] *10;
                }
                if (!empty($bet['FanMen_Super_Double']))
                {
                    $betMoney = $betMoney + $bet['FanMen_Super_Double'] * 10;
                }
                if (!empty($bet['ShunMen_equal'])){
                    $betMoney = $betMoney + $bet['ShunMen_equal'];
                }
                if (!empty($bet['TianMen_equal']))
                {
                    $betMoney = $betMoney + $bet['TianMen_equal'];
                }
                if (!empty($bet['FanMen_equal']))
                {
                    $betMoney = $betMoney + $bet['FanMen_equal'];
                }
                $money = $money + $betMoney * $agentInfo['fee']['A89']/100;
            }
        }
        return $money;
    }

    /**抽水
     * @param $deskId
     * @param $tableName
     * @return float|int
     */
    public function getSumPump($deskId,$tableName)
    {
        $money=0;
        $order = new Order();
        $order->setTable('order_'.$tableName);
        $data = $order->where('desk_id','=',$deskId)->get()->toArray();
        foreach ($data as $key=>$datum)
        {
            if ($datum['game_type']==1)
            {
                $money = $money + $this->bjlPump($datum);
            }elseif ($datum['game_type']==2)
            {
                $money = $money + $this->lhPump($datum);
            }elseif ($datum['game_type']==3)
            {
                $money = $money + $this->nnPump($datum);
            }elseif($datum['game_type']==4)
            {
                $money = $money + $this->sgPump($datum);
            }elseif ($datum['game_type']==5)
            {
                $money = $money + $this->aPump($datum);
            }
        }
        return $money;
    }

    /**
     * 百家乐抽水
     * @param $order
     * @return float|int
     */
    public function bjlPump($order)
    {
        $money = 0;
        $betMoney = json_decode($order['bet_money'],true);
        $userInfo = $this->getUserInfoByUserId($order['user_id']);
        $agentInfo = $this->getZsYjByAgentId($userInfo['agent_id']);
        $recordSn = $order['record_sn'];
        $tableName = $this->getGameRecordTableNameByRecordSn($recordSn);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $info = $game->where('record_sn','=',$recordSn)->first();
        $winner = json_decode($info['winner'],true);
        if ($winner['game']==4)
        {
            if ($betMoney['player']>0)
            {
                $money =  ($agentInfo['bjlbets_fee']['player'] - $userInfo['bjlbets_fee']['player']/100) * $betMoney['player'];
            }
        }
        elseif ($winner['game']==7)
        {
            if ($betMoney['banker']>0)
            {
                $money = ($agentInfo['bjlbets_fee']['banker'] - $userInfo['bjlbets_fee']['banker']/100) * $betMoney['banker'];
            }
        }
        elseif ($winner['game']==1)
        {
            if ($betMoney['tie']>0)
            {
                $money =  ($agentInfo['bjlbets_fee']['tie'] - $userInfo['bjlbets_fee']['tie']/100) * $betMoney['tie'];
            }
        }
        if ($winner['bankerPair']==2)
        {
            if ($betMoney['bankerPair']>0)
            {
                $money = $money + ($agentInfo['bjlbets_fee']['bankerPair'] - $userInfo['bjlbets_fee']['bankerPair']/100) * $betMoney['bankerPair'];
            }
        }
        if ($winner['playerPair']==5)
        {
            if ($betMoney['playerPair']>0)
            {
                $money = $money + ($agentInfo['bjlbets_fee']['playerPair'] - $userInfo['bjlbets_fee']['playerPair']/100)*$betMoney['playerPair'];
            }
        }
        return $money;
    }

    /**
     * 龙虎抽水
     * @param $order
     * @return float|int
     */
    public function lhPump($order)
    {
        $money = 0;
        $betMoney = json_decode($order['bet_money'],true);
        $userInfo = $this->getUserInfoByUserId($order['user_id']);
        $agentInfo = $this->getZsYjByAgentId($userInfo['agent_id']);
        $recordSn = $order['record_sn'];
        $tableName = $this->getGameRecordTableNameByRecordSn($recordSn);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $info = $game->where('record_sn','=',$recordSn)->first();
        $winner = $info['winner'];
        if ($winner==1)
        {
            if ($betMoney['tie']>0)
            {
                $money = ($agentInfo['lhbets_fee']['tie'] - $userInfo['lhbets_fee']['tie']/100) * $betMoney['tie'];
            }
        }
        elseif ($winner==4)
        {
            if ($betMoney['tiger']>0)
            {
                $money = ($agentInfo['lhbets_fee']['tiger'] - $userInfo['lhbets_fee']['tiger']/100) * $betMoney['tiger'];
            }
        }
        elseif ($winner==7)
        {
            if ($betMoney['dragon']>0)
            {
                $money = ($agentInfo['lhbets_fee']['dragon'] - $userInfo['lhbets_fee']['dragon']/100) * $betMoney['dragon'];
            }
        }
        return $money;
    }
    /**
     * 三公抽水
     * @param $order
     * @return float|int
     */
    public function sgPump($order)
    {
        $money = 0;
        $betMoney = json_decode($order['bet_money'],true);
        $userInfo = $this->getUserInfoByUserId($order['user_id']);
        $agentInfo = $this->getZsYjByAgentId($userInfo['agent_id']);
        $recordSn = $order['record_sn'];
        $tableName = $this->getGameRecordTableNameByRecordSn($recordSn);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $info = $game->where('record_sn','=',$recordSn)->first();
        $winner = json_decode($info['winner'],true);
        if ($winner['x1result']=="win")
        {
            $x1Num = $this->sConvertNumbers($winner['x1num']);
            if (!empty($betMoney['x1_equal']))
            {
                $money = $money + ($agentInfo['sgbets_fee']['Equal'] - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x1_equal'];
            }
            if (!empty($betMoney['x1_double']))
            {
                //
                if ($x1Num > 9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x1_double'] * 3;
                }elseif ($x1Num>6 && $x1Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x1_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x1_double'];
                }
            }
            if (!empty($betMoney['x1_Super_Double']))
            {
                if ($x1Num>9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x1_Super_Double'] *10;
                }elseif ($x1Num>0 && $x1Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x1_Super_Double'] * $x1Num;
                }else
                {
                    $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x1_Super_Double'];
                }
            }
        }
        if ($winner['x2result']=="win")
        {
            $x2Num = $this->sConvertNumbers($winner['x2num']);
            if (!empty($betMoney['x2_equal']))
            {
                $money = $money + ($agentInfo['sgbets_fee']['Equal'] - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x2_equal'];
            }
            if (!empty($betMoney['x2_double']))
            {
                //
                if ($x2Num > 9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x2_double'] * 3;
                }elseif ($x2Num>6 && $x2Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x2_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x2_double'];
                }
            }
            if (!empty($betMoney['x2_Super_Double']))
            {
                if ($x2Num>9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x2_Super_Double'] *10;
                }elseif ($x2Num>0 && $x2Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x2_Super_Double'] * $x2Num;
                }else
                {
                    $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x2_Super_Double'];
                }
            }
        }
        if ($winner['x3result']=="win")
        {
            $x3Num = $this->sConvertNumbers($winner['x3num']);
            if (!empty($betMoney['x3_equal']))
            {
                $money = $money + ($agentInfo['sgbets_fee']['Equal'] - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x3_equal'];
            }
            if (!empty($betMoney['x3_double']))
            {
                if ($x3Num > 9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x3_double'] * 3;
                }elseif ($x3Num>6 && $x3Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x3_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x3_double'];
                }
            }
            if (!empty($betMoney['x3_Super_Double']))
            {
                if ($x3Num>9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x3_Super_Double'] *10;
                }elseif ($x3Num>0 && $x3Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x3_Super_Double'] * $x3Num;
                }else
                {
                    $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x3_Super_Double'];
                }
            }
        }
        if ($winner['x4result']=="win")
        {
            $x4Num = $this->sConvertNumbers($winner['x4num']);
            if (!empty($betMoney['x4_equal']))
            {
                $money = $money + ($agentInfo['sgbets_fee']['Equal'] - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x4_equal'];
            }
            if (!empty($betMoney['x4_double']))
            {
                //
                if ($x4Num > 9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x4_double'] * 3;
                }elseif ($x4Num>6 && $x4Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x4_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x4_double'];
                }
            }
            if (!empty($betMoney['x4_Super_Double']))
            {
                if ($x4Num>9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x4_Super_Double'] *10;
                }elseif ($x4Num>0 && $x4Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x4_Super_Double'] * $x4Num;
                }else
                {
                    $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x4_Super_Double'];
                }
            }
        }
        if ($winner['x5result']=="win")
        {
            $x5Num = $this->sConvertNumbers($winner['x5num']);
            if (!empty($betMoney['x5_equal']))
            {
                $money = $money + ($agentInfo['sgbets_fee']['Equal'] - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x5_equal'];
            }
            if (!empty($betMoney['x5_double']))
            {
                //
                if ($x5Num > 9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x5_double'] * 3;
                }elseif ($x5Num>6 && $x5Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x5_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x5_double'];
                }
            }
            if (!empty($betMoney['x5_Super_Double']))
            {
                if ($x5Num>9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x5_Super_Double'] *10;
                }elseif ($x5Num>0 && $x5Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x5_Super_Double'] * $x5Num;
                }else
                {
                    $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x5_Super_Double'];
                }
            }
        }
        if ($winner['x6result']=="win")
        {
            $x6Num = $this->sConvertNumbers($winner['x6num']);
            if (!empty($betMoney['x6_equal']))
            {
                $money = $money + ($agentInfo['sgbets_fee']['Equal'] - $userInfo['sgbets_fee']['Equal']/100) * $betMoney['x6_equal'];
            }
            if (!empty($betMoney['x6_double']))
            {
                //
                if ($x6Num > 9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x6_double'] * 3;
                }elseif ($x6Num>6 && $x6Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x6_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['sgbets_fee']['Double'] - $userInfo['sgbets_fee']['Double']/100) * $betMoney['x6_double'];
                }
            }
            if (!empty($betMoney['x6_Super_Double']))
            {
                if ($x6Num>9)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x6_Super_Double'] *10;
                }elseif ($x6Num>0 && $x6Num<10)
                {
                    $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x6_Super_Double'] * $x6Num;
                }else
                {
                    $money = $money + ($agentInfo['sgbets_fee']['SuperDouble'] - $userInfo['sgbets_fee']['SuperDouble']/100) * $betMoney['x6_Super_Double'];
                }
            }
        }
        return $money;
    }
    /**
     * 三公结果转数字
     * @param $str
     * @return int
     */
    public function sConvertNumbers($str){
        switch ($str)
        {
            case "0点":
                $count = 0;
                break;
            case "1点":
                $count=1;
                break;
            case "2点":
                $count=2;
                break;
            case "3点":
                $count=3;
                break;
            case "4点":
                $count=4;
                break;
            case "5点":
                $count=5;
                break;
            case "6点":
                $count=6;
                break;
            case "7点":
                $count=7;
                break;
            case "8点":
                $count=8;
                break;
            case "9点":
                $count=9;
                break;
            case "混三公":
                $count=10;
                break;
            case "小三公":
                $count=11;
                break;
            default:
                $count=12;
        }
        return $count;
    }

    /**
     * 牛牛抽水
     * @param $order
     * @return float|int
     */
    public function nnPump($order)
    {
        $money = 0;
        $betMoney = json_decode($order['bet_money'],true);
        $userInfo = $this->getUserInfoByUserId($order['user_id']);
        $agentInfo = $this->getZsYjByAgentId($userInfo['agent_id']);
        $recordSn = $order['record_sn'];
        $tableName = $this->getGameRecordTableNameByRecordSn($recordSn);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $info = $game->where('record_sn','=',$recordSn)->first();
        $winner = json_decode($info['winner'],true);
        if ($winner['x1result']=="win")
        {
            $x1Num = $this->nConvertNumbers($winner['x1num']);
            if (!empty($betMoney['x1_equal']))
            {
                $money = $money + ($agentInfo['nnbets_fee']['Equal'] - $userInfo['nnbets_fee']['Equal']/100) * $betMoney['x1_equal'];
            }
            if (!empty($betMoney['x1_double']))
            {
                //
                if ($x1Num > 9)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x1_double'] * 3;
                }elseif ($x1Num>6 && $x1Num<10)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x1_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x1_double'];
                }
            }
            if (!empty($betMoney['x1_Super_Double']))
            {
                if ($x1Num>9)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['SuperDouble'] - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x1_Super_Double'] *10;
                }elseif ($x1Num>0 && $x1Num<10)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['SuperDouble'] - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x1_Super_Double'] * $x1Num;
                }else
                {
                    $money = $money + ($agentInfo['nnbets_fee']['SuperDouble'] - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x1_Super_Double'];
                }
            }
        }
        if ($winner['x2result']=="win")
        {
            $x2Num = $this->nConvertNumbers($winner['x2num']);
            if (!empty($betMoney['x2_equal']))
            {
                $money = $money + ($agentInfo['nnbets_fee']['Equal'] - $userInfo['nnbets_fee']['Equal']/100) * $betMoney['x2_equal'];
            }
            if (!empty($betMoney['x2_double']))
            {
                if ($x2Num > 9)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x2_double'] * 3;
                }elseif ($x2Num>6 && $x2Num<10)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x2_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x2_double'];
                }
            }
            if (!empty($betMoney['x2_Super_Double']))
            {
                if ($x2Num>9)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['SuperDouble'] - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x2_Super_Double'] *10;
                }elseif ($x2Num>0 && $x2Num<10)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['SuperDouble'] - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x2_Super_Double'] * $x2Num;
                }else
                {
                    $money = $money + ($agentInfo['nnbets_fee']['SuperDouble'] - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x2_Super_Double'];
                }
            }
        }
        if ($winner['x3result']=="win")
        {
            $x3Num = $this->nConvertNumbers($winner['x3num']);
            if (!empty($betMoney['x3_equal']))
            {
                $money = $money + ($agentInfo['nnbets_fee']['Equal'] - $userInfo['nnbets_fee']['Equal']/100) * $betMoney['x3_equal'];
            }
            if (!empty($betMoney['x3_double']))
            {
                if ($x3Num > 9)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x3_double'] * 3;
                }elseif ($x3Num>6 && $x3Num<10)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x3_double'] * 2;
                }else{
                    $money = $money + ($agentInfo['nnbets_fee']['Double'] - $userInfo['nnbets_fee']['Double']/100) * $betMoney['x3_double'];
                }
            }
            if (!empty($betMoney['x3_Super_Double']))
            {
                if ($x3Num>9)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['SuperDouble'] - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x3_Super_Double'] *10;
                }elseif ($x3Num>0 && $x3Num<10)
                {
                    $money = $money + ($agentInfo['nnbets_fee']['SuperDouble'] - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x3_Super_Double'] * $x3Num;
                }else
                {
                    $money = $money + ($agentInfo['nnbets_fee']['SuperDouble'] - $userInfo['nnbets_fee']['SuperDouble']/100) * $betMoney['x3_Super_Double'];
                }
            }
        }
        return $money;
    }

    /**
     * 获取直属一级
     * @param $agentId
     * @return Agent|Agent[]|array|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function getZsYjByAgentId($agentId)
    {
        $agent = $this->getAgentInfoById($agentId);
        $ancestors = explode(',',$agent['ancestors']);
        $ancestors[] = $agent['id'];
        return $this->getAgentInfoById($ancestors[1]);
    }
    /**
     * a89抽水
     * @param $order
     * @return float|int
     */
    public function aPump($order)
    {
        $money = 0;
        $betMoney = json_decode($order['bet_money'],true);
        $userInfo = $this->getUserInfoByUserId($order['user_id']);
        $agentInfo = $this->getZsYjByAgentId($userInfo['agent_id']);
        $recordSn = $order['record_sn'];
        $tableName = $this->getGameRecordTableNameByRecordSn($recordSn);
        $game = new GameRecord();
        $game->setTable('game_record_'.$tableName);
        $info = $game->where('record_sn','=',$recordSn)->first();
        $winner = json_decode($info['winner'],true);
        if ($winner['Fanresult']=="win")
        {
            $fanNum = $this->aConvertNumbers($winner['FanNum']);
            if (!empty($betMoney['FanMen_equal']))
            {
                $money = $money + ($agentInfo['a89bets_fee']['Equal'] - $userInfo['a89bets_fee']['Equal']/100) * $betMoney['FanMen_equal'];
            }
            if (!empty($betMoney['FanMen_Super_Double']))
            {
                $money = $money + ($agentInfo['a89bets_fee']['SuperDouble'] - $userInfo['a89bets_fee']['SuperDouble']/100) * $betMoney['FanMen_Super_Double'] * $fanNum;
            }
        }
        if ($winner['Shunresult']=="win")
        {
            $shunMen = $this->aConvertNumbers($winner['ShunNum']);
            if (!empty($betMoney['ShunMen_equal']))
            {
                $money = $money + ($agentInfo['a89bets_fee']['Equal'] - $userInfo['a89bets_fee']['Equal']/100) * $betMoney['ShunMen_equal'];
            }
            if (!empty($betMoney['ShunMen_Super_Double']))
            {
                $money = $money + ($agentInfo['a89bets_fee']['SuperDouble'] - $userInfo['a89bets_fee']['SuperDouble']/100) * $betMoney['ShunMen_Super_Double'] * $shunMen;
            }
        }
        if ($winner['Tianresult']=="win")
        {
            $fanNum = $this->aConvertNumbers($winner['TianNum']);
            if (!empty($betMoney['TianMen_equal']))
            {
                $money = $money + ($agentInfo['a89bets_fee']['Equal'] - $userInfo['a89bets_fee']['Equal']/100) * $betMoney['TianMen_equal'];
            }
            if (!empty($betMoney['FanMen_Super_Double']))
            {
                $money = $money + ($agentInfo['a89bets_fee']['SuperDouble'] - $userInfo['a89bets_fee']['SuperDouble']/100) * $betMoney['FanMen_Super_Double'] * $fanNum;
            }
        }
        return $money;
    }
    /**
     * 获取总输赢
     * @param $deskId
     * @param $tableName
     * @return int|mixed
     */
    public function getGetMoney($deskId,$tableName)
    {
        $order = new Order();
        $order->setTable('order_'.$tableName);
        return $order->where(['desk_id'=>$deskId,'status'=>1])->sum('get_money');
    }

    /**
     * a89结果转数字
     * @param $str
     * @return int
     */
    public function aConvertNumbers($str){
        switch ($str)
        {
            case "0点":
                $count=1;
                break;
            case "1点":
                $count=1;
                break;
            case "2点":
                $count=2;
                break;
            case "3点":
                $count=3;
            case "4点":
                $count=4;
                break;
            case "5点":
                $count=5;
                break;
            case "6点":
                $count=6;
                break;
            case "7点":
                $count=7;
                break;
            case "8点":
                $count=8;
                break;
            case "9点":
                $count=9;
                break;
            default:
                $count=10;
        }
        return $count;
    }

    /**
     * 把牛牛游戏结果转成数字
     * @param $str
     * @return int
     */
    public function nConvertNumbers($str){
        $num=0;
        switch ($str)
        {
            case "炸弹牛":
                $num=12;
                break;
            case "五花牛":
                $num=11;
                break;
            case "牛牛":
                $num=10;
                break;
            case "牛9":
                $num=9;
                break;
            case "牛8":
                $num=8;
                break;
            case "牛7":
                $num=7;
                break;
            case "牛6":
                $num=6;
                break;
            case "牛5":
                $num=5;
                break;
            case "牛4":
                $num=4;
                break;
            case "牛3":
                $num=3;
                break;
            case "牛2":
                $num=2;
                break;
            case "牛1":
                $num=1;
                break;
            default:
                $num=0;
                break;
        }
        return $num;
    }

    //根据游戏单号获取表名
    public function getGameRecordTableNameByRecordSn($recordSn)
    {
        return substr($recordSn,0,8);
    }

    /**
     * 获取龙虎台桌的总下注金额
     * @param $deskId 台桌id
     * @param $gameId 游戏类型
     * @param $bootNum
     * @param $paveNum
     * @param $tableName 表名
     * @return float|int|mixed
     */
    public function getDragonTieTigerMoney($deskId,$gameId,$bootNum,$paveNum,$tableName){
        $money = 0;
        $order = new Order();
        $order->setTable('order_'.$tableName);
        $sql = $order->where(['desk_id'=>$deskId,'game_type'=>$gameId,'status'=>1]);
        if ($bootNum!=0){
            $sql->where('boot_num','=',$bootNum);
        }
        if ($paveNum!=0){
            $sql->where('pave_num','=',$paveNum);
        }
        $data = $sql->get();
        foreach ($data as $key=>$datum){
            //获取游戏记录表表名
            $recordTName = $this->getGameRecordTableName($datum['record_sn']);
            //获取游戏详情
            $recordInfo = GameRecord::getGameRecordInfo($datum['record_sn'],$recordTName);
            //判断游戏结果
            if ($recordInfo['winner']!=1){//结果不为和
                $money = $money + array_sum(json_decode($datum['bet_money'],true));
            }else{
                //如果这把游戏结果为和
                $betMoney = json_decode($datum['bet_money'],true);
                $money = $money + $betMoney['tie'];
            }
        }
        return $money;
    }

    /**
     * 根据userId获取用户
     * @param $userId
     * @return HqUser|HqUser[]|array|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function getUserInfoByUserId($userId)
    {
        $user = $userId?HqUser::find($userId):[];
        $user['bjlbets_fee']=json_decode($user['bjlbets_fee'],true);
        $user['lhbets_fee']=json_decode($user['lhbets_fee'],true);
        $user['nnbets_fee']=json_decode($user['nnbets_fee'],true);
        $user['sgbets_fee']=json_decode($user['sgbets_fee'],true);
        $user['a89bets_fee']=json_decode($user['a89bets_fee'],true);
        return $user;
    }

    /**
     * 根据agentId获取代理
     * @param $agentId
     * @return Agent|Agent[]|array|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function getAgentInfoById($agentId)
    {
        $agent = $agentId?Agent::find($agentId):[];
        $agent['fee']=json_decode($agent['fee'],true);
        $agent['bjlbets_fee']=json_decode($agent['bjlbets_fee'],true);
        $agent['lhbets_fee']=json_decode($agent['lhbets_fee'],true);
        $agent['nnbets_fee']=json_decode($agent['nnbets_fee'],true);
        $agent['sgbets_fee']=json_decode($agent['sgbets_fee'],true);
        $agent['a89bets_fee']=json_decode($agent['a89bets_fee'],true);
        return $agent;
    }

    /**
     * 获取百家乐台桌的总下注金额
     * @param $deskId 台桌id
     * @param $gameId 游戏id
     * @param $bootNum
     * @param $paveNum
     * @param $tableName 表名
     * @return float|int|mixed
     */
    public function getBaccaratMoney($deskId,$gameId,$bootNum,$paveNum,$tableName){
        $money = 0;
        $order = new Order();
        $order->setTable('order_'.$tableName);
        $sql = $order->where(['desk_id'=>$deskId,'game_type'=>$gameId,'status'=>1]);
        if ($bootNum!=0){
            $sql->where('boot_num','=',$bootNum);
        }
        if ($paveNum!=0){
            $sql->where('pave_num','=',$paveNum);
        }
        $data = $sql->get()->toArray();
        foreach ($data as $key=>$datum){
            //获取游戏记录表名
            $recordTName = $this->getGameRecordTableName($datum['record_sn']);
            //获取游戏详情
            $recordInfo = GameRecord::getGameRecordInfo($datum['record_sn'],$recordTName);
            //把游戏结果转成数组
            $winner = json_decode($recordInfo['winner'],true);
            if ($winner['game']!=1){//游戏结果不为和
                $money = $money + array_sum(json_decode($datum['bet_money'],true));
            }else{
                $betMoney = json_decode($datum['bet_money'],true);
                $money = $money + $betMoney['tie'];
            }
        }
        return $money;
    }

    /**
     * 获取牛牛全部下注金额
     * @param $deskId
     * @param $gameId
     * @param $bootNum
     * @param $paveNum
     * @param $tableName
     * @return int
     */
    public function getNiuNiuMoney($deskId,$gameId,$bootNum,$paveNum,$tableName){
        $money = 0;
        $order = new Order();
        $order->setTable('order_'.$tableName);
        $sql = $order->where(['desk_id'=>$deskId,'game_type'=>$gameId,'status'=>1]);
        if ($bootNum!=0){
            $sql->where('boot_num','=',$bootNum);
        }
        if ($paveNum!=0)
        {
            $sql->where('pave_num','=',$paveNum);
        }
        $data = $sql->get()->toArray();
        foreach ($data as $key=>$datum){
            $winner = json_decode($datum['bet_money'],true);
            //{"x3_Super_Double":2000,"x3_double":2000} {"x3_equal":5000}
            if (!empty($winner['x1_Super_Double'])){
                $money = $money + ($winner['x1_Super_Double']*10);
            }
            if (!empty($winner['x2_Super_Double'])){
                $money = $money + ($winner['x2_Super_Double']*10);
            }
            if (!empty($winner['x3_Super_Double'])){
                $money = $money + ($winner['x3_Super_Double']*10);
            }
            if (!empty($winner['x1_double'])){
                $money = $money + ($winner['x1_double']*3);
            }
            if (!empty($winner['x2_double'])){
                $money = $money + ($winner['x2_double']*3);
            }
            if (!empty($winner['x3_double'])){
                $money = $money + ($winner['x3_double']*3);
            }
            if (!empty($winner['x1_equal'])){
                $money = $money + $winner['x1_equal'];
            }
            if (!empty($winner['x2_equal'])){
                $money = $money + $winner['x2_equal'];
            }
            if (!empty($winner['x3_equal'])){
                $money = $money + $winner['x3_equal'];
            }
        }
        return $money;
    }

    /**
     * 获取三公总下注
     * @param $deskId
     * @param $gameId
     * @param $bootNum
     * @param $paveNum
     * @param $tableName
     * @return int
     */
    public function getSanGongMoney($deskId,$gameId,$bootNum,$paveNum,$tableName)
    {
        $money = 0;
        $order = new Order();
        $order->setTable('order_'.$tableName);
        $sql = $order->where(['desk_id'=>$deskId,'game_type'=>$gameId,'status'=>1]);
        if ($bootNum!=0){
            $sql->where('boot_num','=',$bootNum);
        }
        if ($paveNum!=0){
            $sql->where('pave_num','=',$paveNum);
        }
        $data = $sql->get()->toArray();
        foreach ($data as $key=>$value)
        {
            $winner = json_decode($value['bet_money'],true);
            if (!empty($winner['x1_Super_Double'])){
                $money = $money + $winner['x1_Super_Double']*10;
            }
            if (!empty($winner['x2_Super_Double'])){
                $money = $money + $winner['x2_Super_Double']*10;
            }
            if (!empty($winner['x3_Super_Double'])){
                $money = $money + $winner['x3_Super_Double']*10;
            }
            if (!empty($winner['x4_Super_Double'])){
                $money = $money + $winner['x4_Super_Double']*10;
            }
            if (!empty($winner['x5_Super_Double'])){
                $money = $money + $winner['x5_Super_Double']*10;
            }
            if (!empty($winner['x6_Super_Double'])){
                $money = $money + $winner['x6_Super_Double']*10;
            }
            if (!empty($winner['x1_double'])){
                $money = $money + $winner['x1_double'] * 3;
            }
            if (!empty($winner['x2_double'])){
                $money = $money + $winner['x2_double'] * 3;
            }
            if (!empty($winner['x3_double'])){
                $money = $money + $winner['x3_double'] * 3;
            }
            if (!empty($winner['x4_double'])){
                $money = $money + $winner['x4_double'] * 3;
            }
            if (!empty($winner['x5_double'])){
                $money = $money + $winner['x5_double'] * 3;
            }
            if (!empty($winner['x6_double'])){
                $money = $money + $winner['x6_double'] * 3;
            }
            if (!empty($winner['x1_equal'])){
                $money = $money + $winner['x1_equal'];
            }
            if (!empty($winner['x2_equal'])){
                $money = $money + $winner['x2_equal'];
            }
            if (!empty($winner['x3_equal'])){
                $money = $money + $winner['x3_equal'];
            }
            if (!empty($winner['x4_equal'])){
                $money = $money + $winner['x4_equal'];
            }
            if (!empty($winner['x5_equal'])){
                $money = $money + $winner['x5_equal'];
            }
            if (!empty($winner['x6_equal'])){
                $money = $money + $winner['x6_equal'];
            }
        }
        return $money;
    }

    public function getA89Money($deskId,$gameId,$bootNum,$paveNum,$tableName)
    {
        $money = 0;
        $order = new Order();
        $order->setTable('order_'.$tableName);
        $sql=$order->where('desk_id','=',$deskId)->where('game_type','=',$gameId)->where('status','=',1);
        if ($bootNum!=0)
        {
            $sql->where('boot_num','=',$bootNum);
        }
        if ($paveNum!=0)
        {
            $sql->where('pave_num','=',$paveNum);
        }
        $data = $sql->get()->toArray();
        foreach ($data as $key=>$value)
        {
            $betMoney = json_decode($value['bet_money'],true);
            if (!empty($betMoney['ShunMen_Super_Double'])){
                $money = $money + $betMoney['ShunMen_Super_Double']*10;
            }
            if (!empty($betMoney['TianMen_Super_Double']))
            {
                $money = $money + $betMoney['TianMen_Super_Double'] *10;
            }
            if (!empty($betMoney['FanMen_Super_Double']))
            {
                $money = $money + $betMoney['FanMen_Super_Double'] * 10;
            }
            if (!empty($betMoney['ShunMen_equal'])){
                $money = $money + $betMoney['ShunMen_equal'];
            }
            if (!empty($betMoney['TianMen_equal']))
            {
                $money = $money + $betMoney['TianMen_equal'];
            }
            if (!empty($betMoney['FanMen_equal']))
            {
                $money = $money + $betMoney['FanMen_equal'];
            }
        }
        return $money;
    }

    /**
     * 解析order表中得bet_money获取总金额
     * @param $deskId
     * @param $bootNum
     * @param $paveNum
     * @param $tableName
     * @return float|int
     */
    public function getCalculationMoney($deskId,$bootNum,$paveNum,$tableName)
    {
        $sum=0;
        $order = new Order();
        $order->setTable('order_'.$tableName);
        $sql = $order->where('desk_id','=',$deskId)->where('status','=',1);
        if ($bootNum!=0){
            $sql->where('boot_num','=',$bootNum);
        }
        if ($paveNum!=0){
            $sql->where('pave_num','=',$paveNum);
        }
        $data = $sql->get()->toArray();
        foreach ($data as $key=>$datum){
            $bet = json_decode($datum['bet_money'],true);
            $sum = $sum + array_sum($bet);
        }
        return $sum;
    }

    /**
     * 获取游戏输赢情况
     * @param $deskId
     * @param $bootNum
     * @param $paveNum
     * @param $tableName
     * @return int|mixed
     */
    public function getWinAndErrMoney($deskId,$bootNum,$paveNum,$tableName)
    {
        $order = new Order();
        $order->setTable('order_'.$tableName);
        $sql = $order->where('desk_id','=',$deskId)->where('status','=',1);
        if ($bootNum!=0){
            $sql->where('boot_num','=',$bootNum);
        }
        if ($paveNum!=0){
            $sql->where('pave_num','=',$paveNum);
        }
        return $sql->sum('get_money');
    }

    /**
     * 获取游戏记录表名
     * @param $str 游戏记录编号
     * @return false|string
     */
    public function getGameRecordTableName($str){
        return substr($str,0,8);
    }
}