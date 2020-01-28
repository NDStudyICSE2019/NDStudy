CREATE TABLE IF NOT EXISTS `__CL_COURSE__calendar_event` (
    `id` int(11) NOT NULL auto_increment,
    `titre` varchar(200),
    `contenu` text,
    `day` date NOT NULL default '0000-00-00',
    `hour` time NOT NULL default '00:00:00',
    `lasting` varchar(20),
    `speakers` varchar(150),
    `visibility` enum('SHOW','HIDE') NOT NULL default 'SHOW',
    `location` varchar(150),
    `group_id` INT(4) NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=MyISAM;
