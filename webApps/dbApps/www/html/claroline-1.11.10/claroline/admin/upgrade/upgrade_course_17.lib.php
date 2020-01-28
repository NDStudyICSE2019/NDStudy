<?php // $Id: upgrade_course_17.lib.php 13348 2011-07-18 13:58:28Z abourguignon $

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * Function to update course tool 1.6 to 1.7.
 *
 * @version     $Revision: 13348 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/index.php/Upgrade_claroline_1.6
 * @package     UPGRADE
 * @author      Claro Team <cvs@claroline.net>
 * @author      Mathieu Laurent   <mla@claroline.net>
 * @author      Christophe Gesché <moosh@claroline.net>
 */

/*===========================================================================
 Upgrade to claroline 1.7
 ===========================================================================*/

/**
 * Upgrade announcement tool to 1.7
 * add visibility fields in announcement
 *
 * @param $course_code string
 * @return boolean whether tru if succeed
 */

function announcement_upgrade_to_17($course_code)
{
    global $currentCourseVersion;

    $versionRequiredToProceed = '/^1.6/';
    $tool = 'CLANN';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);

    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :  // STEP 1 CREATE TABLES
                $sql_step1[] = "ALTER IGNORE TABLE `" . $currentCourseDbNameGlu."announcement` " .
                               "ADD `visibility` enum('SHOW','HIDE') NOT NULL default 'SHOW'";
                if ( ! upgrade_apply_sql($sql_step1) ) return $step;
                $step = set_upgrade_status($tool, 0, $course_code);
            default :
                return $step;
        }
    }
    return false;
}

/**
 * Upgrade agenda tool to 1.7
 * add visibility fields in calendar
 *
 * @param $course_code string
 * @return boolean whether tru if succeed
 */

function agenda_upgrade_to_17($course_code)
{
    global $currentCourseVersion;

    $versionRequiredToProceed = '/^1.6/';
    $tool = 'CLCAL';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);
    
    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :  // add visibility fields in calendar
                $sql_step1[] = "ALTER IGNORE TABLE `" . $currentCourseDbNameGlu."calendar_event` " .
                               "ADD `visibility` enum('SHOW','HIDE') NOT NULL default 'SHOW'";
                if ( ! upgrade_apply_sql($sql_step1) ) return $step;
                $step = set_upgrade_status($tool, 0, $course_code);
            default :
                return $step;
        }
    }
    return false;
}


/**
 * Upgrade course description tool to 1.7
 * add visibility fields in course description
 *
 * @param $course_code string
 * @return boolean whether tru if succeed
 */

function course_description_upgrade_to_17($course_code)
{
    global $currentCourseVersion;

    $versionRequiredToProceed = '/^1.6/';
    $tool = 'CLDSC';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);
    
    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :
                $sql_step1[] = "ALTER IGNORE TABLE `" . $currentCourseDbNameGlu."course_description` " .
                               "ADD `visibility` enum('SHOW','HIDE') NOT NULL default 'SHOW'";
                if ( ! upgrade_apply_sql($sql_step1) ) return $step;
                $step = set_upgrade_status($tool, 0, $course_code);
            default :
                return $step;
        }
    }
    return false;
    
}

/**
 * Upgrade tracking tool to 1.7
 *  Tracking need two new tables for quizzes is new in 1.7
 * @param $course_code string
 * @return boolean whether tru if succeed
 */

function tracking_upgrade_to_17($course_code)
{
    global $currentCourseVersion;

    $versionRequiredToProceed = '/^1.6/';
    $tool = 'CLTRK';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);
    
    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :  // STEP 1 CREATE TABLES
    
                $sql_step1[] = "CREATE TABLE IF NOT EXISTS `" . $currentCourseDbNameGlu . "track_e_exe_details` (
                                `id` int(11) NOT NULL auto_increment,
                                `exercise_track_id` int(11) NOT NULL default '0',
                                `question_id` int(11) NOT NULL default '0',
                                `result` float NOT NULL default '0',
                                PRIMARY KEY  (`id`)
                                ) ENGINE=MyISAM COMMENT='Record answers of students in exercices'";
            
                $sql_step1[] = "CREATE TABLE IF NOT EXISTS `" . $currentCourseDbNameGlu . "track_e_exe_answers` (
                                `id` int(11) NOT NULL auto_increment,
                                `details_id` int(11) NOT NULL default '0',
                                `answer` text NOT NULL,
                                PRIMARY KEY  (`id`)
                                ) ENGINE=MyISAM COMMENT=''";
        
                if ( !upgrade_apply_sql($sql_step1) ) return $step;
                $step = set_upgrade_status($tool, 0, $course_code);
            default :
                return $step;
        }
    }
    return false;
}

/**
 * Upgrade linker tool to 1.7
 * @param $course_code string
 * @return boolean whether tru if succeed
 */

function linker_upgrade_to_17($course_code)
{
    global $currentCourseVersion;

    $versionRequiredToProceed = '/^1.6/';
    $tool = 'CLLNK';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);
    
    // LINKER is new in 1.7
    // Job for this upgrade
    // STEP 1 CREATE TABLES
    
    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :  // STEP 1 CREATE TABLES
                $sql_step1[] = "CREATE TABLE IF NOT EXISTS `". $currentCourseDbNameGlu ."lnk_links` (
                            `id` int(11) NOT NULL auto_increment,
                            `src_id` int(11) NOT NULL default '0',
                            `dest_id` int(11) NOT NULL default '0',
                            `creation_time` timestamp NOT NULL,
                            PRIMARY KEY  (`id`)
                            ) ENGINE=MyISAM";
                       
                $sql_step1[] = "CREATE TABLE IF NOT EXISTS `". $currentCourseDbNameGlu ."lnk_resources` (
                            `id` int(11) NOT NULL auto_increment,
                            `crl` text NOT NULL,
                            `title` text NOT NULL,
                            PRIMARY KEY  (`id`)
                            ) ENGINE=MyISAM";

                if ( ! upgrade_apply_sql($sql_step1) ) return $step;
                $step = set_upgrade_status($tool, 0, $course_code);
            default :
                return $step;
        }
    }
    return false;
}

/**
 * Upgrade wiki tool to 1.7
  * @param $course_code string
 * @return boolean whether tru if succeed
 */

function wiki_upgrade_to_17($course_code)
{
    global $currentCourseVersion;

    $versionRequiredToProceed = '/^1.6/';
    $tool = 'CLWIKI';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);
    
    // WIKI is new in 1.7

    // Job for this upgrade
    // STEP 1 CREATE TABLES
    // STEP 2 register tool in the course
    
    ////////////////////////////////////
    
    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :  // STEP 1 CREATE TABLES
            $sql_step1[] = "CREATE TABLE IF NOT EXISTS `". $currentCourseDbNameGlu ."wiki_properties`(
                            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                            `title` VARCHAR(255) NOT NULL DEFAULT '',
                            `description` TEXT NULL,
                            `group_id` INT(11) NOT NULL DEFAULT 0,
                            PRIMARY KEY(`id`)
                            )";
        
            $sql_step1[] = "CREATE TABLE IF NOT EXISTS `". $currentCourseDbNameGlu ."wiki_acls` (
                            `wiki_id` INT(11) UNSIGNED NOT NULL,
                            `flag` VARCHAR(255) NOT NULL,
                            `value` ENUM('false','true') NOT NULL DEFAULT 'false'
                            )";
        
            $sql_step1[] = "CREATE TABLE IF NOT EXISTS `". $currentCourseDbNameGlu ."wiki_pages` (
                            `id` int(11) unsigned NOT NULL auto_increment,
                            `wiki_id` int(11) unsigned NOT NULL default '0',
                            `owner_id` int(11) unsigned NOT NULL default '0',
                            `title` varchar(255) NOT NULL default '',
                            `ctime` datetime NOT NULL default '0000-00-00 00:00:00',
                            `last_version` int(11) unsigned NOT NULL default '0',
                            `last_mtime` datetime NOT NULL default '0000-00-00 00:00:00',
                            PRIMARY KEY  (`id`) )" ;
        
            $sql_step1[] = "CREATE TABLE IF NOT EXISTS `". $currentCourseDbNameGlu ."wiki_pages_content` (
                            `id` int(11) unsigned NOT NULL auto_increment,
                            `pid` int(11) unsigned NOT NULL default '0',
                            `editor_id` int(11) NOT NULL default '0',
                            `mtime` datetime NOT NULL default '0000-00-00 00:00:00',
                            `content` text NOT NULL,
                            PRIMARY KEY  (`id`) )";

            if ( ! upgrade_apply_sql($sql_step1) ) return $step;
            $step = set_upgrade_status($tool, 2, $course_code);
            
            case 2 : // STEP 2 register tool in the course

                if ( !add_tool_in_course_tool_list('CLWIKI__','COURSE_ADMIN',$currentCourseDbNameGlu) )
                {
                    log_message('Error: Add wiki failed in course ' . $course_code);
                    return $step;
                }
                else
                {
                    $step = set_upgrade_status($tool, 0, $course_code);
                }

            default :
                return $step;
        }
    }
    return false;
}

/**
 * Upgrade forum tool to 1.7
  * @param $course_code string
 * @return boolean whether tru if succeed
 */

function forum_upgrade_to_17($course_code)
{
    $versionRequiredToProceed = '/^1.6/';
    $tool = 'CLFRM';
    
    global $currentCourseVersion;
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);
    
    // IN 1.7 forum's can be link to a group.
    // Before 1.7 It' was groups which was linked to a forum
    // Job for this upgrade
    // STEP1 create new field to keep group_id
    // STEP2 set a value in groups link to a forum
    // STEP3 remove old value in groups
    // STEP4 remove a deprecated field

    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            // groups of forums
            case 1 :  // STEP1 create new field to keep group_id

                $sql_step1[] = "ALTER IGNORE TABLE `" . $currentCourseDbNameGlu . "bb_forums` ADD group_id int(11) default NULL";
                if ( ! upgrade_apply_sql($sql_step1) ) return $step;
                $step = set_upgrade_status($tool, 1, $course_code);

            case 2 :  // STEP2 set a value in groups link to a forum

                $sql = "SELECT `id`,`forumId`
                        FROM `" . $currentCourseDbNameGlu . "group_team`";

                $result = upgrade_sql_query($sql);
                if (! $result)
                {
                    return $step;
                }
                while ( ($row = mysql_fetch_array($result)) )
                {
                    $sql = " UPDATE `" . $currentCourseDbNameGlu."bb_forums`
                             SET group_id = " . $row['id'] . "
                             WHERE `forum_id` = " . $row['forumId'] . "";
                    if (! upgrade_sql_query($sql))
                    {
                        return $step;
                    }
                }
                $step = set_upgrade_status($tool, 3, $course_code);

            case 3 : // STEP3 remove old value in groups

                $sql_step3[] = "ALTER IGNORE TABLE `" . $currentCourseDbNameGlu . "group_team` DROP COLUMN forumId";
                if ( ! upgrade_apply_sql($sql_step3) )
                {
                    return $step;
                }
                $step = set_upgrade_status($tool, 4, $course_code);

            case 4 :    // STEP4 remove a deprecated field

                $sql_step4[] = "ALTER IGNORE TABLE `" . $currentCourseDbNameGlu."bb_forums` DROP COLUMN md5";
                if ( ! upgrade_apply_sql($sql_step4) ) return $step;
                $step = set_upgrade_status($tool, 0, $course_code);
            default :
                return $step;
        }
    }
    return false;
}

/**
 * Upgrade introduction text table to 1.7
  * @param $course_code string
 * @return boolean whether tru if succeed
 */

function introtext_upgrade_to_17($course_code)
{
    global $currentCourseVersion;

    $versionRequiredToProceed = '/^1.6/';
    $tool = 'CLINTRO';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);
    
    // IN 1.7 intro table has new column to prepare multi-intro orderable

    // Job for this upgrade
    // STEP 1 BAcKUP OLD TABLE Before creat the new
    // STEP 2 Create The new table
    // STEP 3 FILL The new table with value from the old
    // STEP 4 Delete backuped table
    
    ////////////////////////////////////
    
    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            // groups of forums
            case 1 :  // STEP 1 BAcKUP OLD TABLE Before creat the new
                $sql_step1[] = "RENAME TABLE `".$currentCourseDbNameGlu."tool_intro` TO `".$currentCourseDbNameGlu."tool_intro_prev17`";
                if ( ! upgrade_apply_sql($sql_step1) ) return $step;
                $step = set_upgrade_status($tool, 2, $course_code);
            
            case 2 : // STEP 2 Create The new table
                $sql_step2[] = "CREATE TABLE `".$currentCourseDbNameGlu."tool_intro` (
                              `id` int(11) NOT NULL auto_increment,
                              `tool_id` int(11) NOT NULL default '0',
                              `title` varchar(255) default NULL,
                              `display_date` datetime default NULL,
                              `content` text,
                              `rank` int(11) default '1',
                              `visibility` enum('SHOW','HIDE') NOT NULL default 'SHOW',
                           PRIMARY KEY  (`id`) ) ";
                if ( ! upgrade_apply_sql($sql_step2) ) return $step;
                $step = set_upgrade_status($tool, 3, $course_code);

            case 3 : // STEP 3 FILL The new table with value from the old
                $sql = " SELECT `id`, `texte_intro`
                         FROM `".$currentCourseDbNameGlu."tool_intro_prev17` ";

                $result = upgrade_sql_query($sql);

                if ( ! $result ) return $step;

                while ( ( $row = mysql_fetch_array($result) ) )
                {
                    $sql = "INSERT INTO `" . $currentCourseDbNameGlu . "tool_intro`
                            (`tool_id`,`content`)
                            VALUES
                            ('" . $row['id'] . "','" . claro_sql_escape($row['texte_intro']) . "')";

                    if ( ! upgrade_sql_query($sql) )
                    {
                        return $step;
                    }
                }
                $step = set_upgrade_status($tool, 4, $course_code);
            
            case 4 :   // STEP 4 Delete OLD

                $sql_step4[] = "DROP TABLE IF EXISTS `".$currentCourseDbNameGlu."tool_intro_prev17`";
                if ( ! upgrade_apply_sql($sql_step4) ) return $step;
                $step = set_upgrade_status($tool, 0, $course_code);

            default :

                return $step;
        }
    }
    return false;
}