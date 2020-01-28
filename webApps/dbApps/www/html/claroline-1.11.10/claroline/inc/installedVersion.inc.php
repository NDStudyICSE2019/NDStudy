<?php // $Id: installedVersion.inc.php 14691 2014-02-14 07:49:06Z zefredz $

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * Define version number variables and stability and upgrade availability
 *
 * @version     Claroline 1.11 $Revision: 14691 $
 * @copyright   (c) 2001-2013, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     kernel
 * @author      Claro Team <cvs@claroline.net>
 */

$stable = true;
$is_upgrade_available = true;

// version strings : max. 10 chars

$new_version = '1.11.10';
$new_version_branch = '1.11';

/**
 * The API version indicates the last time the API has been altered.
 * 
 * If someone modifies the signature of a function, class, method, or change a (global) variable or 
 * mark a function/method/class/variable has deprecated, the API version MUST be changed.
 * 
 * WARNING : this does not the implementation and internal of methods, functions or class. 
 * The internal version number is given by the revision number in each file.
 */
$GLOBALS['clarolineAPIVersion'] = '1.11.10';
/**
 * The DB version number indicates the last time the database schemas has been altered.
 */
$GLOBALS['clarolineDBVersion'] = '1.10.7';

$requiredPhpVersion = '5.2.0';
$requiredMySqlVersion = '5.0';

if (!$stable)
{
    $new_version = $new_version . '.[unstable:' . date('yzBs') . ']';
}

if (!$is_upgrade_available)
{
    $new_version = $new_version . '[NO UPGRADE]';
}
