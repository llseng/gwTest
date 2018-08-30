<?php
namespace app\api\controller;
use think\Db;
use think\facade\Session;
use CenCMS\ApiController;

class Index extends ApiController
{
	public function index() {
	   echo self::returnSuccess(array(
        'banner' => $this->banner(), 
        'announce' => $this->announce(), 
        'calendar' => $this->calendar(), 
        'horizon_banner1' => $this->horizonBanner(1),
        'horizon_banner2' => $this->horizonBanner(2),
        'recommend' => $this->recommend(),
        'normal_infomation' => $this->normalInfomation(),
        ),'获取成功');
	}

    // todo
    public function city() {
        /*$provinces = self::doQuery(
            $command = "select",
            $db      = "provinces",
            $map     = "",
            $param   = "*"
        );   
        foreach ($provinces as $value) {
            $province[$value['province']] = self::doQuery(
                $command = "select",
                $db      = "cities",
                $map     = ["provinceid"=>$value['provinceid']],
                $param   = "city"
            );
        } */
       /* $provinces = self::doQuery(
            $command = "select",
            $db      = "provinces",
            $map     = "",
            $param   = "*"
        );
        // print_r($provinces);
        // 遍历查询本身就速度慢，更何况查询的内容又多，还是全局搜索。。。（总之一句话，就是速度慢慢慢啊啊啊~）
        foreach ($provinces as $value) {
            $province = self::doQuery(
                $command = "select",
                $db      = "cities",
                $map     = ["provinceid"=>$value['provinceid']],
                $param   = "city"
            );
            foreach ($province as $key => $value) {
                $cities[] = json_encode($value);
            }
            $city[$value['province']] = implode(",", $cities);
        }

        echo self::returnSuccess($city);*/
        return ;
    }

    // 横向滚动
    public function banner() {
        $banner = self::doQuery(
            $command = "select",
            $db      = "banner",
            $map     = '',
            $param   = "pic_url,id"
        );

        return $banner;
    }

    // 公告
    public function announce() {
        $announces = self::doQuery(
            $command = "select",
            $db      = "announce",
            $map     = "",
            $param   = "title,id"
        );

        return $announces;
    }

    // 日历
    public function calendar() {
        $timeYmd=date('Y-m-d',time());
        $weekarray=array("星期日","星期一","星期二","星期三","星期四","星期五","星期六");

        $calendar=array(
              'timeYmd' =>  $timeYmd,
              'week' =>  $weekarray[date("w",strtotime($timeYmd))],
              'date' =>  '农历五月初五',
              'date2' =>  '戊戌年戊戌月辛巳日', 
              'suit' =>  '嫁娶生娃生猴', 
              'nose' =>  '只能谈恋爱不能秀恩爱', 
             );
        return $calendar;
    }

    // 自动横向滚动   TODO
    public function horizonBanner($number) {
        $postcity='5810';
        
        $varName = 'horizonBanner'.$number;
        $horizonBannerValue = 'horizonBannerValue'.$number;
        $$varName = self::doQuery(
            $command = "select",
            $db      = "lease_house",
            $map     = ["city"=>$postcity,'type'=>1],
            $param   = "title,id,price,pic_url,longitude,latitude",
            $join    = "",
            $link    = "",
            $order   = "id",
            $sort    = "desc",
            $start   = 0,
            $num     = 2
        );

        return $$varName;
    }

    // 人气推荐 
    public function recommend() {
        $recommend = self::doQuery(
            $command = "select",
            $db      = "common_house",
            $map     = "",
            $param   = "id,title,info,price,pic_url",
            $join    = "",
            $link    = "",
            $order   = "id",
            $sort    = "desc",
            $start   = 0,
            $num     = 3
        );

        return $recommend;
    }

    // 普通信息
    public function normalInfomation() {
        $normal = self::doQuery(
            $command = "select",
            $db      = "common_house",
            $map     = "",
            $param   = "id,title,price",
            $join    = "",
            $link    = "",
            $order   = "id",
            $sort    = "desc",
            $start   = 0,
            $num     = 8
        );

        return $normal;
    }

}