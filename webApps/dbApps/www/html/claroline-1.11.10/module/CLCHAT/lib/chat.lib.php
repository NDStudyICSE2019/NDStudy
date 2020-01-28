<?php // $Id: chat.lib.php 415 2008-03-31 13:32:19Z fragile_be $
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 *
 * @version 1.8 $Revision: 415 $
 *
 * @copyright (c) 2001-2006 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package CLCHAT
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Sebastien Piraux <pir@cerdecam.be>
 */

/**
 * Get the number of days equivalent to timestamp
 *
 * @author Sebastien Piraux <pir@cerdecam.be>
 * @return boolean true
 */ 
 
function get_days_from_timestamp($timestamp)
{
    return floor($timestamp/86400);
}


?>