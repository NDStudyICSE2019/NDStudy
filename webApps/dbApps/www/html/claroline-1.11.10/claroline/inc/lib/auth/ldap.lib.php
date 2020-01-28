<?php

/**
 * LDAP connection classes
 *
 * @version     2.5
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU AFFERO GENERAL PUBLIC LICENSE version 3 
 */

/**
 * Represents data from the LDAP and declare __get and __Set magic methods
 */
class Claro_Ldap_DataObject
{
    protected $data;
    protected $mapping;
    
    public function __construct( $data )
    {
        $this->data = $data;
        $this->mapping = array(
            'officialCode' => 'employeenumber',
            'firstName' => 'givenname',
            'lastName' => 'sn',
            'phone' => 'telephonenumber',
            'username' => 'uid',
            'email' => 'mail'
        );
    }
    
    protected function getMappedName( $name )
    {
        if ( isset( $this->mapping[$name] ) )
        {
            return $this->mapping[$name];
        }
        else
        {
            return $name;
        }
    }
    
    public function __get( $name )
    {
        $name = $this->getMappedName( $name );
        
        if ( isset ( $this->data[$name] ) )
        {
            $data = Claro_Ldap_Utils::decode( $this->data[$name] );
            
            if ( $data['count'] == 1 )
            {
                $data = $data[0];
            }
            else
            {
                $data = Claro_Ldap_Utils::removeCount( $data );
            }
            
            return $data;
        }
        else
        {
            return null;
        }
    }

    /**
     * Get the list of retreived attributes
     * @return array
     */
    public function listGivenAttributes()
    {
        $arrayKeys = array_keys( $this->data );
        $givenAttributes = array();
        
        foreach ( $arrayKeys as $key )
        {
            if ( ! is_numeric( $key ) && $key != 'count' )
            {
                $givenAttributes[] = $key;
            }
        }
        
        return $givenAttributes;
    }

    /**
     * Set the data object fields to LDAP attributes names mapping
     * @param array $mapping
     * @return $this
     */
    public function setMapping( $mapping )
    {
        $this->mapping = $mapping;

        // allow chaining
        return $this;
    }
}

/**
 * LDAP User
 */
class Claro_Ldap_User extends Claro_Ldap_DataObject
{
    protected $dn, $userAttr;
    
    public function __construct( $dn, $data, $userAttr = null )
    {
        $this->dn = $dn;
        $this->userAttr = $userAttr;
        parent::__construct( $data );
    }

    /**
     * Get the dn of the user
     * @return string
     */
    public function getDn()
    {
        return $this->dn;
    }

    /**
     * Get the retreived data (ie attributes) of the user
     * @return array
     */
    public function getData()
    {
        return Claro_Ldap_Utils::decode( $this->data );
    }
    
    /**
     * Get the retreived mapped data (ie attributes) of the user.
     * @return array
     */
    public function getMappedData()
    {
        $data = $this->getData();
        
        foreach ( $data as $name => $value )
        {
            if ( isset( $this->mapping[$name] ) )
            {
                $data[$this->mapping[$name]] = $value;
            }
        }
        
        return $data;
    }

    /**
     * Get the user identifier in the LDAP (use the setMapping to map the uid
     * filed of the USer object to the correct LDAP attribute)
     * @return string
     */
    public function getUid()
    {
        if ( empty( $this->userAttr ) )
        {
            return $this->uid;
        }
        else
        {
            return $this->__get($this->userAttr);
        }
    }
}

/**
 * LDAP Connection class
 */
class Claro_Ldap
{
    protected $ds;
    protected $server, $port;
    protected $rootDomain;
    
    public function __construct( $server, $port, $rootDomain = '' )
    {
        $this->server = $server;
        $this->port = $port;
        $this->rootDomain = $rootDomain;
    }
    
    public function __destruct()
    {
        $this->close();
        unset( $this->ds, $this->server, $this->port, $this->rootDomain );
    }
    
    public function connect()
    {
        $this->ds = ldap_connect( $this->server, $this->port );
        
        if ( $this->ds )
        {
            ldap_set_option( $this->ds, LDAP_OPT_PROTOCOL_VERSION, 3 );
        }
        else
        {
            throw new Exception("Cannot connect to LDAP server");
        }
        
        return $this;
    }
    
    public function bind( $dn, $pw )
    {
        if ( false === @ldap_bind( $this->ds, $dn, $pw ) )
        {
            throw new Exception("Cannot bind to server");
        }
        
        return $this;
    }
    
    public function bindAnonymous()
    {
        if ( false === @ldap_bind( $this->ds ) )
        {
            throw new Exception("Cannot bind to server");
        }
        
        return $this;
    }
    
    public function close()
    {
        if ( $this->ds )
        {
            @ldap_unbind( $this->ds );
            @ldap_close( $this->ds );
        }
        
        return $this;
    }
    
    /**
     * Get one user from the LDAP given its uid
     * @return Ldap_User or false
     */
    public function getUser( $uid, $filter = null, $userAttr = null )
    {
        if ( ! is_null($userAttr) )
        {
            $searchString = "({$userAttr}={$uid})";
        }
        else
        {
            $searchString = "(uid={$uid})";
        }
        
        if ( ! is_null( $filter ) )
        {
            $searchString = '(& '.$searchString.' '.$filter.')';
        }
        
        $sr = @ldap_search( $this->ds, $this->rootDomain, $searchString );
        
        if ( @ldap_count_entries( $this->ds, $sr ) == 1 )
        {
            // get the resource of the user
            $re = @ldap_first_entry( $this->ds, $sr );
            // get the data of the user as an array
            $entries = @ldap_get_entries( $this->ds, $sr );
            // user object from the dn and the entries corresponding to the user
            $user = new Claro_Ldap_User( ldap_get_dn( $this->ds, $re ), $entries[0], $userAttr );

            return $user;
        }
        else
        {
            return false;
        }
    }
    
    /**
     * Search the LDAP
     * @return array of matching entries or false
     */
    public function search( $searchString, $filterString = null )
    {
        if ( empty( $filterString ) )
        {
            $sr = @ldap_search( $this->ds, $this->rootDomain, $searchString );
        }
        else
        {
            $sr = @ldap_search( $this->ds, $this->rootDomain, $searchString, $filterString );
        }
        
        if ( @ldap_count_entries( $this->ds, $sr ) > 0 )
        {
            $entries = @ldap_get_entries( $this->ds, $sr );
            return $entries;
        }
        else
        {
            return false;
        }
    }
    
    public function authenticate( $dn, $pw )
    {
        try
        {
            $this->bind( $dn, $pw );
            return true;
        }
        catch ( Exception $e )
        {
            return false;
        }
    }
}

/**
 * LDAP Utility Functions
 */
class Claro_Ldap_Utils
{
    public static function decode( $data )
    {
        if ( is_array( $data ) )
        {
            array_walk_recursive( $data, array('Claro_Ldap_Utils', '_decode') );
            
            return $data;
        }
        else
        {
            return Claro_Ldap_Utils::_decode( $data );
        }
    }
    
    private static function _decode( &$str )
    {
        // $str = iconv( 'UTF-8', 'ISO-8859-1', $str );
        $str = utf8_decode( $str );
        return $str;
    }
    
    public static function encode( $data  )
    {
        if ( is_array( $data ) )
        {
            array_walk_recursive( $data, array('Claro_Ldap_Utils', '_encode') );
            
            return $data;
        }
        else
        {
            return Claro_Ldap_Utils::_encode( $data );
        }
    }
    
    private static function _encode( &$str )
    {
        // $str = iconv( 'ISO-8859-1', 'UTF-8', $str );
        utf8_encode( $str );
        return $str;
    }
    
    public static function removeCount( $data )
    {
        if ( isset( $data['count'] ) )
        {
            unset( $data['count'] );
        }
        
        return $data;
    }
}
