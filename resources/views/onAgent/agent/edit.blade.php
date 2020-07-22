@section('title', '配置编辑')
@section('content')
    <div class="layui-form-item">
        <label class="layui-form-label">用户名：</label>
        <div class="layui-input-block">
            <input type="text" value="{{$info['username'] or ''}}" name="username" required lay-verify="user_name" placeholder="请输入用户名(请不要出现汉字)" autocomplete="off" class="layui-input" @if($id!=0) readonly @endif>
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">名称：</label>
        <div class="layui-input-block">
            <input type="text" value="{{$info['nickname'] or ''}}" name="nickname" required lay-verify="required" placeholder="请输入名称" autocomplete="off" class="layui-input">
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">角色：</label>
        <div class="layui-input-block">
            <select name="user_role" lay-verify="required">
                <option value=""></option>
                @foreach($roles as $role)
                    <option
                            value="{{ $role->id }}" {{isset($userRole['role_id'])&&$userRole['role_id']==$role->id?'selected':''}}>{{ $role->display_name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">状态：</label>
        <div class="layui-input-block">
            <input type="radio" name="status" value="0" title="正常"
                   @if(!isset($info['status']))
                   checked
                   @elseif(isset($info['status'])&&$info['status'])
                   checked
            @else
                    @endif>
            <input type="radio" name="status" value="1" title="停用" {{isset($info['status'])&&!$info['status']?'checked':''}}>
        </div>
    </div>
    @if($info==null)
        <div class="layui-form-item">
            <label class="layui-form-label">密码：</label>
            <div class="layui-input-block">
                <input type="password" name="pwd" lay-verify="pwd" placeholder="请输入6-12位数字加字母密码" autocomplete="off" class="layui-input">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">确认密码：</label>
            <div class="layui-input-block">
                <input type="password" name="pwd_confirmation" lay-verify="pwd_confirmation" placeholder="请确认密码" autocomplete="off" class="layui-input">
            </div>
        </div>
    @endif
    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">抽水：</label>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="pump" lay-verify="pump" value="{{$info['pump'] or ''}}"  placeholder="%" autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid layui-word-aux">比如20%就填写20</div>
        </div>
    </div>
    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">占比：</label>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="proportion" lay-verify="proportion" value="{{$info['proportion'] or ''}}"  placeholder="%" autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid layui-word-aux">比如20%就填写20</div>
        </div>
    </div>
    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">最小限红</label>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="limit[min]" lay-verify="minLimit" value="{{$info['limit']['min'] or '10'}}"  placeholder="￥" autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">最大限红</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="limit[max]" lay-verify="maxLimit" value="{{$info['limit']['max'] or '50000'}}" placeholder="￥" autocomplete="off" class="layui-input">
            </div>
        </div>
    </div>
    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">最小和限红</label>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="limit[tieMin]" lay-verify="minPairLimit" value="{{$info['limit']['tieMin'] or '10'}}" placeholder="￥" autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">最大和限红</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="limit[tieMax]" lay-verify="maxPairLimit" value="{{$info['limit']['tieMax'] or '5000'}}" placeholder="￥" autocomplete="off" class="layui-input">
            </div>
        </div>
    </div>
    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">最小对限红</label>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="limit[pairMin]" lay-verify="minTieLimit" value="{{$info['limit']['pairMin'] or '10'}}"  placeholder="￥" autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">最大对限红</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="limit[pairMax]" lay-verify="maxTieLimit" value="{{$info['limit']['pairMax'] or '5000'}}"  placeholder="￥" autocomplete="off" class="layui-input">
            </div>
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">状态：</label>
        <div class="layui-input-block">
            <input type="radio" name="data_permission" value="2" title="本人及以下权限"
                   @if(!isset($info['data_permission']))
                   checked
                   @elseif(isset($info['data_permission'])&&$info['data_permission'])
                   checked
            @else
                    @endif>
            <input type="radio" name="data_permission" value="1" title="所有数据权限" {{isset($info['data_permission'])&&!$info['data_permission']?'checked':''}}>
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
            var id = $("input[name='id']").val();
            var index = parent.layer.getFrameIndex(window.name);
            if(id==0){
                form.on('submit(formDemo)', function(data) {
                    $.ajax({
                        url:"{{url('/admin/onAgent')}}",
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
            }else{
                form.on('submit(formDemo)', function(data) {
                    $.ajax({
                        url:"{{url('/admin/optionsUpdate')}}",
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
            }

        });
    </script>
@endsection
@extends('common.edit')