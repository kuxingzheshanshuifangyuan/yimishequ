<?php
namespace app\common\enum;

/**
 * 枚举
 */
class SupermarketEnum
{

	const ForumProductListFileUrl = 'http://www.baijiaqianbao.com/index.php/Owner/forum_product_listfile';

	const ForumProductDetailsUrl = 'http://www.baijiaqianbao.com/index.php/Owner/forum_product_details';

    static public function getAuditorAttr($str)
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

    static public function getAccountAttr($str)
    {
        switch ($str) {
            case 1 :
                return '储蓄卡到账';
            case 2 :
                return '信用卡到账';
            case 3 :
                return '支付宝到账';
            case 4 :
                return '微信到账';
            default :
                return '';
        }
    }

    static public function getRepaymentAttr($str)
    {
        switch ($str) {
            case 1 :
                return '自动扣费';
            case 2 :
                return '主动还款';
            default :
                return '';
        }
    }

    static public function getAheadAttr($str)
    {
        switch ($str) {
            case 1 :
                return '仍支付全部息费';
            case 2 :
                return '可根据时间减少息费';
            default :
                return '';
        }
    }

    static public function getRepaymentTpyeAttr($str)
    {
        switch ($str) {
            case 1 :
                return '一次还请';
            case 2 :
                return '分期还款';
            default :
                return '';
        }
    }
}