@section('title', '会员列表')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-normal addBtn" id="addAgent" data-desc="添加代理" data-url="{{url('/admin/onAgent/0/edit')}}"><i class="layui-icon">&#xe654;</i></button>
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#xe9aa;</i></button>
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="username" value="{{ $input['username'] or '' }}" name="username" placeholder="请输入代理账号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="nickname" value="{{ $input['nickname'] or '' }}" name="nickname" placeholder="请输入代理昵称" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="formDemo">搜索</button>
        <button class="layui-btn layui-btn-normal reset" lay-submit>重置</button>
    </div>
@endsection
@section('table')
    <table class="layui-table" lay-size="sm">
        <colgroup>
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="200">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="550">
        </colgroup>
        <thead>
        <tr>
            <th class="hidden-xs">代理账号</th>
            <th class="hidden-xs">姓名</th>
            <th class="hidden-xs">账户余额</th>
            <th class="hidden-xs">群组余额</th>
            <th class="hidden-xs">抽水比例</th>
            <th class="hidden-xs">占成</th>
            <th class="hidden-xs">创建时间</th>
            <th class="hidden-xs">是否激活</th>
            <th class="hidden-xs">状态</th>
            <th class="hidden-xs">操作</th>
        </tr>
        </thead>
        <tbody>
        @foreach($list as $info)
            <tr>
                <td class="hidden-xs"><a class="a" data-id="{{$info['id']}}">{{$info['username']}}</a></td>
                <td class="hidden-xs">{{$info['nickname']}}</td>
                <td class="hidden-xs">{{number_format($info['balance']/100,2)}}</td>
                <td class="hidden-xs">{{number_format($info['groupBalance']/100,2)}}</td>
                <td class="hidden-xs">{{$info['pump']}}%</td>
                <td class="hidden-xs">{{$info['proportion']}}%</td>
                <td class="hidden-xs">{{$info['created_at']}}</td>
                <td class="hidden-xs">
                    @if($info['is_act']==0)
                        <span class="layui-btn layui-btn-xs layui-btn-danger act" data-id="{{$info['id']}}">未激活</span>
                    @elseif($info['is_act']==1)
                        <span class="layui-btn layui-btn-xs layui-btn-warm">激活</span>
                    @endif
                </td>
                <td class="hidden-xs">
                    @if($info['status']==0)
                        <input type="checkbox" checked="" name="open" value="{{$info['id']}}" lay-skin="switch" lay-filter="switchTest" lay-text="正常|停用">
                    @else
                        <input type="checkbox" name="close" lay-skin="switch" value="{{$info['id']}}" lay-filter="switchTest" lay-text="正常|停用">
                    @endif
                </td>
                <td class="hidden-xs">
                    <button class="layui-btn layui-btn-xs layui-btn-normal edit-btn" data-id="{{$info['id']}}" data-desc="修改代理" data-url="{{url('/admin/onAgent/'. $info['id'] .'/edit')}}">编辑</button>
                    @if($info['status']==0)
                        <button class="layui-btn layui-btn-xs layui-btn-warm stop" data-id="{{$info['id']}}" data-desc="代理停用">停用</button>
                    @elseif($info['status']==1)
                        <button class="layui-btn layui-btn-xs layui-btn start" data-id="{{$info['id']}}" data-desc="代理启用">启用</button>
                    @endif
                    <button class="layui-btn layui-btn-xs cz" data-id="{{$info['id']}}" data-name="{{$info['nickname']}}"data-desc="下级会员"><i class="layui-icon">上分</i></button>
                    <button class="layui-btn layui-btn-xs @if($info['userCount']==0) layui-btn-disabled @else layui-btn-normal @endif user" data-id="{{$info['id']}}" data-name="{{$info['nickname']}}"data-desc="下级会员"><i class="layui-icon">下级会员</i></button>
                    <button class="layui-btn layui-btn-xs @if($info['agentCount']==0) layui-btn-disabled @else layui-btn-normal @endif agent" data-id="{{$info['id']}}"data-name="{{$info['nickname']}}" data-desc="下级代理"><i class="layui-icon">下级代理</i></button>
                    <button class="layui-btn layui-btn-xs resetPwd" data-id="{{$info['id']}}" data-name="{{$info['nickname']}}"data-desc="下级会员"><i class="layui-icon">修改密码</i></button>
                    <button class="layui-btn layui-btn-xs layui-btn-danger del-btn" data-id="{{$info['id']}}" data-url="{{url('/admin/agent/'.$info['id'])}}"><i class="layui-icon">删除</i></button>
                </td>
            </tr>
        @endforeach
        @if(!$list[0])
            <tr><td colspan="9" style="text-align: center;color: orangered;">暂无数据</td></tr>
        @endif
        </tbody>
    </table>
    <input type="hidden" id="token" value="{{csrf_token()}}">
    {{--<div class="page-wrap">
        {{$list->render()}}
    </div>--}}
@endsection
@section('js')
    <script>
        layui.use(['form', 'jquery','laydate', 'layer'], function() {
            var form = layui.form,
                $ = layui.jquery,
                laydate = layui.laydate,
                layer = layui.layer
            ;
            $(".reset").click(function(){
                $('input[name="username"]').val('')
                $('input[name="nickname"]').val('')
            });
            $('.act').click(function () {
                var id = $(this).attr('data-id');
                layer.open({
                    type:2,
                    title:"激活二维码",
                    shadeClose:true,
                    offset:'10%',
                    area:['30%','50%'],
                    content:'/admin/onAgentList/qrCode/' + id
                });
            });
            //停用
            $('.stop').click(function () {
                var that = $(this);
                var id=that.attr('data-id');
                layer.confirm('确定要停用吗？',{title:'提示'},function (index) {
                        $.ajax({
                            headers: {
                                'X-CSRF-TOKEN': $('#token').val()
                            },
                            url:"{{url('/admin/agentStop')}}",
                            data:{
                                "id":id,
                            },
                            type:"post",
                            dataType:"json",
                            success:function (res) {
                                if(res.status==1){
                                    layer.msg(res.msg,{icon:6,time:1000},function () {
                                        location.reload();
                                    });

                                }else{
                                    layer.msg(res.msg,{icon:5,time:1000},function(){
                                        location.reload();
                                    });

                                }
                            }
                        });
                    }
                );
            });
            $('.start').click(function () {
                var that = $(this);
                var id=that.attr('data-id');
                layer.confirm('确定要启用吗？',{title:'提示'},function (index) {
                        $.ajax({
                            headers: {
                                'X-CSRF-TOKEN': $('#token').val()
                            },
                            url:"{{url('/admin/agentStart')}}",
                            data:{
                                "id":id,
                            },
                            type:"post",
                            dataType:"json",
                            success:function (res) {
                                if(res.status==1){
                                    layer.msg(res.msg,{icon:6,time:1000},function () {
                                        location.reload();
                                    });

                                }else{
                                    layer.msg(res.msg,{icon:5,time:1000},function(){
                                        location.reload();
                                    });

                                }
                            }
                        });
                    }
                );
            });
            //修改密码
            $('.resetPwd').click(function () {
                var id = $(this).attr('data-id');
                var name = $(this).attr('data-name');
                layer.open({
                    type:2,
                    title: name + "修改密码",
                    shadeClose:true,
                    offset:'10%',
                    area:['60%','80%'],
                    content:'/admin/agent/resetPwd/' + id
                });
            });
            $(".cz").click(function () {
                var id = $(this).attr('data-id');
                var name = $(this).attr('data-name');
                layer.open({
                    type:2,
                    title: name + "上分",
                    shadeClose:true,
                    offset:'10%',
                    area:['60%','80%'],
                    content:'/admin/czOnEdit/' + id
                });
            });
            //下级会员
            $(".user").click(function () {
                var id = $(this).attr('data-id');
                var name = $(this).attr('data-name');
                var index = layer.open({
                    type: 2,
                    title: name + '的下级会员',
                    shadeClose: true,
                    offset:'10%',
                    area:['60%','80%'],
                    content:'/admin/onAgent/subUser/' + id
                });
                layer.full(index)
            });
            //下级代理
            $(".agent").click(function () {
                var id = $(this).attr('data-id');
                var name = $(this).attr('data-name');
                var index = layer.open({
                    type:2,
                    title:name+'的下级代理',
                    shadeClose:true,
                    offset:'10%',
                    area:['60%','80%'],
                    content:'/admin/onAgent/subordinate/'+id
                });
                layer.full(index)
            });
            form.render();
            form.on('submit(formDemo)', function(data) {
            });
        });
    </script>
@endsection
@extends('common.list')