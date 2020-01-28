<?php // $Id: upgrade_course_110.lib.php 13302 2011-07-11 15:19:09Z abourguignon $
if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * Function to update course tool from 1.9 to 1.10
 *
 * - READ THE SAMPLE AND COPY PASTE IT
 * - ADD TWICE MORE COMMENT THAT YOU THINK NEEDED
 *
 * This code would be splited by task for the 1.8 Stable but code inside
 * function won't change, so let's go to write it.
 *
 * @version     $Revision: 13302 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     UPGRADE
 * @author      Claro Team <cvs@claroline.net>
 * @author      Antonin Bourguignon <antonin.bourguignon@claroline.net>
 */

/*===========================================================================
 Upgrade to claroline 1.10
 ===========================================================================*/


function announcements_upgrade_to_110 ($course_code)
{
    global $currentCourseVersion;

    $versionRequiredToProceed = '/^1.9/';
    
    $tool = 'ANNOUNCEMENTS';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);

    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        // On init , $step = 1
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :
                
                // Add the attribute sourceCourseId to the course table
                $sqlForUpdate[] = "ALTER TABLE `" . $currentCourseDbNameGlu . "announcement` ADD `visibleFrom` DATE NULL DEFAULT NULL AFTER `contenu`";
                
                if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step;
                
                unset($sqlForUpdate);
            
            case 2 :
                
                // Add the attribute sourceCourseId to the course table
                $sqlForUpdate[] = "ALTER TABLE `" . $currentCourseDbNameGlu . "announcement` ADD `visibleUntil` DATE NULL DEFAULT NULL AFTER `visibleFrom`";
                
                if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step;
                
                unset($sqlForUpdate);
            
            default :
                
                $step = set_upgrade_status($tool, 0);
                return $step;
        }
    }
    
    return false;
}

function calendar_upgrade_to_110 ($course_code)
{
    global $currentCourseVersion;

    $versionRequiredToProceed = '/^1.9/';
    
    $tool = 'CALENDAR';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);

    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        // On init , $step = 1
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :
                
                // Add the attribute sourceCourseId to the course table
                $sqlForUpdate[] = "ALTER TABLE `" . $currentCourseDbNameGlu . "calendar_event` ADD `speakers` VARCHAR(150) NULL DEFAULT NULL AFTER `lasting`";
                
                if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step;
                
                unset($sqlForUpdate);
                
            case 2 :
                
                // Change the attribute location
                $sqlForUpdate[] = "ALTER TABLE `" . $currentCourseDbNameGlu . "calendar_event` CHANGE `location` `location` VARCHAR(150) NULL DEFAULT NULL";
                
                if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step;
                
                unset($sqlForUpdate);

           case 3 :

                // Add the attribute group_id into the course table
                $sqlForUpdate[] = "ALTER TABLE `" . $currentCourseDbNameGlu . "calendar_event` ADD `group_id` INT(4) NOT NULL DEFAULT 0";

                if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step;

                unset($sqlForUpdate);
                
            default :
                
                $step = set_upgrade_status($tool, 0);
                return $step;
            
        }
    }
    
    return false;
}

function exercise_upgrade_to_110 ($course_code)
{
    global $currentCourseVersion;

    $versionRequiredToProceed = '/^1.9/';
    
    $tool = 'CLQWZ';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);
    
    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        // On init , $step = 1
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :
                
                // Add the attribute sourceCourseId to the course table
                $sqlForUpdate[] = "ALTER TABLE `" . $currentCourseDbNameGlu . "qwz_question` ADD `id_category` INT(11) NULL DEFAULT '0' AFTER `grade`";
                
                upgrade_apply_sql($sqlForUpdate);
                $step = set_upgrade_status($tool, $step+1, $course_code);
                
                unset($sqlForUpdate);
                
             case 2 :
                
                // Add the key
                $sqlForUpdate[] = "ALTER TABLE `" . $currentCourseDbNameGlu . "qwz_tracking` ADD INDEX `user_id` (`user_id`)";
                
                upgrade_apply_sql($sqlForUpdate);
                $step = set_upgrade_status($tool, $step+1, $course_code);
                
                unset($sqlForUpdate);
                
             case 3 :
                
                // Add the key
                $sqlForUpdate[] = "ALTER TABLE `" . $currentCourseDbNameGlu . "qwz_tracking` ADD INDEX `exo_id` (`exo_id`)";
                
                upgrade_apply_sql($sqlForUpdate);
                $step = set_upgrade_status($tool, $step+1, $course_code);
                
                unset($sqlForUpdate);
                
             case 4 :
                
                // Add the key
                $sqlForUpdate[] = "ALTER TABLE `" . $currentCourseDbNameGlu . "qwz_tracking_questions` ADD INDEX `exercise_track_id` (`exercise_track_id`)";
                
                upgrade_apply_sql($sqlForUpdate);
                $step = set_upgrade_status($tool, $step+1, $course_code);
                
                unset($sqlForUpdate);
                
             case 5 :
                
                // Add the key
                $sqlForUpdate[] = "ALTER TABLE `" . $currentCourseDbNameGlu . "qwz_tracking_questions` ADD INDEX `question_id` (`question_id`)";
                
                upgrade_apply_sql($sqlForUpdate);
                $step = set_upgrade_status($tool, $step+1, $course_code);
                
                unset($sqlForUpdate);
                
             case 6 :
                
                // Add the key
                $sqlForUpdate[] = "ALTER TABLE `" . $currentCourseDbNameGlu . "qwz_tracking_answers` ADD INDEX `details_id` (`details_id`)";
                
                upgrade_apply_sql($sqlForUpdate);
                $step = set_upgrade_status($tool, $step+1, $course_code);
                
                unset($sqlForUpdate);

             case 7 :
                $sqlForUpdate[] = "CREATE TABLE IF NOT EXISTS `" . $currentCourseDbNameGlu . "qwz_users_random_questions` (
                                    `id` int(11) NOT NULL auto_increment,
                                    `user_id` int(11) NOT NULL,
                                    `exercise_id` int(11) NOT NULL,
                                    `questions` text NOT NULL,
                                    PRIMARY KEY  (`id`)
                                  ) ENGINE=MyISAM;";
                $step = set_upgrade_status( $tool, $step+1, $course_code);


            default :
                
                $step = set_upgrade_status($tool, 0);
                return $step;
        }
    }
    
    return false;
}

function tool_intro_upgrade_to_110 ($course_code)
{
    global $currentCourseVersion;

    $versionRequiredToProceed = '/^1.9/';
    
    $tool = 'CLTI';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);
    
    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        // On init , $step = 1
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :
                // Are there any tool_intro to migrate ?
                $req = "SELECT
                            COUNT(id) AS nbToolIntro
                            FROM `" . $currentCourseDbNameGlu . "tool_intro`
                            WHERE `tool_id` <= 0";
                
                $sql = mysql_query($req);
                $sqlForUpdate = array();
                
                if ($sql)
                {
                    $res = mysql_fetch_assoc($sql);
                    
                    // If yes: create a portlet for this course in `rel_course_portlet`
                    if (isset($res['nbToolIntro']) && $res['nbToolIntro'] > 0)
                    {
                        // Select the id of the course (int)
                        $req = "SELECT cours_id AS courseId
                                FROM `" . get_conf('mainTblPrefix') . "cours`
                                WHERE `code` = '".$course_code."'";
                        
                        $sql = mysql_query($req);
                        
                        $res = mysql_fetch_assoc($sql);
                        
                        // Insert the portlet
                        $sqlForUpdate[] = "INSERT INTO `" . get_conf('mainTblPrefix') . "rel_course_portlet`
                                (courseId, rank, label, visible)
                                VALUES
                                ('".$res['courseId']."', 1, 'CLTI', 1)";
                    }
                }
                
                if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step;
                
                unset($sqlForUpdate);
            default :
                
                $step = set_upgrade_status($tool, 0);
                return $step;
            
        }
    }
    
    return false;
}