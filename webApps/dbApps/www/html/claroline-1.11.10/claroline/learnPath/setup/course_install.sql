    CREATE TABLE IF NOT EXISTS `__CL_COURSE__lp_module` (
        `module_id` int(11) NOT NULL auto_increment,
        `name` varchar(255) NOT NULL default '',
        `comment` text NOT NULL,
        `accessibility` enum('PRIVATE','PUBLIC') NOT NULL default 'PRIVATE',
        `startAsset_id` int(11) NOT NULL default '0',
        `contentType` enum('CLARODOC','DOCUMENT','EXERCISE','HANDMADE','SCORM','LABEL') NOT NULL,
        `launch_data` text NOT NULL,
        PRIMARY KEY  (`module_id`)
    ) ENGINE=MyISAM  COMMENT='List of available modules used in learning paths';
    
    CREATE TABLE IF NOT EXISTS `__CL_COURSE__lp_learnPath` (
        `learnPath_id` int(11) NOT NULL auto_increment,
        `name` varchar(255) NOT NULL default '',
        `comment` text NOT NULL,
        `lock` enum('OPEN','CLOSE') NOT NULL default 'OPEN',
        `visibility` enum('HIDE','SHOW') NOT NULL default 'SHOW',
        `rank` int(11) NOT NULL default '0',
        PRIMARY KEY  (`learnPath_id`),
        UNIQUE KEY rank (`rank`)
    ) ENGINE=MyISAM  COMMENT='List of learning Paths';
    
    CREATE TABLE IF NOT EXISTS `__CL_COURSE__lp_rel_learnPath_module` (
        `learnPath_module_id` int(11) NOT NULL auto_increment,
        `learnPath_id` int(11) NOT NULL default '0',
        `module_id` int(11) NOT NULL default '0',
        `lock` enum('OPEN','CLOSE') NOT NULL default 'OPEN',
        `visibility` enum('HIDE','SHOW') NOT NULL default 'SHOW',
        `specificComment` text NOT NULL,
        `rank` int(11) NOT NULL default '0',
        `parent` int(11) NOT NULL default '0',
        `raw_to_pass` tinyint(4) NOT NULL default '50',
        PRIMARY KEY  (`learnPath_module_id`)
    ) ENGINE=MyISAM  COMMENT='This table links module to the learning path using them';
    
    CREATE TABLE IF NOT EXISTS `__CL_COURSE__lp_asset` (
        `asset_id` int(11) NOT NULL auto_increment,
        `module_id` int(11) NOT NULL default '0',
        `path` varchar(255) NOT NULL default '',
        `comment` varchar(255) default NULL,
        PRIMARY KEY  (`asset_id`)
    ) ENGINE=MyISAM  COMMENT='List of resources of module of learning paths';
    
    CREATE TABLE IF NOT EXISTS `__CL_COURSE__lp_user_module_progress` (
        `user_module_progress_id` int(22) NOT NULL auto_increment,
        `user_id` mediumint(9) NOT NULL default '0',
        `learnPath_module_id` int(11) NOT NULL default '0',
        `learnPath_id` int(11) NOT NULL default '0',
        `lesson_location` varchar(255) NOT NULL default '',
        `lesson_status` enum('NOT ATTEMPTED','PASSED','FAILED','COMPLETED','BROWSED','INCOMPLETE','UNKNOWN') NOT NULL default 'NOT ATTEMPTED',
        `entry` enum('AB-INITIO','RESUME','') NOT NULL default 'AB-INITIO',
        `raw` tinyint(4) NOT NULL default '-1',
        `scoreMin` tinyint(4) NOT NULL default '-1',
        `scoreMax` tinyint(4) NOT NULL default '-1',
        `total_time` varchar(13) NOT NULL default '0000:00:00.00',
        `session_time` varchar(13) NOT NULL default '0000:00:00.00',
        `suspend_data` text NOT NULL,
        `credit` enum('CREDIT','NO-CREDIT') NOT NULL default 'NO-CREDIT',
        PRIMARY KEY  (`user_module_progress_id`)
    ) ENGINE=MyISAM  COMMENT='Record the last known status of the user in the course';