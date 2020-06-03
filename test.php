<?php

$json = '{"banker":2000,"bankerPair":2000,"player":2000,"playerPair":2000,"tie":2000}';
$data = json_decode($json,true);
print_r($data);
$str = '';
foreach ($data as $key=>$value) {
    if ($data['banker']>0) {
        $str = "庄".$data['banker'];
    }
    if ($data['bankerPair']>0) {
        $str = $str."庄对".$data['bankerPair'];
    }
    if ($data['player']>0) {
        $str = $str."闲".$data['player'];
    }
    if ($data['playerPair']>0) {
        $str = $str."庄对".$data['playerPair'];
    }
    if ($data['tie']>0) {
        $str = $str."和".$data['tie'];
    }
}
print_r($str);