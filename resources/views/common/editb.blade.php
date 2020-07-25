<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
    <title>@yield('title') | {{ Config::get('app.name') }}</title>
    <link rel="stylesheet" type="text/css" href="/static/admin/layui/css/layui.css" />
    <link rel="stylesheet" type="text/css" href="/static/admin/css/admin.css" />
    <link href="/static/admin/summernote/summernote.css" rel="stylesheet">
    <link href="/static/admin/summernote/summernote-bs3.css" rel="stylesheet">
    <script src="/static/admin/layui/layui.js" type="text/javascript" charset="utf-8"></script>
    <script src="/static/admin/js/common.js" type="text/javascript" charset="utf-8"></script>
    <script src="http://apps.bdimg.com/libs/jquery/2.1.4/jquery.min.js"></script>
    <script src="http://netdna.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.js"></script>
    <script src="/static/admin/summernote/summernote.min.js"></script>
    <!--引入中文JS包-->
    <script src="/static/admin/summernote/summernote-zh-CN.js"></script>
    <script src="/static/admin/js/qrcode.js" type="text/javascript" charset="utf-8"></script>
</head>
<body>
<div class="wrap-container">
    <form class="layui-form" style="width: 90%;padding-top: 20px;">
        {{ csrf_field() }}
        @yield('content')
        <input name="id" type="hidden" value="@yield('id')">
    </form>
</div>
@yield('js')
</body>
</html>