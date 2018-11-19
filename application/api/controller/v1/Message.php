<?php
namespace app\api\controller\v1;
use think\Db;
use \app\xgy_api\exception\BaseException as Exception;
use \app\common\enum\TableName;
use \app\common\model\Message as MessageModel;

class Message extends Base{

	// 校验参数规则数组
	protected $validate_param_rules = [
		'type' => ['消息类型', 'require|between:2,3'],
        'no' => ['消息编号', 'require']
	];

	/**
	 * 获取当前未读消息列表
	 */
	public function getUnreadType()
	{
		$user_id = $this->getUserInfo()['id'];
        $unread_class_list = MessageModel::getUnreadClassListByUserID($user_id);
		return ToApiFormat('ok',$unread_class_list);
	}

	/**
	 * 读取某类消息
	 */
	public function readMessage()
	{
		$params = $this->validateRequestParams(['type']);
		$user_id = $this->getUserInfo()['id'];

		$where = [
			'user_id' => $user_id,
			'type' => $params['type']
		];

		$msg = Db::name(TableName::PLATFORM_MESSAGE)->field('message,create_time,no')->where($where)->order('create_time','desc');

		$msg = $this->paginate($msg);

        // 如果是第一页, 判断若有未读, 则修改其状态为已读
        list($size, $page) = $this->getPaginateParams();
        if($page == 1){
            MessageModel::markedRead($user_id,$params['type']);
        }
		return ToApiFormat('ok',$msg);
	}

	/**
     * 删除消息
     */
	public function delMessage()
    {
        $user_info = $this->getUserInfo();
        $request_params = $this->validateRequestParams(['no']);

        $result = MessageModel::delMessage($user_info['id'], $request_params['no']);
        if($result){
            return ToApiFormat('ok');
        }else{
            throw new Exception('delete message fail', 803);
        }
    }
}