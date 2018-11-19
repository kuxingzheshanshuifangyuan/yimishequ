<?php

namespace app\api\controller\v1;

use \app\api\exception\BaseException as Exception;

class Upload extends Base
{
    public function upload()
    {
        $this->checkToken();
        if (!$files = request()->file()) {
            throw new Exception("not upload file", 20);

        }

        $_re_path = '/public/uploads/';
        $_path = ROOT_PATH . $_re_path;
        if (!is_dir($_path)) {
            //如果没有目标地址，则尝试创建，失败则返回false;
            if (!@mkdir($_path, 0777, true)) {
                throw new Exception("dir create fail", 21);
            }
        }

        $saved_files = [];

        foreach ($files as $file) {
            // 移动到框架应用根目录/public/uploads/ 目录下
            if ($file->getSize() > 10000000) {
                throw new Exception('文件不能大于10m', 22);
            }
            $ext = pathinfo($file->getInfo()['name'], PATHINFO_EXTENSION);
            $allow_ext_arr = array('jpg', 'gif', 'png', 'jpeg', 'xls', 'xlsx', 'doc', 'docx', 'wps', 'txt', 'pdf', 'ppt', 'pptx');//允许上传文档格式
            if (!in_array($ext, $allow_ext_arr)) {
                throw new Exception('不支持上传该类型', 22);
            }
            $info = $file->move($_path);

            if ($info) {
            } else {
                throw new Exception($file->getError(), 22);
            }

            // 压缩图片 若图片大于500k 并且为jpg 或png时
            $extension = pathinfo($info->getSaveName(), PATHINFO_EXTENSION);
            if ($info->getSize() > 500000 && ($extension == 'jpg' || $extension == 'png')) {
                $hard_path = $_path . $info->getSaveName();
                $quality = -22 * ($info->getSize() / 5000000) + 82;
                \app\xgy_api\lib\PhotoDeal::compressPhoto($hard_path, $hard_path, 0.8, $quality);
            }

            if(in_array($extension, ['jpg','png','gif'])){
                $saved_files[] = str_replace("\\", "/", $_re_path . $info->getSaveName());
            }else{
                $saved_files[] = str_replace("\\", "/", $_re_path . $info->getSaveName().'|'.$info->getInfo('name'));
            }
        }

        return ToApiFormat('upload success', compact('saved_files'));
    }
}