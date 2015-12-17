<?php

/**
 * 淘宝采集 入口文件
 *
 * @version 1.0
 * @author dx <358654744@qq.com>
 * @date 2015-11-04
 */
//根目录
defined('TB_ROOT') || define('TB_ROOT', str_replace('\\', DIRECTORY_SEPARATOR, dirname(__FILE__)));
//是否处于开发模式(开发模式将记录接口请求参数，错误记录等)
defined('TB_DEBUG') || define('TB_DEBUG', true);
//资源保存目录
defined('TB_RESOURCE_PATH') || define('TB_RESOURCE_PATH', './Public/Uploads/Coltaobao');
//日志保存路径
defined('TB_LOG_DIR') || define('TB_LOG_DIR', TB_ROOT . '/tmp_log/');
//引入自动加载类
include(TB_ROOT . '/basic/TbAutoloader.php');