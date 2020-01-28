CREATE TABLE IF NOT EXISTS `__CL_COURSE__bb_categories` (
    cat_id int(10) NOT NULL auto_increment,
    cat_title varchar(100),
    cat_order int(10),
    PRIMARY KEY (cat_id)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__bb_forums`(
    forum_id int(10) NOT NULL auto_increment,
    group_id int(11) default NULL,
    forum_name varchar(150),
    forum_desc text,
    forum_access int(10) DEFAULT '1',
    forum_moderator int(10),
    forum_topics int(10) DEFAULT '0' NOT NULL,
    forum_posts int(10) DEFAULT '0' NOT NULL,
    forum_last_post_id int(10) DEFAULT '0' NOT NULL,
    cat_id int(10),
    forum_type int(10) DEFAULT '0',
    forum_order int(10) DEFAULT '0',
    PRIMARY KEY (forum_id),
    KEY forum_last_post_id (forum_last_post_id)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__bb_posts`(
    post_id int(10) NOT NULL auto_increment,
    topic_id int(10) DEFAULT '0' NOT NULL,
    forum_id int(10) DEFAULT '0' NOT NULL,
    poster_id int(10) DEFAULT '0' NOT NULL,
    post_time varchar(20),
    poster_ip varchar(16),
    nom varchar(30),
    prenom varchar(30),
    PRIMARY KEY (post_id),
    KEY forum_id (forum_id),
    KEY topic_id (topic_id),
    KEY poster_id (poster_id)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__bb_posts_text` (
    post_id int(10) DEFAULT '0' NOT NULL,
    post_text text,
    PRIMARY KEY (post_id)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__bb_priv_msgs` (
    msg_id int(10) NOT NULL auto_increment,
    from_userid int(10) DEFAULT '0' NOT NULL,
    to_userid int(10) DEFAULT '0' NOT NULL,
    msg_time varchar(20),
    poster_ip varchar(16),
    msg_status int(10) DEFAULT '0',
    msg_text text,
    PRIMARY KEY (msg_id),
    KEY to_userid (to_userid)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__bb_topics` (
    topic_id int(10) NOT NULL auto_increment,
    topic_title varchar(100),
    topic_poster int(10),
    topic_time varchar(20),
    topic_views int(10) DEFAULT '0' NOT NULL,
    topic_replies int(10) DEFAULT '0' NOT NULL,
    topic_last_post_id int(10) DEFAULT '0' NOT NULL,
    forum_id int(10) DEFAULT '0' NOT NULL,
    topic_status int(10) DEFAULT '0' NOT NULL,
    topic_notify int(2) DEFAULT '0',
    nom varchar(30),
    prenom varchar(30),
    PRIMARY KEY (topic_id),
    KEY forum_id (forum_id),
    KEY topic_last_post_id (topic_last_post_id)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__bb_users` (
    user_id int(10) NOT NULL auto_increment,
    username varchar(40) NOT NULL,
    user_regdate varchar(20) NOT NULL,
    user_password varchar(32) NOT NULL,
    user_email varchar(50),
    user_icq varchar(15),
    user_website varchar(100),
    user_occ varchar(100),
    user_from varchar(100),
    user_intrest varchar(150),
    user_sig varchar(255),
    user_viewemail tinyint(2),
    user_theme int(10),
    user_aim varchar(18),
    user_yim varchar(25),
    user_msnm varchar(25),
    user_posts int(10) DEFAULT '0',
    user_attachsig int(2) DEFAULT '0',
    user_desmile int(2) DEFAULT '0',
    user_html int(2) DEFAULT '0',
    user_bbcode int(2) DEFAULT '0',
    user_rank int(10) DEFAULT '0',
    user_level int(10) DEFAULT '1',
    user_lang varchar(255),
    user_actkey varchar(32),
    user_newpasswd varchar(32),
    PRIMARY KEY (user_id)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__bb_whosonline` (
    id int(3) NOT NULL auto_increment,
    ip varchar(255),
    name varchar(255),
    count varchar(255),
    date varchar(255),
    username varchar(40),
    forum int(10),
    PRIMARY KEY (id)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__bb_rel_topic_userstonotify` (
    `notify_id` int(10) NOT NULL auto_increment,
    `user_id` int(10) NOT NULL default '0',
    `topic_id` int(10) NOT NULL default '0',
    PRIMARY KEY  (`notify_id`),
    KEY `SECONDARY` (`user_id`,`topic_id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__bb_rel_forum_userstonotify` (
    `notify_id` int(10) NOT NULL auto_increment,
    `user_id` int(10) NOT NULL default '0',
    `forum_id` int(10) NOT NULL default '0',
    PRIMARY KEY  (`notify_id`),
    KEY `SECONDARY` (`user_id`,`forum_id`)
) ENGINE=MyISAM;