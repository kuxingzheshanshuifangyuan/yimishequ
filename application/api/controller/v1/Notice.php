<?php
namespace app\api\controller\v1;

use think\Db;

use \app\xgy_api\exception\BaseException as Exception;
use \app\common\enum\TableName;
use \app\common\model\Notice as NoticeModel;

class Notice extends Base{

    // 校验参数规则数组
    protected $validate_param_rules = [
        'notice_id' => ['公告ID', 'require'],
    ];

    /**
     * 读取公告列表
     * @throws
     */
    public function lists()
    {
        $user_info = $this->getUserInfo();
        $result = Db::name(TableName::PLATFORM_NOTICE)
            ->field('id,title,create_time')
            ->where('status','=',NoticeModel::STATUS_SHOW)
            ->order('create_time', 'desc');

        $result = $this->paginate($result);

        if(isset($result['data']) && $result['data']){
            foreach($result['data'] as $k => &$item){
                $result['data'][$k]['is_read'] = NoticeModel::isRead($user_info['id'], $item['id']);
                $result['data'][$k]['create_time'] = date('Y-m-d H:i:s', $result['data'][$k]['create_time']); // yaoyao180628 因前端无法修改时间搓修改接口
            }
        }
        return ToApiFormat('ok',$result);
    }
    /**
     * 读取公告内容
     */
    public function read()
    {
        $user_id = $this->getUserInfo()['id'];
        $request_params = $this->validateRequestParams(['notice_id']);
        $notice = Db::name(TableName::PLATFORM_NOTICE)
            ->field('id,title,content,create_time')
            ->where('id','=',$request_params['notice_id'])
            ->find();
        if(!$notice){
            throw new Exception('not found this notice', 805);
        }
        NoticeModel::markedRead($user_id,$notice['id']);

        $notice['create_time'] = date('Y-m-d H:i:s', $notice['create_time']);
        return ToApiFormat('ok',$notice);
    }



}