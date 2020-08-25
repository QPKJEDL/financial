@section('title', '角色编辑')
@section('content')
    <fieldset class="layui-elem-field layui-field-title" style="margin-top: 20px;">
        <legend>{{$info['username']}}结构关系</legend>
    </fieldset>
    <ul class="layui-timeline">
        <li class="layui-timeline-item">
            <i class="layui-icon layui-timeline-axis"></i>
            <div class="layui-timeline-content layui-text">
                <h3 class="layui-timeline-title">admin(总公司)</h3>
            </div>
        </li>
        @foreach($parent as $p)
            <li class="layui-timeline-item">
                <i class="layui-icon layui-timeline-axis"></i>
                <div class="layui-timeline-content layui-text">
                    <h3 class="layui-timeline-title">{{$p['username']}}({{$p['nickname']}})</h3>
                    <p>
                        @if($p['userType']==2)[抽水{{$p['pump']}}%]@endif[占成{{$p['proportion']}}%]<br/>
                        创建日期：{{$p['created_at']}}
                    </p>
                </div>
            </li>
        @endforeach
        <li class="layui-timeline-item">
            <i class="layui-icon layui-timeline-axis"></i>
            <div class="layui-timeline-content layui-text">
                <h3 class="layui-timeline-title">{{$info['username']}}({{$info['nickname']}})</h3>
                <p>
                    @if($info['userType']==2)[抽水{{$info['pump']}}%]@endif[占成{{$info['proportion']}}%]<br/>
                    创建日期：{{$info['created_at']}}
                </p>
            </div>
        </li>
    </ul>
@endsection
@section('id','')
@section('js')
    <script>
        layui.use(['form','jquery','laypage', 'layer','tree'], function() {
            var tree = layui.tree;
            var form = layui.form,
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