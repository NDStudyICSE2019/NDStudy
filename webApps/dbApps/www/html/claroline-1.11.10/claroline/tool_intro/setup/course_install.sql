CREATE TABLE IF NOT EXISTS `__CL_COURSE__tool_intro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tool_id` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) DEFAULT NULL,
  `display_date` datetime DEFAULT NULL,
  `content` text,
  `rank` int(11) DEFAULT '1',
  `visibility` enum('SHOW','HIDE') NOT NULL DEFAULT 'SHOW',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;

