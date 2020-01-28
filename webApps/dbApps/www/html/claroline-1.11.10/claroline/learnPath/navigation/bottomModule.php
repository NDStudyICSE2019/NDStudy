<?php // $Id: bottomModule.php 14087 2012-03-21 14:03:31Z zefredz $
/**
 * CLAROLINE 
 *
 * @version 1.11 $Revision: 14087 $
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


Claroline::getDisplay()->body->hideCourseTitleAndTools();

Claroline::getDisplay()->banner->hide();

echo Claroline::getDisplay()->render();

