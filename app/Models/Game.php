<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $table = "game";

    /**
     * 根据游戏类型id获取到游戏名称
     */
    public static function getGameNameByGameId($gameId)
    {
        $data = Game::where('id','=',$gameId)->first();
        return $data['game_name'];
    }

    /**
     * 获取type等于1的数据
     */
    public static function getGameByType(){
        $data = Game::where('type','=',1)->get();
        return $data;
    }
}