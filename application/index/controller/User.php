<?php

namespace app\index\controller;

use app\common\controller\HomeBase;
use think\Db;
use app\common\model\User as UserModel;
use think\Validate;

class User extends HomeBase
{
    protected $userId;

    protected $showUserId;

    protected $userGradeId;

    public function _initialize()
    {
        parent::_initialize();

        mb_internal_encoding('UTF-8');

        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录', url('index/index'));
        }


        $this->userId = session('userid');
        $this->showUserId = $this->request->param('id');
        $this->userGradeId = session('usergrade');

//        if(!$this->showUserId){
//            $this->error('非法请求');
//        }

        $userExist = Db::name('user')->where('id', $this->userId)->find();

        if (!$userExist) {
            $this->error('未找到相关用户');
        }

        $this->assign('userId', $this->userId);
    }

    public function topic()
    {
        $forum = Db::name('forum');
        $uid = session('userid');
        $count = $forum->where("uid = {$uid}")->count();
        $tptc = $forum->where("uid = {$uid}")->order('id desc')->paginate(10);
        $this->assign('tptc', $tptc);
        $this->assign('uid', $uid);
        $this->assign('count', $count);
        return view();
    }

    public function delallmessage()
    {

        $uid = session('userid');
        $tptc = Db::name('message')->where(array('touid' => 0))->column('id');
        $tptc1 = array();
        $tptc1 = Db::name('readmessage')->where(array('uid' => $uid))->column('mid');


        if (Db::name('message')->where(array('touid' => $uid))->count() > 0) {

            if (Db::name('message')->where(array('touid' => $uid))->delete()) {
                if (!empty($tptc)) {
                    foreach ($tptc as $k => $v) {
                        if (!in_array($v, $tptc1)) {
                            $messdata['uid'] = $uid;
                            $messdata['mid'] = $v;
                            Db::name('readmessage')->insert($messdata);
                        }
                    }
                }

                //$this->success('删除成功');
                return json(array('code' => 200, 'msg' => '删除成功'));
            } else {
                // $this->error('删除失败');
                return json(array('code' => 0, 'msg' => '删除失败'));
            }
        } else {


            if (!empty($tptc)) {

                if (count($tptc) != count($tptc1)) {

                    foreach ($tptc as $k => $v) {

                        if (!in_array($v, $tptc1)) {
                            $messdata['uid'] = $uid;
                            $messdata['mid'] = $v;
                            Db::name('readmessage')->insert($messdata);
                        }

                    }

                    return json(array('code' => 200, 'msg' => '删除成功'));
                } else {
                    return json(array('code' => 0, 'msg' => '您无任何消息可删除'));
                }
            } else {
                return json(array('code' => 0, 'msg' => '您无任何消息可删除'));

            }
        }


    }

    public function delsysmessage($id)
    {

        $uid = session('userid');
        $messdata['uid'] = $uid;
        $messdata['mid'] = $id;


        if (Db::name('readmessage')->insert($messdata)) {

            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }

    }

    public function delmessage($id)
    {

        if (Db::name('message')->delete($id)) {
            //$this->success('删除成功');
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            // $this->error('删除失败');
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }

    public function comment()
    {
        $comment = Db::name('comment');
        $uid = session('userid');
        $tptc = $comment->alias('c')->join('forum f', 'f.id=c.fid')->field('c.*,f.title')->where("c.uid = {$uid}")->order('c.id desc')->paginate(5);

        $this->assign('tptc', $tptc);
        $this->assign('uid', $uid);
        return view();
    }


    /**
     * 我的首页
     * @author GuoLin
     * @createdate 2018-08-03
     *
     */
    public function index()
    {

        //个人主页 浏览量
        Db::name('user')->where('id', $this->showUserId)->setInc('view', 1);

        //主页 动态
        $forum = Db::name('forum')
            ->alias('f')
            ->join('user u', 'f.uid=u.id')
            ->field('f.id,f.uid,f.title,f.content,f.view,f.reply,f.praise,f.reply_time,f.pic,f.choice,f.settop,f.create_time,u.username,u.userhead')
            ->where(['u.id' => $this->userId, 'f.open' => 1])
            ->order('f.create_time desc')
            ->paginate(3, false, ['query' => request()->param()]);


        $commentCount = Db::name('comment')
            ->alias('c')
            ->join('user u', 'c.uid = u.id', 'left')
            ->join('forum f', 'f.id = c.fid', 'left')
            ->where(['c.uid' => $this->userId, 'f.open' => 1])
            ->count();


        $messageCount = Db::name('message')
            ->alias('m')
            ->join('user u', 'u.id = m.uid', 'left')
            ->join('forum f', 'm.fid = f.id')
            ->field('m.id,u.username,m.fid,m.type,m.content,m.time,m.is_read,f.id as forum_id, f.title')
            ->where(['m.touid' => $this->userId, 'm.status' => 1, 'u.status' => 1])
            ->count();

        $forumList = $forum->toArray()['data'];

        foreach ($forumList as $key => &$val) {

            $forumList[$key]['new'] = '';
            $forumList[$key]['hot'] = '';

            if ($val['create_time'] >= strtotime(date('Y/m/d'))) {
                $forumList[$key]['new'] = 1;
            }

            if ($val['view'] > 100) {
                $forumList[$key]['hot'] = 1;
            }

            $val['content'] = strip_tags(html_entity_decode($val['content']));

        }

        $this->assign([
            'forumList' => $forumList,
            'page' => $forum->render(),
            'total' => $forum->total(),
            'commentCount' => $commentCount,
            'messageCount' => $messageCount
        ]);
//        dump($forumList);exit;
        return view();
    }


    /**
     * 我的回复
     * @author GuoLin
     * @createdate 2018-08-03
     *
     */
    public function reply()
    {

        $userCommentData = Db::name('comment')
            ->alias('c')
            ->join('forum f', 'f.id = c.fid')
            ->join('user u', 'f.uid = u.id')
            ->field('f.id,c.fid,f.title,f.pic,c.create_time,u.username,u.userhead,c.content as reply_content,f.content as forum_content,c.create_time as reply_time,f.tid,f.view,f.settop,f.praise,f.reply')
            ->where(['c.uid' => $this->userId, 'f.open' => 1])
            ->order('c.create_time desc')
            ->paginate(15, false, ['query' => ['page' => request()->param()]]);

        $userCommentList = $userCommentData->toArray()['data'];

        foreach ($userCommentList as $key => &$val) {
            $userCommentList[$key]['parent'] = [];
            if ($val['tid']) {
                $userCommentList[$key]['parent'] = Db::name('comment')
                    ->alias('c')
                    ->join('user u', 'c.uid = u.id', 'left')
                    ->field('u.id,u.username')
                    ->where(['c.id' => $val['tid']])
                    ->find();
            }

            $val['reply_content'] = html_entity_decode($val['reply_content']);
            $val['forum_content'] = mb_substr(strip_tags(html_entity_decode($val['forum_content'])), 0, 100);

            $userCommentList[$key]['new'] = '';
            $userCommentList[$key]['hot'] = '';

            if ($val['create_time'] >= strtotime(date('Y/m/d'))) {
                $userCommentList[$key]['new'] = 1;
            }

            if ($val['view'] > 100) {
                $userCommentList[$key]['hot'] = 1;
            }

        }

        $forumCount = Db::name('forum')
            ->alias('f')
            ->join('user u', 'f.uid=u.id')
            ->where(['u.id' => $this->userId, 'f.open' => 1])
            ->order('f.create_time desc')
            ->count();


        $messageCount = Db::name('message')
            ->alias('m')
            ->join('user u', 'u.id = m.uid', 'left')
            ->join('forum f', 'm.fid = f.id')
            ->field('m.id,u.username,m.fid,m.type,m.content,m.time,m.is_read,f.id as forum_id, f.title')
            ->where(['m.touid' => $this->userId, 'm.status' => 1, 'u.status' => 1])
            ->count();

        $this->assign([
            'userCommentList' => $userCommentList,
            'page' => $userCommentData->render(),
            'total' => $userCommentData->total(),
            'forumCount' => $forumCount,
            'messageCount' => $messageCount
        ]);

        return view();
    }


    /**
     * 我的消息
     * @author GuoLin
     * @createdate 2018-08-03
     *
     */
    public function message()
    {

        $messageList = Db::name('message')
            ->alias('m')
            ->join('user u', 'u.id = m.uid', 'left')
            ->join('forum f', 'm.fid = f.id', 'left')
            ->field('m.id,u.username,m.fid,m.type,m.content,m.time,m.is_read,f.id as forum_id, f.title')
            ->where(['m.touid' => $this->userId, 'm.status' => 1, 'u.status' => 1])
            ->order('m.id desc')
            ->paginate(15, false, ['query' => $this->request->param()]);

        $ids = array_column($messageList->toArray()['data'], 'id');
        if ($ids) {
            Db::name('message')->where('id', 'in', $ids)->update(['is_read' => 1]);
        }

        $forumCount = Db::name('forum')
            ->alias('f')
            ->join('user u', 'f.uid=u.id')
            ->where(['u.id' => $this->userId, 'f.open' => 1])
            ->order('f.create_time desc')
            ->count();


        $commentCount = Db::name('comment')
            ->alias('c')
            ->join('user u', 'c.uid = u.id', 'left')
            ->join('forum f', 'f.id = c.fid', 'left')
            ->where(['c.uid' => $this->userId, 'f.open' => 1])
            ->count();

        $this->assign([
            'messageList' => $messageList,
            'page' => $messageList->render(),
            'total' => $messageList->total(),
            'forumCount' => $forumCount,
            'commentCount' => $commentCount
        ]);

        return view();
    }

    /*修改资料*/
    public function set()
    {

        $member = new UserModel();

        $uid = session('userid');

        $tptc = $member->where(array('id' => $uid))->find();

        if (request()->isPost()) {
            if ($tptc['is_username'] !== request()->post('username')) {
                $existUserName = Db::name('user')->where(['username' => request()->post('username')])->find();
                if ($existUserName) {
                    return json(array('code' => 0, 'msg' => '当前用户名已存在'));
                }
                $data = $this->request->post();
                $data['is_username'] = 1;

            } else {

                $data['sex'] = request()->post('sex') ? request()->post('sex') : '';
                $data['provid'] = request()->post('provid') ? request()->post('provid') : '';
                $data['cityid'] = request()->post('cityid') ? request()->post('cityid') : '';
                $data['areaid'] = request()->post('areaid') ? request()->post('areaid') : '';
                $data['description'] = request()->post('description') ? request()->post('description') : '';

            }
            if ($member->update($data, ['id' => $uid])) {

                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {
                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }

        $this->assign('tptc', $tptc);
        $this->assign('uid', $uid);
        return view();


    }

    /*修改密码*/
    public function setedit()
    {

        $uid = session('userid');

        $code = $this->request->param('userCode');
        $newPass = $this->request->param('newPass');


        if (!$code) {
            return json(array('code' => 0, 'msg' => '短信验证码不能为空'));
        }

        if (!$newPass) {
            return json(array('code' => 0, 'msg' => '新秘密不能为空'));
        }

        if ($code != session('cmsCode')) {
            return json(array('code' => 0, 'msg' => '短信验证码错误'));
        }

        $newPass = md5(md5($newPass));

        $result = Db::name('user')->where('id', $uid)->update(['password' => $newPass]);

        if ($result !== false) {

            session('cmsCode', md5(time()));

            session("userstatus", NULL);
            session("userid", NULL);
            session("username", NULL);
            session("usermail", NULL);
            session("kouling", NULL);

            cookie('sys_key', null);

            return json(['code' => 200, 'msg' => '密码修改成功，请重新登录']);
        } else {
            return json(['code' => 0, 'msg' => '密码修改失败，请稍后重试']);
        }


    }

    //修改头像
    public function headedit()
    {

        $uid = session('userid');
        if (request()->isPost()) {

            $imgData = $this->request->param('img');

            if (!$imgData) {
                return json(array('code' => 0, 'msg' => '图片不能为空'));
            }

            $path = saveBase64File($imgData);

            if ($path === false) {
                return json(array('code' => 0, 'msg' => '图片上传失败'));
            }

            $result = Db::name('user')->where('id', $uid)->update(['userhead' => $path]);
            if ($result !== false) {
                session('userhead', $path);

                $taskStatus = runTask(2, $this->userId, session(''));

                return json(array('code' => 200, 'msg' => '修改成功', 'taskStatus' => $taskStatus));
            } else {

                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }

        $this->assign('id', $uid);
        return view();

    }


    /**
     * 签到
     * @author GuoLin
     * @createdate 2018-07-17
     *
     */
    public function sign()
    {
        $today_exist = Db::name('sign_record')->where(['uid' => session('userid'), 'create_time' => ['>=', strtotime(date('Y-m-d'))]])->find();

        if ($today_exist) {
            return json(['code' => 0, 'msg' => '您今天已经签到过了']);
        }

        $system = Db::name('system')->where('name', 'operate')->find()['value'];
        $system = unserialize($system);

        if (!$system['sign_min'] || $system['sign_min'] < 1) {
            return json(['code' => 0, 'msg' => '签到未开启']);
        }

        $point = mt_rand($system['sign_min'], $system['sign_max']);
        $money = mt_rand($system['sign_jinbi_min'], $system['sign_jinbi_max']);

        $data = [
            'uid' => session('userid'),
            'point' => $point,
            'money' => $money,
            'create_time' => time()
        ];

        Db::startTrans();
        try {
            Db::name('sign_record')->insert($data);
            Db::name('user')->where(['id' => $this->userId])->setInc('point', $point);
            Db::name('user')->where(['id' => $this->userId])->setInc('money', $money);
            if ($point) {
                addMoneyRecord($this->userId, '完成签到的奖励', $point, 1, 0);
            }
            if ($money) {
                addMoneyRecord($this->userId, '完成签到的奖励', $money, 2, 0);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '签到失败,请稍后重试']);
        }

        return json(['code' => 1, 'msg' => '签到成功,获得' . $point . $system['jifen_name']]);
    }


    /**
     * 帖子打赏
     * @author GuoLin
     * @createdate 2018-08-15
     *
     */
    public function give_reward()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '非法请求']);
        }

        $id = $this->request->param('id', '', 'intval');
        $money = $this->request->param('money', '', 'intval');

        if (!$id || !$money) {
            return json(['code' => 0, 'msg' => '缺少参数']);
        }

        $forumData = Db::name('forum')->field('title,uid')->where(['id' => $id])->find();

        if (mb_strlen($forumData['title']) > 12) {
            $forumData['title'] = mb_substr($forumData['title'], 0, 12) . '...';
        }

        $sid = $forumData['uid'];

        if ($this->userId == $sid) {
            return json(['code' => 0, 'msg' => '不可以打赏自己的帖子']);
        }

        $userData = Db::name('user')->field('id,usergrade,money,username')->where(['id' => $this->userId])->find();

        Db::startTrans();
        try {
            $userMoney = Db::name('user')->where(['id' => $this->userId])->value('money');
            if ($userMoney < $money) {
                return json(['code' => 0, 'msg' => '账户余额不足']);
            }
            Db::name('user')->where(['id' => $this->userId])->setDec('money', $money);
            Db::name('user')->where(['id' => $sid])->setInc('money', $money);
            Db::name('give_reward_record')->insert(['tid' => $id, 'uid' => $this->userId, 'money' => $money, 'create_time' => time()]);
            Db::name('forum')->where(['id' => $id])->setInc('reward', $money);
            Db::name('forum')->where(['id' => $id])->setInc('reward_sum');
            addMoneyRecord($this->userId, '打赏帖子《' . $forumData['title'] . '》', $money, 2, 0, 1);
            addMoneyRecord($sid, '帖子《' . $forumData['title'] . '》被' . $userData['username'] . '打赏', $money, 2, 0, 0);
            runTask(9, $this->userId, $this->userGradeId);
            Db::commit();
            return json(['code' => 1, 'msg' => '打赏成功']);
        } catch (\Exception $e){
            Db::rollback();
            return json(['code' => 0, 'msg' => '打赏失败', 'error' => $e]);
        }
    }


    /**
     * 积分记录
     * @author GuoLin
     * @createdate 2018-08-29
     *
     */
    public function point_record()
    {
        $forumCount = Db::name('forum')
            ->alias('f')
            ->join('user u', 'f.uid=u.id')
            ->where(['u.id' => $this->userId, 'f.open' => 1])
            ->order('f.create_time desc')
            ->count();


        $commentCount = Db::name('comment')
            ->alias('c')
            ->join('user u', 'c.uid = u.id', 'left')
            ->join('forum f', 'f.id = c.fid', 'left')
            ->where(['c.uid' => $this->userId, 'f.open' => 1])
            ->count();

        $messageCount = Db::name('message')
            ->alias('m')
            ->join('user u', 'u.id = m.uid', 'left')
            ->join('forum f', 'm.fid = f.id')
            ->field('m.id,u.username,m.fid,m.type,m.content,m.time,m.is_read,f.id as forum_id, f.title')
            ->where(['m.touid' => $this->userId, 'm.status' => 1, 'u.status' => 1])
            ->count();

        $dataList = Db::name('point_record')
            ->where(['uid' => $this->userId])
            ->order(['id' => 'desc'])
            ->paginate(15, false, ['query' => $this->request->param()]);

        $this->assign([
            'forumCount'   => $forumCount,
            'commentCount' => $commentCount,
            'messageCount' => $messageCount,
            'dataList'     => $dataList,
            'page'         => $dataList->render(),
        ]);

        return view();
    }

    /**
     * 米币记录
     * @author GuoLin
     * @createdate 2018-08-29
     *
     */
    public function money_record()
    {
        $forumCount = Db::name('forum')
            ->alias('f')
            ->join('user u', 'f.uid=u.id')
            ->where(['u.id' => $this->userId, 'f.open' => 1])
            ->order('f.create_time desc')
            ->count();


        $commentCount = Db::name('comment')
            ->alias('c')
            ->join('user u', 'c.uid = u.id', 'left')
            ->join('forum f', 'f.id = c.fid', 'left')
            ->where(['c.uid' => $this->userId, 'f.open' => 1])
            ->count();

        $messageCount = Db::name('message')
            ->alias('m')
            ->join('user u', 'u.id = m.uid', 'left')
            ->join('forum f', 'm.fid = f.id')
            ->field('m.id,u.username,m.fid,m.type,m.content,m.time,m.is_read,f.id as forum_id, f.title')
            ->where(['m.touid' => $this->userId, 'm.status' => 1, 'u.status' => 1])
            ->count();

        $dataList = Db::name('money_record')
            ->where(['uid' => $this->userId])
            ->order(['id' => 'desc'])
            ->paginate(15, false, ['query' => $this->request->param()]);

        $this->assign([
            'forumCount' => $forumCount,
            'commentCount' => $commentCount,
            'messageCount' => $messageCount,
            'dataList' => $dataList,
            'page' => $dataList->render(),
        ]);

        return view();
    }


    public function wechat()
    {

        $url1 = urlencode("http://www.1miclub.com" . url('binding_wechat', '', true, false, true));
        $redirect_ur1 = "https://open.weixin.qq.com/connect/qrconnect?appid=wxcfd9143033bcf806&redirect_uri=$url1&response_type=code&scope=snsapi_login&state=3d6be0a4035d839573b04816624a415e#wechat_redirect";
        header("location:$redirect_ur1");

    }


    /**
     * 微信授权绑
     * @author GuoLin
     * @createdate 2018-10-17
     *
     */
    public function binding_wechat()
    {
        $appid = "wxcfd9143033bcf806";
        $AppSecret = "d9980614a9f20672cfe9e8b95005b9f7";
        $code = $this->request->param('code');
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$AppSecret&code=$code&grant_type=authorization_code";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        $access_token = json_decode($output, true);
        $token = $access_token['access_token'];
        $openid = $access_token['openid'];
        $url1 = "https://api.weixin.qq.com/sns/userinfo?access_token=$token&openid=$openid&lang=zh_CN";
        $ch1 = curl_init();
        curl_setopt($ch1, CURLOPT_URL, $url1);
        curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
        $output1 = curl_exec($ch1);
        curl_close($ch1);
        $data = json_decode($output1, true);

        //登陆开始
        $userData = Db::name('user')
            ->field('id as userid,username,point,money,mobile,sex,status,userhead,regtime,is_username,usergrade')
            ->where(['wechat_unionid' => $data['unionid']])
            ->find();

        if ($userData) {
            $this->error('该微信已绑定其他账号', url('set'));
        } else {
            $result = Db::name('user')->where(['id' => $this->userId])->update([
                'wechat_openid'  => $data['openid'],
                'wechat_unionid' => $data['unionid'],
                'wechat_nickname'=> $data['nickname'],
                'userhome'       => $data['country'] . $data['province'] . $data['city'],
            ]);

            if ($result !== false) {
                $this->redirect(url('set'));
            } else {
                $this->error('微信授权失败', url('set'));
            }
        }

    }

    /**
     * 修改头像
     * @author GuoLin
     * @createdate 2018-10-26
     *
     */
    public function edit_head()
    {
        if (request()->isPost()) {

            $imgData = $this->request->param('img');

            if (!$imgData) {
                return json(array('code' => 0, 'msg' => '图片不能为空'));
            }

            $path = saveBase64File($imgData);

            if ($path === false) {
                return json(array('code' => 0, 'msg' => '图片上传失败'));
            }

            $result = Db::name('user')->where('id', $this->userId)->update(['userhead' => $path]);

            if ($result !== false) {
                session('userhead', $path);
                $taskStatus = runTask(2, $this->userId);
                return json(array('code' => 200, 'msg' => '修改成功', 'taskStatus' => $taskStatus));
            } else {
                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }

        $this->assign('id', $this->userId);
        return view();
    }

    /**
     * 修改资料
     * @author GuoLin
     * @createdate 2018-11-01
     *
     */
    public function edit()
    {
        $renaming = Db::name('user')->where(['id'=>$this->userId])->value('is_username');
        if($this->request->isPost()){
            $user_data = [
                'description'   => $this->request->param('description','','trim'),
                'sex'           => $this->request->param('sex'),
                'qq'            => $this->request->param('qq','','trim'),
                'usermail'      => $this->request->param('usermail','','trim'),
                'provid'        => $this->request->param('provid'),
                'cityid'        => $this->request->param('cityid'),
                'areaid'        => $this->request->param('areaid')
            ];

            if($this->request->param('username') && !$renaming){
                $user_data['username'] = $this->request->param('username','','trim');
            }

            $validate = new Validate([
                'username|用户名'   => 'length:1,15',
                'description|描述'  => 'length:0,200',
                'sex|性别'          => 'require|between:1,3',
                'qq'                => 'integer|length:4,15',
                'usermail|邮箱'     => 'regex:^[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*\.[a-zA-Z0-9]{2,6}$',
                'provid|省'         => 'integer',
                'cityid|市'         => 'integer',
                'areaid|县'         => 'integer'
            ]);

            if (!$validate->check($user_data)) {
                return json(['error_code'=>1,'msg'=>$validate->getError()]);
            }

            $result = Db::name('user')->where(['id'=>$this->userId])->update($user_data);

            if($result !== false){
                return json(['error_code'=>0,'msg'=>'验证通过']);
            }else{
                return json(['error_code'=>2,'msg'=>'修改失败']);
            }

        }

        $this->assign([
            'renaming'  => $renaming
        ]);

        return view();
    }

    /**
     * 积分信息
     * @author GuoLin
     * @createdate 2018-10-26
     *
     */
    public function point_info()
    {
        $dataList = Db::name('point_record')
            ->where(['uid' => $this->userId])
            ->order(['id' => 'desc'])
            ->paginate(15, false, ['query' => $this->request->param()]);


        $task_point_list = Db::name('task')->field('task_name,task_type,task_point')->where(['status'=>1,'task_point'=>['<>',0]])->select();
        $this->assign([
            'dataList'        => $dataList,
            'task_point_list' => $task_point_list
        ]);
        return view();
    }

    /**
     * 米币信息
     * @author GuoLin
     * @createdate 2018-10-26
     *
     */
    public function money_info()
    {
        $dataList = Db::name('money_record')
            ->where(['uid' => $this->userId])
            ->order(['id' => 'desc'])
            ->paginate(15, false, ['query' => $this->request->param()]);
        $this->assign([
            'dataList' => $dataList
        ]);
        return view();
    }

    /**
     * 用户组
     * @author GuoLin
     * @createdate 2018-10-26
     *
     */
    public function grade()
    {
        $gradeList = Db::name('usergrade')->order(['score'=>'asc'])->select();
        $this->assign([
            'gradeList' => $gradeList
        ]);
        return view();
    }

    /**
     * 修改密码
     * @author GuoLin
     * @createdate 2018-10-26
     *
     */
    public function password()
    {
        if($this->request->isPost()){

            $mobile   = $this->request->param('mobile');
            $password = $this->request->param('password');
            $code     = $this->request->param('code');

            if(!is_mobile($mobile)){
                return json(['code' => 0, 'msg' => '非法请求1']);
            }

            if(!$password || !$code){
                return json(['code' => 0, 'msg' => '信息填写不完整']);
            }

            if ($code != session('cmsCode')) {
                return json(array('code' => 0, 'msg' => '短信验证码错误'));
            }

            $mobile = Db::name('user')->where(['id'=>$this->userId])->value('mobile');

            $result = Db::name('user')->where('id',$this->userId)->update(['password' =>md5(md5($password))]);

            if ($result !== false) {
                session(null);
                return json(['code' => 200, 'msg' => '密码修改成功，请重新登录']);
            } else {
                return json(['code' => 0, 'msg' => '密码修改失败，请稍后重试']);
            }

        }

        return view();
    }


    /**
     * 商务合作
     * @author GuoLin
     * @createdate 2018-10-26
     *
     */
    public function cooperation()
    {
        return view();
    }

    /**
     * 我的帖子
     * @author GuoLin
     * @createdate 2018-11-06
     *
     */
    public function my_article(){

        $data_list = Db::name('forum')
            ->alias('f')
            ->join('forumcate c','f.tid = c.id','left')
            ->field('f.id,f.title,f.view,f.reply,f.praise,f.description,f.pic,f.settop,c.name as cate_name,f.create_time')
            ->where(['f.open'=>1,'f.uid'=>$this->userId,'open'=>1])
            ->order(['f.create_time'=>'desc'])
            ->paginate(15,false,['query'=>$this->request->param()]);

        $list = $data_list->toArray()['data'];

        foreach($list as $key => $value){
            $list[$key]['new'] = '';
            $list[$key]['hot'] = '';

            if ($value['create_time'] >= strtotime(date('Y/m/d'))) {
                $forumList[$key]['new'] = 1;
            }

            if ($value['view'] >= 1000) {
                $forumList[$key]['hot'] = 1;
            }
        }

//        $data_hot_list = Db::name('forum')
//            ->alias('f')
//            ->join('forumcate c','f.tid = c.id','left')
//            ->field('id,title,view,reply,praise,description,pic,settop,c.name as cate_name,create_time')
//            ->where(['f.open'=>1,'f.view'=>['>=',1000]])
//            ->order(['create_time'=>'desc'])
//            ->paginate(15,false,['query'=>$this->request->param()]);

        $this->assign([
            'data_list' => $list,
            'page'      => $data_list->render()
        ]);

        return view();
    }

    /**
     * 我的回复
     * @author GuoLin
     * @createdate 2018-11-06
     *
     */
    public function my_reply(){

        $data_list = Db::name('comment')
            ->alias('com')
            ->join('forum f','f.id = com.fid')
            ->join('forumcate c','c.id = f.tid')
            ->join('user u','u.id = f.id')
            ->field('com.id as com_id,f.id as forum_id,f.title,f.view,f.praise,c.name as cate_name,u.username,com.create_time,com.content')
            ->where(['com.uid'=>$this->userId])
            ->order(['com.create_time'=>'desc'])
            ->paginate(5,false,['query'=>$this->request->param()]);

        $list = $data_list->toArray()['data'];

        foreach($list as $key => &$value){
            $list[$key]['new'] = 0;
            $list[$key]['hot'] = 0;

            if ($value['create_time'] >= strtotime(date('Y/m/d'))) {
                $forumList[$key]['new'] = 1;
            }

            if ($value['view'] >= 1000) {
                $forumList[$key]['hot'] = 1;
            }

            $value['content'] = html_entity_decode($value['content']);
        }

        $this->assign([
            'data_list' => $list,
            'page'      => $data_list->render(),
        ]);

        return view();
    }

    /**
     * 我的收藏
     * @author GuoLin
     * @createdate 2018-11-06
     *
     */
    public function my_collect(){

        $data_list = Db::name('collect')
            ->alias('c')
            ->join('forum f','f.id = c.forum_id')
            ->join('user u','u.id = f.uid')
            ->join('forumcate cate','cate.id = f.tid')
            ->field('f.id,f.title,f.view,f.reply,f.praise,f.description,f.pic,f.settop,cate.name as cate_name,f.create_time,u.userhead')
            ->where(['c.uid'=>$this->userId,'f.open'=>1])
            ->paginate(15,false,['query'=>$this->request->param()]);

        $list = $data_list->toArray()['data'];

        foreach($list as $key => $value){
            $list[$key]['new'] = 0;
            $list[$key]['hot'] = 0;

            if ($value['create_time'] >= strtotime(date('Y/m/d'))) {
                $forumList[$key]['new'] = 1;
            }

            if ($value['view'] >= 1000) {
                $forumList[$key]['hot'] = 1;
            }
        }

        $this->assign([
            'data_list' => $list,
            'page'      => $data_list->render(),
        ]);

        return view();
    }

    /**
     * 取消收藏
     * @author GuoLin
     * @createdate 2018-11-08
     *
     */
    public function cancel_collect(){
        if($this->request->isPost()){

            $id = $this->request->param('collect_id');

            if(!$id){
                return json(['error_code'=>1,'msg'=>'非法请求']);
            }

            $result = Db::name('collect')->where(['uid'=>$this->userId,'forum_id'=>$id])->delete();

            if($result){
                return json(['error_code'=>0,'msg'=>'取消成功']);
            }else{
                return json(['error_code'=>3,'msg'=>'非法请求']);
            }

        }

    }

    /**
     * 系统消息
     * @author GuoLin
     * @createdate 2018-11-06
     *
     */
    public function system_messages(){

        $data_list = Db::name('message')->field('')->where(['type'=>1,'touid'=>$this->userId])->paginate(15,false,['query'=>$this->request->param()]);

        $this->assign([
            'data_list' => $data_list,
            'page'      => $data_list->render()
        ]);

        return view();
    }

    /**
     * 个人消息
     * @author GuoLin
     * @createdate 2018-11-06
     *
     */
    public function personal_messages(){

        $messages = Db::name('message')
            ->alias('m')
            ->join('user u','u.id = m.uid','left')
            ->join('forum f','m.fid = f.id','left')
            ->field('m.id,u.username,u.userhead,m.type,m.content,m.time,f.id as forum_id,f.title,m.is_read')
            ->where(['m.touid'=>$this->userId,'m.status'=>1,'u.status'=>1,'m.type'=>['in',['2','3']]])
            ->order('m.id desc')
            ->paginate(15, false, ['query' => $this->request->param()]);

        $ids = array_column($messages->toArray()['data'],'id');

        if($ids){
            Db::name('message')->where('id','in',$ids)->where('is_read','0')->update(['is_read'=>1]);
        }

        $this->assign([
            'data_list' => $messages,
            'page'      => $messages->render()
        ]);

        return view();
    }

    /**
     * 站内消息
     * @author GuoLin
     * @createdate 2018-11-06
     *
     */
    public function messages(){


        return view();
    }

    /**
     * 站内消息
     * @author GuoLin
     * @createdate 2018-11-06
     *
     */
    public function message_detail(){


        return view();
    }

    /**
     * 基础任务
     * @author GuoLin
     * @createdate 2018-11-06
     *
     */
    public function basic_tasks(){

        $task_list = Db::name('task')->where(['task_type' => 2,'status'=>1])->select();

        foreach ($task_list as $key => $value) {
            $task_list[$key]['taskStatus'] = 0;
            $task_list[$key]['taskNumber'] = 0;

            $result = getTaskStatus($value,$this->userId);

            if ($result === true) {
                $task_list[$key]['taskStatus'] = 1;
            }

            $task_list[$key]['taskNumber'] = $result === true ? $value['task_per']:$result;
        }

        $this->assign([
            'task_list' => $task_list
        ]);

        return view();
    }

    /**
     * 额外任务
     * @author GuoLin
     * @createdate 2018-11-06
     *
     */
    public function additional_tasks(){

        $taskList = Db::name('task')->field('id,task_name,task_allow_group,task_point,task_money')->where(['task_type'=>3,'status'=>1])->select();

        $this->assign([
            'task_list' => $taskList
        ]);

        return view();
    }

    /**
     * 每日任务
     * @author GuoLin
     * @createdate 2018-11-06
     *
     */
    public function daily_tasks(){

        //每日任务
        $day_task = Db::name('task')->where(['task_type' => 1,'status'=>1])->select();

        foreach ($day_task as $key => $value) {
            $day_task[$key]['taskStatus'] = 0;
            $day_task[$key]['taskNumber'] = 0;

            $result = getTaskStatus($value,$this->userId);

            if ($result === true) {
                $day_task[$key]['taskStatus'] = 1;
            }

            $day_task[$key]['taskNumber'] = $result === true ? $value['task_per']:$result;
        }

        $today_exist = Db::name('sign_record')->where(['uid' => session('userid'), 'create_time' => ['>=', strtotime(date('Y-m-d'))]])->find();

        $system = Db::name('system')->where('name', 'operate')->find()['value'];
        $system = unserialize($system);

        if (!$system['sign_min'] || $system['sign_min'] < 1) {
            return json(['code' => 0, 'msg' => '签到未开启']);
        }

        $point = mt_rand($system['sign_min'], $system['sign_max']);

        $money = mt_rand($system['sign_jinbi_min'], $system['sign_jinbi_max']);

        $this->assign([
            'task_list' => $day_task,
            'today_exist' => $today_exist,
            'sign_min'      => $system['sign_min'],
            'sign_max'      => $system['sign_max']
        ]);

        return view();
    }

    /**
     * 我的关注
     * @author GuoLin
     * @createdate 2018-11-06
     *
     */
    public function my_attention(){

        $data_list = Db::name('fans')
            ->alias('f')
            ->join('user u','u.id = f.uid')
            ->join('usergrade g','g.id = u.usergrade','left')
            ->field('f.uid,u.userhead,u.username,u.description,g.name as grade_name')
            ->where(['f.fans_uid'=>$this->userId])
            ->order(['f.create_time'=>'desc'])
            ->paginate(1,false,['query'=>$this->request->param()]);

        $this->assign([
            'data_list' => $data_list
        ]);

        return view();
    }
    /**
     * 我的粉丝
     * @author GuoLin
     * @createdate 2018-11-06
     *
     */
    public function my_fans(){

        $data_list = Db::name('fans')
            ->alias('f')
            ->join('user u','u.id = f.fans_uid')
            ->join('usergrade g','g.id = u.usergrade','left')
            ->field('f.fans_uid,u.userhead,u.username,u.description,g.name as grade_name')
            ->where(['f.uid'=>$this->userId])
            ->order(['f.create_time'=>'desc'])
            ->paginate(1,false,['query'=>$this->request->param()]);

        $this->assign([
            'data_list' => $data_list
        ]);

        return view();
    }

    /**
     * 邀请好友
     * @author GuoLin
     * @createdate 2018-11-06
     *
     */
    public function invite_friends(){
        return view();
    }

    /**
     * 黑名单
     * @author GuoLin
     * @createdate 2018-11-06
     *
     */
    public function blacklist(){

        $data_list = Db::name('blacklist')
            ->alias('b')
            ->join('user u','b.other_uid = u.id')
            ->join('usergrade g','u.usergrade = g.id')
            ->field('b.other_uid,u.username,u.userhead,g.name as grade_name,u.description')
            ->where(['uid'=>$this->userId])
            ->order(['b.create_time'=>'desc'])
            ->paginate(15,false,['query'=>$this->request->param()]);

        $this->assign([
            'data_list' => $data_list
        ]);
        return view();
    }

    /**
     * 移除黑名单
     * @author GuoLin
     * @createdate 2018-11-06
     *
     */
    public function remove_blacklist(){
        if($this->request->isPost()){

            $uid = $this->request->param('uid');

            if(!$uid){
                return json(['error_code'=>1,'msg'=>'参数不能为空']);
            }

            $result = Db::name('blacklist')->where(['uid'=>$this->userId,'other_uid'=>$uid])->delete();

            if($result){
                return json(['error_code'=>0,'msg'=>'移除成功']);
            }else{
                return json(['error_code'=>2,'msg'=>'移除失败']);
            }
        }
    }



    /**
     * 个人主页
     * @author GuoLin
     * @createdate 2018-10-26
     *
     */
    public function home()
    {
        $user_data = Db::name('user')->field('provid,cityid,areaid')->where(['id'=>$this->userId])->find();

        $collect_count = Db::name('collect')->where(['uid'=>$this->userId])->count();

        //主页 动态
        $forum = Db::name('forum')
            ->alias('f')
            ->join('user u', 'f.uid=u.id')
            ->field('f.id,f.uid,f.title,f.content,f.view,f.reply,f.praise,f.reply_time,f.pic,f.choice,f.settop,f.create_time,u.username,u.userhead,f.description')
            ->where(['u.id' => $this->userId, 'f.open' => 1])
            ->order('f.create_time desc')
            ->paginate(3, false, ['query' => request()->param()]);

        $forumList = $forum->toArray()['data'];

        foreach ($forumList as $key => &$val) {

            $forumList[$key]['new'] = '';
            $forumList[$key]['hot'] = '';

            if ($val['create_time'] >= strtotime(date('Y/m/d'))) {
                $forumList[$key]['new'] = 1;
            }

            if ($val['view'] > 1000) {
                $forumList[$key]['hot'] = 1;
            }

            $val['description'] = strip_tags(html_entity_decode($val['content']));

        }

        $attention_count = Db::name('fans')->where(['fans_uid'=>$this->userId])->count();
        $fans_count = Db::name('fans')->where(['uid'=>$this->userId])->count();

        $this->assign([
            'forum_list'        => $forumList,
            'page'              => $forum->render(),
            'collect_count'     => $collect_count,
            'attention_count'   => $attention_count,
            'fans_count'        => $fans_count,
//            'user_data'         => $user_data,

        ]);

        return view();
    }


//    public function



















}