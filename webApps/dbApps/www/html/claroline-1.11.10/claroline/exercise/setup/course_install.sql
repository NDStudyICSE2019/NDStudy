CREATE TABLE IF NOT EXISTS `__CL_COURSE__qwz_exercise` (
    `id` int(11) NOT NULL auto_increment,
    `title` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `visibility` enum('VISIBLE','INVISIBLE') NOT NULL default 'INVISIBLE',
    `displayType` enum('SEQUENTIAL','ONEPAGE') NOT NULL default 'ONEPAGE',
    `shuffle` smallint(6) NOT NULL default '0',
    `useSameShuffle` enum('0','1') NOT NULL default '0',
    `showAnswers` enum('ALWAYS','NEVER','LASTTRY') NOT NULL default 'ALWAYS',
    `startDate` datetime NOT NULL,
    `endDate` datetime NOT NULL,
    `timeLimit` smallint(6) NOT NULL default '0',
    `attempts` tinyint(4) NOT NULL default '0',
    `anonymousAttempts` enum('ALLOWED','NOTALLOWED') NOT NULL default 'NOTALLOWED',
    `quizEndMessage` text NOT NULL,
    PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__qwz_question` (
    `id` int(11) NOT NULL auto_increment,
    `title` varchar(255) NOT NULL default '',
    `description` text NOT NULL,
    `attachment` varchar(255) NOT NULL default '',
    `type` enum('MCUA','MCMA','TF','FIB','MATCHING') NOT NULL default 'MCUA',
    `grade` float NOT NULL default '0',
    `id_category` int(11) NOT NULL default '0',
    PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__qwz_rel_exercise_question` (
    `exerciseId` int(11) NOT NULL,
    `questionId` int(11) NOT NULL,
    `rank` int(11) NOT NULL default '0'
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__qwz_answer_truefalse` (
    `id` int(11) NOT NULL auto_increment,
    `questionId` int(11) NOT NULL,
    `trueFeedback` text NOT NULL,
    `trueGrade` float NOT NULL,
    `falseFeedback` text NOT NULL,
    `falseGrade` float NOT NULL,
    `correctAnswer` enum('TRUE','FALSE') NOT NULL,
    PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__qwz_answer_multiple_choice` (
    `id` int(11) NOT NULL auto_increment,
    `questionId` int(11) NOT NULL,
    `answer` text NOT NULL,
    `correct` tinyint(4) NOT NULL,
    `grade` float NOT NULL,
    `comment` text NOT NULL,
    PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__qwz_answer_fib` (
    `id` int(11) NOT NULL auto_increment,
    `questionId` int(11) NOT NULL,
    `answer` text NOT NULL,
    `gradeList` text NOT NULL,
    `wrongAnswerList` text NOT NULL,
    `type` tinyint(4) NOT NULL,
    PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__qwz_answer_matching` (
    `id` int(11) NOT NULL auto_increment,
    `questionId` int(11) NOT NULL,
    `answer` text NOT NULL,
    `match` varchar(32) default NULL,
    `grade` float NOT NULL default '0',
    `code` varchar(32) default NULL,
    PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__qwz_tracking` (
    `id` int(11) NOT NULL auto_increment,
    `user_id` int(10) default NULL,
    `date` datetime NOT NULL default '0000-00-00 00:00:00',
    `exo_id` int(11) NOT NULL default '0',
    `result` float NOT NULL default '0',
    `time`    mediumint(8) NOT NULL default '0',
    `weighting` float NOT NULL default '0',
    PRIMARY KEY  (`id`),
    KEY `user_id` (`user_id`),
    KEY `exo_id` (`exo_id`)
) ENGINE=MyISAM  COMMENT='Record informations about exercices';

CREATE TABLE IF NOT EXISTS `__CL_COURSE__qwz_tracking_questions` (
    `id` int(11) NOT NULL auto_increment,
    `exercise_track_id` int(11) NOT NULL default '0',
    `question_id` int(11) NOT NULL default '0',
    `result` float NOT NULL default '0',
    PRIMARY KEY  (`id`),
    KEY `exercise_track_id` (`exercise_track_id`),
    KEY `question_id` (`question_id`)
) ENGINE=MyISAM  COMMENT='Record answers of students in exercices';

CREATE TABLE IF NOT EXISTS `__CL_COURSE__qwz_tracking_answers` (
    `id` int(11) NOT NULL auto_increment,
    `details_id` int(11) NOT NULL default '0',
    `answer` text NOT NULL,
    PRIMARY KEY  (`id`),
    KEY `details_id` (`details_id`)
) ENGINE=MyISAM  COMMENT='';

CREATE TABLE IF NOT EXISTS `__CL_COURSE__qwz_users_random_questions` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `exercise_id` int(11) NOT NULL,
  `questions` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `user_exo` ( `user_id`, `exercise_id` )
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__qwz_questions_categories` (
    `id` int(11) NOT NULL auto_increment,
    `title` varchar(50) NOT NULL,
    `description` TEXT,
   PRIMARY KEY (`id`)
) ENGINE=MyISAM COMMENT='Record the categories of questions';
