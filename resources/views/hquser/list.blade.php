@section('title', '会员列表')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#xe9aa;</i></button>
        <button class="layui-btn reset" lay-submit>重置</button>
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="account" value="{{ $input['account'] or '' }}" name="account" placeholder="请输入会员账号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="nickname" value="{{ $input['nickname'] or '' }}" name="nickname" placeholder="请输入会员昵称" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <select name="user_type">
            <option value="">请选择类型</option>
            <option value="0" {{isset($input['user_type'])&&$input['user_type']==0?'selected':''}}>全部</option>
            <option value="2" {{isset($input['user_type'])&&$input['user_type']==2?'selected':''}}>线上</option>
            <option value="1" {{isset($input['user_type'])&&$input['user_type']==1?'selected':''}}>线下</option>
        </select>
    </div>
    <div class="layui-inline">
        <button class="layui-btn" lay-submit lay-filter="formDemo">搜索</button>
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
            <col class="hidden-xs" width="300">
        </colgroup>
        <thead>
        <tr>
            <th class="hidden-xs">会员账号</th>
            <th class="hidden-xs">身份</th>
            <th class="hidden-xs">姓名</th>
            <th class="hidden-xs">所属代理</th>
            <th class="hidden-xs">最近充值</th>
            <th class="hidden-xs">当前余额</th>
            <th class="hidden-xs">百/龙/牛/三/A</th>
            <th class="hidden-xs">创建日期</th>
            <th class="hidden-xs">最近登录IP</th>
            <th class="hidden-xs">状态</th>
            <th class="hidden-xs">在线</th>
            <th class="hidden-xs">操作</th>
        </tr>
        </thead>
        <tbody>
        @foreach($list as $info)
            <tr>
                <td class="hidden-xs"><a href="javascript:;" class="children" data-id="{{$info['user_id']}}">{{$info['account']}}</a></td>
                <td class="hidden-xs">
                    @if($info['user_type']==1)
                        线下
                    @else
                        线上
                    @endif
                </td>
                <td class="hidden-xs">{{$info['nickname']}}</td>
                <td class="hidden-xs">
                    @if($info['agent_id']==0)
                        试玩会员
                    @else
                        @if($info['username']=="" || $info['username']==null)
                            已被删除
                        @else
                            {{$info['agentName']}}[{{$info['username']}}]
                        @endif
                    @endif
                </td>
                <td class="hidden-xs">{{number_format($info['cz']/100,2)}}</td>
                <td class="hidden-xs">{{number_format($info['userAccount']['balance']/100,2)}}</td>
                <td class="hidden-xs">
                    @if($info['user_type']==2)
                        -
                    @else
                        {{$info['fee']['baccarat']}}/{{$info['fee']['dragonTiger']}}/{{$info['fee']['niuniu']}}/{{$info['fee']['sangong']}}/{{$info['fee']['A89']}}
                    @endif
                </td>
                <td class="hidden-xs">{{$info['creatime']}}</td>
                <td class="hidden-xs">{{$info['last_ip']}}</td>
                <td class="hidden-xs">
                    @if($info['is_over']==0)
                        <input type="checkbox" data-id="{{$info['user_id']}}" name="close" checked lay-skin="switch" lay-filter="switchTest" lay-text="开启|封禁">
                    @else
                        <input type="checkbox" data-id="{{$info['user_id']}}" name="close" lay-skin="switch" lay-filter="switchTest" lay-text="开启|封禁">
                    @endif
                </td>
                <td class="hidden-xs">
                    @if($info['is_online']==1)
                        <span style="color: green;">在线</span>
                    @else
                        离线
                    @endif
                </td>
                <td class="hidden-xs">
                    <button class="layui-btn layui-btn-xs code" data-id="{{$info['user_id']}}" data-name="{{$info['nickname']}}" data-acc="{{$info['account']}}"><i class="layui-icon">充值提现</i></button>
                    <button class="layui-btn layui-btn-xs edit" data-id="{{$info['user_id']}}"><i class="layui-icon">账号编辑</i></button>
                    <button class="layui-btn layui-btn-xs password" data-id="{{$info['user_id']}}"><i class="layui-icon">&#xe673;</i>修改密码</button>
                    <button class="layui-btn layui-btn-xs del-btn @if($info['userAccount']['balance']>0) layui-btn-disabled @else layui-btn-danger @endif" data-id="{{$info['id']}}" @if($info['userAccount']['balance']>0) disabled="disabled" @endif data-url="{{url('/admin/hquser/'.$info['user_id'])}}"><i class="layui-icon">&#xe640;</i>结算删除</button>
                </td>
            </tr>
        @endforeach
        @if(count($list)==0)
            <tr><td colspan="12" style="text-align: center;color: orangered;">暂无数据</td></tr>
        @endif
        </tbody>
    </table>
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
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

            var count = "{{$list->total()}}";
            var curr = "{{$list->currentPage()}}";
            var limit = "{{$limit}}";
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
            $(".reset").click(function(){
                $("input[name='account']").val('');
                $("input[name='nickname']").val('');
                $("select[name='user_type']").val('');
            });
            //关系结构
            $('.children').click(function () {
                console.log('1')
                var id = $(this).attr('data-id');
                layer.open({
                    type:2,
                    title:"关系结构",
                    shadeClose:true,
                    offset:'10%',
                    area:['60%','80%'],
                    content:'/admin/userRelation/'+id
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
                var index = layer.open({
                    type: 2,
                    title: name + '充值提现',
                    shadeClose: true,
                    offset:'10%',
                    area:['60%','80%'],
                    content:'/admin/hquser/topCode/' + id
                });
                layer.full(index)
            });
            $(".underCode").click(function () {
                var id = $(this).attr('data-id');
                layer.open({
                    type:2,
                    title: name + "会员下分",
                    shadeClose: true,
                    offset: '10%',
                    area:['60%','80%'],
                    content:'/admin/hquser/underCode/' +id
                });
            });
            form.on('switch(switchTest)',function (data) {
                var ch = data.elem.checked;
                var id = $(data.elem).attr('data-id')
                var status;
                if(ch){
                    status = 0;
                }else{
                    status = 1;
                }$.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    }
                });
                $.ajax({
                    url:"{{url('/admin/hquser/changeStatus')}}",
                    data:{
                        'id':id,
                        'status':status
                    },
                    type:'post',
                    dataType:'json',
                    success:function(res){
                        if(res.status == 1){
                            layer.msg(res.msg,{icon:6});
                        }else{
                            layer.msg(res.msg,{shift: 6,icon:5});
                        }
                    },
                    error : function(XMLHttpRequest, textStatus, errorThrown) {
                        layer.msg('网络失败', {time: 1000});
                    }
                });
            });
            form.render();
            form.on('submit(formDemo)', function(data) {
            });
        });
    </script>
@endsection
@extends('common.list')