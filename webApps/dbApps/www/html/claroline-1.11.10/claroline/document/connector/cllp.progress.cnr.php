<?php // $Id: cllp.progress.cnr.php 14182 2012-06-13 11:20:54Z zefredz $
/**
 * CLAROLINE
 *
 * @version 0.1 $Revision: 14182 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package CLDOC
 *
 * @author Sebastien Piraux
 *
 */

$tlabelReq = 'CLDOC';

require_once dirname( __FILE__ ) . '/../../../claroline/inc/claro_init_global.inc.php';

if ( !claro_is_tool_allowed() )
{
    if ( claro_is_in_a_course() )
    {
        claro_die( get_lang( "Not allowed" ) );
    }
    else
    {
        claro_disp_auth_form( true );
    }
}


$inLP = (claro_called_from() == 'CLLP')? true : false;

if( !$inLP )
{
   claro_redirect('../document.php');
}


$jsloader = JavascriptLoader::getInstance();
$jsloader->load('jquery');
// load functions required to be able to discuss with API
$jsloader->loadFromModule('CLLP', 'connector13');
$jsloader->loadFromModule('CLLP', 'scormtime');

$jsloader->load('cllp.cnr');


$claroline->setDisplayType( Claroline::FRAME );

$out = '';

$out .= '<div>' . "\n"
.    '<form method="get" action="#" id="progressForm">' . "\n"
.    get_lang('Progress') . ' : ' . "\n"
.    '<input type="radio" name="progress" id="none" class="progressRadio" value="0" checked="checked" />' . "\n"
.    '<label for="none">0%</label>' . "\n"

.    '<input type="radio" name="progress" id="low" class="progressRadio" value="25" />' . "\n"
.    '<label for="low">25%</label>' . "\n"

.    '<input type="radio" name="progress" id="medium" class="progressRadio" value="50" />' . "\n"
.    '<label for="medium">50%</label>' . "\n"

.    '<input type="radio" name="progress" id="high" class="progressRadio" value="75" />' . "\n"
.    '<label for="high">75%</label>' . "\n"

.    '<input type="radio" name="progress" id="full" class="progressRadio" value="100" />' . "\n"
.    '<label for="full">100%</label>' . "\n"

//.    '<input type="button" value="'.get_lang('Done').'" id="progressDone" />' . "\n"
.    '</form>' . "\n"
.    '</div>' . "\n";

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
