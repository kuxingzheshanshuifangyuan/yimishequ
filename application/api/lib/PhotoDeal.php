<?php
namespace app\api\lib;

class PhotoDeal
{
    /**
     * @param $from_photo_path string 图片来源地址
     * @param $to_photo_path string 需要保存图片位置
     * @param $percent float 缩小率(0 -1)
     * @param $quality int 图像质量 0 - (100 最大最好)
     * @return bool
     */
    static public function compressPhoto($from_photo_path, $to_photo_path = '', $percent = 0.8, $quality = 80)
    {
        list($width, $height, $image_type) = $imageInfo = getimagesize($from_photo_path);
        $new_width = $width * $percent;
        $new_height = $height * $percent;

        //以原图长宽为0.8创建新图
        $new_image = imagecreatetruecolor($new_width,$new_height);//创建新画布

        switch ($image_type){
            case IMAGETYPE_JPEG:
                $type = 'jpeg';
                break;
            case IMAGETYPE_PNG:
                $quality = $quality/10 - 1;
                $type = 'png';
                break;
            case IMAGETYPE_GIF:
                $type = 'gif';
                break;
            case IMAGETYPE_BMP:
                $type = 'bmp';
                break;
            default:
                imagedestroy($new_image);
                return false;
        }

        $create_image_funname = 'imagecreatefrom' . $type;

        // self::checkMemory($imageInfo);
        if(!$image = @$create_image_funname($from_photo_path)){
            return false;
        }
        imagecopyresampled($new_image,$image,0,0,0,0,$new_width,$new_height,$width,$height);

        $to_image_funname = 'image' . $type;
        $to_image_funname($new_image, $to_photo_path, $quality);

        imagedestroy($new_image);
        imagedestroy($image);
        return true;
    }

    /**
     * 检测内存
     * @param $imageInfo array 图形信息
     */
    static public function checkMemory($imageInfo)
    {
        $MB = Pow(1024,2);   // number of bytes in 1M
        $K64 = Pow(2,16);    // number of bytes in 64K
        $TWEAKFACTOR = 1.8;   // Or whatever works for you
        $memoryNeeded = round( ( $imageInfo[0] * $imageInfo[1]
                * $imageInfo['bits']
                * $imageInfo['channels'] / 8  // 注意: 有一部分浏览器在上传图片是, 没有携带 该参数,会导致其报错,请留意
                + $K64
            ) * $TWEAKFACTOR
        );
        $memoryHave = memory_get_usage();
        $memoryLimitMB = (integer) ini_get('memory_limit');
        $memoryLimit = $memoryLimitMB * $MB;

        if ( function_exists('memory_get_usage')
            && $memoryHave + $memoryNeeded > $memoryLimit
        ) {
            $newLimit = $memoryLimitMB + ceil( ( $memoryHave
                        + $memoryNeeded
                        - $memoryLimit
                    ) / $MB
                );
            ini_set( 'memory_limit', $newLimit . 'M' );
        }
    }
}
// $quality = -22*($info->getSize()/5000000) + 82; //当图片大于1m时,将图片压缩到500k
// 注意 , 需要修改配置文件 php.ini
// post_max_size = 50M
// upload_max_filesize = 50M