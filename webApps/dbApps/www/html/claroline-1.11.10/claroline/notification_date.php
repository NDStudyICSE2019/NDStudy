<?php // $Id: index.php 9923 2008-04-10 15:03:09Z fragile_be $

/**
 * CLAROLINE
 *
 * @version     $Revision: 11767 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 * @since       1.10
 */

require_once dirname(__FILE__) . '/inc/claro_init_global.inc.php';

if ( claro_is_user_authenticated() )
{

    require_once get_path('incRepositorySys') . '/lib/form.lib.php';
    
    $dialogBox = new DialogBox();
    
    $display_form = true;
    
    if (((isset($_REQUEST['fday']) && is_numeric($_REQUEST['fday'])))
        && ((isset($_REQUEST['fmonth']) && is_numeric($_REQUEST['fmonth'])))
        && ((isset($_REQUEST['fyear']) && is_numeric($_REQUEST['fyear']))))
    {
        $_SESSION['last_action'] = $_REQUEST['fyear'] . '-' . $_REQUEST['fmonth'] . '-' . $_REQUEST['fday'] . ' 00:00:00';

        if ( claro_get_current_course_id() != '' )
        {
            claro_redirect(Url::Contextualize(get_path('clarolineRepositoryWeb') . '/course/index.php'));
        }
        else
        {
            claro_redirect(get_path('clarolineRepositoryWeb').'/index.php');
        }
    }
    
    /**
     *     DISPLAY SECTION
     *
     */
    
    $output = '';
    
    $output .= claro_html_title(get_lang('Change notification date'),2);
    $output .= $dialogBox->render();
    
    if ($display_form)
    {
        $output .= '<form method="get" action="' . claro_htmlspecialchars( $_SERVER['PHP_SELF'] ) . '">'
        . claro_form_relay_context()
        . '<fieldset>' . "\n"
        . '<dd>'
        . claro_html_date_form('fday', 'fmonth', 'fyear', 0 , 'long' ) . ' '
        . '</dd>' . "\n"
            . '</dl>'
        . '</fieldset>'
        . '<input type="submit" class="claroButton" name="notificationDate" value="' . get_lang('Ok') . '" />' . "\n"
        . '</form>' . "\n";
    }
    Claroline::getDisplay()->body->appendContent( $output );
    
    echo Claroline::getDisplay()->render();

}
else claro_redirect('index.php');
