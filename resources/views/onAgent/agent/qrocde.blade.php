@section('title', '角色编辑')
@section('content')
    <input id="id" type="hidden" value="{{$id}}">
    <div id="qrcode"></div>
@endsection
@section('id',$id)
@section('js')
    <script>
        var id = document.getElementById('id').value
        var str = '/admin/agentRegister/'+id;
        new QRCode(document.getElementById("qrcode"), window.location.host+str);  // 设置要生成二维码的链接
    </script>
@endsection
@extends('common.editb')