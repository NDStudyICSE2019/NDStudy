<?php // $Id: upgrade_course_16.lib.php 13348 2011-07-18 13:58:28Z abourguignon $

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * Function to upgrade course tool 1.5 to 1.6.
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
 Upgrade to claroline 1.6
 ===========================================================================*/

function upgrade_to_16_remove_deprecated_tool($course_code)
{
    global $currentCourseVersion;

    $versionRequiredToProceed = '/^1.5/';
    $tool = 'DROP';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);
    
    if ( preg_match($versionRequiredToProceed, $currentCourseVersion) )
    {
        switch ( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :  // STEP 1 DROP UNUSED TABLE
                $sql_step1[] = "DROP TABLE IF EXISTS `".$currentCourseDbNameGlu."pages`";
                if ( ! upgrade_apply_sql($sql_step1) ) return $step;
                $step = set_upgrade_status($tool, 0, $course_code);
            default :
                return $step;
        }
    }
    return false;
}

/**
 * Upgrade forum tool to 1.6
 * IN 1.6 intro table has new column to prepare multi-intro orderable
 * Job for this upgrade
 * STEP 1 drop all unused tables
 */

function forum_upgrade_to_16($course_code)
{
    global $currentCourseVersion;

    $versionRequiredToProceed = '/^1.5/';
    $tool = 'CLFRM';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);
    
    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        switch ( $step = get_upgrade_status($tool,$course_code) )
        {
         
            case 1 : // STEP 1 drop all unused tables
                $sql_step1[] = "DROP TABLE IF EXISTS `".$currentCourseDbNameGlu."bb_access`";
                $sql_step1[] = "DROP TABLE IF EXISTS `".$currentCourseDbNameGlu."bb_banlist`";
                $sql_step1[] = "DROP TABLE IF EXISTS `".$currentCourseDbNameGlu."bb_config`";
                $sql_step1[] = "DROP TABLE IF EXISTS `".$currentCourseDbNameGlu."bb_disallow`";
                $sql_step1[] = "DROP TABLE IF EXISTS `".$currentCourseDbNameGlu."bb_forum_access`";
                $sql_step1[] = "DROP TABLE IF EXISTS `".$currentCourseDbNameGlu."bb_forum_mods`";
                $sql_step1[] = "DROP TABLE IF EXISTS `".$currentCourseDbNameGlu."bb_headermetafooter`";
                $sql_step1[] = "DROP TABLE IF EXISTS `".$currentCourseDbNameGlu."bb_ranks`";
                $sql_step1[] = "DROP TABLE IF EXISTS `".$currentCourseDbNameGlu."bb_sessions`";
                $sql_step1[] = "DROP TABLE IF EXISTS `".$currentCourseDbNameGlu."bb_themes`";
                $sql_step1[] = "DROP TABLE IF EXISTS `".$currentCourseDbNameGlu."bb_words`";
                if ( ! upgrade_apply_sql($sql_step1) ) return $step;
                $step = set_upgrade_status($tool, 0, $course_code);
            default :
                return $step;
        }
    }
    return false;
}
 
/**
 * Upgrade quizz tool to 1.6
 */
function quizz_upgrade_to_16($course_code)
{
    global $currentCourseVersion, $currentCourseCreationDate;

    $versionRequiredToProceed = '/^1.5/';
    $tool = 'CLQWZ';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);
    
    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :  // STEP 1 UPDATE TABLES STRUCTURES
                $sql_step1[] = "ALTER IGNORE TABLE `".$currentCourseDbNameGlu."quiz_question` " .
                               "CHANGE `picture_name` `attached_file` varchar(50) default ''";
                $sql_step1[] = "ALTER IGNORE TABLE `".$currentCourseDbNameGlu."quiz_question` " .
                               "CHANGE `ponderation` `ponderation` float unsigned default NULL";
                $sql_step1[] = "ALTER IGNORE TABLE `".$currentCourseDbNameGlu."quiz_test` " .
                               "ADD `max_time` smallint(5) unsigned NOT NULL default '0'";
                $sql_step1[] = "ALTER IGNORE TABLE `".$currentCourseDbNameGlu."quiz_test` " .
                               "ADD `max_attempt` tinyint(3) unsigned NOT NULL default '0'";
                $sql_step1[] = "ALTER IGNORE TABLE `".$currentCourseDbNameGlu."quiz_test` ".
                               "ADD `show_answer` enum('ALWAYS','NEVER','LASTTRY') NOT NULL default 'ALWAYS'";
                $sql_step1[] = "ALTER IGNORE TABLE `".$currentCourseDbNameGlu."quiz_test` " .
                               "ADD `anonymous_attempts` enum('YES','NO') NOT NULL default 'YES'";
                $sql_step1[] = "ALTER IGNORE TABLE `".$currentCourseDbNameGlu."quiz_test` ".
                               "ADD `start_date` datetime NOT NULL default '0000-00-00 00:00:00'";
                $sql_step1[] = "ALTER IGNORE TABLE `".$currentCourseDbNameGlu."quiz_test` ".
                               "ADD `end_date` datetime NOT NULL default '0000-00-00 00:00:00'";
                $sql_step1[] = "ALTER IGNORE TABLE `".$currentCourseDbNameGlu."quiz_answer` ".
                               "CHANGE `ponderation` `ponderation` float default NULL";
                $sql_step1[] = "UPDATE `".$currentCourseDbNameGlu."quiz_test` ".
                               "SET `start_date` = '" . $currentCourseCreationDate ."'" ;
                $sql_step1[] = "UPDATE `".$currentCourseDbNameGlu."quiz_test` ".
                               "SET `end_date` = '9999-12-31 23:59:59'";
                if ( ! upgrade_apply_sql($sql_step1) ) return $step;
                $step = set_upgrade_status($tool, 2, $course_code);
            case 2 : // STEP 2 Create The new table
                if ( quizz_upgrade_to_16_step_2() === false )
                {
                    return $step;
                }
                $step = set_upgrade_status($tool, 0, $course_code);
            default :
                return $step;
        }
    }
    return false;
}


function quizz_upgrade_to_16_step_2()
{
    global $currentcoursePathSys;

    $error = false;

    // rename folder image in course folder to exercise
    if ( is_dir($currentcoursePathSys . 'image') )
    {
        if ( !is_dir($currentcoursePathSys . 'exercise') )
        {
            if ( ! @rename($currentcoursePathSys . 'image',$currentcoursePathSys . 'exercise') )
            {
                $error = true;
                log_message('Error: Cannot rename ' . $currentcoursePathSys.'image' . ' to ' . $currentcoursePathSys . 'exercise');
            }
        }
        else
        {
            log_message('Warning: ' . sprintf( '%1$s and %2$s exists in %3$s. '
                                             . 'It\'s due to an old upgrade error. '
                                             . 'Check if all files of %1$s ares in %1$s and delete %1$s', 'image', 'exercise', $currentcoursePathSys));
        }
    }
    elseif ( !is_dir($currentcoursePathSys . 'exercise') )
    {
        if ( !@mkdir($currentcoursePathSys . 'exercise', CLARO_FILE_PERMISSIONS) )
        {
            $error = true;
            log_message('Error: Cannot create ' .  $currentcoursePathSys . 'exercise');
        }
    }
    if ( !$error ) return true;
    else           return false;
}

/**
 * Upgrade assignment tool to 1.6
 */

function assignment_upgrade_to_16($course_code)
{
    global $currentCourseVersion, $currentcoursePathSys, $currentCourseCreationDate;
    global $_uid;

    $versionRequiredToProceed = '/^1.5/';
    $tool = 'CLWRK';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tbl_rel_course_user = $tbl_mdb_names['rel_course_user'];
    $tbl_course_tool = $tbl_mdb_names['tool'];
    
    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :
    
            /**
             * STEP 1 Create new work table
             */
    
            $sql_step1[] = "CREATE TABLE IF NOT EXISTS `".$currentCourseDbNameGlu."wrk_assignment` (
                `id` int(11) NOT NULL auto_increment,
                `title` varchar(200) NOT NULL default '',
                `description` text NOT NULL,
                `visibility` enum('VISIBLE','INVISIBLE') NOT NULL default 'VISIBLE',
                `def_submission_visibility` enum('VISIBLE','INVISIBLE') NOT NULL default 'VISIBLE',
                `assignment_type` enum('INDIVIDUAL','GROUP') NOT NULL default 'INDIVIDUAL',
                `authorized_content` enum('TEXT','FILE','TEXTFILE') NOT NULL default 'FILE',
                `allow_late_upload` enum('YES','NO') NOT NULL default 'YES',
                `start_date` datetime NOT NULL default '0000-00-00 00:00:00',
                `end_date` datetime NOT NULL default '0000-00-00 00:00:00',
                `prefill_text` text NOT NULL,
                `prefill_doc_path` varchar(200) NOT NULL default '',
                `prefill_submit` enum('ENDDATE','AFTERPOST') NOT NULL default 'ENDDATE',
                PRIMARY KEY  (`id`)
                ) ENGINE=MyISAM";
/*
            $sql_step1[] = "UPDATE `".$currentCourseDbNameGlu."wrk_assignment`
                SET
                `end_date` = '".date('Y-m-d H:i:00', mktime( date('H'),date('i'),0,date('m'), date('d'), date('Y')+1 ) )."'
                WHERE `end_date` = '0000-00-00 00:00:00'
                ";
*/
            $sql_step1[] = "CREATE TABLE IF NOT EXISTS `".$currentCourseDbNameGlu."wrk_submission` (
                `id` int(11) NOT NULL auto_increment,
                `assignment_id` int(11) default NULL,
                `parent_id` int(11) default NULL,
                `user_id`  int(11) default NULL ,
                `group_id` int(11) default NULL,
                `title` varchar(200) NOT NULL default '',
                `visibility` enum('VISIBLE','INVISIBLE') default 'VISIBLE',
                `creation_date` datetime NOT NULL default '0000-00-00 00:00:00',
                `last_edit_date` datetime NOT NULL default '0000-00-00 00:00:00',
                `authors` varchar(200) NOT NULL default '',
                `submitted_text` text NOT NULL,
                `submitted_doc_path` varchar(200) NOT NULL default '',
                `private_feedback` text,
                `original_id` int(11) default NULL,
                `score` smallint(3) default NULL,
                PRIMARY KEY  (`id`)
                ) ENGINE=MyISAM";
                if ( ! upgrade_apply_sql($sql_step1) ) return $step;
                $step = set_upgrade_status($tool, 2, $course_code);
    
            case 2 :
    
                /**
                 * STEP 2 : Create a new assignment
                 */
    
                // get work intro in tool
                $sql_work_intro = "SELECT ti.texte_intro
                                    FROM `" . $currentCourseDbNameGlu . "tool_list` tl,
                                         `" . $currentCourseDbNameGlu . "tool_intro` ti,
                                         `" . $tbl_course_tool . "` ct
                                    WHERE ti.id = tl.id
                                        AND tl.tool_id =  ct.id
                                        AND ct.claro_label = 'CLWRK___'";
                
                $work_intro = claro_sql_query_get_single_value($sql_work_intro);
                
                if ( $work_intro === FALSE ) $work_intro = '';
                
                $sql_step2[] = "INSERT INTO `".$currentCourseDbNameGlu."wrk_assignment`
                    SET `id` = 1,
                    `title` = 'Assignments',
                    `description`= '" . mysql_real_escape_string($work_intro) . "',
                    `visibility` = 'VISIBLE',
                    `def_submission_visibility` = 'VISIBLE',
                    `assignment_type` = 'INDIVIDUAL',
                    `authorized_content` = 'FILE',
                    `allow_late_upload` = 'NO',
                    `start_date` = '" . $currentCourseCreationDate . "',
                    `end_date` = DATE_ADD(NOW(),INTERVAL 1 YEAR),
                    `prefill_text` = '',
                    `prefill_doc_path` = '',
                    `prefill_submit` = 'ENDDATE' ";
                if ( ! upgrade_apply_sql($sql_step2) ) return $step;
                $step = set_upgrade_status($tool, 3, $course_code);
            
            case 3 :
    
                /**
                 * STEP 3 : Add old works as submissions of new assignment
                 */
    
                // get course manager of the course
                $sql_get_id_of_one_teacher = "SELECT `user_id` `uid` " .
                                             " FROM `". $tbl_rel_course_user . "` " .
                                             " WHERE `code_cours` = '".$course_code."' LIMIT 1";
                
                $teacher = claro_sql_query_fetch_all($sql_get_id_of_one_teacher);

                $teacher_uid = $teacher[0]['uid'];
                
                // if no course manager, you are enrolled in as
                if ( !is_numeric($teacher_uid) )
                {
                    $teacher_uid = $_uid;
                    $sql_set_teacher = "INSERT INTO `". $tbl_rel_course_user . "`
                                        SET `user_id` = '" . $teacher_uid . "'
                                             , `code_cours` = '" . $course_code . "'
                                             , `role` = 'Course missing manager';";
                    if ( ! claro_sql_query($sql_set_teacher) ) return $step;
                    log_message('Warning : Course '.$course_code.' has no teacher, you are enrolled in as course manager.');
                }
    
                // add old work in submission of course manager
                $sql_step3[] = "INSERT IGNORE INTO `".$currentCourseDbNameGlu."wrk_submission`
                 (assignment_id,user_id,title,visibility,authors,submitted_text,submitted_doc_path)
                 SELECT 1, '". $teacher_uid ."', titre, IF(accepted,'VISIBLE','INVISIBLE'), auteurs, description, url
                    FROM `".$currentCourseDbNameGlu."assignment_doc`";
    
                if ( ! upgrade_apply_sql($sql_step3) ) return $step;
                $step = set_upgrade_status($tool, 4, $course_code);
    
            case 4 :
    
                /**
                 * STEP 4 : Update document path of submissions
                 */
    
                $sql_step4[] = "UPDATE `".$currentCourseDbNameGlu."wrk_submission`
                                SET submitted_doc_path = REPLACE (`submitted_doc_path` ,'work/','')";
                if ( ! upgrade_apply_sql($sql_step4) ) return $step;
                $step = set_upgrade_status($tool, 5, $course_code);
    
            case 5 :
    
                /**
                 * STEP 5 : Create new folder to store assig_1 and move old old work documents in it
                 */
                
                // create new folder
                $work_dirname = $currentcoursePathSys.'work/';
                $assignment_dirname = $work_dirname . 'assig_1/';
                if ( !is_dir($assignment_dirname) )
                {
                    if ( !@mkdir($assignment_dirname, CLARO_FILE_PERMISSIONS) )
                    {
                        log_message('Error: Cannot create ' . $assignment_dirname );
                        return $step;
                    }
                }
                
                // move assignment from work to work/assig_1
                if ( is_dir($work_dirname) )
                {
                    if ( ( $handle = opendir($work_dirname) ) )
                    {
                        while ( FALSE !== ($file = readdir($handle)) )
                        {
                            if ( is_dir($work_dirname.$file) ) continue;
                
                            if ( @rename($work_dirname.$file,$assignment_dirname.$file) === FALSE )
                            {
                                log_message('Error: Cannot rename ' . $work_dirname . $file . ' to ' . $assignment_dirname . $file );
                                return $step;
                            }
                
                        }
                        closedir($handle);
                    }
                }
                $step = set_upgrade_status($tool, 6, $course_code);
    
            case 6 :
                /**
                 * STEP 6 Drop deprecated assignment_doc
                 */
                // $sql_step6[] = "DROP TABLE IF EXISTS `".$currentCourseDbNameGlu."assignment_doc`";
                // if ( ! upgrade_apply_sql($sql_step6) ) return $step;
                $step = set_upgrade_status($tool, 0, $course_code);
            default :
                return $step;
        } // end switch
    }
    return false;
}

/**
 * Upgrade tracking tool to 1.6
 * STEP 1 BAcKUP OLD TABLE Before creat the new
 * STEP 2 Create The new table
 * STEP 3 Update tracking Table
 */

function tracking_upgrade_to_16($course_code)
{
    global $currentCourseVersion;

    $versionRequiredToProceed = '/^1.5/';
    $tool = 'CLTRK';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);
    
    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
         
            case 1 :  // STEP 1 BAcKUP OLD TABLE Before creat the new
                $sql_step1[] = "RENAME TABLE `".$currentCourseDbNameGlu."track_e_access` TO `".$currentCourseDbNameGlu."track_e_access_15`";
                if ( ! upgrade_apply_sql($sql_step1) ) return $step;
                $step = set_upgrade_status($tool, 2, $course_code);

            case 2 : // STEP 2 Create The new table

                $sql_step2[] = "CREATE TABLE IF NOT EXISTS `".$currentCourseDbNameGlu."track_e_access` (
                  `access_id` int(11) NOT NULL auto_increment,
                  `access_user_id`  int(11) default NULL ,
                  `access_date` datetime NOT NULL default '0000-00-00 00:00:00',
                  `access_tid` int(10) default NULL,
                  `access_tlabel` varchar(8) default NULL,
                  PRIMARY KEY  (`access_id`)
                ) ENGINE=MyISAM COMMENT='Record informations about access to course or tools'";
                if ( ! upgrade_apply_sql($sql_step2) ) return $step;
                $step = set_upgrade_status($tool, 3, $course_code);

            case 3 : // STEP 3 Update tracking Table

                $sql_step3[] = "ALTER IGNORE TABLE `".$currentCourseDbNameGlu."track_e_exercices` " .
                               "ADD `exe_time`  mediumint(8) NOT NULL default '0'";
                $sql_step3[] = "ALTER IGNORE TABLE `".$currentCourseDbNameGlu."track_e_exercices` " .
                               "CHANGE `exe_result` `exe_result` float NOT NULL default '0'";
                $sql_step3[] = "ALTER IGNORE TABLE `".$currentCourseDbNameGlu."track_e_exercices` " .
                               "CHANGE `exe_weighting` `exe_weighting` float NOT NULL default '0'";

                if ( ! upgrade_apply_sql($sql_step3) ) return $step;
                $step = set_upgrade_status($tool, 0, $course_code);
            default :
                return $step;
        }
    }
    return false;
}
