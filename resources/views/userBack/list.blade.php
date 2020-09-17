@section('title', '公告管理')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-normal addBtn" data-desc="添加黑名单" data-url="{{url('/admin/userBack/0/edit')}}"><i class="layui-icon">&#xe654;</i></button>
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#xe9aa;</i></button>
        <button class="layui-btn layui-btn-normal reset" lay-submit>重置</button>
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="account" value="{{ $input['account'] or '' }}" name="account" placeholder="用户账号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
            <select name="user_type">
                <option value="">请选择用户类型</option>
                <option value="1" {{isset($input['user_type'])&&$input['user_type']==1?'selected':''}}>代理</option>
                <option value="2" {{isset($input['user_type'])&&$input['user_type']==2?'selected':''}}>会员</option>
            </select>
    </div>
    <div class="layui-inline">
        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="formDemo">搜索</button>
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
            <th class="hidden-xs">用户类型</th>
            <th class="hidden-xs">用户名称[账号]</th>
            <th class="hidden-xs">拉黑权限</th>
            <th class="hidden-xs">操作者名称[账号]</th>
            <th class="hidden-xs">创建时间</th>
            <th class="hidden-xs">备注</th>
            <th class="hidden-xs">操作</th>
        </tr>
        </thead>
        <tbody>
        @foreach($list as $info)
            <tr>
                <td class="hidden-xs">
                    @if($info['user_type']==1)
                        代理
                    @else
                        会员
                    @endif
                </td>
                <td class="hidden-xs">
                    @if($info['user_type']==1)
                        {{$info['agent']['nickname']}}[{{$info['agent']['username']}}]
                    @else
                        {{$info['user']['nickname']}}[{{$info['user']['account']}}]
                    @endif
                </td>
                <td class="hidden-xs">
                    @if($info['status']==1)
                        打赏
                    @endif
                </td>
                <td class="hidden-xs">{{$info['nickname']}}[{{$info['username']}}]</td>
                <td class="hidden-xs">{{$info['create_time']}}</td>
                <td class="hidden-xs">{{$info['remark']}}</td>
                <td class="hidden-xs">
                    <div class="layui-inline">
                        <button class="layui-btn layui-btn-xs layui-btn-danger del-btn" data-id="{{$info['id']}}" data-url="{{url('/admin/userBack/'.$info['id'])}}">删除</button>
                    </div>
                </td>
            </tr>
        @endforeach
        @if(!$list[0])
            <tr><td colspan="7" style="text-align: center;color: orangered;">暂无数据</td></tr>
        @endif
        </tbody>
    </table>
    <div class="page-wrap" style="text-align: center;">
        <div id="demo"></div>
    </div>
@endsection
@section('js')
    <script>
        layui.use(['form', 'jquery', 'layer','laydate','laypage'], function() {
            var form = layui.form,
                $ = layui.jquery,
                laydate=layui.laydate,
                layer = layui.layer,
                laypage = layui.laypage
            ;
            //分页初始化
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
            $(".reset").click(function () {
                $("input[name='account']").val('');
                $("select[name='user_type']").val('');
            });
            form.on('submit(formDemo)', function(data) {
            });
            
        });
    </script>
@endsection
@extends('common.list')