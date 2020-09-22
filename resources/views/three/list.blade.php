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
        <input class="layui-input" lay-verify="orderSn" name="orderSn" placeholder="订单号" value="{{$input['orderSn'] or ''}}" autocomplete="off"/>
    </div>
    <div class="layui-inline">
        <select name="status">
            <option value="">全部</option>
            <option value="1" {{isset($input['status'])&&$input['status']==1?'selected':''}}>成功</option>
            <option value="0" {{isset($input['status'])&&$input['status']==0?'selected':''}}>待支付</option>
        </select>
    </div>
    <div class="layui-inline">
        <select name="business_id">
            <option value="">支付商户</option>
            @foreach($business as $k)
                <option value="{{$k['business_id']}}" {{isset($input['business_id'])&&$input['business_id']==$k['business_id']?'selected':''}}>{{$k['service_name']}}</option>
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
            @foreach($list as $info)
                <tr>
                    <td class="hidden-xs">{{$info['order_sn']}}</td>
                    <td class="hidden-xs">{{$info['service_name']}}</td>
                    <td class="hidden-xs">{{$info['account']}}</td>
                    <td class="hidden-xs">{{$info['creatime']}}</td>
                    <td class="hidden-xs">
                        @if($info['savetime']!=0)
                            {{$info['savetime']}}
                        @else
                            -
                        @endif
                    </td>
                    <td class="hidden-xs">{{number_format($info['score']/100,2)}}</td>
                    <td class="hidden-xs">
                        @if($info['status']==0)
                            0.00
                        @else
                            {{number_format($info['score']/100,2)}}
                        @endif
                    </td>
                    <td class="hidden-xs">
                        @if($info['status']==0)
                            待充值
                        @else
                            <span style="color: green;">充值成功</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            @if(count($list)==0)
                <tr><td colspan="8" style="text-align: center;color: orangered;">暂无数据</td></tr>
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
                $("input[name='orderSn']").val('')
                $('select[name="status"]').val('')
                $('select[name="business_id"]').val('')
            });
        });
    </script>
@endsection
@extends('common.list')