<?php // $Id: messagescourse.php 14314 2012-11-07 09:09:19Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Send message for a user in context of course.
 *
 * @version     1.9 $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

// initializtion
require_once dirname(__FILE__) . '/../../claroline/inc/claro_init_global.inc.php';
include claro_get_conf_repository() . 'CLMSG.conf.php';
require_once dirname(__FILE__) . '/lib/message/messagetosend.lib.php';
require_once dirname(__FILE__) . '/lib/recipient/userlistrecipient.lib.php';
require_once get_path('incRepositorySys') . '/lib/group.lib.inc.php';
require_once get_path('incRepositorySys') . '/lib/class.lib.php';
require_once get_path('incRepositorySys') . '/lib/course_user.lib.php';
       
claro_set_display_mode_available(true);

// move to kernel
$claroline = Claroline::getInstance();

// ------------- Business Logic ---------------------------
require_once 'lib/messagebox/inbox.lib.php';
if (!claro_is_user_authenticated())
{
    claro_disp_auth_form(true);
}
    
if (!claro_is_in_a_course() || (!claro_is_course_manager() && !claro_is_platform_admin()))
{
    claro_die(get_lang("Not allowed"));
}

$displayForm = FALSE;
$content = "";
$from = (isset($_REQUEST['from'])) ? get_module_entry_url(strtoupper($_REQUEST['from'])) : $_SERVER['PHP_SELF'];

//commande
$acceptedCmdList = array('exSendMessage');

if (isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'], $acceptedCmdList))
{
    if ($_REQUEST['cmd'] == 'exSendMessage')
    {
                
        /*
         * Explode the values of incorreo in groups and users
         */
        $userIdList = array();
        $groupIdList = array();
        $classIdList = array();
        foreach($_REQUEST['incorreo'] as $thisIncorreo)
        {
            list($type, $elmtId) = explode(':', $thisIncorreo);

            switch($type)
            {
                case 'CLASS':
                $classIdList[] = $elmtId;
                $classIdList = array_merge($classIdList,getSubClasses($elmtId));
                break;
                
                case 'GROUP':
                $groupIdList[] = $elmtId;
                break;

                case 'USER':
                $userIdList[] = $elmtId;
                break;
            }

        } // end foreach

        /*
        * Select the students of the different groups
        */
        if ( !empty($classIdList) )
        {
            $userIdList = array_merge($userIdList,get_class_list_user_id_list($classIdList));
        }
        
        if ( !empty($groupIdList) )
        {
            $userIdList = array_merge($userIdList,get_group_list_user_id_list($groupIdList));
        }
        
        
        // subject
        $subject = $_REQUEST['subject'];
        if (empty($subject))
        {
           $subject .= get_lang('Message from your lecturer');
        }
        

        // content
        $body = $_REQUEST['content'];

        
         
        $message = new MessageToSend(claro_get_current_user_id(),$subject,$body);
        $message->setCourse(claro_get_current_course_id());
        
        $recipient = new UserListRecipient();
        $recipient->addUserIdList($userIdList);
        
        $recipient->sendMessage($message);
        
        $dialogBox = new DialogBox();
        $dialogBox->success( get_lang('Message sent') );
        
        if ( $failure = claro_failure::get_last_failure() )
        {
            $dialogBox->warning( $failure );
        }

        $dialogBox->info('<a href="' . $_SERVER['PHP_SELF'] . '">&lt;&lt;&nbsp;' . get_lang('Back') . '</a>');
        $content .= $dialogBox->render();

    } // end cmd exSendMessage
    
}
else
{
    
    /*
    * Get user    list of    this course
    */

    $singleUserList = claro_get_course_user_list(claro_get_current_course_id());
    
    $userList = array();

    if ( is_array($singleUserList) && !empty($singleUserList) )
    {
        foreach ( $singleUserList as $singleUser  )
        {
            $userList[] = $singleUser;
        }
    }

    /*
    * Get group list of this course
    */
    $courseTableName = get_module_course_tbl(array('group_team','group_rel_team_user'));
    $courseTableName = get_module_course_tbl(array('group_team','group_rel_team_user'));
    $mainTableName = claro_sql_get_main_tbl();

    $sql = "SELECT 
                `g`.`id`,
                `g`.`name`,
                COUNT(`cu`.`user_id`) AS `userNb`
            FROM 
                `" . $courseTableName['group_team'] . "` AS `g` 
            LEFT JOIN 
                `" . $courseTableName['group_rel_team_user'] . "` AS `gu`
            ON 
                `g`.`id` = `gu`.`team`
            LEFT JOIN 
                `".$mainTableName['rel_course_user']."` AS cu
            ON 
                `gu`.`user` = cu.user_id
            AND 
                cu.code_cours = '".claro_sql_escape(claro_get_current_course_id())."'
            GROUP BY `g`.`id`";

    $groupSelect = claro_sql_query_fetch_all($sql);

    $groupList = array();

    if ( is_array($groupSelect) && !empty($groupSelect) )
    {
        foreach ( $groupSelect as $groupData  )
        {
            $groupList[$groupData['id']] = $groupData;
        }
    }

    /*
     * Get class user list of this course
     */
    
    $classList = get_class_list_of_course(claro_get_current_course_id());
    $displayForm = TRUE;
}

// ------------ Prepare display --------------------
if ($displayForm)
{

    $claroline->display->header->addHtmlHeader( '<script type="text/javascript" language="JavaScript">
        
        <!-- Begin javascript menu swapper
        
        function move(fbox,    tbox)
        {
            var    arrFbox    = new Array();
            var    arrTbox    = new Array();
            var    arrLookup =    new    Array();
        
            var    i;
            for    (i = 0;    i <    tbox.options.length; i++)
            {
                arrLookup[tbox.options[i].text]    = tbox.options[i].value;
                arrTbox[i] = tbox.options[i].text;
            }
        
            var    fLength    = 0;
            var    tLength    = arrTbox.length;
        
            for(i =    0; i < fbox.options.length;    i++)
            {
                arrLookup[fbox.options[i].text]    = fbox.options[i].value;
        
                if (fbox.options[i].selected &&    fbox.options[i].value != "")
                {
                    arrTbox[tLength] = fbox.options[i].text;
                    tLength++;
                }
                else
                {
                    arrFbox[fLength] = fbox.options[i].text;
                    fLength++;
                }
            }
        
            arrFbox.sort();
            arrTbox.sort();
            fbox.length    = 0;
            tbox.length    = 0;
        
            var    c;
            for(c =    0; c < arrFbox.length; c++)
            {
                var    no = new Option();
                no.value = arrLookup[arrFbox[c]];
                no.text    = arrFbox[c];
                fbox[c]    = no;
            }
            for(c =    0; c < arrTbox.length; c++)
            {
                var    no = new Option();
                no.value = arrLookup[arrTbox[c]];
                no.text    = arrTbox[c];
                tbox[c]    = no;
            }
        }
        
        function valida()
        {
            var f =    document.datos;
            var dat;
        
            var incorreo = f.elements[\'incorreo[]\'];
        
            if (incorreo.length <    1) {
                alert("' . clean_str_for_javascript(get_lang('You must select some users')) . '");
                return false;
            }
            for    (var i=0; i<incorreo.length; i++)
                incorreo[i].selected = incorreo[i].checked = true
        
            dat=f.emailContent.value;
            if (dat.length == 0)
            {
                //old: Debe    introducir el Texto    del    Mensaje
                alert("' . clean_str_for_javascript(get_lang('You must introduce the message text')) . '");
                f.emailContent.focus();
                f.emailContent.select();
                return false;
            }
        
            f.submit();
            return true;
        }
        
        //    End    -->
        </script>');
    
    $content .= get_lang('To send a message, select groups of users (marked with a * in the front) or single users from the list on the left.') . "<br/><br/>\n" ;

    $content .= '<div class="messagesform">'."\n";
    
    $content .= '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="datos" '
    .    'onsubmit="return valida();">' . "\n"
    .    claro_form_relay_context()."\n"
    .    '<div class="userList">'."\n"
    .    '<input type="hidden" name="claroFormId" value="' . uniqid('') . '" />'."\n"
    .    '<input type="hidden" name="cmd" value="exSendMessage" />'."\n"
    .    '<table class="multiselect">'."\n"
    .    '<tr>'."\n"
    .    '<td>'."\n"
    .    get_lang('User list') . '<br/>'."\n"
    .    '<select name="nocorreo[]" size="15" multiple="multiple" id="mslist1">' . "\n"
    ;

    if ( $groupList )
    {
        foreach( $groupList as $thisGroup )
        {
            $content .= '<option value="GROUP:' . $thisGroup['id'] . '">'
            .    '* ' . $thisGroup['name'] . ' (' . $thisGroup['userNb'] . ' ' . get_lang('Users') . ')'
            .    '</option>' . "\n";
        }
    }
    
    if ( $classList )
    {
        foreach( $classList as $thisClass )
        {
            $content .= '<option value="CLASS:' . $thisClass['id'] . '">'
            .    '* ' . $thisClass['name'] . ' ('. get_class_user_number($thisClass['id']) . ' ' . get_lang('Users')  . ')'
            .    '</option>' . "\n";
        }
    }

    $content .= '<option value="">'
    .    '---------------------------------------------------------'
    .    '</option>' . "\n"
    ;

    // display user list

    foreach ( $userList as $thisUser )
    {
        $content .= '<option value="USER:' . $thisUser['user_id'] . '">'
        .    ucwords(strtolower($thisUser['nom'] . ' ' . $thisUser['prenom']))
        .    '</option>' . "\n"
        ;
    }

    // WATCH OUT ! form elements are called by numbers "form.element[3]"...
    // because select name contains "[]" causing a javascript
    // element name problem List of selected users

    $content .= '</select></td>' . "\n"
    .    '<td class="arrows">'
    .    '<a href="#" class="msadd"><img src="' . get_icon_url('go_right') . '" /></a>'
    .    '<br /><br />'
    .    '<a href="#" class="msremove"><img src="' . get_icon_url('go_left') . '" /></a>'
    .    '</td>'
    .    '<td>'
    .    get_lang('Selected Users')."<br/>" . "\n"
    .    '<select name="incorreo[]" size="15" multiple="multiple" id="mslist2">'
    .    '</select>'."\n"
    .    '</td>'
    .    '</tr>'
    .    '</table>'."\n"
    .    '<div class="composeMessage">'."\n"
    .    '<br/>'.get_lang('Subject') . '<br />' . "\n"
    .    '<input type="text" name="subject" maxlength="255" size="40" />'
    .    '<br/>'.get_lang('Message') .'<br/>'. "\n"
    .    claro_html_textarea_editor('content', "")
    .    '<br/><input type="submit" name="submitMessage" value="' . get_lang('Submit') . '" />'
    .     claro_html_button(claro_htmlspecialchars(Url::Contextualize($from)), get_lang('Cancel'))
    .    '</div>'."\n"
    .    '</div>'."\n\n"
    .    '</form>'."\n\n"
    .    '</div>'
    ;
    
}
$claroline->display->body->appendContent(claro_html_tool_title(get_lang('Messages to selected users')));
$claroline->display->body->appendContent($content);

// ------------- Display page -----------------------------
echo $claroline->display->render();
// ------------- End of script ----------------------------