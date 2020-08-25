<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
    <title>修改用户</title>
    <link rel="stylesheet" type="text/css" href="/static/admin/layui/css/layui.css"/>
    <link rel="stylesheet" type="text/css" href="/static/admin/css/admin.css"/>
</head>
<body>
<div class="layui-tab page-content-wrap">
    <ul class="layui-tab-title">
        <li class="layui-this">修改密码</li>
    </ul>
    <div class="layui-tab-content">
        <div class="layui-tab-item layui-show">
            <form class="layui-form" style="width: 90%;padding-top: 20px;" id="pwd_form">
                {{ csrf_field() }}
                <div class="layui-form-item">
                    <label class="layui-form-label">用户名：</label>
                    <div class="layui-input-block">
                        <input type="text" name="username" disabled autocomplete="off" class="layui-input layui-disabled" value="{{$userinfo['username']}}">
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">旧密码：</label>
                    <div class="layui-input-block">
                        <input type="password" name="oldpwd" required lay-verify="oldpwd" placeholder="请输入密码" autocomplete="off" class="layui-input">
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">新密码：</label>
                    <div class="layui-input-block">
                        <input type="password" name="pwd" required lay-verify="pwd" placeholder="请输入密码" autocomplete="off" class="layui-input">
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">重复密码：</label>
                    <div class="layui-input-block">
                        <input type="password" name="pwd_confirmation" required lay-verify="pwd_confirmation" placeholder="请输入密码" autocomplete="off" class="layui-input">
                    </div>
                </div>
                <input name="id" type="hidden" value="{{$userinfo['id'] or 0}}">

                <div class="layui-form-item">
                    <div class="layui-input-block">
                        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="adminPassword">立即提交</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="/static/admin/layui/layui.js" type="text/javascript" charset="utf-8"></script>
<script>
    layui.use(['form','jquery','element'], function(){
        var form = layui.form(),
            $ = layui.jquery;
        form.render();
        form.verify({
            oldpwd:function(value){
                if(value.length<6){
                    return '旧密码最低6位长度';
                }
            },
            pwd:function(value){
                if(value==$("input[name='oldpwd']").val()){
                    return '新密码不能与旧密码一样';
                }
                if(value.length<6){
                    return '新密码长度不能低于6位';
                }
            },
            pwd_confirmation: function(value) {
                if(value && $("input[name='pwd']").val() != value) {
                    return '两次输入密码不一致';
                }
            },
        });
        form.on('submit(adminPassword)', function(data){
            $.ajax({
                url:"{{url('/admin/updatePwd')}}",
                data:$('#pwd_form').serialize(),
                type:'post',
                dataType:'json',
                success:function(res){
                    if(res.status == 1){
                        layer.msg(res.msg,{icon:6});
                        var index = parent.layer.getFrameIndex(window.name);
                        setTimeout('parent.layer.close('+index+')',2000);
                    }else{
                        layer.msg(res.msg,{shift: 6,icon:5});
                    }
                },
                error : function(XMLHttpRequest, textStatus, errorThrown) {
                    layer.msg('网络失败', {time: 1000});
                }
            });
            return false;
        });
    });
</script>
</body>
</html>