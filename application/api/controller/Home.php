<?php

namespace app\api\controller;

use app\common\model\Cate;
use think\Controller;
use think\Db;
use \app\api\exception\BaseException as Exception;
use app\common\enum\SupermarketEnum;


class Home extends Base
{

    // 校验参数规则数组
    protected $validate_param_rules = [
        'version'       => ['版本','require'],
    ];


    /**
     * 首页
     * @author GuoLin
     * @createdate 2018-08-17
     *
     */
    public function index(){

        $data = [];
        $data['banner']     = Db::name('banner')
            ->field('id,app_img,url')
            ->where('status', 1)
            ->order('order','asc')
            ->limit(5)
            ->select();
        $data['newForum']   = Db::name('forum')
            ->alias('f')
            ->join('user u','f.uid = u.id','left')
            ->field('f.id,f.title,f.pic,f.create_time,f.view,u.username,u.userhead')
            ->where(['f.open'=>1,'f.tid'=>['NOT IN',[30,31,32,33,34]]])
            ->where('f.open',1)
            ->order('f.create_time desc')
            ->limit(3)
            ->select();
        $data['hotForum']   = Db::name('forum')
            ->alias('f')
            ->join('user u','f.uid = u.id','left')
            ->field('f.id,f.title,f.pic,f.create_time,f.view,u.username,u.userhead')
            ->where('f.open',1)
            ->where(['create_time'=>['>=',strtotime(date('Y-m-d',time()-86400*30))]])
            ->order('f.view desc')
            ->limit(3)
            ->select();
        $data['hotNews']    = Db::name('forum')
            ->alias('f')
            ->join('user u','f.uid = u.id','left')
            ->field('f.id,f.title,f.pic,f.create_time,f.view,u.username,u.userhead')
            ->where(['f.open'=>1,'f.tid'=>17])
            ->order('f.create_time desc')
            ->limit(3)
            ->select();
        $data['successfulCase'] = Db::name('money')
            ->field('id,name,phone,money')
            ->where('status', 1)
            ->limit(7)
            ->select();
        $data['productDisplay'] =  Db::name('app')
            ->field('id,img,dec,app_id')
            ->where('status', 1)
            ->order('id desc')
            ->limit(6)
            ->select();

        $data['loansSupermarket'] = json_decode(getHttpResponsePOST(SupermarketEnum::ForumProductListFileUrl,'',[]),true);
        $data['loansSupermarket'] = $data['loansSupermarket']['name'] == 'error' || !$data['loansSupermarket'] ? []: array_slice($data['loansSupermarket']['data'], 0, 4);

        return ToApiFormat('success',$data);
    }

    /**
     * app更新
     * @author GuoLin
     * @createdate 2018-08-30
     *
     */
    public function appUpdate(){
        $data = [
            'updateStatus' => 1,        //更新状态   1开启 0关闭
            'version'      => '1.0',    //版本号
            'comment'      => '测试版本更新啦啦啦',      //更新描述
            'packaging'    => 0,        //马甲包状态 1开启 0关闭
            'isForced'     => 0,        //强制更新   1开启 0关闭
            'downloadUrl'  => 'http://www.1miclub.com/H58F129D9_0821185643.apk',
        ];
        return ToApiFormat('success',$data);
    }


    /**
     * app更新
     * @author GuoLin
     * @createdate 2018-09-06
     *
     */
    public function appIosUpdate(){

        $data = [
            'updateStatus' => 1,        //更新状态   1开启 0关闭
            'version'      => '1.1',    //版本号
            'comment'      => '测试版本更新啦啦啦',      //更新描述
            'packaging'    => 0,        //马甲包状态 1开启 0关闭
            'isForced'     => 0,        //强制更新   1开启 0关闭
            'downloadUrl'  => 'http://www.1miclub.com/ios',
        ];

        return ToApiFormat('success',base64_encode((json_encode($data))));

    }

    /**
     * app多渠道版本更新
     * @author GuoLin
     * @createdate 2018-09-20
     *
     */
    public function appVersionUpdate(){

        $params = $this->validateRequestParams(['id','version']);

        try{

            $currentVersion = Db::name('app_update_record')->where(['version'=>$params['version'],'platform_id'=>$params['id']])->find();

            $updateVersion  = Db::name('app_update_record')->where(['platform_id'=>$params['id']])->order(['id'=>'desc'])->find();

        }catch (\Exception $e){
            throw new Exception($e->getMessage(), $e->getCode());
        }

        if(!$currentVersion || !$updateVersion){
            throw new Exception('非法请求', 2);
        }

        if($params['version'] < $updateVersion['version'] && $updateVersion['is_packaging'] == 1){
            $updateVersion['version']       = $currentVersion['version'];
            $updateVersion['push_content']  = $currentVersion['push_content'];
            $updateVersion['is_forced']     = $currentVersion['is_forced'];
            $updateVersion['download_url']  = $currentVersion['download_url'];
        }

        $data = [
            'updateStatus' => 1,                                      //更新状态   1开启 0关闭
            'version'      => $updateVersion['version'],              //版本号
            'comment'      => $updateVersion['push_content'],         //更新描述
            'packaging'    => $currentVersion['is_packaging'],        //马甲包状态 1开启 0关闭
            'isForced'     => $updateVersion['is_forced'],            //强制更新   1开启 0关闭
            'downloadUrl'  => $updateVersion['download_url'],
        ];

        return ToApiFormat('success',$data);
    }


    public function indexExtend(){

        $data = [];

        $data['banner']     = [
            [
                'id'       => 1,
                'app_img'  => '/uploads/20180901/76954a68d9793e1cdc78330801ff6946.png',
                'url'      => '#'
            ],
            [
                'id'       => 2,
                'app_img'  => '/uploads/20180901/0127e752ce0aceb3e00986aeae59e608.png',
                'url'      => '#'
            ]
        ];

        $data['newForum']   = Db::name('forum')
            ->alias('f')
            ->join('user u','f.uid = u.id','left')
            ->field('f.id,f.title,f.pic,f.create_time,f.view,u.username,u.userhead')
            ->where('f.open',1)
            ->where('f.tid','in',[30,31,32,33,34])
            ->order('f.id desc')
            ->limit(3)
            ->select();

        $data['hotForum']   = Db::name('forum')
            ->alias('f')
            ->join('user u','f.uid = u.id','left')
            ->field('f.id,f.title,f.pic,f.create_time,f.view,u.username,u.userhead')
            ->where('f.open',1)
            ->where('f.tid','in',[30,31,32,33,34])
            ->order('f.view desc')
            ->limit(3)
            ->select();

        $data['hotNews']    = Db::name('forum')
            ->alias('f')
            ->join('user u','f.uid = u.id','left')
            ->field('f.id,f.title,f.pic,f.create_time,f.view,u.username,u.userhead')
            ->where(['f.open'=>1,'f.tid'=>30])
            ->order('f.id desc')
            ->limit(3)
            ->select();

        $data['programa'] = [
            [
                'id'    => 31,
                'name'  => '服饰',
            ],
            [
                'id'    => 32,
                'name'  => '家居	',
            ],
            [
                'id'    => 33,
                'name'  => '出行',
            ]
        ];

        $data['successfulCase'] = [];
        $data['productDisplay'] = [];
        $data['loansSupermarket'] = [];

        return ToApiFormat('success',$data);
    }

    /**
     * 获取所有板块
     * @author GuoLin
     * @createdate 2018-08-31
     *
     */
    public function forumCateList(){
        $forumCateList = Db::name('forumcate')
            ->field('id,name')
            ->where(['show'=>1,'type'=>1])
            ->where('id','in',[30,31,32,33,34])
            ->order(['sort'=>'asc','id'=>'desc'])
            ->select();
        return ToApiFormat('success',$forumCateList);
    }

}