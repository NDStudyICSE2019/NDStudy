<?php // $Id: lib.wikidisplay.php 14314 2012-11-07 09:09:19Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * @version     1.11 $Revision: 14314 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * This program is under the terms of the GENERAL PUBLIC LICENSE (GPL)
 * as published by the FREE SOFTWARE FOUNDATION. The GPL is available
 * through the world-wide-web at http://www.gnu.org/copyleft/gpl.html
 * @author      Frederic Minne <zefredz@gmail.com>
 * @package     Wiki
 */

require_once dirname(__FILE__) . "/class.wiki2xhtmlarea.php";
require_once dirname(__FILE__) . "/class.wikiaccesscontrol.php";
require_once dirname(__FILE__) . "/lib.url.php";

/**
 * Generate wiki editor html code
 * @param int wikiId ID of the Wiki
 * @param string title page title
 * @param string content page content
 * @param string script callback script url
 * @param boolean showWikiToolbar use Wiki toolbar if true
 * @param boolean forcePreview force preview before saving
 *      (ie disable save button)
 * @return string HTML code of the wiki editor
 */
function claro_disp_wiki_editor( $wikiId, $title, $versionId
    , $content, $script = null, $showWikiToolBar = true
    , $forcePreview = true )
{

    // create script
    $script = ( is_null( $script ) ) ? Url::Contextualize($_SERVER['PHP_SELF']) : $script;
    $script = add_request_variable_to_url( $script, "title", rawurlencode($title) );

    // set display title
    $localtitle = ( $title === '__MainPage__' ) ? get_lang("Main page") : $title;

    // display title
    $out = '<div class="wikiTitle">' . "\n";
    $out .= '<h1>'.$localtitle.'</h1>' . "\n";
    $out .= '</div>' . "\n";

            // display editor
    $out .= '<form method="post" action="'.claro_htmlspecialchars($script).'"'
        . ' name="editform" id="editform">' . "\n"
        ;

    if ( $showWikiToolBar === true )
    {
        $wikiarea = new Wiki2xhtmlArea( $content, 'content', 80, 15, null );
        $out .= $wikiarea->toHTML();
    }
    else
    {
        $out .= '<label>Texte :</label><br />' . "\n";
        $out .= '<textarea name="content" id="content"'
             . ' cols="80" rows="15" >'
             ;
        $out .= $content;
        $out .= '</textarea>' . "\n";
    }

    $out .= '<div style="padding:10px;">' . "\n";

    $out .= '<input type="hidden" name="wikiId" value="'
        . $wikiId
        . '" />' . "\n"
        ;

    $out .= '<input type="hidden" name="versionId" value="'
        . $versionId
        . '" />' . "\n"
        ;
    
    $out .= claro_form_relay_context() . "\n";

    $out .= '<input type="submit" name="action[preview]" value="'
        .get_lang("Preview").'" />' . "\n"
        ;

    if( ! $forcePreview )
    {
        $out .= '<input type="submit" name="action[save]" value="'
            .get_lang("Save").'" />' . "\n"
            ;
    }

    $location = add_request_variable_to_url( $script, "wikiId", $wikiId );
    $location = add_request_variable_to_url( $location, "action", "show" );

    $out .= claro_html_button ( claro_htmlspecialchars($location), get_lang("Cancel") );

    $out .= '</div>' . "\n";

    $out .= "</form>\n";

    return $out;
}

/**
 * Generate html code of the wiki page preview
 * @param Wiki2xhtmlRenderer wikiRenderer rendering engine
 * @param string title page title
 * @param string content page content
 * @return string html code of the preview pannel
 */
function claro_disp_wiki_preview( &$wikiRenderer, $title, $content = '' )
{

    $out = "<div id=\"preview\" class=\"wikiTitle\">\n";

    if( $title === '__MainPage__' )
    {
        $title = get_lang("Main page");
    }
    
    $title = "<h1 class=\"wikiTitle\">" . get_lang('Preview :') . "$title</h1>\n";

    $out .= $title;

    $out .= '</div>' . "\n";
    $dialogBox = new DialogBox();
    $dialogBox->warning( '<small>'
        . get_lang("WARNING: this page is a preview. Your modifications to the wiki has not been saved yet ! To save them do not forget to click on the 'save' button at the bottom of the page.")
        . '</small>' );
    $out .= $dialogBox->render(). "\n";
    
    $out .= '<div class="wiki2xhtml">' . "\n";

    if ( $content != '' )
    {
        $out .= $wikiRenderer->render( $content );
    }
    else
    {
        $out .= get_lang("This page is empty, click on 'Edit this page' to add a content");
    }

    $out .= "</div>\n";

    return $out;
}

/**
 * Generate html code ofthe preview panel button bar
 * @param int wikiId ID of the Wiki
 * @param string title page title
 * @param string content page content
 * @param string script callback script url
 * @return string html code of the preview pannel button bar
 */
function claro_disp_wiki_preview_buttons( $wikiId, $title, $content, $script = null )
{
    $script = ( is_null( $script ) ) ? Url::Contextualize($_SERVER['PHP_SELF']) : $script;

    $out = '<div><form method="post" action="' . claro_htmlspecialchars( $script )
        . '" name="previewform" id="previewform">' . "\n"
        ;
    $out .= '<input type="hidden" name="content" value="'
        . claro_htmlspecialchars($content) . '" />' . "\n"
        ;

    $out .= '<input type="hidden" name="title" value="'
        . claro_htmlspecialchars($title)
        . '" />' . "\n"
        ;

    $out .= '<input type="hidden" name="wikiId" value="'
        . (int)$wikiId
        . '" />' . "\n"
        ;
    
    $out .= claro_form_relay_context() . "\n";

    $out .= '<input type="submit" name="action[edit]" value="'
        . get_lang("Edit") . '"/>' . "\n"
        ;

    $out .= '<input type="submit" name="action[save]" value="'
        . get_lang("Save").'" />' . "\n"
        ;

    $location = add_request_variable_to_url( $script, "wikiId", $wikiId );
    $location = add_request_variable_to_url( $location, "title", $title );
    $location = add_request_variable_to_url( $location, "action", "show" );

    $out .= claro_html_button ( claro_htmlspecialchars($location), get_lang("Cancel") );

    $out .= "</form></div>\n";

    return $out;
}

/**
 * Generate html code of Wiki properties edit form
 * @param int wikiId ID of the wiki
 * @param string title wiki tile
 * @param string desc wiki description
 * @param int groupId id of the group the wiki belongs to
 *      (0 for a course wiki)
 * @param array acl wiki access control list
 * @param string script callback script url
 * @return string html code of the wiki properties form
 */
function claro_disp_wiki_properties_form( $wikiId = 0
    , $title ='', $desc = '', $groupId = 0, $acl = null
    , $script = null )
{
    $title = ( $title != '' ) ? $title : get_lang("New Wiki");

    $desc = ( $desc != '' ) ? $desc : get_lang("Enter the description of your wiki here");

    if ( is_null ( $acl ) && $groupId == 0 )
    {
        $acl = WikiAccessControl::defaultCourseWikiACL();
    }
    elseif ( is_null ( $acl ) && $groupId != 0 )
    {
        $acl = WikiAccessControl::defaultGroupWikiACL();
    }

    // process ACL
    $group_read_checked = ( $acl['group_read'] == true ) ? ' checked="checked"' : '';
    $group_edit_checked = ( $acl['group_edit'] == true ) ? ' checked="checked"' : '';
    $group_create_checked = ( $acl['group_create'] == true ) ? ' checked="checked"' : '';
    $course_read_checked = ( $acl['course_read'] == true ) ? ' checked="checked"' : '';
    $course_edit_checked = ( $acl['course_edit'] == true ) ? ' checked="checked"' : '';
    $course_create_checked = ( $acl['course_create'] == true ) ? ' checked="checked"' : '';
    $other_read_checked = ( $acl['other_read'] == true ) ? ' checked="checked"' : '';
    $other_edit_checked = ( $acl['other_edit'] == true ) ? ' checked="checked"' : '';
    $other_create_checked = ( $acl['other_create'] == true ) ? ' checked="checked"' : '';

    $script = ( is_null( $script ) ) ? Url::Contextualize($_SERVER['PHP_SELF']) : $script;

    $form = '<form method="post" id="wikiProperties" action="'.claro_htmlspecialchars($script).'">' . "\n"
        . '<fieldset>' . "\n"
        . '<legend>'.get_lang("Wiki description").'</legend>' . "\n"
        . '<!-- wikiId = 0 if creation, != 0 if edition  -->' . "\n"
        . '<p class="notice">'
        . get_lang('You can choose a title and a description for the wiki :')
        . '</p>' . "\n"
        . '<input type="hidden" name="wikiId" value="'.$wikiId.'" />' . "\n"
        . '<!-- groupId = 0 if course wiki, != 0 if group_wiki  -->' . "\n"
        . '<input type="hidden" name="groupId" value="'.$groupId.'" />' . "\n"
        . '<dl>' . "\n"
        . '<dt><label for="wikiTitle">' . get_lang("Title of the wiki") . '</label></dt>' . "\n"
        . '<dd><input type="text" name="title" id="wikiTitle" size="80" maxlength="254" value="'.claro_htmlspecialchars($title).'" /></dd>' . "\n"
        . '<dt><label for="wikiDesc">'.get_lang("Description of the Wiki").'</label></dt>' . "\n"
        . '<dd><textarea id="wikiDesc" name="desc" cols="80" rows="10">'.$desc.'</textarea></dd>' . "\n"
        . '</dl>'
        . '</fieldset>' . "\n\n"
        
        . '<fieldset id="acl">' . "\n"
        . '<legend>' . get_lang("Access control management") . '</legend>' . "\n"
        . '<p class="notice">'
        . get_lang('You can set access rights for users using the following grid :')
        . '</p>' . "\n"
        . '<table style="text-align: center; padding: 5px;" id="wikiACL">' . "\n"
        . '<tr class="matrixAbs">' . "\n"
        . '<td><!-- empty --></td>' . "\n"
        . '<td>'.get_lang("Read Pages").'</td>' . "\n"
        . '<td>'.get_lang("Edit Pages").'</td>' . "\n"
        . '<td>'.get_lang("Create Pages").'</td>' . "\n"
        . '</tr>' . "\n"
        . '<tr>' . "\n"
        . '<td class="matrixOrd">'.get_lang("Course members").'</td>' . "\n"
        . '<td><input type="checkbox" onclick="updateBoxes(\'course\',\'read\');" id="course_read" name="acl[course_read]"'.$course_read_checked.' /></td>' . "\n"
        . '<td><input type="checkbox" onclick="updateBoxes(\'course\',\'edit\');" id="course_edit" name="acl[course_edit]"'.$course_edit_checked.' /></td>' . "\n"
        . '<td><input type="checkbox" onclick="updateBoxes(\'course\',\'create\');" id="course_create" name="acl[course_create]"'.$course_create_checked.' /></td>' . "\n"
        . '</tr>' . "\n"
        ;

    if ( $groupId != 0 )
    {
        $form .= '<!-- group acl row hidden if groupId == 0, set all to false -->' . "\n"
            . '<tr>' . "\n"
            . '<td class="matrixOrd">'.get_lang("Group members").'</td>' . "\n"
            . '<td><input type="checkbox" onclick="updateBoxes(\'group\',\'read\');" id="group_read" name="acl[group_read]"'.$group_read_checked.' /></td>' . "\n"
            . '<td><input type="checkbox" onclick="updateBoxes(\'group\',\'edit\');" id="group_edit" name="acl[group_edit]"'.$group_edit_checked.' /></td>' . "\n"
            . '<td><input type="checkbox" onclick="updateBoxes(\'group\',\'create\');" id="group_create" name="acl[group_create]"'.$group_create_checked.' /></td>' . "\n"
            . '</tr>' . "\n"
            ;
    }

    $form .= '<tr>' . "\n"
        . '<td class="matrixOrd">'.get_lang("Others (*)").'</td>' . "\n"
        . '<td><input type="checkbox" onclick="updateBoxes(\'other\',\'read\');" id="other_read" name="acl[other_read]"'.$other_read_checked.' /></td>' . "\n"
        . '<td><input type="checkbox" onclick="updateBoxes(\'other\',\'edit\');" id="other_edit" name="acl[other_edit]"'.$other_edit_checked.' /></td>' . "\n"
        . '<td><input type="checkbox" onclick="updateBoxes(\'other\',\'create\');" id="other_create" name="acl[other_create]"'.$other_create_checked.' /></td>' . "\n"
        . '</tr>' . "\n"
        . '</table>' . "\n"
        . '<p class="notice">'
        . get_lang("(*) anonymous users, users who are not members of this course...")
        . '</p>' . "\n"
        . '</fieldset>' . "\n\n"
        ;

    if ( $groupId != 0 )
    {
        $form .= '<input type="hidden" name="gidReq" value="' . $groupId  . '" />' . "\n";
    }
    
    $form .= claro_form_relay_context() ."\n";

    $form .= '<input type="submit" name="action[exEdit]" value="' . get_lang("Ok") . '" />' . "\n"
        . claro_html_button (
            claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?action=list' )),
            get_lang("Cancel") ) . "\n"
        ;

    $form .= '</form>' . "\n"
        ;

    return $form;
}
