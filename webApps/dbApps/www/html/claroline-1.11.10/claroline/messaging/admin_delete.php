<?php // $Id: admin_delete.php 14314 2012-11-07 09:09:19Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Allow the administrator to delete messages.
 *
 * @version     $Revision: 14314 $
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
require_once dirname(__FILE__) . '/lib/userlist.lib.php';

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

$displayRemoveAllConfirmation = FALSE;
$displayRemoveAllValidated = FALSE;

$displayRemoveFromUserConfirmation = FALSE;
$displayRemoveFromUserValidated = FALSE;
$displaySearchUser = FALSE;
$displayResultUserSearch = FALSE;

$displayRemoveOlderThanConfirmation = FALSE;
$displayRemoveOlderThanValidated = FALSE;

$displayRemovePlatformMessageConfirmation = FALSE;
$displayRemovePlatformMessageValidated = FALSE;

$userId = isset($_REQUEST['userId'])? (int)$_REQUEST['userId'] : NULL;

//used for user search
$arguments = array();

$acceptedCommand = array('rqDeleteAll','exDeleteAll'
                        ,'rqFromUser','exFromUser'
                        ,'rqOlderThan','exOlderThan'
                        ,'rqPlatformMessage','exPlatformMessage');

// ------------- display
if (isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'], $acceptedCommand))
{
    // -------- delete all
    if ($_REQUEST['cmd'] == "rqDeleteAll")
    {
        $subTitle = get_lang('Delete all messages');
        $displayRemoveAllConfirmation = TRUE;
    }
    
    if ($_REQUEST['cmd'] == "exDeleteAll")
    {
        $subTitle = get_lang('Delete all messages');
        $box = new AdminMessageBox();
        $box->deleteAllMessages();
        $displayRemoveAllValidated = TRUE;
    }
    
    // -----------delete from user
    if ($_REQUEST['cmd'] == 'rqFromUser')
    {
        $subTitle = get_lang('Delete all user\'s messages');
        $arguments['cmd'] = 'rqFromUser';
        if ( ! is_null($userId) )
        {
            $displayRemoveFromUserConfirmation = TRUE;
        }
        else
        {
            $displaySearchUser = TRUE;
        }
        // generate the user list
        if (isset($_REQUEST['search']) && $_REQUEST['search'] != "")
        {
            $displayResultUserSearch = TRUE;
            $arguments['search'] = strip_tags($_REQUEST['search']);
                        
            $userList = new UserList();
            $selector = $userList->getSelector();
            
            //order
            if (isset($_REQUEST['order']))
            {
                $order = $_REQUEST['order'] == 'asc' ? 'asc' : 'desc';
                
                $arguments['order'] = $order;
                
                if ($arguments['order'] == 'asc')
                {
                    $selector->setOrder(UserStrategy::ORDER_ASC);
                    $nextOrder = 'desc';
                }
                else
                {
                    $selector->setOrder(UserStrategy::ORDER_DESC);
                    $nextOrder = 'asc';
                }
            }
            else
            {
                $nextOrder = 'desc';
            }
            //orderfield
            if (isset($_REQUEST['fieldOrder']))
            {
                $fieldOrder = $_REQUEST['fieldOrder'] == 'name' ? 'name' : 'username';
                
                $arguments['fieldOrder'] = $fieldOrder;
                
                if ($arguments['fieldOrder'] == 'name')
                {
                    $selector->setFieldOrder(UserStrategy::ORDER_BY_NAME);
                }
                else
                {
                    $selector->setFieldOrder(UserStrategy::ORDER_BY_USERNAME);
                }
            }
            //namesearch
            $selector->setSearch($arguments['search']);
            //paging
            if (isset($_REQUEST['page']))
            {
                $page = max(array(1,$_REQUEST['page']));
                $page = min(array($page,$userList->getNumberOfPage()));
                
                $arguments['page'] = $page;
                
                $selector->setPageToDisplay($page);
            }
            $userList->setSelector($selector);
        }
    }
    
    if ( 'exFromUser' == $_REQUEST['cmd'] && ! is_null($userId))
    {
        $subTitle = get_lang('Delete all user\'s messages');
        $box = new AdminMessageBox();
        $box->deleteAllMessageFromUser($userId);
        $displayRemoveFromUserValidated = TRUE;
    }
    // delete older than
    if ( 'rqOlderThan' == $_REQUEST['cmd'] )
    {
        $subTitle = get_lang('Delete messages older than');
        $displayRemoveOlderThanConfirmation = TRUE;
    }
    
    if ( 'exOlderThan' == $_REQUEST['cmd'] && isset($_REQUEST['date']))
    {
        $subTitle = get_lang('Delete messages older than');
        $box = new AdminMessageBox();
        
        list($day,$month,$year) = explode('/',$_REQUEST['date']);
        
        if (checkdate($month,$day,$year))
        {
            $box->deleteMessageOlderThan(strtotime($year.'-'.$month.'-'.$day));
            $displayRemoveOlderThanValidated = TRUE;
        }
        else
        {
            $dialogBox = new DialogBox();
            $dialogBox->info(get_lang('Invalid date'));
            $content .= $dialogBox->render();
        }
        
    }
    
    // -------- delete platform message
    if ( 'rqPlatformMessage' == $_REQUEST['cmd'] )
    {
        $subTitle = get_lang('Delete platform messages');
        $displayRemovePlatformMessageConfirmation = TRUE;
    }
    elseif ( 'exPlatformMessage' == $_REQUEST['cmd'] )
    {
        $subTitle = get_lang('Delete platform messages');
        $box = new AdminMessageBox();
        $box->deletePlatformMessage();
        $displayRemovePlatformMessageValidated = TRUE;
    }
}
else
{
    claro_die(get_lang('Missing command'));
}

// ----------- delete all --------------
if ($displayRemoveAllConfirmation)
{
    $dialogBox = new DialogBox();
    $dialogBox->question( get_lang('Are you sure to delete all messages?') );
    $dialogBox->warning( get_lang('There is no way to restore deleted messages.') );

    $dialogBox->info( '<br /><br />'
         . '<a href="'.$_SERVER['PHP_SELF'].'?cmd=exDeleteAll">' . get_lang('Yes') . '</a> | <a href="admin.php">' . get_lang('No') .'</a>'
         );

    $dialogBox->setBoxType('question');
    $content .= '<br />'.$dialogBox->render();
}

if ($displayRemoveAllValidated)
{
    $dialogBoxMsg = get_lang('All messages have been deleted')
         . '<br /><br />'
         . '<a href="admin.php">' . get_lang('Back') .'</a>'
         ;
    $dialogBox = new DialogBox();
    $dialogBox->success($dialogBoxMsg);
    $content .= '<br />'.$dialogBox->render();
}

// ----------- end delete all

// --------- from user

if ($displayRemoveFromUserConfirmation)
{
    $userId = (int)$_REQUEST['userId'];
    
    $confirmation =
         get_lang('Are you sur to delete user\'s message?')
        .'<br /><br />'
        .'<a href="'.$_SERVER['PHP_SELF'].'?cmd=exFromUser&amp;userId='.$userId.'">'.get_lang('Yes').'</a> | <a href="admin.php">'.get_lang('No').'</a>'
        ;
    $dialogBox = new DialogBox();
    $dialogBox->question($confirmation);
    $content .= $dialogBox->render();
}

if ($displayRemoveFromUserValidated)
{
    $dialogBoxMsg = get_lang('All user\'s message have been deleted')
         . '<br /><br />'
         . '<a href="admin.php">' . get_lang('Back') .'</a>'
         ;
    $dialogBox = new DialogBox();
    $dialogBox->success($dialogBoxMsg);
    $content .= '<br />'.$dialogBox->render();
}

if ($displaySearchUser)
{
    if (isset($arguments['search']))
    {
        $search = $arguments['search'];
    }
    else
    {
        $search = "";
    }
    $form =
         '<form action="" method="post">'
        .get_lang('User').': <input type="text" name="search" value="'.$search.'" class="inputSearch" />'
        .'<input type="submit" value="'.get_lang('Search').'" />'
        .'</form>'
        ;
        
    $dialogBox = new DialogBox();
    $dialogBox->form($form);
    
    $content .= $dialogBox->render();
    
}

if ($displayResultUserSearch)
{
    
    $arg_sorting = makeArgLink($arguments,array('fieldOrder','order'));
    if ($arg_sorting == "")
    {
        $linkSorting = $_SERVER['PHP_SELF']."?fieldOrder=";
    }
    else
    {
        $linkSorting = $_SERVER['PHP_SELF'] . '?' . $arg_sorting . '&amp;fieldOrder=';
    }
    $arg_delete = makeArgLink($arguments);
    if ($arg_sorting == "")
    {
        $linkDelete = $_SERVER['PHP_SELF'] . '?';
    }
    else
    {
        $linkDelete = $_SERVER['PHP_SELF'] . '?' . $arg_delete . '&amp;';
    }
    
    $content .= '<br />'
       .'<table class="claroTable emphaseLine">' . "\n\n"
       .'<thead>'
       .'<tr>' . "\n"
       .'<th>' . get_lang('Id') . '</th>' . "\n"
       .'<th><a href="' . $linkSorting . 'name&amp;order='.$nextOrder.'">' . get_lang('Name') . '</a></th>'."\n"
       .'<th><a href="' . $linkSorting . 'username&amp;order='.$nextOrder.'">' . get_lang('Username') . '</a></th>'."\n"
       .'<th>' . get_lang('Delete messages') . '</th>'."\n"
       .'</tr>' . "\n"
       .'</thead>'."\n"
       ;

     if ( $userList->getNumberOfUser() > 0)
     {
         $javascriptDelete = '
            <script type="text/javascript">
            function deleteMessageFromUser ( localPath )
            {
                if (confirm("'.get_lang('Are you sure to delete all messages from this user').'"))
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
 
        foreach ($userList as $user)
        {
            $content .=
                  '<tr>' . "\n"
                . '<td>' . $user['id'] . '</td>' . "\n"
                . '<td>' . get_lang('%firstName %lastName', array ('%firstName' =>claro_htmlspecialchars($user['firstname']), '%lastName' => claro_htmlspecialchars($user['lastname']))).'</td>'."\n"
                . '<td>' . $user['username'] . '</td>' . "\n"
                . '<td align="center">'
                . '<a href="' . $linkDelete . 'cmd=rqFromUser&amp;userId=' . $user['id'] . '" '
                . ' onclick="return deleteMessageFromUser(\'' . $linkDelete . 'cmd=exFromUser&amp;userId=' . $user['id'] . '\')">'
                . '<img src="'.get_icon_url('delete').'" alt="'.get_lang('Delete messages').'" /></a></td>' . "\n"
                . '</tr>' . "\n\n"
                ;
        }
     }
     else
     {
         $content .=
              '<tr>'."\n"
             .'<td colspan="4">'.get_lang('Empty').'</td>' ."\n"
             .'</tr>'."\n\n"
             ;
     }
     $content .=
        '</table>'
       ;
     if ($userList->getNumberOfPage() > 1)
     {
         $arg_paging = makeArgLink($arguments,array('page'));
         if ($arg_paging == "")
         {
             $linkPaging = $_SERVER['PHP_SELF']."?page=";
         }
         else
         {
             $linkPaging = $_SERVER['PHP_SELF']."?".$arg_paging."&amp;page=";
         }
         
         $content .= getPager($linkPaging,$arguments['page'],$userList->getNumberOfPage());
     }
      
}
//----------- end from user

//--------------- older than
if ($displayRemoveOlderThanConfirmation)
{
    
    $date = isset($_REQUEST['date']) ? $_REQUEST['date'] : NULL;
    
    
    if (is_null($date))
    {
        $CssLoader = CssLoader::getInstance();
        $CssLoader->load('ui.datepicker');
        
        $JsLoader = JavascriptLoader::getInstance();
        $JsLoader->load('jquery');
        $JsLoader->load('ui.datepicker');
        
        $javascript = '
            <script type="text/javascript" charset="utf-8">
                jQuery(function($){
                    $("#dateinput").datepicker({dateFormat: \'dd/mm/yy\'});
                });
            </script>';
        $claroline->display->header->addHtmlHeader($javascript);
            
        $disp = get_lang('Choose a date')
                .' :<br />'
                . '<form action="'.$_SERVER['PHP_SELF'].'?cmd=rqOlderThan" method="post">'
                . '<input type="text" name="date" value="'.date('d/m/Y').'" id="dateinput" /> '.get_lang('(jj/mm/aaaa)').'<br />'
                . '<input type="submit" value="'.get_lang('Delete').'" />'
                . '</form>'
                ;
        $dialogBox = new DialogBox();
        $dialogBox->form($disp);
        
        $content .= $dialogBox->render();
    }
    else
    {
        $javascriptDelete = '
            <script type="text/javascript">
            if (confirm("'.get_lang('Are you sure to delete all messages older than %date?', array('%date'=>$date)). "\n\n" . get_lang('There is no way to restore deleted messages.') .'"));
            {
                window.location=\''.$_SERVER['PHP_SELF'].'?cmd=exOlderThan&amp;date='.urlencode($date).'\';
            }
            else
            {
                window.location=\'admin.php\';
            }
            </script>';
        $claroline->display->header->addHtmlHeader($javascriptDelete);
        
        $dialogBox = new DialogBox();
        $dialogBox->setBoxType('question');
        $dialogBox->question(get_lang('Are you sure to delete all messages older than %date?', array('%date'=>$date)) );
        $dialogBox->warning(get_lang('There is no way to restore deleted messages.'));
        $dialogBox->info('<br /><br /><a href="'.$_SERVER['PHP_SELF'].'?cmd=exOlderThan&amp;date='.urlencode($_REQUEST['date']).'">' . get_lang('Yes') . '</a> | <a href="admin.php">' . get_lang('No') .'</a>');
        $content .= '<br />'.$dialogBox->render();
    }
}

if ($displayRemoveOlderThanValidated)
{
    $date = claro_htmlspecialchars($_REQUEST['date']);
    $dialogBox = new DialogBox();
    $dialogBoxMsg = get_lang('All messages older than %date% have been deleted',array('%date%' => $date))
         . '<br /><br />'
         . '<a href="admin.php">' . get_lang('Back') .'</a>'
         ;
    $dialogBox->success($dialogBoxMsg);
    $content .= '<br />'.$dialogBox->render();
}
// --------------- end older than

// ------------ platform message

if ($displayRemovePlatformMessageConfirmation)
{
    
    $dialogBox = new DialogBox();
    $dialogBox->setBoxType('question');
    $dialogBox->question(get_lang('Are you sure to delete all platform messages?') );
    $dialogBox->warning(get_lang('There is no way to restore deleted messages.'));
    $dialogBox->info('<br /><br /><a href="'.$_SERVER['PHP_SELF'] . '?cmd=exPlatformMessage">' . get_lang('Yes') . '</a> | <a href="admin.php">' . get_lang('No') .'</a>');
    $content .= '<br />'.$dialogBox->render();
}

if ($displayRemovePlatformMessageValidated)
{
    $dialogBoxMsg = get_lang('All platform messages have been deleted')
         . '<br /><br />'
         . '<a href="admin.php">' . get_lang('Back') .'</a>'
         ;
    $dialogBox = new DialogBox();
    $dialogBox->info($dialogBoxMsg);
    $content .= '<br />'.$dialogBox->render();
}

// ------------- end platform message

// ------------------- render ----------------------------
$claroline->display->banner->breadcrumbs->append(get_lang('Administration'),get_path('rootAdminWeb'));
$claroline->display->banner->breadcrumbs->append(get_lang('Internal messaging'),'admin.php');
$claroline->display->banner->breadcrumbs->append(get_lang('Delete'),'admin_delete.php?cmd='.addslashes($_REQUEST['cmd']));

$title['mainTitle'] = get_lang('Internal messaging') . ' - ' . get_lang('Delete');
$title['subTitle'] = $subTitle;
$claroline->display->body->appendContent(claro_html_tool_title($title));

$claroline->display->body->appendContent($content);

echo $claroline->display->render();