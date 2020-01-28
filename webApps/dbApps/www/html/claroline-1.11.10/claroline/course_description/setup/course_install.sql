CREATE TABLE IF NOT EXISTS `__CL_COURSE__course_description` (
    `id` int(11) NOT NULL auto_increment,
    `category` int(11) NOT NULL default '-1',
    `title` varchar(255) default NULL,
    `content` TEXT,
    `lastEditDate` DATETIME NOT NULL,
    `visibility` enum('VISIBLE','INVISIBLE') NOT NULL default 'VISIBLE',
    PRIMARY KEY  (`id`)
) ENGINE=MyISAM;