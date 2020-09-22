@section('title', '台桌输赢情况')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#xe9aa;</i></button>
        <button class="layui-btn layui-btn-normal reset" lay-submit>重置</button>
    </div>
    <div class="layui-inline">
    <input class="layui-input" lay-verify="begin" id="begin" name="begin" placeholder="日期" value="{{ $input['begin'] or '' }}" autocomplete="off">
    </div>
    <div class="layui-inline">
            <select name="deskId">
                <option value="">请选择台桌</option>
                @foreach($desk as $d)
                    <option value="{{$d['id']}}" {{isset($input['deskId'])&&$input['deskId']==$d['id']?'selected':''}}>{{$d['desk_name']}}</option>
                @endforeach
            </select>
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
        </colgroup>
        <thead>
        <tr>
            <th class="hidden-xs">日期</th>
            <th class="hidden-xs">台桌类型</th>
            <th class="hidden-xs">台桌名称</th>
            <th class="hidden-xs">总下注</th>
            <th class="hidden-xs">下注输赢</th>
            <th class="hidden-xs">洗码费</th>
            <th class="hidden-xs">公司总赢</th>
        </tr>
        </thead>
        <tbody>
        @foreach($list as $info)
            <tr>
                <td class="hidden-xs">{{$info['time']}}</td>
                <td class="hidden-xs">{{$info['game_id']}}</td>
                <td class="hidden-xs">{{$info['desk_name']}}</td>
                <td class="hidden-xs">{{number_format($info['betMoney']/100,2)}}</td>
                <td class="hidden-xs">
                    @if($info['getMoney']<0)
                        <span style="color:red;">{{number_format($info['getMoney']/100,2)}}</span>
                    @else
                        {{number_format($info['getMoney']/100,2)}}
                     @endif
                </td>
                <td class="hidden-xs">{{number_format($info['code']/100,2)}}</td>
                <td class="hidden-xs">
                    @if($info['win']<0)
                        <span style="color: red;">{{number_format($info['win']/100,2)}}</span>
                    @else
                        {{number_format($info['win']/100,2)}}
                    @endif
                </td>
            </tr>
        @endforeach
        @if(!$list[0])
            <tr><td colspan="9" style="text-align: center;color: orangered;">暂无数据</td></tr>
        @endif
        </tbody>
    </table>
    <div class="page-wrap" style="text-align: center;">
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
                ,limits:[100,500,1000]
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
                min:"{{$min}}",
                max:max
            });
            $(".reset").click(function(){
                $("input[name='begin']").val('');
                $("select[name='deskId']").val('');
            });
            form.verify({
                begin:function(value){
                    var begin = Date.parse(new Date(value));
                    //获取当前时间戳
                    var nowTime = (new Date()).getTime();
                    if(begin>nowTime){
                        return "选择的日期不能大于今天的日期";
                    }
                },
                pave_num:function (value) {
                    var v = $("input[name='boot_num']").val();
                    if(value!="" || value!=null){
                        if (v ==""){
                            return '请填写靴号'
                        }
                    }
                }
            });
            form.on('submit(formDemo)', function(data) {
            });
        });
    </script>
@endsection
@extends('common.list')