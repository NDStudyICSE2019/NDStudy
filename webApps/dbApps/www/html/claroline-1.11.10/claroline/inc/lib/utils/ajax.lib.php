<?php // $Id: ajax.lib.php 14187 2012-06-14 11:58:40Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Ajax utility functions and classes
 *
 * @version     $Revision: 14187 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.utils.ajax
 */

require_once dirname(__FILE__) . '/input.lib.php';
require_once dirname(__FILE__) . '/../core/url.lib.php';

/**
 * JSON Response
 */
class Json_Response
{
    /**
     * Message type SUCCESS
     */
    const SUCCESS = 'success';

    /**
     * Message type ERROR
     */
    const ERROR = 'error';
    
    protected $type, $body;

    /**
     * @param mixed $body
     * @param string $type Json_Response::SUCCESS (default)
     *  or Json_Response::ERROR
     */
    public function __construct( $body, $type = self::SUCCESS )
    {
        $this->body = $body;
        $this->type = $type;
    }

    /**
     * Get JSON code for the response
     * @return string JSON code (utf-8 encoded)
     */
    public function toJson()
    {
        $response = $response = array(
            'responseType' => $this->type,
            'responseBody' => $this->body
        );
        
        claro_utf8_encode_array( $response );
        
        return json_encode( $response );
    }
}

/**
 * Json_Error message
 */
class Json_Error extends Json_Response
{
    /**
     * @param string $error error message
     */
    public function __construct( $error )
    {
        parent::__construct( $error, Json_Response::ERROR );
    }
}

/**
 * Json_Exception message
 */
class Json_Exception extends Json_Error
{
    /**
     * Send a JSON-encoded exception to the client
     * @param Exception $e
     */
    public function __construct( $e )
    {
        $errorArr = array(
            'errno' => $e->getCode(),
            'error' => $e->getMessage()
        );
        
        if ( claro_debug_mode() )
        {
            $errorArr['trace'] = $e->getTraceAsString();
            $errorArr['file'] = $e->getFile();
            $errorArr['line'] = $e->getLine();
        }
        
        parent::__construct( $errorArr );
    }
}

/**
 * AJAX Remote Method Request
 * @since Claroline 1.9.5
 */
class Ajax_Request
{
    protected $klass, $method, $params;

    /**
     * @param string $class class name
     * @param string $method invoked method name
     * @param array $params method invokation parameters
     */
    public function __construct( $class, $method, $params = array() )
    {
        $this->klass = $class;
        $this->method = $method;
        $this->params = $params;
    }

    /**
     * Get the name of the invoked class
     * @return string class name
     */
    public function getClass()
    {
        return $this->klass;
    }

    /**
     * Get the name of the invoked method
     * @return string method name
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get the parameters for the invoked method
     * @param  bool $getInputValidator return a Claro_Input_validator instead of an array
     * @return array method parameters or Claro_Input_validator
     */
    public function getParameters( $getInputValidator = false )
    {
        if ( $getInputValidator )
        {
            return new Claro_Input_Validator( 
                new Claro_Input_Array( $this->params ) );
        }
        else
        {
            return $this->params;
        }
    }

    public function  __toString()
    {
        return $this->getClass().'::'.$this->getMethod().'('.implode(',',$this->getParameters()).')';
    }

    /**
     * FActory : build an Ajax Request object from the user input
     * @param Claro_Input $userInput
     * @return Ajax_Request
     */
    public static function getRequest( Claro_Input $userInput )
    {
        if ( $userInput->get( 'parametersType', null ) == 'json' )
        {
            $parameters = json_decode($userInput->get('parameters', array()));
        }
        // default parametersType = array
        else
        {
            $parameters = $userInput->get('parameters', array());
        }

        $request = new self(
            $userInput->getMandatory('class'),
            $userInput->getMandatory('method'),
            $parameters
        );

        return $request;
    }
}

/**
 * Ajax Remote Service interface
 * @since Claroline 1.9.5
 */
interface Ajax_Remote_Service
{
    /**
     * Check if the current user is allowed execute the request
     * @param Ajax_Request $request
     * @return boolean
     */
    public function isMethodInvokationAllowed( Ajax_Request $request );
}

/**
 * Abstract Module Remote Service. Should be extended by module Ajax Remote
 * Service.
 * @since Claroline 1.9.5
 */
abstract class Ajax_Remote_Module_Service implements Ajax_Remote_Service
{
    /**
     * Get the invokable methods for the service
     * @return array of remotely invokable public methods
     */
    abstract public function getInvokableMethods();

    /**
     * Get the invokation name of the class
     * @return string
     */
    abstract public function getInvokableClassName();

    /**
     * Get the label of the module this service belongs to
     * @return string
     */
    abstract public function getModuleLabel();

    /**
     * Register the service
     * @param Ajax_Remote_Service_Broker $broker
     */
    public function register( Ajax_Remote_Service_Broker $broker )
    {
        $broker->register(
            $this->getInvokableClassName(),
            $this,
            $this->getInvokableMethods(),
            true );
    }

    /**
     * Get the Invokation url to use to call the given method from inside the 
     * platform. The url object return is not contextualized !
     * @param string $method optional name of the method to invoke
     * @param array $parameters optional parameters for method invokation
     * @return Url
     */
    public function getInvokationUrl( $method = null, $parameters = null )
    {
        $url = new Url( rtrim( get_platform_base_url (), '/' )
            . '/claroline/backends/ajaxbroker.php' );

        $url = $this->addParametersToInvokationUrl($url, $method, $parameters);
        
        return  $url;
    }
    
    /**
     * Get the Invokation url to use to call the given method from outside the 
     * platform. The url object return is not contextualized !
     * @param string $method optional name of the method to invoke
     * @param array $parameters optional parameters for method invokation
     * @return Url
     */
    public function getExternalInvokationUrl( $method = null, $parameters = null )
    {
        $url = new Url( rtrim( get_path('rootWeb'), '/' )
            . '/claroline/backends/ajaxbroker.php' );

        $url = $this->addParametersToInvokationUrl($url, $method, $parameters);
        
        return  $url;
    }
    
    private function addParametersToInvokationUrl( $url, $method = null, $parameters = null )
    {
        $url->addParam( 'moduleLabel', $this->getModuleLabel() );
        $url->addParam( 'class', $this->getInvokableClassName() );

        if ( ! empty( $method ) )
        {
            if ( ! in_array( $method, $this->getInvokableMethods() ) )
            {
                throw new Exception("Method not in invokable method list");
            }
            else
            {
                $url->addParam( 'method', $method );
            }
        }

        if ( is_array( $parameters ) && !empty( $parameters ) )
        {
            foreach ( $parameters as $key => $value )
            {
                $url->addParam("parameters['{$key}']", $value);
            }
        }

        return $url;
    }

    /**
     * Instanciate and register the AJAX remote service for a given module.
     * The remote service must be defined in the connector/ajaxservice.cnr.php
     * file of the module, extends the Ajax_Remote_Module_Service abstract class
     * and, in addition, it's name must follow the pattern "{$moduleLabel}_AjaxRemoteService"
     * @param string $moduleLabel
     * @return Ajax_Remote_Module_Service
     * @throws Exception if the module does not provide an AJAX remote service
     */
    public static function registerModuleServiceInstance( $moduleLabel )
    {
        $ajaxHandler = self::getModuleServiceInstance ( $moduleLabel );
        $ajaxHandler->register( Claroline::ajaxServiceBroker() );
        
        return $ajaxHandler;
    }
    
    /**
     * Factory method to instanciate the AJAX remote service for a given module.
     * The remote service must be defined in the connector/ajaxservice.cnr.php
     * file of the module, extends the Ajax_Remote_Module_Service abstract class
     * and, in addition, it's name must follow the pattern "{$moduleLabel}_AjaxRemoteService"
     * @param string $moduleLabel
     * @return Ajax_Remote_Module_Service
     * @throws Exception if the module does not provide an AJAX remote service
     */
    public static function getModuleServiceInstance( $moduleLabel )
    {
        $ajaxHandlerPath = get_module_path($moduleLabel) . '/connector/ajaxservice.cnr.php';
        $ajaxHandlerClass = "{$moduleLabel}_AjaxRemoteService";

        if ( file_exists( $ajaxHandlerPath ) )
        {
            require_once $ajaxHandlerPath;

            if ( class_exists( $ajaxHandlerClass ) )
            {
                $ajaxHandler = new $ajaxHandlerClass();

                return $ajaxHandler;
            }
            else
            {
                throw new Exception("No AJAX service found for {$moduleLabel}");
            }
        }
        else
        {
            throw new Exception("No AJAX service found for {$moduleLabel}");
        }
    }
}

/**
 * Ajax remote service broker serves the ajax request to the right ajax remote
 * service and returns the response from the method invokation
 * @since Claroline 1.9.5
 */
class Ajax_Remote_Service_Broker
{
    protected $register = array();

    /**
     * Register an Ajax Remote Service
     * @param string $className
     * @param Ajax_Remote_Service $object
     * @param array $methods or null to allow remote invokation of the public
     *  methods of the service (not recommanded)
     * @param boolean $overwrite set to true to averwrite a previous
     *  registration of the same service
     * @throws Exception if trying to overwrite accidentally (i.e. without
     *  setting $overwrite to true) an already registered service
     */
    public function register( $className, Ajax_Remote_Service $object, $methods = null, $overwrite = false )
    {
        if ( ! isset($this->register[$className]) || $overwrite === true )
        {
            $this->register[$className] = array(
                'object' => $object,
                'methods' => $methods
            );
        }
        else
        {
            throw new Exception ("Service Error : try to overwrite class {$className}");
        }
    }

    /**
     * Handle an Ajax Request
     * @param Ajax_Request $request
     * @return Json_Response or Json_Exception if the invoked class or method
     *  is not found or not callable or if the invokation is not allowed or
     *  throws an exception
     */
    public function handle( Ajax_Request $request )
    {
        try
        {
            if ( isset ($this->register[$request->getClass()]) )
            {
                if (
                    in_array( $request->getMethod(), $this->register[$request->getClass()]['methods'] )
                    || is_null($this->register[$request->getClass()]['methods'])
                )
                {
                    if ( ! $this->register[$request->getClass()]['object']->isMethodInvokationAllowed($request) )
                    {
                        throw new Exception('Remote method invokation not allowed : ' . $request->__toString());
                    }

                    if ( is_callable( array(
                            $this->register[$request->getClass()]['object'],
                            $request->getMethod() )
                        )
                    )
                    {
                        $response = call_user_func(
                            array( $this->register[$request->getClass()]['object'],
                            $request->getMethod() ),
                            $request );

                        return new Json_Response(array(
                            'class' => $request->getClass(),
                            'method' => $request->getMethod(),
                            'parameters' => $request->getParameters(),
                            'response' => $response
                        ));
                    }
                    else
                    {
                        throw new Exception( "Method not callable {$request->getMethod()} in class {$request->getClass()}" );
                    }
                }
                else
                {
                    throw new Exception( "Method not found {$request->getMethod()} in class {$request->getClass()}" );
                }
            }
            else
            {
                throw new Exception( "Class not found {$request->getClass()}" );
            }
        }
        catch ( Exception $e )
        {
            return new Json_Exception($e);
        }
    }
}
