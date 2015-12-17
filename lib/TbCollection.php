<?php

/**
 * 采集类
 * 采集店铺所有商品
 *
 * @author  dx
 * @date 2015/12/9
 * @version 1.0
 */
class TbCollection extends TbBase
{
    /**
     * 采集主要流程
     */
    public function colMain()
    {
        //第一步：通过店铺地址采集所有的店铺下所有商品的基本信息并保存（最好保存到缓存比如redis）
        $this->colGoodsList();
        //第二步：通过商品id 循环采集单个商品详细信息
        $this->colGoodsInfo();
    }

    /**
     * 采集商品基本信息列表
     *
     * @return array
     * @throws Exception
     */
    protected function colGoodsList()
    {
        $request = new TbRequest();
        //首先采集店铺下所有的商品基本信息
        for ($pageNum = 1; $pageNum < 50; $pageNum++) {
            //获取html
            $content = $request->get($this->shopUrl . $this->allGoodsUrl . '&pageNum=' . $pageNum);
            if (empty($content)) {
                throw new Exception('采集失败！请重试！');
            }
            //字符集从gbk转化为utf-8
            $content = iconv('GBK', 'UTF-8', $content);
            //正则匹配出商品基本信息
            $goodsList = $this->regExGoods($content);
            if (!empty($goodsList)) {
                $this->goodsList = array_merge($this->goodsList, $goodsList);
                //保存到缓存或数据库
                $this->saveGoodsList($goodsList);
            } else {
                //如果为空就说明采集完所有页 跳出循环
                break;
            }
        }
        //进行下一步操作：采集单个商品
        return $this->goodsList;
    }

    /**
     * 循环采集商品详细信息
     *
     */
    protected function colGoodsInfo()
    {
        if (!empty($this->goodsList)) {
            $request = new TbRequest();
            foreach ($this->goodsList as $k => $goods) {
                try {
                    $info = $request->curl($this->goodsInfoUrl . $goods['id']);
                } catch (Exception $e) {
                    //采集如出错就标记状态（暂时只记录日志）
                    TbLog::write(date('Y-m-d H:i:s') . $goods['id'] . " 采集失败！\r\n");
                    continue;
                }
                $this->goodsId = $goods['id'];
                //保存到数据库
                if (!empty($info)) {
                    $rs = json_decode($info);
                    if (!empty($rs)) {
                        $goodsInfo = $rs->data;
                        $goodsInfo->itemInfoModel->price = $goods['price'];
                        $this->shopId = $goodsInfo->seller->shopId;
                        if ($k == 0) {
                            //保存店铺信息
                            $this->saveShop($goodsInfo->seller);
                        }
                        //保存商品信息
                        $this->saveGoods($goodsInfo);
                    }
                }
            }
        }
    }

    /**
     * 保存店铺信息到数据库
     *
     * @param $data
     * @return mixed
     */
    private function saveShop($data)
    {
        $shopModle = D('ColtaobaoShop');
        if (isset($data->picUrl) && !empty($data->picUrl)) {
            //保存店铺logo到本地
            $savePath = $this->createDir($this->shopId, null, 'logo');
            $data->img = $this->saveImage($data->picUrl, $savePath);
        }

        $shopModle->create($data);
        $id = $shopModle->add('', array(), true);
        if ($id > 0) {
            return true;
        } else {
            TbLog::write(date('Y-m-d H:i:s') . " :\r\n" . print_r($data, true) . " 采集失败！\r\n");
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
        $goodsModle = D('ColtaobaoGoods');
        $data->itemInfoModel->shopId = $this->shopId;
        $goodsModle->create($data->itemInfoModel);
        $goodsId = $goodsModle->add('', array(), true);
        if ($goodsId > 0) {
            //采集banner图片地址并保存
            $this->saveGoodsBanner($data);
            //采集商品描述并保存
            $this->saveGoodsInfo($data);
        }
        echo $data->itemInfoModel->title . '--采集成功！<br/>';
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
        $goodsInfoModle = D('ColtaobaoGoodsInfo');
        //采集描述
        $fullDesc = $this->colGoodsDesc($data->descInfo->fullDescUrl);
        $info = array(
            'itemId' => $data->itemInfoModel->itemId,
            'skuProps' => json_encode($data->skuModel->skuProps),
            'props' => json_encode($data->props),
            'pcDescUrl' => $data->descInfo->pcDescUrl,
            'h5DescUrl' => $data->descInfo->h5DescUrl,
            'fullDesc' => $fullDesc,
        );
        $goodsInfoModle->create($info);
        $goodsInfoModle->where('itemId =' . $data->itemInfoModel->itemId)->delete();
        $goodsInfoModle->add();
        return true;
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
        $request = new TbRequest();
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
                    foreach ($img[1] as $_pathUrl) {
                        $imgPath = $this->saveImage($_pathUrl, $savePath);
                        if (!empty($imgPath)) {
                            $replaceImg[] = $imgPath;
                        }
                    }
                    if (isset($replaceImg)) {
                        $fullDescHtml = str_replace($img[1], $replaceImg, $fullDesc[1]);
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
        $goodsBannerModle = D('ColtaobaoGoodsBanner');
        $goodsBannerModle->where('itemId =' . $data->itemInfoModel->itemId)->delete();
        $savePath = $this->createDir($this->shopId, $this->goodsId, 'banner');
        //采集banner图片地址并保存
        foreach ($data->itemInfoModel->picsPath as $_pathUrl) {
            $imgPath = $this->saveImage($_pathUrl, $savePath);
            if (!empty($imgPath)) {
                $banner = array(
                    'itemId' => $data->itemInfoModel->itemId,
                    'picsPath' => $_pathUrl,
                    'path' => $imgPath,
                );
                $goodsBannerModle->create($banner);
                $goodsBannerModle->add();
            }
        }
        return true;
    }

    /**
     * 正则匹配商品列表
     *
     * @param string $content 采集的html
     * @return array
     */
    protected function regExGoods($content)
    {
        //正则匹配出每个li
        preg_match_all('/<li class="item-wrap.+?>(.+?)<\/li>/s', $content, $goodsListLi);
        $goodsList = array();
        if (isset($goodsListLi[1]) && !empty($goodsListLi[1])) {
            foreach ($goodsListLi[1] as $_li) {
                //正则匹配出每个li里面的 商品id 缩略图 名称 价格
                //匹配缩略图
                //preg_match('/data-ks-lazyload="\/\/(.*?)"/s', $_li, $img);
                //匹配id和名称
                preg_match('/<p class="title">.+<a.+id=([0-9]+).+>(.+)<\/a>.+<\/p>/s', $_li, $name);
                //匹配价格
                preg_match('/<p class="price">.+"value">(.+?)<.+<\/p>/s', $_li, $price);
                if (isset($name[1]) && !empty($name[1])) {
                    $goodsList[] = array(
                        'id' => isset($name[1]) ? $name[1] : '',
                        'name' => isset($name[2]) ? $name[2] : '',
                        'price' => isset($price[1]) ? $price[1] : '',
                    );
                } else {
                    continue;
                }
            }
        }
        return $goodsList;
    }

    /**
     * 保存商品列表到数据库
     *
     * @param $goodsList
     * @return bool
     */
    protected function saveGoodsList($goodsList)
    {
        if (!empty($goodsList)) {

            return true;
        } else {
            return false;
        }
    }
}