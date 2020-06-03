<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
    <title>后台用户绑定谷歌验证码</title>
    <link rel="stylesheet" type="text/css" href="/static/admin/layui/css/layui.css" />
    <link rel="stylesheet" type="text/css" href="/static/admin/css/login.css" />
</head>

<body>
<div class="m-login-bg">
    <div class="m-login">
        <h3>后台用户绑定谷歌验证码</h3>
        <div class="m-login-warp">
            <form class="layui-form" id="form">
                {{--{{ csrf_field() }}--}}
                <input type="hidden" id="token" value="{{csrf_token()}}">
                <div class="layui-form-item">
                    <input type="text" name="account" lay-verify="account" placeholder="用户名" autocomplete="off" class="layui-input">
                </div>
                <div class="layui-form-item">
                    <input type="password" name="password" lay-verify="password" placeholder="密码" autocomplete="off" class="layui-input">
                </div>
                <div class="layui-form-item m-login-btn">
                    {{--<div class="layui-inline">
                        <button class="layui-btn layui-btn-primary" id="bind">绑定</button>
                    </div>--}}
                    <div class="layui-inline">
                        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="submit">验证</button>
                    </div>
                    <div class="layui-inline">
                        <button type="reset" class="layui-btn layui-btn-primary" id="res">返回登陆</button>
                    </div>
                </div>
            </form>
        </div>
        <p class="copyright">Copyright 2018-{{date("Y",time())}} by EPP</p>
    </div>
</div>
<script src="/static/admin/layui/layui.js" type="text/javascript" charset="utf-8"></script>
<script src="/static/admin/js/qrcode.js" type="text/javascript" charset="utf-8"></script>
<script>
    layui.use(['form','jquery'], function() {
        var form = layui.form(),
            layer = layui.layer;
        $ = layui.jquery;
        form.verify({
            username: [/(.+){2,12}$/, '用户名必须2到12位'],
            password: [/(.+){6,12}$/, '密码必须6到12位'],
        });
        $('#res').click(function () {
            window.location.href="/admin/login";
            // layer.open({
            //     type:1,
            //     title: false,
            //     closeBtn:false,
            //     area: '400px',
            //     shade:0.8,
            //     id:'LAY_layui_code',//设定一个id,防止重复弹出
            //     btn: ['绑定完成去登陆','点击关闭'],
            //     btnAlign:'c',
            //     moveType:1,//拖拽模式 0或1
            //     content:'<fieldset class="layui-elem-field layui-field-title" style="margin-top: 20px;">' +
            //         '<legend>请下载“Google身份验证器”进行扫描绑定</legend>' +
            //         '</fieldset>' +
            //         '<div id="qrcode" style="padding: 50px; line-height: 32px;"></div>',
            //     success:function (layero) {
            //         var btn = layero.find('.layui-layer-btn');
            //         btn.find('.layui-layer-btn0').attr({
            //             href: '/admin/login'
            //         });
            //         new QRCode(document.getElementById("qrcode"), "otpauth://totp/EPayPlusadmin:18037297220?\n" +
            //             "secret=&issuer=EPayPlusadmin&algorithm=SHA1&digits=6&period=30");
            //     }
            // });
        });
        $("input[name='account']").blur(function () {
            var _this = $(this);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('#token').val()
                },
                url:"{{url('/admin/valAccount')}}",
                data:{
                    "account":_this.val()
                },
                type:"post",
                dataType:"json",
                success:function (res) {
                    if(res.status==1){
                        layer.msg(res.msg,{shift: 6,icon:5});
                    }
                }
            });
        });
        form.on('submit(submit)',function (data) {
            //获取账号密码
            var account = $("input[name='account']").val();
            var password = $("input[name='password']").val();
            if(account==null || account == ''){
                layer.msg("账号不能为空！",{shift: 6,icon:5});
                return false;
            }else if(password==null || password ==''){
                layer.msg("密码不能为空！",{shift: 6,icon:5});
                return false;
            }else{
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN':$("#token").val()
                    },
                    url: "{{url('/admin/valUser')}}",
                    data:$("#form").serialize(),
                    type:"post",
                    dataType: "json",
                    success:function (res) {
                        if(res.status==1){
                            layer.open({
                                type:1,
                                title: false,
                                closeBtn:false,
                                area: '400px',
                                shade:0.8,
                                id:'LAY_layuipro',//设定一个id,防止重复弹出
                                btn: ['点击关闭'],
                                btnAlign:'c',
                                moveType:1,//拖拽模式 0或1
                                content:'<div style="padding: 50px; line-height: 22px; background-color: #393D49; color: #fff; font-weight: 300;">' +
                                    /*'<form class="layui-form" id="layui-form">' +*/
                                    '<div class="layui-form-item"><input type="text" name="account" id="account" readonly="readonly" value="'+account+'" class="layui-input" autocomplete="off"></div>' +
                                    '<div class="layui-form-item"><input type="text" name="mobile" id="mobile" placeholder="手机号" class="layui-input" autocomplete="off"></div>' +
                                    '<div class="layui-form-item"><input type="text" name="code" id="code" placeholder="验证码" class="layui-input" autocomplete="off" style="width: 60%;float: left;">' +
                                    '<input type="button" class="layui-btn layui-btn-sm" style="float: right;" onclick="sendMsgCode(this)" value="点击发送">' +
                                    '</div>' +
                                    '<div class="layui-form-item">' +
                                    '<input type="button" class="layui-btn layui-btn-sm" onclick="submit()" value="验证">' +
                                    '</div>' +
                                   /* '</form>' +*/
                                    '</div>',
                                success:function (layero) {
                                    var btn = layero.find('.layui-layer-btn');

                                }
                            });
                        }else{
                            layer.msg(res.msg,{shift: 6,icon:5});
                        }
                    }
                });
            }
            return false;
        });
    });
    //发送验证码
    function sendMsgCode(_this) {
        var account = document.getElementById('account');
        var mobile = document.getElementById('mobile');
        if(mobile.value==null||mobile.value==''){
            layer.msg("手机号不能为空！",{shift: 6,icon:5});
        }else if(!(/^1[3456789]\d{9}$/.test(mobile.value))){
            layer.msg("手机号格式不对！",{shift: 6,icon:5});
        }else{

            $.ajax({
                headers: {
                    'X-CSRF-TOKEN':$("#token").val()
                },
                url:"{{url('/admin/sendSMS')}}",
                data:{
                    "account":account.value,
                    "mobile":mobile.value
                },
                type:"post",
                dataType:"json",
                success:function (res) {
                    if(res.status==1){
                        layer.msg(res.msg,{icon:6});
                        buttoncss(_this);
                    }else{
                        layer.msg(res.msg,{shift: 6,icon:5});
                    }
                }
            });
        }
    }
    //按钮样式
    function buttoncss(_this) {
        //禁用时间
        var count = 60;
        //禁用按钮
        _this.setAttribute('class','layui-btn layui-btn-disabled');
        //把按钮的文本改成重新发送
        _this.value="重新发送"+count+"s";
        var timer = setInterval(function () {
            if(count<=0){
                //清空时间
                clearInterval(timer);
                //修改样式
                _this.setAttribute('class','layui-btn layui-btn-sm');
                //修改文本
                _this.value="点击发送";
                count = 60;
            }else{
                _this.value="重新发送"+(count-1)+"s";
                count--;
            }
        },1000);
    }

    /**
     * 验证
     */
    function submit() {
        //获取账号手机验证码
        var account = document.getElementById('account');
        var mobile = document.getElementById('mobile');
        var code = document.getElementById('code');
        var url;
        if(mobile.value==''||mobile.value==null){
            layer.msg("手机号不能为空！",{shift: 6,icon:5});
        }else if(code.value==''||code.value==null){
            layer.msg("验证码不能为空！",{shift: 6,icon:5});
        }else{
            $.ajax({
                headers:{
                    'X-CSRF-TOKEN':$("#token").val()
                },
                url:"{{url('/admin/bindCode')}}",
                data:{
                    "account":account.value,
                    "mobile":mobile.value,
                    "code":code.value
                },
                type:"post",
                dataType:"json",
                success:function (res) {
                    if(res.status==1){
                        url=res.url;
                        //layer.msg(res.msg,{icon:6});
                        layer.open({
                            type:1,
                            title: false,
                            closeBtn:false,
                            area: '500px',
                            shade:0.8,
                            id:'LAY_layui_code',//设定一个id,防止重复弹出
                            btn: ['绑定完成去登陆','点击关闭'],
                            btnAlign:'c',
                            moveType:1,//拖拽模式 0或1
                            content:'<fieldset class="layui-elem-field layui-field-title" style="margin-top: 20px;">' +
                                '<legend>请下载“Google身份验证器”进行扫描绑定</legend>' +
                                '</fieldset>' +
                                '<div id="qrcode" style="padding: 50px; line-height: 32px;"></div>',
                            success:function (layero) {
                                var btn = layero.find('.layui-layer-btn');
                                btn.find('.layui-layer-btn0').attr({
                                    href: '/admin/login'
                                    ,target: '_blank'
                                });
                                new QRCode(document.getElementById("qrcode"), res.url);
                            }
                        });
                    }else{
                        layer.msg(res.msg,{shift: 6,icon:5});
                    }
                }
            });
        }
        return false;
    }
</script>
</body>

</html>