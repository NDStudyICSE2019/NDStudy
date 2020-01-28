CREATE TABLE IF NOT EXISTS `__CL_COURSE__announcement` (
    `id` mediumint(11) NOT NULL auto_increment,
    `title` varchar(80) default NULL,
    `contenu` text,
    `visibleFrom` date default NULL,
    `visibleUntil` date default NULL,
    `temps` date default NULL,
    `ordre` mediumint(11) NOT NULL default '0',
    `visibility` enum('SHOW','HIDE') NOT NULL default 'SHOW',
    PRIMARY KEY  (`id`)
) ENGINE=MyISAM COMMENT='announcements table';