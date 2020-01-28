<?php // $Id: upgrade_course_19.lib.php 14211 2012-07-20 07:32:41Z zefredz $

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * Function to update course tool 1.8 to 1.9
 *
 * - READ THE SAMPLE AND COPY PASTE IT
 *
 * - ADD TWICE MORE COMMENT THAT YOU THINK NEEDED
 *
 * This code would be splited by task for the 1.8 Stable but code inside
 * function won't change, so let's go to write it.
 *
 * @version     $Revision: 14211 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/index.php/Upgrade_claroline_1.6
 * @package     UPGRADE
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Mathieu Laurent   <mla@claroline.net>
 * @author Christophe Gesche <moosh@claroline.net>
 */

/*===========================================================================
 Upgrade to claroline 1.8
 ===========================================================================*/

/**
 * Upgrade foo tool to 1.8
 *
 * explanation of task
 *
 * @param $course_code string
 * @return boolean whether true if succeed
 */

function tool_list_upgrade_to_19 ($course_code)
{
    global $currentCourseVersion;

    $versionRequiredToProceed = '/^1.8/';
    $tool = 'TOOLLIST';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);

    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        // On init , $step = 1
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :
                $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $currentCourseDbNameGlu . "tool_list`
                              ADD `activated` ENUM('true','false') NOT NULL DEFAULT 'true'";
                
                if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step ;
                
            case 2 :
                $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $currentCourseDbNameGlu . "tool_list`
                              ADD `installed` ENUM('true','false') NOT NULL DEFAULT 'true'";
                
                if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, 0, $course_code);
                else return $step ;
                
            default :
                $step = set_upgrade_status($tool, 0, $course_code);
                return $step;
        }
    }
    return false;
}

function chat_upgrade_to_19 ($course_code)
{

    global $currentCourseVersion;

    $versionRequiredToProceed = '/^1.8/';
    $tool = 'CLCHT';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);

    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        $coursePath  = get_path('coursesRepositorySys') . claro_get_course_path($course_code);
        $courseChatPath  = $coursePath . '/chat/';
        
        $toolId = get_tool_id_from_module_label( 'CLCHAT' );
        
        if( !$toolId )
        {
            // get out of here if new chat module is missing
            log_message('New Chat module not found : keep the old one !');
            $step = set_upgrade_status($tool, 0, $course_code);
            return $step;
        }
                
        // On init , $step = 1
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            
            case 1 :
                // get all chat files
                log_message("Search in ". $courseChatPath);
                
                if ( !file_exists ( $courseChatPath ) )
                {
                    log_message( "Cannot save chat : folder {$courseChatPath} does not exists" );
                    $error = true;
                }
                else
                {
                    try
                    {
                        $it = new DirectoryIterator($courseChatPath);
                        $error = false;

                        foreach( $it as $file )
                        {
                            if( ! $file->isFile() ) continue;

                            if( $file->getFilename() == $course_code . '.chat.html' )
                            {
                                // chat de cours
                                log_message("Try to export course chat : " . $file->getFilename() );
                                $exportFileDir = $coursePath.'/document/recovered_chat/';
                                $groupId = null;
                            }
                            else
                            {
                                // group chat
                                log_message("Try to export group chat : " . $file->getFilename() );
                                // get groupId
                                $matches = array();
                                preg_match('/\w+\.(\d+)\.chat\.html/', $file->getFilename(), $matches);
                                if( isset($matches[1]) )
                                {
                                    $groupId = (int) $matches[1];
                                }
                                else
                                {
                                    log_message('Cannot find group id in chat filename : '. $file->getFilename());
                                    break;
                                }

                                if( ! ($groupData = claro_get_group_data(array(CLARO_CONTEXT_COURSE => $course_code, CLARO_CONTEXT_GROUP => $groupId))) )
                                {
                                    // group cannot be found, save in document
                                    $exportFileDir = $coursePath.'/document/recovered_chat/';
                                    log_message('Cannot find group so save chat filename in course : '. $file->getFilename());
                                }
                                else
                                {
                                    $exportFileDir = $coursePath.'/group/'.$groupData['directory'].'/recovered_chat/';
                                }
                            }

                            // create dire
                            claro_mkdir($exportFileDir, CLARO_FILE_PERMISSIONS, true);

                            // try to find a unique filename
                            $fileNamePrefix = 'chat.'.date('Y-m-j').'_';
                            if( !is_null($groupId) )
                            {
                                $fileNamePrefix .=  $groupId . '_';
                            }

                            $i = 1;
                            while ( file_exists($exportFileDir.$fileNamePrefix.$i.'.html') ) $i++;

                            $savedFileName = $fileNamePrefix.$i.'.html';

                            // prepare output
                            $out = '<html>'
                            . '<head>'
                            . '<title>Discussion - archive</title>'
                            . '</head>'
                            . '<body>'
                            . file_get_contents($file->getPathname())
                            . '</body>'
                            . '</html>';


                            // write to file
                            if( ! file_put_contents($exportFileDir.$savedFileName, $out) )
                            {
                                log_message('Cannot save chat : '. $exportFileDir.$savedFileName);
                                $error = true;
                            }
                        }
                    }
                    catch ( Exception $e )
                    {
                        log_message( 'Cannot save chat : '. $e->getMessage() );
                        $error = true;
                    }
                }
                // save those with group id in group space

                
                if ( !$error ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step ;
                
            case 2 :
                // activate new chat in each course
                $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);

                if( ! register_module_in_single_course( $toolId, $course_code ) )
                {
                    log_message("register_module_in_single_course( $toolId, $course_code ) failed");
                    return $step;
                }
                
                $sqlForUpdate[] = "UPDATE `" . $currentCourseDbNameGlu . "tool_list`
                SET `activated` = 'true',
                    `installed` = 'false'
                WHERE tool_id = " . (int) $toolId;
                
                if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step ;
                
                unset($sqlForUpdate);
                
            default :
                $step = set_upgrade_status($tool, 0, $course_code);
                return $step;
        }
    }
    return false;

}

function course_description_upgrade_to_19 ($course_code)
{
    global $currentCourseVersion;

    $versionRequiredToProceed = '/^1.8/';
    $tool = 'CLDSC';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);

    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        // On init , $step = 1
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :
                // id becomes int(11)
                $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $currentCourseDbNameGlu . "course_description`
                              CHANGE `id` `id` int(11) NOT NULL auto_increment";
                
                if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step ;
                
                unset($sqlForUpdate);
                
            case 2 :
                // add category field
                $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $currentCourseDbNameGlu . "course_description`
                              ADD `category` int(11) NOT NULL DEFAULT '-1'";
                
                if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step ;
                
                unset($sqlForUpdate);
            
            case 3 :
                // rename update to lastEditDate
                $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $currentCourseDbNameGlu . "course_description`
                              CHANGE `upDate` `lastEditDate` datetime NOT NULL";
                
                if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step ;
                
                unset($sqlForUpdate);

            case 4 :
                // change possible values of visibility fields (show/hide -> visible/invisible)
                // so to do that we: #1 change the possible enum values to have them all
                //                   #2 change fields with show to visible / fields with hide to invisible
                //                   #3 change the possible enum values to keep only the good ones

                // #1
                $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $currentCourseDbNameGlu . "course_description`
                              CHANGE `visibility` `visibility` enum('SHOW','HIDE','VISIBLE','INVISIBLE') NOT NULL default 'VISIBLE'";
                //#2
                $sqlForUpdate[] = "UPDATE `" . $currentCourseDbNameGlu . "course_description`
                                SET `visibility` = IF(`visibility` = 'SHOW' ,'VISIBLE','INVISIBLE')";
                
                //#3
                $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $currentCourseDbNameGlu . "course_description`
                              CHANGE `visibility` `visibility` enum('VISIBLE','INVISIBLE') NOT NULL default 'VISIBLE'";
                
                if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step ;
                
                unset($sqlForUpdate);
                
            default :
                $step = set_upgrade_status($tool, 0, $course_code);
                return $step;
        }
    }
    return false;
}

/**
 * Upgrade foo tool to 1.9
 *
 * explanation of task
 *
 * @param $course_code string
 * @return boolean whether true if succeed
 */

function quiz_upgrade_to_19 ($course_code)
{
    // PRIMARY KEY (`exerciseId`,`questionId`)
    global $currentCourseVersion, $currentcoursePathSys;

    $versionRequiredToProceed = '/^1.8/';
    $tool = 'CLQWZ';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);

    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        // On init , $step = 1
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :
                // qwz_rel_exercise_question - fix key and index
                $sql = "ALTER TABLE `". $currentCourseDbNameGlu ."qwz_rel_exercise_question`
                  DROP PRIMARY KEY,
                  ADD PRIMARY KEY(`exerciseId`, `questionId`)";
                  
                $success = upgrade_sql_query( $sql );
                
                if ( ! $success )
                {
                    $sql = "ALTER TABLE `". $currentCourseDbNameGlu ."qwz_rel_exercise_question`
                        ADD PRIMARY KEY(`exerciseId`, `questionId`)";
                        
                    $success = upgrade_sql_query( $sql );
                }
                if ( $success ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step ;
                
            case 2 :
                // qwz_tracking - rename table
                $sqlForUpdate[] = "ALTER IGNORE TABLE `". $currentCourseDbNameGlu . "track_e_exercices`
                                RENAME TO `". $currentCourseDbNameGlu ."qwz_tracking`";
                if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step ;
                
                unset($sqlForUpdate);
                
            case 3 :
                // qwz_tracking - rename fields
                $sqlForUpdate[] = "ALTER IGNORE TABLE `". $currentCourseDbNameGlu . "qwz_tracking`
                                CHANGE `exe_id`         `id`        int(11) NOT NULL auto_increment,
                                CHANGE `exe_user_id`    `user_id`   int(11) default NULL,
                                CHANGE `exe_date`       `date`      datetime NOT NULL default '0000-00-00 00:00:00',
                                CHANGE `exe_exo_id`     `exo_id`    int(11) NOT NULL default '0',
                                CHANGE `exe_result`     `result`    float NOT NULL default '0',
                                CHANGE `exe_time`       `time`      mediumint(8) NOT NULL default '0',
                                CHANGE `exe_weighting`  `weighting` float NOT NULL default '0'";

                if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step ;
                
                unset($sqlForUpdate);

            case 4 :
                 // qwz_tracking_questions - rename table
                $sqlForUpdate[] = "ALTER TABLE `". $currentCourseDbNameGlu . "track_e_exe_details`
                                RENAME TO `". $currentCourseDbNameGlu . "qwz_tracking_questions`";

                if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step ;
                
                unset($sqlForUpdate);
                
            case 5 :
                // qwz_tracking_answers - rename table
                $sqlForUpdate[] = "ALTER TABLE `". $currentCourseDbNameGlu . "track_e_exe_answers`
                                RENAME TO `". $currentCourseDbNameGlu . "qwz_tracking_answers`";

                if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step ;
                
                unset($sqlForUpdate);
                
            case 6 :
                //qwz_exercise - add field quizEndMessage
                $sqlForUpdate[] = "ALTER TABLE `" . $currentCourseDbNameGlu . "qwz_exercise`
                                    ADD `quizEndMessage` TEXT NOT NULL";
                if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step;
                
                unset($sqlForUpdate);
            
            case 7 :
                // qwz_users_random_questions add random questions list table
                $sqlForUpdate[] = "CREATE TABLE IF NOT EXISTS `" . $currentCourseDbNameGlu . "qwz_users_random_questions` (
                                    `id` int(11) NOT NULL auto_increment,
                                    `user_id` int(11) NOT NULL,
                                    `exercise_id` int(11) NOT NULL,
                                    `questions` text NOT NULL,
                                    PRIMARY KEY  (`id`)
                                  ) ENGINE=MyISAM;";
                if( upgrade_apply_sql( $sqlForUpdate ) ) $step = set_upgrade_status( $tool, $step+1, $course_code);
                else return $step;
                
                unset($sqlForUpdate);
            
            case 8 :
                // qwz_exercise - add field useSameShuffle
                $sqlForUpdate[] = "ALTER TABLE `". $currentCourseDbNameGlu ."qwz_exercise`
                                    ADD `useSameShuffle` ENUM( '0', '1' ) NOT NULL DEFAULT '0' AFTER `shuffle` ;";
                if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step;
                
                unset($sqlForUpdate);
            
            default :
                $step = set_upgrade_status($tool, 0, $course_code);
                return $step;
        }
    }

    return false;
}

function calendar_upgrade_to_19($course_code)
{
    $versionRequiredToProceed = '/^1.8/';
    $tool = 'CLCAL';
    
    global $currentCourseVersion;
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);

    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :
                $sql = "ALTER IGNORE TABLE `".$currentCourseDbNameGlu."calendar_event`
                        ADD `location` varchar(50)";
                
                if ( upgrade_sql_query($sql) ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step;

            default :
                $step = set_upgrade_status($tool, 0, $course_code);
                return $step;
        }
    }

    return false;
}

function convert_crl_from_18_to_19( $crl )
{
    $matches =array();
    
    if (preg_match(
        '!(crl://claroline\.net/\w+/[^/]+/groups/\d+/)([^/]+)(.*)!',
        $crl, $matches ) )
    {
        $crl = $matches[1] . rtrim( $matches[2], '_' ) . $matches[3];
    }
    elseif (preg_match(
        '!(crl://claroline\.net/\w+/[^/]+/)([^/]+)(.*)!',
        $crl, $matches ) )
    {
        $crl = $matches[1] . rtrim( $matches[2], '_' ) . $matches[3];
    }
    else
    {
        $crl = $crl;
    }
    
    log_message($crl);
    
    return $crl;
}


function linker_upgrade_to_19($course_code)
{
    $versionRequiredToProceed = '/^1.8/';
    $tool = 'CLLNK';
    
    global $currentCourseVersion;
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);

    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :
                $sql = "SELECT `crl` FROM `".$currentCourseDbNameGlu."lnk_resources`";
                
                $res = claro_sql_query_fetch_all_rows( $sql );
                $success = ($res !== false);
                
                log_message("found " . count($res) . " crls to convert");

                foreach( $res as $resource )
                {
                    $sql = "UPDATE `".$currentCourseDbNameGlu."lnk_resources`
                    SET `crl` = '" . claro_sql_escape( convert_crl_from_18_to_19($resource['crl']) ) ."'
                    WHERE `crl` = '" .claro_sql_escape( $resource['crl'] ) ."'";
                    
                    $success = upgrade_sql_query( $sql );
                    
                    if ( ! $success )
                    {
                        break;
                    }
                }
                
                if ( $success ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step;

            default :
                $step = set_upgrade_status($tool, 0, $course_code);
                return $step;
        }
    }

    return false;
}

/**
 * Upgrade tracking tool to 1.9 - this function do not take care of old data  !
 */

function tracking_upgrade_to_19($course_code)
{
    /*
     * DO NOT get the old tracking data to put it in this table here
     * as it is a very heavy process it will be done in another script dedicated to that.
     */
    $versionRequiredToProceed = '/^1.8/';
    $tool = 'CLSTATS';
    
    global $currentCourseVersion;
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);

    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :
                $sql = "CREATE TABLE IF NOT EXISTS `".$currentCourseDbNameGlu."tracking_event` (
                      `id` int(11) NOT NULL auto_increment,
                      `tool_id` int(11) DEFAULT NULL,
                      `user_id` int(11) DEFAULT NULL,
                      `group_id` int(11) DEFAULT NULL,
                      `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                      `type` varchar(60) NOT NULL DEFAULT '',
                      `data` text NOT NULL,
                      PRIMARY KEY  (`id`)
                    ) ENGINE=MyISAM;";
                
                if ( upgrade_sql_query($sql) ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step;

            default :
                $step = set_upgrade_status($tool, 0, $course_code);
                return $step;
        }
    }

    return false;
}

/**
 * Move tracking data from old tables to new ones.
 * Note that exercise tracking update is made in quiz_upgrade_to_19 function
 * and that tmp table is mostly created to insert in date order data from different old tables
 * to the new one
 *
 * @param integer $course_code
 * @return upgrade status
 */
function tracking_data_upgrade_to_19( $course_code )
{
    $versionRequiredToProceed = '/^1.8/';
    $tool = 'CLSTATS_DATA';
    
    global $currentCourseVersion;
    $currentCourseDbNameGlu = claro_get_course_db_name_glued( $course_code );

    if ( preg_match( $versionRequiredToProceed, $currentCourseVersion ) )
    {
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :
                // drop id to be able to recreate it with correct autoincrement values at last step
                $sql = "ALTER TABLE `" . $currentCourseDbNameGlu . "tracking_event` DROP `id`";
                
                if ( upgrade_sql_query( $sql ) ) $step = set_upgrade_status( $tool, $step+1 );
                else return $step ;
    
                unset( $sql );
                
            case 2 :
                // get total number of rows in track_e_access
                $sql = "SELECT COUNT(*)
                            FROM `". $currentCourseDbNameGlu . "track_e_access`";
                $tableRows = (int) claro_sql_query_fetch_single_value($sql);
                
                $recoveredOffset = UpgradeTrackingOffset::retrieve();
                // get a subgroup of 250 rows and insert group by group in tracking_event table
                for( $offset = $recoveredOffset; $offset < $tableRows; $offset += 250 )
                {
                    // we have to store offset to start again from it if something failed
                    UpgradeTrackingOffset::store($offset);
                    
                    $query = "SELECT `access_id`, `access_user_id`, `access_date`, `access_tid`
                                FROM `". $currentCourseDbNameGlu . "track_e_access`
                            ORDER BY `access_date`, `access_id`
                               LIMIT ".$offset.", 250";
                    // then copy these 250 rows to tracking_event
                    $eventList = claro_sql_query_fetch_all_rows( $query );
    
                    // build query to insert all 250 rows
                    $sql = "INSERT INTO `" . $currentCourseDbNameGlu . "tracking_event`
                            ( `user_id`, `tool_id`, `date`, `type`, `data` )
                            VALUES
                            ";
                    //inject former data into new table structure
                    foreach( $eventList as $event )
                    {
                        $user_id = !is_null( $event['access_user_id'] ) ? $event['access_user_id'] : "null";
                        $tool_id = !is_null( $event['access_tid'] ) ? $event['access_tid'] : "null";
                        $date = $event['access_date'];
                        $type = !is_null( $event['access_tid'] ) ? 'tool_access' : 'course_access';
                        $data = '';
                        
                        $sql .= "("
                             . $user_id . ","
                             . $tool_id . ","
                             . "'" . claro_sql_escape( $date ) . "',"
                             . "'" . claro_sql_escape( $type ) . "',"
                             . "'" . claro_sql_escape( $data ) . "'),\n";
                    }
                    unset( $eventList );
                    
                    if ( upgrade_sql_query( rtrim($sql,",\n") ) )
                    {
                        unset($sql);
                        //continue;
                    }
                    else
                    {
                        return $step ;
                    }
                }
    
                UpgradeTrackingOffset::reset();
                $step = set_upgrade_status( $tool, $step+1 );
    
            case 3 :
                // get total number of rows in track_e_downloads
                $sql = "SELECT COUNT(*)
                            FROM `". $currentCourseDbNameGlu . "track_e_downloads`";
                $tableRows = (int) claro_sql_query_fetch_single_value($sql);
                
                $recoveredOffset = UpgradeTrackingOffset::retrieve();
                // get a subgroup of 250 rows and insert group by group in tracking_event table
                for( $offset = $recoveredOffset; $offset < $tableRows; $offset += 250 )
                {
                    // we have to store offset to start again from it if something failed
                    UpgradeTrackingOffset::store($offset);
                    
                    $query = "SELECT `down_id`, `down_user_id`, `down_date`, `down_doc_path`
                                FROM `". $currentCourseDbNameGlu . "track_e_downloads`
                            ORDER BY `down_date`, `down_id`
                               LIMIT ".$offset.", 250";
                    // then copy these 250 rows to tracking_event
                    $eventList = claro_sql_query_fetch_all_rows( $query );
    
                    // build query to insert all 250 rows
                    $sql = "INSERT INTO `" . $currentCourseDbNameGlu . "tracking_event`
                            ( `user_id`, `tool_id`, `date`, `type`, `data` )
                            VALUES
                            ";
                    //inject former data into new table structure
                    foreach( $eventList as $event )
                    {
                        $user_id = !is_null( $event['down_user_id'] ) ? $event['down_user_id'] : "null";
                        $tool_id = "null";
                        $date = $event['down_date'];
                        $type = 'download';
                        $data = serialize( array( 'url' => $event['down_doc_path'] ) );
                            
                        $sql .= "("
                             . $user_id . ","
                             . $tool_id . ","
                             . "'" . claro_sql_escape( $date ) . "',"
                             . "'" . claro_sql_escape( $type ) . "',"
                             . "'" . claro_sql_escape( $data ) . "'),\n";
                    }
                    unset( $eventList );
                    
                    if ( upgrade_sql_query( rtrim($sql,",\n") ) )
                    {
                        unset($sql);
                        //continue;
                    }
                    else
                    {
                        return $step ;
                    }
                }
    
                UpgradeTrackingOffset::reset();
                $step = set_upgrade_status( $tool, $step+1 );
                
            case 4 :
                // order table using dates then recreate primary key with correct autoincrement value
                $sqlForUpdate[] = "ALTER TABLE `" . $currentCourseDbNameGlu . "tracking_event`  ORDER BY `date`";
                $sqlForUpdate[] = "ALTER TABLE `" . $currentCourseDbNameGlu . "tracking_event` ADD `id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
                
                if ( upgrade_apply_sql( $sqlForUpdate ) ) $step = set_upgrade_status( $tool, $step+1 );
                else return $step ;
                
            case 5 :
                //drop deprecated tracking tables and temporary table
                $sqlForUpdate[] = "DROP TABLE IF EXISTS `" . $currentCourseDbNameGlu . "track_e_uploads`";
                $sqlForUpdate[] = "DROP TABLE IF EXISTS `" . $currentCourseDbNameGlu . "track_e_access`";
                $sqlForUpdate[] = "DROP TABLE IF EXISTS `" . $currentCourseDbNameGlu . "track_e_downloads`";
                
                if ( upgrade_apply_sql( $sqlForUpdate ) ) $step = set_upgrade_status( $tool, $step+1 );
                else return $step ;
                unset( $sqlForUpdate );
            
            default :
                $step = set_upgrade_status( $tool, 0 );
                return $step;
        }
    }
    return false;
}

function forum_upgrade_to_19( $course_code )
{
    $versionRequiredToProcedd = '/^1.8/';
    $tool = 'CLFRM';
    
    global $currentCourseVersion;
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);
    
    if( preg_match($versionRequiredToProcedd,$currentCourseVersion) )
    {
        switch( $step = get_upgrade_status($tool, $course_code) )
        {
            case 1 :
                $sql = "CREATE TABLE IF NOT EXISTS `" . $currentCourseDbNameGlu . "bb_rel_forum_userstonotify` (
                        `notify_id` int(10) NOT NULL auto_increment,
                        `user_id` int(10) NOT NULL default '0',
                        `forum_id` int(10) NOT NULL default '0',
                        PRIMARY KEY  (`notify_id`),
                        KEY `SECONDARY` (`user_id`,`forum_id`)
                    ) ENGINE=MyISAM;";
                
                if( upgrade_sql_query($sql) ) $step = set_upgrade_status($tool, $step+1, $course_code);
                else return $step;
            default :
                $step = set_upgrade_status($tool, 0, $course_code);
                return $step;
                
        }
    }
    
    return false;
}

