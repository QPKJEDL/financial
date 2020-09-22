@section('title', '会员列表')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-normal insert" type="button" id="addAgent" data-desc="添加代理" data-url="{{url('/admin/agent/0/edit')}}"><i class="layui-icon">&#xe654;</i></button>
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#xe9aa;</i></button>
        <button class="layui-btn layui-btn-normal reset" lay-submit>重置</button>
    </div>
    @if(array_key_exists('parent_id',$input))
        <input name="parent_id" type="hidden" value="{{$input['parent_id']}}">
    @endif
    <div class="layui-inline">
        <input type="text" lay-verify="username" value="{{ $input['username'] or '' }}" name="username" placeholder="请输入代理账号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="nickname" value="{{ $input['nickname'] or '' }}" name="nickname" placeholder="请输入代理昵称" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <select name="userType">
            <option value="">请选择代理类型</option>
            <option value="1" {{isset($input['userType'])&&$input['userType']==1?'selected':''}}>线下</option>
            <option value="2" {{isset($input['userType'])&&$input['userType']==2?'selected':''}}>线上</option>
        </select>
    </div>
    <div class="layui-inline">
        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="formDemo">搜索</button>
        <button class="layui-btn layui-btn-normal" name="excel" value="excel">导出Excel</button>
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
            <col class="hidden-xs" width="400">
        </colgroup>
        <thead>
        <tr>
            <th class="hidden-xs">代理账号</th>
            <th class="hidden-xs">姓名</th>
            <th class="hidden-xs">账户余额</th>
            <th class="hidden-xs">群组余额</th>
            <th class="hidden-xs">百/龙/牛/三/A洗码率</th>
            <th class="hidden-xs">占成</th>
            <th class="hidden-xs">抽水百分比</th>
            <th class="hidden-xs">创建日期</th>
            <th class="hidden-xs">状态</th>
            <th class="hidden-xs">操作</th>
        </tr>
        </thead>
        <tbody>
            @if(array_key_exists('parent_id',$input)==false)
                <tr>
                    <td class="hidden-xs">admin</td>
                    <td class="hidden-xs">总公司</td>
                    <td class="hidden-xs">{{number_format($balance/100,2)}}</td>
                    <td class="hidden-xs">{{number_format($sumBalance/100,2)}}</td>
                    <td class="hidden-xs">0.9/0.9/0.9/0.9/0.9</td>
                    <td class="hidden-xs">100%</td>
                    <td class="hidden-xs">100%</td>
                    <td class="hidden-xs">1970-01-01 08:00:00</td>
                    <td class="hidden-xs"><input type="checkbox" name="close" lay-skin="switch" disabled lay-text="ON|OFF"></td>
                    <td class="hidden-xs">
                        <button class="layui-btn layui-btn-xs layui-btn-disabled" disabled="disabled"><i class="layui-icon">下级会员</i></button>
                        <button class="layui-btn layui-btn-xs layui-btn-disabled" disabled="disabled"><i class="layui-icon">下级代理</i></button>
                    </td>
                </tr>
            @endif
        @foreach($list as $info)
            <tr>
                <td class="hidden-xs"><a href="javascript:;" class="children" data-id="{{$info['id']}}" data-name="{{$info['nickname']}}">{{$info['username']}}</a></td>
                <td class="hidden-xs">{{$info['nickname']}}</td>
                <td class="hidden-xs">{{number_format($info['balance']/100,2)}}</td>
                <td class="hidden-xs">{{number_format($info['groupBalance']/100,2)}}</td>
                <td class="hidden-xs">
                    @if($info['userType']==1)
                        {{$info['fee']['baccarat']}}/{{$info['fee']['dragonTiger']}}/{{$info['fee']['niuniu']}}/{{$info['fee']['sangong']}}/{{$info['fee']['A89']}}
                    @else
                        -
                    @endif
                </td>
                <td class="hidden-xs">
                    @if($info['userType']==1)
                        {{$info['proportion']}}%
                    @else
                        -
                    @endif
                </td>
                <td class="hidden-xs">
                    @if($info['userType']==1)
                        -
                    @else
                        {{$info['pump']}}%
                    @endif
                </td>
                <td class="hidden-xs">{{$info['created_at']}}</td>
                <td class="hidden-xs">
                    @if($info['status']==0)
                        <input type="checkbox" name="close" data-id="{{$info['id']}}" data-status="{{$info['status']}}" checked lay-skin="switch" lay-filter="switchTest" lay-text="启用|停用">
                    @elseif($info['status']==1)
                        <input type="checkbox" name="close" data-id="{{$info['id']}}" data-status="{{$info['status']}}" lay-skin="switch" lay-filter="switchTest" lay-text="启用|停用">
                    @endif
                </td>
                <td class="hidden-xs">
                    <button class="layui-btn layui-btn-xs @if($info['userCount']==0) layui-btn-disabled @else layui-btn-normal @endif user"  @if($info['userCount']==0) disabled="disabled" @else style="background-color: #9e856a" @endif data-id="{{$info['id']}}" data-name="{{$info['nickname']}}"data-desc="下级会员"><i class="layui-icon">下级会员</i></button>
                    <button class="layui-btn layui-btn-xs @if($info['agentCount']==0) layui-btn-disabled @else layui-btn-normal @endif agent" @if($info['agentCount']==0) disabled="disabled" @else style="background-color: #9e856a" @endif data-id="{{$info['id']}}"data-name="{{$info['nickname']}}" data-desc="下级代理"><i class="layui-icon">下级代理</i></button>
                    <button class="layui-btn layui-btn-xs  cz" data-id="{{$info['id']}}" data-name="{{$info['nickname']}}"data-desc="下级会员"><i class="layui-icon">充值提现</i></button>
                    <button class="layui-btn layui-btn-xs edit-btn" data-id="{{$info['id']}}" data-desc="修改代理" @if($info['userType']==1) data-url="{{url('/admin/agent/'. $info['id'] .'/edit')}}" @else data-url="{{url('/admin/onAgent/'. $info['id'] .'/edit')}}" @endif><i class="layui-icon">&#xe642;</i>账号编辑</button>
                    <button class="layui-btn layui-btn-xs resetPwd" data-id="{{$info['id']}}" data-name="{{$info['nickname']}}"data-desc="下级会员"><i class="layui-icon">&#xe673;</i>修改密码</button>
                    <button class="layui-btn layui-btn-xs @if($info['groupBalance']!=0)layui-btn-disabled @else layui-btn-danger @endif  del-btn" @if($info['groupBalance']!=0) disabled="disabled" @endif data-id="{{$info['id']}}" data-url="{{url('/admin/agent/'.$info['id'])}}"><i class="layui-icon">&#xe640;</i>结算删除</button>
                </td>
            </tr>
        @endforeach
        @if(!$list[0])
            <tr><td colspan="9" style="text-align: center;color: orangered;">暂无数据</td></tr>
        @endif
        </tbody>
    </table>
    <input type="hidden" id="token" value="{{csrf_token()}}">
    <div class="page-wrap" style="text-align: center;">
        {{--{{$list->render()}}--}}
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
            $(".children").click(function () {
                var id = $(this).attr('data-id');
                layer.open({
                    type:2,
                    title:'结构关系',
                    shadeClose:true,
                    offset:'10%',
                    area:['30%','50%'],
                    content:'/admin/getRelationalStruct/'+id
                });
            });
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
            //停用启用
            form.on('switch(switchTest)',function (data) {
                var that = $(data.elem);
                var id = that.attr('data-id');
                if(!this.checked){
                    /*layer.confirm('确定要停用吗？',{title:'提示'},function (index) {*/
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
                    /*);*/
                }else{
                    /*layer.confirm('确定要启用吗？',{title:'提示'},function (index) {*/
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
                            /*});*/
                        }
                    );
                }
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
            $(".reset").click(function(){
                $('input[name="username"]').val('')
                $('input[name="nickname"]').val('')
                $('select[name="userType"]').val('')
            });
            $(".cz").click(function () {
                var id = $(this).attr('data-id');
                var name = $(this).attr('data-name');
                var index = layer.open({
                    type:2,
                    title: name + "充值提现",
                    shadeClose:true,
                    offset:'10%',
                    area:['60%','80%'],
                    content:'/admin/czEdit/' + id,
                    end:function () {
                        location.reload();
                    }
                });
                layer.full(index);
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
                    content:'/admin/agent/subUser/' + id
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
                    content:'/admin/agent/subordinate/'+id
                });
                layer.full(index)
            });
            $(".insert").click(function () {
                //示范一个公告层
                layer.open({
                    type: 1
                    ,title: false //不显示标题栏
                    ,closeBtn: false
                    ,area: '300px;'
                    ,shade: 0.8
                    ,id: '11' //设定一个id，防止重复弹出
                    ,btn: ['线下', '线上','关闭']
                    ,btnAlign: 'c'
                    ,moveType: 0 //拖拽模式，0或者1
                    //,content: '<div style="padding: 50px; line-height: 22px; background-color: #393D49; color: #fff; font-weight: 300;">你知道吗？亲！<br>layer ≠ layui<br><br>layer只是作为Layui的一个弹层模块，由于其用户基数较大，所以常常会有人以为layui是layerui<br><br>layer虽然已被 Layui 收编为内置的弹层模块，但仍然会作为一个独立组件全力维护、升级。<br><br>我们此后的征途是星辰大海 ^_^</div>'
                    ,success: function(layero){
                        var btn = layero.find('.layui-layer-btn');
                        btn.find('.layui-layer-btn0').click(function () {
                            var index = layer.open({
                                type:2,
                                title:'添加线下代理',
                                shadeClose:true,
                                offset:'10%',
                                area:['60%','80%'],
                                content:'/admin/agent/0/edit'
                            });
                            layer.full(index);
                        });
                        btn.find('.layui-layer-btn1').click(function () {
                            var index = layer.open({
                                type:2,
                                title:'添加线上代理',
                                shadeClose:true,
                                offset:'10%',
                                area:['60%','80%'],
                                content:'/admin/onAgent/0/edit'
                            });
                            layer.full(index);
                        });
                    }
                });
            });
            form.on('submit(formDemo)', function(data) {
            });
        });
    </script>
@endsection
@extends('common.list')