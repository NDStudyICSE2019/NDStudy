CREATE TABLE IF NOT EXISTS `__CL_COURSE__tool_list` (
    `id` int(11) NOT NULL auto_increment,
    `tool_id` int(10) unsigned default NULL,
    `rank` int(10) unsigned NOT NULL,
    `visibility` tinyint(4) default 0,
    `script_url` varchar(255) default NULL,
    `script_name` varchar(255) default NULL,
    `addedTool` ENUM('YES','NO') DEFAULT 'YES',
    `activated` ENUM('true','false') NOT NULL DEFAULT 'true',
    `installed` ENUM('true','false') NOT NULL DEFAULT 'true',
PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__course_properties` (
    `id` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL default '',
    `value` varchar(255) default NULL,
    `category` varchar(255) default NULL,
    PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__tool_intro` (
    `id` int(11) NOT NULL auto_increment,
    `tool_id` int(11) NOT NULL default '0',
    `title` varchar(255) default NULL,
    `display_date` datetime default NULL,
    `content` text,
    `rank` int(11) default '1',
    `visibility` enum('SHOW','HIDE') NOT NULL default 'SHOW',
PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__userinfo_content` (
   `id` int(10) unsigned NOT NULL auto_increment,
   `user_id` mediumint(8) unsigned NOT NULL default '0',
   `def_id` int(10) unsigned NOT NULL default '0',
   `ed_ip` varchar(39) default NULL,
   `ed_date` datetime default NULL,
   `content` text,
   PRIMARY KEY  (`id`),
   KEY `user_id` (`user_id`)
) ENGINE=MyISAM COMMENT='content of users information';

CREATE TABLE IF NOT EXISTS `__CL_COURSE__userinfo_def` (
   `id` int(10) unsigned NOT NULL auto_increment,
   `title` varchar(80) NOT NULL default '',
   `comment` varchar(160) default NULL,
   `nbLine` int(10) unsigned NOT NULL default '5',
   `rank` tinyint(3) unsigned NOT NULL default '0',
   PRIMARY KEY  (`id`)
) ENGINE=MyISAM COMMENT='categories definition for user information of a course';

CREATE TABLE IF NOT EXISTS `__CL_COURSE__group_team` (
    id int(11) NOT NULL auto_increment,
    name varchar(100) default NULL,
    description text,
    tutor int(11) default NULL,
    maxStudent int(11) NULL default '0',
    secretDirectory varchar(30) NOT NULL default '0',
PRIMARY KEY  (id)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__group_rel_team_user` (
    id int(11) NOT NULL auto_increment,
    user int(11) NOT NULL default '0',
    team int(11) NOT NULL default '0',
    status int(11) NOT NULL default '0',
    role varchar(50) NOT NULL default '',
PRIMARY KEY  (id)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__lnk_links` (
    `id` int(11) NOT NULL auto_increment,
    `src_id` int(11) NOT NULL default '0',
    `dest_id` int(11) NOT NULL default '0',
    `creation_time` timestamp NOT NULL,
PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__lnk_resources` (
    `id` int(11) NOT NULL auto_increment,
    `crl` text NOT NULL,
    `title` text NOT NULL,
PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__tracking_event` (
  `id` int(11) NOT NULL auto_increment,
  `tool_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `type` varchar(60) NOT NULL DEFAULT '',
  `data` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `tool` (`tool_id`),
  KEY `user` (`user_id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__document` (
    `id` int(4) NOT NULL auto_increment,
    `path` varchar(255) NOT NULL,
    `visibility` char(1) DEFAULT 'v' NOT NULL,
    `comment` varchar(255),
    PRIMARY KEY (id)
) ENGINE=MyISAM;