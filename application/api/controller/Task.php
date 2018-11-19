<?php

namespace app\api\controller;

use app\common\model\Cate;
use think\Controller;
use think\Db;
use \app\api\exception\BaseException as Exception;
use \app\common\service\Vercode;
use think\cache;

class Task extends Base
{

    // 校验参数规则数组
    protected $validate_param_rules = [
//        'phone' => ['手机号', 'require|isMobile'],
//        'account'   => ['手机号', 'require|isMobile'],
//        'password'  => ['密码', 'require|length:32'],
//        'newPass'  => ['新密码', 'require|length:32'],
//        'password2' => ['重复密码', 'require|confirm:password'],
//        'verifyCode'=> ['验证码', 'require'],
//        'is_register' => ['是否注册操作', 'boolean', false]
    ];


    /**
     * 任务列表
     * @author GuoLin
     * @createdate 2018-08-22
     *
     */
    public  function taskList(){

        $userId = $this->checkToken();

        $userGroupId = Db::name('user')->where(['id'=>$userId])->value('usergrade');

        $signEexist = Db::name('sign_record')->where(['uid'=>$userId,'create_time'=>['>=',strtotime(date('Y-m-d'))]])->find();

        //新手任务
        $newbieTask = Db::name('task')->where(['task_type' => 2,'status'=>1])->select();

        $additionalTask = Db::name('task')->field('id,task_name,task_point,task_money')->where(['task_type'=>3,'status'=>1])->select();

        foreach ($newbieTask as $key => $value) {
            $newbieTask[$key]['taskStatus'] = 0;
            $newbieTask[$key]['taskNumber'] = 0;
            $result = getTaskStatus($value,$userId,$userGroupId);

            if ($result === true) {
                $newbieTask[$key]['taskStatus'] = 1;
            }

            $newbieTask[$key]['taskNumber'] = $result;
        }

        //每日任务
        $day_task = Db::name('task')->where(['task_type' => 1,'status'=>1])->limit(7)->select();

        foreach ($day_task as $key => $value) {
            $day_task[$key]['taskStatus'] = 0;
            $day_task[$key]['taskNumber'] = 0;
            $result = getTaskStatus($value,$userId,$userGroupId);
            if ($result === true) {
                $day_task[$key]['taskStatus'] = 1;
            }
            $day_task[$key]['taskNumber'] = $result;
        }

        $signStatus = $signEexist?1:0;

        return ToApiFormat('success',compact('newbieTask', 'day_task','additionalTask','signStatus'));

    }





    /**
     * 签到
     * @author GuoLin
     * @createdate 2018-08-23
     *
     */
    public function sign()
    {

        $userId = $this->checkToken();

        $today_exist = Db::name('sign_record')->where(['uid'=>$userId,'create_time'=>['>=',strtotime(date('Y-m-d'))]])->find();

        if($today_exist){
            throw new Exception('您今天已经签到过了', 1);
        }

        $system = Db::name('system')->where('name', 'operate')->find()['value'];

        $system = unserialize($system);

        if(!$system['sign_min'] || $system['sign_min'] < 1){
            throw new Exception('签到未开启', 2);
        }

        $point = mt_rand($system['sign_min'],$system['sign_max']);
        $money = mt_rand($system['sign_jinbi_min'],$system['sign_jinbi_max']);

        $data = [
            'uid'           => $userId,
            'point'         => $point,
            'money'         => $money,
            'create_time'   => time()
        ];

        Db::startTrans();
        try{
            Db::name('sign_record')->insert($data);
            Db::name('user')->where(['id'=>$userId])->setInc('point',$point);
            Db::name('user')->where(['id'=>$userId])->setInc('money',$money);
            if($point){
                addMoneyRecord($userId,'完成签到的奖励',$point,1,0);
            }
            if($money){
                addMoneyRecord($userId,'完成签到的奖励',$money,2,0);
            }
            autoUpGrade($userId);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            return ToApiFormat($e->getMessage(),[],3);
        }

        return ToApiFormat('签到成功,获得'. $point . $system['jifen_name'],['point'=>$point]);

    }


    /**
     * 获取当前任务状态
     * @author GuoLin
     * @createdate 2018-08-22
     *
     */
    public function getTaskStatus($taskParams,$userId,$userGroupId){

        if(!$taskParams){
            return false;
        }

        if(!$userId){
            return false;
        }

        if($taskParams['task_allow_group'] != ''){
            // 判断下用户组
            $allowGroups = explode(',', $taskParams['task_allow_group']);
            if (in_array($userGroupId, $allowGroups)) {
                return false;
            }
        }

        $prevTask = Db::name('UserTask')->where(array(
            'uid' => $userId,
            'task_id' => $taskParams['id'],
        ))->order('id desc')->find();

        if ($prevTask) {

            switch ($taskParams['task_type']) {
                case 1:     //每日任务
                    $count = Db::name('UserTask')->where(array(
                        'uid' => $userId,
                        'task_id' => $taskParams['id'],
                    ))->where(array(
                        'create_time' => [
                            '>=',strtotime(date('Y-m-d'))
                        ]
                    ))->count();
                    if($count >= $taskParams['task_per']){
                        return true;
                    }else{
                        return $count;
                    }
                    break;
                case 2:     //新手任务
                    $count = Db::name('UserTask')->where(array(
                        'uid' => $userId,
                        'task_id' => $taskParams['id'],
                    ))->count();
                    if($count >= $taskParams['task_per']){
                        return true;
                    }else{
                        return $count;
                    }
                    break;
                default:
                    return '参数异常';
            }
        }else{
            return 0;
        }
    }

    /**
     * 本月签到全勤奖励(米币)
     * @author GuoLin
     * @createdate 2018-10-10
     *
     */
    public function monthlyAttendanceAward(){

        $userId = $this->checkToken();

        $needTime  = strtotime(date('Y-m-d', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600)));
        $signCount = Db::name('sign_record')->where(['uid'=>$userId,'create_time'=>['>=',$needTime]])->count();
        $money = 1;

        if($signCount !=  date('t', strtotime('Y-m'))) {
            throw new Exception('未满足奖励领取条件', 2);
        }

        $result = Db::name('user')->where(['id'=>$userId])->setInc('money',$money);

        if($result){
            addMoneyRecord($userId,'签到全勤奖励',$money,2,0);
        }else{
            throw new Exception('领取失败', 3);
        }

    }


    public function todayTaskTotalAward(){

    }



}