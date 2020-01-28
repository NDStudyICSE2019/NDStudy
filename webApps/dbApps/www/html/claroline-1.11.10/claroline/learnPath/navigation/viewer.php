<?php // $Id: viewer.php 14314 2012-11-07 09:09:19Z zefredz $
/**
 * CLAROLINE
 *
 * @version version 1.11
 *
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @author claro team <cvs@claroline.net>
 * @author Piraux Sebastien <pir@cerdecam.be>
 * @author Lederer Guillaume <led@cerdecam.be>
 *
 * @package CLLNP
 * @subpackage navigation
 *
 */

require '../../inc/claro_init_global.inc.php';

if ( ! claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form(true);

// the following constant defines the default display of the learning path browser
// 0 : display only table of content and content
// 1 : display claroline header and footer and table of content, and content
define ( 'USE_FRAMES' , 1 );

/*
if(isset ($_GET['path_id']) && $_GET['path_id'] != '')
    $_SESSION['path_id'] = $_GET['path_id'];

if(isset ($_GET['viewModule_id']) && $_GET['viewModule_id'] != '')
    $_SESSION['module_id'] = $_GET['viewModule_id'];
*/
$nameTools = get_lang('Learning path');

if (!isset($titlePage)) $titlePage = '';

if(!empty($nameTools))
{
    $titlePage .= $nameTools.' - ';
}

if(claro_get_current_course_data('officialCode') != '' )
{
    $titlePage .= claro_get_current_course_data('officialCode') . ' - ';
}

$titlePage .= get_conf('siteName');

// set charset as claro_header should do but we cannot include it here
header('Content-Type: text/html; charset=' . get_locale('charset'));
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"
   "http://www.w3.org/TR/html4/frameset.dtd">
<html>

    <head>
        <title><?php echo $titlePage; ?></title>
    </head>
<?php
if ( !isset($_GET['frames']) )
{
    // choose default display
    // default display is without frames
    $displayFrames = USE_FRAMES;
}
else
{
    $displayFrames = $_REQUEST['frames'];
}

if( $displayFrames )
{
?>
    <frameset border="0" rows="150,*,70" frameborder="no">
        <frame src="<?php echo claro_htmlspecialchars(Url::Contextualize('topModule.php'));?>" name="headerFrame" />
        <frame src="<?php echo claro_htmlspecialchars(Url::Contextualize('startModule.php'));?>" name="mainFrame" />
        <frame src="<?php echo claro_htmlspecialchars(Url::Contextualize('bottomModule.php'));?>" name="bottomFrame" />
    </frameset>
<?php
}
else
{
?>
    <frameset cols="*" border="0">
        <frame src="<?php echo claro_htmlspecialchars(Url::Contextualize('startModule.php'));?>" name="mainFrame" />
    </frameset>
<?php
}
?>
    <noframes>
        <body>
            <?php echo get_lang('Your browser cannot see frames.') ?>
            <br />
            <a href="<?php echo claro_htmlspecialchars(Url::Contextualize('../module.php'));?>"><?php echo get_lang('Back') ?></a>
        </body>
    </noframes>
</html>