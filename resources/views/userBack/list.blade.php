@section('title', '公告管理')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-normal addBtn" data-desc="添加黑名单" data-url="{{url('/admin/userBack/0/edit')}}"><i class="layui-icon">&#xe654;</i></button>
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#x1002;</i></button>
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
                        <button class="layui-btn layui-btn-small layui-btn-danger del-btn" data-id="{{$info['id']}}" data-url="{{url('/admin/userBack/'.$info['id'])}}">删除</button>
                    </div>
                </td>
            </tr>
        @endforeach
        @if(!$list[0])
            <tr><td colspan="7" style="text-align: center;color: orangered;">暂无数据</td></tr>
        @endif
        </tbody>
    </table>
    <div class="page-wrap">
        {{$list->render()}}
    </div>
@endsection
@section('js')
    <script>
        layui.use(['form', 'jquery', 'layer','laydate'], function() {
            var form = layui.form(),
                $ = layui.jquery,
                laydate=layui.laydate,
                layer = layui.layer;
            laydate({istoday: true});
            form.render();
            form.on('submit(formDemo)', function(data) {                
            });
            
        });
    </script>
@endsection
@extends('common.list')