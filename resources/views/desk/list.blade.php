@section('title', '台桌输赢情况')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#x1002;</i></button>
    </div>
    <div class="layui-inline">
    <input class="layui-input" lay-verify="begin" name="begin" placeholder="日期" onclick="layui.laydate({elem: this, festival: true,min:'{{$min}}'})" value="{{ $input['begin'] or '' }}" autocomplete="off">
    </div>
    <div class="layui-inline">
        <div class="layui-inline">
            <select name="deskId">
                <option value="">请选择台桌</option>
                @foreach($desk as $d)
                    <option value="{{$d['id']}}" {{isset($input['deskId'])&&$input['deskId']==$d['id']?'selected':''}}>{{$d['desk_name']}}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="boot_num" onkeyup="if(this.value.length==1){this.value=this.value.replace(/[^1-9]/g,'')}else{this.value=this.value.replace(/\D/g,'')}" value="{{$input['boot_num'] or ''}}" name="boot_num" placeholder="靴号(不填就是0)" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="formDemo">搜索</button>
        <button class="layui-btn layui-btn-normal reset" lay-submit>重置</button>
    </div>
@endsection
@section('table')
    <table class="layui-table" lay-even lay-skin="nob">
        <colgroup>
        <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
        </colgroup>
        <thead>
        <tr>
            <th class="hidden-xs">日期</th>
            <th class="hidden-xs">台桌类型</th>
            <th class="hidden-xs">台桌名称</th>
            <th class="hidden-xs">靴号</th>
            <th class="hidden-xs">总下注</th>
            <th class="hidden-xs">下注输赢</th>
            <th class="hidden-xs">洗码费</th>
            <th class="hidden-xs">公司总赢</th>
        </tr>
        </thead>
        <tbody>
        @foreach($list as $info)
            <tr>
                <td class="hidden-xs">{{$info['time']}}</td>
                <td class="hidden-xs">{{$info['game_id']}}</td>
                <td class="hidden-xs">{{$info['desk_name']}}</td>
                <td class="hidden-xs">{{$input['boot_num'] or 0}}</td>
                <td class="hidden-xs">{{number_format($info['betMoney']/100,2)}}</td>
                <td class="hidden-xs">{{number_format($info['winAndErr']/100,2)}}</td>
                <td class="hidden-xs">{{number_format(($info['money']/100) * 0.009,2)}}</td>
                <td class="hidden-xs">
                    @if($info['winAndErr']/100>0)
                    {{number_format(($info['winAndErr']/100) -(($info['money']/100) * 0.009),2)}}
                    @else
                    {{number_format(abs(($info['winAndErr']/100) -(($info['money']/100) * 0.009)),2)}}
                    @endif
                </td>
            </tr>
        @endforeach
        @if(!$list[0])
            <tr><td colspan="9" style="text-align: center;color: orangered;">暂无数据</td></tr>
        @endif
        </tbody>
    </table>
    <div class="page-wrap">
        {{$list->render()}}
    </div>
@endsection
@section('js')
    <script>
        layui.use(['form', 'jquery','laydate', 'layer'], function() {
            var form = layui.form(),
                $ = layui.jquery,
                laydate = layui.laydate,
                layer = layui.layer
            ;
            laydate({istoday: true});
            $(".reset").click(function(){
                $("input[name='begin']").val('');
                $("select[name='deskId']").val('');
            });
            form.render();
            form.verify({
                begin:function(value){
                    var begin = Date.parse(new Date(value));
                    //获取当前时间戳
                    var nowTime = (new Date()).getTime();
                    if(begin>nowTime){
                        return "选择的日期不能大于今天的日期";
                    }
                }
            });
            form.on('submit(formDemo)', function(data) {
            });
        });
    </script>
@endsection
@extends('common.list')