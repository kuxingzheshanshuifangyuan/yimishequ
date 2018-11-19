<?php

namespace app\common\controller;

use think\Cache;
use think\Controller;
use think\Db;
use think\Config;
use think\Session;
use Captcha\Captcha;
use think\Loader;

class HomeBase extends Controller
{

    protected function _initialize()
    {
        parent::_initialize();
        $this->getSystem();
        $this->getNav();
        $this->systemlogin();

        $user_data = [];
        if (session('userid')) {
            $user_data = Db::name('user')
                ->alias('u')
                ->join('usergrade g','g.id = u.usergrade','left')
                ->field('u.point,u.username,u.userhead,u.money,u.id,u.view,u.description,u.mobile,u.wechat,u.qq,g.name as grade_name,g.money as grade_money,u.sex,u.wechat_unionid,u.wechat_nickname,usermail,provid,cityid,areaid')
                ->where('u.id', session('userid'))
                ->find();
            $user_data['forum_count'] = Db::name('forum')->where(['uid' => session('userid'),'open'=>1])->count();

            $user_data['msg_count'] = Db::name('message')->where(['touid' => session('userid') ,'is_read'=>0])->count();

        }

        //底部 板块栏 信息
        $forumcate=Db::name('forumcate')->where(['show'=>1])->order(['sort'=>'asc'])->limit(6)->select();

        $this->assign([
            'user_data' => $user_data,
            'forumcate' => $forumcate,
        ]);

    }

    protected function systemlogin()
    {
        if (!session('userid') || !session('username')) {

            $user = unserialize(decrypt(cookie('sys_key')));
            if ((empty($user['id'])) || (empty($user['username']))) {

            } else {
                systemSetKey($user);
                if ($user['userhead'] == '') {
                    $user['userhead'] = '/public/images/default.png';
                }
                session('userstatus', $user['status']);
                session('usergrade', $user['usergrade']);
                session('userhead', $user['userhead']);
                session('username', $user['username']);
                session('userid', $user['id']);
                session('point', $user['point']);
                Db::name('user')->update(
                    [
                        'last_login_time' => time(),
                        'last_login_ip' => $this->request->ip(),
                        'id' => $user['id']
                    ]
                );

            }
        }

    }

    /**
     * 添加邮件到队列
     */
    protected function _mail_queue($to, $subject, $body, $priority = 1, $bool = false)
    {
        $to_emails = is_array($to) ? $to : array($to);
        $mails = array();
        $time = time();
        foreach ($to_emails as $_email) {
            $mails[] = array(
                'mail_to' => $_email,
                'mail_subject' => $subject,
                'mail_body' => $body,
                'priority' => $priority,
                'add_time' => $time,
                'lock_expiry' => $time,
            );
        }
        $user = model('MailQueue');
        $user->addAll($mails);

        //异步发送邮件
        $this->db_send_mail($bool);
    }

    /**
     * 发送邮件
     */
    public function db_send_mail($is_sync = true)
    {
        if (!$is_sync) {
            //异步
            session('async_sendmail', true);
            return true;
        } else {
            //同步
            session('async_sendmail', null);
            $user = model('MailQueue');
            return $user->send();
        }
    }

    /**
     * 获取站点信息
     */
    protected function getSystem()
    {
        if (Cache::has('site_config')) {
            $site_config = Cache::get('site_config');
        } else {
            $site_config = Db::name('system')->field('value')->where('name', 'site_config')->find();
            $site_config = unserialize($site_config['value']);
            Cache::set('site_config', $site_config);
        }

        if (empty($site_config['jifen_name'])) {
            $site_config['jifen_name'] = '积分';
        }
        $this->assign('site_config', $site_config);
    }

    /**
     * 获取前端导航列表
     */
    protected function getNav()
    {
        if (Cache::has('nav')) {
            $top = Cache::get('nav');
        }else{
            //导航
            $nav = Db::table("ea_nav")->where(['status' => 1,'nav_id'=>0])->order('sort','asc')->select();
            $top = [];

            foreach($nav as $key=>$val){
                $top[$key]['top_nav'] = $val['name'];
                $top[$key]['top_link']= $val['link'];
                $top[$key]['top_id']  = $val['id'];
                $top[$key]['forumcate_id'] = $val['forumcate_id'];
                $second=Db::name('nav')->where('nav_id',$val['id'])->select();
                foreach($second as $k=>$v){
                    $top[$key]['second'][$k]['second_nav'] = $v['name'];
                    $top[$key]['second'][$k]['second_link']= $v['link'];
                    $top[$key]['second'][$k]['second_id']  = $v['id'];
                    $top[$key]['second'][$k]['forumcate_id'] = $v['forumcate_id'];
                    $top[$key]['second'][$k]['second_size']= $v['id'] ? \model('Forum')->where(['tid'=> $v['forumcate_id']])->count() : 0;
                }
            }

            if (!empty($nav)) {
                Cache::set('nav', $top);
            }

        }

        $this->assign('top', $top);
    }

    public function captcha()
    {
        $m = new Captcha(Config::get('captcha'));
        $img = $m->entry();
        return $img;
    }

    /**
     * 获取短信验证码
     * @author GuoLin
     * @createdate 2018-07-28
     *
     */
    public function getSms($phone = ''){

        if(!$this->request->param('phone') && !$phone){
            return json(['code'=>0,'msg'=>'手机号不能为空']);
        }

        if(!$phone) $phone = $this->request->param('phone');

        if(!check_mobile_number($phone)){
            return json(['code'=>0,'msg'=>'手机号格式有误']);
        }

        Loader::import('cmpp.Cmpp', EXTEND_PATH);

        $sms = new \Cmpp();

        $code = mt_rand(10000,99999);

        $content = '【一米社区】 您的验证码是'.$code.'。如非本人操作，请忽略本短信';

        session('cmsCode',$code);

        $smsStatus = $sms->yunsms($phone,$content);

        if($smsStatus){
            return json(['code'=>1,'msg'=>'短信发送成功']);
        }else{
            return json(['code'=>0,'msg'=>'短信发送失败']);
        }
    }


}