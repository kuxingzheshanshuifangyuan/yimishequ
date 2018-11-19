<?php

/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/7/21
 * Time: 9:09
 */
namespace app\common\vendor\task;

interface ITask
{
    public function meta();
    public function run($taskParams);
}