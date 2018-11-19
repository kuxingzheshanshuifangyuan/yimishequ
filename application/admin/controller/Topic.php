<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/7/17
 * Time: 17:35
 */

namespace app\admin\controller;

use app\common\model\Cate as CateModel;
use app\common\controller\AdminBase;
use think\Db;

class Topic extends AdminBase{

    protected $cate_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->cate_model = new CateModel();

    }

    //主题 页面
    public function index(){
        
        $topic=Db::name('cate')
                  ->alias('c')
                  ->join('forumcate f','c.p_id=f.id')
                  ->field('c.id,c.sort,c.title_ch,c.title_en,c.create_time,c.update_time,c.status,f.name')
                  ->order(['c.sort' => 'asc','c.id' => 'desc'])
                  ->select();
        //搜索查询  版块
        $forumcate=Db::name('forumcate')->order(['sort' => 'asc'])->select();
        $this->assign([
            'topic'     => $topic,
            'forumcate' => $forumcate,
            ]);

        return $this->fetch();
    }

    //搜索 版块下主题
    public function checktopic(){

        $forumcate_id=$_POST['id'];

        $topics=Db::name('cate')
            ->alias('c')
            ->join('forumcate f','c.p_id=f.id')
            ->field('c.id,c.sort,c.title_ch,c.title_en,c.create_time,c.update_time,c.status,f.name')
            ->where('c.p_id',$forumcate_id)
            ->order(['c.sort' => 'asc','c.id' => 'desc'])
            ->select();
        foreach($topics as $key=>$val){

            $topic_id=$val['id'];
            $topics[$key]['url']=url("topic/edit","id=$topic_id");
            $topics[$key]['create_time']=friendlyDate($val['create_time']);
            $topics[$key]['update_time']=friendlyDate($val['update_time']);

        }
            
        echo json_encode($topics);

    }

    //添加主题 页面
    public function add(){
        //版块
        $forumcate=Db::name('forumcate')->select();
        $this->assign('forumcate',$forumcate);
        return $this->fetch();
    }

    //主题添加
    public function save(){

        $topic['title_ch']=request()->post('title_ch');
        $topic['title_en']=request()->post('title_en');
        $topic['status']=request()->post('status');
        $topic['sort']=request()->post('sort');
        $topic['p_id']=request()->post('p_id');
        $topic['create_time']=time();
        $topic['update_time']=time();
        $add_topic=Db::name('cate')->insert($topic);

        if($add_topic){
            $this->success('添加成功！',url('topic/index'));
        }
    }

    //主题编辑 修改 页面
    public function edit(){
        $topic_id=input('id');
        $topic=Db::name('cate')->where('id',$topic_id)->find();
        $forumcate=Db::name('forumcate')->order(['sort' => 'asc','id' => 'desc'])->select();
        $this->assign([
            'forumcate' => $forumcate,
            'topic'     => $topic,
        ]);
        return $this->fetch();
    }

    //主题编辑 修改
    public function update(){

        $topic_id=$_POST['id'];

        $topic['title_ch']=isset($_POST['title_ch']) ? $_POST['title_ch'] : '';
        $topic['title_en']=isset($_POST['title_en']) ? $_POST['title_en'] : '';
        $topic['p_id']=isset($_POST['p_id']) ? $_POST['p_id'] : '';
        $topic['sort']=isset($_POST['sort']) ? $_POST['sort'] : '';
        $topic['status']=isset($_POST['status']) ? $_POST['status'] : '';
        $topic['update_time']=time();
        $update_topic=Db::name('cate')->where('id',$topic_id)->update($topic);
        if($update_topic){
            $this->success("修改成功！",url('topic/index'));
        }

    }

    //显示 隐藏 状态
    public function updatestatus($id,$status){
        if ($this->request->isGet()) {

            if (Db::name('cate')->where('id', $id)->update(['status' =>$status]) !== false) {
                //  $this->success('更新成功');
                return json(array('code' => 200, 'msg' => '更新成功'));
            } else {
                // $this->error('更新失败');
                return json(array('code' => 0, 'msg' => '更新失败'));
            }
        }

    }

    //删除主题
    public function delete($id)
    {
        if ($this->cate_model->destroy($id)) {
            //$this->success('删除成功');
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            // $this->error('删除失败');
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }
}