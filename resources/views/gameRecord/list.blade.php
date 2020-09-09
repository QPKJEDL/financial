@section('title', '会员列表')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#xe9aa;</i></button>
        <button class="layui-btn layui-btn-normal reset" lay-submit>重置</button>
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="begin" name="begin" placeholder="日期" id="begin" value="{{ $input['begin'] or '' }}" autocomplete="off">
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
        <input type="text" lay-verify="" value="{{ $input['boot'] or ''}}" onkeyup="if(this.value.length==1){this.value=this.value.replace(/[^1-9]/g,'')}else{this.value=this.value.replace(/\D/g,'')}" name="boot" placeholder="靴号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="" value="{{$input['pave'] or ''}}" onkeyup="if(this.value.length==1){this.value=this.value.replace(/[^1-9]/g,'')}else{this.value=this.value.replace(/\D/g,'')}" name="pave" placeholder="铺号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="formDemo">搜索</button>
        <button class="layui-btn layui-btn-normal" lay-submit name="excel" value="excel">导出</button>
    </div>
@endsection
@section('table')
    <table class="layui-table" lay-size="sm">
        <colgroup>
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
        </colgroup>
        <thead>
        <tr>
            <th class="hidden-xs">台桌</th>
            <th class="hidden-xs">靴号</th>
            <th class="hidden-xs">铺号</th>
            <th class="hidden-xs">时间</th>
            <th class="hidden-xs">结果</th>
            <th class="hidden-xs">修改前结果</th>
            <th class="hidden-xs">操作人</th>
        </tr>
        </thead>
        <tbody>
        @foreach($list as $info)
            <tr>
                <td class="hidden-xs">{{$info['desk']['desk_name']}}</td>
                <td class="hidden-xs">{{$info['boot_num']}}</td>
                <td class="hidden-xs">{{$info['pave_num']}}</td>
                <td class="hidden-xs">{{$info['creatime']}}</td>
                <td class="hidden-xs">
                @if($info['status']==1)
                        @if($info['type']==1)
                            <span style="@if($info['result']['game']=='和')color: green;@elseif($info['result']['game']=='庄') color: red;@else color: blue;@endif">{{$info['result']['game']}}</span>&nbsp;<span style="color: black;">{{$info['result']['playerPair']}}</span> <span style="color: black;">{{$info['result']['bankerPair']}}</span>
                        @elseif($info['type']==2)
                            <span style="@if($info['result']=='龙')color:red;@elseif($info['result']=='虎') color:blue;@else color:green;@endif">{{$info['result']}}</span>
                        @elseif($info['type']==3)
                            @if($info['result']['bankernum']=="")
                                <span style="color: blue;">{{$info['result']['x1result']}}</span>&nbsp;<span style="color: blue;">{{$info['result']['x2result']}}</span>&nbsp;<span style="color: blue;">{{$info['result']['x3result']}}</span>
                            @else
                                <span style="color: red;">{{$info['result']['bankernum']}}</span>
                            @endif
                            [{{$info['result']['num']}}]
                        @elseif($info['type']==4)
                            @if($info['result']['bankernum']=="")
                                <span style="color: blue;">{{$info['result']['x1result']}}</span>&nbsp;<span style="color: blue;">{{$info['result']['x2result']}}</span>&nbsp;<span style="color: blue;">{{$info['result']['x3result']}}</span>
                                <span style="color: blue;">{{$info['result']['x4result']}}</span>&nbsp;<span style="color: blue;">{{$info['result']['x5result']}}</span>&nbsp;<span style="color: blue;">{{$info['result']['x6result']}}</span>
                            @else
                                <span style="color: red;">{{$info['result']['bankernum']}}</span>
                            @endif
                            [{{$info['result']['num']}}]
                        @elseif($info['type']==5)
                            @if($info['result']['bankernum']=="")
                                <span style="color: blue;">{{$info['result']['Fanresult']}}</span> <span style="color: blue;">{{$info['result']['Shunresult']}}</span> <span style="color: blue;">{{$info['result']['Tianresult']}}</span>
                            @else
                                <span style="color: red;">{{$info['result']['bankernum']}}</span>
                            @endif
                            [{{$info['result']['num']}}]
                        @endif
                @elseif($info['status']==2)
                    作废
                @elseif($info['status']==0)
                    下注中
                @endif
                </td>
                <td class="hidden-xs">
                @if($info['type']==1)
                        {{$info['afterResult']['game']}}&nbsp;{{$info['afterResult']['playerPair']}} {{$info['afterResult']['bankerPair']}}
                    @elseif($info['type']==2)
                        <span style="@if($info['afterResult']=='龙')color:red;@elseif($info['afterResult']=='虎') color:blue;@else color:green;@endif">{{$info['afterResult']}}</span>
                    @elseif($info['type']==3)
                        @if($info['afterResult']['bankernum']=="")
                            <span style="color: blue;">{{$info['afterResult']['x1result']}}</span>&nbsp;<span style="color: blue;">{{$info['afterResult']['x2result']}}</span>&nbsp;<span style="color: blue;">{{$info['afterResult']['x3result']}}</span>
                        @else
                            <span style="color: red;">{{$info['afterResult']['bankernum']}}</span>
                        @endif
                        @if($info['update_result_before']!='')
                           [{{$info['afterResult']['num']}}]
                        @endif
                    @elseif($info['type']==4)
                        @if($info['afterResult']['bankernum']=="")
                            <span style="color: blue;">{{$info['afterResult']['x1result']}}</span>&nbsp;<span style="color: blue;">{{$info['afterResult']['x2result']}}</span>&nbsp;<span style="color: blue;">{{$info['afterResult']['x3result']}}</span>
                            <span style="color: blue;">{{$info['afterResult']['x4result']}}</span>&nbsp;<span style="color: blue;">{{$info['afterResult']['x5result']}}</span>&nbsp;<span style="color: blue;">{{$info['afterResult']['x6result']}}</span>
                        @else
                            <span style="color: red;">{{$info['afterResult']['bankernum']}}</span>
                        @endif
                            @if($info['update_result_before']!='')
                                [{{$info['afterResult']['num']}}]
                            @endif
                    @elseif($info['type']==5)
                        @if($info['afterResult']['bankernum']=="")
                            <span style="color: blue;">{{$info['afterResult']['Fanresult']}}</span> <span style="color: blue;">{{$info['afterResult']['Shunresult']}}</span> <span style="color: blue;">{{$info['afterResult']['Tianresult']}}</span>
                        @else
                            <span style="color: red;">{{$info['afterResult']['bankernum']}}</span>
                        @endif
                            @if($info['update_result_before']!='')
                                [{{$info['afterResult']['num']}}]
                            @endif
                    @endif
                </td>
                <td class="hidden-xs">{{$info['update_by']}}</td>
            </tr>
        @endforeach
        @if(!$list[0])
            <tr><td colspan="9" style="text-align: center;color: orangered;">暂无数据</td></tr>
        @endif
        </tbody>
    </table>
    <div class="page-wrap">
        <div id="demo"></div>
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
            var count = {{$list->total()}};
            var curr = {{$list->currentPage()}};
            var limit = {{$limit}};
            var url = "";
            //分页
            laypage.render({
                elem: 'demo'
                ,count: count
                ,curr:curr
                ,limit:limit
                ,limits:[10,50,100,150]
                ,layout: ['count', 'prev', 'page', 'next', 'limit', 'refresh', 'skip']
                ,jump: function(obj,first){
                    if(url.indexOf("?") >= 0){
                        url = url.split("?")[0] + "?page=" + obj.curr + "&limit="+ obj.limit + "&" +$("form").serialize();
                    }else{
                        url = url + "?page=" + obj.curr + "&limit="+obj.limit;
                    }
                    if (!first){
                        location.href = url;
                    }
                }
            });
            var date = new Date();
            var max = date.getFullYear()+'-'+(date.getMonth()+1) +'-'+date.getDate();
            laydate.render({
                elem:"#begin",
                max:max
            });
            $(".reset").click(function(){
                $("input[name='begin']").val('');
                $("select[name='desk_id']").val(''); 
                $("input[name='boot']").val('');
                $("input[name='pave']").val('');
            });
            form.on('submit(formDemo)', function(data) {
                console.log(data);
            });
        });
    </script>
@endsection
@extends('common.list')