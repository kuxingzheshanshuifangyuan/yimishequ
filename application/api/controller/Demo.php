<?php
namespace app\api\controller\v1;

use think\Db;
use \app\api\exception\BaseException as Exception;
use \app\common\enum\TableName;
use \app\common\service\Message;


class Demo extends Base{

	// 校验参数规则数组
	protected $validate_param_rules = [
		'type' => ['类型', 'require|between:1,3' ,5],
		'id' => ['ID','number'],
		'email' => ['邮箱','email'],
		'phone'=> ['手机号','require|isMobile'],
		'password' => ['密码', 'require|length:8,16'],
		'password2' => ['重复密码','require|confirm:password']
	];
	// 其他验证类型可以在本demo最后或者tp5文档中查看

    /**
     * 例子:
     * @param $type
     * @return array
     * @throws
     */
	public function lists($type)
	{
		// 请求数据验证 例如：
		$params = $this->validateRequestParams(['id','email','phone','password','password2']);
		// 验证传入数据是否通过验证
		$this->validateParams(['type'=>$type]);

		// 身份校验及获取身份信息
        $this->checkToken(); // 若身份校验失败，直接返回未登录错误
		$user_info = $this->getUserInfo();  // 获取当前用户信息

		// 数据库操作 分页
		$query_obj = Db::name(TableName::DEMO)->where();
		$result = $this->paginate($query_obj);

		// 抛出异常 返回给客户端
		throw new Exception('demo test error', 999);

		// 发送消息
        Message::send('batch_back', [
            [
                'to_user_id'=>1,
                'service_name' => 'yaoyao\'s company',
                'batch_no' => 'ajsdfasoifjnwse'
            ],
            [
                'to_user_id'=>2,
                'service_name' => 'yaoyao\'s company',
                'batch_no' => 'ajsdfasoifjnwse'
            ]
        ]);

		// 返回正确信息并且携带数据
		$result = [];
		return ToApiFormat('demo end',compact('result'));
	}

	/*
	 * @throws
	 */
	public function test()
    {
        echo $this->request->time();
        exit;
//        echo strtotime('2018-05-08 09:00'), '<br>',strtotime('2018-05-09 18:00');
//        exit;
//        OaCompany::initCompanyOaData(173);
        echo '<pre>';
//        OaCardRecord::updateStatisById(2);
//        OaCardRecord::updateStatisById(3);
//        OaCardRecord::updateStatisById(4);
//        OaCardRecord::runStatisDayRecord();

//        var_dump(OaCardRecord::createMonthStatisByCmpId(173,'201805'));

        var_dump(OaCardRecord::setMonthStatisByUserId(294,'201805'));
        var_dump(OaCardRecord::setMonthStatisByUserId(294,'201804'));

//        var_dump(OaCardRecord::getMonthStatisByUserId(294,201805));
//        var_dump(OaCardRecord::runStatisLastMonthRecord());

    }
}

	// thinkphp5自带验证类型 
	// require 
	// number|integer float 
	// boolean 
	// email 
	// array 
	// accepted(验证是否是 yes|no 在用户确认条款时有用) 
	// date 
	// dateFormat:format（是否为指定日期格式：'create_time'=>'dateFormat:y-m-d'）

	// alpha (字母)
	// alphaNum (字母+数字)
	// alphaDash （字母+数字+下划线+破折号-）
	// chs（汉字）
	// chsAlpha（汉字+字母）
	// chsAlphaNum（汉字+字母+数字）
	// chsDash（汉字+字母+数字+下划线+破折号-）
	// activeUrl（有效的域名或ip）
	// url（有效的url)
	// ip(有效的ip)

	// in（字段值是否在某范围：'num'=>'in:1,2,3'）
	// between（数字在某范围中间：'num'=>'between:1,10'）
	// notBetween（不在）
	// length:num1,num2（字符长度：'name'=>'length:4,25' 若一个参数，为指定长度）
	// min:string1 max:string1
	// after:日期 (某时间开始之后:'begin_time' => 'after:2016-3-18',)
	// before:日期 (某时间结束之前:'end_time' => 'before:2016-10-01',)

	// different 与confirm相反
	// eq 或者 = 或者 same 
	// egt 或者 >=
	// gt 或者 >
	// elt 或者 <=
	// lt 或者 <