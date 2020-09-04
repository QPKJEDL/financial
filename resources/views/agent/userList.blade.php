@section('title', '角色列表')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#xe9aa;</i></button>
        <button class="layui-btn layui-btn-normal reset" lay-submit>重置</button>
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="account" value="{{ $input['account'] or '' }}" name="account" placeholder="请输入会员账号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="nickname" value="{{ $input['nickname'] or '' }}" name="nickname" placeholder="请输入昵称" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="formDemo">搜索</button>
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
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="300">
        </colgroup>
        <thead>
            <input type="hidden" id="token" value="{{csrf_token()}}">
        <tr>
            <th class="hidden-xs">会员账号</th>
            <th class="hidden-xs">姓名</th>
            <th class="hidden-xs">所属代理</th>
            <th class="hidden-xs">最近充值</th>
            <th class="hidden-xs">当前余额</th>
            <th class="hidden-xs">百/龙/牛/三/A洗码率</th>
            <th class="hidden-xs">创建时间</th>
            <th class="hidden-xs">最近登录IP</th>
            <th class="hidden-xs">在线</th>
            <th class="hidden-xs">状态</th>
            <th class="hidden-xs">操作</th>
        </tr>
        </thead>
        <tbody>
            @foreach($list as $info)
            <tr>
                <td class="hidden-xs"><a class="a" data-id="{{$info['user_id']}}">{{$info['account']}}</a></td>
                <td class="hidden-xs">{{$info['nickname']}}</td>
                <td class="hidden-xs">{{$info['agentName']}}</td>
                <td class="hidden-xs">{{number_format($info['cz']['score']/100,2)}}</td>
                <td class="hidden-xs">{{number_format($info['balance']/100,2)}}</td>
                <td class="hidden-xs">{{$info['fee']['baccarat']}}/{{$info['fee']['dragonTiger']}}/{{$info['fee']['niuniu']}}/{{$info['fee']['sangong']}}/{{$info['fee']['A89']}}</td>
                <td class="hidden-xs">{{$info['creatime']}}</td>
                <td class="hidden-xs">{{$info['last_ip']}}</td>
                <td class="hidden-xs">
                    @if($info['is_online']==1)
                        <span style="color: green;">在线</span>
                    @else
                        离线
                    @endif
                </td>
                <td class="hidden-xs">
                    @if($info['is_over']==0)
                        <input type="checkbox" checked="" name="open" value="{{$info['user_id']}}" lay-skin="switch" lay-filter="switchTest" lay-text="正常|停用">
                    @else
                        <input type="checkbox" name="close" lay-skin="switch" value="{{$info['user_id']}}" lay-filter="switchTest" lay-text="正常|停用">
                    @endif
                </td>
                <td class="hidden-xs">
                    <button class="layui-btn layui-btn-xs code" data-id="{{$info['user_id']}}" data-name="{{$info['nickname']}}" data-acc="{{$info['account']}}"><i class="layui-icon">充值提现</i></button>
                    <button class="layui-btn layui-btn-xs edit" data-id="{{$info['user_id']}}"><i class="layui-icon">账号编辑</i></button>
                    <button class="layui-btn layui-btn-xs password" data-id="{{$info['user_id']}}"><i class="layui-icon">&#xe673;</i>修改密码</button>
                    <button class="layui-btn layui-btn-xs del-btn @if($info['balance']>0) layui-btn-disabled @else layui-btn-danger @endif" data-id="{{$info['id']}}" @if($info['balance']>0) disabled="disabled" @endif data-url="{{url('/admin/hquser/'.$info['user_id'])}}"><i class="layui-icon">&#xe640;</i>结算删除</button>
                </td>
            </tr>
        @endforeach
        @if(!$list[0])
            <tr><td colspan="6" style="text-align: center;color: orangered;">暂无数据</td></tr>
        @endif
        </tbody>
    </table>
    <div class="page-wrap">
        <div id="demo"></div>
    </div>
@endsection
@section('js')
    <script>
        layui.use(['form', 'jquery','laydate', 'layer','element','laypage'], function() {
            var form = layui.form,
                $ = layui.jquery,
                laydate = layui.laydate,
                layer = layui.layer,
                element = layui.element,
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
                ,limits:[50,100,150]
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
            form.render();
            $(".reset").click(function(){
                $("input[name='account']").val('');
                $("input[name='nickname']").val('');
            });
            $(".a").click(function () {
                var id = $(this).attr('data-id');
                layer.open({
                    type:2,
                    title:'关系结构',
                    shadeClose:true,
                    offset:'10%',
                    area:['30%','50%'],
                    content:'/admin/agentList/getUserRelation/'+id
                });
            });
            //编辑
            $(".edit").click(function () {
                var id = $(this).attr('data-id');
                layer.open({
                    type:2,
                    title:"编辑",
                    shadeClose:true,
                    offset:"10%",
                    area:['60%','80%'],
                    content:'/admin/hquser/edit/'+id
                });
            });
            //修改密码
            $(".password").click(function () {
                var id = $(this).attr('data-id');
                layer.open({
                    type:2,
                    title:'修改密码',
                    shadeClose:true,
                    offset:'10%',
                    area:['60%','80%'],
                    content:'/admin/hquser/resetPwd/'+id
                });
            });
            //充值提现
            $(".code").click(function () {
                var id = $(this).attr('data-id');
                var nickname = $(this).attr('data-name');
                var account = $(this).attr('data-acc');
                var index = layer.open({
                    type: 2,
                    title: nickname + '('+account+')' + '充值提现',
                    shadeClose: true,
                    offset:'10%',
                    area:['60%','80%'],
                    content:'/admin/hquser/topCode/' + id
                });
                layer.full(index)
            });
            $(".update").click(function(){
                var id = $(this).attr('data-id');
                var name = $(this).attr('data-name');
                layer.open({
                    type:2,
                    title:name+'修改密码',
                    shadeClose:true,
                    offset:'10%',
                    area:['60%','80%'],
                    content:'/admin/agentList/resetPwd/' + id
                });
            });
            $(".user").click(function(){
                var id = $(this).attr('data-id');
                var name = $(this).attr('data-name');
                layer.open({
                    type:2,
                    title:name + '在线充值提现',
                    shadeClose:true,
                    offset:'10%',
                    area:['60%','80%'],
                    content:'/admin/hqUser/czCord/' + id
                });
            });
            form.on('switch(switchTest)',function(data){
                var name = $(data.elem).attr('name');
                if(name=='open'){
                    var status = 1;    
                }else if(name='close'){
                    var status = 0;
                }
                $.ajax({
                    headers:{
                        'X-CSRF-TOKEN': $("#token").val()
                    },
                    url:"{{url('/admin/hquser/changeStatus')}}",
                    type:"post",
                    data:{
                        "id":$(data.elem).val(),
                        "status":status
                    },
                    dataType:"json",
                    success:function(res){
                        if(res.status==1){
                            layer.msg(res.msg,{icon:6});
                        }else{
                            layer.msg(res.msg,{shift:6,icon:5});
                        }
                    }
                });
            });
            form.on('submit(formDemo)', function(data) {
            });
        });
    </script>
@endsection
@extends('common.list')
