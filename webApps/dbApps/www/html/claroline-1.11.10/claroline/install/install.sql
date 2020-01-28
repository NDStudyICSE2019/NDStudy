# Claroline Database version 1.11

# MAIN TABLES

CREATE TABLE IF NOT EXISTS `__CL_MAIN__cours` (
  `cours_id` INT(11) NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(40) DEFAULT NULL,
  `isSourceCourse` TINYINT(4) NOT NULL DEFAULT '0',
  `sourceCourseId` INT(11) DEFAULT NULL,
  `administrativeNumber` VARCHAR(40) DEFAULT NULL,
  `directory` VARCHAR(20) DEFAULT NULL,
  `dbName` VARCHAR(40) DEFAULT NULL,
  `language` VARCHAR(15) DEFAULT NULL,
  `intitule` VARCHAR(250) DEFAULT NULL,
  `titulaires` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `extLinkName` VARCHAR(30) DEFAULT NULL,
  `extLinkUrl` VARCHAR(180) DEFAULT NULL,
  `visibility` ENUM ('visible','invisible') DEFAULT 'visible' NOT NULL,
  `access`     ENUM ('public','private','platform') DEFAULT 'public' NOT NULL,
  `registration` ENUM ('open','close','validation') DEFAULT 'open' NOT NULL,
  `registrationKey` VARCHAR(255) DEFAULT NULL,
  `diskQuota` INT(10) UNSIGNED DEFAULT NULL,
  `versionDb` VARCHAR(250) NOT NULL DEFAULT 'NEVER SET',
  `versionClaro` VARCHAR(250) NOT NULL DEFAULT 'NEVER SET',
  `lastVisit` DATETIME DEFAULT NULL,
  `lastEdit` DATETIME DEFAULT NULL,
  `creationDate` DATETIME DEFAULT NULL,
  `expirationDate` DATETIME DEFAULT NULL,
  `defaultProfileId` INT(11) NOT NULL,
  `status` enum('enable','pending','disable','trash','date') NOT NULL DEFAULT 'enable',
  `userLimit` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cours_id`),
  KEY `administrativeNumber` (`administrativeNumber`)
) ENGINE=MyISAM COMMENT='data of courses';

CREATE TABLE IF NOT EXISTS `__CL_MAIN__rel_course_portlet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `courseId` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  `label` varchar(255) NOT NULL,
  `visible` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `courseId` (`courseId`,`label`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__coursehomepage_portlet` (
  `label` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`label`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__user` (
  `user_id` INT(11)  UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(60) DEFAULT NULL,
  `prenom` VARCHAR(60) DEFAULT NULL,
  `username` VARCHAR(255) DEFAULT 'empty',
  `password` VARCHAR(50) DEFAULT 'empty',
  `language` VARCHAR(15) DEFAULT NULL,
  `authSource` VARCHAR(50) DEFAULT 'claroline',
  `email` VARCHAR(255) DEFAULT NULL,
  `officialCode`  VARCHAR(255) DEFAULT NULL,
  `officialEmail` VARCHAR(255) DEFAULT NULL,
  `phoneNumber` VARCHAR(30) DEFAULT NULL,
  `pictureUri` VARCHAR(250) DEFAULT NULL,
  `creatorId` INT(11)  UNSIGNED DEFAULT NULL,
  `isPlatformAdmin` TINYINT(4) DEFAULT 0,
  `isCourseCreator` TINYINT(4) DEFAULT 0,
   PRIMARY KEY  (`user_id`),
  KEY `loginpass` (`username`,`password`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__rel_course_user` (
  `code_cours` VARCHAR(40) NOT NULL DEFAULT '0',
  `user_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `profile_id` INT(11) NOT NULL,
  `role` VARCHAR(60) DEFAULT NULL,
  `team` INT(11) NOT NULL DEFAULT '0',
  `tutor` INT(11) NOT NULL DEFAULT '0',
  `count_user_enrol` INT(11) NOT NULL DEFAULT '0',
  `count_class_enrol` INT(11) NOT NULL DEFAULT '0',
  `isPending` tinyINT(4) NOT NULL DEFAULT 0,
  `isCourseManager` tinyINT(4) NOT NULL DEFAULT 0,
  `enrollment_date` DATETIME NULL,
   PRIMARY KEY  (`code_cours`,`user_id`),
  KEY `isCourseManager` (`isCourseManager`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `code` varchar(12) NOT NULL DEFAULT '',
  `idParent` int(11) DEFAULT '0',
  `rank` int(11) NOT NULL DEFAULT '0',
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `canHaveCoursesChild` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__rel_course_category` (
  `courseId` int(11) NOT NULL,
  `categoryId` int(11) NOT NULL,
  `rootCourse` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`courseId`,`categoryId`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__course_tool` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `claro_label` VARCHAR(8) NOT NULL DEFAULT '',
  `script_url` VARCHAR(255) NOT NULL DEFAULT '',
  `icon` VARCHAR(255) DEFAULT NULL,
  `def_access` ENUM('ALL','COURSE_MEMBER','GROUP_MEMBER','GROUP_TUTOR','COURSE_ADMIN','PLATFORM_ADMIN') NOT NULL DEFAULT 'ALL',
  `def_rank` INT(10) UNSIGNED DEFAULT NULL,
  `add_in_course` ENUM('MANUAL','AUTOMATIC') NOT NULL DEFAULT 'AUTOMATIC',
  `access_manager` ENUM('PLATFORM_ADMIN','COURSE_ADMIN') NOT NULL DEFAULT 'COURSE_ADMIN',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `claro_label` (`claro_label`)
) ENGINE=MyISAM COMMENT='based definiton of the claroline tool used in each course';

CREATE TABLE IF NOT EXISTS `__CL_MAIN__class` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  `class_parent_id` INT(11) DEFAULT NULL,
  `class_level` INT(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM COMMENT='classe_id, name, classe_parent_id, classe_level';

CREATE TABLE IF NOT EXISTS `__CL_MAIN__rel_class_user` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL DEFAULT '0',
  `class_id` INT(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`id`),
  KEY `user_id` (`user_id`),
  KEY `class_id` (`class_id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__rel_course_class` (
    `courseId` VARCHAR(40) NOT NULL,
    `classId` INT(11) NOT NULL DEFAULT '0',
    PRIMARY KEY  (`courseId`,`classId`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__config_file` (
  `config_code` VARCHAR(30) NOT NULL DEFAULT '',
  `config_hash` VARCHAR(40) NOT NULL DEFAULT '',
  PRIMARY KEY  (`config_code` )
) ENGINE=MyISAM  AVG_ROW_LENGTH=48;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__sso` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `cookie` VARCHAR(255) NOT NULL DEFAULT '',
  `rec_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_id` INT(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__notify` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `course_code` VARCHAR(40) NOT NULL DEFAULT '0',
  `tool_id` INT(11) NOT NULL DEFAULT '0',
  `ressource_id` VARCHAR(255) NOT NULL DEFAULT '0',
  `group_id` INT(11) NOT NULL DEFAULT '0',
  `user_id` INT(11) NOT NULL DEFAULT '0',
  `date` DATETIME DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  KEY `course_id` (`course_code`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__upgrade_status` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `cid` VARCHAR( 40 ) NOT NULL ,
    `claro_label` VARCHAR( 8 ) ,
    `status` TINYINT NOT NULL ,
    PRIMARY KEY ( `id` )
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__module` (
  `id`         INT(11)    UNSIGNED             NOT NULL AUTO_INCREMENT,
  `label`      VARCHAR(8)                          NOT NULL DEFAULT '',
  `name`       VARCHAR(100)                        NOT NULL DEFAULT '',
  `activation` ENUM('activated','desactivated') NOT NULL DEFAULT 'desactivated',
  `type`       VARCHAR(10)                      NOT NULL DEFAULT 'applet',
  `script_url` char(255)                        NOT NULL DEFAULT 'entry.php',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__module_info` (
  id             SMALLINT     NOT NULL AUTO_INCREMENT,
  module_id      SMALLINT     NOT NULL DEFAULT '0',
  version        VARCHAR(10)  NOT NULL DEFAULT '',
  author         VARCHAR(50)  DEFAULT NULL,
  author_email   VARCHAR(100) DEFAULT NULL,
  author_website VARCHAR(255) DEFAULT NULL,
  description    VARCHAR(255) DEFAULT NULL,
  website        VARCHAR(255) DEFAULT NULL,
  license        VARCHAR(50)  DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__module_contexts` (
  module_id INTEGER UNSIGNED NOT NULL,
  context VARCHAR(60) NOT NULL DEFAULT 'course',
  PRIMARY KEY(`module_id`,`context`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__dock` (
  id        SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  module_id SMALLINT UNSIGNED NOT NULL DEFAULT '0',
  name      VARCHAR(50) NOT NULL DEFAULT '',
  rank      TINYINT  UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY  (id)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__right_profile` (
  `profile_id` INT(11) NOT NULL AUTO_INCREMENT,
  `type` ENUM('COURSE','PLATFORM') NOT NULL DEFAULT 'COURSE',
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `label` VARCHAR(50) NOT NULL DEFAULT '',
  `description` VARCHAR(255) DEFAULT '',
  `courseManager` TINYINT(4) DEFAULT '0',
  `mailingList` TINYINT(4) DEFAULT '0',
  `userlistPublic` TINYINT(4) DEFAULT '0',
  `groupTutor` TINYINT(4) DEFAULT '0',
  `locked` TINYINT(4) DEFAULT '0',
  `required` TINYINT(4) DEFAULT '0',
  PRIMARY KEY  (`profile_id`),
  KEY `type` (`type`)
)ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__right_action` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `description` VARCHAR(255) DEFAULT '',
  `tool_id` INT(11) DEFAULT NULL,
  `rank` INT(11) DEFAULT '0',
  `type` ENUM('COURSE','PLATFORM') NOT NULL DEFAULT 'COURSE',
  PRIMARY KEY  (`id`),
  KEY `tool_id` (`tool_id`),
  KEY `type` (`type`)
)ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__right_rel_profile_action` (
  `profile_id` INT(11) NOT NULL,
  `action_id` INT(11) NOT NULL,
  `courseId`  VARCHAR(40) NOT NULL DEFAULT '',
  `value` TINYINT(4) DEFAULT '0',
  PRIMARY KEY  (`profile_id`,`action_id`,`courseId`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__property_definition` (
  `propertyId` VARCHAR(50) NOT NULL DEFAULT '',
  `contextScope` VARCHAR(10) NOT NULL DEFAULT '',
  `label` VARCHAR(50) NOT NULL DEFAULT '',
  `type` VARCHAR(10) NOT NULL DEFAULT '',
  `defaultValue` VARCHAR(255) NOT NULL DEFAULT '',
  `description` TEXT NOT NULL,
  `required` TINYINT(1) NOT NULL DEFAULT '0',
  `rank` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `acceptedValue` TEXT NOT NULL,
  PRIMARY KEY  (`contextScope`,`propertyId`),
  KEY `rank` (`rank`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS  `__CL_MAIN__user_property` (
  `userId`        INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `propertyId`    VARCHAR(255) NOT NULL DEFAULT '',
  `propertyValue` VARCHAR(255) NOT NULL DEFAULT '',
  `scope`         VARCHAR(45) NOT NULL DEFAULT '',
  PRIMARY KEY  (`scope`,`propertyId`,`userId`)
) ENGINE=MyISAM;

# INTERNAL MESSAGING SYSTEM

CREATE TABLE IF NOT EXISTS `__CL_MAIN__im_message` (
  `message_id` int(10) unsigned NOT NULL auto_increment,
  `sender` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `send_time` datetime NOT NULL default '0000-00-00 00:00:00',
  `course` varchar(40) default NULL,
  `group` int(11) default NULL,
  `tools` char(8) default NULL,
  PRIMARY KEY  (`message_id`)
) ENGINE=MyISAM ;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__im_message_status` (
  `user_id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `is_read` tinyint(4) NOT NULL default '0',
  `is_deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`user_id`,`message_id`)
) ENGINE=MyISAM ;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__im_recipient` (
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sent_to` enum('toUser','toGroup','toCourse','toAll') NOT NULL,
  PRIMARY KEY  (`message_id`,`user_id`)
) ENGINE=MyISAM ;

# DESKTOP
CREATE TABLE IF NOT EXISTS `__CL_MAIN__desktop_portlet` (
  `label` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `rank` int(11) NOT NULL,
  `visibility` ENUM ('visible','invisible') DEFAULT 'visible' NOT NULL,
  `activated` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY  (`label`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_MAIN__desktop_portlet_data` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `idUser` int(11) NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

# STATS TABLE
CREATE TABLE IF NOT EXISTS `__CL_MAIN__tracking_event` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `course_code` VARCHAR(40) NULL DEFAULT NULL,
  `tool_id` INT(11) NULL DEFAULT NULL,
  `user_id` INT(11) NULL DEFAULT NULL,
  `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `type` VARCHAR(60) NOT NULL DEFAULT '',
  `data` TEXT NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `course_id` (`course_code`),
  KEY `user_tracking` (`user_id`)
) ENGINE=MyISAM;

# LOG TABLE
CREATE TABLE IF NOT EXISTS `__CL_MAIN__log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `course_code` VARCHAR(40) NULL DEFAULT NULL,
  `tool_id` INT(11) NULL DEFAULT NULL,
  `user_id` INT(11) NULL DEFAULT NULL,
  `ip` VARCHAR(15) NULL DEFAULT NULL,
  `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `type` VARCHAR(60) NOT NULL DEFAULT '',
  `data` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `course_id` (`course_code`),
  KEY `user_log` (`user_id`)
) ENGINE=MyISAM;

# EVENT-RESOURCE TABLE
CREATE TABLE IF NOT EXISTS `__CL_MAIN__event_resource` (
  `event_id` INTEGER NOT NULL,
  `resource_id` INTEGER NOT NULL,
  `tool_id` INTEGER NOT NULL,
  `course_code` VARCHAR(40) NOT NULL,
  PRIMARY KEY (`event_id`, `resource_id`, `tool_id`, `course_code`),
  UNIQUE KEY (`event_id`, `course_code`)
) ENGINE=MyISAM;

# INSERT COMMANDS
INSERT INTO `__CL_MAIN__category` 
(`name`, `code`, `idParent`, `rank`, `visible`, `canHaveCoursesChild`) 
VALUES
('Root', 'ROOT', NULL, 0, 0, 0), 
('Sciences', 'SC', 0, 1, 1, 1), 
('Economics', 'ECO', 0, 2, 1, 1), 
('Humanities', 'HUMA', 0, 3, 1, 1);

UPDATE `__CL_MAIN__category` 
SET `id` = 0 
WHERE `code` = 'ROOT';

INSERT IGNORE INTO `__CL_MAIN__coursehomepage_portlet` 
(`label`, `name`) 
VALUES
('CLTI',    'Headlines'),
('CLCAL',   'Calendar'),
('CLANN',   'Announcements');