<?php // $Id: upgrade_course_18.lib.php 13348 2011-07-18 13:58:28Z abourguignon $

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * Function to update course tool 1.7 to 1.8.
 * - READ THE SAMPLE AND COPY PASTE IT
 * - ADD TWICE MORE COMMENT THAT YOU THINK NEEDED
 *
 * This code would be splited by task for the 1.8 Stable but code inside
 * function won't change, so let's go to write it.
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
 Upgrade to claroline 1.8
 ===========================================================================*/

/**
 * Upgrade course repository files and script to 1.8
 */

function course_repository_upgrade_to_18 ($course_code)
{
    global $currentCourseVersion, $currentcoursePathSys;

    $versionRequiredToProceed = '/^1.7/';
    $tool = 'CLINDEX';

    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :
                
                if ( is_writable($currentcoursePathSys) )
                {
                    if ( !is_dir($currentcoursePathSys) )
                        claro_mkdir($currentcoursePathSys);
                    if ( !is_dir($currentcoursePathSys.'/chat') )
                        claro_mkdir($currentcoursePathSys.'/chat');
                    if ( !is_dir($currentcoursePathSys.'/modules') )
                        claro_mkdir($currentcoursePathSys.'/modules');
                    if ( !is_dir($currentcoursePathSys.'/scormPackages') )
                        claro_mkdir($currentcoursePathSys . '/scormPackages');
            
                    $step = set_upgrade_status($tool, 2, $course_code);
                }
                else
                {
                    log_message(sprintf('Repository %s not writable', $currentcoursePathSys));
                    return $step;
                }

            case 2 :

                // build index.php of course
                $fd = fopen($currentcoursePathSys . '/index.php', 'w');
        
                if (!$fd) return $step ;

                // build index.php
                $string = '<?php ' . "\n"
                    . 'header (\'Location: '. $GLOBALS['urlAppend'] . '/claroline/course/index.php?cid=' . rawurlencode($course_code) . '\') ;' . "\n"
                    . '?' . '>' . "\n" ;

                if ( ! fwrite($fd, $string) ) return $step;
                if ( ! fclose($fd) )          return $step;
                    
                $step = set_upgrade_status($tool, 0, $course_code);

            default :
                return $step;
        }
    }
    return false ;
}

/**
 * Upgrade foo tool to 1.8
 *
 * explanation of task
 *
 * @param $course_code string
 * @return boolean whether true if succeed
 */

function group_upgrade_to_18($course_code)
{
    global $currentCourseVersion;

    $versionRequiredToProceed = '/^1.7/';
    $tool = 'CLGRP';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);

    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        // On init , $step = 1
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :

                $sql_step1 = " CREATE TABLE
                        `".$currentCourseDbNameGlu."course_properties`
                        (
                            `id` int(11) NOT NULL auto_increment,
                            `name` varchar(255) NOT NULL default '',
                            `value` varchar(255) default NULL,
                            `category` varchar(255) default NULL,
                            PRIMARY KEY  (`id`)
                        ) ENGINE=MyISAM ";

                if ( upgrade_sql_query($sql_step1) )
                {
                    $step = set_upgrade_status($tool, 2, $course_code);
                }
                else
                {
                    return $step;
                }

            case 2 :

                $sql = "SELECT self_registration,
                               private,
                               nbGroupPerUser,
                               forum,
                               document,
                               wiki,
                               chat
                    FROM `".$currentCourseDbNameGlu."group_property`";

                $groupSettings = claro_sql_query_get_single_row($sql);

                if ( is_array($groupSettings) )
                {
                    $sql = "INSERT
                            INTO `".$currentCourseDbNameGlu."course_properties`
                                   (`name`, `value`, `category`)
                            VALUES
                            ('self_registration', '".$groupSettings['self_registration']."', 'GROUP'),
                            ('nbGroupPerUser',    '".$groupSettings['nbGroupPerUser'   ]."', 'GROUP'),
                            ('private',           '".$groupSettings['private'          ]."', 'GROUP'),
                            ('CLFRM',             '".$groupSettings['forum'            ]."', 'GROUP'),
                            ('CLDOC',             '".$groupSettings['document'         ]."', 'GROUP'),
                            ('CLWIKI',            '".$groupSettings['wiki'             ]."', 'GROUP'),
                            ('CLCHT',             '".$groupSettings['chat'             ]."', 'GROUP')";
                }

                if ( upgrade_sql_query($sql) )
                {
                    $step = set_upgrade_status($tool, 3, $course_code);
                }
                else
                {
                    return $step;
                }

            case 3 :

                $sql = "DROP TABLE IF EXISTS`".$currentCourseDbNameGlu."group_property`";

                if ( upgrade_sql_query($sql) )
                {
                    $step = set_upgrade_status($tool, 4, $course_code);
                }
                else
                {
                    return $step;
                }

            case 4 :

                $sql = "UPDATE `".$currentCourseDbNameGlu."group_team`
                        SET `maxStudent` = NULL
                        WHERE `maxStudent` = 0 ";

                if ( upgrade_sql_query($sql) )
                {
                    $step = set_upgrade_status($tool, 0, $course_code);
                }
                else
                {
                    return $step;
                }


            default :
                return $step;
        }
    }

    return false;
}

/**
 * Upgrade foo tool to 1.8
 *
 * explanation of task
 *
 * @param $course_code string
 * @return boolean whether true if succeed
 */

function tool_list_upgrade_to_18 ($course_code)
{
    global $currentCourseVersion;

    $versionRequiredToProceed = '/^1.7/';
    $tool = 'TOOLLIST';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);

    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        // On init , $step = 1
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :

                $sql_step1 = "ALTER IGNORE TABLE `" . $currentCourseDbNameGlu . "tool_list` ADD `visibility` tinyint(4) default 0 ";

                if ( upgrade_sql_query($sql_step1) )
                {
                    $step = set_upgrade_status($tool, 2, $course_code);
                }
                else
                {
                    return $step;
                }

            case 2 :
                
                $sql_step2 = "DELETE FROM `" . $currentCourseDbNameGlu . "tool_list`
                              WHERE `access` <> 'ALL' AND `access` <> 'COURSE_ADMIN' ";

                if ( upgrade_sql_query($sql_step2) )
                {
                    $step = set_upgrade_status($tool, 3, $course_code);
                }
                else
                {
                    return $step;
                }

            case 3 :

                $sql_step3 = "UPDATE `" . $currentCourseDbNameGlu . "tool_list`
                      SET `visibility` = 1
                      WHERE `access` = 'ALL' ";

                if ( upgrade_sql_query($sql_step3) )
                {
                    $step = set_upgrade_status($tool, 4, $course_code);
                }
                else
                {
                    return $step;
                }
    
            case 4 :

                $sql_step4 = "ALTER IGNORE TABLE `" . $currentCourseDbNameGlu . "tool_list` DROP column `access` ";

                if ( upgrade_sql_query($sql_step4) )
                {
                    $step = set_upgrade_status($tool, 0, $course_code);
                }
                else
                {
                    return $step;
                }

            default :

                $step = set_upgrade_status($tool, 0, $course_code);
                return $step;
        }
    }
    return false;
}

/**
 * Upgrade foo tool to 1.8
 *
 * explanation of task
 *
 * @param $course_code string
 * @return boolean whether true if succeed
 */

function quiz_upgrade_to_18 ($course_code)
{
    global $currentCourseVersion, $currentcoursePathSys;

    $versionRequiredToProceed = '/^1.7/';
    $tool = 'CLQWZ';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);

    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        // On init , $step = 1
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :
                
                $sql_step1[] = "CREATE TABLE `". $currentCourseDbNameGlu . "qwz_exercise` (
                    `id` int(11) NOT NULL auto_increment,
                    `title` varchar(255) NOT NULL,
                    `description` text NOT NULL,
                    `visibility` enum('VISIBLE','INVISIBLE') NOT NULL default 'INVISIBLE',
                    `displayType` enum('SEQUENTIAL','ONEPAGE') NOT NULL default 'ONEPAGE',
                    `shuffle` smallint(6) NOT NULL default '0',
                    `showAnswers` enum('ALWAYS','NEVER','LASTTRY') NOT NULL default 'ALWAYS',
                    `startDate` datetime NOT NULL,
                    `endDate` datetime NOT NULL,
                    `timeLimit` smallint(6) NOT NULL default '0',
                    `attempts` tinyint(4) NOT NULL default '0',
                    `anonymousAttempts` enum('ALLOWED','NOTALLOWED') NOT NULL default 'NOTALLOWED',
                    PRIMARY KEY  (`id`)
                    ) ENGINE=MyISAM ";

                $sql_step1[] = "CREATE TABLE `". $currentCourseDbNameGlu . "qwz_question` (
                    `id` int(11) NOT NULL auto_increment,
                    `title` varchar(255) NOT NULL default '',
                    `description` text NOT NULL,
                    `attachment` varchar(255) NOT NULL default '',
                    `type` enum('MCUA','MCMA','TF','FIB','MATCHING') NOT NULL default 'MCUA',
                    `grade` float NOT NULL default '0',
                    PRIMARY KEY  (`id`)
                    ) ENGINE=MyISAM ";

                $sql_step1[] = "CREATE TABLE `" . $currentCourseDbNameGlu . "qwz_rel_exercise_question` (
                    `exerciseId` int(11) NOT NULL,
                    `questionId` int(11) NOT NULL,
                    `rank` int(11) NOT NULL default '0'
                    ) ENGINE=MyISAM ";

                $sql_step1[] = "CREATE TABLE `" . $currentCourseDbNameGlu . "qwz_answer_truefalse` (
                    `id` int(11) NOT NULL auto_increment,
                    `questionId` int(11) NOT NULL,
                    `trueFeedback` text NOT NULL,
                    `trueGrade` float NOT NULL,
                    `falseFeedback` text NOT NULL,
                    `falseGrade` float NOT NULL,
                    `correctAnswer` enum('TRUE','FALSE') NOT NULL,
                    PRIMARY KEY  (`id`)
                    ) ENGINE=MyISAM ";

                $sql_step1[] = "CREATE TABLE `" . $currentCourseDbNameGlu . "qwz_answer_multiple_choice` (
                    `id` int(11) NOT NULL auto_increment,
                    `questionId` int(11) NOT NULL,
                    `answer` text NOT NULL,
                    `correct` tinyint(4) NOT NULL,
                    `grade` float NOT NULL,
                    `comment` text NOT NULL,
                    PRIMARY KEY  (`id`)
                    ) ENGINE=MyISAM ";

                $sql_step1[] = "CREATE TABLE `" . $currentCourseDbNameGlu . "qwz_answer_fib` (
                    `id` int(11) NOT NULL auto_increment,
                    `questionId` int(11) NOT NULL,
                    `answer` text NOT NULL,
                    `gradeList` text NOT NULL,
                    `wrongAnswerList` text NOT NULL,
                    `type` tinyint(4) NOT NULL,
                    PRIMARY KEY  (`id`)
                    ) ENGINE=MyISAM ";

                $sql_step1[] = "CREATE TABLE `" . $currentCourseDbNameGlu . "qwz_answer_matching` (
                    `id` int(11) NOT NULL auto_increment,
                    `questionId` int(11) NOT NULL,
                    `answer` text NOT NULL,
                    `match` varchar(32) default NULL,
                    `grade` float NOT NULL default '0',
                    `code` varchar(32) default NULL,
                    PRIMARY KEY  (`id`)
                    ) ENGINE=MyISAM ";

                if ( upgrade_apply_sql($sql_step1) )
                {
                    $step = set_upgrade_status($tool, 2, $course_code);
                }
                else
                {
                    return $step;
                }
                
        // handle exercises and questions
        case 2 :
                // add old exercise data in new exercise table
                $sql_step2[] = "INSERT IGNORE INTO `". $currentCourseDbNameGlu . "qwz_exercise`
                 (id,title,description,visibility,displayType,shuffle,showAnswers,startDate,endDate,timeLimit,attempts,anonymousAttempts)
                 SELECT id, titre, description, IF(active,'VISIBLE','INVISIBLE'),IF(type = 1,'ONEPAGE','SEQUENTIAL'),random,show_answer,start_date,end_date,max_time,max_attempt,IF(anonymous_attempts = 'YES','ALLOWED','NOTALLOWED')
                    FROM `".$currentCourseDbNameGlu."quiz_test`";
                
                // add old question data in new question table
                $sql_step2[] = "INSERT IGNORE INTO `". $currentCourseDbNameGlu . "qwz_question`
                 (id,title,description,attachment,type,grade)
                 SELECT id,question,description,attached_file,
                         CASE type WHEN 1 THEN 'MCUA' WHEN 2 THEN 'MCMA' WHEN 3 THEN 'FIB' WHEN 4 THEN 'MATCHING' WHEN 5 THEN 'TF' END,
                         ponderation
                    FROM `".$currentCourseDbNameGlu."quiz_question`";
                
                // add relations between exercises and questions and recalculate rank
                $sql = "SELECT exercice_id, question_id, q_position
                    FROM `".$currentCourseDbNameGlu."quiz_rel_test_question` AS RTQ, `".$currentCourseDbNameGlu."quiz_question` AS Q
                    WHERE RTQ.question_id = Q.id
                    ORDER BY exercice_id ASC, q_position ASC";
                $result = claro_sql_query($sql);
                
                if( ! $result ) return $step;
                
                $sql_upgrade_qwz_rel = "INSERT INTO `". $currentCourseDbNameGlu . "qwz_rel_exercise_question`
                            ( exerciseId, questionId, rank )
                            VALUES
                            ";
                            
                $rankList = array();
                while ( ( $row = mysql_fetch_array($result) ) )
                {
                    if( isset($rankList[$row['exercice_id']]) )
                    {
                        $rankList[$row['exercice_id']]++;
                    }
                    else
                    {
                        $rankList[$row['exercice_id']] = 1;
                    }
                    
                    $sql_upgrade_qwz_rel_values[] = "(".$row['exercice_id'].",".$row['question_id'].",".$rankList[$row['exercice_id']].")";
                }
                
                if( !empty($sql_upgrade_qwz_rel_values) )
                {
                    $sql_step2[] = $sql_upgrade_qwz_rel . implode(",",$sql_upgrade_qwz_rel_values);
                }
                
                if ( upgrade_apply_sql($sql_step2) )
                {
                    $step = set_upgrade_status($tool, 3, $course_code);
                }
                else
                {
                    return $step;
                }
                
        // handle answers
        case 3 :
                // add MCMA AND MCUA answers (let id auto increment)
                $sql_step3[] = "INSERT IGNORE INTO `". $currentCourseDbNameGlu . "qwz_answer_multiple_choice`
                 (questionId,answer,correct,grade,comment)
                 SELECT A.question_id,A.reponse,A.correct,A.ponderation,A.comment
                    FROM `".$currentCourseDbNameGlu."quiz_answer` AS A, `".$currentCourseDbNameGlu."quiz_question` AS Q
                    WHERE A.question_id = Q.id
                     AND ( Q.type = 1 OR Q.type = 2 )"; // Q.type = mcma or mcua

                // add FIB answers
                $sql = "SELECT Q.id, A.reponse
                    FROM `".$currentCourseDbNameGlu."quiz_answer` AS A, `".$currentCourseDbNameGlu."quiz_question` AS Q
                    WHERE A.question_id = Q.id
                     AND Q.type = 3"; // Q.type = FIB

                $result = claro_sql_query($sql);

                if ( ! $result ) return $step;

                while ( ( $row = mysql_fetch_array($result) ) )
                {
                    $reponse = explode( '::',$row['reponse']);
                    
                     $answer = (isset($reponse[0]))?$reponse[0]:'';
                        $gradeList = (isset($reponse[1]))?$reponse[1]:'';
                    $type = (!empty($reponse[2]))?$reponse[2]:1;
                    $wrongAnswerList = (isset($reponse[3]))?$reponse[3]:'';

                    $wrongAnswerList = str_replace(',','&#44;',$wrongAnswerList);
                    $wrongAnswerList = str_replace('[',',',$wrongAnswerList);
                    
                    $sql = "INSERT INTO `" . $currentCourseDbNameGlu . "qwz_answer_fib`
                            (`questionId`,`answer`, `gradeList`,`wrongAnswerList`,`type`)
                            VALUES
                            ('" . $row['id'] . "',
                             '" . claro_sql_escape($answer) . "',
                             '" . claro_sql_escape($gradeList) . "',
                             '" . claro_sql_escape($wrongAnswerList) . "',
                             '" . claro_sql_escape($type) . "'
                            )";

                    if ( ! upgrade_sql_query($sql) )
                    {
                        return $step;
                    }
                }
                
                // add MATCHING answers

                $answerList = array();

                $sql = "SELECT A.id, A.question_id, A.reponse, A.correct, A.ponderation
                    FROM `".$currentCourseDbNameGlu."quiz_answer` AS A, `".$currentCourseDbNameGlu."quiz_question` AS Q
                    WHERE A.question_id = Q.id
                     AND Q.type = 4"; // Q.type = MATCHING

                $result = claro_sql_query($sql);

                if ( ! $result ) return $step;
                
                while ( ( $row = mysql_fetch_array($result) ) )
                {
                    $answerId = $row['question_id'].'-'.$row['id'];
                       $code = md5(uniqid(''));

                       $answerList[$answerId]['questionId'] = $row['question_id'];
                       $answerList[$answerId]['answer'] = $row['reponse'];
                       $answerList[$answerId]['code'] = $code;
                       
                       // if answer is a rightProposal
                    if( $row['correct'] == 0 )
                    {
                        $answerList[$answerId]['match'] = 0;
                        $answerList[$answerId]['grade'] = 0;
                    }
                    else // if answer is a leftProposal
                    {
                        $answerList[$answerId]['match'] = $row['correct'];
                        $answerList[$answerId]['grade'] = $row['ponderation'];
                    }
                }
                
                foreach( $answerList as $answerId => $answer )
                {
                    if( $answer['match'] != 0 )
                    {
                        // find the matching right proposal code for all left proposals
                        $matchingAnswerId = $answer['questionId'].'-'.$answer['match'];

                        if( isset($answerList[$matchingAnswerId]['code']) )
                        {
                            $answer['match'] = $answerList[$matchingAnswerId]['code'];
                        }
                    }
                    // else right proposal, leave match to 'NULL' value
                    
                    
                    $sql = "INSERT INTO `" . $currentCourseDbNameGlu . "qwz_answer_matching`
                            (`questionId`,`answer`, `match`,`grade`,`code`)
                            VALUES
                            ('" . $answer['questionId'] . "',
                             '" . claro_sql_escape($answer['answer']) . "',
                             " . ($answer['match']==0?'NULL':"'".$answer['match']."'"). ",
                             '" . $answer['grade'] . "',
                             '" . $answer['code'] . "'
                            )";

                    if ( ! upgrade_sql_query($sql) )
                    {
                        return $step;
                    }
                }

                // add TF answers

                $answerList = array();

                $sql = "SELECT A.id, A.question_id, A.reponse, A.correct, A.comment, A.ponderation
                    FROM `".$currentCourseDbNameGlu."quiz_answer` AS A, `".$currentCourseDbNameGlu."quiz_question` AS Q
                    WHERE A.question_id = Q.id
                     AND Q.type = 5"; // Q.type = TF
                     
                $result = claro_sql_query($sql);

                if ( ! $result ) return $step;
                
                // build an answer array that looks like the new db format
                while ( ( $row = mysql_fetch_array($result) ) )
                {
                    $answerId = $row['question_id'];
                        
                    $answerList[$answerId]['questionId'] = $answerId;
                    
                    if( $row['id'] == '1' )
                    {
                        // 'True'
                        $answerList[$answerId]['trueFeedback'] = $row['comment'];
                        $answerList[$answerId]['trueGrade'] = $row['ponderation'];
                        $answerList[$answerId]['correctAnswer'] = ($row['correct'] == 1)?'TRUE':'FALSE';
                    }
                    else
                    {
                        // $row['id'] = 2 so 'False'
                        $answerList[$answerId]['falseFeedback'] = $row['comment'];
                        $answerList[$answerId]['falseGrade'] = $row['ponderation'];
                        $answerList[$answerId]['correctAnswer'] = ($row['correct'] == 1)?'FALSE':'TRUE';
                    }
                }

                foreach( $answerList as $answerId => $answer)
                {
                    
                    $sql = "INSERT INTO `" . $currentCourseDbNameGlu . "qwz_answer_truefalse`
                            (`questionId`,`trueFeedback`, `trueGrade`,`falseFeedback`,`falseGrade`,`correctAnswer`)
                            VALUES
                            ('" . $answer['questionId'] . "',
                             '" . claro_sql_escape($answer['trueFeedback']) . "',
                             '" . $answer['trueGrade'] . "',
                             '" . claro_sql_escape($answer['falseFeedback']) . "',
                             '" . $answer['falseGrade'] . "',
                             '" . $answer['correctAnswer'] . "'
                            )";

                    if ( ! upgrade_sql_query($sql) )
                    {
                        return $step;
                    }
                }
                                               
                if ( upgrade_apply_sql($sql_step3) )
                {
                    $step = set_upgrade_status($tool, 4, $course_code);
                }
                else
                {
                    return $step;
                }
                
        case 4 :
                // move attached files
                $sql = "SELECT id, attached_file
                    FROM `".$currentCourseDbNameGlu."quiz_question`";

                $result = claro_sql_query($sql);
    
                if ( ! $result ) return $step;

                while ( ( $row = mysql_fetch_array($result) ) )
                {
                    // create new folder
                    $exe_dirname = $currentcoursePathSys.'exercise'; // is also the dir where file where in previous versions

                    if ( !is_dir($exe_dirname) )
                    {
                        if ( !@mkdir($exe_dirname, CLARO_FILE_PERMISSIONS) )
                        {
                            log_message('Error: Cannot create ' . $exe_dirname );
                            return $step;
                        }
                    }

                    $question_dirname = $exe_dirname . '/question_'.$row['id'];
                    
                    if ( !is_dir($question_dirname) )
                    {
                        if ( !@mkdir($question_dirname, CLARO_FILE_PERMISSIONS) )
                        {
                            log_message('Error: Cannot create ' . $question_dirname );
                            return $step;
                        }
                    }

                    // move file
                    $filename = $row['attached_file'];
                    
                    if( !empty($filename) && file_exists($exe_dirname.'/'.$filename) )
                    {
                        if ( @rename($exe_dirname.'/'.$filename,$question_dirname.'/'.$filename) === FALSE )
                        {
                            log_message('Error: Cannot rename ' . $exe_dirname . '/' . $filename . ' to ' . $question_dirname . '/' . $filename );
                            return $step;
                        }
                    }
                }
                
                
                $step = set_upgrade_status($tool, 5, $course_code);

        case 5 :
                
                $sql_step5 = "DROP TABLE `".$currentCourseDbNameGlu."quiz_answer`,
                                         `".$currentCourseDbNameGlu."quiz_question`,
                                         `".$currentCourseDbNameGlu."quiz_rel_test_question`,
                                         `".$currentCourseDbNameGlu."quiz_test`";

                if ( upgrade_sql_query($sql_step5) )
                {
                    $step = set_upgrade_status($tool, 0, $course_code);
                }
                else
                {
                    return $step;
                }

        default :
                return $step;
        }
    }

    return false;
}

/**
 * Function to upgrade tool intro
 */

function tool_intro_upgrade_to_18 ($course_code)
{
    global $currentCourseVersion, $currentcoursePathSys;

    $versionRequiredToProceed = '/^1.7/';
    $tool = 'CLINTRO';
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);

    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :

                // remove tool intro
                $sql = "DELETE FROM `".$currentCourseDbNameGlu."tool_intro`
                        WHERE tool_id > 0" ;

                if ( upgrade_sql_query($sql) ) $step = set_upgrade_status($tool, 0, $course_code);
                else return $step;

            default :
                return $step;
        }
    }

    return false;
}

/**
 * Upgrade forum tool to 1.8
 */

function forum_upgrade_to_18($course_code)
{
    $versionRequiredToProceed = '/^1.7/';
    $tool = 'CLFRM';
    
    global $currentCourseVersion;
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);

    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :

                // update type of cat_order (fix bug 740)
                $sql = "ALTER IGNORE TABLE `".$currentCourseDbNameGlu."bb_categories`
                        CHANGE `cat_order` `cat_order` int(10)" ;

                if ( upgrade_sql_query($sql) ) $step = set_upgrade_status($tool, 0, $course_code);
                else return $step;

            default :
                return $step;
        }
    }

    return false;
}

/**
 * Upgrade tracking tool to 1.8
 */

function tracking_upgrade_to_18($course_code)
{
    $versionRequiredToProceed = '/^1.7/';
    $tool = 'CLSTATS';
    
    global $currentCourseVersion;
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);

    if ( preg_match($versionRequiredToProceed,$currentCourseVersion) )
    {
        switch( $step = get_upgrade_status($tool,$course_code) )
        {
            case 1 :

                $sql = "UPDATE `".$currentCourseDbNameGlu."track_e_access`
                        SET access_tlabel = TRIM(TRAILING '_' FROM access_tlabel)";
                
                if ( upgrade_sql_query($sql) ) $step = set_upgrade_status($tool, 2, $course_code);
                else return $step;

            case 2 :

                $sql = "ALTER IGNORE TABLE `".$currentCourseDbNameGlu."track_e_exercices`
                        CHANGE `exe_exo_id` `exe_exo_id` int(11)";
                
                if ( upgrade_sql_query($sql) ) $step = set_upgrade_status($tool, 0, $course_code);
                else return $step;

            default :
                return $step;
        }
    }

    return false;
}