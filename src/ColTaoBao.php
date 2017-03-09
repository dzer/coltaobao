<?php
namespace dzer\coltaobao;

use dzer\coltaobao\basic\Enterance;
use dzer\coltaobao\lib\TbCollection;

class ColTaoBao{
    /**
     * collect
     *
     * @param $shopUrl
     */
    public static function collect($shopUrl, $uid) {
        Enterance::run(__DIR__);
        $collection = new TbCollection($shopUrl, $uid);
        return $collection->colMain();
    }
}