<?php // $Id: admin_search.php 14314 2012-11-07 09:09:19Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Allow the administrator to search through the messages.
 *
 * @version     1.9 $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

$cidReset = true;
require_once dirname(__FILE__) . '/../../claroline/inc/claro_init_global.inc.php';
// manager of the admin message box
require_once dirname(__FILE__) . '/lib/messagebox/adminmessagebox.lib.php';

require_once dirname(__FILE__) . '/lib/tools.lib.php';

require_once dirname(__FILE__) . '/lib/displaymessage.lib.php';

// search user info
require_once get_path('incRepositorySys') . '/lib/user.lib.php';

// move to kernel
$claroline = Claroline::getInstance();

// ------------- permission ---------------------------
if ( ! claro_is_user_authenticated())
{
    claro_disp_auth_form(false);
}

if ( ! claro_is_platform_admin() )
{
    claro_die(get_lang('Not allowed'));
}

// -------------- business logic ----------------------
$content = "";
$arguments = array();

$displayTable = true;

$acceptedSearch = array('fromUser','olderThan','timeInterval','platformMessage');
$acceptedCommand = array('rqDeleteSelection','exDeleteSelection','rqDeleteMessage','exDeleteMessage');

$box = new AdminMessageBox();
$strategy = $box->getSelector();

$JsLoader = JavascriptLoader::getInstance();
$JsLoader->load('jquery');

$messageId = isset($_REQUEST['message_id']) ? (int)$_REQUEST['message_id'] : NULL;

// ---------------- order

if (isset($_REQUEST['order']))
{
    $order = $_REQUEST['order'] == 'asc' ? 'asc' : 'desc';
        
    $arguments['order'] = $order;
    
    if ($arguments['order'] == 'asc')
    {
        $strategy->setOrder(AdminBoxStrategy::ORDER_ASC);
        $nextOrder = 'desc';
    }
    else
    {
        $strategy->setOrder(AdminBoxStrategy::ORDER_DESC);
        $nextOrder = 'asc';
    }
}
else
{
    $nextOrder = 'asc';
}

if (isset($_REQUEST['fieldOrder']))
{
    $arguments['fieldOrder'] = isset( $_REQUEST['fieldOrder'] )
        && in_array( $_REQUEST['fieldOrder'], array('name','username','date') )
        ? $_REQUEST['fieldOrder']
        : 'date'
        ;
    
    if ($arguments['fieldOrder'] == 'name')
    {
        $strategy->setFieldOrder(AdminBoxStrategy::FIELD_ORDER_NAME);
    }
    elseif ($arguments['fieldOrder'] == 'username')
    {
        $strategy->setFieldOrder(AdminBoxStrategy::FIELD_ORDER_USERNAME);
    }
    else
    {
        $strategy->setFieldOrder(AdminBoxStrategy::FIELD_ORDER_DATE);
    }
}


if (isset($_REQUEST['search']) && in_array($_REQUEST['search'],$acceptedSearch))
{
    $arguments['search'] = $_REQUEST['search'];
    
    if ($arguments['search'] == 'fromUser')
    {
        $name = isset($_REQUEST['name']) ? trim(strip_tags($_REQUEST['name'])) : NULL;
        $subTitle = get_lang('All messages from a user');
        if (is_null($name) || $name == "")
        {
            $displayTable = FALSE;
        }
        else
        {
            $arguments['name'] = $name;
            $strategy->setStrategy(AdminBoxStrategy::SENT_BY , array('name' => $arguments['name']));
        }
    }

    if ($arguments['search'] == 'olderThan')
    {
        $subTitle = get_lang('All messages older than');
        
        $date = isset($_REQUEST['date']) ? $_REQUEST['date'] : NULL;
        
        if (!is_null($date))
        {
            
            list($day,$month,$year) = explode('/',$date);
            
            if (!checkdate($month,$day,$year))
            {
                $date = null;
            }
            else
            {
                $date = $day.'/'.$month.'/'.$year;
            }
            //echo $date;
        }
        
        if (is_null($date))
        {
            $displayTable = FALSE;
        }
        else
        {
            $arguments['date'] = $date;
            $strategy->setStrategy(AdminBoxStrategy::OLDER_THAN , array('date' => strtotime($year.'-'.$month.'-'.$day)));
        }
    }
    
    if ($arguments['search'] == 'timeInterval')
    {
        $subTitle = get_lang('All messages in date interval');
        
        $date1 = isset($_REQUEST['date1']) ? $_REQUEST['date1'] : NULL;
        
        if (!is_null($date1))
        {
            list($day1,$month1,$year1) = explode('/',$date1);
            
            if (!checkdate($month1,$day1,$year1))
            {
                $date1 = null;
            }
            else
            {
                $arguments['date1'] = $day1.'/'.$month1.'/'.$year1;
            }
        }
        
        $date2 = isset($_REQUEST['date2']) ? $_REQUEST['date2'] : NULL;
        
        if (!is_null($date2))
        {
            list($day2,$month2,$year2) = explode('/',$date2);
            
            if (!checkdate($month2,$day2,$year2))
            {
                $date2 = null;
            }
            else
            {
                $arguments['date2'] = $day2.'/'.$month2.'/'.$year2;
            }
        }
        
        if (!isset($arguments['date1']) || !isset($arguments['date2']))
        {
            $displayTable = FALSE;
        }
        else
        {
            $strategy->setStrategy(AdminBoxStrategy::DATED_INTERVAL ,
                array('date1' => strtotime($year1.'-'.$month1.'-'.$day1)
                    ,'date2' => strtotime($year2.'-'.$month2.'-'.$day2)));
        }
    }

    if ($arguments['search'] == 'platformMessage')
    {
        $subTitle = get_lang('All platform messages');
        $strategy->setStrategy(AdminBoxStrategy::PLATFORM_MESSAGE);
    }
}
else
{
    claro_redirect('./admin.php');
}

if (isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'],$acceptedCommand))
{
    
    $cmd = $_REQUEST['cmd'];
    
    if ($cmd == "exDeleteSelection" && isset($_REQUEST['msg'])
            && is_array($_REQUEST['msg']))
    {
        
        $box->deleteMessageList($_REQUEST['msg']);
    }
    
    if ($cmd == "rqDeleteSelection" && isset($_REQUEST['msg'])
            && is_array($_REQUEST['msg']))
    {
        
        $form =    get_lang('Are you sure to delete selected message?')."\n"
                        .'<form action="" method="post">'."\n"
                        .'<input type="hidden" name="cmd" value="exDeleteSelection" />'."\n\n"
                        ;
        foreach ( $_REQUEST['msg'] as $count => $idMessage )
        {
            $form .= '<input type="hidden" name="msg[]" value="'.(int)$idMessage.'" />'."\n";
        }
        
        $form .= '<input type="submit" value="'.get_lang('Yes').'" /> '."\n"
                .'<a href=""><input type="button" value="'.get_lang('No').'" /></a>'   ."\n"
                .'</form>'."\n\n"
                ;
        
        $dialogbox = new DialogBox();
        $dialogbox->form($form);
        
        $content .= $dialogbox->render();
    }
    
    if ($cmd == "rqDeleteMessage" && ! is_null($messageId))
    {
        $argDelete = makeArgLink($arguments);
        if ($argDelete == "")
        {
            $linkDelete = $_SERVER['PHP_SELF']."?";
        }
        else
        {
            $linkDelete = $_SERVER['PHP_SELF']."?".$argDelete."&amp;";
        }
        
        $deleteConfirmation = get_lang('Are you sure to delete the message?')
            . '<br /><br />'
            . '<a href="'.$linkDelete.'cmd=exDeleteMessage&amp;message_id='.$messageId.'">' . get_lang('Yes') . '</a>'
            .' | <a href="'.$linkDelete.'">' . get_lang('No') .'</a>'
            ;
            
        $dialogbox = new DialogBox();
        $dialogbox->question($deleteConfirmation);
        
        $content .= $dialogbox->render();
    }
    
    if ($cmd == "exDeleteMessage" && ! is_null($messageId))
    {
        $message = SentMessage::fromId($messageId);
        $message->delete();
    }
}

// ---------- paging
if (isset($_REQUEST['page']))
{
    $page = min(array($_REQUEST['page'],$box->getNumberOfPage()));
    $page = max(array($page,1));
    $strategy->setPageToDisplay($page);
    $arguments['page'] = $page;
    
}


// ------------- display

if ($arguments['search'] == 'fromUser')
{
    if (isset($arguments['name']))
    {
        $name = $arguments['name'];
    }
    else
    {
        $name = "";
    }
    
    $searchForm =
        '<form action="'.$_SERVER['PHP_SELF'].'?search=fromUser" method="post">'."\n"
       .'<input type="text" name="name" value="'.$name.'" class="inputSearch" />'."\n"
       .'<input type="submit" value="'.get_lang("Search").'" />'."\n"
       .'</form>'."\n\n"
       ;
    $dialogbox = new DialogBox();
    $dialogbox->form($searchForm);
    
    $content .= $dialogbox->render();
}

if ($arguments['search'] == 'olderThan')
{
    if (is_null($date))
    {
        $date = date('d/m/Y');
    }
    
    $CssLoader = CssLoader::getInstance();
    $CssLoader->load('ui.datepicker');
    
    $JsLoader->load('ui.datepicker');
    
    $javascript = '
        <script type="text/javascript" charset="utf-8">
            jQuery(function($){
                $("#dateinput").datepicker({dateFormat: \'dd/mm/yy\'});
            });
        </script>';
    $claroline->display->header->addHtmlHeader($javascript);
    $disp = "\n"
        . get_lang('Select date') . '<br />'."\n"
        . '<form action="'.$_SERVER['PHP_SELF'].'?search=olderThan" method="post">'."\n"
        . '<input type="text" name="date" value="'.$date.'" id="dateinput" />'.get_lang('(jj/mm/aaaa)').'<br />'."\n"
        . '<input type="submit" value="'.get_lang('Search').'" />'."\n"
        . '</form>'."\n\n"
        ;
    $dialogbox = new DialogBox();
    $dialogbox->form($disp);
    
    $content .= $dialogbox->render();
}

if ($arguments['search'] == 'timeInterval')
{
    if (isset($arguments['date1']) && isset($arguments['date2']))
    {
        $date1 = $arguments['date1'];
        $date2 = $arguments['date2'];
    }
    else
    {
        $date1 = date('d/m/Y');
        $date2 = date('d/m/Y');
    }
    
    $CssLoader = CssLoader::getInstance();
    $CssLoader->load('ui.datepicker');
    
    $JsLoader->load('ui.datepicker');
    
    $javascript = '
        <script type="text/javascript" charset="utf-8">
            $(document).ready( function(){
                $(".daterange").datepicker({dateFormat: \'dd/mm/yy\', beforeShow: customRange});
                
                function customRange(input) {
                    return {minDate: (input.id == \'dateinput2\' ? $(\'#dateinput1\').datepicker(\'getDate\') : null),
                    maxDate: (input.id == \'dateinput1\' ? $(\'#dateinput2\').datepicker(\'getDate\') : null)};
                }
            });
        </script>';
    $claroline->display->header->addHtmlHeader($javascript);
    $disp = "\n"
        . get_lang('Select interval') . '<br />'."\n"
        . '<form action="'.$_SERVER['PHP_SELF'].'?search=timeInterval" method="post">'."\n"
        . get_lang('From').' <input type="text" name="date1" value="'.$date1.'" class="daterange" id="dateinput1" /> '."\n"
        . get_lang('to').' <input type="text" name="date2" value="'.$date2.'" class="daterange" id="dateinput2" /> '.get_lang('(jj/mm/aaaa)').'<br />'."\n"
        . '<input type="submit" value="'.get_lang('Search').'" />'."\n"
        . '</form>'."\n\n"
        ;
    $dialogbox = new DialogBox();
    $dialogbox->form($disp);
    
    $content .= $dialogbox->render();
}


if ($displayTable)
{
    $argLink = makeArgLink($arguments,array('fieldOrder','order'));
    $orderLink = $_SERVER['PHP_SELF'].'?'.$argLink;
    
    if ($argLink != "")
    {
        $orderLink .= "&amp;";
    }
    $orderLink .= "order=".$nextOrder."&amp;";
    
    $javascriptDelete = '
    <script type="text/javascript">
  
        function deleteSelection ()
        {
           if ( $("input[@type=checkbox][@checked]").size() < 1 )
           {
               return false;
           }
        
           if (confirm("'.clean_str_for_javascript(get_lang('Are you sure to delete selected message(s) ?')).'"))
           {
               $("input[@name=cmd]").val("exDeleteSelection");
               return true;
           }
           else
           {
               return false;
           }
        }
    </script>';
    $claroline->display->header->addHtmlHeader($javascriptDelete);
    $argDeleteSelection = makeArgLink($arguments,array('cmd'));
    $content .= '<form action="'.$_SERVER['PHP_SELF'].'?'.$argDeleteSelection.'" method="post"
                    onsubmit="return deleteSelection(this)">'."\n"
            . '<input type="hidden" name="cmd" value="rqDeleteSelection" />'."\n\n"
            ;
    $content .= '<br />'
       .'<table class="claroTable emphaseLine" width="100%">'."\n\n"
       .'<thead>'
       .'<tr>'."\n"
       .'<th>&nbsp;</th>'."\n"
       .'<th>'.get_lang('Subject').'</th>'."\n"
       .'<th><a href="'.$orderLink.'fieldOrder=name">'.get_lang('Sender').'</a></th>'."\n"
       .'<th><a href="'.$orderLink.'fieldOrder=username">'.get_lang('Username').'</a></th>'."\n"
       .'<th><a href="'.$orderLink.'fieldOrder=date">'.get_lang('Date').'</a></th>'."\n"
       .'<th class="im_list_action">'.get_lang('Delete').'</th>'."\n"
       .'</tr>'."\n"
       .'</thead>'."\n";
    
    if ($box->getNumberOfMessage() == 0)
    {
        $content .= '<tfoot>' . "\n"
        .   '<tr>' . "\n"
        .   '<td colspan="6">' . get_lang('No result') . '</td>' . "\n"
        .   '</tr>' . "\n"
        .   '</tfoot>' . "\n"
        .   '</table>' . "\n"
           ;
    }
    else
    {
        $argDelete = makeArgLink($arguments);
        if ($argDelete == "")
        {
            $linkDelete = $_SERVER['PHP_SELF']."?";
        }
        else
        {
            $linkDelete = $_SERVER['PHP_SELF']."?".$argDelete."&amp;";
        }
        
        $javascriptDelete = '
            <script type="text/javascript">
            function deleteMessage ( localPath )
            {
                if (confirm("'.get_lang('Are you sure to delete the message?').'"))
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
        
        foreach ($box as $key => $message)
        {
            $userData = user_get_properties($message->getSender());
            
            $content .=
                '<tr>'."\n"
                .'<td class="im_list_selection"><input type="checkbox" name="msg[]" value="'.$message->getId().'" /></td>'."\n"
                .'<td><a href="readmessage.php?messageId='.$message->getId().'&amp;type=received">'.claro_htmlspecialchars($message->getSubject()).'</a></td>'."\n"
                .'<td><a href="sendmessage.php?cmd=rqMessageToUser&amp;userId='.$message->getSender().'">'
                        .get_lang('%firstName %lastName', array ('%firstName' =>claro_htmlspecialchars($message->getSenderFirstName()), '%lastName' => claro_htmlspecialchars($message->getSenderLastName())))
                .     '</a>'
                .'</td>'
                .'<td>'.claro_htmlspecialchars($userData['username']).'</td>'."\n"
                .'<td>'.claro_html_localised_date(get_locale('dateTimeFormatLong'),strtotime($message->getSendTime())).'</td>'."\n"
                .'<td class="im_list_action"><a href="'.$linkDelete.'cmd=rqDeleteMessage&amp;message_id='.$message->getId().'" '
                .        'onclick="return deleteMessage(\''.$linkDelete.'cmd=exDeleteMessage&amp;message_id='.$message->getId().'\')"'
                .    '><img src="' . get_icon_url('delete') . '" alt="" /></a></td>'."\n"
                .'</tr>'."\n\n"
                ;
       }
       $content .= '</table>'
       .    '<input type="submit" value="'.get_lang('Delete selected message(s)').'" />'."\n\n";
   }

   $content .= '</form>';
    // prepare the link to change of page
    if ($box->getNumberOfPage()>1)
    {
        // number of page to display in the page before and after thecurrent page
        $nbPageToDisplayBeforeAndAfterCurrentPage = 1;
        
        $content .= '<div id="im_paging">';
        
        $arg_paging = makeArgLink($arguments,array('page'));
        if ($arg_paging == "")
        {
            $linkPaging = $_SERVER['PHP_SELF']."?page=";
        }
        else
        {
            $linkPaging = $_SERVER['PHP_SELF']."?".$arg_paging."&amp;page=";
        }
        
        if (!isset($arguments['page']))
        {
            $page=1;
        }
        else
        {
            $page = $arguments['page'];
        }
        $content .= getPager($linkPaging,$page,$box->getNumberOfPage());
    }
}

// ------------------- render ----------------------------
$claroline->display->banner->breadcrumbs->append(get_lang('Administration'),get_path('rootAdminWeb'));
$claroline->display->banner->breadcrumbs->append(get_lang('Internal messaging'),'admin.php');
$claroline->display->banner->breadcrumbs->append(get_lang('Search'),'admin_search.php?search='.addslashes($arguments['search']));

$title['mainTitle'] = get_lang('Internal messaging') . ' - ' . get_lang('Search');
$title['subTitle'] = $subTitle;
$claroline->display->body->appendContent(claro_html_tool_title($title));
$claroline->display->body->appendContent($content);

echo $claroline->display->render();