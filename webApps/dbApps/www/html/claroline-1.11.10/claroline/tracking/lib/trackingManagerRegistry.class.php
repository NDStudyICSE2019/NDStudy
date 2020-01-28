<?php // $Id: trackingManagerRegistry.class.php 13708 2011-10-19 10:46:34Z abourguignon $

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

// vim: expandtab sw=4 ts=4 sts=4 foldmethod=marker:

/**
 * Singleton class used to load all available tracking manager connector
 * and to keep the list of available tracking manager classes found in
 * connectors
 */
class TrackingManagerRegistry
{
    private static $instance = false;

    private $managerList;

    public function __construct()
    {
        $this->managerList = array();
        
        $this->loadAll();
    }

    public function register( $className )
    {
        $this->managerList[] = $className;
    }


    public function loadAll($cidReq = null)
    {
        $this->loadDefaultManager();
        
        $this->loadModuleManager($cidReq);
    }

    private function loadDefaultManager()
    {
        $file = dirname(__FILE__) . '/defaultTrackingManager.class.php';
                
        if( file_exists( $file ) )
        {
            require_once $file;
            if ( claro_debug_mode() ) pushClaroMessage('Tracking : default tracking managers loaded', 'debug');
        }
        else
        {
            if ( claro_debug_mode() ) pushClaroMessage('Tracking : cannot find default tracking managers (file : ' . $file . ')', 'error');
        }
    }
    
    private function loadModuleManager($cidReq = null)
    {
        $toolList = claro_get_main_course_tool_list();
        
        foreach( $toolList as $tool )
        {
            if( !is_null($tool['label']) )
            {
                $file = get_module_path($tool['label']) . '/connector/trackingManager.cnr.php';
                
                if( file_exists( $file ) )
                {
                    require_once $file;
                    if ( claro_debug_mode() ) pushClaroMessage('Tracking : '.$tool['label'].' tracking managers loaded', 'debug');
                }
            }
        }
    }

    public function getManagerList()
    {
        return $this->managerList;
    }
    

    public static function getInstance()
    {
        if ( ! TrackingManagerRegistry::$instance )
        {
            TrackingManagerRegistry::$instance = new TrackingManagerRegistry;
        }

        return TrackingManagerRegistry::$instance;
    }
}
