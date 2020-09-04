@section('title', '会员列表')
@section('header')
    <div class="wrap-container welcome-container">
        <div class="row">
            <div class="welcome-left-container col-lg-9">
                <div class="data-show">
                    <ul class="clearfix" id="iconUl">
                        <li class="col-sm-4 col-md-4 col-xs-4">
                            <a href="javascript:;" class="clearfix">
                                <div class="icon-bg bg-org f-l" style="background: #7480a9">
                                    <i class="layui-icon">&#xe658;</i>
                                </div>
                                <div class="right-text-con">
                                    <p class="name">总人数</p>
                                    <p><span class="color-org">{{$count}}</span></p>
                                </div>
                            </a>
                        </li>
                        <li class="col-sm-4 col-md-4 col-xs-4">
                            <a href="javascript:;" class="clearfix">
                                <div class="icon-bg bg-blue f-l" style="background: #aab13f">
                                    <i class="layui-icon ">&#xe638;</i>
                                </div>
                                <div class="right-text-con">
                                    <p class="name">电脑版</p>
                                    <p><span class="color-blue">{{$pc}}</span></p>
                                </div>
                            </a>
                        </li>
                        <li class="col-sm-4 col-md-4 col-xs-4">
                            <a href="javascript:;" class="clearfix">
                                <div class="icon-bg bg-green f-l" style="background: #57bbd0">
                                    <i class="layui-icon">&#xe680;</i>
                                </div>
                                <div class="right-text-con">
                                    <p class="name">苹果版</p>
                                    <p><span class="color-green">{{$ios}}</span></p>
                                </div>
                            </a>
                        </li>
                        <li class="col-sm-4 col-md-4 col-xs-4">
                            <a href="javascript:;" class="clearfix">
                                <div class="icon-bg bg-green f-l" style="background: #5cbf78">
                                    <i class="layui-icon ">&#xe684;</i>
                                </div>
                                <div class="right-text-con">
                                    <p class="name">安卓版</p>
                                    <p><span class="color-green">{{$android}}</span></p>
                                </div>
                            </a>
                        </li>
                        <li class="col-sm-4 col-md-4 col-xs-4">
                            <a href="javascript:;" class="clearfix">
                                <div class="icon-bg bg-green f-l" style="background: #ff8822">
                                    <i class="layui-icon ">&#xe67f;</i>
                                </div>
                                <div class="right-text-con">
                                    <p class="name">网页版</p>
                                    <p><span class="color-green">{{$h5}}</span></p>
                                </div>
                            </a>
                        </li>
                        <li class="col-sm-4 col-md-4 col-xs-4">
                            <a href="javascript:;" class="clearfix">
                                <div class="icon-bg bg-green f-l" style="background: #dab569">
                                    <i class="layui-icon ">&#xe65e;</i>
                                </div>
                                <div class="right-text-con">
                                    <p class="name">总金额</p>
                                    <p><span class="color-green">{{$money/100}}</span></p>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#xe9aa;</i></button>
        <button class="layui-btn layui-btn-normal reset" lay-submit>重置</button>
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="username" value="{{ $input['username'] or '' }}" name="username" placeholder="代理账号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="account" value="{{ $input['account'] or '' }}" name="account" placeholder="会员账号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <select name="user_type">
            <option value="">请选择会员身份</option>
            <option value="1" {{isset($input['user_type'])&&$input['user_type']==1?'selected':''}}>线上</option>
            <option value="2" {{isset($input['user_type'])&&$input['user_type']==2?'selected':''}}>线下</option>
        </select>
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
        </colgroup>
        <thead>
        <tr>
            <th class="hidden-xs">账号</th>
            <th class="hidden-xs">名称</th>
            <th class="hidden-xs">直属上级</th>
            <th class="hidden-xs">当前余额</th>
            <th class="hidden-xs">登录IP</th>
            <th class="hidden-xs">所在台桌</th>
            <th class="hidden-xs">登录时间</th>
            <th class="hidden-xs">客户端</th>
            <th class="hidden-xs">操作</th>
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
                <td class="hidden-xs">{{number_format($info['balance']/100,2)}}</td>
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
                    @elseif($info['online_type']==5)
                        三方
                    @else
                        未知
                    @endif
                </td>
                <td class="hidden-xs">
                    <button type="button" data-id="{{$info['user_id']}}" data-url="{{url('/admin/retreat/'.$info['user_id'])}}" class="a layui-btn layui-btn-primary layui-btn-xs">强踢</button>
                </td>
            </tr>
        @endforeach
        @if(!$list[0])
            <tr><td colspan="9" style="text-align: center;color: orangered;">暂无数据</td></tr>
        @endif
        </tbody>
    </table>
    <input type="hidden" name="_token" value="{{csrf_token()}}">
    <div class="page-wrap">
        {{$list->render()}}
    </div>
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
                $("input[name='begin']").val('');
                $("select[name='desk_id']").val(''); 
                $("input[name='boot']").val('');
            });
            $('.a').click(function () {
                var id = $(this).attr('data-id');
                layer.confirm('确定要把该用户踢下线？', {
                    btn: ['确定','取消'] //按钮
                }, function(){
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': $("input[name='_token']").val()
                        },
                        url:"{{url('/admin/retreat')}}",
                        type:'post',
                        data:{
                            'id':id
                        },
                        success:function (res) {
                            if (res.status==1){
                                location.reload();
                            }else{
                                layer.msg(res.msg);
                            }
                        }
                    });
                }, function(){
                    layer.msg('取消了');
                    location.reload();
                });
            });
            form.render();
            form.on('submit(formDemo)', function(data) {
                console.log(data);
            });
        });
    </script>
    <style>
        #iconUl li{height: 50px;border-radius: 5px;overflow: hidden;display: inline-block;width: 16%}
        #iconUl li a{display: block;height: 100%}
        #iconUl li a div{display: inline-block;background: #fff;height: 100%;vertical-align: middle;text-align: center;width: 45%;}
        #iconUl li a div .name{padding-top: 8px}
        #iconUl li i{color: #fff;line-height: 50px;font-size: 26px}
        @media (max-width: 700px) {
            #iconUl li{width: 48%}

        }
    </style>
@endsection
@extends('common.list')