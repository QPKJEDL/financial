@section('title', '角色编辑')
@section('content')
    <fieldset class="layui-elem-field layui-field-title" style="margin-top: 20px;">
        <legend>{{$info['username']}}结构关系</legend>
    </fieldset>
    {{--赤色分割线
    <hr class="layui-bg-red">--}}
    @foreach($parent as $p)
        {{$p['username']}}({{$p['nickname']}}) 创建日期：{{$p['created_at']}}
        <hr class="layui-bg-orange">
    @endforeach
    {{$info['username']}}({{$info['nickname']}}) 创建日期：{{$info['created_at']}}
    <hr class="layui-bg-orange">
@endsection
@section('id','')
@section('js')
    <script>
        layui.use(['form','jquery','laypage', 'layer','tree'], function() {
            var tree = layui.tree;
            var form = layui.form(),
                $ = layui.jquery;
            form.render();
            var layer = layui.layer;
            form.verify({
            });
            form.on('submit(formDemo)', function(data) {
                return false;
            });
        });
    </script>
@endsection
@extends('common.edita')