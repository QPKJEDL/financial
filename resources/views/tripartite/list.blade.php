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
        <select name="">
            <option value="">请选择支付商户</option>
            <option value="1">全部</option>
        </select>
    </div>
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#xe9aa;</i></button>
    </div>
@endsection
@section('table')
    <table class="layui-table" lay-size="sm">
        <colgroup>
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
        </colgroup>
        <thead>
        <tr>
            <th class="hidden-xs">商户成功</th>
            <th class="hidden-xs">直属代理账户</th>
            <th class="hidden-xs">一级代理账户</th>
            <th class="hidden-xs">充值金额</th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
@endsection
@section('js')
@endsection
@extends('common.list')