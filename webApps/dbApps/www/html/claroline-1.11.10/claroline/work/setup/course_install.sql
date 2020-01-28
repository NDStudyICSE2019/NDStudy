    CREATE TABLE IF NOT EXISTS `__CL_COURSE__wrk_submission` (
        `id` int(11) NOT NULL auto_increment,
        `assignment_id` int(11) default NULL,
        `parent_id` int(11) default NULL,
        `user_id` int(11) default NULL,
        `group_id` int(11) default NULL,
        `title` varchar(200) NOT NULL default '',
        `visibility` enum('VISIBLE','INVISIBLE') default 'VISIBLE',
        `creation_date` datetime NOT NULL default '0000-00-00 00:00:00',
        `last_edit_date` datetime NOT NULL default '0000-00-00 00:00:00',
        `authors` varchar(200) NOT NULL default '',
        `submitted_text` text NOT NULL,
        `submitted_doc_path` varchar(200) NOT NULL default '',
        `private_feedback` text default NULL,
        `original_id` int(11) default NULL,
        `score` smallint(3) NULL default NULL,
        PRIMARY KEY  (`id`),
        KEY `user` (`user_id`),
        KEY `original` (`original_id`),
        KEY `visible` (`visibility`),
        KEY `parent` (`parent_id`),
        KEY `group` (`group_id`),
        KEY `last_edit` (`last_edit_date`)
    ) ENGINE=MyISAM;
    
    CREATE INDEX `assigid` ON `__CL_COURSE__wrk_submission` ( `assignment_id` );
    
    CREATE TABLE IF NOT EXISTS `__CL_COURSE__wrk_assignment` (
        `id` int(11) NOT NULL auto_increment,
        `title` varchar(200) NOT NULL default '',
        `description` text NOT NULL,
        `visibility` enum('VISIBLE','INVISIBLE') NOT NULL default 'VISIBLE',
        `def_submission_visibility` enum('VISIBLE','INVISIBLE') NOT NULL default 'VISIBLE',
        `assignment_type` enum('INDIVIDUAL','GROUP') NOT NULL default 'INDIVIDUAL',
        `authorized_content`  enum('TEXT','FILE','TEXTFILE') NOT NULL default 'FILE',
        `allow_late_upload` enum('YES','NO') NOT NULL default 'YES',
        `start_date` datetime NOT NULL default '0000-00-00 00:00:00',
        `end_date` datetime NOT NULL default '0000-00-00 00:00:00',
        `prefill_text` text NOT NULL,
        `prefill_doc_path` varchar(200) NOT NULL default '',
        `prefill_submit` enum('ENDDATE','AFTERPOST') NOT NULL default 'ENDDATE',
        PRIMARY KEY  (`id`)
    ) ENGINE=MyISAM;