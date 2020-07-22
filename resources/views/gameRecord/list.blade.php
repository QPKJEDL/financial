@section('title', '会员列表')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#x1002;</i></button>
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="begin" name="begin" placeholder="日期" onclick="layui.laydate({elem: this, festival: true,min:'{{$min}}'})" value="{{ $input['begin'] or '' }}" autocomplete="off">
    </div>
    <div class="layui-inline">
        <select name="desk_id">
            <option value="">请选择台桌</option>
            @foreach($desk as $d)
                <option value="{{$d['id']}}" {{isset($input['desk_id'])&&$input['desk_id']==$d['id']?'selected':''}}>{{$d['desk_name']}}</option>
            @endforeach
        </select>
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="" value="" name="boot" placeholder="靴号" autocomplete="off" class="layui-input">
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
        </colgroup>
        <thead>
        <tr>
            <th class="hidden-xs">台桌</th>
            <th class="hidden-xs">靴号</th>
            <th class="hidden-xs">铺号</th>
            <th class="hidden-xs">时间</th>
            <th class="hidden-xs">结果</th>
            <th class="hidden-xs">修改前结果</th>
        </tr>
        </thead>
        <tbody>
        @foreach($list as $info)
            <tr>
                <td class="hidden-xs">{{$info['desk']['desk_name']}}</td>
                <td class="hidden-xs">{{$info['boot_num']}}</td>
                <td class="hidden-xs">{{$info['pave_num']}}</td>
                <td class="hidden-xs">{{$info['creatime']}}</td>
                <td class="hidden-xs">
                @if($info['type']==1)
                        {{$info['result']['game']}}&nbsp;{{$info['result']['playerPair']}} {{$info['result']['bankerPair']}}
                    @elseif($info['type']==2)
                        {{$info['result']}}
                    @elseif($info['type']==3)
                        @if($info['result']['bankernum']=="")
                            {{$info['result']['x1result']}}&nbsp;{{$info['result']['x2result']}}&nbsp;{{$info['result']['x3result']}}
                        @else
                            {{$info['result']['bankernum']}}
                        @endif
                    @elseif($info['type']==4)
                        @if($info['result']['bankernum']=="")
                            {{$info['result']['x1result']}}&nbsp;{{$info['result']['x2result']}}&nbsp;{{$info['result']['x3result']}}
                            {{$info['result']['x4result']}}&nbsp;{{$info['result']['x5result']}}&nbsp;{{$info['result']['x6result']}}
                        @else
                            {{$info['result']['bankernum']}}
                        @endif
                    @elseif($info['type']==5)
                        @if($info['result']['bankernum']=="")
                            {{$info['result']['Fanresult']}} {{$info['result']['Shunresult']}} {{$info['result']['Tianresult']}}
                        @else
                            {{$info['result']['bankernum']}}
                        @endif
                    @endif
                </td>
                <td class="hidden-xs">
                @if($info['type']==1)
                        {{$info['afterResult']['game']}}&nbsp;{{$info['afterResult']['playerPair']}} {{$info['afterResult']['bankerPair']}}
                    @elseif($info['type']==2)
                        {{$info['afterResult']}}
                    @elseif($info['type']==3)
                        @if($info['afterResult']['bankernum']=="")
                            {{$info['afterResult']['x1result']}}&nbsp;{{$info['afterResult']['x2result']}}&nbsp;{{$info['afterResult']['x3result']}}
                        @else
                            {{$info['afterResult']['bankernum']}}
                        @endif
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
                $("select[name='desk_id']").val(''); 
                $("input[name='boot']").val('');
            });
            form.render();
            form.on('submit(formDemo)', function(data) {
                console.log(data);
            });
        });
    </script>
@endsection
@extends('common.list')