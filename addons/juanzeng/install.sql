DROP TABLE IF EXISTS `ea_shopitem`;
CREATE TABLE IF NOT EXISTS `ea_shopitem` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tid` int(11) NOT NULL COMMENT '上级',
  `uid` int(11) NOT NULL COMMENT '用户',
  `title` varchar(100) NOT NULL COMMENT '标题',
  `score` int(11) NOT NULL COMMENT '价格',
  `attach` int(11) NOT NULL COMMENT '附件源码',
  `pic` varchar(100) NOT NULL COMMENT '图片',
  `open` tinyint(1) NOT NULL DEFAULT '0' COMMENT '官方还是个人',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '插件模板',
  `issql` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否含有数据库',
  `isadmin` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否含有后台',
  `isconfig` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否需要配置',
  `settop` tinyint(1) NOT NULL DEFAULT '0' COMMENT '顶置',
  `praise` varchar(11) NOT NULL DEFAULT '0' COMMENT '收藏',
  `view` varchar(11) NOT NULL DEFAULT '0' COMMENT '浏览量',
  `time` varchar(11) NOT NULL COMMENT '时间',
  `reply` varchar(11) NOT NULL DEFAULT '0' COMMENT '回复',
  `content` text NOT NULL COMMENT '内容',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;