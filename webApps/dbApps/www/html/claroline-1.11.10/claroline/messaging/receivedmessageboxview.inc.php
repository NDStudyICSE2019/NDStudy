<?php // $Id: receivedmessageboxview.inc.php 14314 2012-11-07 09:09:19Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * View of the inbox and trashbox.
 *
 * @version     $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

require_once dirname(__FILE__) . '/lib/displaymessage.lib.php';
// variable initilization

$messageId = (isset($_GET['messageId'])) ? (int)$_GET['messageId']: NULL;

if (isset($displayConfimation) && $displayConfimation)
{
    // link to delete
    $arg_deleting = makeArgLink($link_arg);
    if ($arg_deleting == "")
    {
        $linkDelete = $linkPage."?";
        $linkBack = $linkPage;
    }
    else
    {
        $linkDelete = $linkPage."?".$arg_deleting."&amp;";
        $linkBack = $linkPage."?".$arg_deleting;
    }
    $linkDelete .= "cmd=exDeleteMessage&amp;messageId=".$messageId;
    
    //----------------------- table display --------------------
    
    $confirmationDelete = get_lang('Move to trashbox?').'<br/><br/>'."\n";
    $confirmationDelete .= '<a href="'.$linkDelete.'">'.get_lang('Yes').'</a> | <a href="'.$linkBack.'">'.get_lang('No').'</a>'."\n";
    
    $dialogbox = new DialogBox();
    $dialogbox->question($confirmationDelete);
    $content .= $dialogbox->render();
}

if (isset($displayConfimationEmptyTrashbox) && $displayConfimationEmptyTrashbox)
{
    $arg_emptyTrashBox = makeArgLink($link_arg);
    $linkEmptyTrashBox = $linkPage."?".$arg_emptyTrashBox;
    $linkBack = $linkEmptyTrashBox;
    if ($arg_emptyTrashBox != "")
    {
        $linkEmptyTrashBox .= "&amp;";
    }
    $linkEmptyTrashBox = $linkEmptyTrashBox."cmd=exEmptyTrashBox";

    $confirmationEmpty = get_lang('Empty your trashbox?')
        . '<br /><br />'
        . '<a href="'.$linkEmptyTrashBox.'">'.get_lang('Yes').'</a> | <a href="'.$linkBack.'">'.get_lang('No').'</a>'
        ;
    $dialogbox = new DialogBox();
    $dialogbox->question($confirmationEmpty);
    $content .= $dialogbox->render();
}

// -------------------- Search form ----------------

$javascriptSearchBox = '
    <script type="text/javascript">
        $(document).ready(function(){
            $(\'#SelectorReadStatus\').hide();
            $(\'#searchStrategyBox\').hide();
            $(\'#toSimpleSearch\').hide();
            
            $(\'#toAdvancedSearch\').click(function(){
                $(\'#SelectorReadStatus\').show();
                $(\'#searchStrategyBox\').show();
                
                $(\'#toAdvancedSearch\').hide();
                $(\'#toSimpleSearch\').show();
            });
            
            $(\'#toSimpleSearch\').click(function(){
                $(\'#SelectorReadStatus\').hide();
                $(\'#searchStrategyBox\').hide();
                
                $(\'#toSimpleSearch\').hide();
                $(\'#toAdvancedSearch\').show();
            });
        });
    </script>';

$claroline->display->header->addHtmlHeader($javascriptSearchBox);
    
$arg_search = makeArgLink($link_arg,array('SelectorReadStatus','search','searchStrategy'));
$linkSearch = $linkPage."?".$arg_search;

$searchForm = '<form action="'.$linkSearch.'" method="post">'."\n"
            . '<input type="text" name="search" value="'
            ;
if (isset($link_arg['search']))
{
    $searchForm .= $link_arg['search'];
}
$searchForm .= '" class="inputSearch" /> '."\n";
// read status
$searchForm .= '    <select name="SelectorReadStatus" id="SelectorReadStatus" size="1">'
             . '        <option value="all" '
             ;
if (isset($link_arg['SelectorReadStatus']) && $link_arg['SelectorReadStatus'] == "all")
{
    $searchForm .= 'selected="selected"';
}
$searchForm .= '>'.get_lang("All (Read or not)").'</option>'
            . '        <option value="read" '
            ;
if (isset($link_arg['SelectorReadStatus']) && $link_arg['SelectorReadStatus'] == "read")
{
    $searchForm .= 'selected="selected"';
}
$searchForm .= '>'.get_lang("Only read").'</option>'
            . '        <option value="unread" ';
if (isset($link_arg['SelectorReadStatus']) && $link_arg['SelectorReadStatus'] == "unread")
{
    $searchForm .= 'selected="selected"';
}
$searchForm .= '>'.get_lang("Only not read").'</option>'
            . '    </select> '
            . '<input type="submit" value="'.get_lang("Search").'" />'."\n"
            . '<span id="toAdvancedSearch">[<a href="#">'.get_lang('Advanced').'</a>]</span>'
            . '<span id="toSimpleSearch">[<a href="#">'.get_lang('Simple').'</a>]</span>'
            . '<br />' . "\n"
            . '<span id="searchStrategyBox">' . "\n"
            . '<input type="checkbox" name="searchStrategy" id="searchStrategy" value="'.get_lang('Match the exact expression').'"'
            ;
if (isset($link_arg['searchStrategy']) && $link_arg['searchStrategy'] == 1)
{
    $searchForm .= ' checked="checked"';
}
$searchForm .= ' /><label for="searchStrategy">'.get_lang('Exact expression').'</label>' . "\n"
. '</span>' . "\n"
. '</form>'."\n";

$dialogbox = new DialogBox();
$dialogbox->form($searchForm);

$content .= $dialogbox->render();

//----------------------end selector form -----------------


$arg_sort = makeArgLink($link_arg,array('fieldOrder','order'));
if ($arg_sort == "")
{
    $linkSort = $linkPage."?";
}
else
{
    $linkSort = $linkPage."?".$arg_sort."&amp;";
}

$content .= '<table class="claroTable emphaseLine" width="100%">'."\n\n"
          . '<thead>'."\n"
          . '<tr> '."\n"
          . '<th>'.get_lang("Subject").'</th>'."\n"
          . '<th><a href="'.$linkSort.'fieldOrder=sender&amp;order='.$nextOrder.'">'.get_lang("Sender").'</a></th>'."\n"
          . '<th><a href="'.$linkSort.'fieldOrder=date&amp;order='.$nextOrder.'">'.get_lang("Date").'</a></th>'."\n"
          . '<th class="im_list_action">'."\n"
          . '</thead>'."\n"
          . '<tbody>'."\n"
          . '</th>'."\n"
          . '</tr>'."\n\n";

if ($box->getNumberOfMessage() == 0)
{
    $content .= '<tr><td colspan="4">'.get_lang("No message").'</td></tr>'."\n\n";
}
else
{
    $javascriptDelete = '
        <script type="text/javascript">
        function deleteMessage ( localPath )
        {
            if (confirm("'.get_lang('Are you sure to delete').'"))
            {
                window.location=localPath;
                return false;
            }
            else
            {
                return false;
            }
        }
        </script>';
    $claroline->display->header->addHtmlHeader($javascriptDelete);
    
    $arg_deleting = makeArgLink($link_arg);
    
    if ($arg_deleting == "")
    {
        $link = $linkPage."?";
    }
    else
    {
        $link = $linkPage."?".$arg_deleting."&amp;";
    }
    
    foreach ($box as $key => $message)
    {
        $content .= '<tr';
        if ($message->isPlatformMessage())
        {
            $content .= ' class="platformMessage"';
        }
        elseif (!$message->isRead())
        {
            $content .= ' class="unreadMessage"';
        }
        else
        {
            $content .= ' class="readMessage"';
        }
        $content .= '>'."\n";
        
        // ---------------- sujet
        $content .= '<td>' . "\n";
        if ( ! $message->isPlatformMessage() )
        {
            if (!$message->isRead())
            {
                if (claro_get_current_user_id() == $currentUserId)
                {
                    $content .= '<a href="'.$link.'cmd=exMarkRead&amp;messageId='.$message->getId().'">'
                    .   '<img src="' . get_icon_url('mail_close') . '" alt="'.get_lang("Unread").'" />'
                    .   '</a>&nbsp;';
                }
                //if admin read messagebox of a other user he cannot change status
                else
                {
                    $content .= '<img src="' . get_icon_url('mail_close') . '" alt="'.get_lang("Unread").'" />&nbsp;';
                }
            }
            else
            {
                $content .= '<a href="'.$link.'cmd=exMarkUnread&amp;messageId='.$message->getId().'">'
                .    '<img src="' . get_icon_url('mail_open') . '" alt="'.get_lang("Read").'" />'
                .    '</a>&nbsp;';
            }
        }
        else
        {
            $content .= '<img src="' . get_icon_url('important') . '" alt="" />&nbsp;';
        }
        
        if (!is_null($message->getCourseCode()))
        {
            $courseData = claro_get_course_data($message->getCourseCode());
            if ($courseData)
            {
                $content .= '<span class="im_context">'
                .   '[' . $courseData['officialCode'];
                
                if (!is_null($message->getToolsLabel()))
                {
                    $md = get_module_data($message->getToolsLabel());
                    $content .= ' - '.get_lang($md['moduleName']);
                }
                
                $content .= ']</span> ';
            }
        }
        
        $content.= '<a href="readmessage.php?messageId='.$message->getId().'&amp;userId='.$currentUserId.'&amp;type=received">'
        .   claro_htmlspecialchars($message->getSubject())
        .   '</a>'
        .   '</td>'."\n";
        
        // ------------------ sender
        $content .= '<td>' . "\n";
        $isAllowed = current_user_is_allowed_to_send_message_to_user($message->getSender());
        
        if ($isAllowed)
        {
            $content .= '<a href="sendmessage.php?cmd=rqMessageToUser&amp;userId='.$message->getSender().'">';
        }
        
        if ( $message->getSender() == 0)
        {
            $content .= get_lang( 'Message from %platformName' , array( '%platformName' => get_conf( 'siteName' ) ) );
        }
        else
        {
            $content .= get_lang('%firstName %lastName', array ('%firstName' =>claro_htmlspecialchars($message->getSenderFirstName()), '%lastName' => claro_htmlspecialchars($message->getSenderLastName())));
        }
        
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
        
        $content .= '</td>'."\n"
        // --------------------date
            .'<td>'.claro_html_localised_date(get_locale('dateTimeFormatLong'),strtotime($message->getSendTime())).'</td>'."\n"
        // ------------------- action
            .'<td class="im_list_action">';
        if ( ! $message->isPlatformMessage() )
        {
            if ($link_arg['box'] == "inbox")
            {
                $content .= '<a href="'.$link.'cmd=rqDeleteMessage&amp;messageId='.$message->getId().'"'
                .    ' onclick="return deleteMessage(\''.$link.'cmd=exDeleteMessage&amp;messageId='.$message->getId().'\')">'
                .    '<img src="' . get_icon_url('user-trash-full') . '" alt="" />'
                .    '</a>';
            }
            else
            {
                $content .= '<a href="'.$link.'cmd=exRestoreMessage&amp;messageId='.$message->getId().'">'.get_lang('Restore').'</a>';
            }
        }
        else
        {
            $content .= "&nbsp;";
        }
        $content .=     '</td>'."\n"
        // ----------------- end of line
                    .'</tr>'."\n\n";
    }
}

$content .= '</tbody>'."\n"
          . '</table>'."\n";

// prepare the link to change of page
if ($box->getNumberOfPage()>1)
{
    $arg_paging = makeArgLink($link_arg,array('page'));
    if ($arg_paging == "")
    {
        $linkPaging = $linkPage."?page=";
    }
    else
    {
        $linkPaging = $linkPage."?".$arg_paging."&amp;page=";
    }
    
    if (!isset($link_arg['page']))
    {
        $page=1;
    }
    else
    {
        $page = $link_arg['page'];
    }
    
    $content .= getPager($linkPaging,$page,$box->getNumberOfPage());
}

//------------------ function of the trashbox
if ($link_arg['box'] == "trashbox")
{
    // ---------- generate the link
    $arg_emptyTrashBox = makeArgLink($link_arg);
    $linkTOEmpltyTrashBox = $linkPage."?".$arg_emptyTrashBox;
    if ($arg_emptyTrashBox != "")
    {
        $linkTOEmpltyTrashBox .= "&amp;";
    }
    $linkToRqEmptyTrashBox = $linkTOEmpltyTrashBox."cmd=rqEmptyTrashBox";
    $linkToExEmptyTrashBox = $linkTOEmpltyTrashBox."cmd=exEmptyTrashBox";
    // ------------ end of generating link
    
    $javascriptDelete = '
        <script type="text/javascript">
        function emptyTrashBox ( localPath )
        {
            if (confirm("'.get_lang('Are you sure to empty trashbox ?').'"))
            {
                window.location=localPath;
                return false;
            }
            else
            {
                return false;
            }
        }
        </script>';
    $claroline->display->header->addHtmlHeader($javascriptDelete);
    
    $content .= "<br />";
    $menu[] = '<a href="'.$linkToRqEmptyTrashBox.'"
                onclick="return emptyTrashBox(\''.$linkToExEmptyTrashBox.'\')" class="claroCmd" >'.get_lang('Empty trashbox').'</a>';
    
    $content .= claro_html_menu_horizontal($menu);
    $content .= "<br /><br />\n\n";
}
// ------------------ end of fonction of the trash box