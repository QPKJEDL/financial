@section('title', '会员列表')
@section('header')
    <div class="wrap-container welcome-container">
        <div class="row">
            <div class="welcome-left-container col-lg-9">
                <div class="data-show">
                    <ul class="clearfix">
                        <li class="col-sm-4 col-md-4 col-xs-4">
                            <a href="javascript:;" class="clearfix">
                                <div class="icon-bg bg-org f-l">
                                    <span class="iconfont">&#xe605;</span>
                                </div>
                                <div class="right-text-con">
                                    <p class="name">总人数</p>
                                    <p><span class="color-org">{{$count}}</span></p>
                                </div>
                            </a>
                        </li>
                        <li class="col-sm-4 col-md-4 col-xs-4">
                            <a href="javascript:;" class="clearfix">
                                <div class="icon-bg bg-blue f-l">
                                    <span class="iconfont">&#xe602;</span>
                                </div>
                                <div class="right-text-con">
                                    <p class="name">电脑版</p>
                                    <p><span class="color-blue">{{$pc}}</span></p>
                                </div>
                            </a>
                        </li>
                        <li class="col-sm-4 col-md-4 col-xs-4">
                            <a href="javascript:;" class="clearfix">
                                <div class="icon-bg bg-green f-l">
                                    <span class="iconfont">&#xe605;</span>
                                </div>
                                <div class="right-text-con">
                                    <p class="name">苹果版</p>
                                    <p><span class="color-green">{{$ios}}</span></p>
                                </div>
                            </a>
                        </li>
                        <li class="col-sm-4 col-md-4 col-xs-4">
                            <a href="javascript:;" class="clearfix">
                                <div class="icon-bg bg-green f-l">
                                    <span class="iconfont">&#xe605;</span>
                                </div>
                                <div class="right-text-con">
                                    <p class="name">安卓版</p>
                                    <p><span class="color-green">{{$android}}</span></p>
                                </div>
                            </a>
                        </li>
                        <li class="col-sm-4 col-md-4 col-xs-4">
                            <a href="javascript:;" class="clearfix">
                                <div class="icon-bg bg-green f-l">
                                    <span class="iconfont">&#xe605;</span>
                                </div>
                                <div class="right-text-con">
                                    <p class="name">网页版</p>
                                    <p><span class="color-green">{{$h5}}</span></p>
                                </div>
                            </a>
                        </li>
                        <li class="col-sm-4 col-md-4 col-xs-4">
                            <a href="javascript:;" class="clearfix">
                                <div class="icon-bg bg-green f-l">
                                    <span class="iconfont">&#xe600;</span>
                                </div>
                                <div class="right-text-con">
                                    <p class="name">总金额</p>
                                    <p><span class="color-green">{{number_format($money,2)}}</span></p>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
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