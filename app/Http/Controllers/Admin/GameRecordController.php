<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Desk;
use App\Models\GameRecord;
use Illuminate\Http\Request;

class GameRecordController extends Controller
{
    /**
     * 数据列表
     */
    public function index(Request $request){
        if(true==$request->has('begin')){
            $tableName = date('Ymd',strtotime($request->input('begin')));
        }else{
            $tableName = date('Ymd',strtotime('-1day'));
        }
        $map = array();
        if(true==$request->has('desk_id')){
            $map['desk_id']=$request->input('desk_id');
        }
        if(true==$request->has('boot')){
            $map['boot_num']=$request->input('boot');
        }
        $gameRecord = new GameRecord();
        $gameRecord->setTable('game_record_'.$tableName);
        $data = $gameRecord->where($map)->paginate(10)->appends($request->all());
        foreach($data as $key=>&$value){
            $data[$key]['desk']=Desk::getDeskInfo($value['desk_id']);
            $data[$key]['creatime']=date('Y-m-d H:m:s',$value['creatime']);
            if ($data[$key]['type'] == 1) {//百家乐
                $data[$key]['result'] = $this->getBaccaratParseJson($data[$key]['winner']);
                if ($data[$key]['update_result_before'] != '') {
                    $data[$key]['afterResult'] = $this->getBaccaratParseJson($data[$key]['update_result_before']);
                }
            } else if ($data[$key]['type'] == 2) {//龙虎
                $data[$key]['result'] = $this->getDragonTigerJson($data[$key]['winner']);
                if ($data[$key]['update_result_before'] != '') {
                    $data[$key]['afterResult'] = $this->getDragonTigerJson($data[$key]['update_result_before']);
                }
            } else if ($data[$key]['type'] == 3) {//牛牛
                $data[$key]['result'] = $this->getFullParseJson($data[$key]['winner']);
                if ($data[$key]['update_result_before'] != '') {
                    $data[$key]['afterResult'] = $this->getFullParseJson($data[$key]['update_result_before']);
                }
            } else if ($data[$key]['type'] == 4) {

            } else {

            }
        }
        $min=config('admin.min_date');
        return view('gameRecord.list',['list'=>$data,'min'=>$min,'desk'=>Desk::getDeskList(),'input'=>$request->all()]);
    }

    /**
     * 百家乐
     */
    public function getBaccaratParseJson($jsonStr)
    {
        $arr = array();
        //json格式数据
        //{"game":4,"playerPair":5,"bankerPair":2}
        $data = json_decode($jsonStr, true);
        if ($data['game'] == 1) {
            $arr['game'] = "和";
        } else if ($data['game'] == 4) {
            $arr['game'] = "闲";
        } else {
            $arr['game'] = "庄";
        }
        if (empty($data['playerPair'])) {
            $arr['playerPair'] = "";
        } else {
            $arr['playerPair'] = "闲对";
        }
        if (empty($data['bankerPair'])) {
            $arr['bankerPair'] = "";
        } else {
            $arr['bankerPair'] = "庄对";
        }
        return $arr;
    }

    /**
     * 龙虎
     */
    public function getDragonTigerJson($winner)
    {
        if ($winner == 7) {
            $result = "龙";
        } else if ($winner == 4) {
            $result = "虎";
        } else {
            $result = "和";
        }
        return $result;
    }

    /**
     * 牛牛
     */
    public function getFullParseJson($jsonStr)
    {
        $arr = array();
        //解析json
        //{"bankernum":"牛1","x1num":"牛牛","x1result":"win","x2num":"牛2","x2result":"win","x3num":"牛3","x3result":"win"}
        $data = json_decode($jsonStr, true);
        //先判断庄是不是通吃
        if ($data['x1result'] == "" && $data['x2result'] == "" && $data['x3result'] == "") {
            $arr['bankernum'] = "庄";
        } else {
            $arr['bankernum'] = "";
        }
        if ($data['x1result'] == "win") {
            $arr['x1result'] = "闲1";
        } else {
            $arr['x1result'] = "";
        }
        if ($data['x2result'] == "win") {
            $arr['x2result'] = "闲2";
        } else {
            $arr['x2result'] = "";
        }
        if ($data['x3result'] == "win") {
            $arr['x3result'] = "闲3";
        } else {
            $arr['x4result'] = "";
        }
        return $arr;
    }
}