<?php // $Id: phpInfo.php 14587 2013-11-08 12:47:41Z zefredz $

/**
 * CLAROLINE
 *
 * This script present state of
 * - configuration of Claroline, PHP, Mysql, Webserver
 * - credits
 *
 * @version     $Revision: 14587 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author :    Christophe Gesche <moosh@claroline.net>
 * @package     MAINTENANCE
 */

require '../../inc/claro_init_global.inc.php';

require_once dirname( __FILE__ ) . '/lib/phpinfo.lib.php';

// Security check
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

$claroCreditFilePath = get_path('rootSys').'CREDITS.txt';

if( file_exists(get_path('rootSys').'platform/currentVersion.inc.php') )
{
    include (get_path('rootSys').'platform/currentVersion.inc.php');
}

require dirname(__FILE__) .'/../../inc/installedVersion.inc.php';

if( ! claro_is_platform_admin() ) claro_disp_auth_form();



if( ! isset($clarolineVersion) )  $clarolineVersion= 'X';


$nameTools = get_lang('System Info');

ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );

$is_allowedToAdmin = claro_is_platform_admin();

if ($is_allowedToAdmin)
{
    $htmlHeadXtra[] = phpinfo_getStyle();

    $claroline->display->body->appendContent( 
        claro_html_tool_title( 
            array( 'mainTitle'=>$nameTools, 'subTitle'=> get_conf('siteName') ) ) );

    $cmd = array_key_exists( 'cmd', $_REQUEST ) ? $_REQUEST['cmd'] : 'versions';
    $ext = array_key_exists( 'ext', $_REQUEST ) ? $_REQUEST['ext'] : '';
    
    ob_start();

?>

<ul id="navlist">
    <li>
        <a href="<?php echo $_SERVER['PHP_SELF'] ?>?cmd=versions" <?php echo ($cmd == 'versions')? 'class="current"': ''; ?>>
        <?php echo get_lang('Software versions'); ?>
        </a>
    </li>
    <li>
        <a href="<?php echo $_SERVER['PHP_SELF'] ?>?cmd=phpinfo" <?php echo ($cmd == 'phpinfo')? 'class="current"': ''; ?>>
        <?php echo get_lang('PHP configuration'); ?>
        </a>
    </li>
    <li>
        <a href="<?php echo $_SERVER['PHP_SELF'] ?>?cmd=secinfo" <?php echo ($cmd == 'secinfo')? 'class="current"': ''; ?>>
        <?php echo get_lang('PHP security information'); ?>
        </a>
    </li>
    <li>
        <a href="<?php echo $_SERVER['PHP_SELF'] ?>?cmd=extensions" <?php echo ($cmd == 'extensions')? 'class="current"': ''; ?>>
        <?php echo get_lang('Loaded extensions'); ?>
        </a>
    </li>
    <li>
        <a href="<?php echo $_SERVER['PHP_SELF'] ?>?cmd=claroconf" <?php echo ($cmd == 'claroconf')? 'class="current"': ''; ?>>
        <?php echo get_lang('Claroline configuration'); ?>
        </a>
    </li>
</ul>

<div class="phpInfoContents">
<?php

    if( $cmd == 'extensions' )
    {
        $extensions = @get_loaded_extensions();
        echo count($extensions) . ' ' . get_lang('Loaded extensions') . '<br /><br />';
        @sort($extensions);

        foreach($extensions as $extension)
        {
            echo $extension.' &nbsp; <a href="'.$_SERVER['PHP_SELF'].'?cmd=extensions&amp;ext='.$extension.'" >'.get_lang('Function list').'</a><br />'."\n";
            if( $extension == $ext )
            {
                $functions = @get_extension_funcs($ext);
                @sort($functions);
                if( is_array($functions) )
                {
                    echo '<ol>';
                    foreach($functions as $function)
                    {
                        print '<li>' . $function . '</li>';
                    }
                    echo '</ol>';
                }
                else
                {
                    echo get_lang('No function in this extension') . '<br />';
                }
            }
        }
    }
    elseif( $cmd == 'phpinfo' )
    {
        echo '<div class="center">';
        echo phpinfoNoHtml();
        echo '</div>';
    }
    elseif( $cmd == 'secinfo' )
    {
        require_once dirname(__FILE__) .'/../../inc/lib/thirdparty/PhpSecInfo/PhpSecInfo.lib.php';
        phpsecinfo();

    }
    elseif( $cmd == 'claroconf' )
    {
        echo '<div style="background-color: #dfdfff;">';
        highlight_file(claro_get_conf_repository() . 'claro_main.conf.php');
        echo '<hr /></div>';
    }
    else // versions
    {
        ?>
        <table class="claroTable">
            <thead>
                <tr>
                    <th scope="col"><?php echo get_lang('Software') ;?></th>
                    <th scope="col"><?php echo get_lang('Version') ;?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th scope="row"><?php echo get_lang('Claroline Install/Last Major Upgrade Version') ;?></th>
                    <td><?php echo $clarolineVersion ;?></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo get_lang('Claroline Current Version') ;?></th>
                    <td><?php echo $new_version ;?></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo get_lang('Claroline API Version') ;?></th>
                    <td><?php echo $clarolineAPIVersion ;?></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo get_lang('Claroline Database Version') ;?></th>
                    <td><?php echo $clarolineDBVersion ;?></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo get_lang('PHP version (installed/minimum)') ;?></th>
                    <td><?php echo phpversion() . ' / ' . $requiredPhpVersion; ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo get_lang('MySQL (installed/minimum)') ;?></th>
                    <td><?php echo mysql_get_server_info() . ' / ' . $requiredMySqlVersion;?></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo get_lang('Web server') ;?></th>
                    <td><?php echo $_SERVER['SERVER_SOFTWARE'] ;?></td>
                </tr>
            </tbody>
        </table>
        <?php
    }
}
else // is not allowed
{
    echo get_lang('No way');
}

?>
</div>

<?php

$contents = ob_get_contents();
ob_end_clean();

$claroline->display->body->appendContent( $contents );

echo $claroline->display->render();
