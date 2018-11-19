<?php
/**
 * Created by PhpStorm.
 * User: GuoLin
 * Date: 2018/7/16
 * Time: 16:09
 */

namespace app\common\enum;

class ForumTypeEnum {
    const HotNews = 3;
    const Recommend = 2;


    static public function getSortTitleAttr($str)
    {
        switch ($str) {
            case 1 :
                return '自动审批';
            case 2 :
                return '人工审核';
            case 3 :
                return '自动加人工审核';
            default :
                return '';
        }
    }

    static public function getTimeHorizonCondition($type){
        switch ($type) {
            case 1 :    //获取当天
                return ['between',[strtotime(date("Y-m-d",time())),strtotime(date("Y-m-d",time()))+86399]] ;
            case 2 :    //前两天的
                return ['>=',strtotime(date('Y-m-d',strtotime('-2 day')))];
            case 3 :    //一周
                return ['>=',strtotime(date("Y-m-d", strtotime("-1 week")))];
            case 4 :    //一月
                return ['>=',strtotime(date("Y-m-d", strtotime("-1 month")))];
            case 5 :    //三月
                return ['>=',strtotime(date("Y-m-d", strtotime("-3 month")))];
            default :
                return '';
        }
    }


}