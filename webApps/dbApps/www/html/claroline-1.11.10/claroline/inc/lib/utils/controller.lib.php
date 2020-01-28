<?php // $Id: controller.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Front Controller Library
 *
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     utils
 */
 
FromKernel::uses ( 'utils/input.lib', 'display/ob.lib' );

/**
 * Rudimentary controller interface
 */
interface Claro_Controller
{
    /**
     * Execute service
     * @param   Claro_Input_Validator $userInput
     * @param   Claroline $claroline
     * @return  string controller output
     */
    public function handleRequest( $userInput, $claroline );
}

/**
 * Controller that includes a PHP script and run it within it's
 * handleRequest method with script output given by a return statement
 */
class Claro_Controller_Script implements Claro_Controller
{
    protected $scriptPath;

    /**
     * Constructor
     * @param   scriptPath string path to the script to call
     */
    public function __construct( $scriptPath )
    {
        $this->scriptPath = $scriptPath;
    }
    
    /**
     * @see Claro_Controller
     */
    public function handleRequest( $userInput, $claroline )
    {
        if ( ! file_exists( $this->scriptPath ) )
        {
            throw new Exception( "File not found {$this->scriptPath}" );
        }
        else
        {
            $output = require_once $this->scriptPath;
            return $output;
        }
    }
}

/**
 * Controller that includes a PHP script and run it within it's
 * handleRequest method with output buffering tho get the script output
 */
class Claro_Controller_ObScript extends Claro_Controller_Script
{   
    /**
     * @see Claro_Controller_Script
     */ 
    public function handleRequest( $userInput, $claroline )
    {
        if ( ! file_exists( $this->scriptPath ) )
        {
            throw new Exception( "File not found "
                . $this->scriptPath );

            return false;
        }
        else
        {
            claro_ob_start();
            require_once $this->scriptPath;
            $output = claro_ob_get_contents();
            claro_ob_end_clean();
            return $output;
        }
    }
}

class Claro_FrontController_Exception extends Exception{};

/**
 * Rudimentary Front Controller for Claroline applications
 * This class is a Singleton !
 * 
 * Bind a requested controller to the Claro_Controller instance
 * Serve a requested controller
 */
class Claro_FrontController
{
    protected static $_instance = false;
    
    /**
     * Get the Front Controller singleton instance
     */
    public static function getInstance( $initConfig = null )
    {
        if ( ! self::$_instance )
        {
            self::$_instance = new self( $initConfig );
        }
        
        return self::$_instance;
    }
    
    protected $registry = array();
    protected $defaultController = false;
    
    /**
     * Constructor
     * @param   initConfig array initial controllers array
     */
    protected function __construct( $initConfig = null )
    {
        if ( !empty( $initConfig ) && is_array( $initConfig ) )
        {
            $this->registry = $initConfig;
        }
    }
    
    /**
     * Bind the given Claro_Controller to the given requestable key
     * @param   string $request requestable key
     * @param   Claro_Controller $controller
     * @param   bool $overwrite set to true to overwrite an existing binding, default false
     * @throws  Claro_FrontController_Exception if try to overwrite a controller and $overwrite set to false
     * @return  void
     */
    public function bind( $request, Claro_Controller $controller, $overwrite = false )
    {
        if ( $overwrite || ! array_key_exists( $request, $this->registry ) )
        {
            $this->registry[$request] = $controller;
        }
        else
        {
            throw new Claro_FrontController_Exception("Controller already bound for {$request}");
        }
    }
    
    /**
     * Set the given requestable key as default
     * @param   string $request requestable key
     * @throws  Claro_FrontController_Exception if if the requestable key is not bound
     * @return  void
     */
    public function setDefault( $request )
    {
        if ( ! array_key_exists( $request, $this->registry ) )
        {
            throw new Claro_FrontController_Exception("No controller bound to {$request}");
        }
        else
        {
            $this->defaultController = $request;
        }
    }
    
    /**
     * Unbind the given requestable key
     * @param   string $request requestable key
     * @throws  Claro_FrontController_Exception if if the requestable key is not bound
     * @return  Claro_Controller unbound controller
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
            throw new Claro_FrontController_Exception("No controller bound to {$request}");
        }
    }
    
    /**
     * Exceute the controller bound to the requested key
     * @param   string $request requestable key, if null, trys to serve the default
     *  Controller if any
     * @throws  Claro_FrontController_Exception if if the requested key is not bound
     * @return  string controller output
     */
    public function serve( $request = null )
    {
        if ( empty( $request ) && $this->defaultController )
        {
            $request = $this->defaultController;
        }
        
        if ( array_key_exists( $request, $this->registry )  )
        {
            $userInput = Claro_UserInput::getInstance();
            $claroline = Claroline::getInstance();
            $svc = $this->registry[$request];
            return $svc->handleRequest( $userInput, $claroline );
        }
        else
        {
            throw new Claro_FrontController_Exception("No controller bound to {$request}");
        }
    }
}
