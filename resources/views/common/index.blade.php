<!DOCTYPE html>
<html>
<head>
    <title>环球国际代理后台</title>
    <meta name="renderer" content="webkit"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="format-detection" content="telephone=no">
    <link rel="stylesheet" type="text/css" href="/static/tools/layui/css/layui.css"/>
    <link rel="stylesheet" type="text/css" href="/static/tools/css/admin.css"/>
    <script src="/static/tools/js/jquery-3.3.1.min.js" type="text/javascript" charset="utf-8"></script>
</head>
<body>
<header class="header" style="position: fixed;width: 100%;">
    <div class="header-left">
        <ul class="layui-nav layui-bg-cyan menuBox"  lay-filter="leftNav" id="menuList">
            <li class="layui-nav-item layui-this"><img src="/static/tools/img/logo.png" onclick="location.reload()"/></li>
            <li class="layui-nav-item"><a href="javascript:;" data-id="0">个人中心</a></li>
            @foreach($list as $info)
                @if($info['parent_id']==0)
                    <li class="layui-nav-item" ><a href="javascript:;" data-id="{{$info['id']}}">{{$info['name']}}</a></li>
                @endif
            @endforeach
        </ul>
        <span id="menuTxt">菜单</span>
    </div>
    <div class="header-right">
        <ul class="layui-nav layui-bg-cyan" lay-filter="rightNav">
            <li class="layui-nav-item">
                <div class="hidden-xs"><a href="javascript:;">上分请求<span class="layui-badge">0</span></a></div>
            </li>
            <li class="layui-nav-item">
                <div class="hidden-xs downCode" data-url="{{url('/admin/down')}}" data-title="下分请求" data-id="36"><a href="javascript:;">下分请求<span class="layui-badge" id="down">0</span></a></div>
            </li>
            <li class="layui-nav-item">
                <div class="hidden-xs">&nbsp;<i class="layui-icon">&#xe612;</i>&nbsp;{{\Illuminate\Support\Facades\Auth::user()['username']}}&nbsp;</div>
            </li>
            <li class="layui-nav-item">
                <div class="addBtn hidden-xs" data-url="{{url('/admin/userinfo')}}">&nbsp;<i class="layui-icon">&#xe673;</i>&nbsp;修改密码&nbsp;</div>
            </li>
            <li class="layui-nav-item"><a href="{{url('/admin/logout')}}">退出</a></li>
        </ul>
    </div>
    <div class="menuList">
        <a href="javascript:;" class="menuCli menuList-0" style="text-decoration: underline;display: none;" data-parent="0" data-title="个人中心" data-url="{{url('/admin/home')}}" data-id="0">个人中心</a>
        @foreach($list as $info)
            @if($info['parent_id']!=0)
                <a href="javascript:;" class="menuCli menuList-{{$info['parent_id']}}" style="text-decoration: underline;display: none;" data-parent="{{$info['parent_id']}}" data-title="{{$info['name']}}" data-url="{{url($info['uri'])}}" data-id="{{$info['id']}}">{{$info['name']}}</a>
            @endif
        @endforeach
    </div>
    <div class="layui-tab layui-tab-brief" id="nav" lay-filter="menuTab" lay-allowclose="true">
        <ul class="layui-tab-title tabList">
            <li lay-id="0" class="layui-this">首页</li>
        </ul>
        <div class="layui-tab-content " >
            <div class="layui-tab-item layui-show" id="index">
                <iframe src="{{url('/admin/home')}}" frameborder="0" style="width: 100%; height: calc(100vh - 157px);" id="demoAdmin"></iframe>
            </div>
        </div>
    </div>
</header>
<script src="/static/tools/layui/layui.js" type="text/javascript" charset="utf-8"></script>
<script src="/static/tools/js/menu.js?t=2" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript">
    setInterval("aaa()",1000)
    function aaa() {
        $.ajax({
            type:"get",
            url:window.location.protocol+"//"+window.location.host + "/admin/getUnRead",
            data:null,
            dataType:'json',
            success:function (res) {
                var down = parseInt($("#down").html());
                $("#down").html(down+parseInt(res.count));
            }
        });
    }
</script>
</body>
</html>
