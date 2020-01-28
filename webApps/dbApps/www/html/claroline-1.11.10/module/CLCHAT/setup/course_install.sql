# $Id: course_install.sql 355 2007-11-15 10:25:27Z mlaurent $

CREATE TABLE IF NOT EXISTS `__CL_COURSE__chat` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL default '0',
  `group_id` int(11) NULL default NULL,  
  `message` text,
  `post_time` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
) ENGINE = MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__chat_users` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL default '0',
  `group_id` int(11) NULL default NULL,
  `last_action` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
) ENGINE = MyISAM;