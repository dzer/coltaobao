<?php
/**
 * 配置文件
 *
 * @author  dzer <d20053140@gmail.com>
 * @since 2.0
 */
return array(
    //时区
    'timeZone' => 'Asia/Shanghai',
    //资源保存路劲
    'resource' => './resource/coltaobao',
    //日志保存路径
    'log' => './tmp_log/coltaobao',
    //批量采集最大链接数
    'maxRequestNum' => 50,
    //数据库配置信息
    'db' => array(
        'host' => '127.0.0.1',
        'username' => 'root',
        'password' => 'root',
        'db' => 'coltaobao',
        'port' => 3306,
        'prefix' => 'coltaobao_',
        'charset' => 'utf8'
    ),
);