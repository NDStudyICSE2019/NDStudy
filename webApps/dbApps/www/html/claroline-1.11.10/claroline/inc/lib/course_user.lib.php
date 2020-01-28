<?php // $Id: course_user.lib.php 14516 2013-08-14 07:46:57Z zefredz $

require_once dirname(__FILE__) . '/auth/authprofile.lib.php';
require_once dirname(__FILE__) . '/course/userprivileges.lib.php';
require_once dirname(__FILE__) . '/users/courseregistration.lib.php';

/**
 * CLAROLINE
 *
 * Course user library contains function to manage users registration and properties in course
 *
 * @version     $Revision: 14516 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLUSR
 * @author      Claro Team <cvs@claroline.net>
 * @author      Christophe Gesche <moosh@claroline.net>
 * @author      Mathieu Laurent <laurent@cerdecam.be>
 * @author      Hugues Peeters <hugues.peeters@advalvas.be>
 */

/**
 * Subscribe a specific user to a specific course.  If this course is a session
 * course, the user will also be subscribed to the source course.
 * 
 * Helper for Claro_CourseUserRegistration
 *
 * @param int $userId user ID from the course_user table
 * @param string $courseCode course code from the cours table
 * @param boolean $admin
 * @param boolean $tutor
 * @param int $class_id
 * @return boolean TRUE  if it succeeds, FALSE otherwise
 */

function user_add_to_course(
    $userId, $courseCode, $admin = false, $tutor = false,
    $class_id = null )
{
    $courseObj = new Claro_Course($courseCode);
    $courseObj->load();
    
    $courseRegistration = new Claro_CourseUserRegistration(
        AuthProfileManager::getUserAuthProfile($userId),
        $courseObj,
        null,
        null
    );
    
    if ( $admin )
    {
        $courseRegistration->setCourseAdmin();
    }
    
    if ( $tutor )
    {
        $courseRegistration->setCourseTutor();
    }
    
    if ( $class_id )
    {
        $claroClass = new Claro_Class();
        $claroClass->load($class_id);
        $courseRegistration->setClass( $claroClass );
    }
    
    $courseRegistration->ignoreRegistrationKeyCheck();
    
    if ( $courseRegistration->addUser() )
    {
        return true;
    }
    else
    {
        // @todo should throw an exception here
        Console::error(
            "Cannot register user {$userId} in course {$courseCode} ["
            . $courseRegistration->getStatus() . ":"
            . $courseRegistration->getErrorMessage()."]" );
            
        return false;
    }
}

/**
 * Check if the registration flag of the given course set to "open"
 * or "validation", if the course statut is "enable" or "date"
 * and, in this case, if we are in the date limits.
 * @author Hugues Peeters <hugues.peeters@advalvas.be>
 * @param string $courseId - sys code of the course
 * @return boolean
 */

function is_course_registration_allowed($courseId)
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tbl_course = $tbl_mdb_names['course'];
    
    $curdate = date('Y-m-d H:i:s', time());
    
    $sql = "
        SELECT count(*) AS registration_allowed
        FROM `" . $tbl_course . "` AS `course`
        WHERE `course`.`code` = '" . claro_sql_escape($courseId) . "'
        AND (
              `course`.`registration` = 'open'
              OR
              `course`.`registration` = 'validation'
            )
        AND ( `course`.`status` = 'enable'
                OR (
                     `course`.`status` = 'date'
                        AND (
                              `course`.`creationDate` < '". $curdate ."'
                              OR
                              `course`.`creationDate` IS NULL OR UNIX_TIMESTAMP( `course`.`creationDate` ) = 0
                            )
                        AND (
                              '". $curdate ."' < `course`.`expirationDate`
                              OR
                              `course`.`expirationDate` IS NULL
                            )
                    )
            )";
    
    $courseRegistrationList = claro_sql_query_get_single_value($sql);
    
    return (bool) ($courseRegistrationList) ;
}

/**
 * @author Hugues Peeters <hugues.peeters@advalvas.be>
 * @param string $courseId - sys code of the course
 * @return string registration key
 */

function get_course_registration_key($courseId)
{
    $tbl = claro_sql_get_main_tbl();


    $sql = " SELECT registrationKey
             FROM `" . $tbl['course'] . "`
             WHERE  code = '" . claro_sql_escape($courseId) . "'";

    $registrationKey = claro_sql_query_get_single_value($sql);

    if ( ! is_null($registrationKey) || ! empty($registrationKey) )
    {
        return $registrationKey;
    }
    else
    {
        return null;
    }
}

/**
 * unsubscribe a specific user from a specific course
 *
 *
 * @param  int     $user_id        user ID from the course_user table
 * @param  mixed (string or array) $courseCodeList course sys code
 * @param  boolean $force  true  possible to remove a course admin from course
 *                        (default false)
 * @param  boolean $deleteTrackingData (default false)
 *
 * @return boolean TRUE        if unsubscribtion succeed
 *         boolean FALSE       otherwise.
 */
function user_remove_from_course( $userId, $courseCodeList = array(), $force = false, $delTrackData = false, $class_id = null)
{
    if ( ! is_array($courseCodeList) ) $courseCodeList = array($courseCodeList);
    
    if ( $class_id )
    {
        $claroClass = new Claro_Class();
        $claroClass->load($class_id);
    }
    else
    {
        $claroClass = null;
    }
    
    $allWorksWell = true;
    
    foreach ( $courseCodeList as $courseCode )
    {
        $course = new Claro_Course( $courseCode );
        $course->load();
        
        $userCourseRegistration = new Claro_CourseUserRegistration(
            AuthProfileManager::getUserAuthProfile($userId),
            $course
        );
        
        if ( $force )
        {
            $userCourseRegistration->forceUnregistrationOfManager();
        }
        
        if ( !is_null( $claroClass ) )
        {
            $userCourseRegistration->setClass( $claroClass );
        }
        
        $keepTrackingData = !$delTrackData;
        
        if ( !$userCourseRegistration->removeUser( $keepTrackingData, array() ) )
        {
            Console::warning("Cannot remove user {$userId} from {$courseCode}");
            $allWorksWell = false;
        }
    }
    
    return $allWorksWell;
}

/**
 * delete a list of user id from a list of courses
 * @param array $userIdList list of user id to remove
 * @param array|string $courseCodeList array of course codes or one single course code
 * @param bool $force forece the deletion
 * @param bool $delTrackData delete tracking data
 * @param bool $unregister_by_class unregister by class
 * @return int number of deleted users
 */
function user_remove_userlist_from_course( $userIdList, $courseCodeList = array(), $force = false, $delTrackData = false, $unregister_by_class = false)
{
    $tbl = claro_sql_get_main_tbl();
    $userDeletedCount = 0;

    if ( ! is_array($courseCodeList) ) $courseCodeList = array($courseCodeList);
    
    foreach ( $userIdList as $userIdToProcess )
    {
        $userId = is_numeric( $userIdToProcess ) ? $userIdToProcess : $userIdToProcess['user_id'];
        
        if ( ! $force && $userId == $GLOBALS['_uid'] )
        {
            // PREVIOUSLY CHECK THE USER IS NOT COURSE ADMIN OF THESE COURSES

            $sql = "SELECT COUNT(user_id)
                    FROM `" . $tbl['rel_course_user'] . "`
                    WHERE user_id = ". (int) $userId ."
                      AND isCourseManager = 1
                      AND code_cours IN ('" . implode("', '", array_map('claro_sql_escape', $courseCodeList) ) . "') ";

            if ( claro_sql_query_get_single_value($sql)  > 0 )
            {
                Claroline::getInstance()->log( 'DELETE_USER_FAILED' , array ('USER' => $userId, 'failure' => 'course_manager_cannot_unsubscribe_himself') );
                continue;
            }
        }

        $sql = "SELECT code_cours , count_user_enrol, count_class_enrol
                FROM `" . $tbl['rel_course_user'] . "`
                WHERE `code_cours` IN ('" . implode("', '", array_map('claro_sql_escape', $courseCodeList) ) . "')
                AND   `user_id` = " . $userId ;

        $userEnrolCourseList = claro_sql_query_fetch_all($sql);

        foreach ( $userEnrolCourseList as $thisUserEnrolCourse )
        {

            $thisCourseCode    = $thisUserEnrolCourse['code_cours'];
            $count_user_enrol  = $thisUserEnrolCourse['count_user_enrol'];
            $count_class_enrol = $thisUserEnrolCourse['count_class_enrol'];

            if ( ( $count_user_enrol + $count_class_enrol ) <= 1 )
            {
                // remove user from course
                if ( user_remove_from_group($userId, $thisCourseCode) == false ) return false;

                $dbNameGlued   = claro_get_course_db_name_glued($thisCourseCode);
                $tbl_cdb_names = claro_sql_get_course_tbl($dbNameGlued);

                $tbl_bb_notify         = $tbl_cdb_names['bb_rel_topic_userstonotify'];
                $tbl_group_team        = $tbl_cdb_names['group_team'         ];
                $tbl_userinfo_content  = $tbl_cdb_names['userinfo_content'   ];

               $sqlList = array();
               $toolCLFRM =  get_module_data('CLFRM');

               if (is_tool_activated_in_course($toolCLFRM['id'],$thisUserEnrolCourse['code_cours']))
               {
                   $sqlList = array(
                        "DELETE FROM `" . $tbl_bb_notify        . "` WHERE user_id = " . (int) $userId );
               }

                array_push($sqlList,
                "DELETE FROM `" . $tbl_userinfo_content . "` WHERE user_id = " . (int) $userId ,
                // change tutor to NULL for the course WHERE the tutor is the user to delete
                "UPDATE `" . $tbl_group_team . "` SET `tutor` = NULL WHERE `tutor`='" . (int) $userId . "'"
                );

                foreach( $sqlList as $thisSql )
                {
                    if ( claro_sql_query($thisSql) == false ) continue;
                }

                if ($delTrackData)
                {
                    if ( user_delete_course_tracking_data($userId, $thisCourseCode) == false) continue;
                }

                $sql = "DELETE FROM `" . $tbl['rel_course_user'] . "`
                    WHERE user_id = " . (int) $userId . "
                      AND code_cours = '" . claro_sql_escape($thisCourseCode) . "'";

                if ( claro_sql_query($sql) == false ) continue;
            }
            else
            {
                // decrement the count of registration by the user or class
                if ( ! $unregister_by_class )  $count_user_enrol--;
                else                           $count_class_enrol--;

                // update enrol count in table rel_course_user

                $sql = "UPDATE `".$tbl['rel_course_user']."`
                          SET `count_user_enrol` = '" . $count_user_enrol . "',
                            `count_class_enrol` = '" . $count_class_enrol . "'
                          WHERE `user_id`   =  " . (int) $userId . "
                          AND  `code_cours` = '" . claro_sql_escape($thisCourseCode) . "'";

                if ( !claro_sql_query($sql) ) continue;

            }
        }
        
        $userDeletedCount++;
    }

    return $userDeletedCount;
}

/**
 * remove a specific user from a course groups
 *
 * TODO : move in group.lib.php
 *
 * @author Hugues Peeters <hugues.peeters@advalvas.be>
 *
 * @param  int     $userId     user ID from the course_user table
 * @param  string  $courseCode course code from the cours table
 *
 * @return boolean TRUE        if removing suceed
 *         boolean FALSE       otherwise.
 */

function user_remove_from_group($userId, $courseId)
{
    $tbl = claro_sql_get_course_tbl(claro_get_course_db_name_glued($courseId));

    $sql = "DELETE FROM `" . $tbl['group_rel_team_user'] . "`
            WHERE user = " . (int) $userId;

    if ( claro_sql_query($sql) ) return true;
    else                         return false;

}

/**
 * remove tracking user data from a course
 *
 * @author Hugues Peeters <hugues.peeters@advalvas.be>
 *
 * @param  int     $userId     user ID from the course_user table
 * @param  string  $courseCode course code from the cours table
 *
 * @return boolean TRUE        if removing suceed
 *         boolean FALSE       otherwise.
 */

function user_delete_course_tracking_data($userId, $courseId)
{
    $dbNameGlued   = claro_get_course_db_name_glued($courseId);
    $tbl_cdb_names = claro_sql_get_course_tbl($dbNameGlued);

    $tbl_tracking_event   = $tbl_cdb_names['tracking_event'];

    $sql = "DELETE FROM `" . $tbl_tracking_event     . "` WHERE user_id = " . (int) $userId ;

    return claro_sql_query($sql);
}

/**
 * change the status of the user in a course
 * @author Hugues Peeters <hugues.peeters@advalvas.be>
 *
 * @param $userId       integer user ID from the course_user table
 * @param $courseId     string course code from the cours table
 * @param $propertyList array should contain 'role', 'profileId', 'isCOurseManager', 'tutor'
 *
 * @return boolean TRUE if update succeed, FALSE otherwise.
 */

function user_set_course_properties($userId, $courseId, $propertyList)
{
    $tbl = claro_sql_get_main_tbl();

    $setList = array();

    if ( array_key_exists('isCourseManager', $propertyList) )
    {
        if ( $propertyList['isCourseManager'] ) $propertyList['profileId'] = claro_get_profile_id('manager') ;
    }

    if ( array_key_exists('profileId', $propertyList) )
    {
        $setList[] = "profile_id = '" . (int) $propertyList['profileId'] . "'";

        if ( $propertyList['profileId'] == claro_get_profile_id('manager') ) $propertyList['isCourseManager'] = 1 ;
        else                                                                 $propertyList['isCourseManager'] = 0 ;
    }

    if ( array_key_exists('isCourseManager', $propertyList) )
    {
        if ( $propertyList['isCourseManager'] ) $setList[] = 'isCourseManager = 1';
        else                                    $setList[] = 'isCourseManager = 0';
    }

    if ( array_key_exists('tutor', $propertyList) )
    {
        if ( $propertyList['tutor'] ) $setList[] = 'tutor = 1';
        else                          $setList[] = 'tutor = 0';
    }

    if ( array_key_exists('role', $propertyList) )
    {
        $setList[] = "role = '" . claro_sql_escape($propertyList['role']) . "'";
    }

    if ( count($setList) > 0 )
    {
        $sql = "UPDATE `" . $tbl['rel_course_user'] . "`
                SET " . implode(', ', $setList) ."
                WHERE   `user_id`    = " . (int) $userId . "
                AND     `code_cours` = '" . claro_sql_escape($courseId) . "'";

        if ( claro_sql_query_affected_rows($sql) > 0 ) return true;
        else                                           return false;
    }

    return false;
}

/**
 * set or unset course manager status for a the user in a course
 *
 * @author Hugues Peeters <hugues.peeters@advalvas.be>
 *
 * @param boolean $status 'true' for course manager, 'false' for not
 * @param integer $user_id user ID from the course_user table
 * @param string  $course_code course code from the cours table
 *
 * @return boolean TRUE  if update succeed
 *         boolean FALSE otherwise.
 */

function user_set_course_manager($status, $userId, $courseId)
{
    return user_set_course_properties($userId, $courseId,
    array('isCourseManager' => $status));
}

/**
 * set or unset course tutor status for a user in a course
 *
 * @author Hugues Peeters <hugues.peeters@advalvas.be>
 *
 * @param boolean $status, 'true' for tutor status, 'false' for not ...
 * @param int $userId user ID from the course_user table
 * @param string $courseId course code from the cours table
 *
 * @return boolean TRUE  if update succeed
 *         boolean FALSE otherwise.
 */

function user_set_course_tutor($status , $userId, $courseId)
{
    $status = ($status == true) ? 1 : 0;

    return user_set_course_properties($userId, $courseId,
    array('tutor' => $status));
}

/**
 * Send enroll to course succeded email to user
 * @author Mathieu Laurent <laurent@cerdecam.be>
 *
 * @param $userId integer
 * @param $data array
 * @return boolean
 */

function user_send_enroll_to_course_mail($userId, $data, $course=null)
{
    require_once dirname(__FILE__) . '/../../messaging/lib/message/messagetosend.lib.php';
    require_once dirname(__FILE__) . '/../../messaging/lib/recipient/singleuserrecipient.lib.php';
    
    $courseData = claro_get_course_data($course);

    $subject  = get_lang('Your registration') ;

    $body = get_block('blockCourseSubscriptionNotification',
        array(
        '%firstname'=> $data['firstname'],
        '%lastname' => $data['lastname'],
        '%courseCode' => $courseData['officialCode'],
        '%courseName' => $courseData['name'],
        '%coursePath' => get_path('rootWeb') . 'claroline/course/index.php?cid=' . $courseData['sysCode'],
        '%siteName'=> get_conf('siteName'),
        '%rootWeb' => get_path('rootWeb'),
        '%administratorName' => get_conf('administrator_name'),
        '%administratorPhone'=> get_conf('administrator_phone'),
        '%administratorEmail'=> get_conf('administrator_email')
        ))
        ;
    
    $message = new MessageToSend(claro_get_current_user_id(),$subject,$body);
    $message->setCourse($courseData['sysCode']);
    
    $recipient = new SingleUserRecipient($userId);
    
    //$message->sendTo($recipient);
    $recipient->sendMessage($message);
    
    return true;
}

/**
 * get the main user information
 * @param  integer $user_id user id as stored in the claroline main db
 * @return array   containing user info as 'lastName', 'firstName'
 *           'email', 'role'
 */

function course_user_get_properties($userId, $courseId)
{
    if (0 == (int) $userId)
    {
        return false;
    }

    $tbl_mdb_names       = claro_sql_get_main_tbl();
    $tbl_user            = $tbl_mdb_names['user'];
    $tbl_rel_course_user = $tbl_mdb_names['rel_course_user'];
    $tbl_course          = $tbl_mdb_names['course'];

    $sql = "SELECT  u.nom        AS lastName,
                    u.prenom     AS firstName,
                    u.email      AS email,
                    u.officialEmail  AS officialEmail,
                    u.pictureUri AS picture,
                    cu.profile_id AS profileId,
                    cu.role      AS role,
                    cu.isCourseManager ,
                    cu.tutor     AS isTutor,
                    c.intitule   AS courseName
            FROM    `" . $tbl_user            . "` AS u,
                    `" . $tbl_rel_course_user . "` AS cu,
                    `" . $tbl_course . "` AS c
            WHERE   u.user_id = cu.user_id
            AND     u.user_id = " . (int) $userId . "
            AND     cu.code_cours = '" . claro_sql_escape($courseId) . "'
            AND     c.code = cu.code_cours ";

    $result = claro_sql_query($sql);

    if (mysql_num_rows($result) > 0)
    {
        $userInfo = mysql_fetch_array($result, MYSQL_ASSOC);
        return $userInfo;
    }

    return false;
}

/**
 * Display form to edit course user properties
 * @author Mathieu Laurent <laurent@cerdecam.be>
 * @param $data array to fill the form
 * @todo $courseManagerChecked never used
 */

function course_user_html_form ( $data, $courseId, $userId, $hiddenParam = null )
{

    // TODO $courseManagerChecked never used
    //$courseManagerChecked = $data['isCourseManager'] == 1 ? 'checked="checked"':'';
    $tutorChecked = $data['isTutor'] == 1 ? 'checked="checked"':'';
    $selectedProfileId = isset($data['profileId'])?(int)$data['profileId']:0;

    $form = '';

    $form .= '<form action="' . claro_htmlspecialchars( $_SERVER['PHP_SELF'] ) . '" method="post">' . "\n"
    .        claro_form_relay_context()
    .        '<input type="hidden" name="cmd" value="exUpdateCourseUserProperties" />' . "\n"
    ;

    if ( ! is_null($hiddenParam) && is_array($hiddenParam) )
    {
        foreach ( $hiddenParam as $name => $value )
        {
            $form .= '<input type="hidden" name="'. claro_htmlspecialchars($name) .'" value="'. claro_htmlspecialchars($value).'" />' . "\n";
        }
    }

    $form.=  '<table class="claroTable" cellpadding="3" cellspacing="0" border="0">' . "\n";

    // User firstname and lastname
    $form .= '<tr >' . "\n"
    .  '<td align="right">' . get_lang('Name') . ' :</td>' . "\n"
    .  '<td ><b>' . claro_htmlspecialchars($data['firstName']) . ' ' . claro_htmlspecialchars($data['lastName'])  . '</b></td>' . "\n"
    .  '</tr>' . "\n" ;

    // Profile select box

    $profileList = claro_get_all_profile_name_list ();

    $form .= '<tr >' . "\n"
    . '<td align="right"><label for="profileId">' . get_lang('Profile') . ' :</label></td>' . "\n"
    . '<td>' ;

    if ( $userId == $GLOBALS['_uid'] )
    {
        $form .= '<input type="text" name="profileIdDisabled" value="'.claro_htmlspecialchars($profileList[$selectedProfileId]['name']).'" disabled="disabled" id="profileId" />' ;
    }
    else
    {
        $form .= '<select name="profileId" id="profileId">' ;

        foreach ( $profileList as $id => $info )
        {
            if ( $info['label'] != 'anonymous' && $info['label'] != 'guest' )
            {
                $form .= '<option value="' . $id . '" ' . ($selectedProfileId==$id?'selected="selected"':'') . '>' . get_lang($info['name']) . '</option>' . "\n" ;
            }
        }

        $form .= '</select>' ;
    }

    $form .= '</td>' . "\n"
    .  '</tr>' . "\n" ;

    // User role label
    $form .= '<tr >' . "\n"
    .  '<td align="right"><label for="role">' . get_lang('Role') . ' (' . get_lang('Optional') .')</label> :</td>' . "\n"
    .  '<td ><input type="text" name="role" id="role" value="'. claro_htmlspecialchars($data['role']) . '" maxlength="40" /></td>' . "\n"
    .  '</tr>' . "\n" ;

    // User is tutor
    $form .= '<tr >' . "\n"
    .  '<td align="right"><label for="isTutor">' . get_lang('Group Tutor') . '</label> :</td>' . "\n"
    .  '<td><input type="checkbox" name="isTutor" id="isTutor" value="1" ' . $tutorChecked . ' /></td>' . "\n"
    .  '</tr>' . "\n" ;

    $form .= '<tr >' . "\n"
    .  '<td align="right"><label for="applyChange">' . get_lang('Save changes') . '</label> :</td>' . "\n"
    .  '<td><input type="submit" name="applyChange" id="applyChange" value="'.get_lang('Ok').'" />&nbsp;'
    . claro_html_button(claro_htmlspecialchars(Url::Contextualize( $_SERVER['HTTP_REFERER'] )), get_lang('Cancel')) . '</td>' . "\n"
    .  '</tr>' . "\n";

    $form .= '</table>' . "\n"
    .  '</form>' . "\n" ;

    return $form;
}

/**
 * return the list of user of the course in parameter. It use by default the
 * current course identification
 *
 * @param string $courseCode course identication
 * @return array of int
 */
function claro_get_course_user_list($courseCode = NULL)
{
    if($courseCode == NULL)
    {
        $courseCode = claro_get_current_course_id();
    }
    
    $tbl_mdb_names = claro_sql_get_main_tbl();

    $tbl_rel_course_user = $tbl_mdb_names['rel_course_user'  ];
    $tbl_users           = $tbl_mdb_names['user'             ];
    
    $sqlGetUsers = "SELECT `user`.`user_id`      AS `user_id`,
                       `user`.`nom`          AS `nom`,
                       `user`.`prenom`       AS `prenom`,
                       `user`.`email`        AS `email`,
                       `course_user`.`profile_id`,
                       `course_user`.`isCourseManager`,
                       `course_user`.`tutor`  AS `tutor`,
                       `course_user`.`role`   AS `role`
               FROM `" . $tbl_users . "`           AS user,
                    `" . $tbl_rel_course_user . "` AS course_user
               WHERE `user`.`user_id`=`course_user`.`user_id`
               AND   `course_user`.`code_cours`='" . claro_sql_escape($courseCode) . "'
               ORDER BY `user`.`nom`,  `user`.`prenom` ;";
    
    return claro_sql_query_fetch_all_rows($sqlGetUsers);
}

/**
 * Get the number of pending users in a given course
 * @param string $courseId (optional, current course will be used if not given)
 * @return int
 */
function claro_count_pending_users( $courseId = null )
{
    if ( !$courseId )
    {
        $courseId = claro_get_current_course_id();
    }
    
    $tbl_mdb_names          = claro_sql_get_main_tbl();
    $tbl_rel_course_user    = $tbl_mdb_names['rel_course_user'];
    
    return Claroline::getDatabase()->query("
        SELECT *
        FROM `{$tbl_rel_course_user}`
        WHERE code_cours = " . Claroline::getDatabase()->quote($courseId) . "
        AND isPending = 1")->numRows();
}

function claro_is_course_registration_pending( $courseId = null, $userId = null )
{
    if ( !$courseId )
    {
        $courseId = claro_get_current_course_id();
    }
    
    if ( !$userId )
    {
        $userId = claro_get_current_user_id();
    }
    
    $privileges = claro_get_course_user_privilege($courseId, $userId);
    
    return $privileges['is_coursePending'];
}

function claro_is_current_user_enrolment_pending()
{
    return !claro_is_platform_admin() && claro_is_course_registration_pending();
}

/**
 * Course enrolment validation class
 */
class UserCourseEnrolmentValidation
{
    const 
        ENROLMENT_PENDING = 1,
        ENROLMENT_NOTPENDING = 0;

    
    protected 
        $privileges, 
        $course, 
        $isPending, 
        $validationCanBeChanged;
    
    
    public function __construct( $course, $privileges )
    {
        $this->privileges = $privileges;    
        $this->course = $course;
        
        if ( ( $this->privileges->isCourseManager() && !$this->privileges->isEnrolmentPending() ) 
            || !$this->privileges->isCourseMember()  )
        {
            $this->validationCanBeChanged = false;
        }
        else
        {
            $this->validationCanBeChanged = true;
            
            if ( $this->privileges->isEnrolmentPending() )
            {
                $this->isPending = self::ENROLMENT_PENDING;
            }
            else
            {
                $this->isPending = self::ENROLMENT_NOTPENDING;
            }
        }
    }
    
    public function isModifiable()
    {
        return $this->validationCanBeChanged;
    }
    
    public function isPending()
    {
        return $this->isPending;
    }
    
    public function grant()
    {
        return $this->changeValidation(self::ENROLMENT_NOTPENDING);
    }
    
    public function revoke()
    {
        return $this->changeValidation(self::ENROLMENT_PENDING);
    }
    
    protected function changeValidation( $pendingStatus )
    {
        if (!$this->isModifiable())
        {
            throw new Exception("Cannot change user "
                . $this->privileges->getUserId()
                ." enrolment validation in course " 
                . $this->privileges->getCourseId() );
        }
        
        $tbl_mdb_names = claro_sql_get_main_tbl();

        $updated = Claroline::getDatabase()->exec("
                UPDATE 
                    `{$tbl_mdb_names['rel_course_user']}` AS rcu
                SET 
                    isPending = " . $pendingStatus . "
                WHERE 
                    `rcu`.`user_id` = " . Claroline::getDatabase()->escape($this->privileges->getUserId()) . "
                AND 
                    `code_cours` = " . Claroline::getDatabase()->quote($this->privileges->getCourseId()) );

        if ($updated)
        {
            if ( $this->course->sourceCourseId )
            {
                $sourceCourseRegistrationValidation = new self( 
                    ClaroCourse::getCodeFromId( $this->course->sourceCourseId ),
                    $this->privileges->getUserId()
                );
                
                $sourceCourseRegistrationValidation->changeValidation( $pendingStatus );
            }
            
            return true;
        }
        else
        {
            return false;
        }
    }
}
