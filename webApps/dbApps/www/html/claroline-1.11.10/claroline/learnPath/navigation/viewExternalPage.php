<?php // $Id: viewExternalPage.php 14385 2013-02-08 12:27:23Z zefredz $
/**
 * CLAROLINE 
 *
 * @version 1.11 $Revision: 14385 $
 *
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @author Piraux Sebastien <pir@cerdecam.be>
 * @author Lederer Guillaume <led@cerdecam.be>
 *
 * @package CLLNP
 * @subpackage navigation
 *
 * DESCRIPTION:
 * ************
 * This script creates the bottom frame needed when we browse a module that needs to use frame
 * This appens when the module is SCORM (@link http://www.adlnet.org )or made by the user with his own html pages.
 *
 */
require '../../inc/claro_init_global.inc.php';
// header

// Turn off session lost
$warnSessionLost = false ;

$userInput = Claro_UserInput::getInstance();
$url = $userInput->get( 'url', null );


Claroline::getDisplay()->body->hideCourseTitleAndTools();

Claroline::getDisplay()->banner->hide();
Claroline::getDisplay()->footer->hide();

if ( $url )
{
    Claroline::getDisplay()->body->appendContent(
        get_lang('Page opened in new window or tab. <a target="_blank" href="%url">Click here if it\'s not the case.</a>',
            array('%url'=>  claro_htmlspecialchars ($url))));
}
else
{
    Claroline::getDisplay()->body->appendContent(get_lang('Missing ressource'));
}

echo Claroline::getDisplay()->render();
