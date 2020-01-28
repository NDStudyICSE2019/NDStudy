<?php // $Id: wiki.php 14314 2012-11-07 09:09:19Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * @version     $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *              This program is under the terms of the GENERAL PUBLIC LICENSE (GPL)
 *              as published by the FREE SOFTWARE FOUNDATION. The GPL is available
 *              through the world-wide-web at http://www.gnu.org/copyleft/gpl.html
 * @author      Frederic Minne <zefredz@gmail.com>
 * @package     Wiki
 */

$tlabelReq = 'CLWIKI';
require_once "../inc/claro_init_global.inc.php";

if ( ! claro_is_tool_allowed() )
{
    if ( ! claro_is_in_a_course() )
    {
        claro_disp_auth_form( true );
    }
    else
    {
        claro_die(get_lang("Not allowed"));
    }
}


// display mode

claro_set_display_mode_available(TRUE);

// check and set user access level for the tool

// set admin mode and groupId

$is_allowedToAdmin = claro_is_allowed_to_edit();


if ( claro_is_in_a_group() && claro_is_group_allowed() )
{
    // group context
    $groupId = (int) claro_get_current_group_id();
}
elseif ( claro_is_in_a_group() && ! claro_is_group_allowed() )
{
    claro_die(get_lang("Not allowed"));
}
elseif ( claro_is_course_allowed() )
{
    // course context
    $groupId = 0;
}
else
{
    claro_disp_auth_form();
}

// require wiki files

require_once "lib/class.wiki.php";
require_once "lib/class.wikistore.php";
require_once "lib/class.wikipage.php";
require_once "lib/lib.requestfilter.php";
require_once "lib/lib.wikisql.php";
require_once "lib/lib.javascript.php";
require_once "lib/lib.wikidisplay.php";


$dialogBox = new DialogBox();

// filter request variables

// filter allowed actions using user status
if ( $is_allowedToAdmin )
{
    $valid_actions = array( 'list', 'rqEdit', 'exEdit', 'rqDelete', 'exDelete', 'rqSearch', 'exSearch', 'exExport' );
}
elseif ( claro_is_group_member() && $groupId )
{
    $valid_actions = array( 'list', 'rqEdit', 'exEdit', 'rqDelete', 'exDelete', 'rqSearch', 'exSearch' );
}
else
{
    $valid_actions = array( 'list', 'rqSearch', 'exSearch' );
}

$_CLEAN = filter_by_key( 'action', $valid_actions, 'R', false );

$action = ( isset( $_CLEAN['action'] ) ) ? $_CLEAN['action'] : 'list';

$wikiId = ( isset( $_REQUEST['wikiId'] ) ) ? (int) $_REQUEST['wikiId'] : 0;

$creatorId = claro_get_current_user_id();

// get request variable for wiki edition
if ( $action == 'exEdit' )
{
    $wikiTitle = ( isset( $_POST['title'] ) ) ? strip_tags( $_POST['title'] ) : '';
    $wikiDesc = ( isset( $_POST['desc'] ) ) ? strip_tags( $_POST['desc'] ) : '';

    if ( $wikiDesc == get_lang("Enter the description of your wiki here") )
    {
        $wikiDesc = '';
    }

    $acl = ( isset( $_POST['acl'] ) ) ? $_POST['acl'] : null;

    // initialise access control list

    $wikiACL = WikiAccessControl::emptyWikiACL();

    if ( is_array( $acl ) )
    {
        foreach ( $acl as $key => $value )
        {
            if ( $value == 'on' )
            {
                $wikiACL[$key] = true;
            }
        }
    }

    // force Wiki ACL coherence

    if ( $wikiACL['course_read'] == false && $wikiACL['course_edit'] == true )
    {
        $wikiACL['course_edit'] = false;
    }
    if ( $wikiACL['group_read'] == false && $wikiACL['group_edit'] == true )
    {
        $wikiACL['group_edit'] = false;
    }
    if ( $wikiACL['other_read'] == false && $wikiACL['other_edit'] == true )
    {
        $wikiACL['other_edit'] = false;
    }

    if ( $wikiACL['course_edit'] == false  && $wikiACL['course_create'] == true )
    {
        $wikiACL['course_create'] = false;
    }
    if ( $wikiACL['group_edit'] == false  && $wikiACL['group_create'] == true )
    {
        $wikiACL['group_create'] = false;
    }
    if ( $wikiACL['other_edit'] == false  && $wikiACL['other_create'] == true )
    {
        $wikiACL['other_create'] = false;
    }
}

// Database nitialisation

$tblList = claro_sql_get_course_tbl();

$config = array();
$config['tbl_wiki_properties'   ] = $tblList['wiki_properties'   ];
$config['tbl_wiki_pages'        ] = $tblList['wiki_pages'        ];
$config['tbl_wiki_pages_content'] = $tblList['wiki_pages_content'];
$config['tbl_wiki_acls'         ] = $tblList['wiki_acls'         ];

$con = Claroline::getDatabase();

// DEVEL_MODE database initialisation
if( defined( 'DEVEL_MODE' ) && ( DEVEL_MODE == true ) )
{
    init_wiki_tables( $con, false );
}

// Objects instantiation

$wikiStore = new WikiStore( $con, $config );
$wikiList = array();

// --------- Start of command processing ----------------

switch ( $action )
{
    case 'exExport':
    {
        require_once "lib/class.wiki2xhtmlexport.php";

        if ( ! $wikiStore->wikiIdExists( $wikiId ) )
        {
            // die( get_lang("Invalid Wiki Id") );
            $message = get_lang("Invalid Wiki Id");
            $dialogBox->error( $message );
            $action = 'error';
        }
        else
        {
            $wiki = $wikiStore->loadWiki( $wikiId );
            $wikiTitle = $wiki->getTitle();
            $renderer = new WikiToSingleHTMLExporter( $wiki );

            $contents = $renderer->export();

            if ( 0 != $groupId )
            {
                $groupPart = '_group' . (int) $groupId;
            }
            else
            {
                $groupPart = '';
            }

            require_once get_conf( 'includePath' ) . '/lib/fileUpload.lib.php';
            // TODO : use function wich return get_conf('coursesRepositorySys') . '/' . $_course['path']
            $exportDir = get_conf('coursesRepositorySys') . '/' . claro_get_course_path() . '/document';
            $exportFile = replace_dangerous_char( $wikiTitle, 'strict' ) . $groupPart;

            $i = 1;
            while ( file_exists($exportDir . '/' .$exportFile.'_'.$i.'.html') ) $i++;

            $wikiFileName = $exportFile . '_' . $i . '.html';
            $exportPath = $exportDir . '/' . $wikiFileName;

            file_put_contents( $exportPath, $contents );
        }

        break;
    }
    case 'exSearch':
    {
        require_once "lib/class.wikisearchengine.php";

        $pattern = isset( $_REQUEST['searchPattern'] )
            ? trim($_REQUEST['searchPattern'])
            : null
            ;

        if ( !empty( $pattern ) )
        {
            $searchEngine = new WikiSearchEngine( $con, $config );
            $searchResult = $searchEngine->searchAllWiki( $pattern, $groupId, CLWIKI_SEARCH_ANY );

            if ( $searchEngine->hasError() )
            {
                $message = $searchEngine->getError();
                $dialogBox->error( $message );
                $action = 'error';
                break;
            }

            if ( is_null( $searchResult ) )
            {
                $searchResult = array();
            }

            $wikiList = $searchResult;

            break;
        }
        else
        {
            $message = '<p>'.get_lang("Missing search keywords").'</p>';
            $dialogBox->error( $message );
        }
    }
    // search wiki
    case 'rqSearch':
    {
        //if ( !isset( $message ) ) $message = '';

        $message = '<form method="post" action="'.claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'])).'">'."\n"
            . '<input type="hidden" name="action" value="exSearch" />'."\n"
            . claro_form_relay_context() . "\n"
            . '<label for="searchPattern">'
            . get_lang("Search")
            . '</label><br />'."\n"
            . '<input type="text" id="searchPattern" name="searchPattern" />'."\n"
            . '<input type="submit" value="'.get_lang("Ok").'" />'."\n"
            . claro_html_button(Url::Contextualize($_SERVER['PHP_SELF']), get_lang("Cancel"))
            . '</form>'."\n"
            ;
        
        $dialogBox->form( $message );
        
        $action = 'list';
        break;
    }
    // request delete
    case 'rqDelete':
    {
        if ( ! $wikiStore->wikiIdExists( $wikiId ) )
        {
            // die( get_lang("Invalid Wiki Id") );
            $message = get_lang("Invalid Wiki Id");
            $dialogBox->error( $message );
            $action = 'error';
        }
        else
        {
            $wiki = $wikiStore->loadWiki( $wikiId );
            $wikiTitle = $wiki->getTitle();
            $message = get_lang("WARNING : you are going to delete this wiki and all its pages. Are you sure to want to continue ?");
            $dialogBox->question( $message );
        }

        break;
    }
    // execute delete
    case 'exDelete':
    {
        if ( $wikiStore->wikiIdExists( $wikiId ) )
        {
            $wiki = $wikiStore->deleteWiki( $wikiId );
            
            if( $wiki )
            {
                $message = get_lang("Wiki deletion succeed");

                //notify that the wiki was deleted
    
                $eventNotifier->notifyCourseEvent('wiki_deleted'
                                             , claro_get_current_course_id()
                                             , claro_get_current_tool_id()
                                             , $wikiId
                                             , $groupId
                                             , '0');

                $dialogBox->success( $message );
            }
            else
            {
                $message = get_lang("Wiki deletion failed");
                $dialogBox->error( $message );
            }
        }
        else
        {
            $message = get_lang("Invalid Wiki Id");
            $dialogBox->error( $message );
            $action = 'error';
        }
        
        $action = 'list';

        break;
    }
    // request edit
    case 'rqEdit':
    {
        if ( $wikiId == 0 )
        {
            $wikiTitle = get_lang("New Wiki");
            $wikiDesc = '';
            $wikiACL = null;
        }
        elseif ( $wikiStore->wikiIdExists( $wikiId ) )
        {
            $wiki = $wikiStore->loadWiki( $wikiId );
            $wikiTitle = $wiki->getTitle();
            $wikiDesc = $wiki->getDescription();
            $wikiACL = $wiki->getACL();
            $groupId = $wiki->getGroupId();

        }
        else
        {
            $message = get_lang("Invalid Wiki Id");
            $action = 'error';
        }
        break;
    }
    // execute edit
    case 'exEdit':
    {
        if ( $wikiId == 0 )
        {
            $wiki = new Wiki( $con, $config );
            $wiki->setTitle( $wikiTitle );
            $wiki->setDescription( $wikiDesc );
            $wiki->setACL( $wikiACL );
            $wiki->setGroupId( $groupId );
            $wikiId = $wiki->save();

            //notify wiki modification

            $eventNotifier->notifyCourseEvent('wiki_added'
                                     , claro_get_current_course_id()
                                     , claro_get_current_tool_id()
                                     , $wikiId
                                     , claro_get_current_group_id()
                                     , '0');

            $mainPageContent = sprintf(
                get_lang("This is the main page of the Wiki %s. Click on '''Edit''' to modify the content.")
                , $wikiTitle )
                ;

            $wikiPage = new WikiPage( $con, $config, $wikiId );
            
            if ( $wikiPage->create( $creatorId, '__MainPage__'
                , $mainPageContent, date("Y-m-d H:i:s"), true ) )
            {
                $message = get_lang("Wiki creation succeed");
                $dialogBox->success( $message );
            }
            else
            {
                $message = get_lang("Wiki creation failed");
                $dialogBox->error( $message . ":" . $wikiPage->getError() );
            }

            
        }
        elseif ( $wikiStore->wikiIdExists( $wikiId ) )
        {
            $wiki = $wikiStore->loadWiki( $wikiId );
            $wiki->setTitle( $wikiTitle );
            $wiki->setDescription( $wikiDesc );
            $wiki->setACL( $wikiACL );
            $wiki->setGroupId( $groupId );
            $wikiId = $wiki->save();

            //notify wiki creation

            $eventNotifier->notifyCourseEvent('wiki_modified'
                                     , claro_get_current_course_id()
                                     , claro_get_current_tool_id()
                                     , $wikiId
                                     , claro_get_current_group_id()
                                     , '0');
            if( $wikiId )
            {
                $message = get_lang("Wiki edition succeed");
                $dialogBox->success( $message );
            }
            else
            {
                $message = get_lang("Wiki edition failed");
                $dialogBox->error( $message );
            }
            
        }
        else
        {
            $message = get_lang("Invalid Wiki Id");
            $dialogBox->error( $message );
            $action = 'error';
        }

        $action = 'list';

        break;
    }
}

// list wiki
if ( 'list' == $action )
{
    if ( $groupId == 0 )
    {
        $wikiList = $wikiStore->getCourseWikiList();
    }
    else
    {
        $wikiList = $wikiStore->getWikiListByGroup( $groupId );
    }
}

// ------------ End of command processing ---------------

// javascript

if ( $action == 'rqEdit' )
{
    $jspath = document_web_path() . '/js';
    $htmlHeadXtra[] = '<script type="text/javascript" src="'.$jspath.'/wiki_acl.js"></script>';
    $claroBodyOnload[] = 'initBoxes();';
}

// Breadcrumps
$nameTools = get_lang( 'Wiki' );

switch( $action )
{
    case 'rqEdit':
    {
        ClaroBreadCrumbs::getInstance()->append(
            $wikiTitle );
        ClaroBreadCrumbs::getInstance()->append(
            claro_htmlspecialchars('Properties') );
        break;
    }
    case 'rqDelete':
    {
        ClaroBreadCrumbs::getInstance()->append(
            $wikiTitle );
        ClaroBreadCrumbs::getInstance()->append(
            claro_htmlspecialchars('Delete') );
        break;
    }
    case 'list':
    default:
    {
        $noQUERY_STRING = true;
    }
}

$out = '';

// --------- Start of display ----------------

// Tool title
$toolTitle = array();

if ( claro_is_in_a_group() )
{
    $toolTitle['supraTitle'] = claro_get_current_group_data('name');
}

switch( $action )
{
    // edit form
    case 'rqEdit':
    {
        if ( $wikiId == 0 )
        {
            $toolTitle['mainTitle'] = get_lang("Wiki : Create new Wiki");
        }
        else
        {
            $toolTitle['mainTitle'] = get_lang("Wiki : Edit properties");
            $toolTitle['subTitle'] = $wikiTitle;
        }

        break;
    }
    // delete form
    case 'rqDelete':
    {
        $toolTitle['mainTitle'] = get_lang("Delete Wiki");

        break;
    }
    // list wiki
    case 'list':
    {
        $toolTitle['mainTitle'] = sprintf( get_lang("Wiki : %s"), get_lang("List of Wiki") );

        break;
    }
}

// Help URL
$helpUrl = claro_htmlspecialchars(get_help_page_url('blockWikiHelpAdminContent','CLWIKI'));

// Command list
$cmdList = array();

switch( $action )
{
    // an error occurs
    case 'error':
    {
        break;
    }
    case 'exExport':
    {
        $out .= '<blockquote>'
            . get_lang( "Wiki %TITLE% exported to course documents. (this file is visible)"
                , array( '%TITLE%' => $wikiTitle ) )
            . '</blockquote>';
            
            $cmdList[] = array(
                'name' => get_lang("Go to documents tool"),
                'url' => claro_htmlspecialchars(Url::Contextualize(get_module_url('CLDOC')
                    . '/document.php?gidReset=1'))
            );
            
            $cmdList[] = array(
                'name' => get_lang("Go back to Wiki list"),
                'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']))
            );

        break;
    }
    // edit form
    case 'rqEdit':
    {
        $out .= claro_disp_wiki_properties_form( $wikiId, $wikiTitle
            , $wikiDesc, $groupId, $wikiACL );

        break;
    }
    // delete form
    case 'rqDelete':
    {
        $out .= '<form method="post" action="'
            . claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']))
            . '" id="rqDelete">' . "\n"
            . '<div style="padding: 5px">' . "\n"
            . '<input type="hidden" name="wikiId" value="' . $wikiId . '" />' . "\n"
            . claro_form_relay_context() ."\n"
            . '<input type="submit" name="action[exDelete]" value="' . get_lang("Continue") . '" />' . "\n"
            . claro_html_button(claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'])), get_lang("Cancel") )
            . '</div>' . "\n"
            . '</form>' . "\n"
            ;

        break;
    }
    // list wiki
    case 'exSearch':
    case 'list':
    {
        //find the wiki with recent modification from the notification system

        if (claro_is_user_authenticated())
        {
            $date = $claro_notifier->get_notification_date(claro_get_current_user_id());
            $modified_wikis = $claro_notifier->get_notified_ressources(
                claro_get_current_course_id(),
                $date,
                claro_get_current_user_id(),
                claro_get_current_group_id(),
                claro_get_current_tool_id());
        }
        else
        {
            $modified_wikis = array();
        }
        
        // if admin, display add new wiki link
        $out .= '<p>';
        
        if ( ( $groupId && claro_is_group_member() ) || $is_allowedToAdmin )
        {
            $cmdList[] = array(
                'img' => 'wiki_new',
                'name' => get_lang("Create a new Wiki"),
                'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?action=rqEdit'))
            );
        }
        
        $cmdList[] = array(
            'img' => 'search',
            'name' => get_lang("Search"),
            'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?action=rqSearch'))
        );
        
        // Display list in a table
        $out .= '<table class="claroTable emphaseLine" style="width: 100%">' . "\n";
        
        // if admin, display title, edit and delete
        if ( ( $groupId && claro_is_group_member() ) || $is_allowedToAdmin )
        {
            $out .= '<thead>' . "\n"
                  . '<tr class="headerX" style="text-align: center;">' . "\n"
                  . '<th>'.get_lang("Title").'</th>' . "\n"
                  . '<th>'.get_lang("Number of pages").'</th>' . "\n"
                  . '<th>'.get_lang("Recent changes").'</th>'
                  . '<th>'.get_lang("Properties").'</th>' . "\n"
                  . '<th>'.get_lang("Delete").'</th>' . "\n"
                  . ( true === $is_allowedToAdmin ? '<th>'.get_lang("Export").'</th>' . "\n" : '' )
                  . '</tr>' . "\n"
                  . '</thead>' . "\n";
        }
        // else display title only
        else
        {
            $out .= '<thead>' . "\n"
                  . '<tr class="headerX" style="text-align: center;">' . "\n"
                  . '<th>'.get_lang("Title").'</th>' . "\n"
                  . '<th>'.get_lang("Number of pages").'</th>' . "\n"
                  . '<th>'.get_lang("Recent changes").'</th>'
                  . '</tr>' . "\n"
                  . '</thead>' . "\n";
        }
        
        $out .= '<tbody>' . "\n";
        
        // wiki list not empty
        if ( count( $wikiList ) > 0 )
        {
            foreach ( $wikiList as $entry )
            {
                $out .= '<tr>' . "\n";
                
                // display title for all users
                
                //modify style if the wiki is recently added or modified since last login
                if ( (claro_is_user_authenticated() && $claro_notifier->is_a_notified_ressource(claro_get_current_course_id(), $date, claro_get_current_user_id(), claro_get_current_group_id(), claro_get_current_tool_id(), $entry['id'])))
                {
                    $classItem=" hot";
                }
                else // otherwise just display its title normally
                {
                    $classItem="";
                }
                
                
                $out .= '<td style="text-align: left;">';
                
                // display direct link to main page
                $out .= '<a class="item'.$classItem.'" href="'
                      . claro_htmlspecialchars(Url::Contextualize('page.php?wikiId='
                        . (int)$entry['id'].'&action=show' ) ) . '">'
                      . '<img src="' . get_icon_url('wiki').'" alt="'.get_lang("Wiki").'" />&nbsp;'
                      . $entry['title'] . '</a>'
                      . '</td>' . "\n"
                      . '<td style="text-align: center;">'
                      . '<a href="'
                      . claro_htmlspecialchars(Url::Contextualize(
                        'page.php?wikiId=' . (int) $entry['id'] . '&action=all' ) )
                      .'">'
                      . $wikiStore->getNumberOfPagesInWiki( $entry['id'] )
                      . '</a>'
                      . '</td>' . "\n"
                      . '<td style="text-align: center;">';
                
                // display direct link to main page
                $out .= '<a href="'
                      . claro_htmlspecialchars(Url::Contextualize('page.php?wikiId='
                        . (int) $entry['id'].'&action=recent' ) ) . '">'
                      . '<img src="' . get_icon_url('history').'" alt="'.get_lang("Recent changes").'" />'
                      . '</a>'
                      . '</td>' . "\n";
                
                // if admin, display edit and delete links
                if ( ( $groupId && claro_is_group_member() ) || $is_allowedToAdmin )
                {
                    // edit link
                    $out .= '<td style="text-align:center;">'
                          . '<a href="'
                          . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF'].'?wikiId='
                            . (int) $entry['id'].'&action=rqEdit' ) )
                          . '">'
                          . '<img src="' . get_icon_url('settings').'" alt="'
                          . get_lang("Edit properties").'" />'
                          . '</a>'
                          . '</td>' . "\n";
                    
                    // delete link
                    $out .= '<td style="text-align:center;">'
                          . '<a href="'
                          . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF'].'?wikiId='
                            . (int) $entry['id'].'&action=rqDelete' ))
                          . '">'
                          . '<img src="' . get_icon_url('delete').'" alt="'.get_lang("Delete").'" />'
                          . '</a>'
                          . '</td>' . "\n";
                    
                    if ( true === $is_allowedToAdmin )
                    {
                        $out .= '<td style="text-align:center;">'
                              . '<a href="'
                              . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF'].'?wikiId='
                                . (int)$entry['id'].'&action=exExport' ))
                              . '">'
                              . '<img src="' . get_icon_url('export').'" alt="'.get_lang("Export").'" />'
                              . '</a>'
                              . '</td>' . "\n";
                    }
                }
                
                $out .= '</tr>' . "\n";
                
                if ( ! empty( $entry['description'] ) )
                {
                    $out .= '<tr>' . "\n";
                    
                    if ( $groupId && claro_is_group_member() )
                    {
                        $colspan = 5;
                    }
                    elseif ( $is_allowedToAdmin )
                    {
                        $colspan = 6;
                    }
                    else
                    {
                        $colspan = 3;
                    }
                    
                    $out .= '<td colspan="'
                          . $colspan.'"><div class="comment">'
                          . $entry['description'].'</div></td>' . "\n"
                          . '</tr>' . "\n";
                }
            }
        }
        // wiki list empty
        else
        {
            if ( $groupId && claro_is_group_member() )
            {
                $colspan = 5;
            }
            elseif ( $is_allowedToAdmin )
            {
                $colspan = 6;
            }
            else
            {
                $colspan = 3;
            }
            
            $out .= '<tr><td colspan="'.$colspan.'" style="text-align: center;">'
                  . get_lang("No Wiki")
                  . '</td></tr>' . "\n";
        }
        
        $out .= '</tbody>'
              . '</table>' . "\n\n";
        
        break;
    }
    default:
    {
        trigger_error( "Invalid action supplied to " . claro_htmlspecialchars( $_SERVER['PHP_SELF'] )
            , E_USER_ERROR
            );
    }
}

$output = '';
$output .= claro_html_tool_title($toolTitle, $helpUrl, $cmdList);
$output .= $dialogBox->render();
$output .= $out;

// ------------ End of display ---------------

$claroline->display->body->appendContent($output);

echo $claroline->display->render();
