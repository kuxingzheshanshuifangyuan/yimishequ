DROP TABLE IF EXISTS `ea_attach`;
CREATE TABLE `ea_attach`(
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '文件ID',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '原始文件名',
  `savename` varchar(255) NOT NULL DEFAULT '' COMMENT '保存名称',
  `savepath` varchar(255) NOT NULL DEFAULT '' COMMENT '文件保存路径',
  `ext` char(5) NOT NULL DEFAULT '' COMMENT '文件后缀',
  `mime` char(40) NOT NULL DEFAULT '' COMMENT '文件mime类型',
  `size` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小',
  `score` int(10) NOT NULL DEFAULT '0' COMMENT '积分',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所有人',
  `md5` varchar(255) NOT NULL DEFAULT '' COMMENT '文件md5',
  `sha1` varchar(255) NOT NULL DEFAULT '' COMMENT '文件 sha1编码',
  `create_time` int(10) unsigned NOT NULL COMMENT '上传时间',
  `download` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='附件上传表' AUTO_INCREMENT=1 ;