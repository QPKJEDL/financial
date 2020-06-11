<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GameRecord extends Model
{
    protected $table;

    /**
     * 获取游戏记录详情
     * @param $record
     * @param $tableName
     * @return GameRecord|Model|null
     */
    public static function getGameRecordInfo($record,$tableName){
        $gameRecord = new GameRecord();
        $gameRecord->setTable("game_record_".$tableName);
        return $gameRecord->where('record_sn','=',$record)->first();
    }
}