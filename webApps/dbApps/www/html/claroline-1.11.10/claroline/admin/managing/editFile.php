<?php // $Id: editFile.php 14587 2013-11-08 12:47:41Z zefredz $

/**
 * CLAROLINE
 *
 * @version $Revision: 14587 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLMANAGE
 * @author      Claro Team <cvs@claroline.net>
 *
 * @todo        use modifiy is use in a cmd request
 */

define('DISP_FILE_LIST', __LINE__);
define('DISP_EDIT_FILE', __LINE__);
define('DISP_VIEW_FILE', __LINE__);

$cidReset=TRUE;

require '../../inc/claro_init_global.inc.php';
require_once get_path('incRepositorySys') . '/lib/fileManage.lib.php';

// Security check
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

$controlMsg = array();

$textZoneList['textzone_top.inc.html'] = array( 'filename' => get_path('rootSys') . 'textzone_top.inc.html',
                         'desc' => get_lang('Welcome text displayed on the homepage'));

$textZoneList['textzone_top.anonymous.inc.html'] = array( 'filename' => get_path('rootSys') . 'platform/textzone/textzone_top.anonymous.inc.html',
                         'desc' => get_lang('Welcome text displayed to anonymous users'));

$textZoneList['textzone_top.authenticated.inc.html'] = array( 'filename' => get_path('rootSys') . 'platform/textzone/textzone_top.authenticated.inc.html',
                         'desc' => get_lang('Welcome text displayed to authenticated users'));

$textZoneList['textzone_right.inc.html'] = array( 'filename' => get_path('rootSys') . 'textzone_right.inc.html',
                         'desc' => get_lang('Text displayed on the right column'));

$textZoneList['course_subscription_locked.inc.html'] = array( 'filename' => get_path('rootSys') . 'platform/textzone/course_subscription_locked.inc.html',
                         'desc' => get_lang('Text displayed if a user tries to enrol in a locked course'));

$textZoneList['course_subscription_locked_by_key.inc.html'] = array( 'filename' => get_path('rootSys') . 'platform/textzone/course_subscription_locked_by_key.inc.html',
                         'desc' => get_lang('Text displayed if a user tries to enrol in a course requiring a key'));

$textZoneList['textzone_inscription.inc.html'] = array( 'filename' => get_path('rootSys') . 'platform/textzone/textzone_inscription.inc.html',
                         'desc' => get_lang('Agreement text displayed before the "Create user account" page'));

$textZoneList['textzone_inscription_form.inc.html'] = array( 'filename' => get_path('rootSys') . 'platform/textzone/textzone_inscription_form.inc.html',
                         'desc' => get_lang('Text displayed on the "Create user account" page'));

$textZoneList['textzone_edit_profile_form.inc.html'] = array( 'filename' => get_path('rootSys') . 'platform/textzone/textzone_edit_profile_form.inc.html',
                         'desc' => get_lang('Text displayed on the "My user account" page'));

$textZoneList['textzone_upload_file_disclaimer.inc.html'] = array( 'filename' => get_path('rootSys') . 'platform/textzone/textzone_upload_file_disclaimer.inc.html',
                         'desc' => get_lang('Message displayed on the file upload pages'));

$textZoneList['textzone_messaging_top.inc.html'] = array( 'filename' => get_path('rootSys') . 'platform/textzone/textzone_messaging_top.inc.html',
                         'desc' => get_lang('Message displayed in the internal messaging'));

$display = DISP_FILE_LIST;

// Get command
$validCmdList = array('rqEdit','exEdit','exView');

$cmd = (isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'],$validCmdList)? $_REQUEST['cmd'] : null);

// input Datas
$fileId = (int) isset($_REQUEST['file']) ? $_REQUEST['file'] : null;
if( !empty($fileId) && ! array_key_exists($fileId,$textZoneList) )
{
    $fileId=null;
    $controlMsg['error'][] = get_lang('Wrong parameters');
};

//If choose a file to modify
//Modify a file

if ( !is_null($fileId) )
{
    if ( $cmd == 'exEdit' )
    {
        $text = isset($_REQUEST['textContent']) ? trim($_REQUEST['textContent']) : null;

        if( !file_exists($textZoneList[$fileId]['filename'])
            && !file_exists( dirname($textZoneList[$fileId]['filename']) ) )
        {
            claro_mkdir(dirname($textZoneList[$fileId]['filename']),CLARO_FILE_PERMISSIONS,true);
        }
        $fp = fopen($textZoneList[$fileId]['filename'], 'w+');
        fwrite($fp,$text);

        $controlMsg['info'][] = get_lang('The changes have been carried out correctly')
        .                       ' <br />'
        .                       '<strong>'
        .                       basename($textZoneList[$fileId]['filename'])
        .                       '</strong>'
        ;

        $display = DISP_FILE_LIST;
    }

    if ( $cmd == 'rqEdit' || $cmd = 'exView' )
    {
        $textContent = (file_exists( $textZoneList[$fileId]['filename'] ) ) ? implode("\n", file($textZoneList[$fileId]['filename']) ) : null;

        if ( $cmd == 'rqEdit' )
        {
            $subtitle = get_lang('Edit : %textZone', array ('%textZone' => $textZoneList[$fileId]['desc']) );
            $display = DISP_EDIT_FILE;
        }
        else
        {
            if ( trim( strip_tags( $textContent,'<img>' ) ) == '' )
            $textContent = '<blockquote>' . "\n"
            .              '<font color="#808080">- <em>' . "\n"
            .              get_lang('No Content') . "\n"
            .              '</em> -</font><br />' . "\n"
            .              '</blockquote>' . "\n"
            ;
            $subtitle = get_lang('Preview : %textZone', array ('%textZone' => $textZoneList[$fileId]['desc']) );
            $display = DISP_VIEW_FILE;
        }
    }
}

// DISPLAY

$nameTools = get_lang('Edit text zones');
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );
$noQUERY_STRING = true;

$out = '';
//display titles

$titles = array('mainTitle'=>$nameTools);
if (isset($subtitle)) $titles['subTitle'] = $subtitle;

$out .= claro_html_tool_title($titles)
.    claro_html_msg_list($controlMsg,1)
;

if ( $display == DISP_EDIT_FILE )
{
    $out .= '<h4>' . basename($textZoneList[$fileId]['filename']) . '</h4>'
    .    '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">' . "\n"
    .    '<input type="hidden" name="file" value="' . claro_htmlspecialchars($fileId) . '" />' . "\n"
    .    '<input type="hidden" name="cmd" value="exEdit" />' . "\n"
    .    claro_html_textarea_editor('textContent', $textContent)
    .    '<p>' . "\n"
    .    '<input type="submit" class="claroButton" value="' . get_lang('Ok') . '" />&nbsp; '
    .    claro_html_button($_SERVER['PHP_SELF'], get_lang('Cancel')) . '</p>' . "\n"
    .    '</form>' . "\n"
    ;
}
elseif( $display == DISP_VIEW_FILE )
{
    $out .= '<br />'
    .    '<h4>' . basename($textZoneList[$fileId]['filename']) . '</h4>'
    .    $textContent
    .    '<br />'
    ;

}

if( $display == DISP_FILE_LIST || $display == DISP_EDIT_FILE || $display == DISP_VIEW_FILE )
{
   $out .= '<p>'
   .    get_lang('Here you can modify the content of the text zones displayed on the platform home page.')
   .    '<br />'
   .    get_lang('See below the files you can edit from this tool.')
   .    '</p>' . "\n"
   .    '<table cellspacing="2" cellpadding="2" border="0" class="claroTable emphaseLine">' . "\n"
   .    '<thead>'
   .    '<tr>' . "\n"
   .    '<th>' . get_lang('Description') . '</th>' . "\n"
   .    '<th>' . get_lang('Edit') . '</th>' . "\n"
   .    '<th>' . get_lang('Preview') . '</th>' . "\n"
   .    '</tr>' . "\n"
   .    '</thead>' . "\n"
   ;

    foreach ( $textZoneList as $idFile => $textZone )
    {
        $out .= '<tr>' . "\n"
        .    '<td >' . ( array_key_exists('desc', $textZone)
                       ? $textZone['desc'] : ''). '</td>' . "\n"
        .    '<td align="center">' . "\n"
        .    '<a href="' . $_SERVER['PHP_SELF'] . '?cmd=rqEdit&amp;file=' . $idFile . '">'
        .    '<img src="' . get_icon_url('edit') . '" alt="' . get_lang('Edit') . '" />' . "\n"
        .    '</a>' . "\n"
        .    '</td>' . "\n"
        .    '<td align="center">' . "\n"
        .    '<a href="' . $_SERVER['PHP_SELF'] . '?cmd=exView&amp;file=' . $idFile . '">'
        .    '<img src="' . get_icon_url('preview') . '" alt="' . get_lang('Preview') . '" />' . "\n"
        .    '</a>' . "\n"
        .    '</td>' . "\n"
        .    '</tr>' . "\n"
        ;
    }

    $out .= '</table>' . "\n"
    .    '<br />' . "\n"
    ;

}

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
