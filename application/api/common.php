<?php

//调试用 显示数据
function p($val,$name=''){
    echo '<pre>';
    echo $name.'-start************************************************************<br/>';
    print_r($val);
    echo '<br/>'.$name.'-end************************************************************<br/>';
}
/**
 * 引用自 admin模块中的公共方法
 * @param $password
 * @return string
 */
function password_hash_tp($password)
{
    return hash("md5", trim($password));
}

function ToApiFormat($msg = '', $data = [], $error_code = 0)
{
	return [
		'msg' => $msg,
		'data' => $data,
		'error_code' =>$error_code
	];
}

// 获取16随机数字串
function getRandom()
{
    return date('ymdHis').mt_rand(1000,9999);
}

/**
 * 根据两个时间戳计算差值,并且返回 day hour min sec
 * @param $timestmp1
 * @param $timestmp2
 * @return array
 */
function getDiffByTimestmp($timestmp1, $timestmp2)
{
    if($timestmp1 < $timestmp2){
        $starttime = $timestmp1;
        $endtime = $timestmp2;
    }
    else{
        $starttime = $timestmp2;
        $endtime = $timestmp1;
    }
    $timediff = $endtime-$starttime;
    $days = intval($timediff/86400);
    $remain = $timediff%86400;
    $hours = intval($remain/3600);
    $remain = $remain%3600;
    $mins = intval($remain/60);
    $secs = $remain%60;
    $res = array("day" => $days,"hour" => $hours,"min" => $mins,"sec" => $secs);
    return $res;
}

