@section('title', '会员列表')
@section('header')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-small layui-btn-warm freshBtn"><i class="layui-icon">&#xe9aa;</i></button>
        <button class="layui-btn layui-btn-normal reset" lay-submit>重置</button>
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="begin" name="begin" placeholder="开始日期" id="begin" value="{{ $input['begin'] or '' }}" autocomplete="off">
    </div>
    <div class="layui-inline">
        <input class="layui-input" lay-verify="end" name="end" placeholder="结束日期" id="end" value="{{ $input['end'] or '' }}" autocomplete="off">
    </div>
    <div class="layui-inline">
        <input type="text" lay-verify="account" value="{{ $input['account'] or '' }}" name="account" placeholder="请输入代理账号" autocomplete="off" class="layui-input">
    </div>
    @if($input['type']==1)
    <div class="layui-inline">
        <select name="userType">
            <option value="">请选择账号类型</option>
            <option value="1" {{isset($input['userType'])&&$input['userType']==1?'selected':''}}>线下</option>
            <option value="2" {{isset($input['userType'])&&$input['userType']==2?'selected':''}}>线上</option>
        </select>
    </div>
    @endif
    <div class="layui-inline">
        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="formDemo">搜索</button>
        <button class="layui-btn layui-btn-normal" lay-submit name="excel" value="excel">导出EXCEL</button>
    </div>
    <br>
    <div class="layui-btn-group">
        <button class="layui-btn" id="today" lay-submit>今天</button>
        <button class="layui-btn" id="yesterday" lay-submit>昨天</button>
        <button class="layui-btn" id="thisWeek" lay-submit>本周</button>
        <button class="layui-btn" id="lastWeek" lay-submit>上周</button>
        <button class="layui-btn" id="thisMonth" lay-submit>本月</button>
        <button class="layui-btn" id="lastMonth" lay-submit>上月</button>
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
            <th class="hidden-xs">总抽水</th>
            <th class="hidden-xs">打赏金额</th>
            <th class="hidden-xs">百/龙/牛/三/A</th>
            <th class="hidden-xs">洗码费</th>
            <th class="hidden-xs">抽水比例</th>
            <th class="hidden-xs">抽水收益</th>
            <th class="hidden-xs">占股</th>
            <th class="hidden-xs">占股收益</th>
            <th class="hidden-xs">总收益</th>
            <th class="hidden-xs">公司收益</th>
            <th class="hidden-xs">操作</th>
        </tr>
        </thead>
        <tbody>
            @if($input['type']==1)
                @if($input['account']=='' || $input['account']==null)
                    <tr>
                        <td class="hidden-xs">全部</td>
                        <td class="hidden-xs">总公司</td>
                        <td class="hidden-xs">admin</td>
                        <td class="hidden-xs">{{number_format($sum['washMoney']/100,2)}}</td>
                        <td class="hidden-xs">
                            @if($sum['getMoney']<0)
                                {{number_format(-$sum['getMoney']/100,2)}}
                            @else
                                <span style="color: red;">{{number_format(-$sum['getMoney']/100,2)}}</span>
                            @endif
                        </td>
                        <td class="hidden-xs">
                                <span style="color: red;">
                                    {{number_format(-$sum['betMoney']/100,2)}}
                                </span>
                        </td>
                        <td class="hidden-xs">
                            <span style="color: red;">{{number_format($sum['feeMoney']/100,2)}}</span>
                           {{-- @if($sum['feeMoney']<0)
                                <span style="color: red;">{{number_format($sum['feeMoney']/100,2)}}</span>
                            @else
                                {{number_format($sum['feeMoney']/100,2)}}
                            @endif--}}
                        </td>
                        <td class="hidden-xs">{{number_format($sum['reward']/100,2)}}</td>
                        <td class="hidden-xs">0.9/0.9/0.9/0.9/0.9</td>
                        <td class="hidden-xs">
                            <span style="color: red;">{{number_format(-($sum['code']/100),2)}}</span>
                        </td>
                        <td class="hidden-xs">100%</td>
                        <td class="hidden-xs">
                            <span style="color: red;">{{number_format(-$sum['pumpSy']/100,2)}}</span>
                        </td>
                        <td class="hidden-xs">100%</td>
                        <td class="hidden-xs">
                            @if($sum['zg']<0)
                                <span style="color: red;">{{number_format($sum['zg']/100,2)}}</span>
                            @else
                                {{number_format($sum['zg']/100,2)}}
                            @endif
                        </td>
                        <td class="hidden-xs">
                            @if($sum['sy']<0)
                                <span style="color: red;">{{number_format($sum['sy']/100,2)}}</span>
                            @else
                                {{number_format($sum['sy']/100,2)}}
                            @endif
                        </td>
                        <td class="hidden-xs">
                            @if($sum['gs']<0)
                                <span style="color: red;">{{number_format($sum['gs']/100,2)}}</span>
                            @else
                                {{number_format($sum['gs']/100,2)}}
                            @endif
                        </td>
                        <td class="hidden-xs"></td>
                    </tr>
                @endif
            @endif
        @foreach($list as $info)
            <tr>
                <td class="hidden-xs">全部</td>
                <td class="hidden-xs">{{$info['nickname']}}</td>
                <td class="hidden-xs">{{$info['username']}}</td>
                <td class="hidden-xs">{{number_format($info['washMoney']/100,2)}}</td>
                <td class="hidden-xs">
                    @if($info['getMoney']<0)
                        <span style="color: red;">{{number_format($info['getMoney']/100,2)}}</span>
                    @else
                        {{number_format($info['getMoney']/100,2)}}
                    @endif
                </td>
                <td class="hidden-xs">
                    @if($info['betMoney']<0)
                        <span style="color: red;">{{number_format($info['betMoney']/100,2)}}</span>
                    @else
                        {{number_format($info['betMoney']/100,2)}}
                    @endif
                </td>
                <td class="hidden-xs">
                    @if($info['userType']==1)
                        @if($info['feeMoney']<0)
                            <span style="color: red;">{{number_format($info['feeMoney']/100,2)}}</span>
                        @else
                            {{number_format($info['feeMoney']/100,2)}}
                        @endif
                    @else
                        -
                    @endif
                </td>
                <td class="hidden-xs">{{number_format($info['reward']/100,2)}}</td>
                <td class="hidden-xs">
                    @if($info['userType']==1)
                        {{$info['fee']['baccarat']}}/{{$info['fee']['dragonTiger']}}/{{$info['fee'] ['niuniu']}}/{{$info['fee']['sangong']}}/{{$info['fee']['A89']}}
                    @else
                        -
                    @endif
                </td>
                <td class="hidden-xs">
                    @if($info['userType']==1)
                        @if($info['code']<0)
                            <span style="color:red;">{{number_format($info['code']/100,2)}}</span>
                        @else
                            {{number_format($info['code']/100,2)}}
                        @endif
                    @else
                        -
                    @endif
                </td>
                <td class="hidden-xs">
                    @if($info['userType']==2)
                        {{isset($info['pump'])?$info['pump']:"-"}}%
                    @else
                        -
                    @endif
                </td>
                <td class="hidden-xs">
                    @if($info['userType']==2)
                        @if($info['feeMoney']<0)
                            <span style="color: red;">{{number_format($info['feeMoney']/100,2)}}</span>
                        @else
                            {{number_format($info['feeMoney']/100,2)}}
                        @endif
                    @else
                        -
                    @endif
                </td>
                <td class="hidden-xs">
                    @if($info['userType']==1)
                        {{$info['proportion']}}%
                    @else
                        -
                    @endif
                </td>

                <td class="hidden-xs">
                    @if($info['userType']==1)
                        @if($info['zg']<0)
                            <span style="color: red;">{{number_format($info['zg']/100,2)}}</span>
                        @else
                            {{number_format(abs($info['zg']/100),2)}}
                        @endif
                    @else
                        0.00
                    @endif
                </td>
                <td class="hidden-xs">
                        @if($info['userType']==1)
                            @if($info['sy']<0)
                                <span style="color: red;">{{number_format($info['sy']/100,2)}}</span>
                            @else
                                {{number_format($info['sy']/100,2)}}
                            @endif
                        @else
                            {{number_format($info['sy']/100,2)}}
                        @endif
                </td>
                <td class="hidden-xs">
                    @if($info['gs']<0)
                        <span style="color:red;">{{number_format($info['gs']/100,2)}}</span>
                    @else
                        {{number_format($info['gs']/100,2)}}
                    @endif
                </td>
                <td class="hidden-xs">
                    <div class="layui-inline">
                        <button type="button" class="layui-btn layui-btn-xs agentDayInfo" data-id="{{$info['agent_id']}}" data-name="{{$info['nickname']}}" data-desc="详情"><i class="layui-icon">代理日结</i></button>
                        <button type="button" class="layui-btn layui-btn-xs userDayInfo" data-id="{{$info['agent_id']}}" data-name="{{$info['nickname']}}" data-desc="详情"><i class="layui-icon">会员日结</i></button>
                    </div>
                </td>
            </tr>
        @endforeach
        @if(count($list)==0)
            <tr><td colspan="17" style="text-align: center;color: orangered;">暂无数据</td></tr>
        @endif
        </tbody>
    </table>
    <div class="page-wrap">
    </div>
@endsection
@section('js')
    <script>
        layui.use(['form', 'jquery','laydate', 'layer'], function() {
            var form = layui.form,
                $ = layui.jquery,
                laydate = layui.laydate,
                layer = layui.layer
            ;
            laydate.render({
                elem:"#begin"
            });
            laydate.render({
                elem:"#end"
            });
            $(".reset").click(function(){
                $("input[name='begin']").val('');
                $("input[name='end']").val('');
                $("select[name='desk_id']").val('');
                $("input[name='account']").val('');
                $("input[name='boot']").val('');
                $("select[name='userType']").val('')
            });
            //今天
            $("#today").click(function () {
                var startDate = new Date(new Date(new Date().toLocaleDateString()).getTime());
                var endDate = new Date(new Date(new Date().toLocaleDateString()).getTime() + 24 *60 *60*1000-1);
                $("input[name='begin']").val(formatDate(startDate))
                $("input[name='end']").val(formatDate(endDate))
            });
            //昨天
            $("#yesterday").click(function () {
                var startDate = new Date(new Date(new Date().toLocaleDateString()).getTime() - 24*60*60*1000);
                var endDate = new Date(new Date(new Date().toLocaleDateString()).getTime() - 24*60*60*1000 + 24*60*60*1000 -1);
                $("input[name='begin']").val(formatDate(startDate))
                $("input[name='end']").val(formatDate(endDate))
            });
            //本周
            $("#thisWeek").click(function () {
                var now = new Date();//当前日期
                var nowDayOfWeek = now.getDay();//今天本周的第几天
                var nowDay = now.getDate();//当前日
                var nowMonth = now.getMonth();//当前月
                var nowYear = now.getFullYear();//当前年
                var weekStartDate = new Date(nowYear, nowMonth, nowDay - nowDayOfWeek + 1);
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
                    content:'/admin/agentDays/'+id + '/' + begin + '/' + end
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
                    content:'/admin/userDays/'+id + '/' + begin + '/' + end
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