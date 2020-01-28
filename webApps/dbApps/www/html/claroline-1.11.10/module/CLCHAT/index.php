<?php // $Id: index.php 1478 2011-09-06 13:11:22Z zefredz $
/**
 * CLAROLINE
 *
 * @version 0.1 $Revision: 1478 $
 *
 * @copyright (c) 2001-2007 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package CLCHAT
 *
 * @author S�bastien Piraux
 *
 */
    $tlabelReq = 'CLCHAT';
    
    require_once dirname( __FILE__ ) . '/../../claroline/inc/claro_init_global.inc.php';
    
    if( !claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form( true );

    /*
     * init request vars
     */
    $acceptedCmdList = array('rqRefresh', 'rqAdd', 'rqFlush', 'rqLogs', 'rqArchive');
    if ( isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'], $acceptedCmdList) )   $cmd = $_REQUEST['cmd'];
    else                                                                             $cmd = null;
    
    
    /*
     * init other vars
     */
    claro_set_display_mode_available(true);

    $is_allowedToEdit = claro_is_allowed_to_edit();


    if( !isset($_SESSION['chat_connectionTime']) )
    {
        // to avoid displaying message that were sent before arrival on the chat
        $_SESSION['chat_connectionTime'] = time(); // should not be reset 
    }
    
    if( !isset($_SESSION['chat_lastReceivedMsg']) )
    {
        // to add a visual effect when lines are added
        // (this var is reset each time new messages are received)
        $_SESSION['chat_lastReceivedMsg'] = time(); 
    }


    $cmdMenu = array();
    if( $is_allowedToEdit )
    {
    $cmdMenu[] = claro_html_cmd_link( '#'
                                        , get_lang('Show/hide logs')
                                        , array('id' => 'clchat_cmd_logs')
                                        );
        $cmdMenu[] = claro_html_cmd_link( '#'
                                        , get_lang('Store Chat')
                                        , array('id' => 'clchat_cmd_archive')
                                        );
        $cmdMenu[] = claro_html_cmd_link( '#'
                                        , get_lang('Reset')
                                        , array('id' => 'clchat_cmd_flush')
                                        );
    }
   
    /*
     * Output
     */
    $cssLoader = CssLoader::getInstance();
    $cssLoader->load( 'clchat', 'screen'); 
    

    //-- Content 
    $out = '';
    
    $nameTools = get_lang('Chat');

    $out .= claro_html_tool_title($nameTools);

    if( claro_is_javascript_enabled() && $_uid )
    {
        $jsloader = JavascriptLoader::getInstance();
        $jsloader->load('jquery');
        $jsloader->load('clchat');
    
        // init var with values from get_conf before including tool library
        $htmlHeaders = '<script type="text/javascript">' . "\n"
        .    'var refreshRate = "' . (get_conf('msg_list_refresh_rate',5)*1000) . '";' . "\n"
        .    'var userListRefresh = "'. (get_conf('user_list_refresh_rate')*1000).'";' . "\n"
        .    'var cidReq = "' . claro_get_current_course_id() . '";' . "\n";
        
        if( claro_is_in_a_group() )
        {
            $htmlHeaders .= 'var gidReq = "' . claro_get_current_group_id() . '";' . "\n";
        }
        
        $htmlHeaders .= 'var lang = new Array();' . "\n"
        .    'lang["confirmFlush"] = "' . clean_str_for_javascript(get_lang('Are you sure to delete all logs ?')) . '";'
        . '</script>';
        
        $claroline->display->header->addHtmlHeader($htmlHeaders);
        
        // dialog box
        $out .= '<div id="clchat_user_list"></div>'.    "\n"
        .    '<div id="clchat_chatarea">'. "\n"
        .    ' <div id="clchat_log"></div>' . "\n"
        .    ' <div id="clchat_connectTime">'
        .    get_lang('Start of this chat session (%connectTime)', array('%connectTime' => claro_html_localised_date(get_locale('dateTimeFormatLong'), $_SESSION['chat_connectionTime']))) 
        . '</div>' . "\n"
        .    ' <div id="clchat_text"></div>' . "\n"
        . '</div>' . "\n";
    
        // display form
        $out .= '<form action="#" id="clchat_form" method="get" >' . "\n"
        .    claro_form_relay_context() . "\n"
        .    '<img src="'.get_module_url('CLCHAT').'/img/loading.gif" alt="'.get_lang('Loading...').'" id="clchat_loading" width="16" height="16" />' . "\n"
        .    '<input id="clchat_msg" type="text" name="message" maxlength="200" size="80" />' . "\n"
        .    '<input type="submit" name="Submit" value=" &gt;&gt; " />' . "\n"
        .    '</form>' . "\n"
    
        .    claro_html_menu_horizontal($cmdMenu) . "\n"
        . '<p id="clchat_dialogBox"></p>' . "\n"
        ;

    }
    else
    {
        if( ! claro_is_javascript_enabled() )
        {
            $dialogBox = new DialogBox();
            $dialogBox->error( get_lang('Javascript must be enabled in order to use this tool.'));
            $out .= $dialogBox->render();
        }
        elseif( ! $_uid )
        {
            $dialogBox = new DialogBox();
            $dialogBox->error( get_lang('Anonymous users cannot use this tool.'));
            $out .= $dialogBox->render();            
        }
    }    

    $claroline->display->body->appendContent($out);

    echo $claroline->display->render();

?>