<?php

namespace app\index\controller;

use think\Db;
use app\index\model\Comment as CommentModel;
use app\common\controller\HomeBase;
use think\Cache;
//use think\Exception;
use think\Validate;

class Comment extends HomeBase
{
    public function _initialize()
    {
        parent::_initialize();
        if (!session('userid') || !session('username')) {
            return json(['code'=>0,'msg'=>'亲！请登录']);
        }
    }

    public function add()
    {

        if (request()->isPost()) {

            $uid     = session('userid');
            $id      = $this->request->param('fid');
            $content = $this->request->param('content');
            $tid     = $this->request->param('tid');

            if(!$id){
                return json(['code'=>0,'msg'=>'缺少参数']);
            }

            if(!$content){
                return json(['code'=>'0','msg'=>'回复内容不得为空']);
            }


            if(strlen(strip_tags(html_entity_decode($content))) > 200){
                return json(['code'=>'0','msg'=>'回复内容不得超过200']);
            };

            $site_config = Cache::get('site_config');

            if (session('userstatus') != 2 && session('userstatus') != 5 && $site_config['email_sh'] == 0) {

                return json(array('code' => 0, 'msg' => '您的邮箱还未激活'));
            }


            $contentData = [
                'time'    => time(),
                'fid'     => $id,
                'uid'     => $uid,
                'content' => remove_xss($content),
                'tid'     => $tid ?:0
            ];

            Db::name('forum')->where('id', $id)->setInc('reply', 1);

            Db::name('forum')->where('id', $id)->update(['reply_time'=>time()]);

            $forumuser = Db::name('forum')->where('id', $id)->value('uid');

            $messdata['type']    = 2;
            $messdata['content'] = $contentData['content'];
            $messdata['status']  = 1;
            $messdata['uid']     = $uid;
            $messdata['fid']     = $id;
            $messdata['touid']   = $forumuser;
            $messdata['time']    = time();

            Db::name('message')->insert($messdata);

            if ($tid > 0) {

                Db::name('comment')->where('id', $tid)->setInc('reply');

                $messdata['type']     = 3;
                $messdata['content']  = $contentData['content'];
                $messdata['status']   = 1;
                $messdata['uid']      = $uid;
                $messdata['fid']      = $id;
                $messdata['touid']    = Db::name('comment')->where('id', $tid)->value('uid');
                $messdata['time']     = time();

                Db::name('message')->insert($messdata);

            }

            $comment = new CommentModel();

            if($comment->add($contentData) !== false){

//                point_note($site_config['jifen_comment'], $uid, 'commentadd', $comment->id);

                $taskStatus = runTask(6,$uid,session('usergrade'));

                return json(array('code' => 200, 'msg' => '回复成功','taskStatus' => $taskStatus));

            }else {

                return json(array('code' => 0, 'msg' => '回复失败'));
            }

        }

    }

    public function edit()
    {
        $id = input('id');
        if(!$id){
            return json(['code'=>0,'msg'=>'缺少参数']);
        }

        session('commenteditid', $id);

        $uid = session('userid');
        $comment = new CommentModel();
        $a = $comment->find($id);
        if (empty($id) || $a == null || $a['uid'] != $uid) {
            $this->error('亲！您迷路了');
        } else {
            if (request()->isPost()) {
                $data = input('post.');
                $data['id'] = session('commenteditid');
                session('commenteditid', null);
                $data['content'] = remove_xss($data['content']);
                if ($comment->edit($data)) {
                    return json(array('code' => 200, 'msg' => '修改成功'));
                } else {
                    return json(array('code' => 0, 'msg' => '修改失败'));
                }
            }
            $tptc = $comment->alias('c')->join('forum f', 'f.id=c.fid')->field('c.*,f.title')->find($id);
            $this->assign('tptc', $tptc);
            return view();
        }
    }

    public function dels()
    {
        if (session('userid') != 1) {//此处设置管理员可以删除评论
            $this->error('亲！你迷路了');
        } else {
            $id = input('id');
            if (db('comment')->delete(input('id'))) {
                return json(array('code' => 200, 'msg' => '删除成功'));
            } else {
                return json(array('code' => 0, 'msg' => '删除失败'));
            }
        }
    }
}