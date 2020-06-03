<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StoreRequest;
use App\Http\Controllers\Controller;
use App\Models\Version;

class VersionController extends Controller
{
    //列表页
    public function index(StoreRequest $request){
        $version=Version::query();
        if(true==$request->has('version_no')){
            $version->where('version_no','like','%'.$request->input('version_no').'%');
        }
        if(true==$request->has('creatime')){
            $creatime=$request->input('creatime');
            $start=strtotime($creatime);
            $end=strtotime('+1day',$start);
            $version->whereBetween('creatime',[$start,$end]);
        }
        $data=$version->orderBy('creatime','desc')->paginate(10)->appends($request->all());
        foreach ($data as $key =>$value){
            $data[$key]['creatime']=date("Y-m-d H:i:s",$value["creatime"]);
        }
        $min=config('admin.min_date');
        return view('version.list',['pager'=>$data,'min'=>$min,'input'=>$request->all()]);
    }
    //编辑页
    public function edit($id=0){
        $info = $id?Version::find($id):[];
        return view('version.edit',['id'=>$id,'info'=>$info]);
    }
    //添加版本
    public function store(StoreRequest $request){
        $data=$request->all();
        unset($data['_token']);
        unset($data['id']);
        $is=Version::add_ver($data['version_no']);
        if($is){
            return ['msg'=>'版本号已存在！'];
        }else{
            $data['creatime']=time();
            $insert=Version::insert($data);
            if($insert){
                return ['msg'=>'添加成功！','status'=>1];
            }else{
                return ['msg'=>'添加失败！'];
            }
        }
    }
    //修改
    public function update(StoreRequest $request){
        $id =$request->input('id');
        $data=$request->all();
        unset($data['_token']);
        unset($data['id']);
        $is=Version::edit_ver($id,$data['version_no']);
        if($is){
            return ['msg'=>'版本号已存在！'];
        }else{
            $update=Version::where('id',$id)->update($data);
            if($update!==false){
                return ['msg'=>'修改成功！','status'=>1];
            }else{
                return ['msg'=>'修改失败！'];
            }
        }

    }
    //删除
    public function destroy($id){
        $res = Version::where('id',$id)->delete();
        if($res){
            return ['msg'=>'删除成功！','status'=>1];
        }else{
            return ['msg'=>'删除失败！'];
        }
    }
    //开关
    public function is_open(StoreRequest $request){
        $data=$request->all();
        $id=$data['id'];
        unset($data['_token']);
        $open=$data['is_open'];
        $res=Version::where('id',$id)->update(array('is_open'=>$open));
        if($res){
            return ['msg'=>'更改成功！','status'=>1];
        }else{
            return ['msg'=>'更改失败！'];
        }
    }
}
