<?php // $Id: claroline.lib.php 14182 2012-06-13 11:20:54Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Singleton class to represent the Claroline platform. This is a utility
 * class providing classes and methods to deal with the kernel and the page
 * display.
 *
 * @version     $Revision: 14182 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.core
 */

require_once dirname(__FILE__) . '/../core/debug.lib.php';
require_once dirname(__FILE__) . '/../core/console.lib.php';
require_once dirname(__FILE__) . '/../core/event.lib.php';
require_once dirname(__FILE__) . '/../core/log.lib.php';
require_once dirname(__FILE__) . '/../core/url.lib.php';
require_once dirname(__FILE__) . '/../core/notify.lib.php';
require_once dirname(__FILE__) . '/../display/display.lib.php';
require_once dirname(__FILE__) . '/../database/database.lib.php';
require_once dirname(__FILE__) . '/../utils/ajax.lib.php';

/**
 * Main Claroline class containing references to Claroline kernel objects
 * This class is a Singleton
 */
class Claroline
{
    // Display type constants
    const PAGE      = 'CL_PAGE';
    const FRAMESET  = 'CL_FRAMESET';
    const POPUP     = 'CL_POPUP';
    const FRAME     = 'CL_FRAME';
    
    // Kernel objects
    /**
     * @var EventManager
     */
    public $eventManager;
    /**
     * @var ClaroNotification
     */
    public $notification;
    /**
     * @var ClaroNotifier
     */
    public $notifier;
    // Display object
    public $display;
    /**
     * @var Logger
     */
    public $logger;
    
    protected $moduleLabelStack;
    
    // this class is a singleton, use static method getInstance()
    private function __construct()
    {
        try
        {
            // initialize the event manager and notification classes
            $this->eventManager = EventManager::getInstance();
            $this->notification = ClaroNotification::getInstance();
            $this->notifier = ClaroNotifier::getInstance();
            
            // initialize logger
            $this->logger = new Logger();
            
            $this->moduleLabelStack = array();
            
            if ( isset($GLOBALS['tlabelReq']) )
            {
                $this->pushModuleLabel($GLOBALS['tlabelReq']);
                
                pushClaroMessage("Set current module to {$GLOBALS['tlabelReq']}", 'debug');
            }
        }
        catch ( Exception $e )
        {
            die( $e );
        }
    }

    /**
     * Add a label at the top of the module stack
     * @param string $label
     */
    public function pushModuleLabel( $label )
    {
        array_push( $this->moduleLabelStack, $label );
    }

    /**
     * Remove the modulke label at the top of the module stack
     */
    public function popModuleLabel()
    {
        array_pop( $this->moduleLabelStack );
    }

    /**
     * Get the label of the current module
     * @return string or false
     */
    public function currentModuleLabel()
    {
        if ( empty( $this->moduleLabelStack ) )
        {
            return false;
        }
        else
        {
           return $this->moduleLabelStack[count($this->moduleLabelStack)-1];
        }
    }
    
    /**
     * Set the type of display to use
     * @param   string type, display type could be
     *  Claroline::PAGE         a standard page with header, banner, body and footer
     *  Claroline::FRAMESET     a frameset with header and frames
     *  Claroline::POPUP        a popup-embedded page
     *  Claroline::FRAME        a frame-embedded page
     *  default value : Claroline::PAGE
     */
    public function setDisplayType( $type = self::PAGE )
    {
        switch ( $type )
        {
            case self::PAGE:
                $this->display = new ClaroPage;
                break;
            case self::POPUP:
                $this->display = new ClaroPage;
                $this->display->popupMode();
                break;
            case self::FRAME:
                $this->display = new ClaroPage;
                $this->display->frameMode();
                break;
            case self::FRAMESET:
                $this->display = new ClaroFramesetPage;
                break;
            default:
                throw new Exception( 'Invalid display type' );
        }
    }
    
    // Singleton instance
    private static $instance = false; // this class is a singleton
    
    /**
     * Returns the singleton instance of the Claroline object
     * @return  Claroline singleton instance
     */
    public static function getInstance()
    {
        if ( ! self::$instance )
        {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Get the current display object
     * @return Display which can be a ClaroPage or ClaroFramesetPage according
     *  to the display type
     */
    public static function getDisplay()
    {
        return self::getInstance()->display;
    }

    /**
     * Helper to initialize the display
     * @param string $displayType
     */
    public static function initDisplay( $displayType = self::PAGE )
    {
        self::getInstance()->setDisplayType( $displayType );
    }

    /**
     * Helper to log a message
     * @param string $type
     * @param string $data
     */
    public static function log( $type, $data )
    {
        self::getInstance()->logger->log($type, $data);
    }
    
    protected static $db = false;
    // Database link
    protected static $database = false;

    /**
     * Get the current database connection object
     * @return Claroline_Database_Connection
     */
    public static function getDatabase()
    {
        if ( ! self::$database )
        {
            // self::initMainDatabase();
            self::$database = new Claroline_Database_Connection();//self::$db);
            self::$database->connect();
            
            // the following options are for campus where only one language is used
            // or multiple languages but with compatible charsets (for example 
            // english (latin1) and portuguese (latin2). @see mysql documentation 
            // for allowed charsets
            
            $charset = get_conf( 'mysqlSetNames' );
            
            if ( !empty( $charset ) )
            {
                self::$database->setCharset($charset);
            }
        }
        
        return self::$database;
    }

    /**
     * Initialize the database for claro_sql_* legacy code
     * @return void
     * @throws Exception when the database connection cannot be created
     * @deprecated since 1.10
     */
    public static function initMainDatabase()
    {
        if ( !self::$db )
        {
            self::$db = self::getDatabase()->getDbLink();
        }
        
        if ($GLOBALS['statsDbName'] == '')
        {
            $GLOBALS['statsDbName'] = get_conf('mainDbName');
        }
    }

    protected static $_ajaxServiceBroker = false;

    /**
     * Get kernel Ajax Service Broker instance
     * @return Ajax_Remote_Service_Broker
     * @since Claroline 1.9.5
     */
    public static function ajaxServiceBroker()
    {
        if ( ! self::$_ajaxServiceBroker )
        {
            self::$_ajaxServiceBroker = new Ajax_Remote_Service_Broker();
        }

        return self::$_ajaxServiceBroker;
    }
}
