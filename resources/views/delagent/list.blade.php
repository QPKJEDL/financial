@section('title', '会员列表')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#xe9aa;</i></button>
        <button class="layui-btn reset" lay-submit>重置</button>
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="username" value="{{ $input['username'] or '' }}" name="username" placeholder="代理账号"" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="nickname" value="{{ $input['nickname'] or '' }}" name="nickname" placeholder="昵称" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <select name="user_type">
            <option value="">请选择身份</option>
            <option value="1"{{isset($input['user_type'])&&$input['user_type']==1?'selected':''}}>线下</option>
            <option value="2"{{isset($input['user_type'])&&$input['user_type']==2?'selected':''}}>线上</option>
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
        </colgroup>
        <thead>
        <tr>
            <th class="hidden-xs">昵称</th>
            <th class="hidden-xs">代理账号</th>
            <th class="hidden-xs">最近充值</th>
            <th class="hidden-xs">账户余额</th>
            <th class="hidden-xs">百/龙/牛/三/A</th>
            <th class="hidden-xs">抽水</th>
            <th class="hidden-xs">占成</th>
            <th class="hidden-xs">创建日期</th>
        </tr>
        </thead>
        <tbody>
        @foreach($list as $info)
            <tr>
                <td class="hidden-xs">{{$info['nickname']}}</td>
                <td class="hidden-xs">{{$info['username']}}</td>
                <td class="hidden-xs">0.00</td>
                <td class="hidden-xs">{{number_format($info['balance']/100,2)}}</td>
                <td class="hidden-xs">
                    @if($info['userType']==1)
                        {{$info['fee']['baccarat']}}/{{$info['fee']['dragonTiger']}}/{{$info['fee']['niuniu']}}/{{$info['fee']['sangong']}}/{{$info['fee']['A89']}}
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
                <td class="hidden-xs">
                    @if($info['userType']==1)
                        {{$info['proportion']}}%
                    @else
                        0%
                    @endif
                </td>
                <td class="hidden-xs">{{$info['created_at']}}</td>
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
            $(".reset").click(function(){
                $("input[name='username']").val('')
                $('input[name="nickname"]').val('')
            });
            form.render();
            form.on('submit(formDemo)', function(data) {
                console.log(data);
            });
        });
    </script>
@endsection
@extends('common.list')