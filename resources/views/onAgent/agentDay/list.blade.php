@section('title', '会员列表')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#x1002;</i></button>
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="begin" name="begin" placeholder="开始日期" onclick="layui.laydate({elem: this, festival: true,min:'{{$min}}'})" value="{{ $input['begin'] or '' }}" autocomplete="off">
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="end" name="end" placeholder="结束日期" onclick="layui.laydate({elem: this, festival: true,min:'{{$min}}'})" value="{{ $input['end'] or '' }}" autocomplete="off">
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="account" value="{{ $input['account'] or '' }}" name="account" placeholder="请输入代理账号" autocomplete="off" class="layui-input">
    </div>
    <div class="layui-inline">
        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="formDemo">搜索</button>
        <button class="layui-btn layui-btn-normal reset" lay-submit>重置</button>
    </div>
    <br>
    <div class="layui-btn-group">
        <button type="button" class="layui-btn" id="today">今天</button>
        <button type="button" class="layui-btn" id="yesterday">昨天</button>
        <button type="button" class="layui-btn" id="thisWeek">本周</button>
        <button type="button" class="layui-btn" id="lastWeek">上周</button>
        <button type="button" class="layui-btn" id="thisMonth">本月</button>
        <button type="button" class="layui-btn" id="lastMonth">上月</button>
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
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="100">
            <col class="hidden-xs" width="300">
        </colgroup>
        <thead>
        <tr>
            <th class="hidden-xs">台类型</th>
            <th class="hidden-xs">名称</th>
            <th class="hidden-xs">账号</th>
            <th class="hidden-xs">总押码</th>
            <th class="hidden-xs">总赢</th>
            <th class="hidden-xs">总洗码</th>
            <th class="hidden-xs">客损</th>
            <th class="hidden-xs">打赏金额</th>
            <th class="hidden-xs">抽水比例</th>
            <th class="hidden-xs">抽水收益</th>
            <th class="hidden-xs">占股</th>
            <th class="hidden-xs">占股收益</th>
            <th class="hidden-xs">总收益</th>
            <th class="hidden-xs">操作</th>
        </tr>
        </thead>
        <tbody>
        @foreach($list as $info)
            <tr>
                <td class="hidden-xs">全部</td>
                <td class="hidden-xs">{{$info['nickname']}}</td>
                <td class="hidden-xs">{{$info['username']}}</td>
                <td class="hidden-xs">{{number_format($info['sum_betMoney']/100,2)}}</td>
                <td class="hidden-xs">{{number_format($info['win_money']/100,2)}}</td>
                <td class="hidden-xs">{{number_format($info['code']/100,2)}}</td>
                <td class="hidden-xs">{{number_format(abs($info['kesun']/100),2)}}</td>
                <td class="hidden-xs">{{number_format($info['reward'][0]->money/100,2)}}</td>
                <td class="hidden-xs">{{$info['pump']}}%</td>
                <td class="hidden-xs">{{number_format($info['pumpMoney']/100,2)}}</td>
                <td class="hidden-xs">{{$info['proportion']}}%</td>
                <td class="hidden-xs">{{number_format($info['kesun']/100,2)}}</td>
                <td class="hidden-xs">
                    {{number_format($info['pumpMoney']/100 + $info['kesun']/100,2)}}
                </td>
                <td class="hidden-xs">
                    <div class="layui-inline">
                        <button type="button" class="layui-btn layui-btn-small agentDayInfo" data-id="{{$info['id']}}" data-name="{{$info['nickname']}}" data-desc="详情"><i class="layui-icon">代理日结</i></button>
                        <button type="button" class="layui-btn layui-btn-small userDayInfo" data-id="{{$info['id']}}" data-name="{{$info['nickname']}}" data-desc="详情"><i class="layui-icon">会员日结</i></button>
                    </div>
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
            //今天
            $("#today").click(function () {
                var startDate = new Date(new Date(new Date().toLocaleDateString()).getTime());
                var endDate = new Date(new Date(new Date().toLocaleDateString()).getTime() + 24 *60 *60*1000-1);
                $("input[name='begin']").val(formDate(startDate))
                $("input[name='end']").val(formDate(endDate))
            });
            //昨天
            $("#yesterday").click(function () {
                var startDate = new Date(new Date(new Date().toLocaleDateString()).getTime() - 24*60*60*1000);
                var endDate = new Date(new Date(new Date().toLocaleDateString()).getTime() - 24*60*60*1000 + 24*60*60*1000 -1);
                $("input[name='begin']").val(formDate(startDate))
                $("input[name='end']").val(formDate(endDate))
            });
            //本周
            $("#thisWeek").click(function () {
                var now = new Date();//当前日期
                var nowDayOfWeek = now.getDay();//今天本周的第几天
                var nowDay = now.getDate();//当前日
                var nowMonth = now.getMonth();//当前月
                var nowYear = now.getFullYear();//当前年
                var weekStartDate = new Date(nowYear, nowMonth, nowDay - nowDayOfWeek +1);
                $("input[name='begin']").val(formatDate(weekStartDate))
                $("input[name='end']").val(formatDate(now))
            });
            //本月
            $("#thisMonth").click(function () {
                var now = new Date();
                var nowYear = now.getFullYear();
                var nowMonth = now.getMonth();
                var monthStartDate = new Date(nowYear, nowMonth, 1);
                $("input[name='begin']").val(formatDate(monthStartDate))
                $("input[name='end']").val(formatDate(now))
            });
            $("#lastWeek").click(function () {
                var now = new Date();                 //当前日期
                var nowDayOfWeek = now.getDay();        //今天本周的第几天
                var nowDay = now.getDate();            //当前日
                var nowMonth = now.getMonth();         //当前月
                var nowYear = now.getFullYear();           //当前年
                var getUpWeekStartDate = new Date(nowYear, nowMonth, nowDay - nowDayOfWeek -7 + 1);
                var getUpWeekEndDate = new Date(nowYear, nowMonth, nowDay + (6 - nowDayOfWeek - 7) + 1);
                $("input[name='begin']").val(formatDate(getUpWeekStartDate))
                $("input[name='end']").val(formatDate(getUpWeekEndDate))
            });
            //上月
            $("#lastMonth").click(function () {
                var now = new Date();
                var nowYear = now.getFullYear();
                var lastMonthDate = new Date(); //上月日期
                lastMonthDate.setDate(1);
                lastMonthDate.setMonth(lastMonthDate.getMonth()-1);
                var lastMonth = lastMonthDate.getMonth();
                var lastMonthStartDate = new Date(nowYear, lastMonth, 1);
                var lastMonthEndDate = new Date(nowYear,lastMonth,getMonthDays(lastMonth));
                $("input[name='begin']").val(formatDate(lastMonthStartDate))
                $("input[name='end']").val(formatDate(lastMonthEndDate))
            });
            $(".agentDayInfo").click(function () {
                var id = $(this).attr('data-id');
                var name = $(this).attr('data-name');
                var begin = $("input[name='begin']").val();
                var end = $("input[name='end']").val();
                var index = layer.open({
                    type:2,
                    title:name+'的下级代理',
                    shadeClose:true,
                    offset:'10%',
                    area:['60%','80%'],
                    content:'/admin/onAgentDayEnd/'+id + '/' + begin + '/' + end
                });
                layer.full(index)
            });
            $(".userDayInfo").click(function () {
                var id = $(this).attr('data-id');
                var name = $(this).attr('data-name');
                var begin = $("input[name='begin']").val();
                var end = $("input[name='end']").val();
                var index = layer.open({
                    type:2,
                    title:name+'的下级会员',
                    shadeClose:true,
                    offset:'10%',
                    area:['60%','80%'],
                    content:'/admin/onUserDayEnd/'+id + '/' + begin + '/' + end
                });
                layer.full(index)
            });
            form.render();
            form.on('submit(formDemo)', function(data) {
                console.log(data);
            });
            //获得某月的天数 （与上面有重复可删除，不然本月结束日期报错）
            function getMonthDays(nowyear){
                var lastMonthDate = new Date(); //上月日期
                lastMonthDate.setDate(1);
                lastMonthDate.setMonth(lastMonthDate.getMonth()-1);
                var lastYear = lastMonthDate.getFullYear();
                var lastMonth = lastMonthDate.getMonth();
                var lastMonthStartDate = new Date(nowyear, lastMonth, 1);
                var lastMonthEndDate= new Date(nowyear, lastMonth+ 1, 1);
                var days = (lastMonthEndDate- lastMonthStartDate) / (1000 * 60 * 60 * 24);//格式转换
                return days
            }
            //格式化日期 yyyy-mm-dd HH:mm:ss
            function formDate(date) {
                var date = new Date(date);
                var y = date.getFullYear();
                var m = date.getMonth() + 1;
                m = m < 10 ? ('0' + m) : m;
                var d = date.getDate();
                d = d < 10 ? ('0' + d) : d;
                var h = date.getHours();
                h = h < 10 ? ('0' + h) : h;
                var minute = date.getMinutes();
                var second = date.getSeconds();
                minute = minute < 10 ? ('0' + minute) : minute;
                second = second < 10 ? ('0' + second) : second;
                return y + '-' + m + '-' + d + ' ' + h + ':' + minute + ':' + second;
            }
            //格式化日期：yyyy-MM-dd
            function formatDate(date) {
                var myyear = date.getFullYear();
                var mymonth = date.getMonth()+1;
                var myweekday = date.getDate();

                if(mymonth < 10){
                    mymonth = "0" + mymonth;
                }
                if(myweekday < 10){
                    myweekday = "0" + myweekday;
                }
                return (myyear+"-"+mymonth + "-" + myweekday);
            }
        });
    </script>
@endsection
@extends('common.list')