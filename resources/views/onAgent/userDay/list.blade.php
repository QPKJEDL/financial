@section('title', '会员列表')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#xe9aa;</i></button>
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="begin" name="begin" id="begin" placeholder="开始日期" value="{{ $input['begin'] or '' }}" autocomplete="off">
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="end" name="end" placeholder="结束日期" value="{{ $input['end'] or '' }}" autocomplete="off">
    </div>
    {{--@if($input['type']==1)--}}
    <div class="layui-inline">
        <input type="text" lay-verify="account" value="{{ $input['account'] or '' }}" name="account" placeholder="会员账号" autocomplete="off" class="layui-input">
    </div>
    {{--@endif--}}
    <div class="layui-inline">
        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="formDemo" value="submit" name="submit">搜索</button>
        <button class="layui-btn layui-btn-normal reset" lay-submit>重置</button>
        <button class="layui-btn layui-btn-normal reset" lay-submit>导出EXCEL</button>
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
    <table class="layui-table" lay-size="sm">
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
            <th class="hidden-xs">台类型</th>
            <th class="hidden-xs">名称</th>
            <th class="hidden-xs">账号</th>
            <th class="hidden-xs">当前金额</th>
            <th class="hidden-xs">下注次数</th>
            <th class="hidden-xs">下注总额</th>
            <th class="hidden-xs">总洗码</th>
            <th class="hidden-xs">派彩所赢</th>
            <th class="hidden-xs">打赏金额</th>
            {{--@if($input['type']==1)--}}
            <th class="hidden-xs">操作</th>
            {{--@endif--}}
        </tr>
        </thead>
        <tbody>
        @foreach($list as $info)
            <tr>
                <td class="hidden-xs">全部</td>
                <td class="hidden-xs">{{$info->nickname}}</td>
                <td class="hidden-xs">{{$info->account}}</td>
                <td class="hidden-xs">{{$info->balance/100}}</td>
                <td class="hidden-xs">{{$info->count}}</td>
                <td class="hidden-xs">{{$info->betMoney/100}}</td>
                <td class="hidden-xs">{{$info->code/100}}</td>
                <td class="hidden-xs">{{$info->getMoney/100}}</td>
                <td class="hidden-xs">{{$info->reward/100}}</td>
                <td class="hidden-xs">
                    <div class="layui-inline">
                        <button class="layui-btn layui-btn-small dayInfo" data-id="{{$info->user_id}}" data-name="{{$info->nickname}}" data-desc="详情"><i class="layui-icon">详情</i></button>
                    </div>
                </td>
            </tr>
        @endforeach
        @if(count($list)==0)
            <tr><td colspan="10" style="text-align: center;color: orangered;">暂无数据</td></tr>
        @endif
        </tbody>
    </table>
    <div class="page-wrap">
        <div id="demo1"></div>
    </div>
@endsection
@section('js')
    <script>
        layui.use(['form', 'jquery','laydate', 'layer','laypage'], function() {
            var form = layui.form,
                $ = layui.jquery,
                laydate = layui.laydate,
                layer = layui.layer,
                laypage = layui.laypage
            ;
            laydate.render({
                elem:"#begin"
            });
            laydate.render({
                elem:"#end"
            });
            var pages={{$pages}};
            var curr = {{$curr}};
            var url = "";
            laypage({
                cont: 'demo1'
                ,pages: pages //总页数
                ,curr:curr
                ,groups: 5 //连续显示分页数
                ,jump:function (obj,first) {
                    if(url.indexOf("?") >= 0){
                        url = url.split("?")[0] + "?pageNum=" + obj.curr + "&" + $("form").serialize();
                    }else{
                        url = url + "?pageNum=" + obj.curr;
                    }
                    if (!first){
                        location.href = url;
                    }
                }
            });
            $(".reset").click(function(){
                $("input[name='begin']").val('');
                $("input[name='end']").val('');
                $("input[name='account']").val('');
            });
            $(".dayInfo").click(function () {
                var id = $(this).attr('data-id');
                var name = $(this).attr('data-name');
                var begin = $("input[name='begin']").val();
                if(begin==null || begin==""){
                    begin = "1";
                }
                var end = $("input[name='end']").val();
                if(end==null || end == ""){
                    end = '1';
                }
                var index = layer.open({
                    type:2,
                    title:name+'下注详情',
                    shadeClose:true,
                    offset:'10%',
                    area:['60%','80%'],
                    content:'/admin/onUserOrderList/' + id + '/' + begin + '/' + end
                });
                layer.full(index);
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
            form.render();
            form.on('submit(formDemo)', function(data) {
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