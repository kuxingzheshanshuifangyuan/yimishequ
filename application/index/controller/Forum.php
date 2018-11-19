<?php

namespace app\index\controller;

use app\common\controller\HomeBase;
use think\Db;
use think\Cache;
use think\Session;
use app\index\model\Forum as ForumModel;
use app\common\model\Upload as UploadModel;
use app\common\enum\SupermarketEnum;
use app\common\enum\ForumTypeEnum;
use app\common\tool\PHPTree;
use think\image;


class Forum extends HomeBase
{

    protected $userId;
    protected $userGrade;

    public function _initialize()
    {
        parent::_initialize();
        $this->userId = session('userid');
        $this->userGrade = session('usergrade');
        mb_internal_encoding('UTF-8');
    }

    /**
     * 帖子详情
     * @author GuoLin
     * @createdate 2018-07-20
     *
     */
    public function detail(){

        if (!$this->request->param('id')) {
            return $this->error('亲！你迷路了');
        }

        $id = $this->request->param('id');

        if(!Db::name('forum')->where(['id'=>$id])->find()){
            $this->error('帖子未找到',url('index/index'));
        }

        $forum_data = Db::name('forum')->where("id = {$id}")->find();;
        $pageNum = $this->request->param('page');
        //浏览量
        $site_config = Db::name('system')->field('value')->where('name', 'operate')->find();
        $site_config = unserialize($site_config['value']);
        $view=$site_config['forum_view'];
        $praise=$site_config['forum_praise'];

        if(!$pageNum || $pageNum  == 1){
            //新增访问量
            if(!$pageNum){

                Db::name('forum')->where("id = {$id}")->setInc('view', $view);

            }

            $forum_data = Db::name('forum')
                ->alias('f')
                ->join('forumcate c','c.id = f.tid','left')
                ->join('user u','u.id = f.uid')
                ->field('f.id,f.tid,f.title,f.content,f.praise,f.view,f.reply,f.pic,f.choice,f.settop,f.create_time,f.uid,u.username,u.userhead,c.name,f.trample,f.reply_time,f.collect,f.reward_sum,f.uid,f.description,f.keywords')
                ->where(['f.id'=>$id,'f.open'=>1])
                ->find();

            if(!$forum_data){
                $this->error('非法请求',url('index/index'));
            }

            $forum_data['create_time_text'] = friendlyDate($forum_data['create_time'],'mohu');
            $forum_data['reply_time']  = friendlyDate($forum_data['reply_time'],'mohu');

            if(!$forum_data){
                return $this->error('未找到相关帖子');
            }

            $forum_data['new'] = '';
            $forum_data['hot'] = '';
            if($forum_data['create_time'] >= strtotime(date('Y/m/d'))){
                $forum_data['new'] = 1;
            }
            if($forum_data['view'] > 100){
                $forum_data['hot'] = 1;
            }
        }

        $commentWhere['fid'] = $id;
        $commentWhere['status'] = ['>=',1];
        if($this->request->param('cid')){
            $commentWhere['uid'] = $this->request->param('cid');
        }
        $comment_list = Db::name('comment')
            ->alias('com')
            ->join('user u','u.id = com.uid')
            ->field('com.id,com.uid,u.userhead,u.username,com.create_time,com.praise,com.trample,com.content,com.tid')
            ->where($commentWhere)
            ->order('com.create_time asc')
            ->paginate(10,false,['page'=>$this->request->get('page')]);

        $comment = $comment_list->toArray()['data'];
        foreach ($comment as $key => &$value){

            $comment[$key]['parent'] = [];

            if($value['tid']){

                $comment[$key]['parent'] = Db::name('comment c')
                    ->join('user u','c.uid = u.id','left')
                    ->field('u.username,c.content,u.id,c.trample,c.praise')
                    ->where(['c.id'=>$value['tid'],'u.status'=>['>=',1]])
                    ->find();

                $comment[$key]['parent']['content'] = strip_tags(html_entity_decode($comment[$key]['parent']['content']));
            }

            $value['create_time'] = friendlyDate($value['create_time'],'mohu');

            $value['content'] = html_entity_decode($value['content']);

            $value['level'] = $comment_list->currentPage() * $comment_list->listRows() - $comment_list->listRows() + $key + 1;

        }

        $forum_data['reply'] = $comment_list->total();

        //小编推荐
        $recommend = Db::name('forum')->where('tid',$id)->where('recommend',1)->limit(5)->select();

        //广告
        $ad = Db::name('advertising')->where('id',6)->find();
        $existDs = '';
        if(session('userid')){
            $existDs = Db::name('give_reward_record')->where(['uid'=>session('userid'),'tid'=>$id])->find();
        }

        $giveRrewardList = Db::name('give_reward_record')
            ->alias('r')
            ->join('user u','r.uid = u.id','left')
            ->field('r.id,u.userhead,r.uid')
            ->where(['r.tid'=>$id,'u.status'=>1])
//            ->group('r.uid')
            ->limit(8)
            ->select();

        $this->assign([
            'forum_data'    => $forum_data,
            'comment'       => $comment,
            'total'         => $comment_list->total(),
            'id'            => $id,
            'page'          => $comment_list->render(),
            'recommend'     => $recommend,
            'ad'            => $ad,
            'existDs'       => $existDs,
            'giveRrewardList'  =>$giveRrewardList,
            'praise'        => $praise
        ]);

        //打赏金币
        $give_reward=Db::name('give_reward')->order(['money'=>'asc','order'=>'asc','id'=>'desc'])->where(['status'=>1])->field('money')->limit(8)->select();
        $this->assign('give_reward',$give_reward);

        return view();

    }

    /**
     * Ajax帖子评论
     * @author GuoLin
     * @createdate 2018-07-25
     *
     */
    public function ajax_comment(){
        if(!$this->request->isAjax() || !$this->request->param('id')){
            return json(['code'=>0,'msg'=>'illegal request']);
        }

        $id = $this->request->param('id');

        $comment_list = Db::name('comment')
            ->alias('com')
            ->join('user u','u.id = com.uid')
            ->field('com.id,com.uid,u.userhead,u.username,com.create_time,com.praise,com.content,com.tid')
            ->where(['fid'=>$id,'status'=>['>=',1]])
            ->order('com.id asc')
            ->paginate(2,false,['page'=>$this->request->get('p')]);

        $comment = $comment_list->toArray();

        foreach ($comment['data'] as $key => &$value){

            $comment['data'][$key]['parent'] = [];

            if($value['tid']){

                $comment['data'][$key]['parent'] = Db::name('comment c')
                    ->join('user u','c.uid = u.id','left')
                    ->field('u.username,c.content,u.id')
                    ->where(['c.id'=>$value['tid'],'u.status'=>['>=',1]])
                    ->find();
                $comment[$key]['parent']['content'] = strip_tags(html_entity_decode($comment[$key]['parent']['content']));
            }

            $value['create_time'] = friendlyDate($value['create_time'],'mohu');

            $value['content'] = html_entity_decode($value['content']);
        }

        return json(['code'=>1,'msg'=>'success','data'=>$comment]);
    }


    /**
     * 帖子收藏
     * @author GuoLin
     * @createdate 2018-07-25
     *
     */
    public function collect(){
        if (!session('userid') || !session('username')) {
            return json(['code' => 0, 'msg' => '请先登录']);
        }

        if(!$this->request->isAjax()){
            return json(['code'=>0,'msg'=>'illegal request']);
        }

        $id = $this->request->param('id');

        if(!$id){
            return json(['code'=>0,'msg'=>'missing parameter']);
        }

        $data = Db::name('collect')->where(['post_id'=>$id,'uid'=>session('userid')])->find();

        if($data){
            Db::name('forum')->where('id',$id)->setDec('collect');
            $result = Db::name('collect')->where(['forum_id'=>$id,'uid'=>session('userid')])->delete();
            if($result){
                return json(['code'=>1,'msg'=>'已取消收藏','type'=>1]);
            }
        }else{
            Db::name('forum')->where('id',$id)->setInc('collect');
            $result = Db::name('collect')->insert(['uid'=>session('userid'),'forum_id'=>$id,'create_time'=>time()]);
            if($result){
                return json(['code'=>1,'msg'=>'已收藏','type'=>2]);
            }
        }

    }


    /**
     * 投票
     * @author GuoLin
     * @createdate 2018-07-25
     *
     */
    public function vote(){
        $site_config = Db::name('system')->field('value')->where('name', 'operate')->find();
        $site_config = unserialize($site_config['value']);
        $praise=$site_config['forum_praise'];

        if(!$this->request->isAjax()){
            return json(['code'=>0,'msg'=>'illegal request']);
        }

        if (!session('userid') || !session('username')) {
            return json(['code' => 0, 'msg' => '请先登录']);
        }

        if(!$this->request->param('type') || !$this->request->param('id') || !$this->request->param('vote_type')){
            return json(['code'=>0,'msg'=>'missing parameter']);
        }

        $insertdata['type']  = $this->request->param('type');
        $insertdata['uid']   = $this->userId;
        $insertdata['sid']   = $this->request->param('id');

        $exist = Db::name('vote')->where($insertdata)->find();

        if(!$exist){
            $insertdata['vote_type'] = $this->request->param('vote_type');
            $field = $insertdata['vote_type'] == 1 ? 'praise': 'trample';
            $insertdata['time'] = time();
            Db::name('vote')->insert($insertdata);
            switch ($insertdata['type']){
                case 1:
                    Db::name('forum')->where('id',  $insertdata['sid'])->setInc($field,$praise);
                    break;
                case 2:
                    Db::name('comment')->where('id',  $insertdata['sid'])->setInc($field);
                    break;
                default:
                    return json(['code'=>0,'msg'=>'illegal request']);
            }
            runTask(8,$this->userId,$this->userGrade);
            return json(['code' => 1, 'msg' => '投票成功']);
        }else{
            return json(['code' => 0, 'msg' => '您已经投过票了']);
        }

    }


    /**
     * 论坛列表
     * @author GuoLin
     * @createdate 2018-07-18
     *
     */
    public function forum_list(){

        $where['f.open'] = 1;
        $order['f.settop'] = 'desc';
        $order['f.create_time'] = 'desc';

        //获取板块类型
        if($this->request->param('forumcate')){
            $where['f.tid'] = $this->request->param('forumcate');
        }else{
            $where['f.tid'] = 2;
        }

        if($this->request->param('topic')){
            $where['topic'] = $this->request->param('topic');
        }

        //获取指定时间范围内的帖子
        if($this->request->param('time_range')){
            $where['f.create_time'] = ForumTypeEnum::getTimeHorizonCondition($this->request->param('time_range'));
        }

        if($this->request->param('type')){
            $type = $this->request->param('type');
            if($type == 'choice'){
                $where['f.choice'] = 1;
            }
            if($type == 'hot'){
                $order['f.praise']  = 'desc';
                $order['f.view']    = 'desc';
            }
        }

        if($this->request->param('order_time')){
            $order_time = $this->request->param('order_time');
            if($order_time == 'reply'){
                $order['reply_time'] = 'desc';
            }
            if($order_time == 'visit'){
                $order['visit_time'] = 'desc';
            }
        }

        $data_list = Db::name('forum')
            ->alias('f')
            ->join('user u','u.id = f.uid','left')
            ->field('f.id,f.title,f.content,f.choice,f.settop,f.praise,f.view,f.create_time,f.reply,f.pic,u.username,u.userhead')
            ->where($where)
            ->order($order)
            ->paginate(12, false, ['query' => $this->request->get()]);

        $notice = Db::name('offer')->where('status',1)->order('id desc')->limit(1)->find();

        $list = $data_list->toArray()['data'];

        foreach($list as $key => &$value){
            $list[$key]['new'] = '';
            $list[$key]['hot'] = '';
            if($value['create_time'] >= strtotime(date('Y/m/d'))){
                $list[$key]['new'] = 1;
            }
            if($value['view'] > 100){
                $list[$key]['hot'] = 1;
            }
            $value['content'] = mb_substr(strip_tags(html_entity_decode($value['content'])),0,50);
        }

        $order['f.create_time'] = 'desc';
        $forumcate      = $this->request->param('forumcate');  //当前版块id

        $forumcate_data = Db::name('forumcate')->field('name,category')->where(['id'=>$forumcate])->find();

        //获取当前板块名称
        $sort_title     = $forumcate_data['name'];

        //获取当前板块所有主题   交流区推荐
        $topic          = Db::name('cate')->field('id,title_ch')->where(['id'=>['in',$forumcate_data['category']]])->select();

        //获取当前板块今日发帖数量
        $today_count    = Db::name('forum')->where(['tid'=>$forumcate,'create_time'=>['>=',strtotime(date('Y-m-d'))]])->count();

        //获取当前板块所有主题数量
        if($forumcate_data['category']){
            $cate_count = count(explode(',',$forumcate_data['category']));
        }else{
            $cate_count = 0;
        }

        $this->view->assign([
            'topic'         => $topic,
            'sort_title'    => $sort_title,
            'today_count'   => $today_count,
            'cate_count'    => $cate_count,
            'data_list'     => $list,
            'notice'        => $notice
        ]);

        $this->view->assign("page", $data_list->render());
        $this->view->assign("count", $data_list->total());
        $this->view->assign('numPerPage', $data_list->listRows());

        //交流区推荐
        $checkforumcate = Db::name('forumcate')->field('recommend')->where('id',$forumcate)->find();

        $new_forumcate = implode(',',$checkforumcate);

        $recommend_forumcate = Db::name('forumcate')->where('id','in',$new_forumcate)->select();


        $forum = [];
        foreach ($recommend_forumcate as $key=>$val){
            $forum[$key]['id'] = $val['id'];
            $forum[$key]['name'] = $val['name'];
            $forum[$key]['url'] = $val['url'];
            $forum[$key]['forum_num'] = $val['id'] ? \model('Forum')->where(['tid'=>$val['id']])->count() : 0;
        }

        $this->assign('forum',$forum);

        //小编推荐
        $recommend=Db::name('forum')->where('tid',$forumcate)->where('recommend',1)->limit(5)->select();
        $this->assign('recommend',$recommend);

        return view();
    }

    /**
     * 贷款超市列表
     * @author GuoLin
     * @createdate 2018-07-18
     *
     */
    public function supermarket()
    {
        if ($this->request->isAjax()) {
            return json([]);
        }
        $data = getHttpResponsePOST(SupermarketEnum::ForumProductListFileUrl,'',[]);
        $data = json_decode($data, true);

        $this->view->assign('data', $data['data']);
        return view();
    }

    /**
     * 贷款超市详情
     * @author GuoLin
     * @createdate 2018-07-18
     *
     */
    public function supermarket_detail()
    {
        if (!$this->request->param('id')) {
            $this->error('非法请求');
        }
        $id = $this->request->param('id');
        $data = getHttpResponsePOST(SupermarketEnum::ForumProductDetailsUrl,'', ['productid' => $id]);
        if(!$data) $this->error('服务器繁忙请稍后重试');


        $data = json_decode($data, true);

        if($data['msg'] != '0') $this->error('服务器繁忙请稍后重试');

        //数据过滤
        foreach ($data['data'] as &$value) {
            $value['apply']         = explode('。', $value['apply']);
            $value['auditor_type']  = SupermarketEnum::getAuditorAttr($value['auditor_type']);
            $value['account_type']  = SupermarketEnum::getAccountAttr($value['account_type']);
            $value['repayment_way'] = SupermarketEnum::getRepaymentAttr($value['repayment_way']);
            $value['ahead_type']    = SupermarketEnum::getAheadAttr($value['ahead_type']);
            $value['repayment_type']= SupermarketEnum::getRepaymentTpyeAttr($value['repayment_type']);
        }

        $this->view->assign('data', $data['data'][0]);
        return view();
    }


    public function add()
    {

        $userGradeId = $this->userGrade;
        $site_config = Cache::get('site_config');

        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录', url('index/index'));
        } else {

            if (request()->isPost()) {

                if (session('userstatus') != 2 && session('userstatus') != 5 && $site_config['email_sh'] == 0) {
                    return json(array('code' => 0, 'msg' => '您的邮箱还未激活'));
                }


                $data = input('post.');

                if ($data['tid'] == 0) {
                    return json(array('code' => 0, 'msg' => '版块为空'));
                }
                if ($data['content'] == '') {
                    return json(array('code' => 0, 'msg' => '内容为空'));
                }


                if (session('userstatus') > 0) {
                    $data['open'] = $site_config['forum_sh'];
                } else {
                    $data['open'] = session('userstatus');
                }


                $data['view'] = 1;
                $data['uid'] = session('userid');
                $data['description'] = mb_substr(strip_tags(remove_xss($data['content'])), 0, 200, 'utf-8');

                $data['title'] = strip_tags($data['title']);

                $data['content'] = remove_xss($data['content']);

                $data['create_time'] = time();



                //保存缩略图
                $imgInfo = getPic(html_entity_decode($data['content']));//使用函数 返回匹配地址 如果不为空则声称缩略图

                if(file_exists(ROOT_PATH.$imgInfo) && $imgInfo){
                    $imgResult = false;
                    $image  = \think\Image::open(ROOT_PATH.$imgInfo);
                    $width  = $image->width(); // 返回图片的宽度
                    $height = $image->height();
                    $new_file = './uploads/'.date('Ymd').'/';
                    if(!file_exists($new_file))
                    {
                        //检查是否有该文件夹，如果没有就创建，并给予最高权限
                        mkdir($new_file, 0700);
                    }
                    $imgThumbName = md5(uniqid( microtime() . mt_rand())).'.jpg';

                    if( $height > $width && $width >= 179){
                        $width  = $width/179; //取得图片的长宽比  179是要输出的图片的宽度
                        $height = ceil($height/$width);
                        $imgResult = $image->thumb(179,$height)->crop(179, 127)->save($new_file.$imgThumbName);
                    } elseif ($width > $height && $width >= 179 && $height >= 127){
                        $width  = $width/179; //取得图片的长宽比  179是要输出的图片的宽度
                        $height = ceil($height/$width);
                        if($height >= 127){
                            $imgResult = $image->thumb(179,$height)->crop(179, 127)->save($new_file.$imgThumbName);
                        }else{
                            $imgResult = $image->thumb($image->width(),127)->crop(179, 127)->save($new_file.$imgThumbName);
                        }
                    } elseif($width >= 179 && $height >= 127){
                        $imgResult = $image->thumb(179,$height)->crop(179, 127)->save($new_file.$imgThumbName);
                    }

                    if($imgResult){
                        $data['pic'] = ltrim($new_file.$imgThumbName,'.');
                    }

                }
                $dataResult = Db::name('Forum')->insertGetId($data);

                if ($dataResult) {

//                    point_note($site_config['jifen_add'], session('userid'), 'forumadd', $dataResult);
                    if (!empty($data['fee'])) {
                        $res = hook('threadfee', array('score' => $data['fee'], 'id' => $dataResult, 'edit' => 0));
                    }

                    return json(array('code' => 200, 'msg' => '添加成功','location'=> url('detail',['id'=>$dataResult])));
                } else {
                    return json(array('code' => 0, 'msg' => '添加失败'));
                }
            }


            $forumCate = Db::name('forumcate')->field('id,name,usergrade')->where(['show'=>1])->order(['sort'=>'asc','id'=>'desc'])->select();

            foreach ($forumCate as $key => $value ){
                $usergradeArr = explode(',',$value['usergrade']);
                if(!in_array($userGradeId,$usergradeArr)){
                    unset($forumCate[$key]);
                }
                unset($forumCate[$key]['usergrade']);
            }

            $topic = Db::name('cate')->where(['status'=>1, 'p_id'=>['in',array_column($forumCate,'id')]])->select();


//            $category = Db::name('forumcate');
//            $tptc = $category->field('id,category,name,tid,usergrade')->where(array('show' => 1))->order(['sort'=>'asc','id'=>'desc'])->select();
//
//            $tptc = PHPTree::generate($tptc);
//
//            $topic = [];
//
//            if($this->request->param('tid')){
//                $forumcate_data = $category->where(['id'=>$this->request->param('tid')])->value('category');
//                $topic = Db::name('cate')->field('id,title_ch')->where(['id'=>['in',$forumcate_data],'status'=>1])->select();
//            }
//
//            foreach($tptc as &$value){
//                $value['category'] = explode(',',$value['category']);
//                $value['usergrade'] = explode(',',$value['usergrade']);
//            }
//
//            $topicAll = Db::name('cate')->field('id,title_ch')->where(['status'=>1])->select();

            $this->assign('forumCate', $forumCate);
            $this->assign('topic',$topic);
            $this->assign('topicAll',json_encode($topic));
            $this->assign('forumCateAll',json_encode($forumCate));
            $tags = $site_config['site_keyword'];
            $tagss = explode(',', $tags);
            $this->assign('tagss', $tagss);

            $this->assign('userGradeId',$userGradeId);
            $this->assign('title', '发布帖子');


            return view();
        }
    }



    public function edit()
    {
        $site_config = Cache::get('site_config');
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录', url('index/index'));
        } else {
            $id = input('id');
            session('editid', $id);
            $uid = session('userid');
            $forum = new ForumModel();
            $forum_data = $forum->find($id);


            if (empty($id) || $forum_data == null || $forum_data['uid'] != $uid) {
                $this->error('亲！您迷路了');
            } else {
                if (request()->isPost()) {
                    $data = input('post.');
                    $data['id'] = session('editid');
                    session('editid', null);
                    if ($data['content'] == '') {
                        return json(array('code' => 0, 'msg' => '内容为空'));
                    }
                    $data['description'] = mb_substr(strip_tags(remove_xss($data['content'])), 0, 200, 'utf-8');
                    $data['title'] = strip_tags($data['title']);
                    $data['title'] = hook('trigtitle', array('title' => $data['title'], 'id' => $data['id']), true, 'title');

                    $data['content'] = remove_xss($data['content']);

                    //保存缩略图
                    $imgInfo = getPic(html_entity_decode($data['content']));//使用函数 返回匹配地址 如果不为空则声称缩略图

                    if(file_exists(ROOT_PATH.$imgInfo) && $imgInfo){
                        $imgResult = false;
                        $image  = \think\Image::open(ROOT_PATH.$imgInfo);
                        $image->water('public/forum/img/index/logo.png',Image::WATER_SOUTHEAST,70)->save(ROOT_PATH.$imgInfo);
                        $width  = $image->width(); // 返回图片的宽度
                        $height = $image->height();
                        $new_file = './uploads/'.date('Ymd').'/';
                        if(!file_exists($new_file))
                        {
                            //检查是否有该文件夹，如果没有就创建，并给予最高权限
                            mkdir($new_file, 0700);
                        }
                        $imgThumbName = md5(uniqid( microtime() . mt_rand())).'.jpg';
                        if($height > $width && $width >= 179){
                            $width  = $width/179; //取得图片的长宽比  179是要输出的图片的宽度
                            $height = ceil($height/$width);
                            $imgResult = $image->thumb(179,$height)->crop(179, 127)->save($new_file.$imgThumbName);
                        } elseif ($width > $height && $height >= 127){
                            $imgResult = $image->thumb($width,127)->crop(179, 127)->save($new_file.$imgThumbName);
                        } elseif($width >= 179 && $height >= 127){
                            $imgResult = $image->thumb(179,$height)->crop(179, 127)->save($new_file.$imgThumbName);
                        }
                        if($imgResult){
                            $data['pic'] = ltrim($new_file.$imgThumbName,'.');
                        }
                    }

                    $editData = [
                        'id'    => $data['id'],
                        'topic' => $data['topic'],
                        'pic'   => $data['pic'],
                        'title' => $data['title'],
                        'content' => $data['content'],
                        'update_time' => time(),
                    ];

                    if ($forum->edit($editData) !== false) {
                        if (!empty($data['fee'])) {
                            $res = hook('threadfee', array('score' => $data['fee'], 'id' => $data['id'], 'edit' => 1));
                        }
                        //$res=hook('forumattach',array('content'=>htmlspecialchars_decode($data['content']),'id'=>$data['id']));

                        return json(array('code' => 200, 'msg' => '修改成功'));
                    } else {
                        return json(array('code' => 0, 'msg' => '修改失败'));
                    }
                }

                //主题
                $topic = [];
                $topic = Db::name('cate')->field('id,title_ch')->where(['p_id'=>$forum_data['tid'],'status'=>1])->select();
                $forum_data['title'] = strip_tags($forum_data['title']);
                //版块
                $forumcate_name = Db::name('forumcate')->where(['id'=>$forum_data['tid']])->value('name');

                $this->assign(array('forumcate_name' => $forumcate_name, 'tptc' => $forum_data));
                $tags = $site_config['site_keyword'];
                $tagss = explode(',', $tags);
                $this->assign('tagss', $tagss);


                $this->assign('topic',$topic);
                $this->assign('title', '修改帖子');

                return view();
            }
        }
    }

    public function doUploadPic()
    {
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录', url('index/index'));
        } else {

            $uploadmodel = new UploadModel();

            $info = $uploadmodel->upfile('images', 'FileName');

            echo $info['headpath'];

        }
    }

    public function zone($id)
    {
        $todayStr = date('Y-m-d');
        $zoneMeta = model('Forumcate')->where(array(
            'id' => $id
        ))->find();

        $todayPost = model('Forumcate')->where(array(
            'id'   => $id,
            'time' => array('BETWEEN', array(
                strtotime($todayStr . ' 00:00:00'),
                strtotime($todayStr . ' 24:00:00')
            ))
        ))->count();

        $totalCount = model('Forumcate')
            ->where(array(
                'id' => $id
            ))->count();

        $this->assign('zoneMeta', $zoneMeta);
        $this->assign('todayPost', $todayPost);
        $this->assign('totalCount', $totalCount);
        return view();
    }

    public function demo(){
        dump($this->request->param('qwe'));
    }


    public function search(){


        return view();
    }



    Public function search_list(){

        $content = $this->request->param('content');
        $page    = '';
        $total   = 0;
        $list    = array();

        if($content){
            $dataList = Db::name('forum')
                ->alias('f')
                ->field('f.id,f.title,f.content,f.choice,f.settop,f.praise,f.view,f.create_time,f.reply,f.pic,u.username,u.userhead')
                ->join('user u','u.id = f.uid','left')
                ->where(['title'=>['like',"%{$content}%"],'open'=>1])
                ->order(['create_time'=>'desc'])
                ->paginate(15,false,['query'=>['page'=>$this->request->param('page'),'content'=>$content]]);

            $list = $dataList->toArray()['data'];

            foreach($list as $key => &$value){
                $list[$key]['new'] = '';
                $list[$key]['hot'] = '';
                if($value['create_time'] >= strtotime(date('Y/m/d'))){
                    $list[$key]['new'] = 1;
                }
                if($value['view'] >= 1000){
                    $list[$key]['hot'] = 1;
                }
                $list[$key]['description'] = mb_substr(strip_tags(html_entity_decode($value['content'])),0,50);
            }

            $page  = $dataList->render();
            $total = $dataList->total();
        }

        $this->assign([
            'page'    => $page,
            'content' => $content,
            'dataList'=> $list,
            'total'   => $total
        ]);

        return view();
    }


    /**
     * 发送私信
     * @author GuoLin
     * @createdate 2018-10-27
     *
     */
    public function  private_letter(){
        if(!$this->request->isPost()){
            return json(['error_code'=>1,'msg'=>'非法请求']);
        }
        $userId= session('uid');
        if(!$userId){
            return json(['error_code'=>7,'msg'=>'非法请求']);
        }
        $uid      = $this->request->param('uid');
        $content  = remove_xss(strip_tags(html_entity_decode($this->request->param('content'))));
        $user     = Db::name('user')->where(['id'=>$uid])->count();

        if(!$content){
            return json(['error_code'=>2,'msg'=>'非法请求']);
        }
        if(!$user){
            return json(['error_code'=>3,'msg'=>'非法请求']);
        }
        if($uid == $userId){
            return json(['error_code'=>5,'msg'=>'不可以私信自己']);
        }

        $result = Db::name('message')->insert([
            'uid'    => $userId,
            'touid'  => $uid,
            'type'   => 5,      //私信消息
            'content'=> $content,
            'time'   => time(),
        ]);

        if($result){
            return json(['error_code'=>0,'msg'=>'success']);
        }else{
            return json(['error_code'=>6,'msg'=>'私信失败']);
        }

    }

    /**
     * 关注
     * @author GuoLin
     * @createdate 2018-10-27
     *
     */
    public function attention(){
        if(!$this->request->isPost()){
            return json(['error_code'=>1,'msg'=>'非法请求']);
        }
        $userId= session('uid');
        if(!$userId){
            return json(['error_code'=>7,'msg'=>'非法请求']);
        }

        $uid  = $this->request->param('uid');

        $user = Db::name('user')->where(['id'=>$uid])->count();

        if(!$user){
            return json(['error_code'=>3,'msg'=>'非法请求']);
        }
        if($uid == $userId){
            return json(['error_code'=>5,'msg'=>'不可以关注自己']);
        }

        $result = Db::name('attention')->insert([
            'uid'          => $userId,
            'gid'          => $uid,
            'create_time'  => time()
        ]);

        if($result){
            return json(['error_code'=>0,'msg'=>'success']);
        }else{
            return json(['error_code'=>6,'msg'=>'关注失败']);
        }

    }

    /**
     * 取消关注
     * @author GuoLin
     * @createdate 2018-10-27
     *
     */
    public function cancel_attention(){
        if(!$this->request->isPost()){
            return json(['error_code'=>1,'msg'=>'非法请求']);
        }
        $userId= session('uid');
        if(!$userId){
            return json(['error_code'=>7,'msg'=>'非法请求']);
        }

        $uid  = $this->request->param('uid');

        $user = Db::name('user')->where(['id'=>$uid])->count();

        if(!$user){
            return json(['error_code'=>3,'msg'=>'非法请求']);
        }
        if($uid == $userId){
            return json(['error_code'=>5,'msg'=>'不可以关注自己']);
        }

    }


}