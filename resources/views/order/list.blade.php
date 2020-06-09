@section('title', '台桌输赢情况')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#x1002;</i></button>
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="begin" name="begin" placeholder="日期" onclick="layui.laydate({elem: this, festival: true,min:'{{$min}}'})" value="{{ $input['begin'] or '' }}" autocomplete="off">
    </div>
    <div class="layui-inline">
        <select name="desk_id">
            <option value="">请选择台桌</option>
            @foreach($desk as $d)
                <option value="{{$d['id']}}" {{isset($input['desk_id'])&&$input['desk_id']==$d['id']?'selected':''}}>{{$d['desk_name']}}</option>
            @endforeach
        </select>
    </div>
    <div class="layui-inline">
        <select name="type">
            <option value="">请选择游戏类型</option>
            @foreach($game as $g)
                <option value="{{$g['id']}}" {{isset($input['type'])&&$input['type']==$g['id']?'selected':''}}>{{$g['game_name']}}</option>
            @endforeach
        </select>
    </div>
    <div class="layui-inline">
        <select name="status" lay-filter="status" lay-verify="status">
            <option value="">请选择状态</option>
            <option value="0" {{isset($input['status'])&&$input['status']=='0'?'selected':''}}>等待开牌</option>
            <option value="1" {{isset($input['status'])&&$input['status']=='1'?'selected':''}}>结算完成</option>
            <option value="2" {{isset($input['status'])&&$input['status']=='2'?'selected':''}}>玩家取消</option>
        </select>
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="" value="" name="boot" placeholder="靴号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="" value="" name="pave" placeholder="铺号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="" value="" name="orderSn" placeholder="注单号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="formDemo">搜索</button>
        <button class="layui-btn layui-btn-normal reset" lay-submit>重置</button>
    </div>
@endsection
@section('table')
    <table class="layui-table" lay-even lay-skin="nob">
        <colgroup>
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="300">
        </colgroup>
        <thead>
        <tr>
            <th class="hidden-xs" style="font-size: 1px;">注单号</th>
            <th class="hidden-xs" style="font-size: 1px;">台类型</th>
            <th class="hidden-xs" style="font-size: 1px;">台号</th>
            <th class="hidden-xs" style="font-size: 1px;">下注时间</th>
            <th class="hidden-xs" style="font-size: 1px;">靴号</th>
            <th class="hidden-xs" style="font-size: 1px;">铺号</th>
            <th class="hidden-xs" style="font-size: 1px;">会员名称[账号]</th>
            <th class="hidden-xs" style="font-size: 1px;">下注前余额</th>
            <th class="hidden-xs" style="font-size: 1px;">注单详情</th>
            <th class="hidden-xs" style="font-size: 1px;">下注后余额</th>
            <th class="hidden-xs" style="font-size: 1px;">开牌结果</th>
            <th class="hidden-xs" style="font-size: 1px;">下注金额</th>
            <th class="hidden-xs" style="font-size: 1px;">洗码量</th>
            <th class="hidden-xs" style="font-size: 1px;">会员赢</th>
            <th class="hidden-xs" style="font-size: 1px;">洗码率%</th>
            <th class="hidden-xs" style="font-size: 1px;">会员码佣</th>
            <th class="hidden-xs" style="font-size: 1px;">状态</th>
        </tr>
        </thead>
        <tbody>
        @foreach($list as $info)
            <tr>
                <td class="hidden-xs" style="font-size: 1px;">{{$info['order_sn']}}</td>
                <td class="hidden-xs" style="font-size: 1px;">
                    @if($info['game_type']==1)
                        百家乐
                    @elseif($info['game_type']==2)
                        龙虎
                    @elseif($info['game_type']==3)
                        牛牛
                    @elseif($info['game_type']==4)
                        三公
                    @elseif($info['game_type']==5)
                        A89
                    @endif
                </td>
                <td class="hidden-xs" style="font-size: 1px;">{{$info['desk_name']}}</td>
                <td class="hidden-xs" style="font-size: 1px;">{{$info['creatime']}}</td>
                <td class="hidden-xs" style="font-size: 1px;">{{$info['boot_num']}}</td>
                <td class="hidden-xs" style="font-size: 1px;">{{$info['pave_num']}}</td>
                <td class="hidden-xs" style="font-size: 1px;">{{$info['user']['nickname']}}[{{$info['user']['account']}}]</td>
                <td class="hidden-xs" style="font-size: 1px;">{{$info['bill']['bet_before']/100}}</td>
                <td class="hidden-xs" style="font-size: 1px;">{{$info['bet_money']}}</td>
                <td class="hidden-xs" style="font-size: 1px;">{{$info['bill']['bet_after']/100}}</td>
                <td class="hidden-xs">
                    @if($info['status']==2)
                        -
                    @elseif($info['status']==1)
                        @if($info['game_type']==1)
                            {{$info['result']['game']}}&nbsp;{{$info['result']['playerPair']}} {{$info['result']['bankerPair']}}
                        @elseif($info['game_type']==2)
                            {{$info['result']}}
                        @elseif($info['game_type']==3)
                            @if($info['result']['bankernum']=="")
                                {{$info['result']['x1result']}}&nbsp;{{$info['result']['x2result']}}&nbsp;{{$info['result']['x3result']}}
                            @else
                                {{$info['result']['bankernum']}}
                            @endif
                        @endif
                    @elseif($info['status']==3)
                        -
                    @endif
                </td>
                <td class="hidden-xs">{{$info['bill']['score']/100}}</td>
                <td class="hidden-xs">{{$info['bill']['score']/100}}</td>
                <td class="hidden-xs">{{$info['get_money']/100}}</td>
                <td class="hidden-xs">
                    @if($info['status']==1)
                        @if($info['game_type']==1)
                            {{$info['user']['fee']['baccarat']}}
                        @elseif($info['game_type']==2)
                            {{$info['user']['fee']['dragonTiger']}}
                        @elseif($info['game_type']==3)
                            {{$info['user']['fee']['niuniu']}}
                        @elseif($info['game_type']==4)
                            {{$info['user']['fee']['sangong']}}
                        @elseif($info['game_type']==5)
                            {{$info['user']['fee']['A89']}}
                        @endif
                    @else
                        -
                    @endif
                </td>
                <td class="hidden-xs">
                    @if($info['status']==1)
                        @if($info['game_type']==1)
                            {{($info['bill']['score']/100)*($info['user']['fee']['baccarat']/100)}}
                        @elseif($info['game_type']==2)
                            {{($info['bill']['score']/100)*($info['user']['fee']['dragonTiger']/100)}}
                        @elseif($info['game_type']==3)
                            {{($info['bill']['score']/100)*($info['user']['fee']['niuniu']/100)}}
                        @elseif($info['game_type']==4)
                            {{($info['bill']['score'])*($info['user']['fee']['sangong']/100)}}
                        @elseif($info['game_type']==5)
                            {{($info['bill']['score']/100)*($info['user']['fee']['A89']/100)}}
                        @endif
                    @else
                        -
                    @endif
                </td>
                <td class="hidden-xs">
                    @if($info['status']==1)
                        结算完成
                    @elseif($info['status']==2)
                        玩家取消
                    @elseif($info['status']==0)
                        等待开牌
                    @elseif($info['status']==3)
                        作废
                    @endif
                </td>
            </tr>
        @endforeach
        @if(!$list[0])
            <tr><td colspan="18" style="text-align: center;color: orangered;">暂无数据</td></tr>
        @endif
        </tbody>
    </table>
    <div class="page-wrap">
        {{$list->render()}}
    </div>
@endsection
@section('js')
    <script>
        layui.use(['form', 'jquery','laydate', 'layer'], function() {
            var form = layui.form(),
                $ = layui.jquery,
                laydate = layui.laydate,
                layer = layui.layer
            ;
            laydate({istoday: true});
            $(".reset").click(function(){
                $("input[name='begin']").val('');
            });
            form.render();
            form.verify({
                begin:function(value){
                    var begin = Date.parse(new Date(value));
                    //获取当前时间戳
                    var nowTime = (new Date()).getTime();
                    if(begin>nowTime){
                        return "选择的日期不能大于今天的日期";
                    }
                }
            });
            form.on('submit(formDemo)', function(data) {
            });
        });
    </script>
@endsection
@extends('common.list')