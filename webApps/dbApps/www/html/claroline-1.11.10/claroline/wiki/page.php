<?php // $Id: page.php 14426 2013-04-19 07:12:52Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * @version     1.11 $Revision: 14426 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *              This program is under the terms of the GENERAL PUBLIC LICENSE (GPL)
 *              as published by the FREE SOFTWARE FOUNDATION. The GPL is available
 *              through the world-wide-web at http://www.gnu.org/copyleft/gpl.html
 * @author      Frederic Minne <zefredz@gmail.com>
 * @package     Wiki
 */

$tlabelReq = 'CLWIKI';
require_once '../inc/claro_init_global.inc.php';

if ( ! claro_is_tool_allowed() )
{
    if ( ! claro_is_in_a_course() )
    {
        claro_die(get_lang("Not allowed"));
    }
    else
    {
        claro_disp_auth_form( true );
    }
}

// check and set user access level for the tool

if ( ! isset( $_REQUEST['wikiId'] ) )
{
    claro_redirect(Url::Contextualize("wiki.php"));
    exit();
}

// set admin mode and groupId

claro_set_display_mode_available(TRUE);

$is_allowedToAdmin = claro_is_allowed_to_edit()
    || ( claro_is_in_a_group() && claro_is_group_tutor() );

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

// Wiki specific classes and libraries

require_once "lib/class.wiki2xhtmlrenderer.php";
require_once "lib/class.wikipage.php";
require_once "lib/class.wikistore.php";
require_once "lib/class.wiki.php";
require_once "lib/class.wikisearchengine.php";
require_once "lib/lib.requestfilter.php";
require_once "lib/lib.wikisql.php";
require_once "lib/lib.wikidisplay.php";
require_once "lib/lib.javascript.php";

$dialogBox = new DialogBox();


// security fix : disable access to other groups wiki
if ( isset( $_REQUEST['wikiId'] ) )
{
    $wikiId = (int) $_REQUEST['wikiId'];

    // Database nitialisation

    $tblList = claro_sql_get_course_tbl();

    $con = Claroline::getDatabase();

    $sql = "SELECT `group_id` "
        . "FROM `" . $tblList[ "wiki_properties" ] . "` "
        . "WHERE `id` = " . $wikiId
        ;

    $result = $con->query( $sql )->fetch();

    $wikiGroupId = (int) $result['group_id'];

    if ( claro_is_in_a_group() && claro_get_current_group_id() != $wikiGroupId )
    {
        claro_die(get_lang("Not allowed"));
    }
    elseif( ! claro_is_in_a_group() && $result['group_id'] != 0 )
    {
        claro_die(get_lang("Not allowed"));
    }
}

// Claroline libraries

require_once get_path('incRepositorySys') . '/lib/user.lib.php';

// set request variables

$wikiId = ( isset( $_REQUEST['wikiId'] ) ) ? (int) $_REQUEST['wikiId'] : 0;

// Database nitialisation

$tblList = claro_sql_get_course_tbl();

$config = array();
$config["tbl_wiki_properties"] = $tblList[ "wiki_properties" ];
$config["tbl_wiki_pages"] = $tblList[ "wiki_pages" ];
$config["tbl_wiki_pages_content"] = $tblList[ "wiki_pages_content" ];
$config["tbl_wiki_acls"] = $tblList[ "wiki_acls" ];

$con = Claroline::getDatabase();

// auto create wiki in devel mode
if ( defined("DEVEL_MODE") && ( DEVEL_MODE == true ) )
{
    init_wiki_tables( $con, false );
}

// Objects instantiation

$wikiStore = new WikiStore( $con, $config );

if ( ! $wikiStore->wikiIdExists( $wikiId ) )
{
    die ( get_lang("Invalid Wiki Id") );
}

$wiki = $wikiStore->loadWiki( $wikiId );
$wikiPage = new WikiPage( $con, $config, $wikiId );
$wikiRenderer = new Wiki2xhtmlRenderer( $wiki );

$accessControlList = $wiki->getACL();

// --------------- Start of access rights management --------------

// Wiki access levels

$is_allowedToEdit   = false;
$is_allowedToRead   = false;
$is_allowedToCreate = false;

// set user access rights using user status and wiki access control list

if ( claro_is_in_a_group() && claro_is_group_allowed() )
{
    // group_context
    if ( is_array( $accessControlList ) )
    {
        $is_allowedToRead = $is_allowedToAdmin
            || ( claro_is_group_member() && WikiAccessControl::isAllowedToReadPage( $accessControlList, 'group' ) )
            || ( claro_is_course_member() && WikiAccessControl::isAllowedToReadPage( $accessControlList, 'course' ) )
            || WikiAccessControl::isAllowedToReadPage( $accessControlList, 'other' );
        $is_allowedToEdit = $is_allowedToRead && ( $is_allowedToAdmin
            || ( claro_is_group_member() && WikiAccessControl::isAllowedToEditPage( $accessControlList, 'group' ) )
            || ( claro_is_course_member() && WikiAccessControl::isAllowedToEditPage( $accessControlList, 'course' ) )
            || WikiAccessControl::isAllowedToEditPage( $accessControlList, 'other' ) );
        $is_allowedToCreate = $is_allowedToEdit && ( $is_allowedToAdmin
            || ( claro_is_group_member() && WikiAccessControl::isAllowedToCreatePage( $accessControlList, 'group' ) )
            || ( claro_is_course_member() && WikiAccessControl::isAllowedToCreatePage( $accessControlList, 'course' ) )
            || WikiAccessControl::isAllowedToCreatePage( $accessControlList, 'other' ) );
    }
}
else
{
    // course context
    if ( is_array( $accessControlList ) )
    {
        // course member
        if ( claro_is_course_member() || claro_is_platform_admin () )
        {
            $is_allowedToRead = $is_allowedToAdmin
                || WikiAccessControl::isAllowedToReadPage( $accessControlList, 'course' );
            $is_allowedToEdit = $is_allowedToRead && ( $is_allowedToAdmin
                || WikiAccessControl::isAllowedToEditPage( $accessControlList, 'course' ) );
            $is_allowedToCreate = $is_allowedToEdit && ( $is_allowedToAdmin
                || WikiAccessControl::isAllowedToCreatePage( $accessControlList, 'course' ) );
        }
        // not a course member
        else
        {
            $is_allowedToRead = $is_allowedToAdmin
                || WikiAccessControl::isAllowedToReadPage( $accessControlList, 'other' );
            $is_allowedToEdit = $is_allowedToRead && ( $is_allowedToAdmin
                || WikiAccessControl::isAllowedToEditPage( $accessControlList, 'other' ) );
            $is_allowedToCreate = $is_allowedToEdit && ( $is_allowedToAdmin
                || WikiAccessControl::isAllowedToCreatePage( $accessControlList, 'other' ) );
        }
    }
}

if ( ! $is_allowedToRead )
{
    claro_die( get_lang("You are not allowed to read this page") );
}

// --------------- End of  access rights management ----------------

// filter action

if ( $is_allowedToEdit || $is_allowedToCreate )
{
    $valid_actions = array( 'edit', 'preview', 'save'
        , 'show', 'recent', 'diff', 'all', 'history'
        , 'rqSearch', 'exSearch'
        );
}
else
{
    $valid_actions = array( 'show', 'recent', 'diff', 'all'
        , 'history', 'rqSearch', 'exSearch'
        );
}

$_CLEAN = filter_by_key( 'action', $valid_actions, "R", false );

$action = ( isset( $_CLEAN['action'] ) ) ? $_CLEAN['action'] : 'show';

// get request variables

$creatorId = claro_get_current_user_id();

$versionId = ( isset( $_REQUEST['versionId'] ) ) ? (int) $_REQUEST['versionId'] : 0;

$title = ( isset( $_REQUEST['title'] ) ) ? strip_tags( rawurldecode( $_REQUEST['title'] ) ) : '';

if ( 'diff' == $action )
{
    $old = ( isset( $_REQUEST['old'] ) ) ? (int) $_REQUEST['old'] : 0;
    $new = ( isset( $_REQUEST['new'] ) ) ? (int) $_REQUEST['new'] : 0;
}

// get content

if ( 'edit' == $action )
{
    if ( isset( $_REQUEST['content'] ) )
    {
        $content = ( $_REQUEST['content'] == '' ) ? "__CONTENT__EMPTY__" : $_REQUEST['content'];
    }
    else
    {
        $content = '';
    }
}
else
{
    $content = ( isset( $_REQUEST['content'] ) ) ? $_REQUEST['content'] : '';
}

// use __MainPage__ if empty title

if ( '' === $title )
{
    // create wiki main page in a localisation compatible way
    $title = '__MainPage__';

    if ( $wikiStore->pageExists( $wikiId, $title ) )
    {
        // do nothing
    }
    // auto create wiki in devl mode
    elseif ( ( ! $wikiStore->pageExists( $wikiId, $title ) )
        && ( defined('DEVEL_MODE') && ( DEVEL_MODE == true ) ) )
    {
        init_wiki_main_page( $con, $wikiId, $creatorId );
    }
    else
    {
        // something weird's happened
        claro_die( get_lang( "Wrong page title" ) );
    }
}

// --------- Start of wiki command processing ----------

// init message
$message = '';

switch( $action )
{
    case 'rqSearch':
    {
        break;
    }
    case 'exSearch':
    {
        $pattern = isset( $_REQUEST['searchPattern'] )
            ? trim($_REQUEST['searchPattern'])
            : null
            ;

        if ( !empty( $pattern ) )
        {
            $searchEngine = new WikiSearchEngine( $con, $config );
            $searchResult = $searchEngine->searchInWiki( $pattern, $wikiId, CLWIKI_SEARCH_ANY );

            if ( $searchEngine->hasError() )
            {
                claro_die( $searchEngine->getError() );
            }

            if ( is_null( $searchResult ) )
            {
                $searchResult = array();
            }

            $wikiList = $searchResult;
        }
        else
        {
            $message = get_lang("Missing search keywords");
            $dialogBox->error( $message );
            $action = 'rqSearch';
        }
        break;
    }
    // show differences
    case 'diff':
    {
        require_once 'lib/lib.diff.php';

        if ( $wikiStore->pageExists( $wikiId, $title ) )
        {
            // older version
            $wikiPage->loadPageVersion( $old );
            $old = $wikiPage->getContent();
            $oldTime = $wikiPage->getCurrentVersionMtime();
            $oldEditor = $wikiPage->getEditorId();

            // newer version
            $wikiPage->loadPageVersion( $new );
            $new = $wikiPage->getContent();
            $newTime = $wikiPage->getCurrentVersionMtime();
            $newEditor = $wikiPage->getEditorId();

            // protect against dangerous html
            $old = claro_htmlspecialchars( $old );
            $new = claro_htmlspecialchars( $new );

            // get differences
            $diff = '<table style="border: 0;">'.diff( $new, $old, true, 'format_table_line' ).'</table>';
        }

        break;
    }
    // page history
    case 'history':
    // recent changes
    case 'recent':
    {
        $wikiPage->loadPage( $title );
        $title = $wikiPage->getTitle();

        ###### CHANGE AND MOVE DEFAULT VALUE TO CONFIG #####
        $defaultStep = 10;

        $offset = isset( $_REQUEST['offset'] )
            ? (int) $_REQUEST['offset']
            : 0
            ;

        $step = isset( $_REQUEST['step'] )
            ? (int) $_REQUEST['step']
            : $defaultStep
            ;

        if ( 'history' == $action )
        {
            $nbEntries = $wikiPage->countVersion();
        }

        if ( 'recent' == $action )
        {
            $nbEntries = $wiki->getNumberOfPages();
        }

        $last = 0;
        $first = 0;

        if ( $step === 0 )
        {
            $offset = 0;

            while ( $last < $nbEntries)
                 $last += $defaultStep;

            $last = $last > $nbEntries
                ? $last - $defaultStep
                : $last
                ;

            $previous = false;
            $next = false;
        }
        else
        {
            while ( $last < $nbEntries)
                 $last += $step;

            $last = $last > $nbEntries
                ? $last - $step
                : $last
                ;

            $previous = ( $offset - $step ) < 0
                ? false
                : $offset - $step
                ;

            $next     = ( $offset + $step ) >= $nbEntries
                ? false
                : $offset + $step
                ;

            if ( $next > $nbEntries )
            {
                $next = false;
            }

            if ( $previous < 0 )
            {
                $previous = false;
            }
        }

        // get page history
        if ( 'history' == $action )
        {
            $history = $wikiPage->history( $offset, $step, 'DESC' );
        }
        // get recent changes
        if ( 'recent' == $action )
        {
            $recentChanges = $wiki->recentChanges( $offset, $step );
        }

        if ( 0 === $step ) $step = $defaultStep;
        break;
    }
    // all pages
    case 'all':
    {
        $allPages = $wiki->allPages();
        break;
    }
    // edit page content
    case 'edit':
    {
        if( $wikiStore->pageExists( $wikiId, $title ) )
        {
            if ( 0 == $versionId )
            {
                $wikiPage->loadPage( $title );
            }
            else
            {
                $wikiPage->loadPageVersion( $versionId );
            }

            if ( '' == $content )
            {
                $content = $wikiPage->getContent();
            }

            if  ( '__CONTENT__EMPTY__' == $content )
            {
                $content = '';
            }

            $title = $wikiPage->getTitle();

            $_SESSION['wikiLastVersion'] = $wikiPage->getLastVersionId();
        }
        else
        {
            if ( $content == '' )
            {
                $message = get_lang("This page is empty, use the editor to add content.");
                $dialogBox->info( $message );
            }
        }
        break;
    }
    // view page
    case 'show':
    {
        unset( $_SESSION['wikiLastVersion'] );

        if ( $wikiStore->pageExists( $wikiId, $title ) )
        {
            if ( $versionId == 0 )
            {
                $wikiPage->loadPage( $title );
            }
            else
            {
                $wikiPage->loadPageVersion( $versionId );
            }

            $content = $wikiPage->getContent();

            $title = $wikiPage->getTitle();

            $claroline = Claroline::getInstance();
            $claroline->notifier->event('wiki_read_page', array( 'data' => array('wiki_id' => $wikiId, 'title' => $title, 'version_id' => $versionId ) ) );
        }
        else
        {
            $message = get_lang('Page %title not found', array('%title'=>$title) );
            $dialogBox->error( $message );
        }
        break;
    }
    // save page
    case 'save':
    {
        if ( isset( $content ) )
        {
            $time = date( "Y-m-d H:i:s" );

            if ( $wikiPage->pageExists( $title ) )
            {
                $wikiPage->loadPage( $title );

                if ( $content == $wikiPage->getContent() )
                {
                    unset( $_SESSION['wikiLastVersion'] );

                    $message = get_lang("Identical content<br />no modification saved");
                    $dialogBox->info( $message );
                    $action = 'show';
                }
                else
                {
                    if ( isset( $_SESSION['wikiLastVersion'] )
                        && $wikiPage->getLastVersionId() != $_SESSION['wikiLastVersion'] )
                    {
                        $action = 'conflict';
                    }
                    else
                    {
                        $wikiPage->edit( $creatorId, $content, $time, true );

                        unset( $_SESSION['wikiLastVersion'] );

                        if ( $wikiPage->hasError() )
                        {
                            $message = get_lang( "Database error : " ) . $wikiPage->getError();
                            $dialogBox->error( $message );
                        }
                        else
                        {
                            $message = get_lang("Page saved");
                            $dialogBox->success( $message );
                        }

                        $action = 'show';
                    }
                }

                //notify modification of the page

                $eventNotifier->notifyCourseEvent('wiki_page_modified'
                                                 , claro_get_current_course_id()
                                                 , claro_get_current_tool_id()
                                     , $wikiId
                                                 , claro_get_current_group_id()
                                     , '0');
            }
            else
            {
                $wikiPage->create( $creatorId, $title, $content, $time, true );

                if ( $wikiPage->hasError() )
                {
                    $message = get_lang( "Database error : " ) . $wikiPage->getError();
                    $dialogBox->error( $message );
                }
                else
                {
                    $message = get_lang("Page saved");
                    $dialogBox->success( $message );
                }

                $action = 'show';

                //notify creation of the page

                $eventNotifier->notifyCourseEvent('wiki_page_added'
                                                 , claro_get_current_course_id()
                                                 , claro_get_current_tool_id()
                                                 , $wikiId
                                                 , claro_get_current_group_id()
                                     , '0');
            }
        }

        break;
    }
}

// change to use empty page content

if ( ! isset( $content ) )
{
    $content = '';
}

// --------- End of wiki command processing -----------

// --------- Start of wiki display --------------------

// set xtra head

$jspath = document_web_path() . '/lib/javascript';

// set image repository
$htmlHeadXtra[] = "<script type=\"text/javascript\">"
    . "\nvar sImgPath = '" . get_path('imgRepositoryWeb') . "'"
    . "\n</script>\n"
    ;

// set style

$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="./css/wiki.css" media="screen, projection, tv" />' . "\n";

if ( $action == 'show' || $action == 'preview' )
{
    /*$htmlHeadXtra[] = '<script type="text/javascript" src="./lib/javascript/toc.js"></script>' . "\n";
    $claroBodyOnload[] = 'createTOC();' . "\n";*/
    $htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="./css/toc.css" media="screen, projection, tv" />' . "\n";
}

// Breadcrumps

$nameTools = get_lang( 'Wiki' );
ClaroBreadCrumbs::getInstance()->append(
    claro_htmlspecialchars($wiki->getTitle()),
    claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF']
        . '?action=show&wikiId=' . (int) $wikiId ))
);

switch( $action )
{
    case 'edit':
    {
        $dispTitle = ( '__MainPage__' == $title ) ? get_lang("Main page") : $title;
        
        ClaroBreadCrumbs::getInstance()->append(
            claro_htmlspecialchars($dispTitle),
            claro_htmlspecialchars(Url::Contextualize( 'page.php?action=show&wikiId='
                . $wikiId . '&title=' . $title ))
        );
        
        ClaroBreadCrumbs::getInstance()->append(
            claro_htmlspecialchars( get_lang('Edit')) );
        
        break;
    }
    case 'preview':
    {
        $dispTitle = ( '__MainPage__' == $title ) ? get_lang("Main page") : $title;
        
        ClaroBreadCrumbs::getInstance()->append(
            claro_htmlspecialchars($dispTitle),
            claro_htmlspecialchars(Url::Contextualize(
                'page.php?action=show&wikiId='
                . $wikiId . '&title=' . $title ))
        );
        
        ClaroBreadCrumbs::getInstance()->append(
            claro_htmlspecialchars( get_lang('Preview')) );
        
        break;
    }
    case 'all':
    {
        ClaroBreadCrumbs::getInstance()->append(
            claro_htmlspecialchars( get_lang('All pages')) );
        
        break;
    }
    case 'recent':
    {
        ClaroBreadCrumbs::getInstance()->append(
            claro_htmlspecialchars( get_lang('Recent changes')) );
        
        break;
    }
    case 'history':
    {
        $dispTitle = ( '__MainPage__' == $title ) ? get_lang("Main page") : $title;
        
        ClaroBreadCrumbs::getInstance()->append(
            claro_htmlspecialchars($dispTitle),
            claro_htmlspecialchars(Url::Contextualize(
                'page.php?action=show&wikiId='
                . $wikiId . '&title=' . $title ))
        );
        
        ClaroBreadCrumbs::getInstance()->append(
            claro_htmlspecialchars( get_lang("History")) );
        
        break;
    }
    default:
    {
        $pageTitle = ( '__MainPage__' == $title ) ? get_lang("Main page") : $title ;
        ClaroBreadCrumbs::getInstance()->append(
            claro_htmlspecialchars($pageTitle) );
    }
}

$out = '';

// Help URL
$helpUrl = null;

// Tool title
$toolTitle = array();
$toolTitle['mainTitle'] = sprintf( get_lang('Wiki : %s'), $wiki->getTitle() );

if ( claro_is_in_a_group() )
{
    $toolTitle['supraTitle'] = claro_get_current_group_data('name');
}

switch( $action )
{
    case 'all':
    {
        $toolTitle['subTitle'] = get_lang("All pages");
        break;
    }
    case 'recent':
    {
        $toolTitle['subTitle'] = get_lang("Recent changes");
        break;
    }
    case 'history':
    {
        $toolTitle['subTitle'] = get_lang("Page history");
        break;
    }
    case 'rqSearch':
    case 'exSearch':
    {
        $toolTitle['subTitle'] = get_lang("Search in pages");
        break;
    }
    default:
    {
        break;
    }
}

// Command list
$cmdList = array();

// Check javascript
$javascriptEnabled = claro_is_javascript_enabled();

// Wiki navigation bar
$cmdWikiNavigationBar[] =
    claro_html_cmd_link(
        claro_htmlspecialchars(Url::Contextualize(
            $_SERVER['PHP_SELF']
            . '?wikiId=' . $wiki->getWikiId()
            . '&action=show'
            . '&title=__MainPage__' ))
        , '<img src="' . get_icon_url('wiki').'" alt="edit" />&nbsp;'
            . get_lang("Main page")
    );

$cmdWikiNavigationBar[] =
    claro_html_cmd_link(
        claro_htmlspecialchars(Url::Contextualize(
            $_SERVER['PHP_SELF']
            . '?wikiId=' . $wiki->getWikiId()
            . '&action=recent' ))
        , '<img src="' . get_icon_url('history').'" '
            . ' alt="recent changes" />&nbsp;'
            . get_lang("Recent changes")
    );

$cmdWikiNavigationBar[] =
    claro_html_cmd_link(
        claro_htmlspecialchars(Url::Contextualize(
            $_SERVER['PHP_SELF']
            . '?wikiId=' . $wiki->getWikiId()
            . '&action=all' ))
        , '<img src="' . get_icon_url('allpages').'" '
            . ' alt="all pages" />&nbsp;'
            . get_lang("All pages")
    );


$cmdWikiNavigationBar[] =
    claro_html_cmd_link(
        claro_htmlspecialchars(Url::Contextualize('wiki.php'))
            , '<img src="' . get_icon_url('list').'" '
            . ' alt="all pages" />'
            . '&nbsp;'
            . get_lang("List of Wiki")
    );

$cmdWikiNavigationBar[] =
    claro_html_cmd_link(
        claro_htmlspecialchars(Url::Contextualize(
            $_SERVER['PHP_SELF']
            . '?wikiId=' . $wiki->getWikiId()
            . '&action=rqSearch' ))
        , '<img src="' . get_icon_url('search').'" '
            . ' alt="all pages" />&nbsp;'
            . get_lang("Search")
    );

$out .= '<p>' . claro_html_menu_horizontal($cmdWikiNavigationBar). '</p>';

if ( 'recent' != $action && 'all' != $action
    && 'rqSearch' != $action && 'exSearch' != $action )
{

    if ( 'show' == $action || 'edit' == $action || 'history' == $action )
    {
        $cmdList[] = array(
            'img' => 'back',
            'name' => get_lang("Back to page"),
            'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']
                . '?wikiId=' . $wiki->getWikiId()
                . '&action=show'
                . '&title=' . rawurlencode($title)))
        );
    }
    
    if ( $is_allowedToEdit || $is_allowedToCreate )
    {
        // Show context
        if ( 'show' == $action || 'edit' == $action || 'diff' == $action )
        {
            $cmdList[] = array(
                'img' => 'edit',
                'name' => get_lang("Edit this page"),
                'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']
                    . '?wikiId=' . $wiki->getWikiId()
                    . '&action=edit'
                    . '&title=' . rawurlencode($title)
                    . '&versionId=' . $versionId))
            );
        }
    }

    if ( 'show' == $action || 'edit' == $action
        || 'history' == $action || 'diff' == $action )
    {
        // active
        $cmdList[] = array(
            'img' => 'versions',
            'name' => get_lang("Page history"),
            'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']
                . '?wikiId=' . $wiki->getWikiId()
                . '&action=history'
                . '&title=' . rawurlencode($title)))
        );
    }
    
    if ( 'edit' == $action || 'diff' == $action )
    {
        $helpUrl = get_help_page_url('blockWikiHelpSyntaxContent', 'CLWIKI');
    }

}

switch( $action )
{
    case 'conflict':
    {
        if( '__MainPage__' === $title )
        {
            $displaytitle = get_lang("Main page");
        }
        else
        {
            $displaytitle = $title;
        }

        $out .= '<div class="wikiTitle">' . "\n";
        $out .= '<h1>'.$displaytitle
            . ' : ' . get_lang("Edit conflict")
            . '</h1>'
            . "\n"
            ;
        $out .= '</div>' . "\n";

        $message = get_block('blockWikiConflictHowTo');
        $dialogBox->info( $message );

        $out .= '<form id="editConflict" action="'
            . claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']))
            . '" method="post">'
            ;
        $out .= '<textarea name="conflictContent" id="content"'
             . ' cols="80" rows="15" >'
             ;
        $out .= $content;
        $out .= '</textarea><br /><br />' . "\n";
        $out .= '<div>' . "\n";
        $out .= '<input type="hidden" name="wikiId" value="'.(int)$wikiId.'" />' . "\n";
        $out .= '<input type="hidden" name="title" value="'.claro_htmlspecialchars($title).'" />' . "\n";
        $out .= '<input type="submit" name="action[edit]" value="'.get_lang("Edit last version").'" />' . "\n";
        $url = claro_htmlspecialchars(Url::Contextualize(
            $_SERVER['PHP_SELF']
            . '?wikiId=' . $wikiId
            . '&title=' . $title
            . '&action=show' ))
            ;
        $out .= claro_html_button( $url, get_lang("Cancel") ) . "\n";
        $out .= '</div>' . "\n";
        $out .= '</form>';
        break;
    }
    case 'diff':
    {
        if( '__MainPage__' === $title )
        {
            $displaytitle = get_lang("Main page");
        }
        else
        {
            $displaytitle = $title;
        }

        $oldTime = claro_html_localised_date( get_locale('dateTimeFormatLong')
                    , strtotime($oldTime) )
                    ;

        $userInfo = user_get_properties( $oldEditor );
        $oldEditorStr = $userInfo['firstname'] . "&nbsp;" . $userInfo['lastname'];

        $newTime = claro_html_localised_date( get_locale('dateTimeFormatLong')
                    , strtotime($newTime) )
                    ;

        $userInfo = user_get_properties( $newEditor );
        $newEditorStr = $userInfo['firstname'] . "&nbsp;" . $userInfo['lastname'];

        $versionInfo = '('
            . sprintf(
                get_lang('differences between version of %1\$s modified by %2\$s and version of %3\$s modified by %4\$s')
                    , $oldTime, $oldEditorStr, $newTime, $newEditorStr )
            . ')'
            ;

        $versionInfo = '&nbsp;<span style="font-size: 40%; font-weight: normal; color: red;">'
                    . $versionInfo . '</span>'
                    ;

        $out .= '<div class="wikiTitle">' . "\n";
        $out .= '<h1>'.$displaytitle
            . $versionInfo
            . '</h1>'
            . "\n"
            ;
        $out .= '</div>' . "\n";

        $out .= '<strong>'.get_lang("Keys :").'</strong>';

        $out .= '<div class="diff">' . "\n";
        $out .= '= <span class="diffEqual" >'.get_lang("Unchanged line").'</span><br />';
        $out .= '+ <span class="diffAdded" >'.get_lang("Added line").'</span><br />';
        $out .= '- <span class="diffDeleted" >'.get_lang("Deleted line").'</span><br />';
        $out .= 'M <span class="diffMoved" >'.get_lang("Moved line").'</span><br />';
        $out .= '</div>' . "\n";

        $out .= '<strong>'.get_lang("Differences :").'</strong>';

        $out .= '<div class="diff">' . "\n";
        $out .= $diff;
        $out .= '</div>' . "\n";

        break;
    }
    case 'recent':
    {
        $script = Url::Contextualize(
            $_SERVER['PHP_SELF']
            . '?wikiId=' . (int) $wikiId
            . '&action=recent' )
            ;

        $out .= '<p>'
            . '<a href="'.claro_htmlspecialchars($script.'&offset='
            . $first .'&step=' . (int) $step ) .'">&lt;&lt; '.get_lang('First').'</a>'
            . ( $previous !== false
                ? ' ' . '<a href="'.claro_htmlspecialchars( $script.'&offset='
                  . $previous .'&step=' . (int) $step ) .'">&lt; '.get_lang('Previous').'</a>'
                : ' &lt; '.get_lang('Previous') )
            . ' ' . '<a href="'.$script.'&offset=0&step=0">'.get_lang('All').'</a>'
            . ( $next !== false
                ? ' ' . '<a href="'.claro_htmlspecialchars( $script.'&offset='
                  . $next .'&step=' . (int) $step ) .'">'.get_lang('Next').' &gt;</a>'
                : get_lang('Next').' &gt;' )
            . ' ' . '<a href="'. claro_htmlspecialchars( $script.'&offset='
            . $last . '&step=' . (int) $step ) .'">'.get_lang('Last').' &gt;&gt;</a>'
            . '</p>'
            ;

        if (  count( $recentChanges ) )
        {
            $out .= '<ul>' . "\n";

            foreach ( $recentChanges as $recentChange )
            {
                $pgtitle = ( '__MainPage__' == $recentChange['title'] )
                    ? get_lang("Main page")
                    : $recentChange['title']
                    ;

                $entry = '<strong><a href="'
                    . claro_htmlspecialchars(Url::Contextualize(
                        $_SERVER['PHP_SELF'].'?wikiId='
                        . (int)$wikiId . '&title=' . rawurlencode( $recentChange['title'] )
                        . '&action=show' ))
                    . '">'.$pgtitle.'</a></strong>'
                    ;

                $time = claro_html_localised_date( get_locale('dateTimeFormatLong')
                    , strtotime($recentChange['last_mtime']) )
                    ;

                $userInfo = user_get_properties( $recentChange['editor_id'] );

                if ( !empty( $userInfo ) )
                {
                    $userStr = $userInfo['firstname'] . "&nbsp;" . $userInfo['lastname'];
                }
                else
                {
                    $userStr = get_lang('Unknown user');
                }

                if ( claro_is_course_member() )
                {
                    $userUrl = '<a href="'
                        . claro_htmlspecialchars(Url::Contextualize(
                            get_module_url('CLUSR')
                            . '/userInfo.php'
                            . '?uInfo=' . (int)$recentChange['editor_id'] ))
                        . '">'
                        . $userStr
                        . '</a>'
                        ;
                }
                else
                {
                    $userUrl = $userStr;
                }

                $out .= '<li>'
                    . sprintf( get_lang('%1\$s modified on %2\$s by %3\$s'), $entry, $time, $userUrl )
                    . '</li>'
                    . "\n"
                    ;
            }

            $out .= '</ul>' . "\n";
        }
        break;
    }
    case 'all':
    {
        // handle main page

        $out .= '<ul><li><a href="'
            . claro_htmlspecialchars(Url::Contextualize(
                $_SERVER['PHP_SELF']
                . '?wikiId=' . (int)$wikiId
                . '&title=' . rawurlencode("__MainPage__")
                . '&action=show'))
            . '">'
            . get_lang("Main page")
            . '</a></li></ul>' . "\n"
            ;

        // other pages

        if ( count( $allPages ) )
        {
            $out .= '<ul>' . "\n";

            foreach ( $allPages as $page )
            {
                if ( '__MainPage__' == $page['title'] )
                {
                    // skip main page
                    continue;
                }

                $pgtitle = rawurlencode( $page['title'] );

                $link = '<a href="'
                    . claro_htmlspecialchars(Url::Contextualize(
                        $_SERVER['PHP_SELF'].'?wikiId='
                        . (int) $wikiId . '&title='
                        . $pgtitle . '&action=show' ))
                    . '">' . $page['title'] . '</a>'
                    ;

                $out .= '<li>' . $link. '</li>' . "\n";
            }
            $out .= '</ul>' . "\n";
        }
        break;
    }
    // edit page
    case 'edit':
    {
        if ( ! $wiki->pageExists( $title ) && ! $is_allowedToCreate )
        {
            $out .= get_lang("You are not allowed to create pages");
        }
        elseif ( $wiki->pageExists( $title ) && ! $is_allowedToEdit )
        {
            $out .= get_lang("You are not allowed to edit this page");
        }
        else
        {
            $script = claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']));

            $out .= claro_disp_wiki_editor( $wikiId, $title, $versionId, $content, $script
                , get_conf('showWikiEditorToolbar'), get_conf('forcePreviewBeforeSaving') )
                ;
        }

        break;
    }
    // page preview
    case 'preview':
    {
        if ( ! isset( $content ) )
        {
            $content = '';
        }

        $out .= claro_disp_wiki_preview( $wikiRenderer, $title, $content );

        $out .= claro_disp_wiki_preview_buttons( $wikiId, $title, $content );

        break;
    }
    // view page
    case 'show':
    {
        if( $wikiPage->hasError() )
        {
            $out .= $wikiPage->getError();
        }
        else
        {
            // get localized value for wiki main page title
            if( '__MainPage__' === $title )
            {
                $displaytitle = get_lang("Main page");
            }
            else
            {
                $displaytitle = $title;
            }

            if ( $versionId != 0 )
            {
                $editorInfo = user_get_properties( $wikiPage->getEditorId() );

                $editorStr = $editorInfo['firstname'] . "&nbsp;" . $editorInfo['lastname'];

                if ( claro_is_course_member() )
                {
                    $editorUrl = '&nbsp;-&nbsp;<a href="'
                        . claro_htmlspecialchars(Url::Contextualize(
                            get_module_url('CLUSR')
                            . '/userInfo.php?uInfo='
                            . (int) $wikiPage->getEditorId() ))
                        .'">'
                        . $editorStr.'</a>'
                        ;
                }
                else
                {
                    $editorUrl = '&nbsp;-&nbsp;' . $editorStr;
                }

                $mtime = claro_html_localised_date( get_locale('dateTimeFormatLong')
                    , strtotime($wikiPage->getCurrentVersionMtime()) )
                    ;

                $versionInfo = sprintf( get_lang('(version of %1\$s modified by %2\$s)'), $mtime, $editorUrl );

                $versionInfo = '&nbsp;<span style="font-size: 40%; font-weight: normal; color: red;">'
                    . $versionInfo . '</span>'
                    ;
            }
            else
            {
                $versionInfo = '';
            }

            $out .= '<div id="mainContent" class="wiki2xhtml">' . "\n";
            $out .= '<h1>'.$displaytitle
                . $versionInfo
                . '</h1>'
                . "\n"
                ;
            $out .= $wikiRenderer->render( $content );
            $out .= '</div>' . "\n";

            $out .= '<div class="clearer"><!-- spacer --></div>' . "\n";
        }

        break;
    }
    case 'history':
    {
        if( '__MainPage__' === $title )
        {
            $displaytitle = get_lang("Main page");
        }
        else
        {
            $displaytitle = $title;
        }

        $out .= '<div class="wikiTitle">' . "\n";
        $out .= '<h1>'.$displaytitle.'</h1>' . "\n";
        $out .= '</div>' . "\n";

        $script = Url::Contextualize(
            $_SERVER['PHP_SELF']
            . '?wikiId=' . (int) $wikiId
            . '&title=' . rawurlencode( $title )
            . '&action=history');

        $out .= '<p>'
            . '<a href="'.claro_htmlspecialchars($script.'&offset='
            . $first .'&step=' . (int) $step) .'">&lt;&lt; ' 
            . get_lang('First') . '</a>'
            . ( $previous !== false
                ? ' ' . '<a href="'.claro_htmlspecialchars($script.'&offset='
                  . $previous .'&step=' . (int) $step) .'">&lt; ' 
                  . get_lang('Previous') . '</a>'
                : ' &lt; ' . get_lang('Previous') )
            . ' ' . '<a href="'.claro_htmlspecialchars($script.'&offset=0&step=0').'">' 
            . get_lang('All') . '</a>'
            . ( $next !== false
                ? ' ' . '<a href="'.claro_htmlspecialchars( $script.'&offset='
                  . $next .'&step=' . (int) $step ) .'">' 
                  . get_lang('Next') . ' &gt;</a>'
                : get_lang('Next') . ' &gt;' )
            . ' ' . '<a href="'.claro_htmlspecialchars( $script.'&offset='
            . $last . '&step=' . (int) $step ).'"> ' . get_lang('Last') 
            . ' &gt;&gt;</a>'
            . '</p>'
            ;

        $out .= '<form id="differences" method="get" action="'
            . claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']))
            . '">'
            . "\n"
            ;

        $out .= '<div>' . "\n"
            . '<input type="hidden" name="wikiId" value="'.(int)$wikiId.'" />' . "\n"
            . '<input type="hidden" name="title" value="'.claro_htmlspecialchars($title).'" />' . "\n"
            . claro_form_relay_context() . "\n"
            . '<input type="submit" name="action[diff]" value="'
            . get_lang("Show differences")
            . '" />' . "\n"
            . '</div>' . "\n"
            ;

        $out .= '<table style="border: 0px;">' . "\n";

        if ( count( $history ) )
        {
            $size = count( $history );
            $passes = 0;

            foreach ( $history as $version )
            {
                $passes++;

                $out .= '<tr>' . "\n";

                // diff between last and previous versions
                // if available
                if ( 1 === $size && 1 === $passes  )
                {
                    $checked1 = ' checked="checked"';
                    $checked2 = ' checked="checked"';
                }
                elseif ( $size > 1 && 1 === $passes )
                {
                    $checked1 = ' checked="checked"';
                    $checked2 = '';
                }
                elseif ( $size > 1 && 2 === $passes )
                {
                    $checked1 = '';
                    $checked2 = ' checked="checked"';
                }
                else
                {
                    $checked1 = '';
                    $checked2 = '';
                }

                $out .= '<td>'
                    . '<input type="radio" name="old" value="'.$version['id'].'"'.$checked1.' />' . "\n"
                    . '</td>'
                    . "\n"
                    ;

                $out .= '<td>'
                    . '<input type="radio" name="new" value="'.$version['id'].'"'.$checked2.' />' . "\n"
                    . '</td>'
                    . "\n"
                    ;

                $userInfo = user_get_properties( $version['editor_id'] );

                if ( ! empty( $userInfo ) )
                {
                    $userStr = $userInfo['firstname'] . " " . $userInfo['lastname'];
                }
                else
                {
                    $userStr = get_lang('Unknown user');
                }

                if ( claro_is_course_member() )
                {
                    $userUrl = '<a href="'
                            . claro_htmlspecialchars(Url::Contextualize(
                             get_module_url('CLUSR')
                             . '/userInfo.php?uInfo='
                             . (int)$version['editor_id']))
                            .'">'
                            . $userStr
                            . '</a>'
                             ;
                }
                else
                {
                    $userUrl = $userStr;
                }

                $versionUrl = '<a href="' .
                    claro_htmlspecialchars(Url::Contextualize(
                        $_SERVER['PHP_SELF']
                        . '?wikiId=' . (int)$wikiId
                        . '&title=' . rawurlencode( $title )
                        . '&action=show'
                        . '&versionId=' . (int)$version['id'] ))
                    . '">'
                    . claro_html_localised_date( get_locale('dateTimeFormatLong')
                                               , strtotime($version['mtime']) )
                    . '</a>'
                    ;

                $out .= '<td>'
                    . sprintf( get_lang('%1\$s by %2\$s'), $versionUrl, $userUrl )
                    . '</td>'
                    . "\n"
                    ;

                $out .= '</tr>' . "\n";
            }
        }

        $out .= '</table>' . "\n";

        $out .= '</form>';

        break;
    }
    case 'exSearch':
    {
        $out .= '<h3>'.get_lang("Search result").'</h3>' . "\n";

        $out .= '<ul>' . "\n";

        foreach ( $searchResult as $page )
        {
            if ( '__MainPage__' == $page['title'] )
            {
                $title = get_lang( "Main page" );
            }
            else
            {
                $title = $page['title'];
            }

            $urltitle = rawurlencode( $page['title'] );

            $link = '<a href="'
                . claro_htmlspecialchars(Url::Contextualize(
                    $_SERVER['PHP_SELF'].'?wikiId='
                    . $wikiId . '&title=' . $urltitle
                    . '&action=show'))
                . '">' . $title . '</a>'
                ;

            $out .= '<li>' . $link. '</li>' . "\n";
        }
        $out .= '</ul>' . "\n";

        break;
    }
    case 'rqSearch':
    {
        $searchForm = '<form method="post" action="'
            . claro_htmlspecialchars(Url::Contextualize(
                $_SERVER['PHP_SELF'].'?wikiId='.(int)$wikiId ))
            .'">'."\n"
            . '<input type="hidden" name="action" value="exSearch" />'."\n"
            . claro_form_relay_context() . "\n"
            . '<label for="searchPattern">'
            . get_lang("Search")
            . '</label><br />'."\n"
            . '<input type="text" id="searchPattern" name="searchPattern" />'."\n"
            . '<input type="submit" value="'.get_lang("Ok").'" />'."\n"
            . claro_html_button(
                claro_htmlspecialchars(Url::Contextualize(
                    $_SERVER['PHP_SELF'].'?wikiId='.$wikiId )),
                get_lang("Cancel"))
            . '</form>'."\n"
            ;
        $dialogBox->form( $searchForm );

        break;
    }
    default:
    {
        trigger_error( "Invalid action supplied to " . claro_htmlspecialchars($_SERVER['PHP_SELF'])
            , E_USER_ERROR
            );
    }
}

$output = '';
$output .= claro_html_tool_title($toolTitle, $helpUrl, $cmdList);
$output .= $dialogBox->render();
$output .= $out;


// ------------ End of wiki script ---------------

$claroline->display->body->appendContent($output);

echo $claroline->display->render();
