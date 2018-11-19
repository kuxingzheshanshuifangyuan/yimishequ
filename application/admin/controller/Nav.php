<?php
namespace app\admin\controller;

use app\common\model\Nav as NavModel;
use app\common\controller\AdminBase;
use think\Db;

/**
 * 导航管理
 * Class Nav
 * @package app\admin\controller
 */
class Nav extends AdminBase
{

    protected $nav_model;

    protected function _initialize(){
        parent::_initialize();
        $this->nav_model = new NavModel();
        $nav_list        = $this->nav_model->order(['sort' => 'ASC', 'id' => 'ASC'])->select();
       // $nav_level_list  = array2level($nav_list);

        $this->assign('nav_list', $nav_list);

    }

    /**
     * 导航管理
     * @return mixed
     */
    public function index(){

        $nav=get_nav();
        $new_nav=get_second_nav();

        $this->assign('nav',$nav);
        $this->assign('new_nav',$new_nav);

        return $this->fetch();

    }

    /**
     * 添加导航
     * @param string $pid
     * @return mixed
     */
    public function add($pid = ''){
        return $this->fetch('add');
    }

    /**
     * 保存导航
     */
    public function save(){
        if ($this->request->isPost()) {
            $data            = $this->request->post();
            $validate_result = $this->validate($data, 'Nav');

            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                if ($this->nav_model->allowField(true)->save($data)) {
                   return json(array('code' => 200, 'msg' => '添加成功'));
                } else {
                  return json(array('code' => 0, 'msg' => '添加失败'));
                }
            }
        }

    }

    /**
     * 编辑导航
     * @param $id
     * @return mixed
     */
    public function edit($id){
        $nav = $this->nav_model->find($id);

        return $this->fetch('edit', ['nav' => $nav]);
    }

    /**
     * 更新导航
     * @param $id
     */
    public function update($id){
        /*dump($_POST);die;*/
        if ($this->request->isPost()) {
            $data            = $this->request->post();
            $validate_result = $this->validate($data, 'Nav');

            if ($validate_result !== true) {
              //  $this->error($validate_result);
                return json(array('code' => 0, 'msg' => $validate_result));
            } else {
                if ($this->nav_model->allowField(true)->save($data, $id) !== false) {
                   // $this->success('更新成功');
                    return json(array('code' => 200, 'msg' => '更新成功'));
                } else {
                   // $this->error('更新失败');
                    return json(array('code' => 0, 'msg' => '更新失败'));
                }
            }
        }

    }
    public function updatestatus($id,$status){
    	if ($this->request->isGet()) {

    		if ($this->nav_model->where('id', $id)->update(['status' =>$status]) !== false) {
    			//  $this->success('更新成功');
    			return json(array('code' => 200, 'msg' => '更新成功'));
    		} else {
    			// $this->error('更新失败');
    			return json(array('code' => 0, 'msg' => '更新失败'));
    		}
    	}
    	 
    }
    /**
     * 删除导航
     * @param $id
     */
    public function delete($id){
        if ($this->nav_model->destroy($id)) {
            //$this->success('删除成功');
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
        	return json(array('code' => 0, 'msg' => '删除失败'));
           // $this->error('删除失败');
        }

    }

    /**
     * 添加二级导航
     */
    public function second_add(){
        $pid=input('id');
        $nav=Db::name('nav')->where('id',$pid)->find();
        $this->assign('nav',$nav);

        return $this->fetch();

    }

    /**
    * 保存二级导航
     */
    public function second_save(){
        $nav['nav_id'] = $_POST['id'];
        $nav['name']   = isset($_POST['second_name']) ? $_POST['second_name'] : '';
        $nav['alias']  = isset($_POST['alias']) ? $_POST['alias'] : '';
        $nav['link']   = isset($_POST['link']) ? $_POST['link'] : '';
        $nav['sid']    = isset($_POST['sid']) ? $_POST['sid'] : '';
        $nav['icon']   = isset($_POST['icon']) ? $_POST['icon'] : '';
        $nav['status'] = isset($_POST['status']) ? $_POST['status'] : '';
        $nav['target'] = isset($_POST['target']) ? $_POST['target'] : '';
        $nav['pid']    = isset($_POST['pid']) ? $_POST['pid'] : '';

        $addnav = Db::name('nav')->insert($nav);
        if ($addnav) {
            $this->success('添加成功！', url('nav/index'));
        }

    }

    //编辑二级导航
    public function second_edit(){

        $nav_id=input('id');
        $nav=Db::name('nav')->where('id',$nav_id)->find();
        //所有父级导航
        $top_nav=Db::name('nav')->where('nav_id',0)->select();

        $this->assign([
            'nav' => $nav,
            'top_nav' => $top_nav,
        ]);
        return $this->fetch();

    }

    //修改二级导航
    public function second_update(){

        $nav_id=$_POST['id'];

        $nav['nav_id'] = isset($_POST['nav_id']) ? $_POST['nav_id'] : '';
        $nav['name']   = isset($_POST['second_name']) ? $_POST['second_name'] : '';
        $nav['alias']  = isset($_POST['alias']) ? $_POST['alias'] : '';
        $nav['link']   = isset($_POST['link']) ? $_POST['link'] : '';
        $nav['sid']    = isset($_POST['sid']) ? $_POST['sid'] : '';

        $nav['pid']    = isset($_POST['pid']) ? $_POST['pid'] : '';
        $nav['status'] = isset($_POST['status']) ? $_POST['status'] : '';
        $nav['target'] = isset($_POST['target']) ? $_POST['target'] : '';
        $nav['sort']   = isset($_POST['sort']) ? $_POST['sort'] : '';

        $update_nav=Db::name('nav')->where('id',$nav_id)->update($nav);

        if($update_nav){
            $this->success('修改成功!',url('nav/index'));
        }else{
            $this->success('修改成功!',url('nav/index'));
        }

    }

}