@section('title', '会员列表')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#x1002;</i></button>
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="begin" name="begin" placeholder="开始时间" onclick="layui.laydate({elem: this, festival: true,min:'{{$min}}'})" value="{{ $input['begin'] or '' }}" autocomplete="off">
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="end" name="end" placeholder="结束时间" onclick="layui.laydate({elem: this, festival: true,min:'{{$min}}'})" value="{{ $input['begin'] or '' }}" autocomplete="off">
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="account" value="{{ $input['account'] or '' }}" name="account" placeholder="用户账号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <select name="pay_type" lay-search="">
            <option value="">请选择类型</option>
            <option value="1" {{isset($input['pay_type'])&&$input['pay_type']==1?'selected':''}}>充值</option>
            <option value="2" {{isset($input['pay_type'])&&$input['pay_type']==2?'selected':''}}>银行卡充值</option>
            <option value="3" {{isset($input['pay_type'])&&$input['pay_type']==3?'selected':''}}>环球支付宝</option>
            <option value="4" {{isset($input['pay_type'])&&$input['pay_type']==4?'selected':''}}>环球火山支付</option>
            <option value="5" {{isset($input['pay_type'])&&$input['pay_type']==5?'selected':''}}>环球闪电支付宝</option>
            <option value="6" {{isset($input['pay_type'])&&$input['pay_type']==6?'selected':''}}>环球四方支付宝</option>
        </select>
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
        </colgroup>
        <thead>
        <tr>
            <th class="hidden-xs">时间</th>
            <th class="hidden-xs">类型</th>
            <th class="hidden-xs">用户名称[账号]</th>
            <th class="hidden-xs">直属上级[账号]</th>
            <th class="hidden-xs">充值提现金额</th>
        </tr>
        </thead>
        <tbody>
        @foreach($list as $info)
            <tr>
                <td class="hidden-xs">{{$info['creatime']}}</td>
                <td class="hidden-xs">
                    @if($info['pay_type']==1)
                        充值
                    @elseif($info['pay_type']==2)
                        银行卡充值
                    @elseif($info['pay_type']==3)
                        环球支付宝
                    @elseif($info['pay_type']==4)
                        环球火山支付
                    @elseif($info['pay_type']==5)
                        环球闪电支付宝
                    @elseif($info['pay_type']==6)
                        环球四方支付宝
                    @endif
                </td>
                <td class="hidden-xs">{{$info['nickname']}}[{{$info['account']}}]</td>
                <td class="hidden-xs">{{$info['agentName']}}[{{$info['username']}}]</td>
                <td class="hidden-xs">{{$info['score']/100}}</td>
            </tr>
        @endforeach
        @if(!$list[0])
            <tr><td colspan="9" style="text-align: center;color: orangered;">暂无数据</td></tr>
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
                $("input[name='end']").val('');
                $("input[name='account']").val('');
                $("select[name='pay_type']").val('');
            });
            form.render();
            form.on('submit(formDemo)', function(data) {
                console.log(data);
            });
        });
    </script>
@endsection
@extends('common.list')