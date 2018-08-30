<?php
namespace app\api\controller;
use think\Db;
use think\facade\Session;
use CenCMS\ApiController;

define('RENTING', 0);
define('SELLING', 1);
define('AVATAR', 2);

class UploadImage extends ApiController {

    public function uploadRentingImage() {
        $pic_url = self::uploadImage(RENTING);
        echo self::returnSuccess(['pic_url' => $pic_url], '上传成功');
    }

    public function uploadAvatarImage() {
        return self::uploadImage(AVATAR);
    }

    /*
    * 该方法成功**返回**图片地址，错误则会 **echo** 错误信息，然后直接 exit ！
    */
    private function uploadImage($type) {

        $path = "Uploads/src/images/";//上传路径 

        switch ($type) {
            case RENTING:
                $path = $path.'rent/';
                break;
            case SELLING:
                $path = $path.'sell/';
                break;
            case AVATAR:
                $path = $path.'avatar/';
                break;
            default:
                break;
        }

        if ($_FILES["file"]["type"] != "image/gif" && 
        $_FILES["file"]["type"] != "image/jpeg" && 
        $_FILES["file"]["type"] != "image/pjpeg") {
            echo self::returnError('图片格式错误');
            exit;
        }

        if ($_FILES["file"]["size"] > 20000000) {
            echo self::returnError('文件过大');
            exit;
        }

        if ($_FILES["file"]["error"] > 0) {
            echo self::returnError($_FILES["file"]["error"]);
            exit;
        }

        $name = $_FILES['file']['name']; 	

        $type = strtolower(substr(strrchr($name, '.'), 1)); //获取文件类型 
        $pic_name = time() . rand(10000, 99999) . "." . $type;//图片名称 
        $pic_url = $path . $pic_name;//上传后图片路径+名称 
        $name_tmp = $_FILES['file']['tmp_name']; 

        if (move_uploaded_file($name_tmp, $pic_url)) { //临时文件转移到目标文件夹 

            // TODO: 连接上服务器的 ip
        
            return $pic_url;

        } else {      
            echo self::returnError('写入失败');
            exit;
        } 

    }

}