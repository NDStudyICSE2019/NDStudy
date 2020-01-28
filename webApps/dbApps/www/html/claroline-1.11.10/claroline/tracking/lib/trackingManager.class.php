<?php // $Id: trackingManager.class.php 13708 2011-10-19 10:46:34Z abourguignon $

/**
 * CLAROLINE
 *
 * @version     $Revision: 13708 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLTRACK
 * @author      Claro Team <cvs@claroline.net>
 * @author      Sebastien Piraux <pir@cerdecam.be>
 */

/**
 * This class defines main methods used in the tracking manager
 *
 * @abstract
 */
abstract class TrackingManager
{
    /**
     * Constructor
     * @param int $courseId id of course we want to manage tracking
     */
    public function __contruct($courseId) {}

    /**
     * Delete all tracking about the related to the module that extends this TrackingManager
     * @return boolean result of delete query
     * @abstract
     */
    abstract public function deleteAll();
    
    /**
     * Delete all tracking prior to date $date
     * about the related to the module that extends this TrackingManager
     * @param timestamp $date
     * @return boolean result of delete query
     * @abstract
     */
    abstract public function deleteBefore($date);
    
    /**
     * Delete all tracking about a user
     * in the related to the module that extends this TrackingManager
     * A date can be specified to delete only events prior to this date
     * @param int $userId  user id
     * @param timestamp $date
     * @return boolean result of delete query
     * @abstract
     */
    abstract public function deleteForUser($userId,$date = null);
    
}
