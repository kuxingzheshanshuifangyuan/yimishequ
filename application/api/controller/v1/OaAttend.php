<?php
namespace app\api\controller\v1;
use app\common\enum\OaApplyStatusEnum;
use app\common\enum\OaCardTypeEnum;
use app\common\enum\OaDateTypeEnum;
use app\common\model\OaCardRecord;
use app\common\model\OaClassNum;
use app\common\model\OaCompany;
use think\Db;
use \app\xgy_api\exception\BaseException as Exception;
use \app\common\enum\TableName;

use \app\common\service\Message;
use \app\common\service\MessageReceiver;
use yaoyao\DateTime;
use yaoyao\Line;

/**
 * Class Oa 考勤模块
 * @package app\xgy_api\controller\v1
 * @author yaoyao
 */
class OaAttend extends Base{

    // 校验参数规则数组
    protected $validate_param_rules = [
        // 申请请假
        'apply_start_time' => ['申请开始时间','require|date'],
        'apply_end_time' => ['申请结束时间','require|date'],
        'comment' => ['备注', 'require'],
        'finish' => ['完成任务', 'require'],
        'photo_arr' => ['图片数组', 'array'],
        'attachment_arr' => ['附件数组', 'array'],
        'reason' => ['原因', 'require'],
        'record_id' => ['记录id', 'require|number'],
        'record_type'=> ['记录类型', 'require|between:1,6'],
        // 打卡
        'is_outside' => ['是否为外勤打卡','require|boolean'],
        // 取消申请
        'apply_type' => ['申请类型', 'require|between:1,2'],
        'id' => ['ID', 'require'],
        // 获取日打卡记录
        'date' => ['打卡日期', 'require|date'],
        // 申请列表
        'apply_status' => ['申请状态', 'between:1,4']
    ];

    /**
     * 返回该公司班次信息
     * @throws
     */
    public function getClassNumInfo()
    {
        $user_info = $this->getUserInfo();
        $class_info = Db::name(TableName::OA_CLASS_NUM)
            ->field('week,
                card_area_start_1,class_start_1,class_off_1,card_area_off_1,
                card_area_start_2,class_start_2,class_off_2,card_area_off_2,
                card_area_start_3,class_start_3,class_off_3,card_area_off_3
                class_all_man_hour,type,class_num')
            ->where('company_id','=',$user_info['cmp_id'])
            ->order('week','asc')
            ->select();
        return ToApiFormat('ok',$class_info);
    }

    /**
     * 得到日打卡记录
     * @throws
     */
    public function getDayRecord()
    {
        $user_info = $this->getUserInfo();
        $request_params = $this->validateRequestParams(['date']);
        $date_info = DateTime::splitDate($request_params['date']);

        // 大于今天 ,返回null 2018-06-27加入代码
        $today_date = date('Ymd');
        if($date_info['Ymd'] > $today_date) {
            throw new Exception('not found day record, this day has not arrived.', 316); // 未来
        }
        unset($today_date);

        $day_record = Db::name(TableName::OA_CARD_RECORD_DAY)
            ->field('id,class_num,status,
            work_time_start_1,card_time_start_1,card_result_type_start_1,work_card_diff_start_1,outside_card_start_1,
            work_time_off_1,card_time_off_1,card_result_type_off_1,work_card_diff_off_1,outside_card_off_1,
            work_time_start_2,card_time_start_2,card_result_type_start_2,work_card_diff_start_2,outside_card_start_2,
            work_time_off_2,card_time_off_2,card_result_type_off_2,work_card_diff_off_2,outside_card_off_2,
            work_time_start_3,card_time_start_3,card_result_type_start_3,work_card_diff_start_3,outside_card_start_3,
            work_time_off_3,card_time_off_3,card_result_type_off_3,work_card_diff_off_3,outside_card_off_3
            ')
            ->where([
                'user_id' => $user_info['id'],
                'date' => $date_info['Ymd']
            ])
            ->find();

        if(!$day_record){
            // 判断是否为休息
            $day_type = OaCompany::getDayType($user_info['cmp_id'],$date_info['Ymd']);
            if(empty($day_type) || $day_type == OaDateTypeEnum::REST || $day_type == OaDateTypeEnum::CMP_REST || $day_type == OaDateTypeEnum::HOLIDAY){
                throw new Exception('not is work day', 300);
            }

            // TODO 在完成请假审批后, 需要将该代码删除
            // 判断是否为请假 以天为单位
            //  得到今天班次区间
//            $this_day_class_info = Db::name(TableName::OA_CLASS_NUM)->where([
//                'company_id' => $user_info['cmp_id'],
//                'week' => $date_info['week']
//            ])->find();
//            $this_day_class_num = $this_day_class_info['class_num'];
//            $this_day_start_time = $this_day_class_info['class_start_1'] + $date_info['timestamp'];
//            $this_day_end_time = $this_day_class_info['class_off_'.$this_day_class_num] + $date_info['timestamp'];

            //  判断该区间内是否有请假 (得到所有包含时间的请假数组)
//            $apply_list = Db::name(TableName::OA_APPLY_LEAVE)
//                ->field('apply_start_time, apply_end_time, no')
//                ->where([
//                    'user_id' => $user_info['id'],
//                    'status' => OaApplyStatusEnum::AGREE,
//                    'apply_start_time' => ['<=', $this_day_end_time],
//                    'apply_end_time' => ['>=' , $this_day_start_time]
//                ])->select();
//            if($apply_list){
//                $_leave_result = [];
//                for($i=1; $i<=$this_day_class_info['class_num']; $i++){ // 循环每一个班次
//                    foreach($apply_list as $apply){
//                        $_leave_result['class_num_'.$i]['leave'][$apply['no']] = Line::intersection($this_day_start_time, $this_day_end_time, $apply['apply_start_time'], $apply['apply_end_time'] );
//                        $_leave_result['class_num_'.$i]['work'][$apply['no']] = Line::difference($this_day_start_time, $this_day_end_time, $apply['apply_start_time'], $apply['apply_end_time'] );
//                    }
//                }
//                return ToApiFormat('this day has leave', $_leave_result, 318);
//            }

            // 判断大于今天还是小于今天
            $today_date = date('Ymd');
            if($date_info['Ymd'] > $today_date){
                throw new Exception('not found day record, this day has not arrived.', 316); // 未来
            }else if($date_info['Ymd'] < $today_date){
                throw new Exception('not found day record, lack of card', 317); // 过去缺卡
            }else{
                // 否则只剩今天
                throw new Exception('not found day record', 315);
            }
        }
        $respone_data = [];
        $respone_data['class_num'] = $day_record['class_num'];
        $respone_data['id'] = $day_record['id'];

        $day_record_not_null = false;
        for($i = 1; $i<=$day_record['class_num']*2; $i++){
            $type_name = OaClassNum::getClassNumName($i);

            if($day_record['card_result_type_'.$type_name] > 0){
                $day_record_not_null = true;
            }

            $respone_data[$type_name]['work_time'] = DateTime::secondToHis($day_record['work_time_'.$type_name]);
            $respone_data[$type_name]['card_time'] = DateTime::secondToHis($day_record['card_time_'.$type_name]);
            $respone_data[$type_name]['s_work_time'] = $day_record['work_time_'.$type_name];
            $respone_data[$type_name]['s_card_time'] = $day_record['card_time_'.$type_name];
            $respone_data[$type_name]['work_card_diff'] = $day_record['work_card_diff_'.$type_name];
            $respone_data[$type_name]['result_type'] = $day_record['card_result_type_'.$type_name];
            $respone_data[$type_name]['cn_type'] = $i;
            $respone_data[$type_name]['outside_card'] = $day_record['outside_card_'.$type_name];

            if( in_array($day_record['card_result_type_'.$type_name],[
                    OaCardTypeEnum::LACK_CARD,
                    OaCardTypeEnum::LATE,
                    OaCardTypeEnum::EARLY_RETREAT
                ])
            ){
                // 若当前状态是缺卡, 则找是否有该缺卡申请, 如果有,则表示申请中
                $lack_apply = Db::name(TableName::OA_APPLY_CARD)->field('id,status')->where([
                    'user_id' => $user_info['id'],
                    'date' => $date_info['Ymd']
                ])->order('create_time', 'desc')->find();
                if($lack_apply){
                    $respone_data[$type_name]['apply_status'] = $lack_apply['status'];
                    $respone_data[$type_name]['apply_id'] = $lack_apply['id'];
                }
            }
        }
        if(!$day_record_not_null){
            throw new Exception('not found day record', 315);
        }
        return ToApiFormat('ok', $respone_data);
    }

    /**
     * 得到月打卡记录
     * @throws
     */
    public function getMonthRecord()
    {
        $user_info = $this->getUserInfo();
        $request_params = $this->validateRequestParams(['date']);
        $date_info = DateTime::splitDate($request_params['date']);
        $month_result = OaCardRecord::getMonthStatisByUserId($user_info['id'],$date_info['Ym']);

        return ToApiFormat('ok', $month_result);
    }
    /**
     * 申请请假
     * @throws
     */
    public function applyLeave()
    {
        $user_info = $this->getUserInfo();
        $request_params = $this->validateRequestParams(['apply_start_time','apply_end_time','comment','photo_arr']);
        $start_timestamp = strtotime($request_params['apply_start_time']);
        $start_date = date('Ymd',$start_timestamp);
        $end_timestamp = strtotime($request_params['apply_end_time']);
        $end_date = date('Ymd',$end_timestamp);

        if($start_timestamp >= $end_timestamp){ // 用户填写错误 开始时间都大于了结束的时间
            return ToApiFormat('submit apply fail',305);
        }

        // 判断之前的申请是否在该范围内
        $before_data = Db::name(TableName::OA_APPLY_LEAVE)
            ->field('id')
            ->where([
                'user_id' => $user_info['id'],
                'apply_start_time|apply_end_time' => ['between', $start_timestamp.','.$end_timestamp],
            ])->find();
        if($before_data){
            throw new Exception('No repeat application has been applied', 306);
        }
        $data = [
            'user_id' => $user_info['id'],
            'user_name' => $user_info['name'],
            'company_id' => $user_info['cmp_id'],
            'org_id' => $user_info['org_id'],
            'no' => getRandom(),
            'apply_start_time' => $start_timestamp,
            'apply_end_time' => $end_timestamp,
            'diff_time' => $end_timestamp - $start_timestamp,
            'comment' => $request_params['comment'],
            'status' => 2, // 申请中
            'create_time' => time(),
            'start_date' => $start_date,
            'end_date' => $end_date
        ];
        if(!empty($request_params['photo_arr'])){
            $data['photo_json'] = json_encode($request_params['photo_arr'],JSON_UNESCAPED_SLASHES);
        }
        $result = Db::name(TableName::OA_APPLY_LEAVE)->insert($data);
        if($result){

            //发送给该公司管理员消息
            $admin_list = MessageReceiver::getServiceAdminByCmpID($user_info['cmp_id']);
            if($admin_list){
                $send_msg_data = [];
                foreach($admin_list as $admin){
                    $send_msg_data[] = [
                        'to_user_id' => $admin['id'],
                        'collector_name' => $user_info['name'],
                        'apply_type' => '请假'
                    ];
                }
                Message::send('collector_apply_work',$send_msg_data);
            }

            return ToApiFormat('ok');
        }else{
            return ToApiFormat('submit apply fail',305);
        }
    }

    /**
     * 申请补卡
     * @throws
     */
    public function applySupplementCard()
    {
        $user_info = $this->getUserInfo();
        $request_params = $this->validateRequestParams(['record_id','record_type','reason','photo_arr']);

        // 查找该记录是否存在
        $record_data = Db::name(TableName::OA_CARD_RECORD_DAY)->where([
            'id' => $request_params['record_id'],
            'user_id' => $user_info['id']
        ])->find();
        if(!$record_data){
            $card_record = new OaCardRecord();
            $record_data = $card_record->createAndInitDayRecord($user_info['id']);
//            throw new Exception('not found your card record', 309);
        }
        $type_name = OaClassNum::getClassNumName($request_params['record_type']);

        if(!empty($record_data['card_result_type_'.$type_name]) &&
            ( !in_array($record_data['card_result_type_'.$type_name],[
                    OaCardTypeEnum::LACK_CARD,
                    OaCardTypeEnum::LATE,
                    OaCardTypeEnum::EARLY_RETREAT
            ])) ){
            throw new Exception('this type is other status', 310);
        }

        $data = [
            'user_id' => $user_info['id'],
            'user_name' => $user_info['name'],
            'company_id' => $user_info['cmp_id'],
            'org_id' => $user_info['org_id'],
            'day_record_id' => $request_params['record_id'],
            'date' => $record_data['date'],
            'time' => $record_data['work_time_'.$type_name],
            'cn_type' => $request_params['record_type'],
            'no' => getRandom(),
            'comment' => $request_params['reason'],
            'status' => 2,
            'apply_type' => empty($record_data['card_result_type_'.$type_name])?OaCardTypeEnum::LACK_CARD:$record_data['card_result_type_'.$type_name],
            'create_time' => time()
        ];
        if(!empty($request_params['photo_arr'])){
            $data['photo_json'] = json_encode($request_params['photo_arr'],JSON_UNESCAPED_SLASHES);
        }
        $result = Db::name(TableName::OA_APPLY_CARD)->insert($data);
        if($result){

            //发送给该公司管理员消息
            $admin_list = MessageReceiver::getServiceAdminByCmpID($user_info['cmp_id']);
            if($admin_list){
                $send_msg_data = [];
                foreach($admin_list as $admin){
                    $send_msg_data[] = [
                        'to_user_id' => $admin['id'],
                        'collector_name' => $user_info['name'],
                        'apply_type' => '补卡'
                    ];
                }
                Message::send('collector_apply_work',$send_msg_data);
            }

            return ToApiFormat('ok');
        }else{
            return ToApiFormat('apply fail',311);
        }
    }

    /**
     * 考勤申请列表
     * @throws
     */
    public function applyList()
    {
        $user_info = $this->getUserInfo();
        $request_params = $this->validateRequestParams(['apply_status']);

        $where['user_id'] = $user_info['id'];
        if($request_params['apply_status']){
            $where['status'] = $request_params['apply_status'];
        }
        $card_apply_sql = Db::name(TableName::OA_APPLY_CARD)
            ->field('id,2 type,comment,create_time,status')
            ->where($where)
            ->buildSql();
        $leave_apply_sql = Db::name(TableName::OA_APPLY_LEAVE)
            ->field('id,1 type,comment,create_time,status')
            ->where($where)
            ->buildSql();
        $apply_sql = $card_apply_sql.' UNION '. $leave_apply_sql . 'order by create_time desc '.$this->paginate('',true);

        $result = Db::query($apply_sql);

        return ToApiFormat('ok', $result);
    }

    /**
     * 取消 补卡和请假申请
     * @throws
     */
    public function cancelApply()
    {
        $user_info = $this->getUserInfo();
        $request_params = $this->validateRequestParams(['apply_type', 'id']);

        if($request_params['apply_type'] == 1){// 请假
            $table_name = TableName::OA_APPLY_LEAVE;
        }else if($request_params['apply_type'] == 2){ // 补卡
            $table_name = TableName::OA_APPLY_CARD;
        }

        $apply_info = Db::name($table_name)
            ->field('status')
            ->where([
                'id'=> $request_params['id'],
                'user_id' => $user_info['id']
            ])->find();

        if(!$apply_info){
            throw new Exception('not found this apply',312);
        }
        if($apply_info['status'] != OaApplyStatusEnum::APPLYING){
            throw new Exception('this apply already have result',313);
        }
        if(Db::name($table_name)->where('id','=',$request_params['id'])->update(['status'=>4])){
            return ToApiFormat('ok');
        }else{
            throw new Exception('cancel apply fail',314);
        }
    }

    /**
     * 申请(补卡和请假)详情
     * @throws
     */
    public function applyDetail()
    {
        $user_info = $this->getUserInfo();
        $request_params = $this->validateRequestParams(['apply_type', 'id']);
        $where = [
            'id'=> $request_params['id'],
            'user_id' => $user_info['id']
        ];
        if($request_params['apply_type'] == 1){// 请假
            $table_name = TableName::OA_APPLY_LEAVE;
        }else if($request_params['apply_type'] == 2){ // 补卡
            $table_name = TableName::OA_APPLY_CARD;
        }
        $apply_detail = Db::name($table_name)
            ->where($where)
            ->find();

        if(!$apply_detail) {
            throw new Exception('not found',404);
        }

        if($request_params['apply_type'] == 2){ // 补卡时,添加特有信息
            $apply_detail['class_cn_name'] =
                DateTime::getChNameByDate($apply_detail['date']).
                '，'.
                OaClassNum::getClassNumChName($apply_detail['cn_type']).
                '，'.
                '补卡时间'.
                DateTime::secondToHis($apply_detail['time'],true,false);
        }
        $apply_detail['zh_create_time'] = date('Y年m月d日 H:i:s',$apply_detail['create_time']);
        if($apply_detail['approve_time']){
            $apply_detail['zh_approve_time'] =
                date('Y年m月d日 H:i:s',$apply_detail['approve_time']);
        }
        unset($apply_detail['user_id']);
        unset($apply_detail['company_id']);
        return ToApiFormat('ok',$apply_detail);
    }

    /**
     * 提交工作日报
     * @throws
     */
    public function submitDaily()
    {
        $user_info = $this->getUserInfo();
        $request_params = $this->validateRequestParams([
            'finish','unfinish','need_coordinate','other_content',
            'photo_arr','attachment_arr'
        ]);
        $today_date = DateTime::splitDate();
        $daliy_data = Db::name(TableName::OA_DAILY)->field('id')
            ->where([
                'user_id' => $user_info['id'],
                'date' => $today_date['Ymd']
            ])->find();
        if($daliy_data){
            throw new Exception('daily already existed',308);
        }
        $data = [
            'user_id' => $user_info['id'],
            'user_name' => $user_info['name'],
            'phone' => $user_info['phone'],
            'sex' => $user_info['sex'],
            'org_id' => $user_info['org_id'],
            'company_id' => $user_info['cmp_id'],
            'date' => $today_date['Ymd'],
            'finish' => $request_params['finish'],
            'unfinish' => $request_params['unfinish'],
            'need_coordinate' => $request_params['need_coordinate'],
            'comment' => $request_params['other_content'],
            'create_time' => time()
        ];
        if(!empty($request_params['photo_arr'])){
            $data['photo_json'] = json_encode($request_params['photo_arr'],JSON_UNESCAPED_SLASHES);
        }
        if(!empty($request_params['attachment_arr'])){
            $data['attachment_json'] = json_encode($request_params['attachment_arr'],JSON_UNESCAPED_SLASHES);
        }
        $result = Db::name(TableName::OA_DAILY)->insert($data);
        if($result){
            return ToApiFormat('ok');
        }else{
            return ToApiFormat('submit fail',307);
        }
    }

    /**
     * 日报列表
     * @throws
     */
    public function dailyList()
    {
        $user_info = $this->getUserInfo();
        $is_self = $this->validateRequestParams(['is_self']);
        if($is_self){
            $where['user_id'] = $user_info['id'];
        }
        $daily_list = Db::name(TableName::OA_DAILY)
            ->field('id, user_name, sex,date,finish')
            ->where('company_id','=',$user_info['cmp_id'])
            ->order('create_time','desc');
        $daily_list = $this->paginate($daily_list);
        return ToApiFormat('ok',$daily_list);
    }

    /**
     * 日报详情
     * @throws
     */
    public function dailyDetail()
    {
        $user_info = $this->getUserInfo();
        $request_params = $this->validateRequestParams(['id']);
        $daily_detail = Db::name(TableName::OA_DAILY)
            ->where([
                'id' => $request_params['id'],
                'company_id' => $user_info['cmp_id']
                ])
            ->find();
        return ToApiFormat('ok', $daily_detail);
    }

    /**
     * 获取企业OA考勤参数, 当前用户的公司打卡规定范围,地点,坐标和wifi名称,mac地址
     * @throws
     */
    public function getCmpOaAttendParams()
    {
        $user_info = $this->getUserInfo();
        $com_conf = Db::name(TableName::OA_COMPANY_CONFIG)
            ->field('card_address_range')
            ->where('company_id','=',$user_info['cmp_id'])
            ->find();
        $address_data = Db::name(TableName::OA_COMPANY_ATTEND_ADDRESS)
            ->field('latitude_and_longitude,detail_address')
            ->where('company_id','=',$user_info['cmp_id'])
            ->select();
        $wifi_data = Db::name(TableName::OA_COMPANY_ATTEND_WIFI)
            ->field('name,mac')
            ->where('company_id','=',$user_info['cmp_id'])
            ->select();
        return ToApiFormat('ok',compact('com_conf','address_data','wifi_data'));
    }

    /**
     * 打卡
     * @throws
     */
    public function doCard()
    {
        // 获取请求参数
        $request_params = $this->validateRequestParams(['coordinate','card_address','card_wifi_name','is_outside']);
        if(empty($request_params['card_address']) && empty($request_params['card_wifi_name'])){
            throw new Exception('card_address or card_wifi_name need least one of them',10);
        }
        $user_info = $this->getUserInfo(true);
        // 获取今日时间秒数 和 今日日期信息
        $now_time_secend = DateTime::hisToSecond();
        $today_info = DateTime::splitDate();

        // 1,判断今天是否有打卡记录
        $day_record = Db::name(TableName::OA_CARD_RECORD_DAY)
            ->where([
                'user_id' => $user_info['id'],
                'date' => $today_info['Ymd'],
            ])->find();
        if(!$day_record){
            // 2-1,如果没有打卡记录,判断今天是否为上班日期, 不是工作日则返回今天不能打卡
            $day_type = OaCompany::getDayType($user_info['cmp_id']);
            if(empty($day_type) || $day_type == OaDateTypeEnum::REST || $day_type == OaDateTypeEnum::CMP_REST || $day_type == OaDateTypeEnum::HOLIDAY){
                throw new Exception('not is work day', 300);
            }
            // 2-2,创建并且初始化打卡
            $card_record = new OaCardRecord();
            $card_record->createAndInitDayRecord($user_info['id']);
        }

        // 判断是否允许外勤打卡
        if($request_params['is_outside']){
            $cmp_conf = Db::name(TableName::OA_COMPANY_CONFIG)
                ->field('allow_outside_card')
                ->where('company_id','=',$user_info['cmp_id'])
                ->find();
            if(!$cmp_conf['allow_outside_card']){
                throw new Exception('not allow outside docard',303);
            }
            $do_card['is_outside'] = 1;
        }else{
            $do_card['is_outside'] = 0;
        }
        // 3,判断当前时间在哪个打卡区间内
        $do_card = $this->_getDoCardType($now_time_secend,$user_info['id'], $user_info['cmp_id'], $today_info);
        if(!$do_card){
            throw new Exception('already do card',302);
        }

        // 判断是否是给昨天打卡
        if(isset($do_card['is_yesterday']) && $do_card['is_yesterday']){
            $now_time_secend += DateTime::hisToSecond('24');
            $today_info = DateTime::splitDate('-1 day');

            $day_record = Db::name(TableName::OA_CARD_RECORD_DAY)
                ->where([
                    'user_id' => $user_info['id'],
                    'date' => $today_info['Ymd'],
                ])->find();
            if(!$day_record){
                $card_record = new OaCardRecord();
                $day_record = $card_record->createAndInitDayRecord($user_info['id'] , 1, $today_info['Ymd']);
            }
        }

        // 4,写入打卡记录数据
        $do_card_result = Db::name(TableName::OA_CARD_RECORD_DAY)
            ->where([
                'user_id' => $user_info['id'],
                'date' => $today_info['Ymd']
            ])
            ->update([
                'card_address_'.$do_card['cn_type_name'] => empty($request_params['card_address'])?'暂无':$request_params['card_address'],
                'card_wifi_name_'.$do_card['cn_type_name'] => empty($request_params['card_wifi_name'])?'暂无':$request_params['card_wifi_name'],
                'card_time_'.$do_card['cn_type_name'] => $now_time_secend,
                'card_equipment_'.$do_card['cn_type_name'] => (isset($_SERVER['HTTP_USER_AGENT']) && strlen($_SERVER['HTTP_USER_AGENT'])<250)?$_SERVER['HTTP_USER_AGENT']:'',
                'card_result_type_'.$do_card['cn_type_name'] => $do_card['result_code'],
                'outside_card_'.$do_card['cn_type_name'] => $request_params['is_outside'],
                'status' => 1
            ]);

        if($request_params['coordinate'] && $request_params['coordinate'] != ',' && $request_params['card_address']){
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
                'daily_address' => $request_params['card_address'],
                'comment' => '考勤打卡',
                'create_time' => $this->request->time(),
                'date' => date('Ymd',$this->request->time())
            ]);
        }
        if(!$do_card_result){
            throw new Exception('do card fail', 306);
        }

        // 若不是一天的第一次打卡, 检测之前所有打卡, 如果有没打卡的,需要设置打卡为0
        OaCardRecord::updateStatis($day_record['id']);

        return ToApiFormat('ok',$do_card);
    }

    /**
     * 预打卡信息
     * @throws
     */
    public function preDoCard()
    {
        $user_info = $this->getUserInfo();
        // 获取今日时间秒数 和 今日日期信息
        $now_time_secend = DateTime::hisToSecond();
        $today_info = DateTime::splitDate();

        $day_type = OaCompany::getDayType($user_info['cmp_id']);
        if(empty($day_type) || $day_type == OaDateTypeEnum::REST || $day_type == OaDateTypeEnum::CMP_REST || $day_type == OaDateTypeEnum::HOLIDAY){
            throw new Exception('not is work day', 300);
        }

        return ToApiFormat('ok', $this->_getDoCardType($now_time_secend,$user_info['id'], $user_info['cmp_id'], $today_info));
    }


    /**
     * 获取打卡类型 (私有方法)
     * @param $time int 打卡时间
     * @param $user_id int 用户id
     * @param $company_id int 公司id
     * @param $date_arr array 日期数据
     * @return array|boolean [cn_type_name,cn_type,result_code]
     * @throws
     */
    private function _getDoCardType($time,$user_id, $company_id, $date_arr)
    {
        // 获取班次数据
        $cn_info = Db::name(TableName::OA_CLASS_NUM)
            ->field('class_num,
                class_start_1,class_off_1,
                class_start_2,class_off_2,
                class_start_3,class_off_3,
                card_area_start_1,card_area_off_1,
                card_area_start_2,card_area_off_2,
                card_area_start_3,card_area_off_3') // 获取第一次允许打卡的范围card_area_start_1 ...
            ->where([
                'company_id' => $company_id,
                'week' => $date_arr['week']
            ])->find();

        if(!$cn_info){
            throw new Exception('class num info not found'.json_encode(compact('now_time','company_id','week')),301);
        }
        // 获取日数据
        $day_record = Db::name(TableName::OA_CARD_RECORD_DAY)
            ->where([
                'user_id' => $user_id,
                'date' => $date_arr['Ymd']
            ])->find();
        if($day_record){
            $cn_info['class_num'] =  $day_record['class_num'];
            $cn_info['class_start_1'] =  $day_record['work_time_start_1'];
            $cn_info['class_off_1'] =  $day_record['work_time_off_1'];
            $cn_info['class_start_2'] =  $day_record['work_time_start_2'];
            $cn_info['class_off_2'] =  $day_record['work_time_off_2'];
            $cn_info['class_start_3'] =  $day_record['work_time_start_3'];
            $cn_info['class_off_3'] =  $day_record['work_time_off_3'];
            $cn_info['card_area_start_1'] =  $day_record['card_area_start_1'];
            $cn_info['card_area_off_1'] =  $day_record['card_area_off_1'];
            $cn_info['card_area_start_2'] =  $day_record['card_area_start_2'];
            $cn_info['card_area_off_2'] =  $day_record['card_area_off_2'];
            $cn_info['card_area_start_3'] =  $day_record['card_area_start_3'];
            $cn_info['card_area_off_3'] =  $day_record['card_area_off_3'];
        }

        // 得到最大班次号, 确定所在班次区间
        $max_no = $this->_getClassNumMaxNo($time,$cn_info);

        // 如果时间区间在上班开始和下班结束,那么则为正常打卡
        // 需要判断该时间是否在上班打卡中
        if($max_no==1 || $max_no>($cn_info['class_num']*2) ){
            if($max_no>1){ // 当前的最后一次打卡(下班卡)
                $max_no -= 1;
            }else{ // 大清早,判断是给昨天打卡  还是给今天打卡
                if( $time < $cn_info['card_area_start_1'] ){ //还没有到今天的打卡时间,看看昨天
                    $lastday_record = Db::name(TableName::OA_CARD_RECORD_DAY)
                        ->where([
                            'user_id' => $user_id,
                            'date' => date('Ymd', strtotime($date_arr['Ymd'])-86400)
                        ])->find();
                    // 看看昨天最后一条打卡数据是否存在 ,没有就返回给昨天晚上打卡
                    if(!$lastday_record || !$lastday_record['card_time_'.OaClassNum::getClassNumName($cn_info['class_num']*2)]){
                        return [
                            'cn_type_name' => OaClassNum::getClassNumName($cn_info['class_num']*2),
                            'cn_type' => $cn_info['class_num']*2,
                            'result_code' => OaCardTypeEnum::NORMAL,
                            'is_yesterday' => true,
                            'need_card_time' => $cn_info['class_'.OaClassNum::getClassNumName($cn_info['class_num']*2)]
                        ];
                    }else{
                        return false; //昨天晚上已经打过卡了
                    }
                }
            }
            $cn_type_name = OaClassNum::getClassNumName($max_no);
            // 若有打卡记录,并且该时间已有数据, 则表示已经打卡
            if($day_record && $day_record['card_result_type_'.$cn_type_name]){
                return false;
            }
            return [
                'cn_type_name' => $cn_type_name,
                'cn_type' => $max_no,
                'result_code' => OaCardTypeEnum::NORMAL,
                'need_card_time' => $cn_info['class_'.$cn_type_name]
            ];
        }

        // 判断打卡时间左右是否有数据,有则返回已打卡
        $before_cn_type_name = OaClassNum::getClassNumName($max_no-1);
        $after_cn_type_name = OaClassNum::getClassNumName($max_no);
        if(!$day_record){
            $day_record['card_time_'.$before_cn_type_name] = '';
        }else if($day_record['card_result_type_'.$after_cn_type_name]){
            return false; // 已经打卡
        }

        if( $max_no%2 == 0){ // 上班
            // 判断应该归属地 若 [当前时间-上个时间 < 下个时间-上个时间/2] ,并且确定上个时间没有内容 则优先比对上个时间
            if( ( ($time-$cn_info['class_'.$before_cn_type_name]) < (($cn_info['class_'.$after_cn_type_name] - $cn_info['class_'.$before_cn_type_name])/2) ) ) {
                if(!$day_record['card_time_'.$before_cn_type_name]){ //若没有上个时间有数据 ,则表示需打卡
                    // (迟到)
                    return [
                        'cn_type_name' => $before_cn_type_name,
                        'cn_type' => $max_no-1,
                        'result_code' => OaCardTypeEnum::LATE,
                        'need_card_time' => $cn_info['class_'.$before_cn_type_name]
                    ];
                }

            }
            // (早退)
            return [
                'cn_type_name' => $after_cn_type_name,
                'cn_type' => $max_no,
                'result_code' => OaCardTypeEnum::EARLY_RETREAT,
                'need_card_time' => $cn_info['class_'.$after_cn_type_name]
            ];
        }else{ // 下班了
            $before_time = $cn_info['card_area_'.$before_cn_type_name];
            $after_time = $cn_info['card_area_'.$after_cn_type_name];

            if($time < $before_time){ // 在下班打卡时间范围内
                return [
                    'cn_type_name' => $before_cn_type_name,
                    'cn_type' => $max_no-1,
                    'result_code' => OaCardTypeEnum::NORMAL,
                    'need_card_time' => $cn_info['class_'.$before_cn_type_name]
                ];
            }else if($time > $after_time){ // 到了下一次允许打卡的时间范围内
                return [
                    'cn_type_name' => $after_cn_type_name,
                    'cn_type' => $max_no,
                    'result_code' => OaCardTypeEnum::NORMAL,
                    'need_card_time' => $cn_info['class_'.$after_cn_type_name]
                ];
            }else{
                return false; // 不在下次打卡允许的时间内
            }
        }
    }


    /**
     * 获取打卡类型2 (私有方法) 对getDoCardType的改进 后期有时间在做
     * @param $time int 打卡时间
     * @param $user_id int 用户id
     * @param $company_id int 公司id
     * @param $date_arr array 日期数据
     * @return array|boolean [cn_type_name,cn_type,result_code]
     * @throws
     */
//    private function _getDoCardType2($time,$user_id, $cn_info)
//    {
//        // 进入判断前的操作
//
//        // 业务标记,将最后一半次的下班打卡允许范围延长到明天
//        if($cn_info['card_area_off_'.$cn_info['class_num']] < $cn_info['class_off_'.$cn_info['class_num']]){
//            // 最后打卡时间小于 最后下班时间 , 则需要将最后打卡时间设置为第二天
//            $cn_info['card_area_off_'.$cn_info['class_num']] .= 86400;
//        }
//
//        // 进入逻辑核心 - 判断当前时间应该在的范围
//        $card_result = [];
//        for($now_cn = 1; $now_cn<=$cn_info['class_num']; $now_cn++){ // 对每一个班次进行排查
//            // 若打卡时间小于当前班次, 直接退出
//            if($time < $cn_info['card_area_start_'.$now_cn]){
//                break;
//            }
//
//            // 若不在该打卡时间段内, 就继续进入到下个该打卡的时间段
//            if($time > $cn_info['card_area_off_'.$now_cn]){
//                continue;
//            }
//
//            // 进入正常判断逻辑
//            $midpoint_time = $cn_info['class_start_'.$now_cn] +
//                (int)(($cn_info['class_off_'.$now_cn]-$cn_info['class_start_'.$now_cn])/2);
//
//            if($time < $cn_info['class_start_'.$now_cn] ||
//                $after_work = ($time > $cn_info['class_off_'.$now_cn]) // 标识 是否是下班后
//            ){ // 上班前打卡
//                if( isset($after_work) ){ // 下班打卡时间
//                    $cn_type = ( ($now_cn-1)*2 ) + 2;
//                    $nct = $cn_info['class_off_'.$now_cn];
//                }else{ // 上班打卡时间
//                    $cn_type = ( ($now_cn-1)*2 ) + 1;
//                    $nct = $cn_info['class_start_'.$now_cn];
//                }
//                $card_result = [
//                    'cn_type_name' => OaClassNum::getClassNumName($cn_type),
//                    'cn_type' => $cn_type,
//                    'result_code' => OaCardTypeEnum::NORMAL,
//                    'need_card_time' => $cn_info['class_start_'.$now_cn]
//                ];
//            }elseif($time < $midpoint_time){ // 在上班中吗 - 前半段
//
//            }elseif($time < $cn_info['class_off_'.$now_cn]){ // 在上班中吗 - 后半段
//
//            }
//        }
//        return $card_result;
//    }

    /**
     * 得到 某秒数 在班次区间中 最大班次编号 (私有方法)
     * @param $now_time int 需要检测时间 (今天0点到现在的时间戳)
     * @param $cn_info array 班次信息数据 需要包含数据(class_num,class_start_1,class_off_1,class_start_2,class_off_2,class_start_3,class_off_3)
     * @return int 例如:4,表示该时间在 3-4之间
     * @throws
     */
    private function _getClassNumMaxNo($now_time, $cn_info)
    {
        // 循环找到当前时间所在班次时间之前后
        for($i=1;$i<=$cn_info['class_num']*2 ;$i++){
            if($now_time<=$cn_info['class_'.OaClassNum::getClassNumName($i)]){
                break;
            }
        }
        return $i;
    }
}
