@section('title', '台桌输赢情况')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#x1002;</i></button>
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="begin" name="begin" placeholder="开始日期" onclick="layui.laydate({elem: this,format:'YYYY-MM-DD hh:mm:ss',istime:true, festival: true,min:'{{$min}}'})" value="{{ $input['begin'] or '' }}" autocomplete="off">
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="end" name="end" placeholder="结束日期" onclick="layui.laydate({elem: this,format:'YYYY-MM-DD hh:mm:ss',istime:true, festival: true,min:'{{$min}}'})" value="{{$input['end'] or ''}}" autocomplete="off">
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
        <input type="text" lay-verify="" value="{{$input['boot_num'] or ''}}" name="boot_num" placeholder="靴号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="" value="{{$input['pave_num'] or ''}}" name="pave_num" placeholder="铺号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="" value="{{$input['orderSn'] or ''}}" name="orderSn" placeholder="注单号" autocomplete="off" class="layui-input">
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
            <th class="hidden-xs" style="font-size: 1px;">状态</th>
        </tr>
        </thead>
        <tbody>
        @foreach($list as $info)
            <tr>
                <td class="hidden-xs" style="font-size: 1px;">{{$info->order_sn}}</td>
                <td class="hidden-xs" style="font-size: 1px;">
                    @if($info->game_type==1)
                        百家乐
                    @elseif($info->game_type==2)
                        龙虎
                    @elseif($info->game_type==3)
                        牛牛
                    @elseif($info->game_type==4)
                        三公
                    @elseif($info->game_type==5)
                        A89
                    @endif
                </td>
                <td class="hidden-xs" style="font-size: 1px;">{{$info->desk_name}}</td>
                <td class="hidden-xs" style="font-size: 1px;">{{$info->creatime}}</td>
                <td class="hidden-xs" style="font-size: 1px;">{{$info->boot_num}}</td>
                <td class="hidden-xs" style="font-size: 1px;">{{$info->pave_num}}</td>
                <td class="hidden-xs" style="font-size: 1px;">{{$info->user['nickname']}}[{{$info->user['account']}}]</td>
                <td class="hidden-xs" style="font-size: 1px;">{{$info->bill['bet_before']/100}}</td>
                <td class="hidden-xs" style="font-size: 1px;">
                    <button type="button" data-id="{{$info->id}}" data-value="{{$info->bet_money}}" class="layui-btn layui-btn-small layui-btn-normal result">查看结果</button>
                </td>
                <td class="hidden-xs" style="font-size: 1px;">{{$info->bill['bet_after']/100}}</td>
                <td class="hidden-xs">
                    @if($info->status==2)
                        -
                    @elseif($info->status==1)
                        @if($info->game_type==1)
                            {{$info->result['game']}}&nbsp;{{$info->result['playerPair']}} {{$info->result['bankerPair']}}
                        @elseif($info->game_type==2)
                            {{$info->result}}
                        @elseif($info->game_type==3)
                            @if($info->result['bankernum']=="")
                                {{$info->result['x1result']}}&nbsp;{{$info->result['x2result']}}&nbsp;{{$info->result['x3result']}}
                            @else
                                {{$info->result['bankernum']}}
                            @endif
                        @elseif($info->game_type==4)
                            @if($info->result['bankernum']=="")
                                {{$info->result['x1result']}}&nbsp;{{$info->result['x2result']}}&nbsp;{{$info->result['x3result']}}
                                {{$info->result['x4result']}}&nbsp;{{$info->result['x5result']}}&nbsp;{{$info->result['x6result']}}
                            @else
                                {{$info->result['bankernum']}}
                            @endif
                        @elseif($info->game_type==5)
                            @if($info->result['bankernum']=="")
                                {{$info->result['Fanresult']}} {{$info->result['Shunresult']}} {{$info->result['Tianresult']}}
                            @else
                                {{$info->result['bankernum']}}
                            @endif
                        @endif
                    @elseif($info->status==3)
                        -
                    @endif
                </td>
                <td class="hidden-xs">{{$info->money/100}}</td>
                <td class="hidden-xs">{{$info->money/100}}</td>
                <td class="hidden-xs">{{$info->get_money/100}}</td>
                <td class="hidden-xs">
                    @if($info->status ==1)
                        结算完成
                    @elseif($info->status==2)
                        玩家取消
                    @elseif($info->status==0)
                        等待开牌
                    @elseif($info->status==3)
                        作废
                    @endif
                </td>
            </tr>
        @endforeach
        @if(count($list)==0)
            <tr><td colspan="18" style="text-align: center;color: orangered;">暂无数据</td></tr>
        @endif
        </tbody>
    </table>
    <div class="page-wrap">
        <div id="demo1"></div>
    </div>
@endsection
@section('js')
    <script>
        layui.use(['form', 'jquery','laydate', 'layer','laypage'], function() {
            var form = layui.form(),
                $ = layui.jquery,
                laydate = layui.laydate,
                layer = layui.layer,
                laypage = layui.laypage
            ;
            var pages = {{$pages}};

            var curr = {{$curr}};
            var url = "";
            laypage({
                cont: 'demo1'
                ,pages: pages //总页数
                ,curr:curr
                ,groups: 5 //连续显示分页数
                ,jump:function (obj,first) {
                    if(url.indexOf("?") >= 0){
                        url = url.split("?")[0] + "?pageNum=" + obj.curr;
                    }else{
                        url = url + "?pageNum=" + obj.curr;
                    }
                    if (!first){
                        location.href = url;
                    }
                }
            });
            laydate({istoday: true});
            $(".reset").click(function(){
                $("input[name='begin']").val('');
                $("select[name='desk_id']").val('');
                $("select[name='type']").val('');
                $("select[name='status']").val('');
                $("input[name='boot_num']").val('');
                $("input[name='pave_num']").val('');
                $("input[name='orderSn']").val('');
            });
            $(".result").click(function () {
                var id = $(this).attr('data-id');
                var value = $(this).attr('data-value');
                layer.open({
                    type:1,
                    offset:'auto',
                    id:"id"+id,
                    content: '<div style="padding: 20px 100px;">'+value+'</div>',
                    btn:'关闭',
                    btnAlign: 'c',
                    shade:0,
                    yes:function () {
                        layer.closeAll();
                    }
                });
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