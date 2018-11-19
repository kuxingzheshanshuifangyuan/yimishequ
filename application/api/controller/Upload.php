<?php

namespace app\api\controller;

use \app\api\exception\BaseException as Exception;
use \think\Image;

class Upload extends Base
{
    /**
     * 图片上传
     * @author GuoLin
     * @createdate 2018-08-20
     *
     */
    public function userUploadBase64Image(){

        $this->checkToken();

        $img = $this->request->param('img');

        if(!$img){
            throw new Exception('图片不能为空', 1);
        }

        $result = saveBase64File($img);

        if($result === false){
            throw new Exception('图片上传失败', 1);
        }

        return ToApiFormat('success',['path'=>$result]);
    }


    public function userUpload()
    {

        $userId = $this->checkToken();


        if (!$files = request()->file()) {
            throw new Exception("not upload file", 20);

        }

        $_re_path = '/uploads/';
        $_path = ROOT_PATH . $_re_path;
        if (!is_dir($_path)) {
            //如果没有目标地址，则尝试创建，失败则返回false;
            if (!mkdir($_path, 0700)) {
                throw new Exception("dir create fail", 21);
            }
        }


        $saved_files = [];

        foreach ($files['img'] as $file) {
            // 移动到框架应用根目录/public/uploads/ 目录下
            if ($file->getSize() > 10000000) {
                throw new Exception('文件不能大于10m', 22);
            }

            $ext = pathinfo($file->getInfo()['name'], PATHINFO_EXTENSION);
//            $allow_ext_arr = array('jpg', 'gif', 'png', 'jpeg', 'xls', 'xlsx', 'doc', 'docx', 'wps', 'txt', 'pdf', 'ppt', 'pptx');//允许上传文档格式
            $allow_ext_arr = array('jpg', 'gif', 'png', 'jpeg');//允许上传文档格式
            if (!in_array($ext, $allow_ext_arr)) {
                throw new Exception('不支持上传该类型', 22);
            }

            $info = $file->move($_path);

            if (!$info) {
                throw new Exception($file->getError(), 22);
            }

            // 压缩图片 若图片大于500k 并且为jpg 或png时
            $extension = pathinfo($info->getSaveName(), PATHINFO_EXTENSION);
            if ($info->getSize() > 500000 && ($extension == 'jpg' || $extension == 'png')) {
                $hard_path = $_path . $info->getSaveName();
                $quality = -22 * ($info->getSize() / 5000000) + 82;
                \app\api\lib\PhotoDeal::compressPhoto($hard_path, $hard_path, 0.8, $quality);
            }

            if (in_array($extension, ['jpg', 'png', 'gif'])) {
                $saved_files[] = str_replace("\\", "/", $_re_path . $info->getSaveName());
            }
//            echo $_path . $info->getSaveName();exit;
            try{
                $img=Image::open(ROOT_PATH . 'uploads/'. $info->getSaveName());
                $img->water(ROOT_PATH.'/public/forum/img/index/watermark_logo.png',Image::WATER_SOUTHEAST,80)->save(ROOT_PATH . 'uploads/'. $info->getSaveName());
            }catch (\Exception $e){
                echo $e->getMessage().ROOT_PATH . 'uploads/'. $info->getSaveName();exit;
            }
            return ToApiFormat('upload success', compact('saved_files'));
        }

    }
}