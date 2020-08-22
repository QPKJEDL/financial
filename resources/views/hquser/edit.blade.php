@section('title', '会员账号编辑')
@section('content')
    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">昵称：</label>
            <div class="layui-input-block">
              <input type="text" name="nickname" value="{{$info['nickname']}}" lay-verify="required" value="" autocomplete="off" class="layui-input">
            </div>
          </div>
          <div class="layui-inline">
            <label class="layui-form-label">日赢上限：</label>
            <div class="layui-input-block">
              <input type="text" name="" lay-verify="" value="{{$info[''] or 0}}" autocomplete="off" class="layui-input">
            </div>
          </div>
    </div>
    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">百家乐洗码率：</label>
            <div class="layui-input-block">
              <input type="text" name="" lay-verify="required" readonly disabled value="{{$info['fee']['baccarat']}}" style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" autocomplete="off" class="layui-input">
            </div>
          </div>
          <div class="layui-inline">
            <label class="layui-form-label">龙虎洗码率：</label>
            <div class="layui-input-block">
              <input type="text" name="" lay-verify="" autocomplete="off" value="{{$info['fee']['dragonTiger']}}" readonly style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" disabled class="layui-input">
            </div>
          </div>
    </div>
    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">牛牛洗码率：</label>
            <div class="layui-input-block">
              <input type="text" name="" lay-verify="required" value="{{$info['fee']['niuniu']}}" autocomplete="off" readonly style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" disabled class="layui-input">
            </div>
        </div>
        <div class="layui-inline">
            <label class="layui-form-label">三公洗码率：</label>
            <div class="layui-input-block">
              <input type="text" name="" lay-verify="required" value="{{$info['fee']['sangong']}}" autocomplete="off" class="layui-input" readonly style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" disabled>
            </div>
        </div>
    </div>
    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">A89洗码率：</label>
            <div class="layui-input-block">
              <input type="text" name="" lay-verify="required" value="{{$info['fee']['A89']}}" autocomplete="off" class="layui-input" readonly style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" disabled>
            </div>
        </div>
    </div>
    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">最小限红：</label>
            <div class="layui-input-block">
              <input type="number" name="" lay-verify="required" value="10" autocomplete="off" readonly style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" disabled class="layui-input">
            </div>
        </div>
        <div class="layui-inline">
            <label class="layui-form-label">最大限红：</label>
            <div class="layui-input-block">
              <input type="number" name="" lay-verify="required" value="50000" autocomplete="off" class="layui-input" readonly style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" disabled>
            </div>
        </div>
    </div>
    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">最小和限红：</label>
            <div class="layui-input-block">
              <input type="numberv" name="" lay-verify="required" value="10" autocomplete="off" readonly style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" disabled class="layui-input">
            </div>
        </div>
        <div class="layui-inline">
            <label class="layui-form-label">最大和限红：</label>
            <div class="layui-input-block">
              <input type="number" name="" lay-verify="required" value="5000" autocomplete="off" class="layui-input" readonly style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" disabled>
            </div>
        </div>
    </div>
    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">最小对限红：</label>
            <div class="layui-input-block">
              <input type="numberv" name="" lay-verify="required" value="10" autocomplete="off" readonly disabled style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" class="layui-input">
            </div>
        </div>
        <div class="layui-inline">
            <label class="layui-form-label">最大对限红：</label>
            <div class="layui-input-block">
              <input type="number" name="" lay-verify="required" value="5000" autocomplete="off" class="layui-input" readonly disabled style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" disabled>
            </div>
        </div>
    </div>
    <fieldset class="layui-elem-field layui-field-title" style="margin-top: 50px;">
        <legend>百家乐游戏赔率</legend>
    </fieldset>
    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">闲：</label>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="bjlbets_fee[player]" readonly disabled style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" placeholder="" value="{{$info['bjlbets_fee']['player']/100}}" autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">闲对：</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="bjlbets_fee[playerPair]" readonly disabled style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" placeholder="" value="{{$info['bjlbets_fee']['playerPair']/100}}"  autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">和：</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="bjlbets_fee[tie]" placeholder="" readonly disabled style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" value="{{$info['bjlbets_fee']['tie']/100}}"  autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">庄：</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="bjlbets_fee[banker]" placeholder="" readonly disabled style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" value="{{$info['bjlbets_fee']['banker']/100}}"  autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">庄对：</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input type="number" name="bjlbets_fee[bankerPair]" readonly disabled style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" placeholder="" value="{{$info['bjlbets_fee']['bankerPair']/100}}"  autocomplete="off" class="layui-input">
            </div>
        </div>
    </div>
    <fieldset class="layui-elem-field layui-field-title" style="margin-top: 50px;">
        <legend>龙虎游戏赔率</legend>
    </fieldset>
    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">龙：</label>
            <div class="layui-input-inline" style="width: 100px;">
                <input readonly disabled style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" type="number" name="lhbets_fee[dragon]" placeholder="" value="{{$info['lhbets_fee']['dragon']/100}}"autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">和：</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input readonly disabled style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" type="number" name="lhbets_fee[tie]" placeholder="" value="{{$info['lhbets_fee']['tie']/100}}"  autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">虎：</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input readonly disabled style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" type="number" name="lhbets_fee[tiger]" placeholder="" value="{{$info['lhbets_fee']['tiger']/100}}"  autocomplete="off" class="layui-input">
            </div>
        </div>
    </div>
    <fieldset class="layui-elem-field layui-field-title" style="margin-top: 50px;">
        <legend>牛牛游戏赔率</legend>
    </fieldset>
    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">平倍：</label>
            <div class="layui-input-inline" style="width: 100px;">
                <input readonly disabled style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" type="number" name="nnbets_fee[Equal]" placeholder="" value="{{$info['nnbets_fee']['Equal']/100}}"autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">翻倍：</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input readonly disabled style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" type="number" name="nnbets_fee[Double]" placeholder="" value="{{$info['nnbets_fee']['Double']/100}}"  autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">超倍：</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input readonly disabled style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" type="number" name="nnbets_fee[SuperDouble]" placeholder="" value="{{$info['nnbets_fee']['SuperDouble']/100}}"  autocomplete="off" class="layui-input">
            </div>
        </div>
    </div>
    <fieldset class="layui-elem-field layui-field-title" style="margin-top: 50px;">
        <legend>三公游戏赔率</legend>
    </fieldset>
    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">平倍：</label>
            <div class="layui-input-inline" style="width: 100px;">
                <input readonly disabled type="number" name="sgbets_fee[Equal]" style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" placeholder="" value="{{$info['sgbets_fee']['Equal']/100}}"autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">翻倍：</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input readonly disabled type="number" name="sgbets_fee[Double]" placeholder="" value="{{$info['sgbets_fee']['Double']/100}}" style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">超倍：</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input readonly disabled style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" type="number" name="sgbets_fee[SuperDouble]" placeholder="" value="{{$info['sgbets_fee']['SuperDouble']/100}}"  autocomplete="off" class="layui-input">
            </div>
        </div>
    </div>
    <fieldset class="layui-elem-field layui-field-title" style="margin-top: 50px;">
        <legend>A89游戏赔率</legend>
    </fieldset>
    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">平倍：</label>
            <div class="layui-input-inline" style="width: 100px;">
                <input readonly disabled type="number" name="a89bets_fee[Equal]" style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" placeholder="" value="{{$info['a89bets_fee']['Equal']/100}}"autocomplete="off" class="layui-input">
            </div>
            <div class="layui-form-mid">超倍：</div>
            <div class="layui-input-inline" style="width: 100px;">
                <input readonly disabled style="border: 1px solid #DDD;background-color: #F5F5F5;color: #ACA899;" type="number" name="a89bets_fee[SuperDouble]" placeholder="" value="{{$info['a89bets_fee']['SuperDouble']/100}}"  autocomplete="off" class="layui-input">
            </div>
        </div>
    </div>
    {{--<div class="layui-form-item">
        <div class="layui-inline">
            <div class="layui-input-block">
                <input type="checkbox" name="is_show" title="报表中显示洗码量">
            </div>
        </div>
    </div>--}}
@endsection
@section('id',$id)
@section('js')
    <script>
        layui.use(['form','jquery','layer'], function() {
            var form = layui.form
                ,layer = layui.layer
                ,$ = layui.jquery;
            form.render();
            form.verify({
                
            });
            form.on('submit(formDemo)', function(data) {
                $.ajax({
                    url:"{{url('/admin/hquser/save')}}",
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