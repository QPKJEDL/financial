@section('title', '台桌输赢情况')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#xe9aa;</i></button>
        <button class="layui-btn layui-btn-normal reset" lay-submit>重置</button>
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="begin" name="begin" placeholder="开始日期" id="begin" value="{{ $input['begin'] or '' }}" autocomplete="off">
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="end" name="end" placeholder="结束日期" id="end" value="{{$input['end'] or ''}}" autocomplete="off">
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
        <input type="text" lay-verify="" value="{{$input['boot_num'] or ''}}" name="boot_num" placeholder="靴号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="" value="{{$input['pave_num'] or ''}}" name="pave_num" placeholder="铺号" autocomplete="off" class="layui-input">
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
        <input type="text" lay-verify="" value="{{$input['orderSn'] or ''}}" name="orderSn" placeholder="注单号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <select name="status" lay-filter="status" lay-verify="status">
            <option value="">请选择状态</option>
            <option value="0" {{isset($input['status'])&&$input['status']=='0'?'selected':''}}>等待开牌</option>
            <option value="1" {{isset($input['status'])&&$input['status']=='1'?'selected':''}}>结算完成</option>
            <option value="2" {{isset($input['status'])&&$input['status']=='2'?'selected':''}}>玩家取消</option>
            <option value="3" {{isset($input['status'])&&$input['status']=='3'?'selected':''}}>作废</option>
            <option value="4" {{isset($input['status'])&&$input['status']=='4'?'selected':''}}>结果修改</option>
        </select>
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify value="{{$input['account'] or ''}}" name="account" placeholder="多个会员账号用英文版逗号隔开" autocomplete="off" class="layui-input"/>
    </div>
    <div class="layui-inline">
        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="formDemo">搜索</button>
        <button class="layui-btn layui-btn-normal" lay-submit name="excel" value="excel">导出EXCEL</button>
    </div>
@endsection
@section('table')
    <table class="layui-table" lay-size="sm" id="table">
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
                <td class="hidden-xs" style="font-size: 1px;">{{number_format($info->bill['bet_before']/100,2)}}</td>
                <td class="hidden-xs" style="font-size: 1px;">
                    {{$info->bet_money}}
                </td>
                <td class="hidden-xs" style="font-size: 1px;">{{number_format($info->bill['bet_after']/100,2)}}</td>
                <td class="hidden-xs">
                    @if($info->status==2)
                        -
                    @elseif($info->status==1 || $info->status==4)
                        @if($info->game_type==1)
                            <span style="@if($info->result['game']=='闲') color:blue;@elseif($info->result['game']=='庄') color:red;@else color:green;@endif">{{$info->result['game']}}</span>&nbsp;{{$info->result['playerPair']}} {{$info->result['bankerPair']}}
                        @elseif($info->game_type==2)
                            <span style="@if($info->result=='龙') color: red; @elseif($info->result=='虎') color:blue; @else color:green;@endif">{{$info->result}}</span>
                        @elseif($info->game_type==3)
                            @if($info->result['bankernum']=="")
                                <span style="color: blue;">{{$info->result['x1result']}}&nbsp;{{$info->result['x2result']}}&nbsp;{{$info->result['x3result']}}</span>
                            @else
                                <span style="color: red;">{{$info->result['bankernum']}}</span>
                            @endif
                                [庄：{{$info->winner['bankernum']}} 闲1：{{$info->winner['x1num']}} 闲2：{{$info->winner['x2num']}} 闲3：{{$info->winner['x3num']}}]
                        @elseif($info->game_type==4)
                            @if($info->result['bankernum']=="")
                                <span style="color: blue;">{{$info->result['x1result']}}&nbsp;{{$info->result['x2result']}}&nbsp;{{$info->result['x3result']}}
                                    {{--{{$info->result['x4result']}}&nbsp;{{$info->result['x5result']}}&nbsp;{{$info->result['x6result']}}--}}</span>
                            @else
                                <span style="color: red;">{{$info->result['bankernum']}}</span>
                            @endif
                            [庄：{{$info->winner['bankernum']}} 闲1：{{$info->winner['x1num']}} 闲2：{{$info->winner['x2num']}} 闲3：{{$info->winner['x3num']}} {{--闲4：{{$info->winner['x4num']}} 闲5：{{$info->winner['x5num']}} 闲6：{{$info->winner['x6num']}}--}}]
                        @elseif($info->game_type==5)
                            @if($info->result['bankernum']=="")
                                <span style="color: blue;">{{$info->result['Fanresult']}} {{$info->result['Shunresult']}} {{$info->result['Tianresult']}}</span>
                            @else
                                <span style="color: red;">{{$info->result['bankernum']}}</span>
                            @endif
                            [庄：{{$info->winner['BankerNum']}} 反门：{{$info->winner['FanNum']}} 顺们：{{$info->winner['ShunNum']}} 天门：{{$info->winner['TianNum']}}]
                        @endif
                    @elseif($info->status==3)
                        -
                    @endif
                </td>
                <td class="hidden-xs">{{number_format($info->money/100,2)}}</td>
                <td class="hidden-xs">
                    @if($info->status==1 || $info->status==4)
                        {{number_format($info->xmCode/100,2)}}
                    @else
                        0.00
                    @endif
                </td>
                <td class="hidden-xs">
                    @if($info->get_money<0)
                        <span style="color: red;">{{number_format($info->get_money/100,2)}}</span>
                    @else
                        {{number_format($info->get_money/100,2)}}
                    @endif
                </td>
                <td class="hidden-xs">
                    @if($info->user['user_type']!=1)
                        -
                    @else
                        @if($info->status==1 || $info->status==4)
                            @if($info->game_type==1)
                                {{$info->user['fee']['baccarat']}}
                            @elseif($info->game_type==2)
                                {{$info->user['fee']['dragonTiger']}}
                            @elseif($info->game_type==3)
                                {{$info->user['fee']['niuniu']}}
                            @elseif($info->game_type==4)
                                {{$info->user['fee']['sangong']}}
                            @elseif($info->game_type==5)
                                {{$info->user['fee']['A89']}}
                            @endif
                        @else
                            -
                        @endif
                    @endif
                </td>
                <td class="hidden-xs">
                    @if($info->status==1 || $info->status==4)
                        @if($info->user['user_type']==1)
                            @if($info->game_type==1)
                                {{number_format(($info->money/100)*($info->user['fee']['baccarat']/100),2)}}
                            @elseif($info->game_type==2)
                                {{number_format(($info->money/100)*($info->user['fee']['dragonTiger']/100),2)}}
                            @elseif($info->game_type==3)
                                {{number_format(($info->money/100)*($info->user['fee']['niuniu']/100),2)}}
                            @elseif($info->game_type==4)
                                {{number_format(($info->money/100)*($info->user['fee']['sangong']/100),2)}}
                            @elseif($info->game_type==5)
                                {{number_format(($info->money/100)*($info->user['fee']['A89']/100),2)}}
                            @endif
                        @else
                            0.00
                        @endif
                    @else
                        0.00
                    @endif
                </td>
                <td class="hidden-xs">
                    @if($info->status ==1)
                        <span style="color: green;">结算完成</span>
                    @elseif($info->status==2)
                        <span style="color: red;">玩家取消</span>
                    @elseif($info->status==0)
                        等待开牌
                    @elseif($info->status==3)
                        <span style="color: red;">作废</span>
                    @elseif($info->status==4)
                        <span style="color: blue;">结果修改</span>
                    @endif
                </td>
            </tr>
        @endforeach
        @if(count($list)==0)
            <tr><td colspan="17" style="text-align: center;color: orangered;">暂无数据</td></tr>
        @endif
        </tbody>
    </table>
    <div class="page-wrap" style="text-align: center;">
        <div id="demo1"></div>
    </div>
@endsection
@section('js')
    <script>
        layui.use(['form', 'jquery','laydate', 'layer','laypage'], function() {
            var form = layui.form,
                $ = layui.jquery,
                laydate = layui.laydate,
                layer = layui.layer,
                laypage = layui.laypage
            ;
            var date = new Date();
            var max = date.getFullYear()+'-'+(date.getMonth()+1) +'-'+date.getDate();
            laydate.render({
                elem:"#begin",
                min:'{{$min}}',
                max:max
            });
            laydate.render({
                elem:"#end",
                min:'{{$min}}',
                max:max
            });
            var pages = {{$pages}};
            var curr = {{$curr}};
            var limit = {{$limit}};
            var url = "";
            laypage.render({
                elem:"demo1"
                ,count: pages
                ,curr:curr
                ,limit:limit
                ,limits:[100,500,1000]
                ,layout: ['count', 'prev', 'page', 'next', 'limit', 'refresh', 'skip']
                ,jump: function(obj,first){
                    if(url.indexOf("?") >= 0){
                        url = url.split("?")[0] + "?pageNum=" + obj.curr + "&limit="+ obj.limit + "&" +$("form").serialize();
                    }else{
                        url = url + "?pageNum=" + obj.curr + "&limit="+obj.limit;
                    }
                    if (!first){
                        location.href = url;
                    }
                }
            });
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
                    var endTime = $("input[name='end']").val();
                    var end = Date.parse(new Date(endTime));
                    if (begin>end){
                        return '开始时间不能大于结束时间'
                    }
                }
            });
            form.on('submit(formDemo)', function(data) {
            });
        });
    </script>
@endsection
@extends('common.list')