<?php

namespace addons\attach\controller;

use app\index\controller\Addons;
use Think\Loader;
use think\Model;
use think\Db;
use think\File;
use think\Request;
use org\Http;
use org\Upload;
use think\Cache;

class Attach extends Addons
{

    public function download()
    {

        $param = request()->post();


        $map['md5'] = $param['md5'];
        $m = Db::name('attach')->where($map)->find();
        $config = $this->getConfig('attach');

        if (preg_match("/^(http:\/\/|https:\/\/).*$/", $m['savepath'])) {
            $remote = 1;
        } else {
            $remote = 0;
        }


        if (empty($m)) {
            return array('code' => 0, 'msg' => '该附件不存在');
        } else {


            if ($param['zuid'] != $param['uid']) {

                $note['controller'] = 'attachdownload';
                $note['uid'] = $param['uid'];
                $note['pointid'] = $m['id'];
                $noteinfo = Db::name('point_note')->where($note)->find();
                if (!empty($noteinfo)) {
                    Db::name('attach')->where($map)->setInc('download');


                    if ($remote == 1) {
                        return array('code' => 200, 'msg' => '开始下载', 'path' => $m['savepath'], 'name' => $m['name'], 'local' => 0);
                    } else {
                        $dir = dirname($_SERVER['SCRIPT_FILENAME']) . '/';
                        return array('code' => 200, 'msg' => '开始下载', 'path' => $dir . $m['savepath'], 'name' => $m['name'], 'local' => 1);
                    }
                } else {
                    $point = Db::name('user')->where('id', $param['uid'])->value('point');
                    if ($point < $m['score'] && $m['score'] > 0) {

                        $site_config = Cache::get('site_config');
                        if (empty($site_config['jifen_name'])) {
                            $site_config['jifen_name'] = '积分';
                        }

                        return array('code' => 0, 'msg' => $site_config['jifen_name'] . '不足');
                    } else {

                        if ($m['score'] > 0) {
                            //Db::name('user')->where('id',$param['uid'])->setDec('point',$param['score']);
                            point_note(0 - $m['score'], $param['uid'], 'attachdownload', $m['id']);
                            point_note($m['score'], $param['zuid'], 'attachdownload', $m['id']);
                            //Db::name('user')->where('id',$param['zuid'])->setInc('point',$param['score']);
                        }

                        Db::name('attach')->where($map)->setInc('download');


                        if ($remote == 1) {
                            return array('code' => 200, 'msg' => '开始下载', 'path' => $m['savepath'], 'name' => $m['name'], 'local' => 0);
                        } else {
                            $dir = dirname($_SERVER['SCRIPT_FILENAME']) . '/';
                            return array('code' => 200, 'msg' => '开始下载', 'path' => $dir . $m['savepath'], 'name' => $m['name'], 'local' => 1);
                        }


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


        //return array('code'=>0,'msg'=>json_encode($param));


    }

    public function info()
    {
        $param = request()->post();


        $map['md5'] = $param['md5'];
        $m = Db::name('attach')->where($map)->find();
        $m['size'] = format_bytes($m['size']);

        return array('code' => 200, 'info' => $m);

    }

    public function setscore()
    {
        $param = request()->post();


        $map['md5'] = $param['md5'];


        $m = Db::name('attach')->where($map)->setField('score', $param['score']);
        return array('code' => 200, 'info' => $m);

    }

    public function upload()
    {

        $config = $this->getConfig('attach');


        if ($config['open'] == 1) {


            $driverConfig = array(

                'secrectKey' => $config['secrectKey'],
                'accessKey' => $config['accessKey'],
                'domain' => $config['domain'],
                'bucket' => $config['bucket'],
            );

            $setting = array(

                'rootPath' => './',
                'saveName' => array('uniqid', ''),
                'hash' => true, //是否生成hash编码

            );


            $setting['exts'] = explode(',', $config['configext']);//array('zip', 'rar');
            $setting['maxSize'] = 50 * 1024 * 1024;
            $File = $_FILES['file'];
            //$file = request()->file('file');
            $Upload = new Upload($setting, 'Qiniu', $driverConfig);

            $info = $Upload->uploadOne($File);
            if ($info) {

                $data['sha1'] = $info['sha1'];

                $data['md5'] = $info['md5'];


                $data['create_time'] = time();//;
                $data['uid'] = session('userid');
                //$data['score']=0;
                //$data['threadid']=0;

                $data['download'] = 0;


                $data['size'] = $info['size'];


                $data['name'] = $info['name'];
                $data['ext'] = $info['ext'];
                $data['savepath'] = $info['url'];
                $data['savename'] = $info['savename'];
                $data['mime'] = $info['type'];
                $map['md5'] = $info['md5'];
                $mmn = Db::name('attach')->where($map)->find();
                if (empty($mmn)) {
                    Db::name('attach')->insert($data);
                    $res = Db::name('attach')->getLastInsID();
                    if ($res > 0) {

                        return array('code' => 200, 'msg' => '上传成功', 'hasscore' => 0, 'ext' => $data['ext'], 'id' => $res, 'path' => $data['savepath'], 'md5' => $data['md5'], 'savename' => $data['savename'], 'filename' => $data['name'], 'info' => $info);
                    } else {
                        return array('code' => 0, 'msg' => '上传失败');
                    }
                } else {
                    return array('code' => 200, 'msg' => '上传成功', 'hasscore' => 1, 'ext' => $mmn['ext'], 'id' => $mmn['id'], 'path' => $mmn['savepath'], 'md5' => $mmn['md5'], 'savename' => $mmn['savename'], 'filename' => $mmn['name'], 'info' => $mmn);
                }
            } else {
                return array('code' => 0, 'msg' => $Upload->getError());
            }


        } else {

            $file = request()->file('file');
            $md5 = $file->hash('md5');

            $n = Db::name('attach')->where('md5', $md5)->find();
            if (empty($n)) {


                //$file = request()->file('file');
                $info = $file->validate(['size' => 50 * 1024 * 1024, 'ext' => $config['configext']])->move(ROOT_PATH . DS . 'uploads');
                if ($info) {

                    $path = DS . 'uploads' . DS . $info->getSaveName();
                    $path = str_replace("\\", "/", $path);
                    $realpath = WEB_URL . $path;

                    $data['sha1'] = $info->sha1();

                    $data['md5'] = $info->md5();
                    $data['create_time'] = time();//;
                    $data['uid'] = session('userid');
                    //$data['score']=0;
                    //$data['threadid']=0;

                    $data['download'] = 0;


                    $data['size'] = $info->getSize();

                    $fileinfo = $info->getInfo();

                    $data['name'] = $fileinfo['name'];
                    $data['ext'] = $info->getExtension();
                    $data['savepath'] = $path;
                    $data['savename'] = $info->getFilename();
                    $data['mime'] = $fileinfo['type'];

                    Db::name('attach')->insert($data);
                    $res = Db::name('attach')->getLastInsID();
                    if ($res > 0) {

                        return array('code' => 200, 'msg' => '上传成功', 'hasscore' => 0, 'ext' => $data['ext'], 'id' => $res, 'path' => $path, 'headpath' => $realpath, 'md5' => $data['md5'], 'savename' => $data['savename'], 'filename' => $data['name'], 'info' => $info->getInfo());
                    } else {
                        return array('code' => 0, 'msg' => '上传失败');
                    }


                } else {

                    return array('code' => 0, 'msg' => $file->getError());
                }


            } else {
                //echo  json_encode($n);
                //	return;
                $path = $n['savepath'];

                $realpath = WEB_URL . $path;
                return array('code' => 200, 'msg' => '上传成功', 'hasscore' => 1, 'ext' => $n['ext'], 'id' => $n['id'], 'path' => $path, 'headpath' => $realpath, 'md5' => $n['md5'], 'savename' => $n['savename'], 'filename' => $n['name'], 'info' => $n);
            }
        }


        //$info = $file->validate(['size'=>5000000])->move(ROOT_PATH . DS . 'uploads');
        //$file = Request::instance()->file('file');
//	$info = $file->validate(['size'=>5000000])->move(ROOT_PATH . DS . 'uploads');
        //$file = request()->file('file');
        //$filemode=new FileModel();
        //$File= $_FILES['file'];
        //$file = new File($File['tmp_name'],'rw');
        // 移动到框架应用根目录/public/uploads/ 目录下
        /* 		$File= $_FILES['file'];
                $file = new File($File['tmp_name'],'rw');
            $time = date("Y-m-d");
            $file->setUploadInfo($File['name']);


            $info = $file->validate(['size'=>15678222222])->move(ROOT_PATH .DS . 'uploads'); */


    }
}
