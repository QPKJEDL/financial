<?php


namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\Billflow;
use App\Models\Czrecord;
use App\Models\HqUser;
use App\Models\Pay;
use Illuminate\Http\Request;

class ThreeController extends Controller
{
    public function index(Request $request)
    {
        $map = array();
        if (true==$request->has('begin'))
        {
            $start = strtotime($request->input('begin'));
        }
        else
        {
            $start = strtotime(date('Y-m-d',time()));
            $request->offsetSet('begin',date('Y-m-d',time()));
        }
        if (true==$request->has('end'))
        {
            $endDate = strtotime('+1day',strtotime($request->input('end')))-1;
        }
        else
        {
            $endDate = strtotime('+1day',strtotime(date('Y-m-d',time())))-1;
            $request->offsetSet('end',date('Y-m-d',time()));
        }
        if (true==$request->has('account'))
        {
            $user = HqUser::where('account','=',$request->input('account'))->first();
            $map['czrecord.user_id']=$user['user_id'];
        }
        if (true==$request->has('orderSn'))
        {
            $map['czrecord.order_sn']=$request->input('orderSn');
        }
        if (true==$request->has('status'))
        {
            $map['czrecord.status']=$request->input('status');
        }
        if (true==$request->has('business_id'))
        {
            $map['czrecord.business_id']=$request->input('business_id');
        }
        $sql = Czrecord::query();
        $sql->leftJoin('user','user.user_id','=','czrecord.user_id')
            ->leftJoin('pay','czrecord.business_id','=','pay.business_id')
            ->select('czrecord.*','pay.service_name','user.account')->where('czrecord.business_id','!=',0)->whereBetween('czrecord.creatime',[$start,$endDate]);
        if (true==$request->has('excel'))
        {
            $head = array('订单号','商户名称','充值会员账号','创建时间','成功充值时间','充值金额','实际到账金额','状态');
            $excelData = $sql->where($map)->orderBy('czrecord.creatime','desc')->get()->toArray();
            $excel = array();
            foreach ($excelData as $key=>$datum)
            {
                $a = array();
                $a['order_sn']=$datum['order_sn'];
                $a['service_name']=$datum['service_name'];
                $a['account']=$datum['account'];
                $a['creatime']=date('Y-m-d H:i:s',$datum['creatime']);
                if ($datum['savetime']!=0)
                {
                    $a['savetime']=date('Y-m-d H:i:s',$datum['savetime']);
                }else
                {
                    $a['savetime']='-';
                }
                $a['score']=number_format($datum['score']/100,2);
                if ($datum['status']==0)
                {
                    $a['m']=0.00;
                }else
                {
                    $a['m']=number_format($datum['score']/100,2);
                }
                if ($datum['status']==1)
                {
                    $a['status']='充值成功';
                }else
                {
                    $a['status']='待充值';
                }
                $excel[] = $a;
            }
            try {
                exportExcel($head, $excel, date('Y-m-d H:i:s',time()).'第三方支付流水', '', true);
            } catch (\PHPExcel_Reader_Exception $e) {
            } catch (\PHPExcel_Exception $e) {
            }
        }
        if (true==$request->has('limit'))
        {
            $limit = $request->input('limit');
        }
        else
        {
            $limit = 10;
        }
        $data = $sql->where($map)->orderBy('czrecord.creatime','desc')->paginate($limit)->appends($request->all());
        foreach ($data as $key=>$datum)
        {
            $data[$key]['creatime']=date('Y-m-d H:i:s',$datum['creatime']);
            if ($data[$key]['savetime']!=0)
            {
                $data[$key]['savetime']=date('Y-m-d H:i:s',$datum['savetime']);
            }
        }
        return view('three.list',['list'=>$data,'input'=>$request->all(),'limit'=>$limit,'business'=>Pay::getAllPayList()]);
    }
    /**
     * 根据开始时间结束时间获取中间得时间段
     * @param $startDate
     * @param $endDate
     * @return array
     */
    public function getDateTimePeriodByBeginAndEnd($startDate,$endDate){
        $arr = array();
        $start_date = date("Y-m-d",strtotime($startDate));
        $end_date = date("Y-m-d",strtotime($endDate));
        for ($i = strtotime($start_date); $i <= strtotime($end_date);$i += 86400){
            $arr[] = date('Ymd',$i);
        }
        return $arr;
    }
}