<?php
namespace app\admin\controller;

use app\common\model\Upload as UploadModel;
use app\common\controller\AdminBase;
use think\Cache;
use think\Db;
use org\PHPMailer\phpmailer;
/**
 * 系统配置
 * Class System
 * @package app\admin\controller
 */
class System extends AdminBase
{
    public function _initialize()
    {
        parent::_initialize();
        
    }

    /**
     * 站点配置
     */
    public function siteConfig(){

        $site_config = Db::name('system')->field('value')->where('name', 'site_config')->find();
        $site_config = unserialize($site_config['value']);
        
      //return view('site_config', ['site_config' => $site_config]);
      
     /* $site_config['user_reg']=1;
        $site_config['forum_sh']=1;
        $site_config['site_wjt']=1;
        $site_config['site_keyword']=''; */

        return $this->fetch('site_config', ['site_config' => $site_config]);
    }

    /**
     * 更新配置
     */
    public function updateSiteConfig()
    {
        if ($this->request->isPost()) {
            $site_config                = $this->request->post('site_config/a');
            $site_config['site_tongji'] = htmlspecialchars_decode($site_config['site_tongji']);


            $data['value'] = serialize($site_config);
            
            $path = 'application/extra/web.php';
            $file = include $path;
            $config = array(
            		'WEB_TPL' => $site_config['site_tpl'],

            );
            $res = array_merge($file, $config);
            $str = '<?php return [';
            
            foreach ($res as $key => $value){
            	$str .= '\''.$key.'\''.'=>'.'\''.$value.'\''.',';
            };
            $str .= ']; ';
            file_put_contents($path, $str);
           
            
            $path = 'application/config.php';
          
        //   $site_config['site_tpl']
         
           $str = '<?php return [';
           $str .="  'datetime_format'           => false,";
         
           if($site_config['site_wjt']==1){
           	$str .="'url_route_on'           => true,";
           }else{
           	$str .="'url_route_on'           => false,";
           }
          
           $str .= ']; ';
           file_put_contents($path, $str);
            
            
            
            
            if (Db::name('system')->where('name', 'site_config')->update($data) !== false) {
                //$this->success('提交成功');
                
                return json(array('code' => 200, 'msg' => '提交成功'));
            } else {
               // $this->error('提交失败');
                return json(array('code' => 200, 'msg' => '提交失败'));
            }
        }
    }

    /**
     * 清除缓存
     */
/*     public function clear()
    {
        if (delete_dir_file(CACHE_PATH) || delete_dir_file(TEMP_PATH)) {
            $this->success('清除缓存成功');
        } else {
            $this->error('清除缓存失败');
        }
    } */
    function clear(){
    	delete_dir_file(CACHE_PATH);
    	array_map('unlink', glob(TEMP_PATH . '/*.php'));
    	if (!file_exists(TEMP_PATH)) {
    	return json(array('code'=>200,'msg'=>'暂无缓存'));
    	}else{
    		rmdir(TEMP_PATH);
    		return json(array('code'=>200,'msg'=>'更新缓存成功'));
    	}
    	
    
    }
    public function doUploadPic()
    {
    	$uploadmodel =new UploadModel();

    	
    	
    	$info=$uploadmodel->upfile('images','FileName');
    	echo $info['headpath'];
    	
    	
    	
    	
    	
    }
    public function ajax_mail_test() {
    	
    	   $data            = $this->request->param();
    	
    	if(!$data['email']){
    		return json(array('code'=>0,'msg'=>'邮箱地址为空'));
    	}else{
    	
    		$data['body']='测试邮件内容';
    		$data['title']='测试邮件标题';
    		
    		asyn_sendmail($data);
    	
    	return json(array('code'=>1,'msg'=>'已发送测试邮件'));
    	//return json(array('code'=>1,'msg'=>$data['email']));
    	}
    
    }

}