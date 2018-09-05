<?php
namespace app\common\logic;

class Upload
{

    private $PATH = "Uploads/src/";

    private $fileField; //

    private $file; //文件对象
    private $fileName; //原文件名
    private $fileTmp; //上传缓存文件
    private $fileType; //文件类型
    private $fileExt; //文件后缀

    private $movePoth; //自定义路径

    private $fullName; //保存后文件名

    private $filePath; //文件保存路径

    private $errMsg; //错误信息

    private $typeRes; //文件限制

    private $extRes; //后缀限制

    private $sizeRes = 8 * 1024 * 1024; //文件大小限制

    //图片文件限制
    private $imageTypeRes = ['image/jpeg','image/png','image/gif'];

    //图片后缀限制
    private $imageExtRes = ['jpg','jpeg','png','gif'];

    //上传错误信息
    private $Uploaderror = [
        '没有错误发生，文件上传成功。',
        '上传的文件大小超过了限制。', //上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值。
        '上传文件的大小超过选项指定值。', //上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值。
        '文件只有部分被上传。',
        '没有文件被上传。',
        '找不到临时文件夹。',
        '文件写入失败。',
        'ERROR_TMP_FILE_NOT_FOUND' => '找不到临时文件',
        'ERROR_TMP_FILE' => '临时文件错误',
        'ERROR_NOT_HTTP_UPLOAD' => '文件不是HTTP POST上传',
        'ERROR_TYPE_NOT_ALLOW' => '文件格式不允许',
        'ERROR_EXT_NOT_ALLOW' => '文件后缀不允许',
        'ERROR_SIZE_NOT_ALLOW' => '上传文件过大',
        'ERROR_FILE_SAVE_FAILED' => '文件保存失败',
        'ERROR_DOR_CREATE_FAILED' => '目录创建失败',
        'ERROR_FILE_WRITE_FAILED' => '目录没有写入权限',

        'ERROR_UNKNOWN' => '未知错误'
    ];

    //构造函数
    public function __construct($fileField = '',array $config,$type = "upImage")
    {
        $this->fileField = $fileField;

        if($config)
        {
            if($config['typeRes']) $this->typeRes = is_array($config['typeRes']) ?: explode(',',$config['typeRes']);
            if($config['extRes']) $this->extRes = is_array($config['extRes']) ?: explode(',',$config['extRes']);

            $sizeRes = (int)$config['sizeRes'];
            if($sizeRes) $this->sizeRes = $sizeRes;

            if($config['movePoth']) $this->movePoth = $config['movePoth'];
        }

        if($type != 'upImage')
        {
            //return $this->getUpInfo();
        }else{
            $this->upImage();
        }
        //return $this->getUpInfo();

    }

    /**
     * 上传图片
     */
    public function upImage()
    {
        if(!$this->typeRes) $this->typeRes = $this->imageTypeRes;

        if(!$this->movePoth) $this->movePoth = "images/";

        if($this->fileField)
        {
            $this->moveFile($this->fileField);
        }else{
            $this->moveFile(key($_FILES));
        }

    }

    /**
     * 单个文件上传
     */
    public function upFile()
    {
        //$FILE = $this->file = $_FILES['']
    }

    /**
     * 保存/移动文件
     * @param $_FILES[key] $file
     * @return string|bool
     */
    private function moveFile($file)
    {
        //上传文件
        $FILE = $this->file = $_FILES[$file];

        //无文件对象
        if(!$FILE)
        {
            return $this->errMsg = $this->getErrMsg('ERROR_TMP_FILES_NOT_FOUND');
        }

        $this->fileName = substr($FILE['name'],0,strripos($FILE['name'],'.'));
        $this->fileSize = $FILE['size'];
        $this->fileTmp = $FILE['tmp_name'];
        
        //文件上传错误
        if($FILE['error'] !== 0)
        {
            return $this->errMsg = $this->getErrMsg($FILE['error']);
        }

        //上传临时文件不存在
        if(!file_exists($this->fileTmp))
        {
            return $this->errMsg = $this->getErrMsg('ERROR_TMP_FILE');
        }

        //不是HTTP POST 上传的
        if(!is_uploaded_file($this->fileTmp))
        {
            return $this->errMsg = $this->getErrMsg('ERROR_NOT_HTTP_UPLOAD');
        }

        $this->fileType = $this->getFileType();
        $this->fileExt = $this->getFileExt();

        $this->fullName = $this->getFullName();

        $this->filePath = $this->getFilePath();

        //验证文件类型
        if(!$this->checkType())
        {
            return $this->errMsg = $this->getErrMsg('ERROR_TYPE_NOT_ALLOW');
        }

        //验证文件后缀
        if(!$this->checkExt())
        {
            return $this->errMsg = $this->getErrMsg('ERROR_EXT_NOT_ALLOW');
        }

        //验证文件大小
        if(!$this->checkSize())
        {
            return $this->errMsg = $this->getErrMsg('ERROR_SIZE_NOT_ALLOW');
        }

        //验证是否有上传目录/并创建
        if(!$this->checkDirExists())
        {
            return $this->errMsg = $this->getErrMsg('ERROR_DOR_CREATE_FAILED');
        }

        //验证文件夹是否有写入权限
        if(!$this->checkDirWrite())
        {
            return $this->errMsg = $this->getErrMsg('ERROR_FILE_WRITE_FAILED');
        }
        
        //文件保存失败
        if(!move_uploaded_file($this->fileTmp , $this->filePath))
        {
            return $this->errMsg = $this->getErrMsg('ERROR_FILE_SAVE_FAILED');
        }

        return $this->errMsg = true;
        
    }

    //获取返回信息
    public function getUpInfo()
    {
        return [
            'status' => $this->errMsg,
            'type' => $this->fileType,
            'ext' => $this->fileExt,
            'size' => $this->fileSize,
            'name' => $this->errMsg!==true ? $this->fileName : $this->fullName,
            'url' => $this->errMsg!==true ? $this->file['name'] : $this->filePath
        ];
    }

    //获取自定义路径
    private function getMovePath()
    {
        return $this->movePoth . date("Ymd/");
    }

    //获取文件保存路径
    private function getFilePath()
    {
        return $this->PATH . $this->getMovePath() . $this->fullName .'.'. $this->fileExt;
    }

    //验证文件夹是否有写入权限
    public function checkDirWrite()
    {
        $path = $this->PATH . $this->getMovePath();

        if(is_writable($path)) return true;

        return false;
    }

    //验证是否有上传目录/并创建
    private function checkDirExists()
    {
        $path = $this->PATH . $this->getMovePath();

        if(!file_exists($path) && !mkdir($path,0777,true)) return false;

        return true;
    }

    //验证文件大小
    private function checkSize()
    {
        return $this->sizeRes > $this->fileSize;
    }

    //验证文件后缀
    private function checkExt()
    {
        if($this->extRes) return in_array($this->fileExt,$this->extRes);

        return true;
    }

    //验证文件格式
    private function checkType()
    {
        if($this->typeRes) return in_array($this->fileType,$this->typeRes);

        return true;
    }

    //获取保存后名字
    private function getFullName()
    {
        return md5($this->fileName . rand(1000,9999));
    }

    //获取文件名
    private function getFileName()
    {
        return $this->file['name'];
    }

    //获取文件类型
    private function getFileType()
    {
        return $this->file['type'];
    }

    //获取文件后缀
    private function getFileExt()
    {
        return substr(strrchr($this->file['name'],'.'),1);
    }

    //设置错误信息
    private function getErrMsg($errCode)
    {
        return $this->Uploaderror[$errCode] ?: $this->Uploaderror['ERROR_UNKNOWN'];
    }


}