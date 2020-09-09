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
        if (true==$request->has('excel'))
        {
            $game = new GameRecord();
            $game->setTable('game_record_'.$tableName);
            $excelData = $game->leftJoin('desk','game_record_'.$tableName.'.desk_id','=','desk.id')
            ->select('desk.desk_name','game_record_'.$tableName.'.boot_num','game_record_'.$tableName.'.pave_num','game_record_'.$tableName.'.creatime',
            'game_record_'.$tableName.'.winner','game_record_'.$tableName.'.update_result_before','game_record_'.$tableName.'.type','game_record_'.$tableName.'.update_by')->where($map)->get()->toArray();
            foreach ($excelData as $key=>$datum)
            {
                $excelData[$key]['creatime']=date('Y-m-d H:i:s',$datum['creatime']);
                if ($excelData[$key]['type'] == 1) {//百家乐
                    $excelData[$key]['type']="百家乐";
                    $excelData[$key]['winner'] = $this->getBaccaratParseJson($excelData[$key]['winner']);
                    $excelData[$key]['winner'] = $excelData[$key]['winner']['game'].$excelData[$key]['winner']['playerPair'].$excelData[$key]['winner']['bankerPair'];
                    if ($excelData[$key]['update_result_before'] != '') {
                        $excelData[$key]['update_result_before'] = $this->getBaccaratParseJson($excelData[$key]['update_result_before']);
                        $excelData[$key]['update_result_before'] = $excelData[$key]['update_result_before']['game'].$excelData[$key]['update_result_before']['playerPair'].$excelData[$key]['update_result_before']['bankerPair'];
                    }
                } else if ($excelData[$key]['type'] == 2) {//龙虎
                    $excelData[$key]['type']="龙虎";
                    $excelData[$key]['winner'] = $this->getDragonTigerJson($excelData[$key]['winner']);
                    if ($excelData[$key]['update_result_before'] != '') {
                        $excelData[$key]['update_result_before'] = $this->getDragonTigerJson($excelData[$key]['update_result_before']);
                    }
                } else if ($excelData[$key]['type'] == 3) {//牛牛
                    $excelData[$key]['type']="牛牛";
                    $excelData[$key]['winner'] = $this->getNiuNiu($excelData[$key]['winner']);
                    if ($excelData[$key]['update_result_before'] != '') {
                        $excelData[$key]['update_result_before'] = $this->getNiuNiu($excelData[$key]['update_result_before']);
                    }
                } else if ($excelData[$key]['type'] == 4) {
                    $excelData[$key]['type']="三公";
                    $excelData[$key]['winner'] = $this->getSanGong($excelData[$key]['winner']);
                    if ($excelData[$key]['update_result_before']!= '')
                    {
                        $excelData[$key]['update_result_before'] = $this->getSanGong($excelData[$key]['update_result_before']);
                    }
                } else {
                    $excelData[$key]['type']="A89";
                    $excelData[$key]['winner'] = $this->getA89($excelData[$key]['winner']);
                    if ($excelData[$key]['update_result_before']!='')
                    {
                        $excelData[$key]['update_result_before'] = $this->getA89($excelData[$key]['update_result_before']);
                    }
                }
            }
            $head = array('台桌','靴号','铺号','时间','结果','修改前结果','游戏类型');
            try {
                exportExcel($head, $excelData, date('Y-m-d',time()).'游戏记录查询', '', true);
            } catch (\PHPExcel_Reader_Exception $e) {
            } catch (\PHPExcel_Exception $e) {
            }
        }
        $data = $gameRecord->where($map)->orderBy('creatime','desc')->paginate($limit)->appends($request->all());
        foreach($data as $key=>&$value){
            $data[$key]['desk']=Desk::getDeskInfo($value['desk_id']);
            $data[$key]['creatime']=date('Y-m-d H:i:s',$value['creatime']);
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
    public function getNiuNiu($jsonStr)
    {
        $str = '';
        $data = json_decode($jsonStr, true);
        //先判断庄是不是通吃
        if ($data['x1result'] == "" && $data['x2result'] == "" && $data['x3result'] == "") {
            $str = "庄";
        } else {
            if ($data['x1result'] == "win") {
                $str = $str. "闲1";
            }
            if ($data['x2result'] == "win") {
                $str = $str."闲2";
            }
            if ($data['x3result'] == "win") {
                $str=$str."闲3";
            }
        }
        return $str;
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

    public function getSanGong($jsonStr)
    {
        $str = '';
        $data = json_decode($jsonStr,true);
        //{"bankernum":"9点","x1num":"小三公","x1result":"win","x2num":"混三公","x2result":"win","x3num":"大三公","x3result":"win","x4num":"0点","x4result":"", "x5num":"1点", "x5result":"", "x6num":"9点", "x6result":""}
        //判断庄是否通吃
        if ($data['x1result']=='' && $data['x2result']=="" && $data['x3result']=="" && $data['x4result']=="" && $data['x5result']=="" && $data['x6result']==""){
            $str = "庄";
        }else{
            if ($data['x1result'] == "win") {
                $str = $str."闲1";
            }
            if ($data['x2result'] == "win") {
                $str = $str."闲2";
            }
            if ($data['x3result'] == "win") {
                $str = $str."闲3";
            }
            if ($data['x4result'] == "win") {
                $str = $str."闲4";
            }
            if ($data['x5result'] == "win") {
                $str = $str."闲5";
            }
            if ($data['x6result'] == "win") {
                $str = $str."闲6";
            }
        }
        return $str;
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

    public function getA89($jsonStr)
    {
        $str = '';
        $data = json_decode($jsonStr,true);
        //{"BankerNum":"5点","FanNum":"0点","Fanresult":"","ShunNum":"8点","Shunresult":"win","TianNum":"5点","Tianresult":"win"}
        //判断庄是否通知
        $arr = array();
        if ($data['Fanresult']=="" && $data['Shunresult']=="" && $data['Tianresult']==""){
            $str = "庄";
        }
        if ($data['Fanresult'] == "win") {
            $str = $str."反门";
        }
        if ($data['Shunresult'] == "win") {
            $str = $str."顺门";
        }
        if ($data['Tianresult']=="win"){
            $str = $str."天门";
        }
        return $str;
    }
}