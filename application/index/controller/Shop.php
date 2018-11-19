<?php

namespace app\index\controller;

use org\Http;
use app\common\controller\HomeBase;
use think\Controller;
use think\Session;
use think\Db;

use think\Cache;

class Shop extends HomeBase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {

        $order = "score asc,time desc";
        if (request()->isPost()) {

            $data = request()->param();
            session('tidval', $data['tidval']);
            session('openval', $data['openval']);
            session('typeval', $data['typeval']);
            session('issqlval', $data['issqlval']);
            session('isadminval', $data['isadminval']);
            session('isconfigval', $data['isconfigval']);
            session('scoreval', $data['scoreval']);
            session('orderval', $data['orderval']);


        }
        if (session('tidval') != 0) {
            $map['tid'] = session('tidval');
        }
        if (session('openval') != 0) {
            $map['open'] = session('openval');
        }
        if (session('typeval') != 0) {
            $map['type'] = session('typeval');
        }
        if (session('issqlval') != 0) {
            $map['issql'] = session('issqlval');
        }
        if (session('isadminval') != 0) {
            $map['isadmin'] = session('isadminval');
        }
        if (session('isconfigval') != 0) {
            $map['isconfig'] = session('isconfigval');
        }

        if (session('orderval') == 0) {
            $order = "score asc,time desc";
        }
        if (session('orderval') == 1) {
            $order = "time desc";
        }
        if (session('orderval') == 2) {
            $order = "score desc";
        }

        if (session('scoreval') > 0) {
            if (session('scoreval') == 1) {
                $map['score'] = 0;
            }
            if (session('scoreval') == 2) {
                $map['score'] = array('between', '1,50');
            }
            if (session('scoreval') == 3) {
                $map['score'] = array('between', '51,100');
            }
            if (session('scoreval') == 4) {
                $map['score'] = array('between', '101,1000');
            }
            if (session('scoreval') == 5) {
                $map['score'] = array('gt', '1001');
            }

        } else {

        }


        if (!isset($map))
            $map = array();


        $list = model('shopitem')->where($map)->order($order)->paginate(16);


        $count = model('shopitem')->where($map)->count();
        $this->assign('count', $count);

        $this->assign('list', $list);
        return view();
    }

    public function view()
    {

        $id = input('id');
        model('shopitem')->where("id = {$id}")->setInc('view', 1);

        $t = model('shopitem')->alias('f')->join('user m', 'm.id=f.uid')->join('attach a', 'a.id=f.attach')->join('point_note p', 'p.pointid=f.id and p.controller="buyshop" and p.score<0')->field('f.*,m.id as userid,m.grades,m.point,m.userhead,m.username,m.userhome,m.status,a.size,count(p.id) as itemcount')->find($id);
        $this->assign('t', $t);


        if ($t['status'] <= 0) {
            $content = '<font color="#FF5722">该用户已被禁用或禁言</font>';


        } else {
            $content = $t['content'];


        }
        $uid = session('userid');
        $t['down'] = 0;
        if ($uid == $t['uid']) {
            $t['down'] = 1;
        }
        $m['uid'] = $uid;
        $m['pointid'] = $t['id'];
        $m['controller'] = 'buyshop';

        $c = Db::name('point_note')->where($m)->count();
        if ($c > 0) {
            $t['down'] = 1;
        }

        $this->assign('content', $content);
        $map['tid'] = 4;

        $tp = Db::name('forum')->where($map)->order('view desc')->limit(10)->select();


        $this->assign('tp', $tp);

        return view();
    }

    public function download()
    {
        $param = request()->post();


        $map1['id'] = $param['id'];
        $fileid = Db::name('shopitem')->where($map1)->value('attach');

        $map['id'] = $fileid;
        $m = Db::name('attach')->where($map)->find();


        if (preg_match("/^(http:\/\/|https:\/\/).*$/", $m['savepath'])) {
            $remote = 1;
        } else {
            $remote = 0;
        }


        if (empty($m)) {
            return array('code' => 0, 'msg' => '该附件不存在');
        } else {


            if ($param['down'] != 1) {

                $note['controller'] = 'buyshop';
                $note['uid'] = $param['uid'];
                $note['pointid'] = $param['id'];

                $point = Db::name('user')->where('id', $param['uid'])->value('point');
                if ($point < $param['score'] && $param['score'] > 0) {

                    $site_config = Cache::get('site_config');
                    if (empty($site_config['jifen_name'])) {
                        $site_config['jifen_name'] = '积分';
                    }

                    return array('code' => 0, 'msg' => $site_config['jifen_name'] . '不足');
                } else {

                    if ($param['score'] > 0) {
                        if (session('grades') == 5) {

                        } else {
//                            point_note(0 - $param['score'], $param['uid'], 'buyshop', $param['id']);
//                            point_note($param['score'], $param['zuid'], 'buyshop', $param['id']);
                        }


                    }

                    Db::name('attach')->where($map)->setInc('download');


                    if ($remote == 1) {
                        return array('code' => 200, 'msg' => '开始下载', 'path' => $m['savepath'], 'name' => $m['name'], 'local' => 0);
                    } else {
                        $dir = dirname($_SERVER['SCRIPT_FILENAME']) . '/';
                        return array('code' => 200, 'msg' => '开始下载', 'path' => $dir . $m['savepath'], 'name' => $m['name'], 'local' => 1);
                    }


                }


            } else {
                Db::name('attach')->where($map)->setInc('download');

                if ($remote == 1) {
                    return array('code' => 200, 'msg' => '开始下载', 'path' => $m['savepath'], 'name' => $m['name'], 'local' => 0);
                } else {
                    $dir = dirname($_SERVER['SCRIPT_FILENAME']) . '/';
                    return array('code' => 200, 'msg' => '开始下载', 'path' => $dir . $m['savepath'], 'name' => $m['name'], 'local' => 1);
                }

            }


        }


    }

    public function add()
    {

        $map['name'] = 'attach';
        $map['status'] = 1;
        $config = Db::name('addons')->where($map)->value('config');
        $config = json_decode($config, true);
        $this->assign('configext', $config['configext']);

        $site_config = Cache::get('site_config');

        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录', url('index/login'));
        } else {


            if (session('userid') != 2) {
                $this->error('非法操作');
            }

            if (request()->isPost()) {

                if (session('userstatus') != 2 && session('userstatus') != 5 && $site_config['email_sh'] == 0) {
                    return json(array('code' => 0, 'msg' => '您的邮箱还未激活'));
                }


                $data = input('post.');

                if ($data['content'] == '') {
                    return json(array('code' => 0, 'msg' => '内容为空'));
                }
                $data['time'] = time();

                if (session('userstatus') > 0) {
                    $data['open'] = $site_config['forum_sh'];
                } else {
                    $data['open'] = session('userstatus');
                }


                $data['view'] = 1;
                $data['uid'] = session('userid');


                $data['title'] = strip_tags($data['title']);

                $data['content'] = remove_xss($data['content']);


                if (model('shopitem')->allowField(true)->save($data)) {


                    return json(array('code' => 200, 'msg' => '添加成功'));
                } else {
                    return json(array('code' => 0, 'msg' => '添加失败'));
                }
            }


            return view();
        }
    }

    public function edit()
    {

        $map['name'] = 'attach';
        $map['status'] = 1;
        $config = Db::name('addons')->where($map)->value('config');
        $config = json_decode($config, true);
        $this->assign('configext', $config['configext']);


        $site_config = Cache::get('site_config');
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录', url('index/login'));
        } else {
            if (session('userid') != 2) {
                $this->error('非法操作');
            }
            $id = input('id');


            session('shopeditid', $id);


            $uid = session('userid');

            $a = model('shopitem')->find($id);

            if (empty($id) || $a == null) {
                $this->error('亲！您迷路了');
            } else {
                if (request()->isPost()) {
                    $data = input('post.');
                    $data['id'] = session('shopeditid');
                    session('shopeditid', null);
                    if ($data['content'] == '') {
                        return json(array('code' => 0, 'msg' => '内容为空'));
                    }
                    $data['time'] = time();
                    $data['title'] = strip_tags($data['title']);
                    $data['content'] = remove_xss($data['content']);

                    if (model('shopitem')->allowField(true)->save($data, $data['id'])) {

                        return json(array('code' => 200, 'msg' => '修改成功'));
                    } else {
                        return json(array('code' => 0, 'msg' => '修改失败'));
                    }
                }


                $tptc = model('shopitem')->find($id);

                $this->assign(array('tptc' => $tptc));

                $this->assign('title', '编辑帖子');
                return view();
            }
        }


        return view();
    }
}