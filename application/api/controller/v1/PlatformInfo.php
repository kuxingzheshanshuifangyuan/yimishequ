<?php

namespace app\api\controller\v1;

use think\Db;
use \app\xgy_api\exception\BaseException as Exception;
use \app\common\enum\TableName;

class PlatformInfo extends Base
{
    // 校验参数规则数组
    protected $validate_param_rules = [
        'id' => ['ID', 'require|number'],
    ];

    public function aboutUs()
    {
        $result = Db::name(TableName::ABOUT_US)->field('version, content')->find();
        return ToApiFormat('ok', $result);
    }

    public function questionList()
    {
        $result = Db::name(TableName::COMMON_QUESTION)->where('title', '=', '手机端')->find();
        if (!$result) {
            throw new Exception("not found this common_question", 900);
        }
        return ToApiFormat('ok', $result);
    }

    /**
     * 催收技巧
     * @return array
     * @throws
     */
    public function skill()
    {
        $skill_list = Db::name(TableName::COLLECT_SKILL)->field('id, title');
        return ToApiFormat('ok', $this->paginate($skill_list));
    }

    /**
     * 催收技巧详情
     * @throws
     */
    public function skillDetail()
    {
        $request_params = $this->validateRequestParams(['id']);
        $skill = Db::name(TableName::COLLECT_SKILL)->where('id','=',$request_params['id'])->find();
        return ToApiFormat('ok', $skill);
    }
}