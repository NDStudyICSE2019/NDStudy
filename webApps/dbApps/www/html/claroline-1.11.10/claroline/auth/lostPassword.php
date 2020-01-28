<?php // $Id: lostPassword.php 14517 2013-08-14 07:47:46Z zefredz $

/**
 * CLAROLINE
 *
 * This script allows users to retrieve the password of their profile(s)
 * on the basis of their e-mail address. The password is send via email
 * to the user.
 *
 * Special case : If the password are encrypted in the database, we have
 * to generate a new one.
 *
 * @version     $Revision: 14517 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLAUTH
 * @author      Claro Team <cvs@claroline.net>
 */

require '../inc/claro_init_global.inc.php';

$nameTools = get_lang('Lost password');

// DB tables definition
$tbl_mdb_names = claro_sql_get_main_tbl();
$tbl_user      = $tbl_mdb_names['user'];

// library for authentification and mail
include_once(get_path('incRepositorySys') . '/lib/user.lib.php');
include_once(get_path('incRepositorySys') . '/lib/sendmail.lib.php');

// Initialise variables

$dialogBox = new DialogBox();
$extAuthPasswordCount = 0;
$passwordFound = false;
$userAccountList = array();

// Get the forgotten email from the form

if ( isset ($_REQUEST['Femail']) ) $emailTo = strtolower(trim($_REQUEST['Femail']));
else                               $emailTo = '';

// Main section

if ( isset($_REQUEST['searchPassword']) && !empty($emailTo) )
{
    // search user with this email

    $sql = "SELECT  `user_id`   `uid`       ,
                    `nom`       `lastName`  ,
                    `prenom`    `firstName` ,
                    `username`  `loginName` ,
                    `password`              ,
                    `email`                 ,
                    `authSource`            ,
                    `creatorId`
             FROM `" . $tbl_user . "`
             WHERE LOWER(email) = '" . claro_sql_escape($emailTo) . "'";

    $userList = claro_sql_query_fetch_all($sql);

    if ( count($userList) > 0 )
    {
        $allowedAuthSources = AuthDriverManager::getDriversAllowingLostPassword();
        
        foreach ( $userList as $user )
        {
            if ( isset( $allowedAuthSources[$user['authSource']] ) )
            {
                $passwordFound = true;

                if (get_conf('userPasswordCrypted',false))
                {
                    /*
                     * If password are crypted, we can not send them as such.
                     * We have to generate new ones.
                     */

                    $user['password'] = generate_passwd();

                    // UPDATE THE DB WITH THE NEW GENERATED PASSWORD

                    $sql = 'UPDATE `' . $tbl_user . '`
                            SET   `password` = "'. claro_sql_escape(md5($user['password'])) .'"
                             WHERE `user_id` = "'.$user['uid'].'"';

                    if ( claro_sql_query($sql) === false )
                    {
                        trigger_error('<p align="center">'. get_lang('Wrong operation') . '</p>', E_USER_ERROR);
                    }
                }
                
                // Build user account list for email
                $userAccountList[] = array('firstname' => $user['firstName'], 'lastname' => $user['lastName'], 'username' => $user['loginName'], 'password' => $user['password']);
            }
            else
            {
                $extAuthPasswordCount ++;
            }
        }

        if ( $passwordFound )
        {

            /*
             * Prepare the email message wich has to be send to the user
             */

            // mail subject
            $emailSubject = get_lang('Login request') . ' ' . get_conf('siteName');
            
            $blockLoginInfo = '';
            
            foreach($userAccountList as $userAccount)
            {
                $blockLoginInfo .= get_block('blockLoginInfo',
                    array(
                    '%firstname'=> $userAccount['firstname'],
                    '%lastname' => $userAccount['lastname'],
                    '%username' => $userAccount['username'],
                    '%password' => $userAccount['password']
                    )
                );
            }
            
            $emailBody = get_block('blockLoginRequest',
                array(
                '%siteName'=> get_conf('siteName'),
                '%rootWeb' => get_path('rootWeb'),
                '%loginInfo' => $blockLoginInfo
                )
            );

            // send message
            if( claro_mail_user($userList[0]['uid'], $emailBody, $emailSubject) )
            {
                $dialogBox->success( get_lang('Your password has been emailed to'). ' : ' . $emailTo );
            }
            else
            {
                $dialogBox->error( get_lang('The system is unable to send you an e-mail.') . '<br />'
                .   get_lang('Please contact') . ' : '
                .   '<a href="mailto:' . get_conf('administrator_email') . '?BODY=' . $emailTo . '">'
                .   get_lang('Platform administrator')
                .   '</a>' );
            }
        }
    }
    else
    {
        $dialogBox->error( get_lang('There is no user account with this email address.') );
    }

    if ($extAuthPasswordCount > 0 )
    {
        if ( $extAuthPasswordCount == count($userList) )
        {
            $dialogBox->warning( get_lang('Your password(s) is (are) recorded in an external authentication system outside the platform.') );
            
        }
        else
        {
            $dialogBox->warning( get_lang('Passwords of some of your user account(s) are recorded an in external authentication system outside the platform.') );

        }
        $dialogBox->info( get_lang('For more information take contact with the platform administrator.') );
    }
}


////////////////////////////////////////////////////
// display section

$out = '';

// display title

$out .= claro_html_tool_title($nameTools);

// display message box

if ( ! $passwordFound )
{
    $dialogBox->title( get_lang('Enter your email so we can send you your password.') );
    
    $dialogBox->form( '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">'
    .       '<input type="hidden" name="searchPassword" value="1" />'
    .       '<label for="Femail">' . get_lang('Email') . ' : </label>'
    .       '<br />'
    .       '<input type="text" name="Femail" id="Femail" size="50" maxlength="100" value="' . claro_htmlspecialchars($emailTo) . '" />'
    .       '<br /><br />'
    .       '<input type="submit" name="retrieve" value="' . get_lang('Ok') . '" />&nbsp; '
    .       claro_html_button(get_conf('urlAppend') . '/index.php', get_lang('Cancel'))
    .       '</form>'
    );
}

$out .= $dialogBox->render();

// display form

$claroline->display->body->appendContent($out);

echo $claroline->display->render();