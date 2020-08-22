<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
    <title>@yield('title') | {{ Config::get('app.name') }}</title>
    <link rel="stylesheet" type="text/css" href="/static/tools/layui/css/layui.css" />
    <link href="/static/admin/summernote/summernote.css" rel="stylesheet">
    <link href="/static/admin/summernote/summernote-bs3.css" rel="stylesheet">
    <link rel="stylesheet" href="/static/admin/css/zTreeStyle/zTreeStyle.css"/>
    <link rel="stylesheet" href="/static/admin/tree/css/iconfont.css"/>
    <script src="/static/tools/layui/layui.js" type="text/javascript" charset="utf-8"></script>
    <script src="/static/admin/js/common.js" type="text/javascript" charset="utf-8"></script>
    <script src="http://apps.bdimg.com/libs/jquery/2.1.4/jquery.min.js"></script>
    <script src="http://netdna.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.js"></script>
    <script src="/static/admin/summernote/summernote.min.js"></script>
    <!--引入中文JS包-->
    <script src="/static/admin/summernote/summernote-zh-CN.js"></script>
    <script src="/static/admin/js/jquery.ztree.all.js" type="text/javascript"></script>
    <script src="/static/admin/tree/leg-tree.js" type="text/javascript"></script>
    <script src="/static/admin/js/select.js" type="text/javascript"></script>
</head>
<body>
<div class="wrap-container">
    <form class="layui-form" style="width: 90%;padding-top: 20px;">
        {{ csrf_field() }}
        @yield('content')
        {{--<div class="layui-form-item">
            <div class="layui-input-block">
                <button class="layui-btn layui-btn-normal" lay-submit lay-filter="formDemo">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>--}}
        <input name="id" type="hidden" value="@yield('id')">
    </form>
</div>
@yield('js')
</body>
</html>