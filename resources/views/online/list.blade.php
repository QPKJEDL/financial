@section('title', '会员列表')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#x1002;</i></button>
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="username" value="{{ $input['username'] or '' }}" name="username" placeholder="代理账号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="account" value="{{ $input['account'] or '' }}" name="account" placeholder="会员账号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <select name="deskId" lay-search="">
            <option value="">请选择台桌</option>
            @foreach($desk as $d)
                <option value="{{$d['id']}}" {{isset($input['deskId'])&&$input['deskId']==$d['id']?'selected':''}}>{{$d['desk_name']}}</option>
            @endforeach
        </select>
    </div>
    <div class="layui-inline">
        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="formDemo">搜索</button>
        <button class="layui-btn layui-btn-normal reset" lay-submit>重置</button>
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
        </colgroup>
        <thead>
        <tr>
            <th class="hidden-xs">账号</th>
            <th class="hidden-xs">名称</th>
            <th class="hidden-xs">直属上级</th>
            <th class="hidden-xs">直属一级</th>
            <th class="hidden-xs">当前余额</th>
            <th class="hidden-xs">登录IP</th>
            <th class="hidden-xs">地区</th>
            <th class="hidden-xs">所在台桌</th>
            <th class="hidden-xs">登录时间</th>
            <th class="hidden-xs">客户端</th>
        </tr>
        </thead>
        <tbody>
        @foreach($list as $info)
            <tr>
                <td class="hidden-xs">{{$info['account']}}</td>
                <td class="hidden-xs">{{$info['nickname']}}</td>
                <td class="hidden-xs">
                    @if($info['username']==null || $info['username']=='')
                        归属公司
                    @else
                        {{$info['username']}}
                    @endif
                </td>
                <td class="hidden-xs">{{$info['balance']/100}}</td>
                <td class="hidden-xs">{{$info['last_ip']}}</td>
                <td class="hidden-xs">
                    @if($info['desk_id']==0)
                        未入台
                    @else
                        {{$info['desk_name']}}
                    @endif
                </td>
                <td class="hidden-xs">{{$info['savetime']}}</td>
                <td class="hidden-xs">
                    @if($info['online_type']==1)
                        电脑版
                    @elseif($info['online_type']==2)
                        苹果版
                    @elseif($info['online_type']==3)
                        安卓版
                    @elseif($info['online_type']==4)
                        网页版
                    @else
                        未知
                    @endif
                </td>
            </tr>
        @endforeach
        @if(!$list[0])
            <tr><td colspan="9" style="text-align: center;color: orangered;">暂无数据</td></tr>
        @endif
        </tbody>
    </table>
    <div class="page-wrap">
        {{$list->render()}}
    </div>
@endsection
@section('js')
    <script>
        layui.use(['form', 'jquery','laydate', 'layer'], function() {
            var form = layui.form(),
                $ = layui.jquery,
                laydate = layui.laydate,
                layer = layui.layer
            ;
            laydate({istoday: true});
            $(".reset").click(function(){
                $("input[name='begin']").val('');
                $("select[name='desk_id']").val(''); 
                $("input[name='boot']").val('');
            });
            form.render();
            form.on('submit(formDemo)', function(data) {
                console.log(data);
            });
        });
    </script>
@endsection
@extends('common.list')