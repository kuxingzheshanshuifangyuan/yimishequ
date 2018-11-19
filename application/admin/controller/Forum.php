<?php
namespace app\admin\controller;

use app\common\model\Forum as ForumModel;
use app\common\model\Forumcate as ForumcateModel;
use app\common\controller\AdminBase;
use think\Db;


class Forum extends AdminBase
{
	protected $forum_model;
    protected function _initialize()
    {
        parent::_initialize();
        $this->forum_model = new ForumModel();
    }


    public function index($keyword = '', $page = 1)
    {
        $map = [];

        if ($keyword) {
            session('forumkeyword',$keyword);
            $map['title|f.keywords']  = ['like', "%{$keyword}%"];
        }else{

            if(session('forumkeyword')!=''&&$page>1){
                $map['title|f.keywords']  = ['like', "%".session('forumkeyword')."%"];
            }else{
                session('forumkeyword',null);
            }

        }
        if($this->request->param('is_robot') == 1){
            $map['u.is_robot'] = 1;
        }elseif ($this->request->param('is_robot') == 2){
            $map['u.is_robot'] = 0;
        }

        $user_list = $this->forum_model->alias('f')->join('forumcate c','c.id=f.tid','left')->join('user u','u.id = f.uid')->field('f.*,c.id as cid,c.name')->order('f.create_time desc')->where($map)->paginate(10,false,['query'=>$this->request->param()]);
        //$user_list = $this->forum_model->where($map)->order('id DESC')->paginate(10, false, ['page' => $page]);
        return $this->fetch('index', ['user_list' => $user_list, 'keyword' => $keyword]);

    }

    public function toggle($id,$status,$name)
    {
    	if ($this->request->isGet()) {

    		if ($this->forum_model->where('id', $id)->update([$name=>$status]) !== false) {
    			// $this->success('更新成功');
    			return json(array('code' => 200, 'msg' => '更新成功'));
    		} else {
    			// $this->error('更新失败');
    			return json(array('code' => 0, 'msg' => '更新失败'));
    		}
    	}
    	 
    }

    /**
     * 编辑分类
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
    	$category = new ForumcateModel();
    	
    	$tptcs = $category->catetree();
    	
    	$emotion=action('index/index/getemotion');
    	
    	$this->assign('emotion',$emotion);
    	$this->assign(array('tptcs' => $tptcs));
        $slide_category = $this->forum_model->find($id);

        return $this->fetch('edit', ['slide_category' => $slide_category]);
    }

    /**
     * 更新分类
     * @throws \think\Exception
     */
    public function update()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            //保存缩略图
            $imgInfo = getPic(html_entity_decode($data['content']));

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

            $data['content']= remove_xss($data['content']);
            $data['title'] =  $data['title'];
            if ($this->forum_model->allowField(true)->save($data,$data['id']) !== false) {
                return json(array('code' =>200, 'msg' => '更新成功'));
            } else {
                return json(array('code' => 0, 'msg' => '更新失败'));
            }
        }
    }

    /**
     * 删除分类
     * @param $id
     * @throws \think\Exception
     */
    public function delete($id)
    {
    	$info=$this->forum_model->find($id);
    	$score=getpoint($info['uid'],'forumadd',$id);
    	point_note(0-$score,$info['uid'],'forumdelete',$id);

        if ($this->forum_model->destroy($id)) {

            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }
    public function alldelete()
    {
    	$params = input('post.');
    	foreach ($params['ids'] as $k =>$v){
    		$info=$this->forum_model->find($v);
    	    $score=getpoint($info['uid'],'forumadd',$v);
    	    point_note(0-$score,$info['uid'],'forumdelete',$v);
    		
    	}

    	$ids = implode(',', $params['ids']);

        $result = $this->forum_model->destroy($ids);
        if ($result) {
    	  	return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
    	  	return json(array('code' => 0, 'msg' => '删除失败'));
        }
   }


}