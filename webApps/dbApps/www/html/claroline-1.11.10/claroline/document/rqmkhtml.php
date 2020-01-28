<?php // $Id: rqmkhtml.php 14314 2012-11-07 09:09:19Z zefredz $


require '../inc/claro_init_global.inc.php';
require_once get_path('incRepositorySys') . '/lib/fileManage.lib.php';
$_course = claro_get_current_course_data();

function is_parent_path($parentPath, $childPath)
{
    // convert the path for operating system harmonize
    $parentPath = realpath($parentPath) ;
    $childPath = realpath($parentPath . $childPath ) ;

    if ( $childPath !== false )
    {
        // verify if the file exists and if the file is under parent path
        return preg_match('|^'.preg_quote($parentPath).'|', $childPath);
    }
    else
    {
        return false;
    }
}

if (claro_is_in_a_group() && claro_is_group_allowed())
{
    $_group = claro_get_current_group_data();
    $courseDir         = claro_get_course_path() .'/group/'.claro_get_current_group_data('directory');
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Documents and Links'), 'document.php' );
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Groups'), '../group/group.php' );
}
else
{
    $courseDir   = claro_get_course_path() .'/document';
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Documents and Links'), 'document.php' );
}

$noPHP_SELF = true;

$baseWorkDir = get_path('coursesRepositorySys') . $courseDir;

if( !empty($_REQUEST['cmd']) ) $cmd = $_REQUEST['cmd'];
else                           $cmd = null;

if( !empty($_REQUEST ['cwd']) ) $cwd = $_REQUEST ['cwd'];
else                            $cwd = '';

if ( isset($_REQUEST['file']) /*&& is_download_url_encoded($_REQUEST['file']) */ )
{
    $file = download_url_decode( $_REQUEST['file'] );
}
else
{
    $file = '';
}

$nameTools = get_lang('Create/edit document');

$out = '';

$out .= claro_html_tool_title(array('mainTitle' => get_lang('Documents and Links'), 'subTitle' => get_lang('Create/edit document')));

/*========================================================================
CREATE DOCUMENT
========================================================================*/

if ($cmd ==  'rqMkHtml' )
{
    $out .= '<form action="' . claro_htmlspecialchars(get_module_entry_url('CLDOC')) .'" method="post">' . "\n"
    . claro_form_relay_context() . "\n"
    . '<input type="hidden" name="cmd" value="exMkHtml" />' . "\n"
    . '<input type="hidden" name="cwd" value="' . claro_htmlspecialchars(strip_tags($cwd)) . '" />' . "\n"
    . '<p>' . "\n"
    . '<b>' . get_lang('Document name') . '&nbsp;: </b><br />' . "\n"
    . '<input type="text" name="fileName" size="80" />' . "\n"
    . '</p>' . "\n"
    . '<p>' . "\n"
    . '<b>' . get_lang('Document content') . '&nbsp;: </b>' . "\n"
    ;
    if (!empty($_REQUEST['htmlContent'])) $content = $_REQUEST['htmlContent']; else $content = "";

    $out .= claro_html_textarea_editor('htmlContent',$content);

    // the second argument _REQUEST['htmlContent'] for the case when we have to
    // get to the editor because of an error at creation
    // (eg forgot to give a file name)
    $out .= '</p>'  . "\n"
    . '<p>' . "\n"
    . '<input type="submit" value="'. get_lang('Ok') .'" />&nbsp;'
    . claro_html_button(claro_htmlspecialchars(Url::Contextualize('./document.php?cmd=exChDir&amp;file='.strip_tags($cwd))), get_lang('Cancel'))
    . '</p>' . "\n"
    . '</form>' . "\n"
    ;
}
elseif($cmd == "rqEditHtml" && !empty($file) )
{
    if ( is_parent_path($baseWorkDir, $file ) )
    {
        $fileContent = implode("\n",file($baseWorkDir.$file));
    }
    else
    {
        claro_die('WRONG PATH');
    }


    $fileContent = get_html_body_content($fileContent);
    $out .= '<form action="' . claro_htmlspecialchars(get_module_entry_url('CLDOC')) .'" method="post">' . "\n"
    . claro_form_relay_context() . "\n"
    . '<input type="hidden" name="cmd" value="exEditHtml" />' . "\n"
    . '<input type="hidden" name="file" value="' . claro_htmlspecialchars(base64_encode($file)) .'" />' . "\n"
    . '<b>'. get_lang('Document name') .' : </b><br />' . "\n"
    . $file . "\n"
    . '</p>' . "\n"
    . '<p>' . "\n"
    . '<b>'. get_lang('Document content') .' : </b>' . "\n"
    . claro_html_textarea_editor('htmlContent', $fileContent ) . "\n"
    . '</p>'
    . '<p>'
    . '<input type="submit" value="' . get_lang('Ok') .'" />&nbsp;' . "\n"
    . claro_html_button(claro_htmlspecialchars(Url::Contextualize('./document.php?cmd=rqEdit&file='.base64_encode($file))), get_lang('Cancel')) . "\n"
    . '</p>' . "\n"
    . '</form>' . "\n"
    ;
    
}

$out .= '<br />' . "\n"
. '<br />' . "\n"
;

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
