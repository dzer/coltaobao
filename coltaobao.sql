
CREATE TABLE `tb_coltaobao_goods` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `shopId` int(10) unsigned DEFAULT NULL COMMENT '店铺ID',
  `itemId` bigint(20) unsigned DEFAULT NULL COMMENT '商品ID',
  `title` varchar(100) DEFAULT NULL,
  `favcount` int(11) DEFAULT NULL COMMENT '收藏',
  `location` varchar(30) DEFAULT NULL COMMENT '地址',
  `categoryId` int(11) DEFAULT NULL COMMENT '分类ID',
  `price` decimal(10,2) DEFAULT NULL COMMENT '价格',
  `createdate` datetime DEFAULT NULL,
  `modifydate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `itemId` (`itemId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for tb_coltaobao_goods_banner
-- ----------------------------
CREATE TABLE `tb_coltaobao_goods_banner` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `itemId` bigint(20) unsigned DEFAULT NULL COMMENT '商品ID',
  `picsPath` varchar(200) DEFAULT NULL COMMENT '远程原图url',
  `path` varchar(200) DEFAULT NULL COMMENT '本地图片地址',
  `createdate` datetime DEFAULT NULL,
  `modifydate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for tb_coltaobao_goods_info
-- ----------------------------
CREATE TABLE `tb_coltaobao_goods_info` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `itemId` bigint(20) unsigned DEFAULT NULL COMMENT '商品ID',
  `skuProps` varchar(5000) DEFAULT NULL COMMENT '颜色分类（json）',
  `props` varchar(5000) DEFAULT NULL COMMENT '特征描述（json）',
  `pcDescUrl` varchar(200) DEFAULT NULL COMMENT 'Pc端商品描述Url',
  `h5DescUrl` varchar(200) DEFAULT NULL COMMENT 'h5端商品描述Url',
  `fullDesc` text COMMENT '完整描述',
  `createdate` datetime DEFAULT NULL,
  `modifydate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `itemId` (`itemId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for tb_coltaobao_shop
-- ----------------------------
CREATE TABLE `tb_coltaobao_shop` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userNumId` int(10) unsigned DEFAULT NULL COMMENT '用户ID',
  `type` char(1) DEFAULT NULL COMMENT '用户类型',
  `nick` varchar(50) DEFAULT NULL COMMENT '昵称',
  `creditLevel` smallint(6) DEFAULT NULL COMMENT '信用等级',
  `goodRatePercentage` decimal(6,2) DEFAULT NULL COMMENT '好评率',
  `shopTitle` varchar(50) DEFAULT NULL COMMENT '店铺名称',
  `shopId` int(10) unsigned DEFAULT NULL COMMENT '商店id',
  `weitaoId` varchar(15) DEFAULT NULL COMMENT '微淘ID',
  `fansCount` int(11) DEFAULT NULL COMMENT '粉丝总数',
  `img` varchar(100) DEFAULT NULL COMMENT '本地图片',
  `picUrl` varchar(100) DEFAULT NULL COMMENT '店铺logo',
  `starts` datetime DEFAULT NULL COMMENT '店铺创建时间',
  `goodsSum` smallint(6) DEFAULT NULL COMMENT '商品总数',
  `createdate` datetime DEFAULT NULL,
  `modifydate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shopId` (`shopId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
