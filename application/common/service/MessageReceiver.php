<?php
namespace app\common\service;
use app\common\enum\TableName;
use app\common\enum\UserStatusEnum;
use \app\common\model\Message as MessageModel;
use \think\Db;
use \app\common\enum\UserRoleEnum;

/**
 * 消息接收方 - 根据某种特定规则 找到消息接收方
 * Class MessageReceiver
 * @package app\common\service
 */
class MessageReceiver
{
    /**
     * 通过 任务id 找到分配任务的管理员 ,默认得到最后一次分配的管理员
     * @param $task_id int 任务id
     * @return object|bool 管理员对象,目前只有admin_id
     * @throws
     */
    static public function getServiceAdminByTaskID($task_id)
    {
        if(empty($task_id)){
            return false;
        }
        return Db::name(TableName::COLLECT_TASK)
            ->field('operator_id admin_id')
            ->where('id','=',$task_id)
            ->find();
    }

    /**
     * 通过企业id 找到相关处置方的管理员信息[id, name]
     * @param $cmp_id int 企业id
     * @return array|bool 处置方所有管理员[[id,name]..]
     * @throws
     */
    static public function getServiceAdminByCmpID($cmp_id)
    {
        if(empty($cmp_id)){
            return false;
        }
        return Db::name(TableName::USER)
            ->field('id,name')
            ->where([
                'cmp_id' => $cmp_id,
                'role_id' => ['in', UserRoleEnum::COLLECTION_ADMIN.','.UserRoleEnum::COLLECTION_DEP_ADMIN.','.UserRoleEnum::COLLECTION_GRP_ADMIN]
            ])->select();
    }
    /**
     * 通过组织id 找到相关组织的管理员信息[id, name]
     * @param $cmp_id int 企业id
     * @return array|bool 处置方所有管理员[[id,name]..]
     * @throws
     */
    static public function getServiceAdminByOrgID($org_id)
    {
        if(empty($org_id)){
            return false;
        }
        return Db::name(TableName::USER)
            ->field('id,name')
            ->where([
                'org_id' => $org_id,
                'role_id' => ['in', UserRoleEnum::COLLECTION_ADMIN.','.UserRoleEnum::COLLECTION_DEP_ADMIN.','.UserRoleEnum::COLLECTION_GRP_ADMIN]
            ])->select();
    }

    /**
     * 得到所有催客 (通过企业id)
     * @param $cmp_id int 企业id
     * @return array|bool [[id,name]... ]
     * @throws
     */
    static public function getCollectorByCmpID($cmp_id)
    {
        if(empty($cmp_id)){
            return false;
        }
        return Db::name(TableName::USER)->field('id, name')->where([
            'cmp_id' => $cmp_id,
            'status' => UserStatusEnum::NORMAL
        ])->select();
    }
}