<?php // $Id: input.lib.php 14130 2012-04-27 12:38:56Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * User input library
 * Replacement for $_GET and $_POST
 * Do not handle $_COOKIES !
 *
 * @version     1.11 $Revision: 14130 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     utils
 */
 
FromKernel::uses ( 'utils/validator.lib' );

/**
 * Data Input Exception, thrown when an input value does not match
 * a filter or is missing
 */
class Claro_Input_Exception extends Exception{};

/**
 * Defines the required methods for a data input object
 */
interface Claro_Input
{
    /**
     * Get a value given its name.
     * @param   string $name variable name
     * @param   mixed $default default value (if $name is missing in the input)
     * @return  mixed value of $name in input data or $default value
     * @throws  Claro_Input_Exception on failure
     */
    public function get( $name, $default = null );
    /**
     * Get a value given its name, the value must be set in the data
     * but can be empty
     * @param   string $name variable name
     * @return  mixed value of $name
     * @throws  Claro_Input_Exception on failure or if $name is missing
     */
    public function getMandatory( $name );
}

/**
 * Array based data input class
 */
class Claro_Input_Array implements Claro_Input
{
    protected $input;
    protected $_notSet;
    
    /**
     * @param   array $input
     */
    public function __construct( $input )
    {
        $this->input = $input;
        // create a singleton object for the getMandatory method
        // this object will be used to check if a value is defined
        // in the input data in order to avoid pitfalls with the empty()
        // PHP function
        $this->_notSet = (object) null;
    }
    
    /**
     * Is considered empty : string(0), null, array(0), empty stdClass object
     * @param mixed $value
     * @return bool 
     */
    protected static function isEmpty( $value )
    {
        return !( is_numeric ( $value ) || is_bool ( $value ) || ( is_object ( $value ) && get_class($value) != 'stdClass' ) ) 
                && empty( $value );
    }
    
    /**
     * @see     Claro_Input
     */
    public function get( $name, $default = null )
    {
        // the variable exists
        if ( array_key_exists( $name, $this->input ) )
        {
            $value = $this->input[$name];
            
            // the variable is considered empty but a default value is given
            if ( !is_null( $default ) 
                && self::isEmpty ( $value )  )
            {
                return $default;
            }
            // no default value given
            else
            {
                return $value;
            }
        }
        else
        {
            return $default;
        }
    }
    
    /**
     * @see     Claro_Input
     */
    public function getMandatory( $name )
    {
        // get the value of the requested variable and give the _notSet
        // singleton object as the default value so we can check if the
        // varaible was set without having issues with the empty() function
        $ret = $this->get( $name, $this->_notSet );
        
        // check if $ret is the instance of the _notSet singleton object
        // if it is the case, the requested variable has not been set
        // in the input data so we have to throw an exception
        if ( $ret === $this->_notSet )
        {
            throw new Claro_Input_Exception(
                "{$name} not found in ".get_class($this)." !" );
        }
        else
        {
            return $ret;
        }
    }
}

/**
 * Data input class with filters callback for validation
 */
class Claro_Input_Validator implements Claro_Input
{
    protected $validators;
    protected $validatorsForAll;
    protected $input;
    
    /**
     * @param   Claro_Input $input
     */
    public function __construct( Claro_Input $input )
    {
        $this->validators = array();
        $this->validatorsForAll = array();
        $this->input = $input;
    }
    
    /**
     * Set a validator for the given variable
     * @param   string $name variable name
     * @param   Claro_Validator $validator validator object
     * @throws  Claro_Input_Exception if the filter callback is not callable
     */
    public function setValidator( $name, Claro_Validator $validator )
    {
        if ( ! array_key_exists( $name, $this->validators ) )
        {
            $this->validators[$name] = array();
        }
        
        $validatorCallback = array( $validator, 'isValid' );
        
        if ( ! is_callable( $validatorCallback ) )
        {
            throw new Claro_Input_Exception ("Invalid validator callback : " 
                . $this->getFilterCallbackString($validatorCallback));
        }
        
        $this->validators[$name][] = $validatorCallback;
    }
    
    /**
     * Set a validator for all variables
     * @param   string $name variable name
     * @param   Claro_Validator $validator validator object
     * @throws  Claro_Input_Exception if the filter callback is not callable
     */
    public function setValidatorForAll( Claro_Validator $validator )
    {
        $validatorCallback = array( $validator, 'isValid' );
        
        if ( ! is_callable( $validatorCallback ) )
        {
            throw new Claro_Input_Exception ("Invalid validator callback : " 
                . $this->getFilterCallbackString($validatorCallback));
        }
        
        $this->validatorsForAll[] = $validatorCallback;
    }
    
    /**
     * @see     Claro_Input
     * @throws  Claro_Input_Exception if $value does not pass the validator
     */
    public function get( $name, $default = null )
    {
        $tainted = $this->input->get( $name, $default );
        
        // we need to detect that the default value has been returned to avoid 
        // running it to the validator
        if ( ( is_null( $default ) && is_null( $tainted ) )
            || $tainted == $default )
        {
            return $default;
        }
        else
        {
            return $this->validate( $name, $tainted );
        }
    }
    
    /**
     * @see     Claro_Input
     * @throws  Claro_Input_Exception if $value does not pass the validator
     */
    public function getMandatory( $name )
    {
        $tainted = $this->input->getMandatory( $name );
        
        return $this->validate( $name, $tainted );
    }
    
    /**
     * @param   string $name
     * @param   mixed $tainted value
     * @throws  Claro_Validator_Exception if $value does not pass the
     * filter for $name
     */
    public function validate( $name, $tainted )
    {
        // validators for all variables if any
        if ( !empty ($this->validatorsForAll ) )
        {
            foreach ( $this->validatorsForAll as $validatorForAllCallback )
            {
                if ( ! call_user_func( $validatorForAllCallback, $tainted ) )
                {
                    throw new Claro_Validator_Exception(
                        get_class( $validatorForAllCallback[0] )
                        . " : {$name} does not pass the validator !" );
                }
            }
        }
        
        // validators for the requested variable
        if ( array_key_exists( $name, $this->validators ) )
        {
            foreach ( $this->validators[$name] as $validatorCallback )
            {
                if ( ! call_user_func( $validatorCallback, $tainted ) )
                {
                    throw new Claro_Validator_Exception(
                        get_class( $validatorCallback[0] )
                        . " : {$name} does not pass the validator !" );
                }
            }
        }
        
        return $tainted;
    }
}

/**
 * User input class to replace $_REQUEST
 * @since Claroline 1.11, Claro_Validator_CustomNotEmpty is no more ssigned to 
 *  all variables because it causes the system to refuse to return the default 
 *  value for an empty string
 */
class Claro_UserInput
{        
    protected static $instance = false;
    
    /**
     * Get user input object
     * @return  Claro_Input_Validator
     */
    public static function getInstance()
    {
        if ( ! self::$instance )
        {
            // Create an input validator instance using the $_GET
            // and $_POST super arrays
            self::$instance = new Claro_Input_Validator( 
                new Claro_Input_Array( array_merge( $_GET, $_POST ) ) );
        }
        
        return self::$instance;
    }
}
