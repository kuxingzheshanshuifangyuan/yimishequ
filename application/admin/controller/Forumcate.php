<?php
namespace app\admin\controller;

use app\common\model\Forum as ForumModel;
use app\common\model\Forumcate as ForumcateModel;
use app\common\controller\AdminBase;
use think\Db;
use think\Request;

/**
 * 栏目管理
 * Class Category
 * @package app\admin\controller
 */
class Forumcate extends AdminBase
{

    protected $category_model;
     protected $article_model;

    protected function _initialize(){
        parent::_initialize();
        $this->category_model = new ForumcateModel();
        $this->article_model = new ForumModel();
      
        $category_level_list  = $this->category_model->catetree();

        //dump($category_level_list);
        $this->assign('category_level_list', $category_level_list);
    }

    /**
     * 栏目管理
     * @return mixed
     */
    public function index(){
        return $this->fetch();
    }

    /**
     * 添加栏目
     * @param string $pid
     * @return mixed
     */
    public function add($pid = ''){
        //推荐板块
        $forumcate=Db::name('forumcate')->field('id,name')->order(['id'=>'desc'])->select();
        //会员等级
        $usergrade=Db::name('usergrade')->field('id,name')->order(['score'=>'asc','id'=>'desc'])->select();
        $this->assign([
            'forumcate' => $forumcate,
            'usergrade' => $usergrade,
        ]);
        return $this->fetch('add');
    }

    /**
     * 保存栏目
     */
    public function save(){

        if($this->request->isPost()){


            $data            = $this->request->param();

            $validate_result = $this->validate($data, 'Forumcate');

            if($validate_result !== true){

            	return json(array('code' => 0, 'msg' =>$validate_result));
                //$this->error($validate_result);
            }else{
                $data['usergrade'] = implode(',',isset($_POST['usergrade']) ? $_POST['usergrade'] : array());
                $data['recommend'] = implode(',' , isset($_POST['recommend']) ? $_POST['recommend'] : array());
                $data['category']  = implode(',' , isset($_POST['category']) ? $_POST['category'] : array());
                if ($this->category_model->allowField(true)->save($data)) {
                    return json(array('code' => 200, 'msg' => '添加成功'));
                }else{
                    return json(array('code' => 0, 'msg' => '添加失败'));
                }
            }
        }
    }

    /**
     * 编辑栏目
     * @param $id
     * @return mixed
     */
    public function edit($id){
        //主题 所有
        $cate=Db::name('cate')->where('p_id',$id)->select();
        //推荐板块  所有
        $forumcate=Db::name('forumcate')->field('id,name')->order(['id'=>'desc'])->select();
        //会员等级
        $usergrade=Db::name('usergrade')->field('id,name')->order(['score'=>'asc','id'=>'desc'])->select();

        $check=Db::name('forumcate')->where('id',$id)->find();

        $category_ids =$check['category'];     //主题
        $forumcate_ids=$check['recommend'];    //板块
        $usergrade_ids=$check['usergrade'];    //所属会员等级

        $category_id =explode(',',$category_ids);
        $forumcate_id=explode(',',$forumcate_ids);
        $usergrade_id=explode(',',$usergrade_ids);

        $this->assign([
            'cate' => $cate,
            'forumcate' => $forumcate,
            'usergrade' => $usergrade,
            'category_id' => $category_id,
            'forumcate_id'=>$forumcate_id,
            'usergrade_id'=>$usergrade_id,
        ]);

        $category = $this->category_model->find($id);

        return $this->fetch('edit', ['tptc' => $category]);
    }

    /**
     * 更新栏目
     * @param $id
     */
    public function update($id){
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            $validate_result = $this->validate($data, 'Forumcate');

            if ($validate_result !== true) {
                  return json(array('code' => 0, 'msg' => $validate_result));
            } else {
                $data['usergrade']= implode(',',  isset($_POST['usergrade']) ? $_POST['usergrade'] : array());
                $data['recommend']= implode(',' , isset($_POST['recommend']) ? $_POST['recommend'] : array());
                $data['category'] = implode(',' , isset($_POST['category']) ? $_POST['category'] : array());
                $children = $this->category_model->getchilrenid($id);
                if (!empty($children)&&in_array($data['pid'], $children)) {
                   // $this->error('不能移动到自己的子分类');
                    return json(array('code' => 0, 'msg' => '不能移动到自己的子分类'));
                } else {
                    if ($this->category_model->allowField(true)->save($data, $id) !== false) {
                       return json(array('code' => 200, 'msg' => '更新成功'));
                    } else {
                       return json(array('code' => 0, 'msg' => '更新失败'));
                    }
                }
            }
        }
    }

    public function updatestatus($id,$status,$name){
    	if ($this->request->isGet()) {

    		if ($this->category_model->where('id', $id)->update([$name=>$status]) !== false) {
    			//  $this->success('更新成功');
    			return json(array('code' => 200, 'msg' => '更新成功'));
    		} else {
    			// $this->error('更新失败');
    			return json(array('code' => 0, 'msg' => '更新失败'));
    		}
    	}
    	 
    }


    //修改帖子封面 状态
    public function update_picstatus($id,$status){
        if ($this->request->isGet()) {

            if (Db::name('forumcate')->where('id', $id)->update(['pic_status' =>$status]) !== false) {
                //  $this->success('更新成功');
                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {
                // $this->error('更新失败');
                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }

    }

    /**
     * 删除栏目
     * @param $id
     */
    public function delete($id){
        $category = $this->category_model->where(['tid' => $id])->find();
        $article  = $this->article_model->where(['tid' => $id])->find();

        if (!empty($category)) {
        	return json(array('code' => 0, 'msg' => '此分类下存在子分类，不可删除'));
          //  $this->error('此分类下存在子分类，不可删除');
        }
        if (!empty($article)) {
        	return json(array('code' => 0, 'msg' => '此分类下存在文章或帖子，不可删除'));
          //  $this->error('此分类下存在文章，不可删除');
        }
        if ($this->category_model->destroy($id)) {
        	return json(array('code' => 200, 'msg' => '删除成功'));
         //   $this->success('删除成功');
        } else {
        	return json(array('code' => 0, 'msg' => '删除失败'));
         //   $this->error('删除失败');
        }
    }


}