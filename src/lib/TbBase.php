<?php
namespace dzer\coltaobao\lib;

use dzer\coltaobao\basic\Config;
use dzer\coltaobao\basic\Log;
use dzer\coltaobao\basic\MysqliDb;

/**
 * 基础类
 *
 * @author  dx
 * @date 2015/12/9
 * @version 1.0
 */
//思路：
//1、输入域名，搜索所有商品，
//echo file_get_contents('http://hhtmy.tmall.com/nocategory.htm?search=y&pageNum=1&viewType=list&searchAll=all&searchRange=all&orderType=price_asc');
//echo curl('http://hws.m.taobao.com/cache/wdetail/5.0/?id=43056929707');
//2、正则匹配出商品id
//3、通过接口获取商品信息	http://hws.m.taobao.com/cache/wdetail/5.0/?id=43056929707
//4、通过接口返回的信息 获取商品描述
class TbBase
{
    protected $log;
    protected $db;

    /**
     * 店铺地址
     *
     * @var string
     */
    protected $shopUrl;

    /**
     * 店铺ID
     *
     * @var integer
     */
    protected $shopId;

    /**
     * 商品ID
     *
     * @var integer
     */
    protected $goodsId;

    /**
     * 获取店铺所有商品的地址
     *
     * @var string
     */
    protected $allGoodsUrl = 'nocategory.htm?search=y&viewType=list&searchAll=all&searchRange=all&orderType=price_asc';

    /**
     * 店铺所有商品id
     *
     * @var array
     */
    protected $allGoodsIds;

    /**
     * 当前采集地址
     * @var string
     */
    protected $goodsInfoUrl = 'http://hws.m.taobao.com/cache/wdetail/5.0/?id=';

    /**
     * 商品列表
     *
     * @var array
     */
    protected $goodsList = array();

    public function __construct()
    {
        $this->log = Log::getInstance();
        $this->db = new MysqliDb(Config::get('db'));
    }

    /**
     * 创建目录
     *
     * @param integer $shopId 店铺id
     * @param integer $goodsId 商品id
     * @param string $dirName 目录名称
     * @return string
     */
    static function createDir($shopId, $goodsId = null, $dirName = null)
    {
        if (!empty($goodsId)) {
            $path = Config::get('resource') . '/' . $shopId . '/' . $goodsId . '/';
        } else {
            $path = Config::get('resource') . '/' . $shopId . '/';
        }
        if (!empty($dirName)) {
            $path .= $dirName . '/';
        }
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }

    /**
     * 保存指定图片到指定路径
     *
     * @param  string $url 图片http地址
     * @param string $path 图片保存的目录名称
     * @return  String
     */
    protected function saveImage($url, $path)
    {
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $pathInfo = pathinfo($url);
        $refer = $this->shopUrl;
        $extension = isset($pathInfo['extension']) ? $pathInfo['extension'] : 'jpg';
        $name = time() . rand(000, 999) . '.' . $extension;
        $result = self::getImgUrl($url, $refer);
        $fopen = fopen($path . $name, "a");
        @fwrite($fopen, $result);
        @fclose($fopen);
        if (file_exists($path . $name))
            return $path . $name;
        else
            return null;
    }

    /**
     * 获取远程图片资源
     *
     * @param  string $url 图片http地址
     * @param  string $refer header里面的referer参数值
     * @return bool|string
     */
    static function getImgUrl($url, $refer = '')
    {
        $option = array(
            'http' => array(
                'header' => "referer:$refer")
        );
        //创建并返回一个文本数据流并应用各种选项，可用于fopen(),file_get_contents()等过程的超时设置、代理服务器、请求方式、头信息设置的特殊过程。
        //函数原型：resource stream_context_create ([ array $options [, array $params ]] )
        $context = stream_context_create($option);
        $_content = file_get_contents($url, false, $context);
        if (empty($_content)) {
            //设置超时
            $ch = curl_init();
            $timeout = 20;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $_content = curl_exec($ch);
            curl_close($ch);
        }
        return $_content;
    }


}