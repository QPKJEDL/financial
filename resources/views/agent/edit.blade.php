@section('title', '用户编辑')
@section('content')
    @if($id==0)
        <div class="layui-form-item">
            <label class="layui-form-label">账号：</label>
            <div class="layui-input-inline">
                <input type="text" name="username" lay-verify="username" lay autocomplete="off" class="layui-input">
            </div>
            <div class="layui-input-inline">
                <button type="button" class="layui-btn" id="account">系统生成</button>
            </div>
        </div>
    @else
        <div class="layui-form-item">
            <label class="layui-form-label">账号：</label>
            <div class="layui-input-block">
                <input type="text" value="{{$info['username'] or ''}}" name="username" required lay-verify="user_name" placeholder="请输入用户名" autocomplete="off" class="layui-input" @if($id!=0) readonly @endif>
            </div>
        </div>
    @endif
    <div class="layui-form-item">
        <label class="layui-form-label">名称：</label>
        <div class="layui-input-block">
            <input type="text" value="{{$info['nickname'] or ''}}" name="nickname" required lay-verify="required" placeholder="请输入名称" autocomplete="off" class="layui-input">
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">状态：</label>
        <div class="layui-input-block">
            @if($id==0)
                <input type="radio" name="status" value="0" title="正常" checked="checked"/>
                <input type="radio" name="status" value="1" title="停用">
            @else
                <input type="radio" name="status" value="0" title="正常" @if($info['status']==0) checked="checked"@endif/>
                <input type="radio" name="status" value="1" title="停用"@if($info['status']==1) checked="checked"@endif>
            @endif
        </div>
    </div>
    @if($info==null)
        <div class="layui-form-item">
            <label class="layui-form-label">密码：</label>
            <div class="layui-input-block">
                <input type="password" name="pwd" lay-verify="pwd" placeholder="请输入密码" autocomplete="off" class="layui-input">
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
            <label class="layui-form-label">占比：</label>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" @if($id!=0) disabled readonly style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" @endif name="proportion" lay-verify="proportion" value="{{$info['proportion'] or ''}}"  placeholder="%" autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid layui-word-aux">比如20%就填写20</div>
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">IP白名单：</label>
        <div class="layui-input-block">
            <textarea placeholder="请填写IP白名单（非必填）" name="ip_config" class="layui-textarea" style="resize: none">{{$info['ip_config'] or ''}}</textarea>
        </div>
    </div>

    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">最小限红</label>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="limit[min]" lay-verify="minLimit" @if($info!=null) readonly style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" @endif value="{{$info['limit']['min'] or '10'}}"  placeholder="￥" autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">最大限红</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="limit[max]" lay-verify="maxLimit" @if($info!=null) readonly style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" @endif value="{{$info['limit']['max'] or '50000'}}" placeholder="￥" autocomplete="off" class="layui-input">
            </div>
        </div>
    </div>
    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">最小和限红</label>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="limit[tieMin]" lay-verify="minPairLimit" @if($info!=null) readonly style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" @endif value="{{$info['limit']['tieMin'] or '10'}}" placeholder="￥" autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">最大和限红</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="limit[tieMax]" lay-verify="maxPairLimit" @if($info!=null) readonly style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" @endif value="{{$info['limit']['tieMax'] or '5000'}}" placeholder="￥" autocomplete="off" class="layui-input">
            </div>
        </div>
    </div>
    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">最小对限红</label>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="limit[pairMin]" lay-verify="minTieLimit" @if($info!=null) readonly style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" @endif value="{{$info['limit']['pairMin'] or '10'}}"  placeholder="￥" autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">最大对限红</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="limit[pairMax]" lay-verify="maxTieLimit" @if($info!=null) readonly style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" @endif value="{{$info['limit']['pairMax'] or '5000'}}"  placeholder="￥" autocomplete="off" class="layui-input">
            </div>
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">百家乐洗码率：</label>
        <div class="layui-input-block">
            <input type="number" name="fee[baccarat]" value="0.9" required lay-verify="required" placeholder="请输入百家乐洗码率" autocomplete="off" class="layui-input">
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">龙虎洗码率：</label>
        <div class="layui-input-block">
            <input type="number" name="fee[dragonTiger]" value="0.9" required lay-verify="required" placeholder="请输入龙虎洗码率" autocomplete="off" class="layui-input">
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">牛牛洗码率：</label>
        <div class="layui-input-block">
            <input type="number" name="fee[niuniu]" value="0.9" required lay-verify="required" placeholder="请输入牛牛洗码率" autocomplete="off" class="layui-input">
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">三公洗码率：</label>
        <div class="layui-input-block">
            <input type="number" name="fee[sangong]" value="0.9" required lay-verify="required" placeholder="请输入三公洗码率" autocomplete="off" class="layui-input">
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">A89洗码率：</label>
        <div class="layui-input-block">
            <input type="number" name="fee[A89]" value="0.9" required lay-verify="required" placeholder="请输入A89洗码率" autocomplete="off" class="layui-input">
        </div>
    </div>
    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label" style="width: 100px;">百家乐：庄赔率</label>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="bjlbets_fee[banker]" lay-verify="minTieLimit" value="0.95"  autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">庄对赔率</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="bjlbets_fee[bankerPair]" lay-verify="maxTieLimit" value="11"  autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">闲赔率</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="bjlbets_fee[player]" lay-verify="maxTieLimit" value="1"  autocomplete="off" class="layui-input">
            </div>
        </div>
        <div class="layui-inline">
            <div class="layui-form-mid" style="padding-left: 64px">闲对赔率</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="bjlbets_fee[playerPair]" lay-verify="maxTieLimit" value="11"  autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid" style="padding-left: 14px">和赔率</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="bjlbets_fee[tie]" lay-verify="maxTieLimit" value="8" autocomplete="off" class="layui-input">
            </div>
        </div>
    </div>

    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label" style="width: 100px;">龙虎：龙赔率</label>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="lhbets_fee[dragon]" lay-verify="minTieLimit" value="0.97"  autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">虎赔率</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="lhbets_fee[tiger]" lay-verify="maxTieLimit" value="0.97"  autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">和赔率</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="lhbets_fee[tie]" lay-verify="maxTieLimit" value="8"  autocomplete="off" class="layui-input">
            </div>
        </div>
    </div>

    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label" style="width: 100px;">牛牛：平倍赔率</label>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="nnbets_fee[Equal]" lay-verify="minTieLimit" value="0.97"  autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">翻倍赔率</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="nnbets_fee[Double]" lay-verify="maxTieLimit" value="0.97"  autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">超倍赔率</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="nnbets_fee[SuperDouble]" lay-verify="maxTieLimit" value="0.97"  autocomplete="off" class="layui-input">
            </div>
        </div>
    </div>

    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label" style="width: 100px;">A89：平倍赔率</label>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="a89bets_fee[Equal]" lay-verify="minTieLimit" value="0.97"  autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">超倍赔率</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="a89bets_fee[SuperDouble]" lay-verify="maxTieLimit" value="0.97"  autocomplete="off" class="layui-input">
            </div>
        </div>
    </div>

    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label" style="width: 100px;">三公：平倍赔率</label>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="sgbets_fee[Equal]" lay-verify="minTieLimit" value="0.97"  autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">翻倍赔率</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="sgbets_fee[Double]" lay-verify="maxTieLimit" value="0.97"  autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">超倍赔率</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="sgbets_fee[SuperDouble]" lay-verify="maxTieLimit" value="0.97"  autocomplete="off" class="layui-input">
            </div>
        </div>
    </div>
    @if($info!=null)
        <div class="layui-form-item">
            <label class="layui-form-label">抽水权限：</label>
            <div class="layui-input-block">
                <input type="checkbox" id="baccarat" name="baccarat" title="百家乐" {{isset($info['baccarat'])&&$info['baccarat']==1?'checked':''}}>
                <br>
                <span style="color: red">勾选后不可取消</span>
            </div>
        </div>
    @else
        <div class="layui-form-item">
            <label class="layui-form-label">抽水权限：</label>
            <div class="layui-input-block">
                <input type="checkbox" name="baccarat" id="baccarat" title="百家乐">
                <br>
                <span style="color: red">勾选后不可取消</span>
            </div>
        </div>
    @endif
    <div class="layui-form-item">
        <div class="layui-input-block">
            <input type="checkbox" name="is_allow" title="允许其直属会员在线充值" @if($info!=null) @if($info['is_allow']==1) checked="checked" @endif @endif>
            <input type="checkbox" name="is_allow_draw" title="允许其直属会员在线提现" @if($info!=null) @if($info['is_allow_draw']==1) checked="checked" @endif @endif>
            <input type="checkbox" name="is_allow_password" title="限制代理提现和修改密码" @if($info!=null) @if($info['is_allow_password']!=1) checked="checked" @endif @endif>
            <input type="checkbox" name="is_realTime" title="注单实时查询" @if($info!=null) @if($info['is_realTime']==1) checked="checked" @endif @endif>
        </div>
    </div>
@endsection
@section('id',$id)
@section('js')
    <script>
        window.onload=function(){
            var id = $("input[name='id']").val();
            if (id!=0){
                var baccarat = document.getElementById("baccarat");
                if (baccarat.checked) {
                    baccarat.setAttribute("disabled", "");
                }
            }
        }

        layui.use(['form','jquery','laypage', 'layer'], function() {
            var form = layui.form,
                $ = layui.jquery;
            form.render();
            var layer = layui.layer;
            form.verify({
                user_name: function (value) {
                    if(value.length<5){
                        return '长度不能小于5';
                    }
                    if(value.length>12){
                        return '长度不能大于10';
                    }
                },
                pwd:function(value){
                    if(value.length<6 || value.length==0){
                        return '密码不能为空，限制长度最低六位';
                    }
                },
                pwd_confirmation: function(value) {
                    if($("input[name='pwd']").val() && $("input[name='pwd']").val() != value) {
                        return '两次输入密码不一致';
                    }
                },
                proportion:function (value) {
                    if (value<0){
                        return '占比不能小于0'
                    }
                }
            });
            $("input[name='username']").blur(function () {
                var account = $(this).val();
                if(account.length==0){
                    layer.msg('账号不能为空',{shift: 6,icon:5});
                }else{
                    $.ajax({
                        headers:{
                            'X-CSRF-TOKEN':$('input[name="_token"]').val()
                        },
                        url:"{{url('/admin/agent/accountUnique')}}",
                        type:"post",
                        data:{
                            "account":account
                        },
                        dataType:"json",
                        success:function (res) {
                            if(res.status==0){
                                layer.msg('账号已存在',{shift:6,icon:5});
                            }
                        }
                    });
                }
            });
            $("#account").click(function(){
                //console.log(Math.random().toString().slice(-6));
                //清空数据
                $("input[name='username']").val('');
                $("input[name='username']").val(Math.floor(Math.random() * (999999-100000)) + 100000);
            });
            var id = $("input[name='id']").val();
            if(id==0){
                form.on('submit(formDemo)', function() {
                    var data = $('form').serializeArray();
                    //获取dom元素
                    //百家乐
                    var baccarat = document.getElementById('baccarat');
                    if (baccarat.checked){
                        data.push({"name":"baccarat","value":"1"});
                    }else{
                        data.push({"name":"baccarat","value":"0"});
                    }
                    $.ajax({
                        url:"{{url('/admin/agent')}}",
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
                    return false;
                });
            }else{
                form.on('submit(formDemo)', function() {
                    var data = $('form').serializeArray();
                    //获取dom元素
                    //百家乐
                    var baccarat = document.getElementById('baccarat');
                    if (baccarat.checked){
                        data.push({"name":"baccarat","value":"1"});
                    }else{
                        data.push({"name":"baccarat","value":"0"});
                    }
                    $.ajax({
                        url:"{{url('/admin/agentUpdate')}}",
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
                    return false;
                });
            }

        });
    </script>
@endsection
@extends('common.edit')