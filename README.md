# ColTaobao 淘宝店铺采集

------

ColTaobao 是方便快速采集淘宝（天猫）店铺的所有商品，包括店铺信息、商品banner、所有商品信息、商品描述、商品图片等。

示例：[http://coltaobao.pupapi.com](http://coltaobao.pupapi.com "http://coltaobao.pupapi.com")

![ColTaobao](https://pupapi.com/site/img/logo4_200x200.png)

- 批量采集
- 符合psr-4
- 数据保存到数据库
- 图片保存到本地
- 商品描述中图片自动替换成本地图片
- 采集日志记录

### 依赖

- PHP 5.3+
- MYSQL 5.1+
- MYSQLI PHP Extension
- CURL PHP Extension

### 1. 安装

```
composer require dzer/coltaobao

```
或者
```
git clone https://github.com/dzer/coltaobao.git
```
### 2. 配置
导入MySQL表 ```config/coltaobao.sql```

配置文件 ```config/config.php```
```
return array(
    //时区
    'timeZone' => 'Asia/Shanghai',
    //资源保存路劲
    'resource' => './resource/coltaobao',
    //日志保存路径
    'log' => './tmp_log/coltaobao',
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
```

### 3. 运行
```
use dzer\coltaobao\ColTaoBao;

ColTaoBao::collect('https://hhtmy.tmall.com/');//传入淘宝或天猫店URL地址
```
------

