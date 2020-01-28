--
-- Creation script with sample data for "php-addressbook"
--
-- * You may add table prefixes, if the "$table_prefix"
--   parameter is set in "config.php".
--
-- $LastChangedDate$
-- $Rev$
-- 
--

CREATE TABLE addressbook (
  domain_id int(9) unsigned NOT NULL default 0,
  id int(9) unsigned NOT NULL,
  firstname varchar(255) NOT NULL,
  middlename varchar(255) NOT NULL,
  lastname varchar(255) NOT NULL,
  nickname varchar(255) NOT NULL,
  company varchar(255) NOT NULL,
  title varchar(255) NOT NULL,
  address text NOT NULL,
  addr_long text,
  addr_lat text,
  addr_status text,
  home text NOT NULL,
  mobile text NOT NULL,
  work text NOT NULL,
  fax text NOT NULL,
  email text NOT NULL,
  email2 text NOT NULL,
  email3 text NOT NULL,
  im text NOT NULL,
  im2 text NOT NULL,
  im3 text NOT NULL,
  homepage text NOT NULL,
  bday tinyint(2) NOT NULL,
  bmonth varchar(50) NOT NULL,
  byear varchar(4) NOT NULL,
  aday tinyint(2) NOT NULL,
  amonth varchar(50) NOT NULL,
  ayear varchar(4) NOT NULL,
  address2 text NOT NULL,
  phone2 text NOT NULL,
  notes text NOT NULL,
  photo mediumtext,
  x_vcard mediumtext,
  x_activesync mediumtext,
  created datetime default NULL,
  modified datetime default NULL,
  deprecated datetime default NULL,
  password varchar(256) default NULL,
  login date default NULL,
  role varchar(256) default NULL,
  PRIMARY KEY (id,deprecated,domain_id),
  KEY deprecated_domain_id_idx (deprecated,domain_id)
) DEFAULT CHARSET=utf8;

CREATE TABLE group_list (
  `domain_id` int(9) unsigned NOT NULL default 0,
  `group_id` int(9) unsigned NOT NULL auto_increment,
  `group_parent_id` int(9) default NULL,
  `created` datetime default NULL,
  `modified` datetime default NULL,
  `deprecated` datetime default NULL,
  `group_name` varchar(255) NOT NULL default '',
  `group_header` mediumtext NOT NULL,
  `group_footer` mediumtext NOT NULL,
  PRIMARY KEY (group_id,deprecated,domain_id)
) DEFAULT CHARSET=utf8;

CREATE TABLE address_in_groups (
  `domain_id` int(9) unsigned NOT NULL default 0,
  `id` int(9) unsigned NOT NULL default 0,
  `group_id` int(9) unsigned NOT NULL default 0,
  `created` datetime default NULL,
  `modified` datetime default NULL,
  `deprecated` datetime default NULL,
  PRIMARY KEY (`group_id`,`id`, deprecated)
) DEFAULT CHARSET=utf8;

CREATE TABLE month_lookup (
  `bmonth` varchar(50) NOT NULL default '',
  `bmonth_short` char(3) NOT NULL default '',
  `bmonth_num` int(2) unsigned NOT NULL default 0,
  PRIMARY KEY (bmonth_num)
) DEFAULT CHARSET=utf8;

INSERT INTO `month_lookup` VALUES ('', '', 0);
INSERT INTO `month_lookup` VALUES ('January', 'Jan', 1);
INSERT INTO `month_lookup` VALUES ('February', 'Feb', 2);
INSERT INTO `month_lookup` VALUES ('March', 'Mar', 3);
INSERT INTO `month_lookup` VALUES ('April', 'Apr', 4);
INSERT INTO `month_lookup` VALUES ('May', 'May', 5);
INSERT INTO `month_lookup` VALUES ('June', 'Jun', 6);
INSERT INTO `month_lookup` VALUES ('July', 'Jul', 7);
INSERT INTO `month_lookup` VALUES ('August', 'Aug', 8);
INSERT INTO `month_lookup` VALUES ('September', 'Sep', 9);
INSERT INTO `month_lookup` VALUES ('October', 'Oct', 10);
INSERT INTO `month_lookup` VALUES ('November', 'Nov', 11);
INSERT INTO `month_lookup` VALUES ('December', 'Dec', 12);

CREATE TABLE users (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `domain_id` int(9) unsigned NOT NULL DEFAULT '0',
  `username` char(128) NOT NULL,
  `md5_pass` char(128) NOT NULL,
  `password_hint` varchar(255) NOT NULL DEFAULT '',
  `sso_facebook_uid` varchar(255) DEFAULT NULL,
  `sso_google_uid` varchar(255) DEFAULT NULL,
  `sso_live_uid` varchar(255) DEFAULT NULL,
  `sso_yahoo_uid` varchar(255) DEFAULT NULL,
  `lastname` varchar(50) NOT NULL DEFAULT '',
  `firstname` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `phone` varchar(50) NOT NULL DEFAULT '',
  `address1` varchar(100) NOT NULL DEFAULT '',
  `address2` varchar(100) NOT NULL DEFAULT '',
  `city` varchar(80) NOT NULL DEFAULT '',
  `state` varchar(20) NOT NULL DEFAULT '',
  `zip` varchar(20) NOT NULL DEFAULT '',
  `country` varchar(50) NOT NULL DEFAULT '',
  `master_code` char(128) NOT NULL,
  `confirmation_code` char(128) DEFAULT NULL,
  `pass_reset_code` char(128) DEFAULT NULL,
  `status` char(128) NOT NULL DEFAULT 'NEW' COMMENT 'New, Ready, Blocked',
  `trials` int(11) NOT NULL DEFAULT '0',
   created datetime default NULL,
   modified datetime default NULL,
   deprecated datetime default NULL,
  PRIMARY KEY (`user_id`)
) DEFAULT CHARSET=utf8;
