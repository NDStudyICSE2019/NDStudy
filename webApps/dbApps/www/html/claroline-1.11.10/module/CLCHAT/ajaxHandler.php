<?php // $Id: ajaxHandler.php 607 2009-01-19 11:14:03Z fragile_be $
/**
 * CLAROLINE
 *
 * @version 0.1 $Revision: 607 $
 *
 * @copyright (c) 2001-2007 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package CLPAGES
 *
 * @author Sebastien Piraux
 *
 */

$tlabelReq = 'CLCHAT';

$cidReset = true;
$gidReset = true;
require_once dirname( __FILE__ ) . '/../../claroline/inc/claro_init_global.inc.php';

/*
 * Tool libraries
 */
require_once dirname(__FILE__) . '/lib/chat.lib.php';
require_once dirname(__FILE__) . '/lib/chatUserList.class.php';
require_once dirname(__FILE__) . '/lib/chatMsgList.class.php';


/*
 * Context
 */
$is_allowedToEdit = claro_is_allowed_to_edit();



/*
 * Init request vars
 */
$acceptedCmdList = array('rqRefresh',
                        'rqAdd',
                        'rqFlush', 
                        'rqLogs', 
                        'rqArchive',
                        'rqRefreshUserList'
                        );
if ( isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'], $acceptedCmdList) )   $cmd = $_REQUEST['cmd'];
else                                                                             $cmd = null;

if( isset($_REQUEST['message']) )   $msg = $_REQUEST['message'];
else                                $msg = '';                                

if( claro_is_in_a_course() )
{
    $courseId = claro_get_current_course_id();
}
else
{
    $courseId = null;
}

if( claro_is_in_a_group() && claro_is_group_allowed() )
{
    $groupId = claro_get_current_group_id();
}
else
{
    $groupId = null;
}

/*
 * Force headers
 */
header("Content-Type: text/xml; charset=utf-8");
header("Cache-Control: no-cache, must-revalidate" );
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" );
header("Pragma: no-cache" );


/*
 * Other commands
 */
if( $cmd == 'rqAdd' )
{
    if( !empty($msg) && claro_is_user_authenticated() )
    {
        $msgList = new ChatMsgList($courseId,$groupId);
        $msgList->addMsg(claro_utf8_decode($msg), claro_get_current_user_id());
    }

    // always request refresh to have a response for ajax call
    $cmd = 'rqRefresh';    
}

if( $cmd == 'rqRefresh' )
{
    $msgList = new ChatMsgList($courseId,$groupId);
    $msgList->load($_SESSION['chat_connectionTime']);
    
    echo claro_utf8_encode($msgList->render());

    return;
}

if( $cmd == 'rqRefreshUserList' )
{
    $chatUserList = new ChatUserList($courseId,$groupId);
    // keep my user alive in user list
    $chatUserList->ping(claro_get_current_user_id());
    // delete user that have not ping recently
    $chatUserList->prune();
    // load the refreshed list
    $chatUserList->load();
    
    echo claro_utf8_encode($chatUserList->render());
    
    return;
}

/*
 * Admin only commands
 */

if( $cmd == 'rqFlush' && $is_allowedToEdit )
{
    $msgList = new ChatMsgList($courseId,$groupId);
    if( $msgList->flush() )
    {
        $dialogBox = new DialogBox();
        $dialogBox->success(get_lang('Chat reset'));
        
        echo claro_utf8_encode($dialogBox->render());
    }
    
    return;    
}

if( $cmd == 'rqLogs' && $is_allowedToEdit )
{
    $msgList = new ChatMsgList($courseId,$groupId);
    $msgList->load(1, $_SESSION['chat_connectionTime'] );

    echo claro_utf8_encode($msgList->render());
    
    return;    
}

if( $cmd == 'rqArchive' && $is_allowedToEdit )
{
    $msgList = new ChatMsgList($courseId,$groupId);
    $msgList->load();
    
    if( $chatFilename = $msgList->archive() )
    {
        $downloadLink = '<a href="'.get_module_url('CLDOC').'/document.php'.claro_url_relay_context('?').'">' . basename($chatFilename) . '</a>';
        
        $dialogBox = new DialogBox();
        $dialogBox->success(get_lang('%chat_filename is now in the document tool. (<em>This file is visible</em>)',array('%chat_filename' => $downloadLink)));

        echo claro_utf8_encode($dialogBox->render());
        return;
    }
    else
    {
        $dialogBox = new DialogBox();
        $dialogBox->error(get_lang('Store failed'));

        echo claro_utf8_encode($dialogBox->render());
        return;
    }
}
?>