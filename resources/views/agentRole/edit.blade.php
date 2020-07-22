@section('title', '角色编辑')
@section('content')
    <div class="layui-form-item">
        <label class="layui-form-label">角色标识：</label>
        <div class="layui-input-block">
            <input type="text" value="{{$info['name'] or ''}}" name="name" required lay-verify="name" placeholder="请输入2-12位字母" autocomplete="off" class="layui-input">
        </div>
    </div>

    <div class="layui-form-item">
        <label class="layui-form-label">角色名称：</label>
        <div class="layui-input-block">
            <input type="text" value="{{$info['display_name'] or ''}}" name="display_name" required lay-verify="role_name" placeholder="请输入2-12位汉字" autocomplete="off" class="layui-input">
        </div>
    </div>

    <div class="layui-form-item">
        <label class="layui-form-label">角色描述：</label>
        <div class="layui-input-block">
            <textarea name="description" placeholder="请输入2-30位汉字" class="layui-textarea" required lay-verify="description">{{$info['description'] or ''}}</textarea>
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">菜单权限：</label>
        <div class="layui-input-block">
            <ul id="tree" class="ztree" style="width: 230px;overflow:auto;"></ul>
        </div>
    </div>
@endsection
@section('id',$id)
@section('js')
    <script>
        window.onload=function(){
            var data= '<?php echo urlencode(json_encode($tree));?>';
            var list = eval(decodeURIComponent(data));

            var setting = {
                check:{
                    enable:true,
                    chkStyle:"checkbox"
                },
                data :{
                    simpleData:{
                        enable:true
                    }
                },
                callback:{
                    beforeCheck:true,
                    onCheck:zTreeOnClick
                }
            };
            var city = $.fn.zTree.init($("#tree"), setting, list);
        }
        var array = new Array();
        function zTreeOnClick(event,treeId,treeNode){
            var treeObj = $.fn.zTree.getZTreeObj('tree'),
            nodes = treeObj.getCheckedNodes(true);
            array = nodes;
        }
        layui.use(['form','jquery','laypage', 'layer','tree','util'], function() {
            var tree = layui.tree
            ,layer = layui.layer
            ,util = layui.util;
            var form = layui.form(),
                $ = layui.jquery;
            form.render();
            var layer = layui.layer;
            form.on('submit(formDemo)', function(data) {
                console.log(array);
                var data = $('form').serializeArray();
                data.push({"name":"menus","value":JSON.stringify(array)});
                var id = $("input[name='id']").val();
                if(id==0){
                    $.ajax({
                        url:"{{url('/admin/agentRole')}}",
                        data:data,
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
                }else{
                    $.ajax({
                        url:"{{url('/admin/agentRole/update')}}",
                        data:data,
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
                }
                return false;
            });
        });
    </script>
@endsection
@extends('common.edit')
