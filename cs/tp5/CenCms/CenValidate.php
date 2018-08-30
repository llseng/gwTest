<?php
namespace CenCMS;

use think\Validate;


class CenValidate extends Validate {
    

    protected $common;

    public function __construct(){
        include('CenCMS/CenValidateCommon.php');
        $this->common = $validateCommon;
    }

    /**
	 * 数据验证
     * 使用递归
     * why: 多层
     * exit: 找到一个错误或者所有子元素都无错误，就退出验证
	 * @param string 	        $key    键
	 * @param string | array    $value  值（如果是数组，继续递归
	 */
	public function dataValidate($key,$value) {
        
        if (!is_array($value)) {
            // 如果不是数组，则返回错误
            // 出口
            $result = self::doValidate($key, $value);
            if ($result !== true) {
                // 不等于 true，就是有错误信息
                return $result;
            }

        } else {
            foreach ($value as $subKey => $subValue) {
                self::dataValidate($subKey, $subValue);
            }
        }

        return true;

    }

	private function doValidate ($field, $param) {
        // 获取验证规则以及错误信息
        $validate = $this->make(
            $this->getRuleByField($field),
            $this->getErrMsgByField($field)
        );
        // 如果validate 为空时，返回true
        if (empty($validate)) {

            return true;
        } 
        // 进行数据校验
        $result = $validate->check([$field => $param]);
        // 如果错误返回错误信息
        if (!$result) {
            return $validate->getError();
        } else {
            // 如果正确，返回true
            return true;
        }
        
	}

    // 获取验证规则
	private function getRuleByField ($field) {
        if (isset($this->common['rule'][$field])) {
            return $this->common['rule'][$field];
        } else {
            return [];
        }
    }
    // 获取错误提示
    private function getErrMsgByField ($field) {
        if (isset($this->common['errMsg'][$field])) {
            return $this->common['errMsg'][$field];
        } else {
            return [];
        }
        // return $this->common['errMsg'][$field];
    }
}