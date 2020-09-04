@section('title', '台桌输赢情况')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#xe9aa;</i></button>
        <button class="layui-btn layui-btn-normal reset" lay-submit>重置</button>
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
        <select name="user_type">
            <option value="">身份</option>
            <option value="1" {{isset($input['user_type'])&&$input['user_type']==1?'selected':''}}>线下</option>
            <option value="2" {{isset($input['user_type'])&&$input['user_type']==2?'selected':''}}>线上</option>
        </select>
    </div>
    <div class="layui-inline">
        <select name="business_id">
            <option value="">请选择支付商户</option>
            @foreach($business as $info)
                <option value="{{$info['business_id']}}" {{isset($input['business_id'])&&$input['business_id']==$info['business_id']?'selected':''}}>{{$info['service_name']}}</option>
            @endforeach
        </select>
    </div>
    <div class="layui-inline">
        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="formDemo">搜索</button>
        <button class="layui-btn layui-btn-normal" lay-submit name="excel" value="excel">导出EXCEL</button>
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
            <th class="hidden-xs">商户名称</th>
            <th class="hidden-xs">直属代理账户</th>
            <th class="hidden-xs">一级代理账户</th>
            <th class="hidden-xs">充值金额</th>
        </tr>
        </thead>
        <tbody>
            @foreach($list as $info)
                <tr>
                    <td class="hidden-xs">
                        {{$input['business_name']}}
                    </td>
                    <td class="hidden-xs">{{$info['username']}}[{{$info['nickname']}}]【占成:@if($info['userType']==1) {{$info['proportion']}} @else 0 @endif%】</td>
                    <td class="hidden-xs">{{$info['zs']['username']}}[{{$info['zs']['nickname']}}]【占成:@if($info['zs']['userType']==1) {{$info['zs']['proportion']}} @else 0 @endif%】</td>
                    <td class="hidden-xs">{{number_format($info['score']/100,2)}}</td>
                </tr>
            @endforeach
            @if(count($list)==0)
                <tr><td colspan="4" style="text-align: center;color: orangered;">暂无数据</td></tr>
            @endif
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
            $(".reset").click(function () {
                $("input[name='begin']").val('')
                $("input[name='end']").val('')
                $("input[name='account']").val('')
                $('select[name="business_id"]').val('')
            });
        });
    </script>
@endsection
@extends('common.list')