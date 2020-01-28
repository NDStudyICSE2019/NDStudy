<?php // $Id: object.lib.php 14326 2012-11-16 09:42:47Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * "Magic" class to represent kernel objects. Defines __get, __set, __isset and
 * __unset magic methods.
 *
 * @version     Claroline 1.11 $Revision: 14326 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.objects
 */

abstract class KernelObject
{
    protected $_rawData = array();
    protected $sessionVarName;

    /**
     * Get the value of a property of the object. Magic method called by
     * $var = $obj->propertyName;
     * @param string $nm property name
     * @return mixed value of the property if the property exists, if not the
     * method returns null
     */
    public function __get( $nm )
    {
        if ( isset ( $this->_rawData[$nm] ) )
        {
            return $this->_rawData[$nm];
        }
        else
        {
            return null;
        }
    }

    /**
     * Prevent from changing the value of one of the object public property.
     * Magic method called by $obj->propertyName = $value;
     * @param string $nm
     * @param mixed $value
     * @throws Exception automaticaly ! (this object is read only)
     */
    public function __set( $nm, $value )
    {
        if ( $nm === '_rawData' )
        {
            $this->_rawData = $value;
        }
        else
        {
            throw new Exception("Cannot change variable {$nm} : ".__CLASS__." is readonly !");
        }
    }

    /**
     * Magic method called by isset($obj->propertyName);
     * @param string $nm property name
     * @return boolean true if the property is set for the object
     */
    public function __isset( $nm )
    {
        if ( isset ( $this->_rawData[$nm] ) )
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Prevent from unsetting read only properties. Magic method called by
     * unset($obj->propertyName);
     * @param string $nm property name
     * @throws Exception automaticaly ! (this object is read only)
     */
    public function __unset( $nm )
    {
        throw new Exception("Cannot unset variable {$nm} : ".__CLASS__." is readonly !");
    }

    /**
     * Get the raw data of the object
     * @todo rewrite the kernel so thi method can be made 'protected'
     * @return array raw data contained in the object
     */
    public function getRawData()
    {
        return $this->_rawData;
    }
    
    public function saveToSession()
    {
        $_SESSION[$this->sessionVarName] = $this->_rawData;
        pushClaroMessage( "Kernel object {$this->sessionVarName} saved to session", 'debug' );
    }
    
    /**
     * Load user properties from session
     */
    public function loadFromSession()
    {
        if ( !empty($_SESSION[$this->sessionVarName]) )
        {
            $this->_rawData = $_SESSION[$this->sessionVarName];
            pushClaroMessage( "Kernel object {$this->sessionVarName} loaded from session", 'debug' );
        }
        else
        {
            throw new Exception("Cannot load kernel object {$this->sessionVarName} from session");
        }
    }
    
    public function load( $refresh = false )
    {
        if ( empty( $this->_rawData ) || $refresh )
        {
            $this->loadFromDatabase();
        }
    }
    
    abstract protected function loadFromDatabase();
}
