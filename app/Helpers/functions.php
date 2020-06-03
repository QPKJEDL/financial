<?php
/**
 * 判断是否为不可操作id
 *
 * @param	number	$id	参数id
 * @param	string	$configName	配置名
 * @param	bool  $emptyRetValue
 * @param	string	$split 分隔符
 * @return	bool
 */

use App\Models\DeskLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

if (!function_exists('is_config_id')) {
    function is_config_id($id, $configName, $emptyRetValue = false, $split = ",")
    {
        if (empty($configName)) return $emptyRetValue;
        $str = trim(config($configName, ""));
        if (empty($str)) return $emptyRetValue;
        $ids = explode($split, $str);
        return in_array($id, $ids);
    }
}
/*
 * 过滤 空格和字符
 */
function htmlformat($str){
    return  preg_replace('/\'/', '', str_replace(" ",'',htmlspecialchars($str)));
}
/**获取角色id
 * @param $id
 * @return mixed
 */
function getrole($id){
     return DB::table('admin_role_user')->where('user_id',$id)->value('role_id');
}
/**获取周
 * @param $date
 * @return float
 */
function computeWeek($date,$status = 'true'){
    date_default_timezone_set('PRC');
    if($status){
        $diff = strtotime($date);
    }else{
        $diff = $date;
    }
    $res = ceil(($diff - 1564934399)/(24*60*60*7));
    return $res;
}

/**
 * 添加台桌操作日志
 * @param $deskId 台桌id
 * @param $action 操作动作
 */
function insertDeskLogOperaType($deskId,$action)
{
    $data = array();
    $data['desk_id']=$deskId;
    $data['log_type']=1;
    $data['action']=$action;
    $data['create_by']= getLoginUser();
    $data['create_time']=time();
    DeskLog::insert($data);
}

function getLoginUser()
{
    $user = Auth::user();
    return $user['username'];
}

/**
 * 添加台桌修改结果日志
 * @param $deskId 台桌id
 * @param $record 游戏记录编号
 * @param $bootNum 靴次
 * @param $paveNum 铺次
 * @param $bootTime 主靴日期
 * @param $beforeResult 修改前结果
 * @param $afterResult 修改后结果
 */
function insertDeskLogOperaResultType($deskId,$record,$bootNum,$paveNum,$bootTime,$beforeResult,$afterResult)
{
    $data = array();
    $data['desk_id']=$deskId;
    $data['log_type']=2;
    $data['record_sn']= $record;
    $data['boot_num']=$bootNum;
    $data['pave_num']=$paveNum;
    $data['boot_time']=$bootTime;
    $data['before_result']=$beforeResult;
    $data['after_result']=$afterResult;
    $data['create_by']=getLoginUser();
    $data['create_time']=time();
    DeskLog::insert($data);
}

/**

 * 数据导出

 * @param array $title   标题行名称

 * @param array $data   导出数据

 * @param string $fileName 文件名

 * @param string $savePath 保存路径

 * @param $type   是否下载  false--保存   true--下载

 * @return string   返回文件全路径

 * @throws PHPExcel_Exception

 * @throws PHPExcel_Reader_Exception

 */
function exportExcel($title=array(), $data=array(), $fileName='', $savePath='./', $isDown=false){

    $obj = new PHPExcel();
    //横向单元格标识
    $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
    $obj->getActiveSheet(0)->setTitle('sheet名称');   //设置sheet名称
    $_row = 1;   //设置纵向单元格标识
    if($title){
        $_cnt = count($title);
        $obj->getActiveSheet(0)->mergeCells('A'.$_row.':'.$cellName[$_cnt-1].$_row);   //合并单元格
        $obj->setActiveSheetIndex(0)->setCellValue('A'.$_row, '数据导出：'.date('Y-m-d H:i:s'));  //设置合并后的单元格内容
        $_row++;
        $i = 0;
        foreach($title AS $v){   //设置列标题
            $obj->setActiveSheetIndex(0)->setCellValue($cellName[$i].$_row, $v);
            $i++;
        }
        $_row++;
    }
    //填写数据
    if($data){
        $i = 0;
        foreach($data AS $_v){
            $j = 0;
            foreach($_v AS $_cell){
                $obj->getActiveSheet(0)->setCellValue($cellName[$j] . ($i+$_row), $_cell);
                $j++;
            }
            $i++;
        }
    }
    //文件名处理
    if(!$fileName){
        $fileName = uniqid(time(),true);
    }
    $objWrite = PHPExcel_IOFactory::createWriter($obj, 'Excel2007');
    if($isDown){   //网页下载
        header('pragma:public');
        header("Content-Disposition:attachment;filename=$fileName.xls");
        $objWrite->save('php://output');exit;
    }
    $_fileName = iconv("utf-8", "gb2312", $fileName);   //转码
    $_savePath = $savePath.$_fileName.'.xlsx';
    $objWrite->save($_savePath);
    return $savePath.$fileName.'.xlsx';
}