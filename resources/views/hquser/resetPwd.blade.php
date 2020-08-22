@section('title', '修改密码')
@section('content')
    <div class="layui-form-item">
        <label class="layui-form-label">密码：</label>
        <div class="layui-input-block">
            <input type="password" name="password" placeholder="请输入密码" lay-verify="password" autocomplete="off" class="layui-input">
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">确认密码：</label>
        <div class="layui-input-block">
            <input type="password" name="newPwd"  placeholder="请确认密码" lay-verify="newPwd" autocomplete="off" class="layui-input">
        </div>
    </div>
@endsection
@section('id',$id)
@section('js')
    <script type="text/javascript">
        layui.use(['form','jquery','layer'], function() {
            var form = layui.form
                ,layer = layui.layer
                ,$ = layui.jquery;
            form.render();
            form.verify({
                password:function(value){
                    if(value.length<5 || value.length>11){
                        return '密码长度需要大于5个字符且小于11个字符';
                    }
                },
                newPwd:function(value){
                    var password = $("input[name='password']").val();
                    if(password!=value){
                        return '两次密码不一致，请重新输入';
                    }
                }
            });
            form.on('submit(formDemo)', function(data) {
                $.ajax({
                    url:"{{url('/admin/hquser/updatePassword')}}",
                    data:$('form').serialize(),
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
@endsection
@extends('common.edit')