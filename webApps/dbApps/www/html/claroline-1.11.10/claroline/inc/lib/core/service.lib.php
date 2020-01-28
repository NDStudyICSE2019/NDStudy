<?php // $Id: service.lib.php 14184 2012-06-13 11:56:52Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Service architecture, provides
 *  - Service classes
 *  - Dispatcher class
 *
 * @version     Claroline 1.11 $Revision: 14184 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.core
 * @deprecated  since 1.9, use utils/controller.lib instead
 */

// FromKernel::uses ( 'core/error.lib' );

/**
 * Abstract Service
 * @abstract
 */
abstract class AbstractService // extends ErrorHandling
{
    private $output = '';

    /**
     * Set service execution output
     * @access  protected
     * @param   output string service output string
     */
    public function setOutput( $output )
    {
        $this->output = $output;
    }

    /**
     * Get service execution output
     * @param   string service output string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Execute service
     * @abstract
     */
    abstract public function run();
}

// Common Services

/**
 * Script Service
 * Execute a given script that uses set/getOutput methods
 * to communicate execution result to calling Service object
 */
class ScriptService extends AbstractService
{
    private $scriptPath;

    /**
     * Constructor
     * @param   scriptPath string path to the script to call
     */
    public function __construct( $scriptPath )
    {
        $this->scriptPath = $scriptPath;
    }

    public function run()
    {
        if ( ! file_exists( $this->scriptPath ) )
        {
            throw new Exception( "File not found "
                . $this->scriptPath );

            return false;
        }
        else
        {
            $claroline = Claroline::getInstance();
            require_once $this->scriptPath;

            return true;
        }
    }
}

/**
 * Output Buffering Script Service
 * Execute a given script by using ob_* functions
 * to retreive execution result from called script
 */
class ObScriptService extends ScriptService
{
    public function run()
    {
        if ( ! file_exists( $this->scriptPath ) )
        {
            throw new Exception( "File not found "
                . $this->scriptPath );

            return false;
        }
        else
        {
            $claroline = Claroline::getInstance();
            
            ob_start();
            require_once $this->scriptPath;
            $output = ob_get_contents();
            ob_end_clean();

            $this->setOutput( $output );

            return true;
        }
    }
}

/**
 * Service dispatcher
 * Receive a requested service identifier and executes the corresponding
 * service. Dispatcher is like a routing table.
 */
class Dispatcher
{
    const DISPATCHER_DEFAULT_SERVICE = 'DISPATCHER_DEFAULT_SERVICE';
    
    private static $_instance = false;
    
    /**
     * Bind table
     * @access  private
     */
    private $registry;
    
    /**
     * Constructor
     * @param   initConfig array initial services array
     */
    private function __construct( $initConfig = null )
    {
        if ( !empty( $initConfig ) && is_array( $initConfig ) )
        {
            $this->registry = $initConfig;
        }
        else
        {
            $this->registry = array();
        }
    }
    
    public static function getInstance( $initConfig = null )
    {
        if ( ! self::$_instance )
        {
            self::$_instance = new Dispatcher( $initConfig );
        }
        
        return self::$_instance;
    }
    
    /**
     * Bind a service to a service identifier
     * @param   request string service identifier
     * @param   service Service service object
     * @param   overwrite boolean overwrites an existing entry
     *  with the same identifier
     * @return  boolean true if binding succeeds, else returns false
     */
    public function bind( $request, $service, $overwrite = false )
    {
        if ( $overwrite || ! array_key_exists( $request, $this->registry ) )
        {
            $this->registry[$request] = $service;
            return true;
        }
        else
        {
            return false;
        }
    }
    
    /**
     * Bind the default service
     * @param   service Service service object
     * @return  boolean true if binding succeeds, else returns false
     */
    public function setDefault( $service )
    {
        return $this->bind( self::DISPATCHER_DEFAULT_SERVICE, $service, true );
    }
    
    /**
     * Unbind the service corresponding to the given service identifier
     * @param   request string service identifier
     * @return  mixed Service if unbinding succeeds, else returns false
     */
    public function unbind( $request )
    {
        if ( array_key_exists( $request, $this->registry ) )
        {
            $tmp = $this->registry[$request];
            unset( $this->registry[$request] );
            return $tmp;
        }
        else
        {
            return false;
        }
    }
    
    /**
     * Rebind a service to a service identifier, same as Dispatcher::bind()
     * with $overwrite set to true
     * @param   request string service identifier
     * @param   service Service service object
     * @return  boolean true if binding succeeds, else returns false
     */
    public function rebind( $request, $service )
    {
        return $this->bind( $request, $service, true );
    }
    
    /**
     * Run the service corresponding to the given identifier
     * @param   request string service identifier
     * @return  mixed Service object if succeeds, false else
     */
    public function serve( $request )
    {
        if ( array_key_exists( $request, $this->registry )  )
        {
            $svc = $this->registry[$request];
            $svc->run();
            
            return $svc;
        }
        else
        {
            throw new Exception( "Unknown page $request" );
        }
    }
    
    /**
     * Run the default service
     * @return  mixed Service object if succeeds, false else
     */
    public function serveDefault()
    {
        return $this->serve( self::DISPATCHER_DEFAULT_SERVICE );
    }
}
