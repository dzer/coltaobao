<?php
namespace dzer\coltaobao\lib;

use dzer\coltaobao\basic\Request;
use Exception;

/**
 * 采集类
 * 采集店铺所有商品
 *
 * @author dzer <d20053140@gmail.com>
 * @version 2.0
 */
class TbCollection extends TbBase
{
    /**
     * @var int 店铺类型 1=天猫 2=淘宝
     */
    protected $shopType;

    public function __construct($shopUrl = null)
    {
        parent::__construct();
        if (!empty($shopUrl)) {
            $this->shopUrl = $shopUrl;
            if (strpos($this->shopUrl, 'tmall.com') !== false) {
                $this->shopType = 1;
            } else {
                $this->shopType = 2;
            }
        }

    }

    /**
     * 采集主要流程
     */
    public function colMain()
    {
        $start_time = $this->microTime();

        //第一步：通过店铺地址采集所有的店铺下所有商品的基本信息
        $this->colGoodsList();
        //第二步：通过商品id 循环采集单个商品详细信息
        $this->colGoodsInfo();

        $end_time = $this->microTime();
        $this->log->info("店铺：" . $this->shopId . ',共采集商品：' . count($this->goodsList) . '条，用时：' . ($end_time - $start_time) ."秒\r\n");
    }

    /**
     * 采集商品基本信息列表
     *
     * @return array
     * @throws Exception
     */
    protected function colGoodsList()
    {
        $request = new Request();
        //首先采集店铺下所有的商品基本信息

        //获取总页数
        $pageNum = $this->getGoodsPage();
        $urlList = $this->getGoodsPageUrl($pageNum);
        $content = implode('', $request->curlMulti($urlList));
        $this->goodsList = self::regExGoods($content);

        if (!empty($this->goodsList)) {
            return $this->goodsList;
        } else {
            throw new Exception('采集商品列表失败');
        }
    }

    /**
     * 获取总页数
     *
     * @return mixed
     * @throws Exception
     */
    public function getGoodsPage()
    {
        $url = $this->getGoodsPageUrl(1);
        $request = new Request();
        $content = $request->curl($url[0]);
        if (empty($content)) {
            throw new Exception('获取页数失败！请重试！');
        }
        //匹配总页数
        if ($this->shopType == 1) {
            preg_match('/<b class="ui-page-s-len">(.+?)<\/b>/s', $content, $page);
        } else {
            preg_match('/<span class="page-info">(.+?)<\/span>/s', $content, $page);
        }

        if (isset($page[1])) {
            $page_num = explode('/', $page[1]);
        }
        return isset($page_num[1]) ? $page_num[1] : false;
    }

    /**
     * 获取商品页面url
     *
     * @param int $pageNum 总页数
     * @return array
     */
    public function getGoodsPageUrl($pageNum)
    {
        $url = array();
        for ($i = 1; $i <= $pageNum; $i++) {
            $url[] = $this->shopUrl . $this->allGoodsUrl . '&pageNum=' . $i;
        }
        return $url;
    }


    /**
     * 循环采集商品详细信息
     *
     */
    protected function colGoodsInfo()
    {
        if (!empty($this->goodsList)) {
            $request = new Request();
            $content = $request->curlMulti($this->getGoodsInfoUrl());
            foreach ($content as $k => $info) {
                if (!empty($info)) {
                    $rs = @json_decode($info);
                    if (!empty($rs->ret[0]) && !empty($rs->data) && (strpos($rs->ret[0], 'SUCCESS') !== false)) {
                        $goodsInfo = $rs->data;
                        $goodsInfo->itemInfoModel->price = $this->goodsList[$goodsInfo->itemInfoModel->itemId]['price'];
                        $this->shopId = $goodsInfo->seller->shopId;
                        if ($k == 0) {
                            //保存店铺信息
                            $this->saveShop($goodsInfo->seller);
                        }
                        //保存商品信息
                        $this->saveGoods($goodsInfo);
                    } else {
                        $msg = isset($rs->ret[0]) ? $rs->ret[0] : '';
                        $msg .= isset($rs->data->redirectUrl) ? $rs->data->redirectUrl : '';
                        $this->log->error('采集失败！' . $msg);
                    }
                }
            }
        }
    }

    /**
     * 获取商品信息页面url列表
     *
     * @return array
     * @throws Exception
     */
    public function getGoodsInfoUrl()
    {
        $url = array();
        if (!empty($this->goodsList)) {
            foreach ($this->goodsList as $_goods) {
                $url[] = $this->goodsInfoUrl . $_goods['id'];
            }
        } else {
            throw new Exception('商品列表为空');
        }
        return $url;
    }

    /**
     * 保存店铺信息到数据库
     *
     * @param $data
     * @return mixed
     */
    private function saveShop($data)
    {
        if (isset($data->picUrl) && !empty($data->picUrl)) {
            //保存店铺logo到本地
            $savePath = $this->createDir($this->shopId, null, 'logo');
            $request = new Request();
            $img_rs = $request->curl($data->picUrl);
            $data->img = $this->saveImages($img_rs, $data->picUrl, $savePath);
        }
        $param = array(
            'userNumId' => $data->userNumId,
            'type' => $data->type,
            'nick' => $data->nick,
            'creditLevel' => $data->creditLevel,
            'goodRatePercentage' => $data->goodRatePercentage,
            'shopTitle' => $data->shopTitle,
            'shopId' => $data->shopId,
            'weitaoId' => $data->weitaoId,
            'fansCount' => $data->fansCount,
            'img' => $data->img,
            'picUrl' => $data->picUrl,
            'starts' => $data->starts,
            'createTime' => date('Y-m-d H:i:s'),
        );
        $this->db->where('shopId', $data->shopId)->delete('shop');
        $id = $this->db->insert('shop', $param);

        if ($id > 0) {
            $this->log->info($data->nick . " 店铺信息保存成功！");
            return true;
        } else {
            $this->log->warning(print_r($data, true) . "\r\n店铺保存失败！" . $this->db->getLastError());
            return false;
        }
    }

    /**
     * 保存商品信息到数据库
     *
     * @param $data
     * @return mixed
     */
    private function saveGoods($data)
    {
        $param = array(
            'shopId' => $this->shopId,
            'itemId' => $data->itemInfoModel->itemId,
            'title' => $data->itemInfoModel->title,
            'favcount' => $data->itemInfoModel->favcount,
            'location' => $data->itemInfoModel->location,
            'categoryId' => $data->itemInfoModel->categoryId,
            'price' => $data->itemInfoModel->price,
            'createTime' => date('Y-m-d H:i:s'),
        );
        $this->db->where('itemId', $data->itemInfoModel->itemId)->delete('goods');
        $goodsId = $this->db->insert('goods', $param);
        if ($goodsId > 0) {
            $this->log->info("商品基本信息保存成功！商品ID：" . $data->itemInfoModel->itemId);
            //采集banner图片地址并保存
            $this->saveGoodsBanner($data);
            //采集商品描述并保存
            $this->saveGoodsInfo($data);
        } else {
            $this->log->warning(print_r($param, true) . "\r\n商品保存失败！" . $this->db->getLastError());
            return false;
        }
        return true;
    }

    /**
     * 保存商品描述和其他信息
     *
     * @param $data
     * @return bool
     */
    private function saveGoodsInfo($data)
    {
        $this->db->where('itemId', $data->itemInfoModel->itemId)->delete('goods_info');
        //采集描述
        $fullDesc = $this->colGoodsDesc($data->descInfo->fullDescUrl);
        $param = array(
            'itemId' => $data->itemInfoModel->itemId,
            'skuProps' => !empty($data->skuModel->skuProps) ? json_encode($data->skuModel->skuProps) : '',
            'props' => !empty($data->props) ? json_encode($data->props) : '',
            'pcDescUrl' => $data->descInfo->pcDescUrl,
            'h5DescUrl' => $data->descInfo->h5DescUrl,
            'fullDesc' => $fullDesc,
            'createTime' => date('Y-m-d H:i:s'),
        );
        $this->db->where('itemId', $data->itemInfoModel->itemId)->delete('goods_info');
        $goodsId = $this->db->insert('goods_info', $param);
        if ($goodsId > 0) {
            $this->log->info("商品描述信息保存成功！商品ID：" . $data->itemInfoModel->itemId);
            return true;
        } else {
            $this->log->warning(print_r($param, true) . "\r\n商品信息保存失败！");
            return false;
        }
    }

    /**
     * 采集商品描述
     *
     * @param string $fullDescUrl 商品描述Url
     * @return mixed|string
     * @throws Exception
     */
    private function colGoodsDesc($fullDescUrl)
    {
        $fullDescHtml = '';
        $request = new Request();
        $rs = $request->curl($fullDescUrl);
        if (!empty($rs)) {
            $desc = json_decode($rs);
            if (!empty($desc)) {
                $desc = $desc->data->desc;
                //正则匹配出所有的图片
                preg_match('/<body>(.+?)<\/body>/s', $desc, $fullDesc);
                preg_match_all('/<img.+?src="(.+?)"/s', $fullDesc[1], $img);
                if (isset($img[1]) && !empty($img[1])) {
                    //采集商品描述图片地址并替换
                    $savePath = $this->createDir($this->shopId, $this->goodsId, 'desc');

                    //采集图片
                    $request = new Request();
                    $request->callback = array('dzer\coltaobao\lib\TbCollection', 'goodsImgCallback');
                    $content = $request->curlMulti($img[1]);

                    foreach ($content as $img) {
                        //保存图片
                        $imgPath = $this->saveImages($img['img_rs'], $img['img_url'], $savePath);
                        if (!empty($imgPath)) {
                            $replaceImg[] = $imgPath;
                            $searchImg[] = $img['img_url'];
                        }
                    }

                    if (isset($replaceImg) && isset($searchImg)) {
                        $fullDescHtml = str_replace($searchImg, $replaceImg, $fullDesc[1]);
                    }
                }
            }
        }
        return $fullDescHtml;
    }

    /**
     * 保存商品banner
     *
     * @param $data
     * @return bool
     */
    private function saveGoodsBanner($data)
    {
        $this->db->where('itemId', $data->itemInfoModel->itemId)->delete('goods_banner');
        $savePath = $this->createDir($this->shopId, $this->goodsId, 'banner');
        //采集banner图片地址并保存
        $request = new Request();
        $request->callback = array('dzer\coltaobao\lib\TbCollection', 'goodsImgCallback');
        $content = $request->curlMulti($data->itemInfoModel->picsPath);

        foreach ($content as $img) {
            $imgPath = $this->saveImages($img['img_rs'], $img['img_url'], $savePath);
            if (!empty($imgPath)) {
                $banner = array(
                    'itemId' => $data->itemInfoModel->itemId,
                    'picsPath' => $img['img_url'],
                    'path' => $imgPath,
                    'createTime' => date('Y-m-d H:i:s'),
                );
                $banner_id = $this->db->insert('goods_banner', $banner);
                if (!$banner_id) {
                    $this->log->warning(print_r($banner, true) . "\r\n商品banner信息保存失败！" . $this->db->getLastError());
                }
            }
        }
        return true;
    }

    /**
     * 保存指定图片到指定路径
     *
     * @param  string $result 图片资源
     * @param string $img_url 图片url
     * @param string $path 图片保存的目录
     * @return  string
     */
    protected function saveImages($result, $img_url, $path)
    {
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        preg_match('/\.jpg|\.png|\.gif|\.bmp|\.jpeg/s', strtolower($img_url), $extension);
        $extension = isset($extension[0]) ? $extension[0] : '.jpg';
        $name = time() . rand(000, 999) . $extension;
        $fopen = fopen($path . $name, "a");
        @fwrite($fopen, $result);
        @fclose($fopen);
        if (file_exists($path . $name))
            return $path . $name;
        else
            return null;
    }

    static function goodsImgCallback($img_rs, $img_url)
    {
        return array(
            'img_rs' => $img_rs,
            'img_url' => $img_url
        );
    }

    /**
     * 正则匹配商品列表
     *
     * @param string $content 采集的html
     * @return array
     */
    public static function regExGoods(&$content)
    {
        //字符集从gbk转化为utf-8
        $content = iconv('GBK', 'UTF-8//IGNORE', $content);
        //正则匹配出每个li
        preg_match_all('/<li class="item-wrap.+?>(.+?)<\/li>/s', $content, $goodsListLi);
        $goodsList = array();
        if (isset($goodsListLi[1]) && !empty($goodsListLi[1])) {
            foreach ($goodsListLi[1] as $_li) {
                //正则匹配出每个li里面的 商品id 缩略图 名称 价格
                //匹配缩略图
                //preg_match('/data-ks-lazyload="\/\/(.*?)"/s', $_li, $img);
                //匹配id和名称
                preg_match('/<p class="title">.+<a.+?id=([0-9]+).+>(.+)<\/a>.+<\/p>/s', $_li, $name);
                //匹配价格
                preg_match('/<p class="price">.+"value">(.+?)<.+<\/p>/s', $_li, $price);
                if (isset($name[1]) && !empty($name[1])) {
                    $goodsList[$name[1]] = array(
                        'id' => isset($name[1]) ? trim($name[1]) : '',
                        'name' => isset($name[2]) ? trim($name[2]) : '',
                        'price' => isset($price[1]) ? trim($price[1]) : '',
                    );
                } else {
                    continue;
                }
            }
        }
        return $goodsList;
    }


    function microTime()
    {
        list($u_sec, $sec) = explode(' ', microtime());
        return (floatval($u_sec) + floatval($sec));
    }
}