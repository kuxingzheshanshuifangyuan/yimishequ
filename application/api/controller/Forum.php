<?php

namespace app\api\controller;

use app\common\model\Cate;
use think\Controller;
use think\Db;
use \app\api\exception\BaseException as Exception;
use app\common\enum\SupermarketEnum;
use \app\common\service\Vercode;
use think\cache;

class Forum extends Base
{

    // 校验参数规则数组
    protected $validate_param_rules = [
        'forumCate'    => ['板块ID','require|integer'],
        'id'           => ['ID','require|integer'],
        'page'         => ['页码','integer'],
        'uid'          => ['参数','integer'],
        'token'        => ['令牌','require|length:32'],
        'cate_id'      => ['板块ID','require|integer'],
        'topic_id'     => ['主题ID','integer'],
        'title'        => ['标题','require|length:5,30'],
        'content'      => ['内容','require'],
        'keyword'      => ['关键词','require|length:1,50'],
        'topic'        => ['主题ID','require|integer']
    ];

    /**
     * 获取所有板块
     * @author GuoLin
     * @createdate 2018-08-18
     *
     */
    public function forumCateList(){
        $forumCateList = Db::name('forumcate')
            ->field('id,name')
            ->where(['show'=>1,'type'=>1])
            ->whereNotIn('id',[30,31,32,33,34])
            ->order(['sort'=>'asc','id'=>'desc'])
            ->select();
        return ToApiFormat('success',$forumCateList);
    }


    /**
     * 获取当前板块所有主题
     * @author GuoLin
     * @createdate 2018-08-18
     *
     */
    public function forumTopic(){
        $params = $this->validateRequestParams(['id']);
        $forumTopicList = Db::name('cate')
            ->field('id,title_ch')
            ->where(['p_id'=>$params['id'],'status'=>1])
            ->order(['sort'=>'asc','id'=>'desc'])
            ->select();

        return ToApiFormat('success',$forumTopicList);

    }


    /**
     * 获取当前板块列表数据
     * @author GuoLin
     * @createdate 2018-08-18
     *
     */
    public function forumList(){
        mb_internal_encoding('UTF-8');
        $params = $this->validateRequestParams(['forumCate','page']);
        $where['f.tid'] = $params['forumCate'];
        $where['f.open'] = 1;
        $order['f.settop'] = 'desc';
        $order['f.create_time'] = 'desc';

        $data_list = Db::name('forum')
            ->alias('f')
            ->join('user u','u.id = f.uid','left')
            ->field('f.id,f.title,f.content,f.choice,f.settop,f.praise,f.view,f.create_time,f.reply,f.pic,u.username,u.userhead')
            ->where($where)
            ->order($order)
            ->paginate(10, false, ['query' => ['page'=>$params['page']]]);

        $list = $data_list->toArray()['data'];

        foreach($list as $key => &$value){
            $list[$key]['new'] = 0;
            $list[$key]['hot'] = 0;
            if($value['create_time'] >= strtotime(date('Y/m/d'))){
                $list[$key]['new'] = 1;
            }
            if($value['view'] > 1000){
                $list[$key]['hot'] = 1;
            }
            $value['create_time_text'] = date('Y/m/d',$value['create_time']);
            $value['content'] = mb_substr(strip_tags(html_entity_decode($value['content'])),0,50);
        }

        $page = [
            'currentPage' => intval(($data_list->currentPage())),
            'lastPage'    => $data_list->lastPage(),
            'total'       => $data_list->total(),
            'listRows'    => $data_list->listRows()
        ];

        return ToApiFormat('success',compact('list', 'page'));

    }


    /**
     * 帖子详情
     * @author GuoLin
     * @createdate 2018-08-18
     *
     */
    public function detail()
    {

        $params = $this->validateRequestParams(['id']);

        $forum_data = Db::name('forum')
            ->alias('f')
            ->join('forumcate c', 'c.id = f.tid', 'left')
            ->join('user u', 'u.id = f.uid', 'left')
            ->join('usergrade grade','u.usergrade = grade.id','left')
            ->field('f.id,c.name as cate_name,f.tid,f.title,f.description,f.content,f.praise,f.view,f.reply,f.pic,f.choice,f.settop,f.create_time,f.uid,u.username,u.userhead,c.name,f.trample,f.reply_time,f.collect,f.reward_sum,f.uid,grade.name as grade_name')
            ->where(['f.id' => $params['id'], 'f.open' => 1])
            ->find();

        if (!$forum_data) {
            throw new Exception('未找到相关帖子', 1);
        }

        $forum_data['content'] = html_entity_decode($forum_data['content']);

        $site_config = Db::name('system')->field('value')->where('name', 'operate')->find();
        $site_config = unserialize($site_config['value']);
        Db::name('forum')->where(['id' => $params['id']])->setInc('view', $site_config['forum_view']);

        $forum_data['new'] = 0;
        $forum_data['hot'] = 0;

        if ($forum_data['create_time'] >= strtotime(date('Y/m/d'))) {
            $forum_data['new'] = 1;
        }
        
        if ($forum_data['view'] > 1000) {
            $forum_data['hot'] = 1;
        }

        $giveRrewardList = Db::name('give_reward_record')
            ->alias('r')
            ->join('user u','r.uid = u.id','left')
            ->field('u.userhead,r.uid')
            ->where(['r.tid'=>$params['id'],'u.status'=>1])
            ->group('r.uid')
            ->limit(8)
            ->select();

        $existDs = 0;

        $existCollect = 0;

        $myArticle = 0;

        $myVote = 0;

        $myMoney = 0;

        $token = $this->request->param('token',false);
        if($token){
            $userId = Db::name('user_api_token')->where(['token'=>$token])->value('user_id');
            if($userId){
                $existCollect = Db::name('collect')->where(['uid'=>$userId,'forum_id'=>$params['id']])->find()?1:0;
                $myArticle = Db::name('forum')->where(['id'=>$params['id'],'uid'=>$userId])->find()?1:0;
                $myVote = Db::name('vote')->where(['uid'=>$userId,'sid'=>$params['id'],'type'=>1,'vote_type'=>1])->find()?1:0;
                $myMoney = Db::name('user')->where(['id'=>$userId])->value('money');
            }
        }

        return ToApiFormat('success', compact('forum_data', 'giveRrewardList','existDs','existCollect','myArticle','myVote','myMoney'));

    }

    /**
     * 评论列表
     * @author GuoLin
     * @createdate 2018-08-18
     *
     */
    public function commentList(){

        $params = $this->validateRequestParams(['id','page','uid']);

        $commentWhere['com.fid'] = $params['id'];
        $commentWhere['u.status'] = ['>=',1];

        if($params['uid']){
            $commentWhere['uid'] = $params['uid'];
        }

        $comment_list = Db::name('comment')
            ->alias('com')
            ->join('user u','u.id = com.uid')
            ->join('usergrade grade','u.usergrade = grade.id','left')
            ->field('com.id,com.uid,u.userhead,u.username,com.create_time,com.praise,com.trample,com.content,com.tid,grade.name as grade_name')
            ->where($commentWhere)
            ->order('com.create_time asc')
            ->paginate(15, false, ['query' => ['page'=>$params['page']]]);

        $comment = $comment_list->toArray()['data'];

//        $token = $this->request->param('token',false);
//        if($token){
//            $userId = Db::name('user_api_token')->where(['token'=>$token])->value('user_id');
//            if($userId) {
//                $comment_ids = Db::name('comment')->field('id')->where(['fid'=>$params['id']])->select();
//                $vote_ids    = Db::name('vote')->where(['vote_type'=>1,'type'=>2,'uid'=>$userId,['sid',['in',$comment_ids]]])->select();
//            }
//        }

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

//            $value['create_time'] = friendlyDate($value['create_time'],'mohu');

            $value['content'] = html_entity_decode($value['content']);

            $value['level'] = $comment_list->currentPage() * $comment_list->listRows() - $comment_list->listRows() + $key + 1;
        }

        $page = [
            'currentPage' => intval(($comment_list->currentPage())),
            'lastPage'    => $comment_list->lastPage(),
            'total'       => $comment_list->total(),
            'listRows'    => $comment_list->listRows()
        ];

        return ToApiFormat('success',compact('comment', 'page'));

    }

    /**
     * 发布新帖
     * @author GuoLin
     * @createdate 2018-08-20
     *
     */
    public function addForum(){

        if(!$this->request->isPost()){
            throw new Exception('非法请求', 1);
        }

        $params = $this->validateRequestParams(['token','cate_id','topic_id','title','content','img/a']);

        if($params['cate_id'] == 16){
            throw new Exception('当前板块未开放发帖权限', 9);
        }

        $userId = $this->checkToken();

        $userGroupId = Db::name('user')->where(['id'=>$userId])->value('usergrade');

        $params['content'] = html_entity_decode(remove_xss($params['content']));

        $imgInfo = getPic($params['content']);//使用函数 返回匹配地址 如果不为空则声称缩略图
//        echo $params['content'].'******'.$imgInfo;exit;

        $pic = '';
        $strImgs = '';
        $imgResult = false;
        if($params['img']){
            foreach ($params['img'] as $val){

                if(file_exists(ROOT_PATH.$val)){
                    $strImgs .= '<img src="'. $val .'">';
                    if($imgResult){
                        continue;
                    }
                    $image  = \think\Image::open(ROOT_PATH.$val);
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
                        $pic = ltrim($new_file.$imgThumbName,'.');
                    }
                }
            }

            $params['content'] .= $strImgs;

            runTask(7,$userId,$userGroupId); //图文帖
        }

        if($imgInfo !== false){
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
                $pic = ltrim($new_file.$imgThumbName,'.');
                runTask(7,$userId,$userGroupId); //图文帖
            }

        }

        $data = [
            'open'          => 1,
            'uid'           => $userId,
            'tid'           => $params['cate_id'],
            'description'   => mb_substr(strip_tags($params['content']), 0, 200, 'utf-8'),
            'title'         => strip_tags($params['title']),
            'content'       => htmlentities($params['content']),
            'topic'         => $params['topic_id'],
            'pic'           => $pic,
            'create_time'   => time()
        ];

        $dataResult = Db::name('Forum')->insertGetId($data);

        if ($dataResult) {
            autoUpGrade($userId);

            return ToApiFormat('success');
        } else {
            throw new Exception('添加失败', 1);
        }
    }


    /**
     * 评论帖子
     * @author GuoLin
     * @createdate 2018-08-20
     *
     */
    public function addComment(){

        if(!$this->request->isPost()){
            throw new Exception('非法请求', 1);
        }

        $params = $this->validateRequestParams(['token','tid','content','fid']);

        $userId = $this->checkToken();

        $userGroupId = Db::name('user')->where(['id'=>$userId])->value('usergrade');

        if(strlen(strip_tags(html_entity_decode($params['content']))) > 200){

            throw new Exception('回复内容不得超过200', 1);

        };

        try{



        $contentData = [
            'create_time'  => time(),
            'fid'          => $params['fid'],
            'uid'          => $userId,
            'content'      => remove_xss($params['content']),
            'tid'          => $params['tid'] ?:0
        ];

        Db::name('forum')->where('id', $params['fid'])->setInc('reply', 1);

        Db::name('forum')->where('id', $params['fid'])->update(['reply_time'=>time()]);

        $forumuser = Db::name('forum')->where('id', $params['fid'])->value('uid');

        $messdata['type']    = 2;
        $messdata['content'] = $contentData['content'];
        $messdata['fid']     = $params['fid'];
        $messdata['status']  = 1;
        $messdata['uid']     = $userId;
        $messdata['touid']   = $forumuser;
        $messdata['time']    = time();

        Db::name('message')->insert($messdata);

        if ($params['tid']) {

            Db::name('comment')->where('id', $params['tid'])->setInc('reply');

            $messdata['type']     = 3;
            $messdata['status']   = 1;
            $messdata['fid']      = $params['fid'];
            $messdata['uid']      = $userId;
            $messdata['touid']    = Db::name('comment')->where('id', $params['tid'])->value('uid');
            $messdata['time']     = time();

            Db::name('message')->insert($messdata);
        }

        $insertId = Db::name('comment')->insertGetId($contentData);
        }catch (\Exception $e){
            throw new Exception($e->getMessage(), 9);
        }
        if($insertId !== false){

            runTask(6,$userId,$userGroupId); //评论任务
            autoUpGrade($userId);

            return ToApiFormat('success',['id'=>$insertId,'create_time'=>date('Y/m/d H:i:s')]);
        }else{
            throw new Exception('回复失败', 1);
        }

    }


    /**
     * 投票
     * @author GuoLin
     * @createdate 2018-08-20
     *
     */
    public function vote(){

        $params = $this->validateRequestParams(['token','vote_type','id','type']);

        $userId = $this->checkToken();

        $userGroupId = Db::name('user')->where(['id'=>$userId])->value('usergrade');

        if(!$this->request->isPost()){
            throw new Exception('非法请求', 2);
        }

        $insertData['type']  = $params['type'];
        $insertData['uid']   = $userId;
        $insertData['sid']   = $params['id'];

        $exist = Db::name('vote')->where($insertData)->find();

        if(!$exist){
            $insertData['vote_type'] = $this->request->param('vote_type');
            $field = $insertData['vote_type'] == 1 ? 'praise': 'trample';
            $insertdata['time'] = time();
            Db::name('vote')->insert($insertData);
            switch ($insertData['type']){
                case 1:
                    Db::name('forum')->where('id',  $insertData['sid'])->setInc($field);
                    break;
                case 2:
                    Db::name('comment')->where('id',  $insertData['sid'])->setInc($field);
                    break;
                default:
                    throw new Exception('非法请求', 2);
            }

            runTask(8,$userId,$userGroupId);
            return ToApiFormat('success');

        }else{

            throw new Exception('您已经投过票了', 2);
        }

    }


    /**
     * 帖子打赏
     * @author GuoLin
     * @createdate 2018-08-21
     *
     */
    public function giveReward(){

        mb_internal_encoding('UTF-8');

        if(!$this->request->isPost()){
            throw new Exception('非法请求', 2);
        }

        $params = $this->validateRequestParams(['id','money']);

        $userId = $this->checkToken();

        $forumData = Db::name('forum')->field('title,uid')->where(['id'=>$params['id']])->find();

        $sid = $forumData['uid'];

        if($sid == $userId){
            throw new Exception('不可以打赏自己的帖子', 3);
        }

        if(mb_strlen($forumData['title']) > 12){
            $forumData['title'] = mb_substr($forumData['title'],0,12) . '...';
        }

        $userData = Db::name('user')->field('id,usergrade,money,username')->where(['id'=>$userId])->find();

        Db::startTrans();
        try{
            if($userData['money'] < $params['money']){
                return ToApiFormat('账户余额不足',[],4);
            }
            Db::name('user')->where(['id' => $userId ])->setDec('money',$params['money']);
            Db::name('user')->where(['id' => $sid])->setInc('money',$params['money']);
            Db::name('give_reward_record')->insert(['tid'=>$params['id'],'uid'=>$userId,'money'=>$params['money'],'create_time'=>time()]);
            Db::name('forum')->where(['id'=>$params['id']])->setInc('reward',$params['money']);
            Db::name('forum')->where(['id'=>$params['id']])->setInc('reward_sum');
            addMoneyRecord($userId,'打赏帖子《'.$forumData['title'].'》',$params['money'],2,0,1);
            addMoneyRecord($sid,'帖子《'.$forumData['title'].'》被'. $userData['username'].'打赏',$params['money'],2,0,0);
            runTask(9,$userId,$userData['usergrade']);
//            autoUpGrade($userId);
            Db::commit();
            return ToApiFormat('success');
        }catch (\Exception $e){
            Db::rollback();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }



    /**
     * 贷款超市列表
     * @author GuoLin
     * @createdate 2018-08-21
     *
     */
    public function supermarketList()
    {
        $page = $this->request->param('page');
        $data = getHttpResponsePOST(SupermarketEnum::ForumProductListFileUrl,'',['page'=>$page]);
        $data = json_decode($data, true);
        return ToApiFormat('success',$data);
    }

    /**
     * 贷款超市详情
     * @author GuoLin
     * @createdate 2018-08-21
     *
     */
    public function supermarketDetail()
    {

        $params = $this->validateRequestParams(['id']);

        $data = getHttpResponsePOST(SupermarketEnum::ForumProductDetailsUrl,'', ['productid' => $params['id']]);

        if(!$data){
            throw new Exception('服务器繁忙请稍后重试', 2);
        }

        $data = json_decode($data, true);

        if($data['msg'] == 1){
            throw new Exception($data['data'], 2);
        }

        //数据过滤
        foreach ($data['data'] as &$value) {

            $value['apply']         = explode('。', $value['apply']);
            $value['auditor_type']  = SupermarketEnum::getAuditorAttr($value['auditor_type']);
            $value['account_type']  = SupermarketEnum::getAccountAttr($value['account_type']);
            $value['repayment_way'] = SupermarketEnum::getRepaymentAttr($value['repayment_way']);
            $value['ahead_type']    = SupermarketEnum::getAheadAttr($value['ahead_type']);
            $value['repayment_type']= SupermarketEnum::getRepaymentTpyeAttr($value['repayment_type']);

        }

        return ToApiFormat('success',$data['data'][0]);
    }

    /**
     * 打赏金额列表
     * @author GuoLin
     * @createdate 2018-08-21
     *
     */
    public function giveRewardList(){

        $data = Db::name('give_reward')->field('id,money')->where(['status'=>1])->order('money','asc')->select();

        return ToApiFormat('success',$data);
    }


    public function appUpdate(){
        $data = [
            'state' => 'yes',
            'mark'  => '1.1',
            'url'   => 'http://www.1miclub.com/H58F129D9_0821185643.apk'
        ];
        return json($data);
    }


    /**
     * 还款问题
     * @author GuoLin
     * @createdate 2018-08-22
     *
     */
    public function forum_repay_problems(){

        $data = getHttpResponsePOST('http://www.baijiaqianbao.com/index.php/Owner/forum_repay_problems','',[]);
        $data = json_decode($data, true);
        return ToApiFormat('success',$data);

    }


    /**
     * 常见问题
     * @author GuoLin
     * @createdate 2018-08-22
     *
     */
    public function forum_common_problems(){

        $data = getHttpResponsePOST('http://www.baijiaqianbao.com/index.php/Owner/forum_common_problems','',[]);
        $data = json_decode($data, true);
        return ToApiFormat('success',$data);
    }

    /**
     * 贷款问题
     * @author GuoLin
     * @createdate 2018-08-22
     *
     */
    public function forum_loans_problems(){

        $data = getHttpResponsePOST('http://www.baijiaqianbao.com/index.php/Owner/forum_loans_problems','',[]);
        $data = json_decode($data, true);
        return ToApiFormat('success',$data);
    }

    public function user(){
        $num = 3055;

        try{
            for($i=0;$i <= 500 ;$i++){
                $rand = mt_rand(6,13);
                $data = [
                    'id'        => $num,
                    'username'  => '米友'. $num,
                    'userhead'  => '/public/images/default.png',
                    'password'  => md5(md5('shanxishengdun')),
                    'sex'       => 3,
                    'status'    => 1,
                    'usergrade' => $rand,
                    'money'     => mt_rand(1,500),
                    'point'     => $this->getGrade($rand),
                    'is_robot'  => 1,
                ];

                Db::name('user')->insert($data);
                $num++;
            }
        }catch (\Exception $e){
            return json(['msg'=>$e->getMessage(),'code'=>$e->getCode()]);
        }

    }

    public function getGrade($rand){
        if($rand == 6){
            return mt_rand(1,100);
        }
        if($rand == 7){
            return mt_rand(100,140);
        }

        if($rand == 8){
            return mt_rand(150,299);
        }

        if($rand == 9){
            return mt_rand(300,430);
        }

        if($rand == 10){
            return mt_rand(450,888);
        }

        if($rand == 11){
            return mt_rand(3600,5000);
        }

        if($rand == 12){
            return mt_rand(900,1780);
        }

        if($rand == 13){
            return mt_rand(1800,3590);
        }

    }


    /**
     * 关键词搜索
     * @author GuoLin
     * @createdate 2018-09-27
     *
     */
    public function searchForum()
    {
        $params = $this->validateRequestParams(['keyword','page','packaging']);

        if($params['packaging']){
            $where['f.tid'] = ['in',[30,31,32,33,34]];
        }else{
            $where['f.tid'] = ['NOT IN',[30,31,32,33,34]];
        }

        $dataList = Db::name('forum')
            ->alias('f')
            ->join('user u','u.id = f.uid')
            ->field('f.id,f.title,f.create_time,f.view,f.pic,u.username,u.userhead')
            ->where(['u.status'=>1,'f.title'=>['like',"%{$params['keyword']}%"],'f.open'=>1])
            ->where($where)
            ->order(['f.create_time'=>'desc'])
            ->paginate(15, false, ['query' => ['page'=>$params['page']]]);

        $page = [
            'currentPage' => intval(($dataList->currentPage())),
            'lastPage'    => $dataList->lastPage(),
            'total'       => $dataList->total(),
            'listRows'    => $dataList->listRows()
        ];

        $dataList = $dataList->toArray()['data'];

        return ToApiFormat('success',compact('dataList', 'page'));
    }


    /**
     * 关键词搜索
     * @author GuoLin
     * @createdate 2018-09-27
     *
     */
    public function keywordList(){

        $params = $this->validateRequestParams(['packaging']);

        if($params['packaging']){
            $keywordList = ['猫','服装','狗','装修','美食'];
        }else{
            $keywordList = ['新口子','分享','芝麻分','高炮','信用卡'];
        }

        return ToApiFormat('success',$keywordList);

    }

    /**
     * 主题列表
     * @author GuoLin
     * @createdate 2018-09-28
     *
     */
    public function cateTopicList(){

        $params = $this->validateRequestParams(['packaging']);

        if($params['packaging']){
            $where['id'] = ['IN',[30,31,32,33,34]];
        }else{
            $where['id'] = ['NOT IN',[30,31,32,33,34]];
        }

        $forumCateList = Db::name('forumcate')
            ->field('id,name,pic,description,tid')
            ->where($where)
            ->where(['show'=>1])
            ->select();

        $topicList = Db::name('cate')->where(['status'=>1])->select();

        foreach ($forumCateList as $key => $value){
            $forumCateList[$key]['topic'] = [];
            foreach($topicList as $val){
                if($value['id'] == $val['p_id']){
                    $forumCateList[$key]['topic'][] = $val;
                }
            }
        }

        return ToApiFormat('success',$forumCateList);

    }


    /**
     * 主题
     * @author GuoLin
     * @createdate 2018-09-28
     *
     */
    public function forumTopicList(){

        $params = $this->validateRequestParams(['page','topic']);

        $dataList = Db::name('forum')
            ->alias('f')
            ->join('user u','u.id = f.uid')
            ->field('f.id,f.title,f.create_time,f.view,f.pic,f.settop,u.username,u.userhead')
            ->where(['f.topic'=>$params['topic'],'f.open'=>1])
            ->order(['f.create_time'=>'desc'])
            ->paginate(15, false, ['query' => ['page'=>$params['page']]]);

        $page = [
            'currentPage' => intval(($dataList->currentPage())),
            'lastPage'    => $dataList->lastPage(),
            'total'       => $dataList->total(),
            'listRows'    => $dataList->listRows()
        ];

        $topicInfo = Db::name('cate')->field('id,title_ch,description')->where(['id'=>$params['topic'],'status'=>1])->find();

        $dataList = $dataList->toArray()['data'];

        return ToApiFormat('success',compact('dataList','topicInfo', 'page'));

    }

    /**
     * 收藏
     * @author GuoLin
     * @createdate 2018-10-08
     *
     */

    public function collect(){

        $params = $this->validateRequestParams(['id']);

        $userId = $this->checkToken();

        $forumData = Db::name('forum')->where(['id'=>$params['id'],'open'=>1])->find();

        if(!$forumData){
            throw new Exception('帖子不存在', 2);
        }

        $data = Db::name('collect')->where(['forum_id'=>$params['id'],'uid'=>$userId])->find();

        if($data){
            Db::name('forum')->where('id',$params['id'])->setDec('collect');
            $result = Db::name('collect')->where(['forum_id'=>$params['id'],'uid'=>$userId])->delete();
            if($result){
                return ToApiFormat('已取消收藏',['type'=>1]);
            }
        }else{
            Db::name('forum')->where('id',$params['id'])->setInc('collect');
            $result = Db::name('collect')->insert(['uid'=>$userId,'forum_id'=>$params['id'],'create_time'=>time()]);
            if($result){
                return ToApiFormat('已收藏',['type'=>2]);
            }
        }
    }


    /**
     * 删除用户自己发的帖子
     * @author GuoLin
     * @createdate 2018-10-08
     *
     */
    public function deleteArticle(){

        $userId = $this->checkToken();

        $params = $this->validateRequestParams(['id']);

        $result = Db::name('forum')->delete(['id'=>$params['id'],'uid'=>$userId]);

        if($result){
            return ToApiFormat('帖子已删除');
        }else{
            throw new Exception('error', 2);
        }

    }

    public function testParam(){
        $params = $this->validateRequestParams(['content']);
        dump(strip_tags(htmlspecialchars_decode($params['content'])));
    }

}