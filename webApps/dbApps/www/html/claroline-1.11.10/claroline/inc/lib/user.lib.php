<?php // $Id: user.lib.php 14551 2013-09-25 12:36:31Z zefredz $

/**
 * CLAROLINE
 *
 * User lib contains function to manage users on the platform
 * @version     1.9 $Revision: 14551 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLUSR
 * @author      Christophe Gesche <moosh@claroline.net>
 * @author      Mathieu Laurent <laurent@cerdecam.be>
 * @author      Hugues Peeters <hugues.peeters@advalvas.be>
 * @author      Claro Team <cvs@claroline.net>
 */

require_once(dirname(__FILE__) . '/form.lib.php');


/**
 * Initialise user data and handle user's form inputs if any.
 *
 * @return $userData array
 * @todo: this is not a simple initialisation anymore
 */
function user_initialise()
{
    $userData = array();
    
    $userData['user_id']        = isset($_REQUEST['uidToEdit'])?(int) $_REQUEST['uidToEdit']:'';
    $userData['lastname']       = isset($_REQUEST['lastname'])?trim(strip_tags($_REQUEST['lastname'])):'';
    $userData['firstname']      = isset($_REQUEST['firstname'])?trim(strip_tags($_REQUEST['firstname'])):'';
    $userData['officialCode']   = isset($_REQUEST['officialCode'])?trim(strip_tags($_REQUEST['officialCode'])):'';
    $userData['officialEmail']  = isset($_REQUEST['officialEmail'])?trim(strip_tags($_REQUEST['officialEmail'])):'';
    $userData['language']       = isset($_REQUEST['language'])?trim(strip_tags($_REQUEST['language'])):'';
    $userData['picture']        = isset($_REQUEST['userPicture'])?trim(strip_tags($_REQUEST['userPicture'])):'';
    $userData['username']       = isset($_REQUEST['username'])?trim(strip_tags($_REQUEST['username'])):'';
    $userData['old_password']   = isset($_REQUEST['old_password'])?trim($_REQUEST['old_password']):'';
    $userData['password']       = isset($_REQUEST['password'])?trim($_REQUEST['password']):'';
    $userData['password_conf']  = isset($_REQUEST['password_conf'])?trim($_REQUEST['password_conf']):'';
    $userData['email']          = isset($_REQUEST['email'])?trim(strip_tags($_REQUEST['email'])):'';
    $userData['phone']          = isset($_REQUEST['phone'])?trim(strip_tags($_REQUEST['phone'])):'';
    $userData['skype']          = isset($_REQUEST['skype'])?trim(strip_tags($_REQUEST['skype'])):'';
    $userData['authSource']     = isset($_REQUEST['authSource'])?trim(strip_tags($_REQUEST['authSource'])):'';
    $userData['isStudent']      = (bool) (isset($_REQUEST['platformRole']) && $_REQUEST['platformRole'] == 'student');
    $userData['isCourseCreator'] = (bool) (isset($_REQUEST['platformRole']) && $_REQUEST['platformRole'] == 'courseManager');
    $userData['isPlatformAdmin'] = (bool) (isset($_REQUEST['platformRole']) && $_REQUEST['platformRole'] == 'platformAdmin'
                                           || isset($userData['user_id']) && $userData['user_id'] == claro_get_current_user_id() && claro_is_platform_admin());
    $userData['courseTutor']    = (bool) !empty($_REQUEST['courseTutor']);
    $userData['courseAdmin']    = (bool) !empty($_REQUEST['courseAdmin']);
    
    $userData['profileId']      = isset($_REQUEST['profileId'])? (int) $_REQUEST['profileId'] : null;
    
    return $userData;
}


/**
 * Handle user's profile picture modification
 * (based on $_REQUEST['delPicture'] and $_FILE['picture'].
 *
 * @return  $feedback array (yes, it is kinda ugly)
 */
function user_handle_profile_picture($userData)
{
    $feedback = array(
        'success' => false,
        'messages' => array(),
        'pictureName' => '');
    
    // Handle user picture
    if (!empty($_REQUEST['delPicture']))
    {
        $picturePath = user_get_picture_path($userData);
        
        if ($picturePath)
        {
            claro_delete_file( $picturePath );
            $feedback['success'] = true;
            $feedback['messages'][] = get_lang("User picture deleted");
        }
        else
        {
            $feedback['messages'][] = get_lang("Cannot delete user picture");
        }
    }
    
    if (isset($_FILES['picture']['name'])
        && $_FILES['picture']['size'] > 0)
    {
        $fileName = $_FILES['picture']['name'];
        $fileTmpName = $_FILES['picture']['tmp_name'];
        
        if (is_uploaded_file($fileTmpName))
        {
            // Is it an picture ?
            if (is_image($fileName))
            {
                // Does it meet the platform's requirements
                list($width, $height, $type, $attr) = getimagesize($fileTmpName);
                
                if ($width > 0 && $width <= get_conf('maxUserPictureWidth', 150)
                    && $height > 0 && $height <= get_conf('maxUserPictureHeight', 200)
                    && $_FILES['picture']['size'] <= get_conf('maxUserPictureSize', 100*1024))
                {
                    $uploadDir = user_get_private_folder_path($userData['user_id']);
                    
                    if (!file_exists($uploadDir))
                    {
                        claro_mkdir($uploadDir, CLARO_FILE_PERMISSIONS, true);
                    }
                    
                    // User's picture successfully treated
                    if (false !== ($pictureName = treat_uploaded_file(
                            $_FILES['picture'],
                            $uploadDir,
                            '',
                            1000000000000)))
                    {
                        $feedback['success'] = true;
                        $feedback['messages'][] = get_lang("User picture added");
                        $feedback['pictureName'] = $pictureName;
                    }
                    else
                    {
                        $feedback['messages'][] = get_lang("Cannot upload file");
                    }
                }
                else
                {
                    $feedback['messages'][] =
                        get_lang("Image is too big : max size %width%x%height%, %size% bytes", array(
                                    '%width%' => get_conf('maxUserPictureWidth', 150),
                                    '%height%' => get_conf('maxUserPictureHeight', 200),
                                    '%size%' => get_conf('maxUserPictureHeight', 100*1024)
                                ));
                }
            }
            else
            {
                $feedback['messages'][] = get_lang("Invalid file format, use gif, jpg or png");
            }
        }
        else
        {
            $feedback['messages'][] = get_lang('Upload failed');
        }
    }
    
    return $feedback;
}


/**
 * Get common user data on the platform
 * @param integer $userId id of user to fetch properties
 *
 * @return  array( `user_id`, `lastname`, `firstname`, `username`, `email`,
 *           `picture`, `officialCode`, `phone`, `isCourseCreator` ) with user data
 * @author Mathieu Laurent <laurent@cerdecam.be>
 */

function user_get_properties($userId)
{
    $tbl = claro_sql_get_main_tbl();

    $sql = "SELECT                 user_id,
                    nom         AS lastname,
                    prenom      AS firstname,
                                   username,
                                   email,
                                   language,
                    authSource  AS authSource,
                    pictureUri  AS picture,
                                   officialCode,
                                   officialEmail,
                    phoneNumber AS phone,
                                   isCourseCreator,
                                   isPlatformAdmin
            FROM   `" . $tbl['user'] . "`
            WHERE  `user_id` = " . (int) $userId;

    $result = claro_sql_query_get_single_row($sql);

    if ($result)
    {
        return $result;
    }
    else
    {
        return claro_failure::set_failure('user_not_found');
    }
}

/**
 * Add a new user
 *
 * @param $settingList array to fill the form
 * @param $creatorId id of account creator
 *                  (null means created by owner)
 *                  default null
 *
 * @author Mathieu Laurent <laurent@cerdecam.be>
 */

function user_create($settingList, $creatorId = null)
{
    $requiredSettingList = array('lastname', 'firstname', 'username',
    'password', 'language', 'email', 'officialCode', 'phone', 'isCourseCreator','isPlatformAdmin');

    // Set non compulsory fields

    if (!isset($settingList['language']))            $settingList['language'] = '';
    if (!isset($settingList['phone']))               $settingList['phone'] = '';
    if (!isset($settingList['isCourseCreator']))     $settingList['isCourseCreator'] = false;
    if (!isset($settingList['officialEmail']))       $settingList['officialEmail'] = false;
    if (!isset($settingList['isPlatformAdmin']))     $settingList['isPlatformAdmin'] = false;

    // Verify required fields
    foreach($requiredSettingList as $thisRequiredSetting)
    {
        if ( array_key_exists( $thisRequiredSetting, $settingList ) ) continue;
        else return trigger_error('MISSING_DATA : ',E_USER_ERROR);
    }

    // Check if the username is available
    if ( ! is_username_available($settingList['username']) )
    {
        return false ;
    }

    $password = get_conf('userPasswordCrypted')
        ? md5($settingList['password'])
        : $settingList['password']
        ;

    $tbl = claro_sql_get_main_tbl();

    $sql = "INSERT INTO `" . $tbl['user'] . "`
            SET nom             = '". claro_sql_escape($settingList['lastname'     ]) ."',
                prenom          = '". claro_sql_escape($settingList['firstname'    ]) ."',
                username        = '". claro_sql_escape($settingList['username'     ]) ."',
                language        = '". claro_sql_escape($settingList['language'     ]) ."',
                email           = '". claro_sql_escape($settingList['email'        ]) ."',
                officialCode    = '". claro_sql_escape($settingList['officialCode' ]) ."',
                officialEmail   = '". claro_sql_escape($settingList['officialEmail']) ."',
                phoneNumber     = '". claro_sql_escape($settingList['phone'        ]) ."',
                password        = '". claro_sql_escape($password) . "',
                isCourseCreator = " . (int) $settingList['isCourseCreator'] . ",
                isPlatformAdmin = " . (int) $settingList['isPlatformAdmin'] . ",
                creatorId    = " . ($creatorId > 0 ? (int) $creatorId : 'NULL');
    $adminId = claro_sql_query_insert_id($sql);
    if (false !== $adminId) return $adminId;
    else return claro_failure::set_failure('Cant create user|' . mysql_error() . '|');
}

/**
 * Update user data
 * @param $user_id integer
 * @param $propertyList array
 * @author Mathieu Laurent <laurent@cerdecam.be>
 */

function user_set_properties($userId, $propertyList)
{
    $tbl = claro_sql_get_main_tbl();
    
    if (array_key_exists('isCourseCreator', $propertyList))
    {
        $propertyList['isCourseCreator'] = $propertyList['isCourseCreator'] ? 1 : 0;
    }
    
    if (array_key_exists('password', $propertyList) && get_conf('userPasswordCrypted'))
    {
        $propertyList['password'] = md5($propertyList['password']);
    }
    
    // Only an administrator can grant a user to administrator statuts
    if (array_key_exists('isPlatformAdmin', $propertyList) )
    {
        $propertyList['isPlatformAdmin'] = $propertyList['isPlatformAdmin'] ? 1 : 0;
    }
    
    // Build query
    $sqlColumnList = array('nom'             => 'lastname',
                           'prenom'          => 'firstname',
                           'username'        => 'username',
                           'phoneNumber'     => 'phone',
                           'email'           => 'email',
                           'officialCode'    => 'officialCode',
                           'isCourseCreator' => 'isCourseCreator',
                           'password'        => 'password',
                           'language'        => 'language',
                           'pictureUri'      => 'picture',
                           'isPlatformAdmin' => 'isPlatformAdmin',
                           'authSource'      => 'authSource' );
    
    $setList = array();
    
    foreach($sqlColumnList as $columnName => $propertyName)
    {
        if ( array_key_exists($propertyName, $propertyList) && (! is_null($propertyList[$propertyName])) )
        {
            $setList[] = $columnName . "= '"
            . claro_sql_escape($propertyList[$propertyName]). "'";
        }
    }
    
    if ( count($setList) > 0)
    {
        $sql = "UPDATE  `" . $tbl['user'] . "`
                SET ". implode(', ', $setList) . "
                WHERE user_id  = " . (int) $userId ;
        
        if ( claro_sql_query_affected_rows($sql) > 0 ) return true;
        else                                           return false;
    }
}


/**
 * Delete user form claroline platform.
 *
 * @author Mathieu Laurent <laurent@cerdecam.be>
 * @param int $userId
 * @return boolean 'true' if it succeeds, 'false' otherwise
 */
function user_delete($userId)
{
    require_once dirname(__FILE__) . '/course_user.lib.php';

    if ( claro_get_current_user_id() == $userId ) // user cannot remove himself of the platform
    {
        return claro_failure::set_failure('user_cannot_remove_himself');
    }

    // main tables name

    $tbl = claro_sql_get_main_tbl();

    // get the list of course code where the user is subscribed
    $sql = "SELECT c.code                          AS code
            FROM `" . $tbl['rel_course_user'] . "` AS cu,
                 `" . $tbl['course'] . "`          AS c
            WHERE cu.code_cours = c.code
            AND  cu.user_id    = " . $userId;

    $courseList = claro_sql_query_fetch_all_cols($sql);

    $log = array();
    if ( user_remove_from_course($userId, $courseList['code'], true, true ) == false ) return false;
    else
    {
        foreach ($courseList['code'] as $k=>$courseCode) $log['course_' . $k] = $courseCode;
        Claroline::log( 'UNROL_USER_COURS' , array_merge( array ('USER' => $userId ) ,$log));
    }
    $sqlList = array(

    "DELETE FROM `" . $tbl['user']            . "` WHERE user_id         = " . (int) $userId ,
    "DELETE FROM `" . $tbl['tracking_event']   . "` WHERE user_id   = " . (int) $userId ,
    "DELETE FROM `" . $tbl['rel_class_user']  . "` WHERE user_id         = " . (int) $userId ,
    "DELETE FROM `" . $tbl['sso']             . "` WHERE user_id         = " . (int) $userId ,

    // Change creatorId to NULL
    "UPDATE `" . $tbl['user'] . "` SET `creatorId` = NULL WHERE `creatorId` = " . (int) $userId

    );
    Claroline::log( 'USER_DELETED' , array_merge( array ('USER' => $userId ) ));

    foreach($sqlList as $thisSql)
    {
        if ( claro_sql_query($thisSql) == false ) return false;
        else                                      continue;
    }

    return true;
}

/**
 * @return list of users wich have admin status
 * @author Christophe Gesche <Moosh@claroline.net>
 *
 */

function claro_get_uid_of_platform_admin()
{
    $tbl = claro_sql_get_main_tbl();

    $sql = "SELECT user_id AS id
            FROM `" . $tbl['user'] . "`
            WHERE isPlatformAdmin = 1 ";

    $resultList = claro_sql_query_fetch_all_cols($sql);

    return $resultList['id'];
}

/**
 * @return list of users wich have status to receipt REQUESTS
 * @author Christophe Gesche <Moosh@claroline.net>
 *
 */

function claro_get_uid_of_request_admin()
{
    $tbl = claro_sql_get_main_tbl();

    $sql = "SELECT user_id AS id
            FROM `" . $tbl['user'] . "` AS u
            INNER JOIN `" . $tbl['user_property'] . "` AS up
            ON up.userId = u.user_id
            WHERE u.isPlatformAdmin = 1
              AND up.propertyId = 'adminContactForRequest'
              AND up.propertyValue = 1
              AND up.scope = 'contacts'
              ";
    $resultList = claro_sql_query_fetch_all_cols($sql);

    return $resultList['id'];
}


/**
 * @return list of users wich have status to receive system notification
 * @author Christophe Gesche <Moosh@claroline.net>
 *
 */

function claro_get_uid_of_platform_contact()
{
    $tbl = claro_sql_get_main_tbl();

    $sql = "SELECT user_id AS id
            FROM `" . $tbl['user'] . "` AS u
            INNER JOIN `" . $tbl['user_property'] . "` AS up
            ON up.userId = u.user_id
            WHERE up.propertyId = 'adminContactForContactPage'
              #AND u.isPlatformAdmin = 1
              AND up.propertyValue = 1
              AND up.scope = 'contacts'
              ";
    $resutlList = claro_sql_query_fetch_all_cols($sql);

    return $resutlList['id'];
}


/**
 * @return list of users wich have status to receive system notification
 * @author Christophe Gesche <Moosh@claroline.net>
 *
 */

function claro_get_uid_of_system_notification_recipient()
{
    $tbl = claro_sql_get_main_tbl();

    $sql = "SELECT user_id AS id
            FROM `" . $tbl['user'] . "` AS u
            INNER JOIN `" . $tbl['user_property'] . "` AS up
            ON up.userId = u.user_id
            WHERE up.propertyId = 'adminContactForSystemNotification'
              AND up.propertyValue = 1
              AND up.scope = 'contacts'
              ";
    $resultList = claro_sql_query_fetch_all_cols($sql);

    return $resultList['id'];
}

function claro_set_uid_recipient_of_system_notification($user_id,$state=true)
{
   $tbl = claro_sql_get_main_tbl();

    $sql = "REPLACE INTO `" . $tbl['user_property'] . "`
            SET userId = " . (int) $user_id . ",
                propertyId = 'adminContactForSystemNotification',
                propertyValue = " . (int) $state . ",
                scope = 'contacts'
              ";

    $result = claro_sql_query_affected_rows($sql);

    return $result;

}

function claro_set_uid_of_platform_contact($user_id,$state=true)
{
   $tbl = claro_sql_get_main_tbl();

    $sql = "REPLACE INTO `" . $tbl['user_property'] . "`
            SET userId = " . (int) $user_id . ",
                propertyId = 'adminContactForContactPage',
                propertyValue = " . (int) $state . ",
                scope = 'contacts'
              ";

    $result = claro_sql_query_affected_rows($sql);

    return $result;

}

function claro_set_uid_recipient_of_request_admin($user_id,$state=true)
{
   $tbl = claro_sql_get_main_tbl();

    $sql = "REPLACE INTO `" . $tbl['user_property'] . "`
            SET userId = " . (int) $user_id . ",
                propertyId = 'adminContactForRequest',
                propertyValue = " . (int) $state . ",
                scope = 'contacts'
              ";
    $result = claro_sql_query_affected_rows($sql);

    return $result;

}


/**
 * Return true, if user is admin on the platform
 * @param $userId
 * @return boolean
 * @author Hugues Peeters <hugues.peeters@advalvas.be>
 */

function user_is_admin($userId)
{
    $userPropertyList = user_get_properties($userId);
    return (bool) $userPropertyList['isPlatformAdmin'];
}

/**
 * Set or unset platform administrator status to a specific user
 *
 * @author Hugues Peeters <hugues.peeters@advalvas.be>
 * @param  boolean $status
 * @param  int     $userId
 * @return boolean 'true' if it succeeds, 'false' otherwise
 */

function user_set_platform_admin($status, $userId)
{
    return user_set_properties($userId, array('isPlatformAdmin' => (bool) $status) );
}

/**
 * Send registration succeded email to user
 * @author Mathieu Laurent <laurent@cerdecam.be>
 *
 * @param integer $userId
 * @param mixed $data array of user data or null to keep data following $userId param.
 * @return boolean
 */

function user_send_registration_mail ($userId, $data, $courseCode = null)
{
    require_once dirname(__FILE__) . '/sendmail.lib.php';
    require_once dirname(__FILE__) . '/../../messaging/lib/message/messagetosend.lib.php';
    require_once dirname(__FILE__) . '/../../messaging/lib/recipient/singleuserrecipient.lib.php';
    
    if ( ! empty($data['email']) )
    {
        // email subjet

        $emailSubject  = '[' . get_conf('siteName') . '] ' . get_lang('Your registration') ;

        // email body

        $emailBody = get_block('blockAccountCreationNotification',
        array(
        '%firstname'=> $data['firstname'],
        '%lastname' => $data['lastname'],
        '%username' => $data['username'],
        '%password' => $data['password'],
        '%siteName'=> get_conf('siteName'),
        '%rootWeb' => get_path('rootWeb'),
        '%administratorName' => get_conf('administrator_name'),
        '%administratorPhone'=> get_conf('administrator_phone'),
        '%administratorEmail'=> get_conf('administrator_email')
        )
        );

                // add information about course manager if user created in course
        if (isset($courseCode))
        {
            $courseData = claro_get_course_data($courseCode);
            $emailBody .=
                    get_lang('User created by ')
                    . ' - ' . $courseData['titular']
                    . ' - ' . $courseData['email']
                    . ' ( ' . $courseData['officialCode']
                    . ' - ' . $courseData['name']
                    . ' )'
            ;

        }

        if ( claro_mail_user($userId, $emailBody, $emailSubject) ) return true;
        else                                                       return false;
    }
    else
    {
        return false;
    }

}

/**
 * Current logged user send a mail to ask course creator status
 * @param string explanation message
 * @author Mathieu Laurent <laurent@cerdecam.be>
 */

function profile_send_request_course_creator_status($explanation)
{
    require_once dirname(__FILE__) . '/../../messaging/lib/message/messagetosend.lib.php';
    require_once dirname(__FILE__) . '/../../messaging/lib/recipient/userlistrecipient.lib.php';
    
    global $_user;

    $mailToUidList = claro_get_uid_of_request_admin();
    if(empty($mailToUidList)) $mailToUidList = claro_get_uid_of_platform_admin();

    
    $requestMessage_Title =
    get_block('Course creator status for %firstname %lastname',
    array('%firstname' => $_user['firstName'],
    '%lastname' => $_user['lastName'] ) );

    $requestMessage_Content =
    nl2br(get_block('blockRequestCourseManagerStatusMail',
    array( '%time'      => claro_html_localised_date(get_locale('dateFormatLong')),
    '%user_id'   => claro_get_current_user_id(),
    '%firstname' => $_user['firstName'],
    '%lastname'  => $_user['lastName'],
    '%email'     => $_user['mail'],
    '%comment'   => $explanation,
    '%url'       => rtrim( get_path('rootWeb'), '/' ) . '/claroline/admin/admin_profile.php?uidToEdit=' . claro_get_current_user_id()
    ))
    );

    $message = new MessageToSend(claro_get_current_user_id(),$requestMessage_Title,$requestMessage_Content);
    
    $recipient = new UserListRecipient();
    $recipient->addUserIdList($mailToUidList);
    
    $recipient->sendMessage($message);
    
    return true;
}

/**
 * Current logged user send a mail to ask course creator status
 * @param string explanation message
 * @author Mathieu Laurent <laurent@cerdecam.be>
 */

function profile_send_request_revoquation($explanation,$login,$password)
{
    if (empty($explanation)) return claro_failure::set_failure('EXPLANATION_EMPTY');

    require_once dirname(__FILE__) . '/../../messaging/lib/message/messagetosend.lib.php';
    require_once dirname(__FILE__) . '/../../messaging/lib/recipient/userlistrecipient.lib.php';
    
    $_user = claro_get_current_user_data();

    $mailToUidList = claro_get_uid_of_request_admin();
    if(empty($mailToUidList)) $mailToUidList = claro_get_uid_of_platform_admin();
    
    $requestMessage_Title =
    get_block('Revocation of %firstname %lastname',
    array('%firstname' => $_user['firstName'],
    '%lastname' => $_user['lastName'] ) );

    $requestMessage_Content =
    nl2br(get_block('blockRequestUserRevoquationMail',
    array('%time'      => claro_html_localised_date(get_locale('dateFormatLong')),
    '%user_id'   => claro_get_current_user_id(),
    '%firstname' => $_user['firstName'],
    '%lastname'  => $_user['lastName'],
    '%email'     => $_user['mail'],
    '%login'     => $login,
    '%password'  => '**********',
    '%comment'   => nl2br($explanation),
    '%url'       => rtrim( get_path('rootWeb'), '/' ) . '/claroline/admin/admin_profile.php?uidToEdit=' . claro_get_current_user_id()
    ))
    );

    $message = new MessageToSend(claro_get_current_user_id(),$requestMessage_Title,$requestMessage_Content);
    
    $recipient = new UserListRecipient();
    $recipient->addUserIdList($mailToUidList);
    
    $recipient->sendMessage($message);
    
    return true;
}


/**
 * Generates randomly password
 * @author Damien Seguy
 * @return string : the new password
 */
function generate_passwd($nb=8)
{

    $lettre = array();

    $lettre[0] = array( 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i',
    'j', 'k', 'l', 'm', 'o', 'n', 'p', 'q', 'r',
    's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A',
    'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J',
    'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'D',
    'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '9',
    '0', '6', '5', '1', '3');

    $lettre[1] =  array('a', 'e', 'i', 'o', 'u', 'y', 'A', 'E',
    'I', 'O', 'U', 'Y' , '1', '3', '0' );

    $lettre[-1] = array('b', 'c', 'd', 'f', 'g', 'h', 'j', 'k',
    'l', 'm', 'n', 'p', 'q', 'r', 's', 't',
    'v', 'w', 'x', 'z', 'B', 'C', 'D', 'F',
    'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P',
    'Q', 'R', 'S', 'T', 'V', 'W', 'X', 'Z',
    '5', '6', '9');

    $retour   = '';
    $prec     = 1;
    $precprec = -1;

    srand((double)microtime() * 20001107);

    while(strlen($retour) < $nb)
    {
        // To generate the password string we follow these rules : (1) If two
        // letters are consonnance (vowel), the following one have to be a vowel
        // (consonnace) - (2) If letters are from different type, we choose a
        // letter from the alphabet.

        $type     = ($precprec + $prec) / 2;
        $r        = $lettre[$type][array_rand($lettre[$type], 1)];
        $retour  .= $r;
        $precprec = $prec;
        $prec     = in_array($r, $lettre[-1]) - in_array($r, $lettre[1]);

    }
    return $retour;
}


/**
 * Check an email
 * @version 1.0
 * @param  string $email email to check
 *
 * @return boolean state of validity.
 * @author Christophe Gesche <moosh@claroline.net>
 */
function is_well_formed_email_address($address)
{
    $regexp = "/[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/i";

    //  $regexp = '^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$';
    return preg_match($regexp, $address);
}


/**
 * validate form registration
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @param array $data from the form
 * @return array with error messages
 */
function user_validate_form_registration($data)
{
    return user_validate_form('registration', $data);
}


/**
 * validate form profile
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @param array $data to fill the form
 * @param int $userId id of the user account currently edited
 * @return array with error messages
 */
function user_validate_form_profile($data, $userId)
{
    return user_validate_form('profile', $data, $userId);
}


/**
 * validate form profile from user administration.
 *
 * @author Frederic Minne <zefredz@claroline.net>
 * @param array $data to fill the form
 * @param int $userId id of the user account currently edited
 * @return array with error messages
 */
function user_validate_form_admin_user_profile($data, $userId)
{
    return user_validate_form('admin_user_profile', $data, $userId);
}


/**
 * Validate user form.
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @author Frederic Minne <zefredz@claroline.net>
 * @param string $mode 'registration' or 'profile' or 'admin_user_profile'
 * @param array $data to fill the form
 * @param int $userId (optional) id of the user account currently edited
 * @return array with error messages
 */
function user_validate_form($formMode, $data, $userId = null)
{
    require_once dirname(__FILE__) .'/datavalidator.lib.php';
    
    if (empty($userId) || claro_is_platform_admin())
    {
        $editableFields = array('name','official_code','login','password','email','phone','language','picture','skype');
        
        if (claro_is_platform_admin())
        {
            $editableFields[] = 'authSource';
        }
    }
    else
    {
        // $editableFields = get_conf('profile_editable');
        $editableFields = AuthProfileManager::getUserAuthProfile( $userId )->getEditableProfileFields();
    }
    
    $validator = new DataValidator();
    $validator->setDataList($data);
    
    if (in_array('name', $editableFields))
    {
        $validator->addRule('lastname' , get_lang('You left some required fields empty'), 'required');
        $validator->addRule('firstname', get_lang('You left some required fields empty'), 'required');
    }
    
    if (in_array('login', $editableFields))
    {
        $validator->addRule('username' , get_lang('You left some required fields empty'), 'required');
        $validator->addRule('username' , get_lang('Username is too long (maximum 60 characters)'), 'maxlength',60);
    }
    
    if (in_array('email', $editableFields) && !get_conf('userMailCanBeEmpty'))
    {
        $validator->addRule('email', get_lang('You left some required fields empty'), 'required');
    }
    
    if (in_array('official_code', $editableFields) && !get_conf('userOfficialCodeCanBeEmpty'))
    {
        $validator->addRule('officialCode', get_lang('You left some required fields empty'), 'required');
    }
    
    if (in_array('password', $editableFields) && (array_key_exists('password',$data) || array_key_exists('password_conf',$data)))
    {
        if ( $formMode != 'registration'
            && $formMode != 'admin_user_profile' )
        {
            $userProperties = user_get_properties($userId);
            
            $validator->addRule('old_password', get_lang('You left some required fields empty'), 'required' );
            $validator->addRule('old_password',
                get_lang('Old password is wrong'),
                'user_check_authentication',
                array( $userProperties['username'] )
            );
        }
        
        if ( get_conf('SECURE_PASSWORD_REQUIRED') )
        {
            $validator->addRule('password',
            get_lang( 'This password is too simple or too close to the username, first name or last name.<br> Use a password like this <code>%passProposed</code>', array('%passProposed'=> generate_passwd() )),
            'is_password_secure_enough',
            array(array( $data['username'] ,
            $data['officialCode'] ,
            $data['lastname'] ,
            $data['firstname'] ,
            $data['email'] )
            )
            );
        }
        
        $validator->addRule('password', get_lang('You typed two different passwords'), 'compare', $data['password_conf']);
    }
    
    $validator->addRule('email'  , get_lang('The email address is not valid'), 'email');
    
    if ('registration' == $formMode)
    {
        $validator->addRule('password_conf', get_lang('You left some required fields empty'), 'required');
        $validator->addRule('officialCode', get_lang('This official code is already used by another user.'), 'is_official_code_available');
        $validator->addRule('username', get_lang('This username is already taken'), 'is_username_available');
        $validator->addRule('password', get_lang('You left some required fields empty'), 'required');
    }
    else // profile mode
    {
        // FIX for the empty password issue
        if ( !empty( $data['password'] ) || !empty( $data['password_conf'] ) )
        {
            $validator->addRule('password', get_lang('You left some required fields empty'), 'required');
        }
        
        $validator->addRule('officialCode', get_lang('This official code is already used by another user.'), 'is_official_code_available', $userId);
        $validator->addRule('username', get_lang('This username is already taken'), 'is_username_available', $userId);
    }
    
    if ( $validator->validate() )
    {
        return array();
    }
    else
    {
        return array_unique($validator->getErrorList());
    }
}


/**
 * Check if the authentication fassword for the given user
 *
 * @author Frederic Minne <zefredz@claroline.net>
 *
 * @param string $password
 * @param string $login
 * @return boolean
 *
 */
function user_check_authentication( $password, $login )
{
    try
    {
        if ( false !== AuthManager::authenticate( $login, $password ) )
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    catch (Exception $e)
    {
        Console::error("Cannot authentified user : " . $e->__toString());
        return false;
    }
}

/**
 * Check if the password chosen by the user is not too much easy to find
 *
 * @author Hugues Peeters <hugues.peeters@advalvas.be>
 *
 * @param string requested password
 * @param array list of other values of the form we wnt to check the password
 * @return boolean true if not too much easy to find
 *
 */

function is_password_secure_enough($requestedPassword, $forbiddenValueList)
{
    foreach ( $forbiddenValueList as $thisValue )
    {
        if ( strtoupper($requestedPassword) == strtoupper($thisValue) )
        {
            return claro_failure::set_failure('ERROR_CODE_too_easy');
        }

        if ( !empty($requestedPassword) && !empty($thisValue)
        && ( false !== stristr($requestedPassword,$thisValue)
        ||   false !== stristr($thisValue,$requestedPassword) ))
        {
            return claro_failure::set_failure('ERROR_CODE_too_easy');
        }

        if ( (function_exists('soundex')) && soundex($requestedPassword) == soundex($thisValue) )
        {
            return claro_failure::set_failure('ERROR_CODE_too_easy');
        }


    }

    return true;
}

/**
 * Check if the username is available
 * @param string username
 * @param integer user_id
 * @return boolean
 */

function is_username_available($username, $userId = null)
{
    $tbl = claro_sql_get_main_tbl();

    $sql = "SELECT COUNT(username)
            FROM `" . $tbl['user'] . "`
            WHERE username='" . claro_sql_escape($username) . "' ";

    if ( ! is_null($userId) ) $sql .= " AND user_id <> "  . (int) $userId ;

    if ( claro_sql_query_get_single_value($sql) == 0 ) return true;
    else                                               return false;
}

/**
 * Check if the official code is available
 *
 * @param string official code
 * @param integer user_id
 *
 * @return boolean
 */

function is_official_code_available($official_code, $userId=null)
{
    $tbl = claro_sql_get_main_tbl();

    $sql = "SELECT COUNT(officialCode)
            FROM `" . $tbl['user'] . "`
            WHERE officialCode = '" . claro_sql_escape($official_code) . "' ";

    if ( ! is_null($userId) ) $sql .= " AND user_id <> "  . (int) $userId ;

    if ( claro_sql_query_get_single_value($sql) == 0 ) return true;
    else                                               return false;
}


/**
 * Display form to edit or add user to the platform.
 *
 * @param $userId int
 */
function user_html_form($userId = null)
{
    // If the user exists (given user id)
    if (!empty($userId))
    {
        // Get user's general informations
        $userData = user_get_properties($userId);
        
        // Get user's skype account
        $userData['skype'] = get_user_property($userId, 'skype');
        
        // Get user's picture
        $picturePath = user_get_picture_path($userData);
        
        if ($picturePath && file_exists($picturePath))
        {
            $pictureUrl = user_get_picture_url($userData);
        }
        else
        {
            $pictureUrl = '';
        }
        
        // Get current language
        $currentLanguage = !empty($userData['language'])?$userData['language']:language::current_language();
        
        // A few javascript
        $htmlHeadXtra[] =
        '<script type="text/javascript">
            function confirmation (name)
            {
                if (confirm("'.clean_str_for_javascript(get_lang('Are you sure to delete')).'"+ name + "?"))
                {return true;}
                else
                {return false;}
            }
            
            $(document).ready(function(){
                $("#delete").click(function(){
                    return confirmation("' . $userData['firstname'] . " " . $userData['lastname'] .'");
                }).attr("href","adminuserdeleted.php?uidToEdit=' . $userId . '&cmd=exDelete");
            });
        </script>';
    }
    // If it's a new user (no given user id)
    else
    {
        // Initialize user's data
        $userData = user_initialise();
        
        // No user's picture
        $pictureUrl = '';
        
        // Prefered language
        $currentLanguage = language::current_language();
    }
    
    // Editable fields
    if (empty($userId) || claro_is_platform_admin())
    {
        $editableFields = array('name','official_code','login','password','email','phone','language','picture','skype');
        
        if (claro_is_platform_admin())
        {
            $editableFields[] = 'authSource';
        }
    }
    else
    {
        $editableFields = AuthProfileManager::getUserAuthProfile( $userId )->getEditableProfileFields(); // get_conf('profile_editable');
        // pushClaroMessage(var_export($editableFields,true), 'debug');
    }
    
    if (!empty($_SERVER['HTTP_REFERER']))
    {
        $cancelUrl = $_SERVER['HTTP_REFERER'];
    }
    else
    {
        $cancelUrl = $_SERVER['PHP_SELF'];
    }
    
    // Hack to prevent autocompletion of password fields from browser
    $htmlHeadXtra[] =
    '<script type="text/javascript">
        $(document).ready(
            function() {
                $("#password").val("");
            }
        );
    </script>';
    
    $template = new CoreTemplate('user_form.tpl.php');
    $template->assign('formAction', $_SERVER['PHP_SELF']);
    $template->assign('relayContext', claro_form_relay_context());
    $template->assign('cancelUrl', claro_htmlspecialchars(Url::Contextualize($cancelUrl)));
    $template->assign('editableFields', $editableFields);
    $template->assign('data', $userData);
    $template->assign('pictureUrl', $pictureUrl);
    $template->assign('currentLanguage', $currentLanguage);
    $template->assign('languages', get_language_to_display_list());
    
    return $template->render();
}


/**
 * Display form to search already registered users to add to course
 * Used when course managers can only add already registered users to their courses
 * @author Jean-Roch Meurisse <jmeuriss@fundp.ac.be>
 * @param $data array to fill the form
 */
function user_html_search_form( $data )
{
    // init form
    $html = '<form action="' . claro_htmlspecialchars( $_SERVER['PHP_SELF'] ) . '" method="post" enctype="multipart/form-data" >' . "\n"
    .       claro_form_relay_context()

    // hidden fields
    .       form_input_hidden( 'cmd', 'registration' )
    .       form_input_hidden( 'claroFormId', uniqid( '' ) )
    ;

    // init table
    $html .= '<table class="claroRecord" cellpadding="3" cellspacing="0" border="0">' . "\n";

    // display search criteria
    $html .= form_input_text( 'lastname', '', get_lang( 'Last name' ), false );
    $html .= form_input_text( 'firstname', '', get_lang( 'First name' ), false );
    if ( get_conf( 'ask_for_official_code' ) )
    {
        $html .= form_input_text( 'officialCode', '', get_lang( 'Administrative code' ), false );
    }
    $html .= form_input_text( 'username', '', get_lang( 'Username' ), false );
    
    $html .= form_input_text( 'email', $data['email'], get_lang( 'Email' ), false );

    // Profile settings for user to add (tutor/course manager)
    $html .= form_row( get_lang( 'Group Tutor' ) . '&nbsp;: ',
                        '<input type="radio" name="tutor" value="1" id="tutorYes" '
                        . ( $data['tutor'] ? 'checked="checked"' : '' ) . ' />'
                        . '<label for="tutorYes">' . get_lang( 'Yes' ) . '</label>'
                
                        . '<input type="radio" name="tutor" value="0"  id="tutorNo" '
                        . ( !$data['tutor'] ? 'checked="checked"' : '' ) . ' />'
                        . '<label for="tutorNo">' . get_lang( 'No' ) . '</label>'
                        );
   
    $html .= form_row( get_lang( 'Manager' ) . '&nbsp;: ',
                        '<input type="radio" name="courseAdmin" value="1" id="courseAdminYes" '
                        . ( $data['courseAdmin'] ? 'checked="checked"' : '') . ' />'
                        . '<label for="courseAdminYes">' . get_lang( 'Yes' ) . '</label>'
                        . '<input type="radio" name="courseAdmin" value="0" id="courseAdminNo" '
                        . ( $data['courseAdmin'] ? '' : 'checked="checked"' ) . ' />'
                        . '<label for="courseAdminNo">' . get_lang( 'No' ) . '</label>'
                        );
    
    // Submit
    $html .= form_row( '&nbsp;',
                         '<input type="submit" name="applySearch" id="applySearch" value="' . get_lang( 'Search' ) . '" />&nbsp;'
                         . claro_html_button( claro_htmlspecialchars( Url::Contextualize( $_SERVER['HTTP_REFERER'] ) ), get_lang( 'Cancel' ) )
                         );
                         
    // close table and form
    $html .= '</table>' . "\n"
    .        '</form>' . "\n"
    ;

    return $html;
}
/**
 * @param array $criterionList -
 *        Allowed keys are 'name', 'firstname', 'email', 'officialCode','username'
 * @param string $courseId (optional)
 *        permit check if user are already enrolled in the concerned cours
 * @param boolean $allCriterion (optional)
 *        define if all submited criterion has to be set.
 * @param boolean $strictCompare (optional)
 *        define if criterion comparison use wildcard or not
 * @return array : existing users who met the criterions
 */

function user_search( $criterionList = array() , $courseId = null, $allCriterion = true, $strictCompare = false, $ignoreDisabledAccounts = false )
{
    $validatedCritList = array('lastname' => '', 'firstname'    => '',
    'email' => ''   , 'officialCode' => '','username'=>'');

    foreach($criterionList as $thisCritKey => $thisCritValue)
    {
        if ( array_key_exists($thisCritKey, $validatedCritList ) )
        {
            $validatedCritList[$thisCritKey] = str_replace('%', '\%', $thisCritValue);
        }
        else claro_die('user_search(): WRONG CRITERION KEY !');
    }

    $operator = $allCriterion  ? 'AND' : 'OR';
    $wildcard = $strictCompare ? '' : '%';

    $tbl_mdb_names   = claro_sql_get_main_tbl();
    $tbl_user        = $tbl_mdb_names['user'           ];
    $tbl_course_user = $tbl_mdb_names['rel_course_user'];

    $sql =  "SELECT U.nom           lastname,
                    U.prenom        firstname,
                    U.email         email,
                    U.officialCode  officialCode,
                    U.username      username,
                    U.`user_id` AS  uid
                   ". ($courseId ? ', CU.user_id AS registered' : '') . "
             FROM `" . $tbl_user . "` AS U ";

    if ($courseId) $sql .= " LEFT JOIN `" . $tbl_course_user . "` AS CU
                                    ON CU.`user_id`=U.`user_id`
                                   AND CU.`code_cours` = '" . $courseId . "' ";

    $sqlCritList = array();

    if ($validatedCritList['lastname'])
    $sqlCritList[] = " U.nom    LIKE '". claro_sql_escape($validatedCritList['lastname'    ])   . $wildcard . "'";
    if ($validatedCritList['firstname'   ])
    $sqlCritList[] = " U.prenom LIKE '". claro_sql_escape($validatedCritList['firstname'   ])   . $wildcard . "'";
    if ($validatedCritList['email'])
    $sqlCritList[] = " U.email  LIKE '". claro_sql_escape($validatedCritList['email'       ])   . $wildcard . "'";
    if ($validatedCritList['officialCode'])
    $sqlCritList[] = " U.officialCode = '". claro_sql_escape($validatedCritList['officialCode']) . "'";
    if ($validatedCritList['username'])
    $sqlCritList[] = " U.username = '". claro_sql_escape($validatedCritList['username']) . "'";

    if ( count($sqlCritList) > 0) $sql .= 'WHERE ' . implode(" $operator ", $sqlCritList);
    
    // ignore disabled account if needed
    if ( $ignoreDisabledAccounts )
    {
        if ( count($sqlCritList) > 0)
        {
            $sql .= " AND U.authSource != 'disabled' ";
        }
        else
        {
            $sql .= "WHERE U.authSource != 'disabled' ";
        }
    }
    
    $sql .= " ORDER BY U.nom, U.prenom";
    
    return claro_sql_query_fetch_all($sql);
}

/**
 * Get html select box for a user language preference
 *
 * @return string html
 * @since 1.8
 */
function user_display_preferred_language_select_box()
{
    $language_list = get_language_to_display_list();
    
    $form = '';
    
    if ( is_array($language_list) && count($language_list) > 1 )
    {
        // get the the current language
        $user_language = language::current_language();
        // build language selector form
        $form .= claro_html_form_select('language',$language_list,$user_language,array('id'=>'language_selector')) ;
    }

    return $form;
}


/**
 * Extended properties
 * some  info  can  be added for each user without change structure of user table.
 * To do that , add a description
 */
/**
 * Get all properties for a user
 *
 * @param int     $userId
 * @param boolean $force reload data from database.
 *                Use it if data can change between
 *                two call in same script
 * @param boolean $getUndefinedProperties. if false, function return only field where data overwrite the default value (NULL)
 *
 * @return array of properties array (array[]=array(propertyId, propertyValue,scope )
 */
function get_user_property_list($userId, $force = false, $getUndefinedProperties = false)
{
    static $userPropertyList = array();
    if (!array_key_exists($userId,$userPropertyList) || $force)
    {
        $tbl = claro_sql_get_tbl(array('user_property','property_definition'));
        if ($getUndefinedProperties)
        {
            $sql = "SELECT
                       propertyId,
                   propertyValue,
                   scope
            FROM  `" . $tbl['user_property'] . "`
            WHERE userId = " . (int) $userId . "
            ORDER BY propertyId";
        }
        else
        {
        $sql = "SELECT up.propertyId,
                   up.propertyValue,
                   up.scope
            FROM  `" . $tbl['user_property'] . "` AS up
            INNER JOIN `" . $tbl['property_definition'] . "` AS pd
            ON up.propertyId = pd.propertyId
            WHERE up.userId = " . (int) $userId . "
            ORDER BY pd.rank, up.propertyId";
        }

        $result = claro_sql_query_fetch_all_rows($sql);
        $propertyList = array();
        foreach ($result as $userInfo) $propertyList[$userInfo['propertyId']] = $userInfo['propertyValue'];
        $userPropertyList[$userId] = $propertyList;
    }
    return $userPropertyList[$userId];
}

/**
 * Return a property of a user.
 *
 * @param interger $userId
 * @param string $propertyId
 * @return mixed value of the selected property for given user
 */

function get_user_property($userId,$propertyId, $force = false)
{
    static $userPropertyList = array();
    if (!array_key_exists($userId,$userPropertyList) || !array_key_exists($propertyId,$userPropertyList[$userId]) || $force )
    {
        $tbl = claro_sql_get_tbl('user_property');
        $sql = "SELECT propertyValue
                FROM `" . $tbl['user_property'] . "`
                WHERE userId = " . (int) $userId . "
                  AND propertyId = '" . claro_sql_escape($propertyId) . "'";
        $userPropertyList[$userId][$propertyId] = claro_sql_query_get_single_value($sql);
    }
    return $userPropertyList[$userId][$propertyId];
}

function set_user_property($userId,$propertyId,$propertyValue, $scope='')
{
    $tbl = claro_sql_get_tbl('user_property');
    $sql = "REPLACE INTO `" . $tbl['user_property'] . "` SET
                userId        =  " . (int) $userId              . ",
                propertyId    = '" . claro_sql_escape($propertyId)    . "',
                propertyValue = '" . claro_sql_escape($propertyValue) . "',
                scope         = '" . claro_sql_escape($scope) . "'";

    return claro_sql_query($sql);
}

/**
 * get the list of extraProperties for user accounts
 *
 * @since claroline 1.8
 *
 * @return array('propertyId'=>array('propertyId', 'label', 'type', 'defaultValue', 'required');
 */
function get_userInfoExtraDefinitionList()
{
    $tbl = claro_sql_get_tbl('property_definition');
    $sql =  "SELECT propertyId, label, type, defaultValue, required
             FROM `" . $tbl['property_definition'] . "`
             WHERE contextScope = 'USER'
             ORDER BY rank
             ";
    $result = claro_sql_query_fetch_all_rows($sql);
    $extraInfoDefList = array();
    foreach ($result as $userPropertyDefinition)
    $extraInfoDefList[$userPropertyDefinition['propertyId']] = $userPropertyDefinition;

    return $extraInfoDefList;
}


/**
 * Set or redefine an extended data for users.
 *
 * @param integer $propertyId
 * @param string $label
 * @param string $type
 * @param mixed $defaultValue
 * @param string $contextScope
 * @param integer $rank
 * @param boolean $required
 * @return claro_sql result
 */
function update_userInfoExtraDefinition($propertyId, $label, $type, $defaultValue, $contextScope, $rank, $required )
{
    $tbl = claro_sql_get_tbl('property_definition');

    $sql = "REPLACE INTO `" . $tbl['property_definition'] . "`
            SET propertyId   = '" . claro_sql_escape($propertyId) . "',
                label        = '" . claro_sql_escape($label) . "',
                type         = '" . claro_sql_escape($type) . "',
                defaultValue = '" . claro_sql_escape($defaultValue) . "',
                contextScope = '" . claro_sql_escape($contextScope) . "',
                rank         = " . (int) $rank . ",
                required     = '" . claro_sql_escape($required) . "'
             WHERE propertyId = '" . claro_sql_escape($propertyId) . "'
             ";

    return claro_sql_query($sql);

}

/**
 * Set or redefine an extended data for users.
 *
 * @param integer $propertyId
 * @param string $contextScope
 * @return claro_sql result
 */
function delete_userInfoExtraDefinition($propertyId, $contextScope )
{
    $tbl = claro_sql_get_tbl('property_definition');

    $sql = "DELETE FROM `" . $tbl['property_definition'] . "`
            WHERE propertyId = '" . claro_sql_escape($propertyId) . "'
            AND  contextScope = '" . claro_sql_escape($contextScope) . "'";

    return claro_sql_query($sql);

}

/**
 * Return the list of user's courses
 * @param int $user_id
 * @return array $userCourseList
 */
function claro_get_user_course_list($user_id = null)
{
    if(is_null($user_id))
    {
        $user_id = claro_get_current_user_id();
    }
    
    $tbl_mdb_names       = claro_sql_get_main_tbl();
    
    $tbl_course          = $tbl_mdb_names['course'];
    $tbl_rel_course_user = $tbl_mdb_names['rel_course_user'];
    
    $sql = "SELECT course.cours_id             AS courseId,
                   course.code                 AS sysCode,
                   course.isSourceCourse       AS isSourceCourse,
                   course.sourceCourseId       AS sourceCourseId,
                   course.administrativeNumber AS officialCode,
                   course.dbName               AS db,
                   course.language             AS language,
                   course.intitule             AS title,
                   course.titulaires           AS titular,
                   course.email                AS email,
                   course.visibility           AS visibility,
                   course.access               AS access,
                   course.registration         AS registration,
                   course.directory            AS dir,
                   course.creationDate         AS creationDate,
                   course.expirationDate       AS expirationDate,
                   course.status               AS status,
                   course_user.isCourseManager AS isCourseManager
            
            FROM    `" . $tbl_course . "`          AS course,
                    `" . $tbl_rel_course_user . "` AS course_user
            
            WHERE course.code         = course_user.code_cours
            AND   course_user.user_id = " . (int) $user_id . "
            ";
            
            if (get_conf('course_order_by') == 'course_title')
            {
                $sql .= "ORDER BY course.intitule";
            }
            else
            {
                $sql .= "ORDER BY course.administrativeNumber";
            }

    $userCourseList = claro_sql_query_fetch_all($sql);

    return $userCourseList;
}

function user_get_private_folder_path( $userId )
{
    return get_path('userRepositorySys')
        . md5($userId.get_conf('platform_id'))
        ;
}

function user_get_private_folder_url( $userId )
{
    return get_path('userRepositoryWeb')
        . md5($userId.get_conf('platform_id'))
        ;
}

function user_get_picture_path( $userData )
{
    if ( !empty( $userData['picture'] ) )
    {
        return user_get_private_folder_path($userData['user_id'])
            . '/' . $userData['picture']
            ;
    }
    else
    {
        return false;
    }
}

function user_get_picture_url( $userData )
{
    if ( !empty( $userData['picture'] ) )
    {
        return user_get_private_folder_url($userData['user_id'])
            . '/' . $userData['picture']
            ;
    }
    else
    {
        return false;
    }
}

function user_get_extra_data($userId)
{
    $extraInfo = array();
    $extraInfoDefList = get_userInfoExtraDefinitionList();
    $userInfo = get_user_property_list($userId);

/**
    $extraInfo['user_id']['label'] = get_lang('User id');
    $extraInfo['user_id']['value'] = $userId;
*/

    foreach ($extraInfoDefList as $extraInfoDef)
    {
        $currentValue = array_key_exists($extraInfoDef['propertyId'],$userInfo)
            ? $userInfo[$extraInfoDef['propertyId']]
            : $extraInfoDef['defaultValue'];

            // propertyId, label, type, defaultValue, required
            $extraInfo[$extraInfoDef['propertyId']]['label'] = $extraInfoDef['label'];
            $extraInfo[$extraInfoDef['propertyId']]['value'] = $currentValue;

    }
    return $extraInfo;
}