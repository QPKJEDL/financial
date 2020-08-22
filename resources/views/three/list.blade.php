@section('title', '台桌输赢情况')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#xe9aa;</i></button>
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="begin" id="begin" name="begin" placeholder="开始时间" value="{{ $input['begin'] or '' }}" autocomplete="off">
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="end" id="end" name="end" placeholder="结算时间" value="{{ $input['end'] or '' }}" autocomplete="off">
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="account" id="account" name="account" placeholder="查询账户" value="{{$input['account'] or ''}}" autocomplete="off"/>
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="orderSn" name="orderSn" placeholder="订单号" value="{{$input['orderSn'] or ''}}" autocomplete="off"/>
    </div>
    <div class="layui-inline">

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
            <th class="hidden-xs">订单号</th>
            <th class="hidden-xs">商户名称</th>
            <th class="hidden-xs">充值会员账号</th>
            <th class="hidden-xs">创建时间</th>
            <th class="hidden-xs">成功充值时间</th>
            <th class="hidden-xs">充值金额</th>
            <th class="hidden-xs">实际到账金额</th>
            <th class="hidden-xs">状态</th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
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
            //初始化laydate插件
            laydate.render({
                elem:"#begin"
            });
            laydate.render({
                elem:"#end"
            });
        });
    </script>
@endsection
@extends('common.list')