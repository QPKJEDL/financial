@section('title', '公告编辑')
@section('content')
    <div class="layui-form-item">
        <label class="layui-form-label">用户类型：</label>
        <div class="layui-input-block">
            <input type="radio" name="user_type" value="1" title="代理" checked="">
            <input type="radio" name="user_type" value="2" title="会员">
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">拉黑账号：</label>
        <div class="layui-input-inline">
            <input type="text" value="" name="account"  placeholder="请输入拉黑账号" lay-verify="required" autocomplete="off" class="layui-input">
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">拉黑权限：</label>
        <div class="layui-input-inline">
            <select name="status" lay-filter="status">
                <option value="">请选择</option>
                <option value="1">打赏</option>
            </select>
        </div>
    </div>
    <div class="layui-form-item layui-form-text">
        <label class="layui-form-label">备注：</label>
        <div class="layui-input-block">
            <textarea placeholder="请输入备注" class="layui-textarea" name="remark"></textarea>
        </div>
    </div>
@endsection
@section('id',$id)
@section('js')
    <script>
        layui.use(['form','jquery','layer'], function() {
            var form = layui.form()
                ,layer = layui.layer
                ,$ = layui.jquery;
            form.render();
            form.verify({
                status:function (value) {
                    if(value=="")
                    {
                        return '请选择拉黑权限'
                    }
                }
            });
            var index = parent.layer.getFrameIndex(window.name);
                form.on('submit(formDemo)', function(data) {
                    var data = $('form').serializeArray();
                    console.log(data);
                    $.ajax({
                        url:"{{url('/admin/userBack')}}",
                        data:data,
                        type:'post',
                        dataType:'json',
                        success:function(res){
                            if(res.status == 1){
                                layer.msg(res.msg,{icon:6},function () {
                                    parent.layer.close(index);
                                    window.parent.frames[1].location.reload();
                                });
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