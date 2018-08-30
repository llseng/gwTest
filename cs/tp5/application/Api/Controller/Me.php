<?php
namespace app\api\controller;
use think\Db;
use think\facade\Session;
use CenCMS\ApiController;
use Addons\AliSms\SMSHelper;
use app\api\UploadImage;

// 「我的」 模块
class Me extends ApiController
{
    // 登录
    public function login() {

        $data = self::getPost(['username', 'password']);

        $username = $data['username'];

        $password = md5($data['password']);

        $mydata = self::doQuery(
            $command 	= 'find',
            $db 	= 'users',
            $map 	= [
                'username'=>$username,
                'password'=>$password
            ],
            $param 	= 'id'
        );

        if ($mydata) {
            session::set('userid',$mydata['id']);
            echo self::returnSuccess([], '登录成功');
        } else {
            echo self::returnError('登录失败');
        }
    }

    public function sendSMS() {

        $post = self::getPost(['mobile']);
        $PhoneNumber = $post['mobile'];
        $code = SMSHelper::generate_code();

        $sendSms = SMSHelper::sendSms($code, $PhoneNumber);

        $data['Message'] = $sendSms -> Message;
        $data['RequestId'] = $sendSms -> RequestId;
        $data['Code'] = $sendSms -> Code;
        $endtime=time()+900;

        if ($data['Message']=="OK"){
                $data = self::setField(
                    $command  = 'insert',
                    $db       = 'sms',
                    $map      = '',
                    $param    = [
                        'Code' => $code,
                        'Mobile' =>  $PhoneNumber,
                        'ctime' => time(),
                        'endtime' => $endtime
                    ]
                );
       
            echo self::returnSuccess([],'发送成功');
            exit; 
        }else{
            echo self::returnError('发送失败');
            exit; 
        }
    }

    // 注册
    public function register() {

        $post = self::getPost(['mobile', 'nickname', 'password', 'code']);
        $mobile = $post['mobile'];
        $nickname = $post['nickname'];
        $password = md5($post['password']);
        $code     = $post['code'];

        $getCode = self::doQuery(
                        $command  = 'find',
                        $db       = 'sms',
                        $map      = ['Mobile'=>$mobile],
                        $param    = 'Code',
                        $join     = '', 
                        $link     = '', 
                        $order    = 'id',
                        $sort     = 'desc'
                    );
        if ($code == $getCode['Code']) {
            // 查看用户是否存在
            $checkUser =  self::doQuery(
                            $command  = 'select',
                            $db       = 'users',
                            $map      = ['username'=>$mobile],
                            $param    = 'username'
                        );
            if ($checkUser) {
                echo self::returnError('用户已存在');
                exit;
            }

            // 插入数据
            $data = self::setField(
                $command  = 'insert',
                $db       = 'users',
                $map      = '',
                $param    = [
                    'username' => $mobile,
                    'nickname' => $nickname,
                    'password' => $password,
                    'mobile'   => $mobile
                ]
            );

            // 判断
            if ( $data ) {
                // 成功
                session::set('userid',$data['id']);
                echo self::returnSuccess([]);
            } else {
                // 失败
                echo self::returnError('注册失败');
            }
        } else {
            // 失败
                echo self::returnError('验证码不正确');
        }

        
    }

    // 获取（个人）基本信息
    public function getUserInfo() {
        
        $userid = self::isLogin();

        $errMsg = '';

        if ($userid) {
            $me = self::doQuery(
                $command  = 'find',
                $db     = 'users',
                $map    = ['id'=>$userid],
                $param  = 'mobile,avatar,id,nickname'
            );

                if ($me) {
                    echo self::returnSuccess($me);
                    return;
                }
    
                $errMsg = '查询失败';
            } else {
                $errMsg = '未登录';
            }
    
            echo self::returnError($errMsg);

    }

    // 获取（个人）详细信息
    public function getUserDetail() {
        
        $userid = self::isLogin();

        $errMsg = '';

        if ($userid) {
            $me = self::doQuery(
                $command  = 'find',
                $db     = 'users',
                $map    = ['cn_users.id'=>$userid],
                $param  = 'nickname,avatar,idcard,mobile,sex,age,email',
                $join	= 'cn_users_info',
                $link   = 'cn_users.id=cn_users_info.uid'
            );

    
                if ($me) {
                    echo self::returnSuccess($me);
                    return;
                }
    
                $errMsg = '查询失败';
            } else {
                $errMsg = '未登录';
            }
    
            echo self::returnError($errMsg);

    }

    public function logout() {

        $userid = self::isLogin();

        Session::delete('userid');

        echo self::returnSuccess([], '退出登录成功');

    }
    
    /*  

    * 获取单个字段

    */

    // 获取 昵称
    public function getNickname() {

            $userid = self::isLogin();

            $errMsg = '';
    
            if ($userid) {
                $me = self::doQuery(
                    $command  = 'find',
                    $db     = 'users',
                    $map    = ['id'=>$userid],
                    $param  = 'nickname'
                );
    
                if ($me) {
                    echo self::returnSuccess($me);
                    return;
                }
    
                $errMsg = '查询失败';
            } else {
                $errMsg = '未登录';
            }
    
            echo self::returnError($errMsg);
    }

    // 获取 身份证 详情
    public function getIdCard() {
 
        $userid = self::isLogin();

        $errMsg = '';

        if ($userid) {
            $me = self::doQuery(
                $command  = 'find',
                $db     = 'users',
                $map    = ['cn_users.id'=>$userid],
                $param  = 'mobile,realname,idcard_no',
                $join   = 'cn_users_info',
                $link   = 'cn_users.id=cn_users_info.uid'
            );

            if ($me) {
                echo self::returnSuccess($me);
                return;
            }

            $errMsg = '查询失败';
        } else {
            $errMsg = '未登录';
        }

        echo self::returnError($errMsg);
    }
    
    // 获取 手机号
    public function getMobile() {
     
        $userid = self::isLogin();

        $errMsg = '';

        if ($userid) {
            $me = self::doQuery(
                $command  = 'find',
                $db     = 'users',
                $map    = ['id'=>$userid],
                $param  = 'mobile'
            );

            if ($me) {
                echo self::returnSuccess($me);
                return;
            }

            $errMsg = '查询失败';
        } else {
            $errMsg = '未登录';
        }

        echo self::returnError($errMsg);
    }

    // 获取 email
    public function getEmail() {

        $userid = self::isLogin();

        $errMsg = '';

        if ($userid) {
            $me = self::doQuery(
                $command  = 'find',
                $db     = 'users',
                $map    = ['id'=>$userid],
                $param  = 'email'
            );

            if ($me) {
                echo self::returnSuccess($me);
                return;
            }

            $errMsg = '查询失败';
        } else {
            $errMsg = '未登录';
        }

        echo self::returnError($errMsg);
    }


    /*  

    * 修改单个字段

    */

    // 修改 昵称
    public function upNickname() {
        $userid = self::isLogin();
        
        $data = self::getPost(['nickname']);
        $errMsg = '';

        if ($userid) {
            $upNickname = self::setField(
                $command  = 'update',
                $db     = 'users',
                $map    = ['id'=>$userid],
                $param  = ['nickname'=>$data['nickname']]
            );

            if ($upNickname) {
                echo self::returnSuccess([]);
                return;
            }

            $errMsg = '修改失败';
        } else {
            $errMsg = '未登录';
        }

        echo self::returnError($errMsg);
    }

    // 修改 身份证
    public function upIdCard() {
        $userid = self::isLogin();
        $post = self::getPost(['mobile', 'realname', 'idcard_no']);
        $mobile =$post['mobile'];
        $realname = $post['realname'];
        $idcard_no = $post['idcard_no'];
        $errMsg = '';

        if ($userid) {
            // 验证手机号是否合法
            // $mobile = self::dataValidate('mobile',$post['mobile']);

            $upIdcard_no = self::setField(
                $command  = 'update',
                $db     = 'users_info',
                $map    = ['uid'=>$userid],
                $param  = [
                    'realname'=>$realname,
                    'idcard_no'=>$idcard_no,
                    'idcard'=>0
                ]
            );

            if (!$upIdcard_no) {
                echo self::returnError('修改失败');
                exit;
            }
                echo self::returnSuccess([]);

            $errMsg = '修改失败';
        } else {
            $errMsg = '未登录';
        }

    }

    // 修改 手机号
    public function upMobile() {

        // 此处做数据验证，如果验证失败，直接输出，且 exit
        $userid = self::isLogin();

        $data = self::getPost(['mobile']);
        
        // 进行数据库的操作
        $upSex = self::setField(
            $command  = 'update',
            $db     = 'users',
            $map    = ['id'=>$userid],
            $param  = ['mobile'=>$data['mobile']]
        );

        if (!$upSex) {
            echo self::returnError('修改失败');
            exit;
        }
        
        echo self::returnSuccess([]);
    }

    // 修改 email
    public function upEmail() {
        // 此处做数据验证，如果验证失败，直接输出，且 exit
        $userid = self::isLogin();

        $data = self::getPost(['email']);
        
        // 进行数据库的操作
        $upSex = self::setField(
            $command  = 'update',
            $db     = 'users',
            $map    = ['id'=>$userid],
            $param  = ['email'=>$data['email']]
        );

        if (!$upSex) {
            echo self::returnError('修改失败');
            exit;
        }
        
        echo self::returnSuccess([]);

    }

    // 修改 头像
    public function upAvatar() {

        $userid = self::isLogin();

        $pic_url = UploadImage::uploadAvatarImage();

        // 进行数据库的操作
        $upAvatar = self::setField(
            $command  = 'update',
            $db     = 'users',
            $map    = ['id'=>$userid],
            $param  = ['avatar'=>$pic_url]
            );

        if (!$upAvatar) {
            echo self::returnError('修改失败');
            exit;
        }

        echo self::returnSuccess(['pic_url' => $pic_url], '修改成功');
        exit; 
    }

    // 修改 性别
    public function upSex() {

        // 此处做数据验证，如果验证失败，直接输出，且 exit
        $userid = self::isLogin();

        $data = self::getPost(['sex']);
        
        // 进行数据库的操作
        $upSex = self::setField(
            $command  = 'update',
            $db     = 'users',
            $map    = ['id'=>$userid],
            $param  = ['sex'=>$data['sex']]
        );

        if (!$upSex) {
            echo self::returnError('修改失败');
            exit;
        }
        
        echo self::returnSuccess([]);

    }
    
    // 修改 年龄
    public function upAge() {
        
        // 此处做数据验证，如果验证失败，直接输出，且 exit
        $userid = self::isLogin();

        $data = self::getPost(['age']);
        
        // 进行数据库的操作
        $upSex = self::setField(
            $command  = 'update',
            $db     = 'users',
            $map    = ['id'=>$userid],
            $param  = ['age'=>$data['age']]
        );

        if (!$upSex) {
            echo self::returnError('修改失败');
            exit;
        }
        
        echo self::returnSuccess([]);
    }

    /*

    * 私有方法

    */

    // 判断是否登录，已经登录返回 userid，未登录返回 false
/*    private function isLogin() {
        $userid = Session::get('userid');

        if (empty($userid)) {
            echo self::returnError('未登录');
            exit;
        }

        return $userid;
    }
*/
}