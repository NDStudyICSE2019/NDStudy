<?php // $Id: courselist.lib.php 13685 2011-10-14 12:42:41Z zefredz $

/**
 * CLAROLINE
 *
 * @version     $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLTRACK
 * @author      Claro Team <cvs@claroline.net>
 * @author      Sebastien Piraux <pir@cerdecam.be>
 */

// vim: expandtab sw=4 ts=4 sts=4 foldmethod=marker:

/**
 * Singleton class used to load all available tracking renderer connector
 * and to keep the list of available tracking rendering classes found in
 * connectors
 */
class TrackingRendererRegistry
{
    const PLATFORM = 'platform';
    const COURSE = 'course';
    
    private static $instance = false;

    private $courseId;
    private $courseRendererList;
    private $userRendererList;
    private $userPlatformRendererList;

    /**
     * Construtor
     *
     */
    public function __construct($courseId = null)
    {
        $this->courseId = $courseId;
        $this->courseRendererList = array();
        $this->userRendererList = array();

        $this->loadAll();
    }

    /**
     * Add $className to the list of course tracking renderers
     *
     * @param string $className
     */
    public function registerCourse( $className )
    {
        $this->courseRendererList[] = $className;
    }
    
    /**
     * Add $className to the list of user tracking renderers
     *
     * @param string $className
     */
    public function registerUser( $className, $context = self::COURSE )
    {
        if( $context != self::COURSE && $context != self::PLATFORM )
        {
            $context = self::COURSE;
        }
        
        $this->userRendererList[$context][] = $className;
    }
    
    /**
     * Load all tracking renderers
     *
     * @param string $cidReq
     */
    public function loadAll()
    {
        $this->loadDefaultRenderer();
        
        $this->loadModuleRenderer();
    }

    /**
     * Load the default tracking renderers.  These are the renderers not related to
     * any module such as course access and tool access
     *
     */
    private function loadDefaultRenderer()
    {
        $file = dirname(__FILE__) . '/defaultTrackingRenderer.class.php';
                
        if( file_exists( $file ) )
        {
            require_once $file;
            if ( claro_debug_mode() ) pushClaroMessage('Tracking : default tracking renderers loaded', 'debug');
        }
        else
        {
            if ( claro_debug_mode() ) pushClaroMessage('Tracking : cannot find default tracking renderers (file : ' . $file . ')', 'error');
        }
    }
    
    /**
     * Search in all activated modules
     *
     * @param string $cidReq
     */
    private function loadModuleRenderer()
    {
        if( !is_null($this->courseId) )
        {
            $profileId = claro_get_current_user_profile_id_in_course($this->courseId);
            $toolList = claro_get_course_tool_list($this->courseId, $profileId);
        }
        else
        {
            
            $toolList = claro_get_main_course_tool_list();
        }
        
        
        foreach( $toolList as $tool )
        {
            if( !is_null($tool['label']) )
            {
                $file = get_module_path($tool['label']) . '/connector/tracking.cnr.php';
                
                if( file_exists( $file ) )
                {
                    require_once $file;
                    if ( claro_debug_mode() ) pushClaroMessage('Tracking : '.$tool['label'].' tracking renderers loaded', 'debug');
                }
            }
        }
    }

    /**
     * Returns array of available course tracking renderers
     *
     * @return array list of classnames
     */
    public function getCourseRendererList()
    {
        return $this->courseRendererList;
    }
    
    /**
     * Returns array of available user tracking renderers
     *
     * @return array list of class names
     */
    public function getUserRendererList( $context = self::COURSE )
    {
        if( $context != self::COURSE && $context != self::PLATFORM )
        {
            $context = self::COURSE;
        }
        return $this->userRendererList[$context];
    }

    /**
     * Returns array of available user tracking renderers
     *
     * @return array list of class names
     */
    public function getUserPlatformRendererList()
    {
        return $this->userRendererList;
    }
    
    /**
     * singleton method
     *
     * @return instance
     */
    public static function getInstance($courseId)
    {
        if ( ! TrackingRendererRegistry::$instance )
        {
            TrackingRendererRegistry::$instance = new TrackingRendererRegistry($courseId);
        }

        return TrackingRendererRegistry::$instance;
    }
}
