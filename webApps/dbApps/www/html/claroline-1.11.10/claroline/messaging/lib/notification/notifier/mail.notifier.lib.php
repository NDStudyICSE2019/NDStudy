<?php // $Id: mail.notifier.lib.php 14363 2013-01-28 10:55:17Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * mailnofifier class
 *
 * @version     1.9 $Revision: 14363 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

//load notfier class
require_once dirname(__FILE__) . '/../notifier.lib.php';
//load course data
require_once get_path('incRepositorySys') . '/lib/course.lib.inc.php';
// load  PHPMail class
require_once get_path('incRepositorySys') . '/lib/sendmail.lib.php';

include claro_get_conf_repository() . 'CLMSG.conf.php';

class MailNotifier implements MessagingNotifier 
{
    /**
     * notify by email the user of the reception of a message
     *
     * @param array of int: $userDataList user identificatin list
     * @param MessageToSend $message message envoy�
     * @param int $messageId identification of the message
     * 
     */
    public function notify ($userDataList,$message,$messageId)
    {
        if (!get_conf('mailNotification',TRUE))
        {
            return;
        }
        
        // sender name and email
        if ( $message->getSender() == 0 )
        {
            $userData = array( 'mail' => get_conf( 'no_reply_mail' ) ? get_conf( 'no_reply_mail' ) : get_conf( 'administrator_email' ),
                               'firstName' => get_lang( 'Message from %platformName'
                                                        , array( '%platformName' => get_conf( 'siteName' ) ) ),
                               'lastName' => '' );
        }
        else
        {
            $userData = claro_get_current_user_data();
        }
        
        //************************************ IS MANAGER
        $stringManager = false;
        $courseManagers =  claro_get_course_manager_id($message->getCourseCode());
        $nbrOfManagers = count($courseManagers);
        
        for ($countManager = 0; $countManager < $nbrOfManagers; $countManager++)
        {
            if ($message->getSender() == $courseManagers[$countManager])
            {
                $courseData = claro_get_course_data($message->getCourseCode());
                
                $stringManager = get_block('Course manager of %course%(%courseCode%)',
                array('%course%' => $courseData['name'], '%courseCode%'=> $courseData['officialCode']));
            }
        }
        
        
        //---------------------- email subject
        $emailSubject = '[' . get_conf('siteName');
        
        if (!is_null($message->getCourseCode()))
        {
            $courseData = claro_get_course_data($message->getCourseCode());
            if ($courseData)
            {
                $emailSubject .= ' - ' . $courseData['officialCode'];
            }
        }
        
        $emailSubject .= '] '.$message->getSubject();

        //------------------------------subject
        /* $altBody = get_lang('If you can\'t read this message go to: ') . rtrim( get_path('rootWeb'), '/' ) . '/claroline/messaging/readmessage.php?messageId=' . $messageId . '&type=received' . "\n\n"
            . '-- '
            . claro_get_current_user_data('lastName') . " " . claro_get_current_user_data('firstName') . "\n"
            . $stringManager
            . "\n\n" . get_conf('siteName') ." <" . get_conf('rootWeb') . '>' . "\n"
            . '   ' . get_lang('Administrator') . ' : ' . get_conf('administrator_name') . ' <' . get_conf('administrator_email') . '>' . "\n"
            ; */
        
        
        //-------------------------BODY
        $msgContent = claro_parse_user_text($message->getMessage());
        
        $urlAppend = get_path('url');
        
        if ( !empty($urlAppend) )
        {
            $msgContent = preg_replace( '!href="'.get_path('url').'!', 'href="'.rtrim(get_path('rootWeb'),'/').'/', $msgContent );
            $msgContent = preg_replace( '!\>'.get_path('url').'!', '>'.get_path('rootWeb'), $msgContent );
        }
        else
        {
            $msgContent = preg_replace( '!href="/!', 'href="'.rtrim(get_path('rootWeb'),'/').'/', $msgContent );
        }
        
        $emailBody = "<html><head></head><body>" . $msgContent
                    . '<br /><br />'
               // footer
                    . '-- <br />'
                    . get_lang( '%firstName %lastName', array(
                        '%firstName' => $userData['firstName'],
                        '%lastName' => $userData['lastName'] ) 
                    ) . "<br />"
                    .$stringManager
                    . '<br /><br /><a href="' . get_conf('rootWeb') . '">' . get_conf('siteName') . '</a><br />'
                    . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . get_lang('Administrator')  . ': <a href="mailto:' . get_conf('administrator_email') . '">' . get_conf('administrator_name') . '</a><br />'
                    . '</body></html>'
                    ;
        //******************************** END BODY
        //******************************************
        
        if ( empty( $userData['mail'] ) || ! is_well_formed_email_address( $userData['mail'] ) )
        {
            // do not send email for a user with no mail address
            pushClaroMessage('Mail Notification Failed : User has no email or an invalid one : '.var_export($userData, true).'!');
            return claro_failure::set_failure( get_lang("Mail Notification Failed : You don't have any email address defined in your user profile or the defined email address is not valid." ) );
        }
        
        self::emailNotification(
            $userDataList,
            $emailBody,
            $emailSubject,
            $userData['mail'],
            get_lang( '%firstName %lastName', array(
                '%firstName' => $userData['firstName'],
                '%lastName' => $userData['lastName'] ) 
            )
        );
    }
    
    /**
     * Send a mail to the user list
     *
     * @param int $userIdList list of the user
     * @param string $message body of the mail
     * @param string $subject subject of the mail
     * @param string $specificFrom email of the sender
     * @param string $specificFromName name to display
     * @param string $altBody link of the message in case of problem of read
     * 
     */
    protected static function emailNotification($userIdList, $message, $subject , $specificFrom='', $specificFromName='',$altBody='')
    {
        if ( ! is_array($userIdList) ) $userIdList = array($userIdList);
        if ( count($userIdList) == 0)  return 0;
    
        $tbl      = claro_sql_get_main_tbl();
        $tbl_user = $tbl['user'];
    
        $sql = 'SELECT DISTINCT email
                FROM `'.$tbl_user.'`
                WHERE user_id IN ('. implode(', ', array_map('intval', $userIdList) ) . ')';
    
        $emailList = claro_sql_query_fetch_all_cols($sql);
        $emailList = $emailList['email'];
        $emailList = array_filter($emailList, 'is_well_formed_email_address');
    
        $mail = new ClaroPHPMailer();
        $mail->IsHTML(true);
        
        if (!empty($altBody))
        {
            $mail->AltBody = $altBody;
        }    
        
        if ($specificFrom != '')     $mail->From = $specificFrom;
        else                         $mail->From = get_conf('administrator_email');
    
        if ($specificFromName != '') $mail->FromName = $specificFromName;
        else                         $mail->FromName = get_conf('administrator_name');
    
        $mail->Sender = $mail->From;
    
        if (strlen($subject)> 78)
        {
            $message = get_lang('Subject') . ' : ' . $subject . "<br />\n\n" . $message;
            $subject = substr($subject,0,73) . '...';
        }
        
        $mail->Subject = $subject;
        $mail->Body    = $message;
        
        if ( claro_debug_mode() )
        {
            $message = '<p>' . get_lang('Subject') . ' : ' . claro_htmlspecialchars($subject) . '</p>' . "\n"
                     . '<p>' . get_lang('Message') . ' : <pre>' . claro_htmlspecialchars($message) . '</pre></p>' . "\n"
                     . '<p>' . get_lang('Sender') . ' : ' . claro_htmlspecialchars($mail->FromName) . ' - ' . claro_htmlspecialchars($mail->From) . '</p>' . "\n"
                     . '<p>' . get_lang('Recipient') . ' : ' . implode(', ', $emailList) . '</p>' . "\n";
            pushClaroMessage($message,'mail');
        }
    
        $error_list=  array();
        
        foreach ( $emailList as $thisEmail )
        {
            try
            {
                 $mail->AddAddress($thisEmail);
                 $mail->Send();
            }
            catch ( phpmailerException $exception ) 
            {
                if ( claro_debug_mode() )
                {
                    pushClaroMessage('Mail Notification Failed ' .$exception->__toString() );
                    $error_list[] = $thisEmail ;
                }
            }
            
            $mail->ClearAddresses();
        }
    }
}