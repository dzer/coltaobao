<?php
namespace dzer\coltaobao\lib;

use dzer\coltaobao\basic\Config;
use dzer\coltaobao\basic\Log;
use dzer\coltaobao\basic\MysqliDb;
use dzer\coltaobao\basic\WebSocketClient;

/**
 * 基础类
 *
 * @package dzer\coltaobao\lib
 * @author dzer <d20053140@gmail.com>
 * @version 2.0
 */
class TbBase
{
    protected $uid;
    protected $log;
    protected $db;
    protected $client;

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
     * 发送消息给服务端
     *
     * @param $send_msg
     * @return mixed
     */
    public function pushNotification($send_msg)
    {
        if (empty($this->uid)) {
            return false;
        }
        $data = [
            'send_user_id' => $this->uid,
            'send_msg' => $send_msg,
            'send_type' => 'coltaobao'
        ];
        if ($this->client == null) {
            $this->client = new WebSocketClient('127.0.0.1', 9501);
        }

        if (!$this->client->connect()) {
            $this->log->error('websocket连接失败！');
        }
        if($this->client->send(json_encode($data))) {
            $this->log->error('websocket消息发送成功！');
        } else {
            $this->log->error('websocket消息发送失败！');
        }
        return $this->client->close();
    }

}