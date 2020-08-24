<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Desk;
use App\Models\GameRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameRecordController extends Controller
{
    /**
     * 数据列表
     */
    public function index(Request $request){
        if(true==$request->has('begin')){
            $tableName = date('Ymd',strtotime($request->input('begin')));
        }else{
            $tableName = date('Ymd',time());
            $request->offsetSet('begin',date('Y-m-d',time()));
        }
        $map = array();
        if(true==$request->has('desk_id')){
            $map['desk_id']=$request->input('desk_id');
        }
        if(true==$request->has('boot')){
            $map['boot_num']=$request->input('boot');
        }
        if (true==$request->has('pave'))
        {
            $map['pave_num']=$request->input('pave');
        }
        $gameRecord = new GameRecord();
        $gameRecord->setTable('game_record_'.$tableName);
        if (true==$request->has('limit'))
        {
            $limit = $request->input('limit');
        }
        else
        {
            $limit = 10;
        }
        $data = $gameRecord->where($map)->orderBy('creatime','desc')->paginate($limit)->appends($request->all());
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
                $data[$key]['result'] = $this->getSanGongResult($data[$key]['winner']);
                if ($data[$key]['update_result_before']!= '')
                {
                    $data[$key]['afterResult'] = $this->getSanGongResult($data[$key]['update_result_before']);
                }
            } else {
                $data[$key]['result'] = $this->getA89Result($data[$key]['winner']);
                if ($data[$key]['update_result_before']!='')
                {
                    $data[$key]['afterResult'] = $this->getA89Result($data[$key]['update_result_before']);
                }
            }
        }
        return view('gameRecord.list',['list'=>$data,'limit'=>$limit,'desk'=>Desk::getDeskList(),'input'=>$request->all()]);
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
            $arr['x3result'] = "";
        }
        $arr['num']='庄'.$data['bankernum'].' 闲1 '.$data['x1num'].' 闲2 '.$data['x2num'].' 闲3 '.$data['x3num'];
        return $arr;
    }
    /**
     * 三公
     * @param $jsonStr
     * @return array
     */
    public function getSanGongResult($jsonStr){
        $arr = array();
        //解析json
        $data = json_decode($jsonStr,true);
        //{"bankernum":"9点","x1num":"小三公","x1result":"win","x2num":"混三公","x2result":"win","x3num":"大三公","x3result":"win","x4num":"0点","x4result":"", "x5num":"1点", "x5result":"", "x6num":"9点", "x6result":""}
        //判断庄是否通吃
        if ($data['x1result']=='' && $data['x2result']=="" && $data['x3result']=="" && $data['x4result']=="" && $data['x5result']=="" && $data['x6result']==""){
            $arr['bankernum'] = "庄";
        }else{
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
            $arr['x3result'] = "";
        }
        if ($data['x4result'] == "win") {
            $arr['x4result'] = "闲4";
        } else {
            $arr['x4result'] = "";
        }
        if ($data['x5result'] == "win") {
            $arr['x5result'] = "闲5";
        } else {
            $arr['x5result'] = "";
        }
        if ($data['x6result'] == "win") {
            $arr['x6result'] = "闲6";
        } else {
            $arr['x6result'] = "";
        }
        $arr['num']='庄'.$data['bankernum'].' 闲1 '.$data['x1num'].' 闲2 '.$data['x2num'].' 闲3 '.$data['x3num'].' 闲4 '.$data['x4num'].' 闲5 '.$data['x5num'].' 闲6 '.$data['x6num'];
        return $arr;
    }

    /**
     * A89
     */
    public function getA89Result($jsonStr){
        $data = json_decode($jsonStr,true);
        //{"BankerNum":"5点","FanNum":"0点","Fanresult":"","ShunNum":"8点","Shunresult":"win","TianNum":"5点","Tianresult":"win"}
        //判断庄是否通知
        $arr = array();
        if ($data['Fanresult']=="" && $data['Shunresult']=="" && $data['Tianresult']==""){
            $arr['bankernum'] = "庄";
        }else{
            $arr['bankernum'] = "";
        }
        if ($data['Fanresult'] == "win") {
            $arr['Fanresult'] = "反门";
        } else {
            $arr['Fanresult'] = "";
        }
        if ($data['Shunresult'] == "win") {
            $arr['Shunresult'] = "顺门";
        } else {
            $arr['Shunresult'] = "";
        }
        if ($data['Tianresult']=="win"){
            $arr['Tianresult'] = "天门";
        }else{
            $arr['Tianresult'] = "";
        }
        $arr['num']='庄'.$data['BankerNum'].' 反门'.$data['FanNum'].'顺门'.$data['ShunNum'].'天门'.$data['TianNum'];
        return $arr;
    }
}