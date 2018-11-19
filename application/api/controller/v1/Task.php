<?php

namespace app\api\controller\v1;

use app\admin\controller\Pact;
use app\common\service\MessageReceiver;
use app\common\enum\CaseStatusEnum;
use app\common\enum\UserSexEnum;
use app\common\enum\UserStatusEnum;
use think\Db;

use \app\xgy_api\exception\BaseException as Exception;
use \app\common\enum\TableName;
use \app\common\enum\CollectTaskStatusEnum;
use \app\common\enum\UserRoleEnum;
use \app\common\model\CollectTask;

use \app\common\service\Message;

/**
 * Class Task 移动端 催客任务类
 * @package app\xgy_api\controller\v1
 * @author yaoyao
 */
class Task extends Base
{
    // 校验参数规则数组
    protected $validate_param_rules = [
        'id' => ['ID', 'require|number'],
        'case_id' => ['案件ID', 'require|number'],
        'task_id' => ['任务ID', 'require|number'],
        'case_no' => ['案件编号', 'require'],
        'only_self' => ['只看自己', 'boolean', false], // 回款记录中 , 是否只看自己的回款记录
        'current_end_time' => ['当前任务结束时间', 'require|date'],
        'delay_time' => ['申请延期时间', 'require|date'],
        'remark' => ['备注', 'require'],

        'type' => ['类型', 'require|chs'],
        'object' => ['对象', 'require|chs'],
        'collect_result' => ['催收结果', 'require|chs'],
        'result' => ['结果', 'require|chs'],
        'address' => ['详细地址', 'chsDash', ''],
        'content' => ['附加内容', 'chsDash'],
        'pic' => ['图片', 'require|array'],
        'coordinate' => ['定位', 'require'],
        'daily_address' => ['催记提交详细地址', 'require']
    ];

    /**
     * 任务 - 列表 （某用户）
     * @return array
     * @throws Exception
     */
    public function lists()
    {
        $user_info = $this->getUserInfo();
        $where['ct.collector_id'] = $user_info['id'];

        $request_params = $this->validateRequestParams(['status']);
        if ($request_params['status'] !== null) {
            $where['ct.status'] = ['in', $request_params['status']];
        }

        $task_list = Db::name(TableName::COLLECT_TASK)->alias('ct')
            ->field('ct.id,ct.init_debt, ct.overdue_day, ct.expired_time, 
                ct.repay_amount, ct.received_rwd,ct.commision_case_id case_id,
                cc.commision_case_no, cc.usual_addr_detail, ct.reward_ratio,ct.status')
            ->join(TableName::COMMISSION_CASE . ' cc', 'ct.commision_case_id = cc.id')
            ->where($where)
            ->order('ct.create_time', 'desc');
        $task_list = $this->paginate($task_list);

        if ($request_params['status'] == CollectTaskStatusEnum::ONGOING) {
            foreach ($task_list['data'] as &$task) {
                $time = strtotime($task['expired_time']) - time();
                $days = 1;
                if ($time > 0) { // 如果大于1天
                    $days = ceil($time / 86400);
//                    $time = $time % 86400; // 计算天后剩余的毫秒数
                }
                $task['expired_days'] = $days;
                // 注意: 如果这里发生改变, 需要将CollectTask::getTaskOverview方法也需要掉
            }
        }
        foreach ($task_list['data'] as &$task) {
            $task['reward_ratio'] *= 100;
        }
        if ($task_list['data']) {
            $this->dealMoneyToDisplay($task_list['data'], ['init_debt', 'repay_amount', 'received_rwd']);
        }

        return ToApiFormat('request success', $task_list);
    }

    /**
     * 任务 - 详情
     * @throws Exception
     */
    public function detail()
    {
        $user_info = $this->getUserInfo();

        $request_params = $this->validateRequestParams(['id']);

        $result = CollectTask::getTaskOverview($request_params['id'], $user_info['id']);

        if (!$result) {
            throw new Exception('not found task by task_id', 200);
        }

        // 将手别改为0
        if($result['case_info']['current_collect_hands'] > 0){
            $result['case_info']['current_collect_hands'] -= 1;
        }

        $this->dealMoneyToDisplay($result, [
            'dept_info.init_debt',
            'dept_info.repayment_amount',
            'task_info.repay_amount',
            'task_info.received_rwd'
        ], false);
        return ToApiFormat('ok', $result);
    }

    /**
     * 手别
     */
    public function hands()
    {
        $this->checkToken();

        $request_params = $this->validateRequestParams(['case_id']);

        $hands = Db::name(TableName::COLLECT_TASK)->alias('ct')
            ->field('ct.create_time, ct.expired_time,
            u.name, u.sex')
            ->join(TableName::USER . ' u', 'ct.collector_id = u.id', 'LEFT')
            ->where([
                'ct.commision_case_id' => $request_params['case_id'],
                'ct.status' => ['>', CollectTaskStatusEnum::ONGOING],
                'u.role_id' => UserRoleEnum::COLLECTION_USR
            ])
            ->order('ct.create_time', 'desc');

        $hands = $this->paginate($hands);

        foreach ($hands['data'] as &$val) {
            $val['sex'] = UserSexEnum::getSexName($val['sex']);
        }
        return ToApiFormat('ok', $hands);
    }

    /**
     * 放弃当前催收任务
     * @throws
     */
    public function giveUp()
    {
        $user_info = $this->getUserInfo();
        $request_params = $this->validateRequestParams(['task_id']);

        $task_info = Db::name(TableName::COLLECT_TASK)
            ->field('commision_case_id, status')
            ->where([
                'id' => $request_params['task_id'],
                'collector_id' => $user_info['id']
            ])
            ->find();
        if (!$task_info) {
            throw new Exception('not found this task', 200);
        }
        if ($task_info['status'] != CollectTaskStatusEnum::ONGOING) {
            throw new Exception('this task status is not onging', 208);
        }

        // 事务 同时提交
        Db::transaction(function () use ($request_params, $task_info, $user_info) {
            Db::name(TableName::COLLECT_TASK)
                ->where('id', '=', $request_params['task_id'])
                ->update([
                    'status' => CollectTaskStatusEnum::COLLECTOR_GIVEUP,
                    'end_type' => 2,
                    'end_time'=>date('Y-m-d H:i:s'),
                    'stop_reason'=>'催客放弃',
                    'operator_id' => $user_info['id'],
                    'operator_name' => $user_info['name']
                ]);
            Db::name(TableName::COMMISSION_CASE)
                ->where('id', '=', $task_info['commision_case_id'])
                ->update([
                    'current_collect_id' => 0,
                    'current_collect_name' => '',
                    'current_collect_task_id' => 0,
                    'status' => CaseStatusEnum::UNASSIGNED // 将当前案件状态变为待分配
                ]);
        });

        // 发送消息
        // 找到当前催客相关所有管理员,
        $admin = MessageReceiver::getServiceAdminByTaskID($request_params['task_id']);
        // 通过task 查询到caseno
        $case_no = Db::name(TableName::COMMISSION_CASE)
            ->field('commision_batch_no')
            ->where('id', '=', $task_info['commision_case_id'])
            ->find()['commision_batch_no'];
        if ($admin && !empty($admin['admin_id'])) {
            Message::send('collector_give_up_case', [
                'to_user_id' => $admin['admin_id'],
                'collector_name' => $user_info['name'],
                'case_no' => $case_no
            ]);
        }

        return ToApiFormat('ok');
    }

    /**
     * 回款记录
     * @throws
     */
    public function repaymentRecord()
    {
        $this->checkToken();

        $request_params = $this->validateRequestParams(['c_id', 't_id', 'only_self']);
        if ($request_params['only_self']) {
            $where['ct.collector_id'] = $this->getUserInfo()['id'];
        }
        if ($request_params['c_id']) {
            $where['brh.case_id'] = $request_params['c_id'];
        } else if ($request_params['t_id']) {
            $where['brh.task_id'] = $request_params['t_id'];
        } else {
            throw new Exception('need param c_id or t_id');
        }
        $result = Db::name(TableName::BATCH_RETURNEDMONEY_HISTORY)->alias('brh')
            ->field('brh.submit_money,brh.confirm_money,brh.status,brh.cofirm_status,
                brh.update_time, brh.create_time,
                ct.reward_ratio,
                u.name')
            ->join(TableName::COLLECT_TASK . ' ct', 'ct.id = brh.task_id', 'LEFT')
            ->join(TableName::USER . ' u', 'ct.collector_id = u.id', 'LEFT')
            ->where($where)
            ->order('brh.create_time', 'desc');

        $result = $this->paginate($result);

        if (isset($result['data']) && $result['data']) {

            // 需要将返回客户端的真实金额改为 return_money  check_status:2未核对  1已核对
            foreach ($result['data'] as $key => &$v){
                $result['data'][$key]['reward_ratio'] *= 100;
                if($result['data'][$key]['update_time'] == '0000-00-00 00:00:00'){ // 并未比对金额
                    $bg_return_money = $result['data'][$key]['submit_money'];
                    $return_money = $result['data'][$key]['submit_money'];
                    $check_status = 2;
                }elseif($result['data'][$key]['cofirm_status'] == 2){ // 比对结果异常, 但修改完后
                    $bg_return_money = $result['data'][$key]['confirm_money'];
                    $return_money = $result['data'][$key]['confirm_money'];
                    $check_status = 1;
                }elseif(empty($result['data'][$key]['status'])){ // 比对完成,无疑义
                    $bg_return_money = $result['data'][$key]['submit_money'];
                    $return_money = $result['data'][$key]['submit_money'];
                    $check_status = 1;
                }else{ // 剩下给客户端为正在进行比对的数据
                    $bg_return_money = $result['data'][$key]['submit_money'];
                    $return_money = $result['data'][$key]['submit_money'];
                    $check_status = 2;
                }
                $result['data'][$key]['bg_return_money'] = $bg_return_money;
                $result['data'][$key]['return_money'] = $return_money;
                $result['data'][$key]['check_status'] = $check_status;

                unset($result['data'][$key]['status']);
                unset($result['data'][$key]['cofirm_status']);
                unset($result['data'][$key]['update_time']);
                unset($result['data'][$key]['confirm_money']);
                unset($result['data'][$key]['submit_money']);
            }


            $this->dealMoneyToDisplay($result['data'], ['return_money'], true);
        }
        return ToApiFormat('ok', $result);
    }

    /**
     * 延期记录 （列表）
     * @throws
     */
    public function delayRecord()
    {
        $this->checkToken();

        $request_params = $this->validateRequestParams(['id']);

        $result = Db::name(TableName::DELAY_TASK)->alias('dt')
            ->field('dt.last_end_time, dt.end_time,dt.create_time,dt.audit_time, dt.process_status status, 
                        u.name, u.phone ')
            ->join(TableName::USER . ' u', 'u.id = operation_user_id', 'LEFT')
            ->where([
                'collect_task_id' => $request_params['id'],
                'dt.type' => 2, //催收员申请类型
            ])
            ->order('dt.create_time', 'desc');

        $result = $this->paginate($result);

        return ToApiFormat('ok', $result);
    }

    /**
     * 延期申请
     * @throws
     */
    public function delayApply()
    {
        $user_info = $this->getUserInfo();

        $request_params = $this->validateRequestParams(['task_id', 'current_end_time', 'delay_time', 'remark']);

        $current_end_time = strtotime($request_params['current_end_time']);
        $delay_time = strtotime($request_params['delay_time']);

//        if ($current_end_time >= $delay_time) {
//            throw new Exception('delay time need gt current end time', 203);
//        }
        $diff = getDiffByTimestmp($current_end_time, $delay_time);

        $task_info = Db::name(TableName::COLLECT_TASK)
            ->field('commision_case_id')
            ->where('id', '=', $request_params['task_id'])
            ->find();
        if (!$task_info) {
            throw new Exception('not found task', 404);
        }

        $result = Db::name(TableName::DELAY_TASK)->insert([
            'collect_task_id' => $request_params['task_id'],
            'case_id' => $task_info['commision_case_id'],
            'collector_id' => $user_info['id'],
            'collector_name' => $user_info['name'],
            'last_end_time' => $request_params['current_end_time'],
            'end_time' => $request_params['delay_time'],
            'type' => 2,
            'process_status' => 1,
            'remark' => $request_params['remark'],
            'create_time' => date('Y-m-d H:i:s', time()),
            'last_remind_time' => time()
        ]);

        if (!$result) {
            throw new Exception('apply delay fail', 202);
        }

        // 发送信息
        // 找到当前催客相关所有管理员,
        $admins = MessageReceiver::getServiceAdminByCmpID($user_info['cmp_id']);
        // 获取当前任务 案件号
        $case_no = Db::name(TableName::COMMISSION_CASE)
            ->field('commision_case_no')
            ->where('current_collect_task_id', '=', $request_params['task_id'])
            ->find()['commision_case_no'];
        if ($admins && $case_no) {
            $send_msg_data = array();
            foreach ($admins as $admin) {
                $send_msg_data[] = [
                    'to_user_id' => $admin['id'],
                    'case_no' => $case_no,
                    'collector_name' => $user_info['name']
                ];
            }
            if (!Message::send('collector_apply_delay', $send_msg_data)) {
                throw new Exception("send message error", 800);
            }
        }


        return ToApiFormat('ok');
    }

    /**
     * 延期督促 （提醒管理员）
     * @throws
     */
    public function delayRemind()
    {
        $user_info = $this->getUserInfo();
        $request_params = $this->validateRequestParams(['task_id']);

        $delay_status = CollectTask::getLastDelay($request_params['task_id']);
        if ($delay_status) {
            $delay_status = $delay_status['status'];
            Db::name(TableName::DELAY_TASK)->where([
                'collect_task_id' => $request_params['task_id']
            ])->update([
                'last_remind_time' => time()
            ]);
        } else {
            $delay_status = null;
        }
        if ($delay_status != 1) {
            throw new Exception('not found need delay task', 204);
        }

        // 发送信息
        // 获取当前任务 案件号
        $case_info = Db::name(TableName::COMMISSION_CASE)
            ->field('commision_case_no')
            ->where('current_collect_task_id', '=', $request_params['task_id'])
            ->find();
        if (!$case_info) {
            throw new Exception('current task not connect case', 210);
        }
        // 找到当前催客相关所有管理员,
        $admins = MessageReceiver::getServiceAdminByCmpID($user_info['cmp_id']);
        if (!$admins) {
            throw new Exception('not find the corresponding administrator by current_user', 205);
        }
        $send_msg_data = array();
        foreach ($admins as $admin) {
            $send_msg_data[] = [
                'to_user_id' => $admin['id'],
                'case_no' => $case_info['commision_case_no'],
                'collector_name' => $user_info['name']
            ];
        }
        if (!Message::send('collector_apply_delay', $send_msg_data)) {
            throw new Exception("send message error", 800);
        }

        return ToApiFormat('ok');
    }

    /**
     * 催记 - 列表
     * 注意 : 催款记录类型:  0上门催收 1电话催收 2智能云呼 3人工坐席
     * @throws
     */
    public function collectRecordList()
    {
        $user_info = $this->getUserInfo();
        $request_params = $this->validateRequestParams(['cid', 'is_self']);

        $where = array();
        if ($request_params['cid']) {
            // 以下两条SQL语句, 是因为客户端需要对所有催款记录类型数据进行分页加载, 所以需要对三条SQL语句进行union
            // 原本想要使用thinkphp5 自带union函数, 但是发现当前版本 在调用union()函数后调用order()函数, 使得order函数依然在union前的表查询结尾添加, 不符合mysql unsion用法,会出现报错,  于是本人使用了原生sql查询解决该问题
            // 智能云呼记录 SQL
            $sql_call_ivr_record = Db::name(TableName::CALL_IVR_RECORD)
                ->field('2 record_type, ivr_result result, null content , null collect_addr_detail, call_start_time create_time, \'null\' pic_1,\'null\' pic_2,\'null\' pic_3,\'null\' pic_4,\'null\' pic_5,\'null\' pic_6, 
            \'robot\' collector_name, 0 return_meney, 0 last_returnmoney_time, phone,
            \'null\' daily_address')
                ->where('commission_case_id', '=', $request_params['cid'])
                ->buildSql();

            // 人工坐席记录 SQL ...
        } else if ($request_params['is_self']) {
            $where['cr.collector_id'] = $user_info['id'];
        } else {
            throw new Exception('参数缺失');
        }

        if ($request_params['cid']) { // 若有cid 直接添加cid需要条件
            $where['cr.commision_case_id'] = $request_params['cid'];
        }

        // 催客记录 SQL
        $sql_collector_record = Db::name(TableName::COLLECT_RECORD)->alias('cr')// 催收地址作为详细地址
        ->field('cr.type record_type, cr.collect_result result, cr.content , cr.daily_address collect_addr_detail, cr.create_time, cr.pic_1,cr.pic_2,cr.pic_3,cr.pic_4,cr.pic_5,cr.pic_6, 
                u.name collector_name,
                group_concat(brh.submit_money) return_meney, group_concat(brh.create_time) last_returnmoney_time,
                \'null\' phone,
                daily_address')
            ->join(TableName::USER . ' u', 'cr.collector_id = u.id', 'LEFT')
            ->join(TableName::BATCH_RETURNEDMONEY_HISTORY . ' brh', 'brh.collect_record_id = cr.id', 'LEFT')
            ->group('brh.collect_record_id')
            ->where($where)
            ->buildSql();

        if ($request_params['cid']) {
            $ennd_sql = $sql_collector_record . ' UNION ' . $sql_call_ivr_record . 'order by create_time desc ' . $this->paginate('', true);
        } else {
            $ennd_sql = $sql_collector_record . 'order by create_time desc ' . $this->paginate('', true);
        }
        $result = Db::query($ennd_sql);

        if ($result) {
            $this->dealMoneyToDisplay($result, ['return_meney'], true, false);
        }
        return ToApiFormat('ok', $result);
    }

    /**
     * 催记 - 保存
     * @throws
     */
    public function collectRecordStore()
    {
        $user_info = $this->getUserInfo(true);

        $request_params = $this->validateRequestParams(['task_id', 'type', 'object', 'collect_result', 'result', 'address', 'content', 'pic', 'return_money', 'coordinate', 'daily_address']);

        // 判断该任务用户是否对应
        $find_task = Db::name(TableName::COLLECT_TASK)->alias('ct')
            ->field('ct.commision_case_id,ct.un_repay_amount,ct.commision_batch_id,ct.status,
            cb.batch_no')
            ->join(TableName::COMMISSION_BATCH . ' cb', 'cb.id = ct.commision_batch_id', 'LEFT')
            ->where([
                'ct.collector_id' => $user_info['id'],
                'ct.id' => $request_params['task_id']
            ])
            ->find();
        if (!$find_task || $find_task['status'] != CollectTaskStatusEnum::ONGOING) {
            throw new Exception('current user and task not matching or status problem', 207);
        }

        // 开启事务
        Db::startTrans();
        try {
            if (!empty($request_params['pic'])) {
                $request_params['pic'] = array_values($request_params['pic']);
            }
            // 写入催客记录表
            $record_id = Db::name(TableName::COLLECT_RECORD)->insertGetId([
                'commision_case_id' => $find_task['commision_case_id'],
                'commision_batch_id' => $find_task['commision_batch_id'],
                'collect_task_id' => $request_params['task_id'],
                'collector_id' => $user_info['id'],
                'type' => ($request_params['type'] == '上门') ? 0 : 1,
                'collect_result' => $request_params['type'] . '-' . $request_params['object'] . '-' . $request_params['collect_result'] . '-' . $request_params['result'],// = 方式 + 对象 + 催收情况 + 结果
                'collect_region_id' => 0,
                'collect_addr_detail' => $request_params['address'],
                'content' => $request_params['content'],
                'pic_1' => isset($request_params['pic'][0]) ? $request_params['pic'][0] : '',
                'pic_2' => isset($request_params['pic'][1]) ? $request_params['pic'][1] : '',
                'pic_3' => isset($request_params['pic'][2]) ? $request_params['pic'][2] : '',
                'pic_4' => isset($request_params['pic'][3]) ? $request_params['pic'][3] : '',
                'pic_5' => isset($request_params['pic'][4]) ? $request_params['pic'][4] : '',
                'pic_6' => isset($request_params['pic'][5]) ? $request_params['pic'][5] : '',
                'coordinate' => $request_params['coordinate'],
                'daily_address' => $request_params['daily_address'],
                'create_time' => date('Y-m-d H:i:s', time())
            ]);

            // 更新case data
            $update_case_data = [
                'collecting_record_utime' => $this->request->time(),
                'collecting_record_count' => ['exp', 'collecting_record_count+1'],
            ];

            // 写入金额记录表(判断是否有资金提交)
            if (!empty($request_params['return_money'])) {
                // 前端输入参数为 '22,2018-05-28  00:00,3234,2018-05-28  00:00'
                $return_money = $request_params['return_money'];
                $return_money = explode(',', $return_money);
                $r_money = array();
                for ($i = 1; $i <= count($return_money) / 2; $i++) {
                    if (empty($return_money[$i * 2 - 2])) {
                        continue;
                    }
                    $r_money[] = [
                        'return_money' => $return_money[$i * 2 - 2],
                        'return_time' => $return_money[$i * 2 - 1]
                    ];
                }
                $request_params['return_money'] = $r_money;

                $returnmoney_list = [];
                $now_sub_money = 0;
                foreach ($request_params['return_money'] as &$money_info) {
                    $money_info['return_money'] = $money_info['return_money'] * 100;
                    $now_sub_money += $money_info['return_money'];
                    $returnmoney_list[] = [
                        'batch_id' => $find_task['commision_batch_id'],
                        'batch_no' => $find_task['batch_no'],
                        'collector_id' => $user_info['id'],
                        'case_id' => $find_task['commision_case_id'],
                        'task_id' => $request_params['task_id'],
                        'collect_record_id' => $record_id,
                        'arrearage_money' => $find_task['un_repay_amount'], // 欠款金额
                        'submit_money' => $money_info['return_money'],
                        'create_time' => $money_info['return_time'], // 回款时间
                        'ctime' => date('Y-m-d H:i:s'), // 创建记录时间
                        'status' => '0'
                    ];
                }
                if (!empty($returnmoney_list)) {
                    Db::name(TableName::BATCH_RETURNEDMONEY_HISTORY)->insertAll($returnmoney_list);
                    Db::name(TableName::COMMISSION_BATCH)
                        ->where('id', '=', $find_task['commision_batch_id'])
                        ->setInc('submit_money', $now_sub_money);
                    // yaoyao180629 添加case回款金额冗余字段数据更新
                    Db::name(TableName::COMMISSION_CASE)
                        ->where('id', '=', $find_task['commision_case_id'])
                        ->setInc('submit_money', $now_sub_money);
                    $update_case_data['settlement_status'] = 2; // 更新 结算状态为 待清算
                }
            }

            //对 批次表 和 案件表中的催记数量进行更新
            Db::name(TableName::COMMISSION_CASE)->where('id', '=', $find_task['commision_case_id'])->update($update_case_data);
            Db::name(TableName::COMMISSION_BATCH)->where('id', '=', $find_task['commision_batch_id'])->update([
                'collecting_record_utime' => $this->request->time(),
                'collecting_record_count' => ['exp', 'collecting_record_count+1'],
            ]);

            if (isset($request_params['coordinate']) &&
                !empty($request_params['coordinate']) &&
                $request_params['coordinate'] != ',') {
                // 写入催客足迹
                Db::name(TableName::OA_COLLECTOR_FOOTPRINT)->insert([
                    'user_id' => $user_info['id'],
                    'user_name' => $user_info['name'],
                    'phone' => $user_info['phone'],
                    'company_id' => $user_info['cmp_id'],
                    'company_name' => $user_info['cmp_info']['cmp_name'],
                    'org_id' => $user_info['org_id'],
                    'org_name' => $user_info['org_info']['org_name'],
                    'coordinate' => $request_params['coordinate'],
                    'daily_address' => $request_params['daily_address'],
                    'comment' => '填写催记',
                    'create_time' => $this->request->time(),
                    'date' => date('Ymd', $this->request->time())
                ]);
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return ToApiFormat('collect record stroe fail :' . $e->getMessage(), '', 208);
        }

        // 发送信息
        // 找到当前催客相关所有管理员,
        $admins = MessageReceiver::getServiceAdminByCmpID($user_info['cmp_id']);
        // 获取当前任务 案件号
        $case_no = Db::name(TableName::COMMISSION_CASE)->field('commision_case_no')->where('current_collect_task_id', '=', $request_params['task_id'])->find()['commision_case_no'];
        if ($admins && $case_no && !empty($request_params['return_money'])) {
            $send_msg_data = array();
            foreach ($admins as $admin) {
                foreach ($request_params['return_money'] as $money_info) {
                    $send_msg_data[] = [
                        'to_user_id' => $admin['id'],
                        'case_no' => $case_no,
                        'collector_name' => $user_info['name'],
                        'returnmoney_date' => $money_info['return_time'],
                        'returnmoney_money' => $money_info['return_money']
                    ];
                }
            }
            if (!Message::send('collector_submit_returnmoney', $send_msg_data)) {
                throw new Exception("send message error", 800);
            }
        }
        return ToApiFormat('ok');
    }

    /**
     * 我的奖金
     */
    public function myBonus()
    {
        $user_info = $this->getUserInfo();
        $result = Db::name(TableName::COLLECT_TASK)->alias('ct')
            ->field('cc.commision_case_no, sum(ct.repay_amount) repay_amount, sum(ct.received_rwd) received_rwd, max(ct.last_pay_time) time')
            ->join(TableName::COMMISSION_CASE . ' cc', 'ct.commision_case_id = cc.id', 'LEFT')
            ->where([
                'ct.collector_id' => $user_info['id'],
                'ct.repay_amount' => ['>', 0] // 已还款金额需要大于0 ,说明本次任务有还款
            ])->group('ct.commision_case_id')
            ->order('ct.last_pay_time', 'desc');

        $result = $this->paginate($result);

        if ($result['data']) {
            $this->dealMoneyToDisplay($result['data'], ['repay_amount', 'received_rwd']);
        }
        return ToApiFormat('ok', $result);
    }

    /**
     * 公司 奖金排行
     * @throws
     */
    public function bonusRankings()
    {
        $cmp_id = $this->getUserInfo()['cmp_id'];
        $result = Db::name(TableName::USER)
            ->field('name,sex,acc_repayment_amount,phone,id')
            ->where([
                'cmp_id' => $cmp_id,
                'status' => UserStatusEnum::NORMAL,
                'role_id' => UserRoleEnum::COLLECTION_USR
            ])
            ->order('acc_repayment_amount', 'desc')
            ->select();

        if ($result) {
            $this->dealMoneyToDisplay($result, ['acc_repayment_amount']);
        }
        return ToApiFormat('ok', $result);
    }
}