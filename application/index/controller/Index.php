<?php

namespace app\index\controller;

use org\Http;
use app\common\controller\HomeBase;
use think\Controller;
use think\Session;
use think\Db;
use think\Model;
use think\Cache;
use think\File;
use app\common\model\Forumcate as ForumcateModel;
use app\common\model\User as UserModel;
use think\Template;

class Index extends HomeBase
{

    protected $userId;
    protected $userGrade;

    public function _initialize()
    {
        parent::_initialize();
        $this->userId = session('userid');
        $this->userGrade = session('usergrade');

    }

    public function login()
    {

        $site_config = Cache::get('site_config');
        $yzmarr = explode(',', $site_config['site_yzm']);

        if (in_array(2, $yzmarr)) {
            $loginyzm = 1;
        } else {
            $loginyzm = 0;
        }


        $this->assign('loginyzm', $loginyzm);
        $member = new UserModel();
        if (request()->isPost()) {
           /* if (in_array(2, $yzmarr)) {
                if (!captcha_check(input('code'))) {

                    return json(array('code' => 0, 'msg' => '验证码错误'));

                }

            }*/
            $account  = $this->request->param('account');
            $password = $this->request->param('password');
            $rememb   = $this->request->param('rememb');

            if (!$account) {
                return json(array('code' => 0, 'msg' => '账号不能为空'));
            }

            if (!$password) {
                return json(array('code' => 0, 'msg' => '密码不能为空'));
            }

            $status['status'] = array('gt', 0);
            $user = $member->where($status)->where('usermail|username|mobile', $account)->find();

            if ($user) {
                autoUpGrade($user['id']);
                if ($user['password'] == md5(md5($password))) {

                    if ($user['userhead'] == '') {
                        $user['userhead'] = '/public/images/default.png';
                    }
                    session('userstatus', $user['status']);
                    session('usergrade', $user['usergrade']);
                    session('userhead', $user['userhead']);
                    session('username', $user['username']);
                    session('userid', $user['id']);
                    session('point', $user['point']);

                    $cook = array('id' => $user['id'], 'status' => $user['status'], 'point' => $user['point'], 'username' => $user['username'], 'userhead' => $user['userhead'], 'usergrade' => $user['usergrade']);
                    systemSetKey($cook);

                    $member->update(
                        [
                            'last_login_time' => time(),
                            'last_login_ip' => $this->request->ip(),
                            'id' => $user['id']
                        ]
                    );

                    //$member->where('id',session('userid'))->setInc('point',$site_config['jifen_login']);
//                    point_note($site_config['jifen_login'], session('userid'), 'login');

                    if (isset($rememb) && $rememb == 1){
                        cookie('rememb', ['account' => $account, 'password' => $password], 604800);
                    } else {
                        cookie('rememb', null);
                    }

                    return json(array('code' => 1, 'msg' => '登录成功'));
                } else {
                    return json(array('code' => 0, 'msg' => '密码错误'));
                }
            } else {
                return json(array('code' => 0, 'msg' => '账号错误或已禁用'));
            }
        }
        return view();
    }

    public function getemotion()
    {
        $path = WEB_URL . '/public/wangEditor/emotion/';

        $dir = ROOT_PATH . '\\public\\wangEditor\\emotion\\';

        $array = array();
        foreach (glob($dir . '*', GLOB_ONLYDIR) as $files) {

            $files1 = iconv('GB2312', 'UTF-8', $files);
            $filename = explode('\\', $files1);
            $filename = array_pop($filename);
            $k = $filename;
            $array[$k]['title'] = $filename;

            if (is_dir($files)) {

                $array[$k]['data'] = array();

                if ($dh = opendir($files)) {
                    while (($file = readdir($dh)) !== false) {

                        if ($file != "." && $file != "..") {

                            $result = pathinfo($file);
                            $file = iconv('GB2312', 'UTF-8', $file);
                            $n = str_replace('.' . $result['extension'], '', $file);
                            array_push($array[$k]['data'], array('icon' => $path . $filename . '/' . $file, 'value' => $n));
                        }

                    }
                    closedir($dh);
                }
            }

        }

        return json_encode($array);

    }

    public function index()
    {
        $forum = Db::name('forum');
        //最新
        $open['open'] = 1;
        $newest = $forum->field('id,title,create_time')->where($open)->where(['tid'=>['NOT IN',[30,31,32,33,34]]])->order('id desc')->limit(8)->select();
        $this->assign('newest', $newest);

        //最热
        $hot = $forum->field('id,title,create_time')->where($open)->where(['create_time'=>['>=',strtotime(date('Y-m-d',time()-86400*30))],'tid'=>['NOT IN',[30,31,32,33,34]]])->order('view desc')->limit(8)->select();
        $this->assign('hot', $hot);

        //精品推荐
        $recommend_where['tid'] = 16;
        $recommend = $forum->field('id,title,create_time')->where($open)->where($recommend_where)->order('id desc')->limit(8)->select();
        $this->assign('recommend', $recommend);

        //热点资讯
        $HotNews_where['tid'] = 17;
        $HotNews = $forum->field('id,title,create_time')->where($open)->where($HotNews_where)->order('id desc')->limit(8)->select();
        $this->assign('HotNews', $HotNews);

        //下载教程
        $DownTutorial_where['tid'] = 19;
        $DownTutorial = $forum->field('id,title,create_time,pic,content')->where($open)->where($DownTutorial_where)->order('id desc')->limit(4)->select();

        foreach ($DownTutorial as $key => &$val){
            $val['pic'] = getPic(html_entity_decode($val['content']));
        }

        $this->assign('DownTutorial', $DownTutorial);

        //轮播图
        $banner = Db::name('banner')->where('status', 1)->order('order','asc')->limit(3)->select();
        $this->assign('banner', $banner);
        //广告图
        $ad = Db::name('advertising')->where('status', 1)->order('sort','asc')->limit(2)->select();
        $this->assign('ad', $ad);


        //App
        $app = Db::name('app')->where('status', 1)->order('id desc')->limit(6)->select();
        $this->assign('app', $app);

        //成功案例
        $demo = Db::name('money')->where('status', 1)->select();
        $this->assign('demo', $demo);

        //友情链接
        $link = Db::name('link')->where('status', 1)->order('sort asc')->order('id desc')->limit(6)->select();
        $this->assign('link', $link);
        $userId=session('userid');

        $userGradeId= $this->userGrade;

        if (session('userid') || session('username')) {

            $sign_exist = Db::name('sign_record')->where(['uid'=>session('userid'),'create_time'=>['>=',strtotime(date('Y-m-d'))]])->find();
            $this->assign('sign_exist', $sign_exist);

            //新手任务
            $newbieTask = Db::name('task')->where(['task_type' => 2,'status'=>1])->select();

            foreach ($newbieTask as $key => $value) {
                $newbieTask[$key]['taskStatus'] = 0;
                $newbieTask[$key]['taskNumber'] = 0;
                $result = getTaskStatus($value,$userId,$userGradeId);

                if ($result === true) {
                    $newbieTask[$key]['taskStatus'] = 1;
                }
                $newbieTask[$key]['taskNumber'] = $result;
            }

            $this->assign('newbieTask', $newbieTask);

            //每日任务
            $day_task = Db::name('task')->where(['task_type' => 1,'status'=>1])->limit(7)->select();

            foreach ($day_task as $key => $value) {
                $day_task[$key]['taskStatus'] = 0;
                $day_task[$key]['taskNumber'] = 0;

                $result = getTaskStatus($value,$userId,$userGradeId);

                if ($result === true) {
                    $day_task[$key]['taskStatus'] = 1;
                }
                $day_task[$key]['taskNumber'] = $result;
            }

//            dump($day_task);

            $this->assign('day_task', $day_task);

        }


        return view();
    }

    public function yzemailurl($id)
    {
        if (!session('userid') || !session('username')) {

            $this->error('亲！请登录', url('index/login'));
        } else {
            $uid = session('userid');
            $user = db('user')->where(array('id' => $uid))->find();

            if ($id == md5($user['salt'] . $uid . $user['usermail'])) {
                if ($user['status'] == 1) {

                    db('user')->where(array('id' => $uid))->setField('status', 2);

                } else {
                    db('user')->where(array('id' => $uid))->setField('status', 5);

                }
                $site_config = Cache::get('site_config');

//                point_note($site_config['jifen_email'], $uid, 'yzemail');
                $this->success('验证成功', url('user/set'));

            } else {
                $this->error('非法验证', url('user/set'));
            }

        }

    }

    public function yzemail()
    {

        $mail = $this->request->param();
        $uid = session('userid');
        $user = db('user')->where(array('id' => $uid))->find();
        $mailarr = db('user')->column('usermail');
        if (in_array($mail['email'], $mailarr) && $user['usermail'] != $mail['email']) {
            return json(array('code' => 0, 'msg' => '该邮箱已经被其他账号注册'));
        } else {
            $n['usermail'] = $mail['email'];
            db('user')->where(array('id' => $uid))->update($n);
            $data['email'] = $mail['email'];
            $data['title'] = '邮箱验证';
            $str = md5($user['salt'] . $uid . $data['email']);
            $data['body'] = 'http://' . $_SERVER['HTTP_HOST'] . url('index/yzemailurl') . '?id=' . $str;
            asyn_sendmail($data);
            return json(array('code' => 1, 'msg' => '邮件已发送，请到邮箱进行查收'));

        }

    }

    public function reyzemail()
    {
        $mail = $this->request->param();
        $uid = session('userid');
        $user = db('user')->where(array('id' => $uid))->find();

        $mailarr = db('user')->column('usermail');
        if (in_array($mail['email'], $mailarr) && $user['usermail'] != $mail['email']) {
            return json(array('code' => 0, 'msg' => '该邮箱已经被其他账号注册'));
        } else {

            $n['usermail'] = $mail['email'];
            if ($user['status'] == 2) {
                $n['status'] = 1;
            } else {
                $n['status'] = 3;
            }

            db('user')->where(array('id' => $uid))->update($n);

            $data['email'] = $mail['email'];
            $data['title'] = '邮箱验证';
            $str = md5($user['salt'] . $uid . $data['email']);
            $data['body'] = 'http://' . $_SERVER['HTTP_HOST'] . url('index/yzemailurl') . '?id=' . $str;
            asyn_sendmail($data);
            return json(array('code' => 1, 'msg' => '邮箱登录已更改为新邮箱，请到邮箱查收验证'));
        }

    }

    public function send_mail()
    {
        $mail = $this->request->param();

        $res = send_mail_local($mail['email'], $mail['title'], $mail['body']);

        if ($res == 1) {
            return json(array('code' => 1, 'msg' => '邮件已发送，请到邮箱进行查收'));
            //	$this->success('邮件已发送，请到邮箱进行查收');
        } else {
            return json(array('code' => 0, 'msg' => '邮件发送失败，请检查邮箱设置'));
            //$this->error('邮件发送失败，请检查邮箱设置');
        }
    }

    public function search()
    {
        $ks = input('ks');
        $kss = urldecode(input('ks'));
        if (empty($ks)) {
            return $this->error('亲！你迷路了');
        } else {
            $forum = Db::name('forum');
            $open['open'] = 1;


            $map['f.title|f.keywords'] = ['like', "%{$kss}%"];

            $tptc = $forum->alias('f')->join('forumcate c', 'c.id=f.tid')->join('user m', 'm.id=f.uid')->field('f.*,c.id as cid,m.id as userid,m.userhead,m.username,c.name')->order('f.id desc')->where($open)->where($map)->paginate(15, false, $config = ['query' => array('ks' => $ks)]);
            $this->assign('tptc', $tptc);
            return view();
        }
    }

    public function guan()
    {
        $id = 59;
        $down = Db::name('attach')->where('id', $id)->find();
        $b = 1000;
        $c = 10000;
        $hits = $down['download'] + 13300;


        if ($hits > $b) {
            if ($hits < $c) {

                $down['download'] = floor($hits / $b) . '千';
            } else {

                $down['download'] = (floor(($hits / $c) * 10) / 10) . '万';
            }
        } else {
            $down['download'] = $hits;
        }
        $down['download'] = $down['download'] . '+';
        $this->assign('down', $down);

        return view();
    }

    public function downinfo()
    {
        $data = request()->param();
        Db::name('attach')->where('id', $data['id'])->setInc('download');
        $info = Db::name('attach')->where('id', $data['id'])->find();
        return json(array('code' => 200, 'msg' => '开始下载', 'url' => $info['savepath']));
    }

    public function view()
    {
        $id = input('id');
        session('forumviewid', $id);
        if (empty($id)) {
            return $this->error('亲！你迷路了');
        } else {
            $category = Db::name('forumcate');


            $c = $category->where('id', $id)->find();
            if ($c) {
                $forum = Db::name('forum');
                $open['open'] = 1;
                $catemodel = new ForumcateModel();
                $children = $catemodel->getchilrenid($id);

                array_push($children, $id);

                $tptc = $forum->alias('f')->join('forumcate c', 'c.id=f.tid')->join('user m', 'm.id=f.uid')->field('f.*,c.id as cid,m.id as userid,m.userhead,m.username,m.grades,c.name')->where('f.tid', 'in', $children)->where($open)->order('f.settop desc,f.id desc')->paginate(15);
                $this->assign('tptc', $tptc);

                $this->assign('name', $c['name']);
                return view();
            } else {
                $this->error("亲！你迷路了！");
            }
        }
    }

    public function forum()
    {
        return view();
    }

    public function choice()
    {
        $forum = Db::name('forum');
        $open['open'] = 1;
        $choice['choice'] = 1;
        $tptc = $forum->alias('f')->join('forumcate c', 'c.id=f.tid')->join('user m', 'm.id=f.uid')->field('f.*,c.id as cid,m.id as userid,m.userhead,m.username,c.name')->where($open)->where($choice)->order('f.settop desc,f.id desc')->paginate(15);
        $this->assign('tptc', $tptc);
        return view();
    }

    public function errors()
    {
        return view();
    }

    public function download($url, $name, $local)
    {
        $down = new Http();
        if ($local == 1) {
            $down->download($url, $name);
        } else {

            //echo 	$down->curlDownload($url,$name);

        }

    }


    public function thread()
    {
        $id = input('id');
        if (empty($id)) {
            return $this->error('亲！你迷路了');
        } else {
            $forum = Db::name('forum');
            $a = $forum->where("id = {$id}")->find();
            if ($a) {
                $forum->where("id = {$id}")->setInc('view', 1);
                $t = $forum->alias('f')->join('forumcate c', 'c.id=f.tid')->join('user m', 'm.id=f.uid')->field('f.*,c.id as cid,c.name,m.id as userid,m.grades,m.point,m.userhead,m.username,m.status')->find($id);
                $this->assign('t', $t);
                if ($t['keywords'] != '') {
                    $keywordarr = explode(',', $t['keywords']);
                    $this->assign('keywordarr', $keywordarr);
                }
                $comment['uid'] = array('not in', Db::name('user')->where('status', 'elt', 0)->column('id'));

                if ($t['status'] <= 0) {
                    $content = '<font color="#FF5722">该用户已被禁用或禁言</font>';


                } else {
                    $content = $t['content'];

                    $content = hook('threadfeecontent', array('content' => $content, 'id' => $t['id'], 'uid' => session('userid'), 'zuid' => $t['userid']), true, 'content');


                }
                $tptc = Db::name('comment')->alias('c')->join('user m', 'm.id=c.uid')->where("fid = {$id}")->where($comment)->order('c.id asc')->field('c.*,m.id as userid,m.grades,m.point,m.userhead,m.username')->paginate(15);

                $this->assign('tptc', $tptc);
                $this->assign('content', $content);

                return view();
            } else {
                return $this->error('亲！你迷路了');
            }
        }
    }

    /**
     * 微信登陆详情内容
     * */
    public function wechat(){
        $url1 = urlencode("http://www.1miclub.com".url('wechat_login', '', true, false, true));
        $redirect_ur1 = "https://open.weixin.qq.com/connect/qrconnect?appid=wxcfd9143033bcf806&redirect_uri=$url1&response_type=code&scope=snsapi_login&state=3d6be0a4035d839573b04816624a415e#wechat_redirect";
        header("location:$redirect_ur1");
    }
    /**
     * 微信登陆回调
     * */
    public function wechat_login(){
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
        $access_token = json_decode($output,true);
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
        $data = json_decode($output1,true);

//        $data['username'] = $list['nickname'];
//
//        $data['userhead'] = $list['headimgurl'];
//
//        $data['sex'] = $list['sex'];
//        $data['userhome'] = $list['country'].$list['province'].$list['city'];
//        $data['wechat_identifying'] = $list['openid'];

        //登陆开始
        $userData = Db::name('user')
            ->field('id as userid,username,point,money,mobile,sex,status,userhead,regtime,is_username,usergrade')
            ->where(['wechat_unionid'=>$data['unionid']])
            ->find();

        if ($userData) {

            if($userData['status'] == 0){
                $this->error('账号已被禁止登陆');
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

            session('userstatus', $userData['status']);
            session('usergrade', $userData['usergrade']);
            session('userhead', $userData['userhead']);
            session('username', $userData['username']);
            session('userid', $userData['userid']);
            session('point', $userData['point']);

            $this->redirect(url('index'));

        } else {

            $userGradeId = Db::name('usergrade')->order(['score'=>'asc'])->value('id') ?:0;

            $insertData = [
                'mobile'            => '',
                'status'            => 1,       //config('web.WEB_REG');
                'point'             => 0,       //config('point.REG_POINT');
                'sex'               => $data['sex'],
                'userhead'          => $data['headimgurl']?:'/public/images/default.png',
                'userip'            => $this->request->ip(),
                'password'          => '',
                'usergrade'         => $userGradeId,
                'wechat_openid'     => $data['openid'],
                'wechat_unionid'    => $data['unionid'],
                'wechat_nickname'   => $data['nickname'],
                'userhome'          => $data['country'].$data['province'].$data['city'],
                'is_username'       => 0,
                'regtime'           => time(),
            ];

            $userId = Db::name('user')->insertGetId($insertData);

            if($userId){
                $nameExist = false;
                $userName = '米友'.$userId;
                if($data['nickname']){
                    $nameExist = Db::name('user')->where(['username'=>$data['nickname']])->count();
                }else{
                    $data['nickname'] = $userName;
                }
                $userName = $nameExist?$userName:$data['nickname'];
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

                session('userstatus', $userData['status']);
                session('usergrade', $insertData['usergrade']);
                session('userhead', $userData['userhead']);
                session('username', $userData['username']);
                session('userid', $userData['userid']);
                session('point', $userData['point']);

                $this->redirect(url('index'));

            }else{

                $this->error('微信登陆失败');
            }
        }

    }


    public function invite_register(){

        $tid = $this->request->param('tid',false);
        if(!$tid){
            $this->error('非法请求');
        }

        $user = Db::name('user')->where(['id'=>$tid])->find();

        if(!$user){
            $this->error('未找到推广人信息');
        }

        $this->assign([
            'tid'   => $tid
        ]);

        return view();
    }

    public function invite_dowload(){
        return view();
    }

    public function testGread(){
        autoUpGrade(11);
    }

    public function testArray(){
        array_diff([],[],[],[],[]);
        array_merge();
        array_intersect();
        array_unique();
        array_push();
        array_pop();
        array_shift();
        array_unshift($arr,1);
        array_splice($arr,1,3);
        array_column();
        array_rand();
        shuffle();
        array_reverse($arr);
        count($arr);
        in_array('1',$arr);
        $a=array("a"=>"red","b"=>"green","c"=>"blue");
        echo array_search("red",$a); // a
    }

    public function testFile(){

        basename('','');

    }

    public function testStr(){
        $str = 'hello word';
        strlen($str);//返回字符串长度 mb_strlen($str) 可以返回中文字符长度；
        mb_strlen($str);//返回中文字符长度；
        strtolower($str);//字母转小写
        strtoupper($str);//字母转大写
        ucwords($str);//每一个单词的首字母转大写
        ucfirst($str);//首字母转大写
        str_replace('a','b',$str);//b替换$str 中的a 区分大小写  ;
        str_ireplace('a','b',$str);//替换 不区分大小写
        htmlspecialchars($str,ENT_NOQUOTES);
        //字符串转换为html 实体 ENT_COMPT(默认只编译双引号)ENT_QUOTES单引号双引号都编译,ENT_NOQUOTES不编译任何引号
        trim($str);//删除字符串前后（左右）空格
        ltrim($str);//只删除字符串左侧的空格
        rtrim($str);//只删除字符串右侧的空格
        //trim加第二个参数 就是移除指定的字符集 如ltrim($str,'0..9') 移除左侧数字开头的字符
        strpos($str,'a');//字符串a 在$str 第一次出现的位置 索引0开始 没有出现返回false 区分大小写
        stripos($str,'a');//同上 但是不区分大小写
        strrpos($str,'a');  //字符串a 在$str 最后一次出现的位置 索引0开始 没有出现返回false 区分大小写
        strripos($str,'a');//同上 但是不区分大小写
        substr($str,0,3);//截取字符串 $str 的第一个字符 截取长度3 长度不填默认截取到最后  参数为负数则倒数
        strstr($str,'a');//截取字符串 $str 中的第一个字符'a'后的字符串 如 sabc -> abc
        strrchr($str,'o');//截取字符串 $str 中最后一一个字符'a'后的字符串
        strrev($str);       //字符串反转 abcd->dcba
        str_shuffle($str);//随机打乱字符串顺序
        explode('-',$str);//指定分隔符分割字符串 返回数组 ‘-’ 分割$str
        implode('-',$str);//数组拼接字符串 与explode()相反说
    }

    public function testDate(){
        array_diff();
        array_intersect();
        array_push();
        array_pop();
        array_shift();
        array_unshift();
        array_unique();
        array_merge();
        shuffle();
        array_column();
        array_rand();
        array_search();
        array_splice();
        in_array();
        count();
        array_reverse();

        $str = 'abcdefg';
        strlen($str);
        mb_strlen($str);
        strpos($str,'c');
        stripos($str,'c');
        strripos($str,'c');
        substr($str,0,1);
        strstr($str,'c');
        strrchr($str,'c');
        strrev($str);
        strtolower($str);
        strtoupper($str);
        ucwords($str);
        ucfirst($str);
        str_replace('a','b',$str);
        str_repeat('a',10);
        implode(',',$str);
        explode(',',$str);
        str_shuffle($str);
        trim(',','');
        ltrim();
        rtrim();
        htmlspecialchars_decode($str);
        htmlentities();

        file_put_contents();
        file_get_contents();

        rename();
        unlink();

        mt_rand();
        round();
        floor();
        ceil();

        strtotime();
        time();
        date();

        define();
        defined();

        intval();
        strval();




    }




}