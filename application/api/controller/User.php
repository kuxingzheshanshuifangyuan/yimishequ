<?php

namespace app\api\controller;

use app\common\model\Cate;
use think\Controller;
use think\Db;
use \app\api\exception\BaseException as Exception;
use \app\common\service\Vercode;
use think\cache;

class User extends Base
{

    // 校验参数规则数组
    protected $validate_param_rules = [
        'phone'      => ['手机号', 'require|isMobile'],
        'account'    => ['手机号', 'require|isMobile'],
        'password'   => ['密码', 'require|length:32'],
        'newPass'    => ['新密码', 'require|length:32'],
        'password2'  => ['重复密码', 'require|confirm:password'],
        'verifyCode' => ['验证码', 'require'],
        'page'       => ['页码','integer'],
        'username'   => ['用户名','require|length:1,20'],
        'description'=> ['个性签名','require|length:1,40'],
        'sex'        => ['性别','require|between:0,3'],
        'openid'     => ['openid','require'],
        'unionid'    => ['unionid','require']
    ];

    /**
     * 用户登陆
     * @author GuoLin
     * @createdate 2018-08-16
     *
     */
    public function login()
    {
        if ($this->request->isPost()) {

            $params = $this->validateRequestParams(['account','password']);

            $userData = Db::name('user')
                ->field('id as userid,username,point,money,mobile,sex,status,password,salt,userhead,regtime,is_username')
                ->where('usermail|username|mobile', $params['account'])
                ->find();

            if ($userData) {

                if($userData['status'] == 0){
                    throw new Exception('账号已被禁止登陆',1);
                }

                if ($userData['password'] == md5($params['password'])) {

                    unset($userData['salt']);
                    unset($userData['password']);

                    // 支持同一账号多次登录， 使用同一token
                    if( ($token = $this->getTokenByDatabase($userData['userid'])) ){

                        try{
                            $this->checkToken($token);
                        }catch (\Exception $e){

                            $token = $this->setToken($userData['userid']);
                        }
                    }else{
                        $token = $this->setToken($userData['userid']);
                    }

                    if ($userData['userhead'] == '') {
                        $userData['userhead'] = '/public/images/default.png';
                    }

                    Db::name('user')->update(
                        [
                            'last_login_time' => time(),
                            'last_login_ip' => $this->request->ip(),
                            'id' => $userData['userid']
                        ]
                    );

                    autoUpGrade($userData['userid']);
                    return ToApiFormat('success',compact('userData','token'));
                } else {

                    throw new Exception('密码错误',1);
                }
            } else {

                throw new Exception('该账号还未注册',1);
            }
        }

    }

    /**
     * 验证码登陆
     * @author GuoLin
     * @createdate 2018-08-17
     *
     */
    public function smsLogin(){


        $params = $this->validateRequestParams(['phone','verifyCode']);

        if(Vercode::verification($params['phone'],$params['verifyCode']) == false){
            throw new Exception('验证码有误',1);
        };

        $userData = Db::name('user')
            ->field('id as userid,username,point,money,mobile,sex,status,password,salt,userhead,is_username')
            ->where('mobile', $params['phone'])
            ->find();

        if ($userData) {

            if($userData['status'] == 0){
                throw new Exception('账号已被禁止登陆',1);
            }

                // 支持同一账号多次登录， 使用同一token
                if( ($token = $this->getTokenByDatabase($userData['userid'])) ){

                    try{
                        $this->checkToken($token);
                    }catch (\Exception $e){

                        $token = $this->setToken($userData['userid']);
                    }
                }else{
                    $token = $this->setToken($userData['userid']);
                }

                if ($userData['userhead'] == '') {
                    $userData['userhead'] = '/public/images/default.png';
                }

                Db::name('user')->update(
                    [
                        'last_login_time' => time(),
                        'last_login_ip' => $this->request->ip(),
                        'id' => $userData['userid']
                    ]
                );

                autoUpGrade($userData['userid']);

                return ToApiFormat('success',compact('userData','token'));
            } else {

            throw new Exception('该账号还未注册',1);
        }


    }


    /**
     * 用户注册
     * @author GuoLin
     * @createdate 2018-08-16
     *
     */
    public function register()
    {
//        $config = Cache::get('site_config');

        if ($this->request->isPost()) {
//            if (!$config['user_reg']) {
//                throw new Exception('网站已关闭会员注册功能',1);
//            }

            $params = $this->validateRequestParams(['phone','password','verifyCode','tid']);

            if(Vercode::verification($params['phone'],$params['verifyCode']) === false){
                throw new Exception('验证码有误',1);
            };

            $isExist = Db::name('user')->where('mobile',$params['phone'])->find();

            if($isExist){
                throw new Exception('该手机号已被注册',1);
            }

            $userGradeId = Db::name('usergrade')->order(['score'=>'asc'])->value('id') ?:0;

            $insertData = [
                'regtime'    => time(),
                'mobile'     => $params['phone'],
//                'grades'     => 0,
                'status'     => 1,//config('web.WEB_REG');
                'point'      => 0,//config('point.REG_POINT');
                'userhead'   => '/public/images/default.png',
                'userip'     => $this->request->ip(),
                'password'   => md5($params['password']),
                'usergrade'  => $userGradeId,
                'tid'        => $params['tid']?:0,
                'is_username'=> 0
            ];

            $userId = Db::name('user')->insertGetId($insertData);

            if($userId){
                $userName = '米友'.$userId;
                Db::name('user')->where('id',$userId)->setField('username',$userName);

                $userData = [
                    'userid'     => $userId,
                    'status'     => $insertData['status'],
                    'point'      => $insertData['point'],
                    'money'      => 0,
                    'username'   => $userName,
                    'userhead'   => $insertData['userhead'],
                    'sex'        => 3,
                    'mobile'     => $insertData['mobile'],
                    'regtime'    => $insertData['regtime'],
                ];


                Db::name('user')->update(
                    [
                        'last_login_time'=> time(),
                        'last_login_ip'  => $insertData['userip'],
                        'id'             => $userId
                    ]
                );

                if($params['tid']){
                    $isUser = Db::name('user')->where(['id'=>$params['tid']])->find();
                    if($isUser){
                        $site_config = Db::name('system')->field('value')->where('name', 'operate')->value('value');
                        $site_config = unserialize($site_config);
                        $invite_friends = $site_config['invite_friends'];

                        Db::name('invite_record')->insert([
                            'uid'          => $userId,
                            'tid'          => $params['tid'],
                            'money'        => $invite_friends,
                            'create_time'  => time()
                        ]);

                        Db::name('user')->where(['id'=>$params['tid']])->setInc('money',$invite_friends);

                        addMoneyRecord($params['tid'],'成功邀请好友',$invite_friends,2,7);
                    }

                }

                runTask(4,$userId,$userGradeId);
                addMessageRecord($userId,$userId,'','一米社区欢迎您的加入');

                $token = $this->getTokenByDatabase($userData['userid']);

                // 支持同一账号多次登录， 使用同一token
                if($token){
                    try{
                        $this->checkToken($token);
                    }catch (\Exception $e){

                        $token = $this->setToken($userData['userid']);
                    }
                }else{
                    $token = $this->setToken($userData['userid']);
                }

                $userData['is_username'] = 0;
                return ToApiFormat('success',compact('userData','token'));
            }else{

                throw new Exception('注册失败',1);
            }

        }
    }

    /**
     * 重置密码
     * @author GuoLin
     * @createdate 2018-08-17
     *
     */
    public function rePass()
    {

        $params = $this->validateRequestParams(['phone','newPass','verifyCode']);

        if(Vercode::verification($params['phone'],$params['verifyCode']) === false){
            throw new Exception('验证码有误',1);
        };


        $userData = Db::name('user')->where('mobile',$params['phone'])->find();

        if(!$userData){
            throw new Exception( '该手机号还未注册',1);
        }

        $newPass = md5($params['newPass']);

        $result = Db::name('user')->where('id',$userData['id'])->update(['password'=>$newPass]);

        if($result !== false){

            return ToApiFormat('success');
        }else{

            throw new Exception('密码修改失败，请稍后重试',1);
        }

    }

    /**
     * 检测用户状态
     */
    public function checkUserStatus($status)
    {
        switch ($status) {
            case '0':
                throw new Exception('user not able', 106);
                break;

            case '1':
                return true;
                break;

            case '2':
                throw new Exception('user lock', 107);
                break;

            default:
                throw new Exception('not define user status : ' . $status, 108);
                break;
        }
    }

    /**
     * 发送验证码
     * @throws
     */
    public function sendVercode()
    {
        $params = $this->validateRequestParams(['phone']);

        try {
            $code = Vercode::sendVer($params['phone']);

        } catch (\Exception $e) {

            throw new Exception($e->getMessage(), $e->getCode());
        }

        return ToApiFormat('vercode sended');
    }


    /**
     * 我的信息
     * @author GuoLin
     * @createdate 2018-08-21
     *
     */
    public function isUser(){

        $params = $this->validateRequestParams(['phone']);

        $isUser = Db::name('user')->field('id')->where(['mobile'=>$params['phone']])->find();

        if(!$isUser){
            return ToApiFormat('success');
        }else{
            throw new Exception('该手机号已被注册', 1);
        }
    }


    /**
     * 我的信息
     * @author GuoLin
     * @createdate 2018-08-21
     *
     */
    public function UserInfo(){

        if(!$this->request->isPost()){
            throw new Exception('非法请求', 1);
        }

        $userId = $this->checkToken();

        $userData = Db::name('user u')
            ->join('usergrade g','g.id = u.usergrade')
            ->field('u.id,u.username,u.userhead,u.point,u.money,u.description,u.usergrade,g.name as gradename,u.regtime,sex,is_username')
            ->where(['u.id'=>$userId])
            ->find();

        $userData['forumTotal'] = Db::name('forum')
            ->alias('f')
            ->join('user u', 'f.uid=u.id')
            ->where(['u.id' => $userId, 'f.open' => 1])
            ->order('f.create_time desc')
            ->count();

        $userData['messagesTotal']  = Db::name('message')
            ->alias('me')
            ->join('user u', 'me.uid=u.id', 'LEFT')
            ->where('me.touid', $userId)
//            ->whereOr('me.uid', $userId)
            ->count();

        $userData['commentTotal'] = Db::name('comment')
            ->alias('c')
            ->join('user u','c.uid = u.id','left')
            ->join('forum f', 'f.id = c.fid','left')
            ->where(['c.uid' => $userId, 'f.open' => 1])
            ->count();

        $userData['collectTotal'] = Db::name('collect')->where(['uid'=>$userId])->count();

        $userData['messageStatus'] = Db::name('message')->where(['touid'=>$userId,'is_read'=>0])->count();

        $userData['signTotal'] = Db::name('sign_record')->where(['uid'=>$userId])->count();

        return ToApiFormat('success',$userData);

    }

    /**
     * 我的回复信息
     * @author GuoLin
     * @createdate 2018-08-22
     *
     */
    public function replyInfo(){

        $userId = $this->checkToken();

        $params = $this->validateRequestParams(['page']);

        $userCommentData = Db::name('comment')
            ->alias('c')
            ->join('forum f', 'f.id = c.fid')
            ->join('user u','f.uid = u.id')
            ->field('c.fid,f.title,f.pic,c.create_time,u.username,u.userhead,c.content as reply_content,f.content as forum_content,c.create_time as reply_time,f.tid')
            ->where(['c.uid' => $userId, 'f.open' => 1])
            ->order('c.create_time desc')
            ->paginate(15, false, ['query' => ['page'=>$params['page']]]);

        $userCommentList = $userCommentData->toArray()['data'];

        foreach ($userCommentList as $key=> &$val){
            $userCommentList[$key]['parent'] = [];
            if($val['tid']){
                $userCommentList[$key]['parent'] = Db::name('comment')
                    ->alias('c')
                    ->join('user u', 'c.uid = u.id','left')
                    ->field('u.id,u.username')
                    ->where(['c.id' => $val['tid']])
                    ->find();
            }

            $val['reply_content']  = html_entity_decode($val['reply_content']);
            $val['forum_content']  = mb_substr(strip_tags(html_entity_decode($val['forum_content'])),0,100);

        }

        $page = [
            'currentPage' => intval(($userCommentData->currentPage())),
            'lastPage'    => $userCommentData->lastPage(),
            'total'       => $userCommentData->total(),
            'listRows'    => $userCommentData->listRows()
        ];

        return ToApiFormat('success',compact('userCommentList', 'page'));

    }

    /**
     * 我的帖子
     * @author GuoLin
     * @createdate 2018-08-24
     *
     */
    public function myForum(){

        $userId = $this->checkToken();

        $params = $this->validateRequestParams(['page']);

        //主页 动态
        $forum = Db::name('forum')
            ->alias('f')
            ->join('user u', 'f.uid=u.id')
            ->field('f.id,f.uid,f.title,f.content,f.view,f.reply,f.praise,f.reply_time,f.pic,f.choice,f.settop,f.create_time,u.username,u.userhead')
            ->where(['u.id' => $userId, 'f.open' => 1])
            ->order('f.create_time desc')
            ->paginate(15, false, ['query' => ['page'=>$params['page']]]);

        $forumList = $forum->toArray()['data'];

        foreach ($forumList as $key => &$val){

            $forumList[$key]['new'] = '';
            $forumList[$key]['hot'] = '';

            if($val['create_time'] >= strtotime(date('Y/m/d'))){
                $forumList[$key]['new'] = 1;
            }

            if($val['view'] > 100){
                $forumList[$key]['hot'] = 1;
            }

            $val['content'] = strip_tags(html_entity_decode($val['content']));

        }

        $page = [
            'currentPage' => intval(($forum->currentPage())),
            'lastPage'    => $forum->lastPage(),
            'total'       => $forum->total(),
            'listRows'    => $forum->listRows()
        ];

        return ToApiFormat('success',compact('forumList', 'page'));
    }


    /**
     * 我的消息
     * @author GuoLin
     * @createdate 2018-08-24
     *
     */
    public function myMessages(){

        $userId = $this->checkToken();

        $params = $this->validateRequestParams(['page']);

        $messages = Db::name('message')
            ->alias('m')
            ->join('user u','u.id = m.uid','left')
            ->join('forum f','m.fid = f.id','left')
            ->field('m.id,u.username,m.type,m.content,m.time,f.id as forum_id,f.title')
            ->where(['m.touid'=>$userId,'m.status'=>1,'u.status'=>1,'m.type'=>['in',['2','3']]])
            ->order('m.id desc')
            ->paginate(15, false, ['query' => ['page'=>$params['page']]]);

        $ids = array_column($messages->toArray()['data'],'id');

        if($ids){
            Db::name('message')->where('id','in',$ids)->where('is_read','0')->update(['is_read'=>1]);
        }

        $page = [
            'currentPage' => intval(($messages->currentPage())),
            'lastPage'    => $messages->lastPage(),
            'total'       => $messages->total(),
            'listRows'    => $messages->listRows()
        ];

        $messageList = $messages->toArray()['data'];

        return ToApiFormat('success',compact('messageList', 'page'));

    }


    public function MySystemMessages(){

        $userId = $this->checkToken();

        $params = $this->validateRequestParams(['page']);

        $messages = Db::name('message')
            ->alias('m')
            ->join('user u','u.id = m.uid','left')
            ->field('m.id,u.username,m.type,m.content,m.time')
            ->where(['m.touid'=>$userId,'m.status'=>1,'u.status'=>1,'m.type'=>1])
            ->order('m.id desc')
            ->paginate(15, false, ['query' => ['page'=>$params['page']]]);

        $ids = array_column($messages->toArray()['data'],'id');

        if($ids){
            Db::name('message')->where('id','in',$ids)->where('is_read','0')->update(['is_read'=>1]);
        }

        $messageList = $messages->toArray()['data'];

        $page = [
            'currentPage' => intval(($messages->currentPage())),
            'lastPage'    => $messages->lastPage(),
            'total'       => $messages->total(),
            'listRows'    => $messages->listRows()
        ];

        return ToApiFormat('success',compact('messageList', 'page'));
    }



    /**
     * 积分记录
     * @author GuoLin
     * @createdate 2018-08-30
     *
     */
    public function pointRecord(){

        $userId = $this->checkToken();

        $params = $this->validateRequestParams(['page']);

        $dataList = Db::name('point_record')
            ->where(['uid'=>$userId])
            ->order(['id'=>'desc'])
            ->paginate(15, false, ['query' => ['page'=>$params['page']]]);

        $pointList = $dataList->toArray();

        $page = [
            'currentPage' => intval(($dataList->currentPage())),
            'lastPage'    => $dataList->lastPage(),
            'total'       => $dataList->total(),
            'listRows'    => $dataList->listRows()
        ];

        return ToApiFormat('success',compact('pointList', 'page'));

    }

    /**
     * 米币记录
     * @author GuoLin
     * @createdate 2018-08-30
     *
     */
    public function moneyRecord(){

        $userId = $this->checkToken();

        $params = $this->validateRequestParams(['page']);

        $dataList = Db::name('money_record')
            ->where(['uid'=>$userId])
            ->order(['id'=>'desc'])
            ->paginate(15, false, ['query' => ['page'=>$params['page']]]);

        $moneyList = $dataList->toArray();

        $page = [
            'currentPage' => intval(($dataList->currentPage())),
            'lastPage'    => $dataList->lastPage(),
            'total'       => $dataList->total(),
            'listRows'    => $dataList->listRows()
        ];

        return ToApiFormat('success',compact('moneyList', 'page'));
    }


    /**
     * 签到月度记录
     * @author GuoLin
     * @createdate 2018-10-10
     *
     */
    public function signMonthRecord(){

        $userId = $this->checkToken();

        $needTime = strtotime(date('Y-m-d', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600)));
        $signData = Db::name('sign_record')->where(['uid'=>$userId,'create_time'=>['>=',$needTime ]])->column('create_time');

        foreach ($signData as &$val){
            $val = date('Y-m-d',$val);
        }

        $totalSign = Db::name('sign_record')->where(['uid'=>$userId])->count();

        $signStatus = Db::name('sign_record')->where(['create_time'=>['>=',strtotime(date('Y-m-d'))],'uid'=>$userId])->count();

        $point = Db::name('user')->where(['id'=>$userId])->value('point');

        $rewardStatus = 0;

        if(count($signData) ==  date('t', strtotime('Y-m'))){
            $rewardStatus = 1;
        }

        return ToApiFormat('success',compact('signData','totalSign','signStatus','point','rewardStatus'));
    }

    /**
     * 修改用户名
     * @author GuoLin
     * @createdate 2018-09-14
     *
     */
    public function updateUserName(){

        $userId = $this->checkToken();

        $params = $this->validateRequestParams(['username']);

        $userInfo = Db::name('user')->where(['id'=>$userId])->find();

        if(!$userInfo){
            throw new Exception('非法请求', 1);
        }

        if($userInfo['is_username']){
            throw new Exception('用户名只能修改一次', 2);
        }

        $updateStatus = Db::name('user')->update(['id'=>$userId,'username'=>$params['username'],'is_username'=>1]);

        if($updateStatus){
            return ToApiFormat('success');
        }else{
            throw new Exception('修改失败', 3);
        }

    }


    /**
     * 修改用户签名
     * @author GuoLin
     * @createdate 2018-09-14
     *
     */
    public function updateUserDescription(){

        $userId = $this->checkToken();

        $params = $this->validateRequestParams(['description']);

        $updateStatus = Db::name('user')->update(['id'=>$userId,'description'=>$params['description']]);

        if($updateStatus !== false){
            return ToApiFormat('success');
        }else{
            throw new Exception('修改失败', 3);
        }

    }

    /**
     * 修改用性别
     * @author GuoLin
     * @createdate 2018-09-14
     *
     */
    public function updateUserSex(){

        $userId = $this->checkToken();

        $params = $this->validateRequestParams(['sex']);

        $updateStatus = Db::name('user')->update(['id'=>$userId,'sex'=>$params['sex']]);

        if($updateStatus !== false){
            return ToApiFormat('success');
        }else{
            throw new Exception('修改失败', 3);
        }
    }

    /**
     * 修改头像
     * @author GuoLin
     * @createdate 2018-09-14
     *
     */
    public function updateUserHead(){

        $userId = $this->checkToken();

        $params = $this->validateRequestParams(['head']);

        $path = saveBase64File($params['head']);

        if($path === false){
            throw new Exception('图片上传失败', 3);
        }

        $updateStatus = Db::name('user')->update(['id'=>$userId,'userhead'=>$path]);

        if($updateStatus !== false){
            return ToApiFormat('success',$path);
        }else{
            throw new Exception('修改失败', 3);
        }
    }




    /**
     * 系统消息
     * @author GuoLin
     * @createdate 2018-09-13
     *
     */
    public function MessageCount(){

        $userId = $this->checkToken();

        $unreadMessageCount = Db::name('message')->where(['touid'=>$userId,'is_read'=>0,'type'=>1])->count();

        return ToApiFormat('success',$unreadMessageCount);

    }


    /**
     * 我的收藏
     * @author GuoLin
     * @createdate 2018-10-09
     *
     */
    public function collectList(){

        $userId = $this->checkToken();

        $params = $this->validateRequestParams(['page']);

        $collectList = Db::name('collect')
            ->alias('c')
            ->join('forum f','f.id = c.forum_id')
            ->join('user u','f.uid = u.id')
            ->field('f.id,f.title,f.pic,u.username,u.userhead,f.create_time,f.view')
            ->where(['c.uid'=>$userId])
            ->order('c.create_time','desc')
            ->paginate(15,false,['request'=>['page'=>$params['page']]]);

        $page = [
            'currentPage' => intval(($collectList->currentPage())),
            'lastPage'    => $collectList->lastPage(),
            'total'       => $collectList->total(),
            'listRows'    => $collectList->listRows()
        ];

        $collectList = $collectList->toArray()['data'];

        return ToApiFormat('success',compact('collectList', 'page'));

    }

    /**
     * 任务奖励说明列表
     * @author GuoLin
     * @createdate 2018-10-10
     *
     */
    public function pointRule(){
        $taskList = Db::name('task')
            ->where(['status'=>1,'task_type'=>['in',[1,3]]])
            ->field('id,task_name,task_point,task_type')
            ->select();
        return ToApiFormat('success',$taskList);
    }

    /**
     * 好友邀请
     * @author GuoLin
     * @createdate 2018-10-15
     *
     */
    public function inviteFriends(){

        $userId = $this->checkToken();

        $friends = Db::name('user')->where(['tid'=>$userId])->count();

        $money = Db::name('invite_record')->where(['tid'=>$userId])->sum('money')?:0;

        $url = 'http://www.1miclub.com/index/index/invite_register/tid/'.$userId;

        $site_config = Db::name('system')->field('value')->where('name', 'operate')->value('value');
        $site_config = unserialize($site_config);
        $invite_friends_money = $site_config['invite_friends'];

        return ToApiFormat('success',compact('friends','money','url','invite_friends_money'));

    }

    public function gradeInfo(){

        $data = Db::name('usergrade')->order(['score'=>'asc'])->select();
        return ToApiFormat('success',$data);

    }


    /**
     * 微信授权登陆
     * @author GuoLin
     * @createdate 2018-10-17
     *
     */
    public function wechartLogin(){

        $params = $this->validateRequestParams(['openid','sex','headimgurl','unionid','nickname']);

        $userData = Db::name('user')
            ->field('id as userid,username,point,money,mobile,sex,status,userhead,regtime,is_username')
            ->where(['wechat_unionid'=>$params['unionid']])
            ->find();

        if ($userData) {

            if($userData['status'] == 0){
                throw new Exception('账号已被禁止登陆',1);
            }

            // 支持同一账号多次登录， 使用同一token
            if (($token = $this->getTokenByDatabase($userData['userid']))) {

                try {
                    $this->checkToken($token);
                } catch (\Exception $e) {

                    $token = $this->setToken($userData['userid']);
                }
            } else {
                $token = $this->setToken($userData['userid']);
            }

            if ($userData['userhead'] == '') {
                $userData['userhead'] = '/public/images/default.png';
            }

            Db::name('user')->update(
                [
                    'last_login_time' => time(),
                    'last_login_ip' => $this->request->ip(),
                    'id' => $userData['userid']
                ]
            );

            autoUpGrade($userData['userid']);

            return ToApiFormat('success',compact('userData','token'));
        } else {

            $userGradeId = Db::name('usergrade')->order(['score'=>'asc'])->value('id') ?:0;

            $insertData = [
                'mobile'           => '',
                'status'           => 1,       //config('web.WEB_REG');
                'point'            => 0,       //config('point.REG_POINT');
                'sex'              => $params['sex'],
                'userhead'         => $params['headimgurl']?:'/public/images/default.png',
                'userip'           => $this->request->ip(),
                'password'         => '',
                'usergrade'        => $userGradeId,
                'wechat_openid'    => $params['openid'],
                'wechat_unionid'   => $params['unionid'],
                'wechat_nickname'  => $params['nickname'],
                'is_username'      => 0,
                'regtime'          => time(),
            ];

            $userId = Db::name('user')->insertGetId($insertData);

            if($userId){
                $nameExist = false;
                $userName = '米友'.$userId;
                if($params['nickname']){
                    $nameExist = Db::name('user')->where(['username'=>$params['nickname']])->count();
                }else{
                    $params['nickname'] = $userName;
                }
                $userName = $nameExist?$userName:$params['nickname'];
                Db::name('user')->where('id',$userId)->setField('username',$userName);

                $userData = [
                    'userid'     => $userId,
                    'status'     => $insertData['status'],
                    'point'      => $insertData['point'],
                    'money'      => 0,
                    'username'   => $userName,
                    'userhead'   => $insertData['userhead'],
                    'sex'        => $insertData['sex'],
                    'regtime'    => $insertData['regtime'],
                ];


                Db::name('user')->update(
                    [
                        'last_login_time'=> time(),
                        'last_login_ip'  => $insertData['userip'],
                        'id'             => $userId
                    ]
                );

                addMessageRecord($userId,$userId,'','一米社区欢迎您的加入');

                $token = $this->getTokenByDatabase($userData['userid']);

                // 支持同一账号多次登录， 使用同一token
                if($token){
                    try{
                        $this->checkToken($token);
                    }catch (\Exception $e){

                        $token = $this->setToken($userData['userid']);
                    }
                }else{
                    $token = $this->setToken($userData['userid']);
                }

                $userData['is_username'] = 0;
                return ToApiFormat('success',compact('userData','token'));
            }else{

                throw new Exception('注册失败',1);
            }

        }

    }


    /**
     * 绑定手机号
     * @author GuoLin
     * @createdate 2018-10-19
     *
     */
    public function bindingMobile(){

        $userId = $this->checkToken();

        $params = $this->validateRequestParams(['phone','password','verifyCode']);

        $userInfo = Db::name('user')->where(['id'=>$userId,'status'=>1])->find();

        if(Vercode::verification($params['phone'],$params['verifyCode']) == false){
            throw new Exception('验证码有误',1);
        };

        if(!$userInfo){
            throw new Exception('非法请求',1);
        }

        $existMobile = Db::name('user')->where(['mobile'=>$params['phone']])->count();
        if($existMobile){
            throw new Exception('该手机号已经注册过了',2);
        }

        $result = Db::name('user')->where(['id'=>$userId])->update([
                'mobile'    => $params['phone'],
                'password'  => md5($params['password'])
            ]);

        if($result){
            return ToApiFormat('success');
        }else{
            throw new Exception('绑定失败',3);
        }
    }

}