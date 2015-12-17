##淘宝（天猫）商品数据批量采集

以扩展形式编写，可以嵌入任何框架，遵循PSR-4。
输入淘宝货天猫店铺地址，自动采集店铺下所有的商品，并保存到数据，同时采集商品所有图片到本地。

###一、引入入口文件


	require './col.php';    
    $tb = new TbCollection('https://hhtmy.tmall.com/');
    $tb->colMain();	//调用方法自动采集保存

###二、入口文件可以配置日志，图片保存路径等


    /**
     * 淘宝采集 入口文件
     *
     * @version 1.0
     * @author dzer <d20053140@gmail.com>
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