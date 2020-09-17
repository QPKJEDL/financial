@section('title', '会员列表')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#xe9aa;</i></button>
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="begin" name="begin" id="begin" placeholder="开始日期" value="{{ $input['begin'] or '' }}" autocomplete="off">
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="end" name="end" id="end" placeholder="结束日期" value="{{ $input['end'] or '' }}" autocomplete="off">
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="account" value="{{ $input['account'] or '' }}" name="account" placeholder="会员账号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="bank_card" value="{{ $input['bank_card'] or '' }}" name="bank_card" placeholder="会员卡号" autocomplete="off" class="layui-input">
    </div>
    {{--<div class="layui-inline">
        <input type="text" lay-verify="account" value="{{ $input['account'] or '' }}" name="account" placeholder="收款账户" autocomplete="off" class="layui-input">
    </div>--}}
    {{--<div class="layui-inline">
        <input type="text" lay-verify="account" value="{{ $input['account'] or '' }}" name="account" placeholder="状态" autocomplete="off" class="layui-input">
    </div>--}}
    <div class="layui-inline">
        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="formDemo">搜索</button>
        <button class="layui-btn layui-btn-normal reset" lay-submit>重置</button>
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
        </colgroup>
        <thead>
        <tr>
            <th class="hidden-xs">会员名称[账号]</th>
            <th class="hidden-xs">上级账号</th>
            <th class="hidden-xs">一级账号</th>
            <th class="hidden-xs">提现时间</th>
            <th class="hidden-xs">提现金额</th>
            <th class="hidden-xs">收款行</th>
            <th class="hidden-xs">开户行</th>
            <th class="hidden-xs">银行卡号</th>
            <th class="hidden-xs">户名</th>
            <th class="hidden-xs">操作人</th>
            <th class="hidden-xs">操作</th>
        </tr>
        </thead>
        <tbody>
        @foreach($list as $info)
            <tr>
                <td class="hidden-xs">{{$info['nickname']}}[{{$info['account']}}]</td>
                <td class="hidden-xs">{{$info['agentName']}}[{{$info['username']}}]</td>
                <td class="hidden-xs">{{$info['agent']['nickname']}}[{{$info['agent']['username']}}]</td>
                <td class="hidden-xs">{{$info['creatime']}}</td>
                <td class="hidden-xs">{{number_format($info['money']/100,2)}}</td>
                <td class="hidden-xs">{{$info['bank_name']}}</td>
                <td class="hidden-xs">{{$info['bank_addr']}}</td>
                <td class="hidden-xs">{{$info['bank_card']}}</td>
                <td class="hidden-xs">{{$info['draw_name']}}</td>
                <td class="hidden-xs">
                    @if($info['lock_by']=="")
                        -
                    @else
                        {{$info['lock_by']}}
                    @endif
                </td>
                <td class="hidden-xs">
                    <div class="layui-inline">
                        @if($info['status']==0)
                            @if($info['lock_by']=="")
                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs lock" data-id="{{$info['id']}}">锁定</button>
                            @else
                                @if($info['lock_by']==\Illuminate\Support\Facades\Auth::user()['username'])
                                    <button type="button" class="layui-btn layui-btn-primary layui-btn-xs approve" data-id="{{$info['id']}}">确认</button>
                                    <button type="button" class="layui-btn layui-btn-primary layui-btn-xs">打印</button>
                                @endif
                            @endif
                                @if($info['lock_by']==\Illuminate\Support\Facades\Auth::user()['username'])
                                    <button type="button" class="layui-btn layui-btn-danger layui-btn-xs void" data-id="{{$info['id']}}">作废</button>
                                @endif
                        @elseif($info['status']==1)
                            已确定
                        @elseif($info['status']==2)
                            <a href="javascript:;" style="color: red;" class="zf" data-rea="{{$info['reason']}}">已作废</a>
                        @endif
                        <button type="button" class="layui-btn layui-btn-xs dayInfo" data-id="{{$info['user_id']}}" data-name="{{$info['nickname']}}" data-desc="详情"><i class="layui-icon">下注详情</i></button>
                    </div>
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
            laydate.render({
                elem:"#begin"
            });
            laydate.render({
                elem:"#end"
            });
            $(".zf").click(function () {
                var id = $(this).attr('data-id');
                var value = $(this).attr('data-rea');
                layer.open({
                    type:1,
                    title:'作废原因',
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
            $(".reset").click(function(){
                $("input[name='begin']").val('');
                $("input[name='end']").val('');
                $("input[name='account']").val('');
                $("input[name='bank_card']").val('');
            });
            //锁定数据
            $(".lock").click(function () {
                var id = $(this).attr('data-id');
                layer.confirm('确定要锁定吗？',function (index) {
                    $.ajax({
                        headers:{
                            'X-CSRF-TOKEN':$("input[name='_token']").val()
                        },
                        url:'{{url('/admin/down/lockDataById')}}',
                        type:'post',
                        data:{
                            'id':id
                        },
                        dataType:'json',
                        success:function (res) {
                            if (res.status==1){
                                layer.msg(res.msg,{icon:6});
                                layer.close(index);
                                window.location.reload();
                            }else{
                                layer.msg(res.msg,{shift: 6,icon:5});
                            }
                        },
                        error : function(XMLHttpRequest, textStatus, errorThrown) {
                            layer.msg('网络失败', {time: 1000});
                        }
                    });
                });
            });
            //确认
            $(".approve").click(function () {
                var id = $(this).attr('data-id');
                layer.prompt({title:'请输入密码，并确认',formType:1},function (pass,index) {
                    $.ajax({
                        headers:{
                            "X-CSRF-TOKEN":$("input[name='_token']").val()
                        },
                        url:"{{url('/admin/checkPasswordIsTrue')}}",
                        type:'post',
                        data:{'password':pass},
                        dataType:"json",
                        success:function (res) {
                            if(res.status==1){
                                layer.close(index);
                                layer.confirm('确定要操作吗？',function (index) {
                                    $.ajax({
                                        headers:{
                                            "X-CSRF-TOKEN":$("input[name='_token']").val()
                                        },
                                        url:"{{url('/admin/down/approveData')}}",
                                        type:"post",
                                        data:{"id":id},
                                        dataType:"json",
                                        success:function (res) {
                                            if(res.status==1){
                                                layer.msg(res.msg,{icon:6});
                                            }else{
                                                layer.msg(res.msg,{shift:6,icon:5});
                                            }
                                        },
                                        error : function (XMLHttpRequest,textStatus,errorThrow) {
                                            layer.msg('网络失败',{time:1000});
                                        }
                                    });
                                    layer.close(index);
                                    window.location.reload();
                                });
                            }else{
                                layer.msg(res.msg,{shift:6,icon:5});
                            }
                        }
                    });
                });
            });
            //作废
            $(".void").click(function () {
                var id = $(this).attr('data-id');
                layer.prompt({title:'请输入密码，并确认',formType:1},function (pass,index) {
                    $.ajax({
                        headers:{
                            "X-CSRF-TOKEN":$("input[name='_token']").val()
                        },
                        url:"{{url('/admin/checkPasswordIsTrue')}}",
                        type:'post',
                        data:{'password':pass},
                        dataType:"json",
                        success:function (res) {
                            if(res.status==1){
                                layer.close(index);
                                layer.confirm('确定要作废吗？',function (index) {
                                    layer.prompt({title:'填写驳回原因，并确认',formType:2},function (text,i) {
                                        layer.close(i)
                                        $.ajax({
                                            headers:{
                                                "X-CSRF-TOKEN":$("input[name='_token']").val()
                                            },
                                            url:"{{url('/admin/down/obsoleteData')}}",
                                            type:"post",
                                            data:{"id":id,'reason':text},
                                            dataType:"json",
                                            success:function (res) {
                                                if(res.status==1){
                                                    layer.msg(res.msg,{icon:6});
                                                    layer.close(index);
                                                    window.location.reload();
                                                }else{
                                                    layer.msg(res.msg,{shift:6,icon:5});
                                                }
                                            },
                                            error : function (XMLHttpRequest,textStatus,errorThrow) {
                                                layer.msg('网络失败',{time:1000});
                                            }
                                        });
                                    });
                                },function () {
                                    layer.msg('取消了');
                                });
                            }else{
                                layer.msg(res.msg,{shift:6,icon:5});
                            }
                        }
                    });
                });
            });
            $(".dayInfo").click(function () {
                var id = $(this).attr('data-id');
                var name = $(this).attr('data-name');
                var time=(new Date).getTime()-24*60*60*1000;
                var yesterday=new Date(time);
                var month=yesterday.getMonth();
                var day=yesterday.getDate();
                var t=yesterday.getFullYear() + "-" + (yesterday.getMonth()> 9 ? (yesterday.getMonth() + 1) : "0" + (yesterday.getMonth() + 1)) + "-" +(yesterday.getDate()> 9 ? (yesterday.getDate()) : "0" + (yesterday.getDate()));
                var index = layer.open({
                    type:2,
                    title:name+'下注详情',
                    shadeClose:true,
                    offset:'10%',
                    area:['60%','80%'],
                    content:'/admin/userOrderList/' + id +'/'+t+'/'+t
                });
                layer.full(index);
            });
            form.render();
            form.on('submit(formDemo)', function(data) {
                console.log(data);
            });
        });
    </script>
@endsection
@extends('common.list')