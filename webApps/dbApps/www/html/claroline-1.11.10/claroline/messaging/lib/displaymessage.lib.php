<?php // $Id: displaymessage.lib.php 14314 2012-11-07 09:09:19Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * class to display messages
 *
 * @version     1.9 $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */



require_once dirname(__FILE__) . '/permission.lib.php';
FromKernel::uses('utils/htmlsanitizer.lib');

class DisplayMessage
{
    /**
     * display the message
     *
     * @param Message $message Message to display
     * @param string $action list of action autorised on the message
     * @return string HTML source
     */
    public static function display($message,$action)
    {
        if ($message instanceof SentMessage)
        {
            return self::displaySentMessage($message,$action);
        }
        elseif ($message instanceof ReceivedMessage)
        {
            return self::displayReceivedMessage($message,$action);
        }
        else
        {
            throw new Exception("Unsupported message type, must be SentMessage or ReceivedMessage");
        }
    }

    /**
     * diplay a sent message
     *
     * @param SentMessage $message Message to display
     * @param string $action list of action autorised on the message
     * @return string HTML source
     */
    private static function displaySentMessage($message,$action)
    {
        $recipientList = $message->getRecipientList();
        
        $recipientString = '';
        
        if ($recipientList['sentTo'] == 'toUser')
        {
            
            for ( $count=0; $count < count($recipientList['userList']); $count++ )
            {
                if ( $recipientString != '' )
                {
                    $recipientString .= ", ";
                }
                
                $recipientString .= get_lang('%firstName %lastName', array ('%firstName' =>claro_htmlspecialchars($recipientList['userList'][$count]['firstName']), '%lastName' => claro_htmlspecialchars($recipientList['userList'][$count]['lastName'])));
                
                if ( $count > 10 && $count < count($recipientList) )
                {
                    $recipientString .= ",...";
                    break;
                }
            }
        }
        elseif ($recipientList['sentTo'] == 'toCourse')
        {
            $courseData = claro_get_course_data($message->getCourseCode());
            
            $recipientString = get_lang('Course: ')." ";
            
            if ($courseData)
            {
                $recipientString .= $courseData['name'];
            }
            else
            {
                $recipientString .= '?';
            }
        }
        elseif ($recipientList['sentTo'] == 'toGroup')
        {
            $groupInfo = claro_get_group_data(array(CLARO_CONTEXT_COURSE => $message->getCourseCode(),
                                                    CLARO_CONTEXT_GROUP => $message->getGroupId()));
            $courseData = claro_get_course_data($message->getCourseCode());
            
            $recipientString = get_lang('Course').' : ';
            
            if ($courseData)
            {
                $recipientString .= $courseData['officialCode'];
            }
            else
            {
                $recipientString .= '?';
            }
            
            $recipientString .= '; '.get_lang('Group'). ' : ';
            
            if ($groupInfo)
            {
                $recipientString .= $groupInfo['name'];
            }
            else
            {
                $recipientString .= '?'; 
            }
            
        }
        elseif ($recipientList['sentTo'] == 'toAll')
        {
             $recipientString = get_lang('All users of the platform');
        }
        else
        {
            throw new Exception("Unsupported sentTo in recipient list : " . claro_htmlspecialchars($recipientList['sentTo']));
        }
        

        $content = '<div id="im_message">'."\n"
                .  '<h4 class="header">'.claro_htmlspecialchars($message->getSubject()).'</h4>'."\n"
                .  '<div class="imInfoBlock">' . "\n"
                .  '<div class="imCmdList">'.$action.'</div>'
                .  '<div class="imInfo">'
                .       '<span class="imInfoTitle">'.get_lang('Recipient').' : </span>'
                .       '<span class="imInfoValue">'.$recipientString.'</span>'
                .   '</div>'
                .  '<div class="imInfo">'
                .       '<span class="imInfoTitle">'.get_lang('Date').'</span>'
                .       '<span class="imInfoValue">'.claro_html_localised_date(get_locale('dateTimeFormatLong'),strtotime($message->getSendTime())).'</span>'
                .   '</div>'
                ;
        if (!is_null($message->getCourseCode()))
        {
            $content .='<div class="imInfo">'
            .    '<span class="imInfoTitle">'.get_lang('Course').'</span>'
            .    '<span class="imInfoValue">'
            ;
            
            $courseData = claro_get_course_data($message->getCourseCode());
            
            if ($courseData)
            {
                $content .= $courseData['name'];
            }
            else
            {
                $content .= '&nbsp;';
            }
            
            $content .= '</span>'
            .    '</div>';
            
            if (!is_null($message->getGroupId()))
            {
                
                $content .='<div class="imInfo">'
                .    '<span class="imInfoTitle">'.get_lang('Group').'</span>'
                .    '<span class="imInfoValue">'
                ;
                               
                $groupData = claro_get_group_data(array (CLARO_CONTEXT_COURSE => $message->getCourseCode(),
                                                    CLARO_CONTEXT_GROUP => $message->getGroupId()));
                                                    
                if ($courseData)
                {
                    $content .= $groupData['name'];
                }
                else
                {
                    $content .= '&nbsp;';
                }
                
                $content .= '</span>'
            .    '</div>';
            }
            
            if (!is_null($message->getToolsLabel()))
            {
                $content .='<div class="imInfo">'
                .          '<span class="imInfoTitle">'.get_lang('Tool').'</span>'
                .       '<span class="imInfoValue">'
                ;
                
                $md = get_module_data($message->getToolsLabel());
                
                if ($md)
                {
                    $content .= get_lang($md['moduleName']);
                }
                else
                {
                    $content .= '?';
                }
                
                $content .= '</span>'
            .    '</div>';
            }
            
            
        }
        $body = $message->getMessage();
        $body = claro_html_sanitize_all($body);
        
        $content .= '</div>' . "\n" // end of imInfoBlock 
        .    '<div class="imContent">'.claro_parse_user_text($body).'</div>'
        .    '</div>'."\n";

        return $content;
    }

    /**
     * diplay a received message
     *
     * @param ReceivedMessage $message Message to display
     * @param string $action list of action autorised on the message
     * @return string HTML source
     */
    private static function displayReceivedMessage($message,$action)
    {
        
        $content = '<div id="im_message">'."\n"
        .    '<h4 class="header">'.claro_htmlspecialchars($message->getSubject()).'</h4>'."\n"
        .    '<div class="imInfoBlock">' . "\n"
        .    '<div class="imCmdList">'.$action.'</div>'."\n\n"
        .    '<div class="imInfo">'."\n"
        .    ' <span class="imInfoTitle">'.get_lang('Sender').' : </span>'."\n"
        .    ' <span class="imInfoValue">'
        ;
        
        $isAllowed = current_user_is_allowed_to_send_message_to_user($message->getSender());
        
        if ($isAllowed)
        {
            $content .= '<a href="sendmessage.php?cmd=rqMessageToUser&amp;userId='.$message->getSender().'">';
        }
        
        $content .= get_lang('%firstName %lastName', array ('%firstName' =>claro_htmlspecialchars($message->getSenderFirstName()), '%lastName' => claro_htmlspecialchars($message->getSenderLastName())));
        
        if ($isAllowed)
        {
            $content .= "</a>";
        }
        $isManager = FALSE;
        $isAdmin = claro_is_user_platform_admin($message->getSender());
        if (!is_null($message->getCourseCode()))
        {
            $isManager = claro_is_user_course_manager($message->getSender(),$message->getCourseCode());
        }
        
        if ($isManager)
        {
            $content .= '&nbsp;<img src="' . get_icon_url('manager') . '" alt="" />';
        }
        elseif ($isAdmin)
        {
            $content .= '&nbsp;<img src="' . get_icon_url('platformadmin') . '" alt="" />';
        }
        else
        {
            $content .= '&nbsp;<img src="' . get_icon_url('user') . '" alt="" />';
        }
        
        $content .= ''      
                .   ' </span>'."\n"
                .   '</div>'."\n\n"
                .   '<div class="imInfo">'."\n"
                .       '<span class="imInfoTitle">'.get_lang('Date').' : </span>'."\n"
                .       '<span class="imInfoValue">'.claro_html_localised_date(get_locale('dateTimeFormatLong'),strtotime($message->getSendTime())).'</span>'."\n"
                .   '</div>'."\n\n"
                ;
        
        if (!is_null($message->getCourseCode()))
        {
            $content .= '<div class="imInfo">'."\n"
            .   ' <span class="imInfoTitle">'.get_lang('Course').'</span>'."\n"
            .   ' <span class="imInfoValue">'."\n"
            ;
            
            $courseData = claro_get_course_data($message->getCourseCode());
            
            if ($courseData)
            {
                $content .= claro_htmlspecialchars($courseData['officialCode']).' - '.claro_htmlspecialchars($courseData['name']);
            }
            else
            {
                $content .= '?';
            }
            
            $content .= ' </span>'."\n"
            .   '</div>'."\n\n"
            ;
            
            if (!is_null($message->getGroupId()))
            {
                $content .= '<div class="imInfo">'."\n"
                .   ' <span class="imInfoTitle">'.get_lang('Group').' : </span>'."\n"
                .   ' <span class="imInfoValue">'."\n"
                ;
                
                $groupData = claro_get_group_data(array (CLARO_CONTEXT_COURSE => $message->getCourseCode(),
                                                    CLARO_CONTEXT_GROUP => $message->getGroupId()));
                if ($groupData)
                {
                     $content .= $groupData['name'];
                }
                else
                {
                     $content .= '?';
                }
                
                $content .= ' </span>'."\n"
                .   '</div>'."\n\n"
                ;
            }
            
            if (!is_null($message->getToolsLabel()))
            {
                $content .= '<div class="imInfo">'."\n"
                .   ' <span class="imInfoTitle">'.get_lang('Tool').' : </span>'."\n"
                .   ' <span class="imInfoValue">'."\n"
                ;

                $md = get_module_data($message->getToolsLabel());

                if ($md)
                {
                    $content .= get_lang($md['moduleName']);
                }
                else
                {
                    $content .= '?';
                }
                
                $content .= ' </span>'."\n"
                .   '</div>'."\n\n"
                ;
                
            }
        }
        
        $body = $message->getMessage();
        $body = claro_html_sanitize_all($body);
        
        $content .= '</div>' . "\n" // end of imInfoBlock 
        .   '<div class="imContent">'.claro_parse_user_text($body).'</div>'."\n"
        .   '</div>'."\n\n"
        ;

        return $content;
    }

}
