@section('title', '会员列表')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#xe9aa;</i></button>
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="begin" id="begin" name="begin" placeholder="开始时间" value="{{ $input['begin'] or '' }}" autocomplete="off">
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="end" id="end" name="end" placeholder="结束时间" value="{{ $input['begin'] or '' }}" autocomplete="off">
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="account" value="{{ $input['account'] or '' }}" name="account" placeholder="用户账号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <select name="user_type">
            <option value="">请选择用户类型</option>
            <option value="1" {{isset($input['user_type'])&&$input['user_type']==1?'selected':''}}>线下</option>
            <option value="2" {{isset($input['user_type'])&&$input['user_type']==2?'selected':''}}>线上</option>
        </select>
    </div>
    <div class="layui-inline">
        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="formDemo">搜索</button>
        <button class="layui-btn layui-btn-normal reset" lay-submit>重置</button>
        <button class="layui-btn layui-btn-normal" name="excel" value="excel">导出EXCEL</button>
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
            <col class="hidden-xs" width="100">
        </colgroup>
        <thead>
        <tr>
            <th class="hidden-xs">时间</th>
            <th class="hidden-xs">用户名称[账号]</th>
            <th class="hidden-xs">操作前金额</th>
            <th class="hidden-xs">充值提现金额</th>
            <th class="hidden-xs">操作后金额</th>
            <th class="hidden-xs">操作类型</th>
            <th class="hidden-xs">操作人</th>
        </tr>
        </thead>
        <tbody>
        @foreach($list as $info)
            <tr>
                <td class="hidden-xs">{{$info->creatime}}</td>
                <td class="hidden-xs">{{$info->nickname}}[{{$info->account}}]</td>
                <td class="hidden-xs">{{number_format($info->bet_before/100,2)}}</td>
                <td class="hidden-xs">{{number_format($info->score/100,2)}}</td>
                <td class="hidden-xs">{{number_format($info->bet_after/100,2)}}</td>
                <td class="hidden-xs">
                    @if($info->status==1)
                        充值
                    @elseif($info->status==3)
                        提现
                    @endif
                    @if($info->pay_type==1)
                        (到款)
                    @elseif($info->pay_type==2)
                        (签单)
                    @elseif($info->pay_type==3)
                        (移分)
                    @elseif($info->pay_type==4)
                        (按比例)
                    @elseif($info->pay_type==5)
                        (支付宝)
                    @elseif($info->pay_type==6)
                        (微信)
                    @endif
                </td>
                <td class="hidden-xs">{{$info->remark}}</td>
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
            //日期插件初始化
            laydate.render({
                elem: '#begin'
            });
            laydate.render({
                elem:"#end"
            });
            $(".reset").click(function(){
                $("input[name='begin']").val('');
                $("input[name='end']").val('');
                $("input[name='account']").val('');
                $("select[name='user_type']").val('')
            });
            form.on('submit(formDemo)', function(data) {
                console.log(data);
            });
        });
    </script>
@endsection
@extends('common.list')