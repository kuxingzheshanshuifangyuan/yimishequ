<?php

/**
 * 每日任务 新手任务
 * @author GuoLin
 * @createdate 2018-07-27
 *
 */
namespace app\common\vendor\task;

use think\db;

class Task implements ITask
{
    public function meta()
    {
        // TODO: Implement meta() method.
    }

    public function run($taskParams)
    {
        $count = 0;
        // 开始查询任务
        $prevTask = Db::name('UserTask')->where(array(
            'uid' => $taskParams['userId'],
            'task_id' => $taskParams['id'],
        ))->order('id desc')->find();

        if ($prevTask) {
            //判断是否为新手任务
            switch ($taskParams['task_type']) {

                case 1:     //每日任务
                    $count = Db::name('UserTask')->where(array(
                        'uid' => $taskParams['userId'],
                        'task_id' => $taskParams['id'],
                    ))->where(array(
                        'create_time' => [
                            '>=',strtotime(date('Y-m-d'))
                        ]
                    ))->count();
                    if($count >= $taskParams['task_per']){
                        return '次数天数中的最大限制了';
                    }
                    break;
                case 2:     //新手任务
                    $count = Db::name('UserTask')->where(array(
                        'uid' => $taskParams['userId'],
                        'task_id' => $taskParams['id'],
                    ))->count();
                    if($count >= $taskParams['task_per']){
                        return '次数天数中的最大限制了';
                    }
                    break;
                case 3:     //额外任务
                    $count = 0;
                    break;
                default:
                    return '参数异常';
            }
        }

        // 开始绑定
        $user = Db::name('User')->where(array(
            'id' => $taskParams['userId']
        ))->find();

        if ($user) {

            $surplus = $taskParams['task_per'] - $count;
            if($surplus === 1 || $taskParams['task_per'] == 1){

                Db::name('User')->where(['id' => $taskParams['userId']])->setInc('point',$taskParams['task_point']);

                Db::name('User')->where(['id' => $taskParams['userId']])->setInc('money',$taskParams['task_money']);

                Db::name('UserTask')->insert([
                    'uid'           =>  $taskParams['userId'],
                    'task_id'       =>  $taskParams['id'],
                    'task_status'   =>  2,
                    'user_point'    =>  $taskParams['task_point'],
                    'user_money'    =>  $taskParams['task_money'],
                    'create_time'   =>  time()
                ]);

                if($taskParams['task_point']){
                    addMoneyRecord($taskParams['userId'],'完成'.$taskParams['task_name'].'的任务奖励',$taskParams['task_point'],1,1);
                }

                if($taskParams['task_money']){
                    addMoneyRecord($taskParams['userId'],'完成'.$taskParams['task_name'].'的任务奖励',$taskParams['task_money'],2,1);
                }

                autoUpGrade($taskParams['userId']);

            }else{
                Db::name('UserTask')->insert([
                    'uid'           =>  $taskParams['userId'],
                    'task_id'       =>  $taskParams['id'],
                    'task_status'   =>  1,
                    'create_time'   =>  time()
                ]);
            }



            return true;
        } else {
            return '当前操作用户不存在';
        }
    }
}