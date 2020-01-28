<?php // $Id: config.php 14314 2012-11-07 09:09:19Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
* CLAROLINE
*
* User desktop administration index.
*
* @version      $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
* @license      http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
* @package      DESKTOP
* @author       Claroline team <info@claroline.net>
*
*/

// reset course and groupe
$cidReset = true;
$gidReset = true;
$uidRequired = true;

// load Claroline kernel
require_once dirname(__FILE__) . '/../../claroline/inc/claro_init_global.inc.php';

// users authentified
if( ! claro_is_user_authenticated() ) claro_disp_auth_form();

if( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed') );

require_once dirname(__FILE__) . '/lib/portlet.lib.php';
FromKernel::uses( 'utils/input.lib', 'utils/validator.lib' );

$dialogBox = new DialogBox;

// Get user input data

$userInput = Claro_UserInput::getInstance();

$acceptedCmdList = array(
    'exDown',
    'exUp',
    'exVisible',
    'exInvisible'
);

$userInput->setValidator('cmd',
    new Claro_Validator_AllowedList( $acceptedCmdList ) );

$cmd = $userInput->get( 'cmd', null );
$label = $userInput->get( 'label', null );

// Execute commands

$portletList = new PortletList;

if( $cmd == 'exUp' )
{
    $portletList->moveUp( $label );
}

if( $cmd == 'exDown' )
{
    $portletList->moveDown( $label );
}

if( $cmd == 'exVisible' )
{
    $portletList->setVisible( $label );
}

if( $cmd == 'exInvisible' )
{
    $portletList->setInvisible( $label );
}

$portletList = $portletList->loadAll();

// Display

CssLoader::getInstance()->load('desktop','all');

ClaroBreadCrumbs::getInstance()->prepend(
    get_lang('Administration'),
    get_path('rootAdminWeb') );

$nameTools = get_lang('Manage user desktop');

$output = '';
$output .= claro_html_tool_title($nameTools);

$output .= $dialogBox->render();

$output .= '<table class="claroTable emphaseLine" '
    .   'width="100%" border="0" cellspacing="2">' . "\n"
    . '<thead>' . "\n"
    . '<tr align="center" valign="top">' . "\n"
    . '<th>' . get_lang('Title') . '</th>' . "\n"
    . '<th>' . get_lang('Visibility') . '</th>' . "\n"
    . '<th colspan="2">' . get_lang('Order') . '</th>' . "\n"
    . '</tr>' . "\n"
    . '</thead>' . "\n"
    . '<tbody>' . "\n"
    ;

if( is_array($portletList) && !empty($portletList) )
{
    // only used to hide first up and last down commands
    $portletListSize = count($portletList);
    $i = 0;
    
    foreach ( $portletList as $portlet )
    {
        $i++;
        $output .= "\n"
            . '<tr>' . "\n"
            . '<td>' 
            . claro_htmlspecialchars(get_lang($portlet['name'])) 
            . '</td>' . "\n";
        
        if( $portlet['visibility'] == 'visible' )
        {
            $output .= "\n"
                . '<td align="center">' . "\n"
                . '<a href="' . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF']
                    .   '?cmd=exInvisible&amp;label='
                    .   claro_htmlspecialchars($portlet['label']) ))
                . '">'
                . claro_html_icon('visible')
                . '</a>' . "\n"
                . '</td>' . "\n"
                ;
        }
        else
        {
            $output .= "\n"
                . '<td align="center">' . "\n"
                . '<a href="'
                . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF']
                    .   '?cmd=exVisible&amp;label='
                    .   claro_htmlspecialchars($portlet['label']) ))
                . '">'
                . claro_html_icon('invisible')
                . '</a>' . "\n"
                . '</td>' . "\n"
                ;
        }

        if( $i > 1 )
        {
            $output .= '<td align="center">'
                . '<a href="'
                . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF']
                    .   '?label=' . claro_htmlspecialchars($portlet['label'])
                    .   '&amp;cmd=exUp' ))
                . '">'
                . '<img src="' . get_icon_url('move_up')
                .   '" alt="' . get_lang('Move up') . '" />'
                . '</a>'
                . '</td>' . "\n"
                ;
        }
        else
        {
            $output .= '<td>&nbsp;</td>' . "\n";
        }
        
        if( $i < $portletListSize )
        {
            $output .= '<td align="center">'
                . '<a href="'
                . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF']
                    .   '?label=' . claro_htmlspecialchars($portlet['label'])
                    .   '&amp;cmd=exDown' ))
                . '">'
                . '<img src="' . get_icon_url('move_down')
                .   '" alt="' . get_lang('Move down') . '" />'
                . '</a>'
                . '</td>' . "\n"
                ;
        }
        else
        {
            $output .= '<td>&nbsp;</td>' . "\n";
        }
        
        $output .= '</tr>' . "\n";
        
    }
}
else
{
    $output .= '<tr><td colspan="4">'.get_lang('Empty').'</tr></td>' . "\n";
}

$output .= "\n"
    . '</tbody>' . "\n"
    . '</table>' . "\n"
    ;


$claroline->display->body->appendContent($output);

echo $claroline->display->render();
