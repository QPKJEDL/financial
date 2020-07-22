@section('title', '会员列表')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#x1002;</i></button>
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="begin" name="begin" placeholder="时间" onclick="layui.laydate({elem: this, festival: true,min:'{{$min}}'})" value="{{ $input['begin'] or '' }}" autocomplete="off">
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="account" value="{{ $input['account'] or '' }}" name="account" placeholder="代理账号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="formDemo">搜索</button>
        <button class="layui-btn layui-btn-normal reset" lay-submit>重置</button>
        <button class="layui-btn layui-btn-normal" name="excel" value="excel">导出EXCEL</button>
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
            <th class="hidden-xs">id</th>
            <th class="hidden-xs">代理名称[账号]</th>
            <th class="hidden-xs">会员名称[账号]</th>
            <th class="hidden-xs">操作前金额</th>
            <th class="hidden-xs">操作金额</th>
            <th class="hidden-xs">操作后金额</th>
            <th class="hidden-xs">操作类型</th>
            <th class="hidden-xs">充值类型</th>
            <th class="hidden-xs">备注</th>
            <th class="hidden-xs">创建时间</th>
        </tr>
        </thead>
        <tbody>
        @foreach($list as $info)
            <tr>
                <td class="hidden-xs">{{$info['id']}}</td>
                <td class="hidden-xs">{{$info['agentName']}}[{{$info['username']}}]</td>
                <td class="hidden-xs">
                    @if($info['user_id']!="")
                        {{$info['account']}}[{{$info['uName']}}]
                    @else
                        -
                    @endif
                </td>
                <td class="hidden-xs">{{number_format($info['bet_before']/100,2)}}</td>
                <td class="hidden-xs">{{number_format($info['money']/100,2)}}</td>
                <td class="hidden-xs">{{number_format($info['bet_after']/100,2)}}</td>
                <td class="hidden-xs">
                    @if($info['status']==1)
                        充值
                    @elseif($info['status']==2)
                        提现
                    @endif
                </td>
                <td class="hidden-xs">
                    @if($info['type']==1)
                        到款
                    @elseif($info['type']==2)
                        签单
                    @elseif($info['type']==3)
                        移分
                    @elseif($info['type']==4)
                        按比例
                    @elseif($info['type']==5)
                        支付宝
                    @elseif($info['type']==6)
                        微信
                    @else
                        -
                    @endif
                </td>
                <td class="hidden-xs">{{$info['remark']}}</td>
                <td class="hidden-xs">{{$info['creatime']}}</td>
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
            });
            form.render();
            form.on('submit(formDemo)', function(data) {
                console.log(data);
            });
        });
    </script>
@endsection
@extends('common.list')