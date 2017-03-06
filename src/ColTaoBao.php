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
    public static function collect($shopUrl) {
        Enterance::run(__DIR__);
        $collection = new TbCollection($shopUrl);
        return $collection->colMain();
    }
}