CREATE TABLE IF NOT EXISTS `__CL_COURSE__wiki_acls` (
  wiki_id int(11) unsigned NOT NULL default '0',
  flag varchar(255) NOT NULL default '',
  `value` enum('false','true') NOT NULL default 'false'
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__wiki_pages` (
  id int(11) unsigned NOT NULL auto_increment,
  wiki_id int(11) unsigned NOT NULL default '0',
  owner_id int(11) unsigned NOT NULL default '0',
  title varchar(255) NOT NULL default '',
  ctime datetime NOT NULL default '0000-00-00 00:00:00',
  last_version int(11) unsigned NOT NULL default '0',
  last_mtime datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (id)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__wiki_pages_content` (
  id int(11) unsigned NOT NULL auto_increment,
  pid int(11) unsigned NOT NULL default '0',
  editor_id int(11) NOT NULL default '0',
  mtime datetime NOT NULL default '0000-00-00 00:00:00',
  content text NOT NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `__CL_COURSE__wiki_properties` (
  id int(11) unsigned NOT NULL auto_increment,
  title varchar(255) NOT NULL default '',
  description text,
  group_id int(11) NOT NULL default '0',
  PRIMARY KEY  (id)
) ENGINE=MyISAM;