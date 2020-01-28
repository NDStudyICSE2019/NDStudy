<?php // $Id: authmanager.lib.php 14536 2013-09-11 05:47:40Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Authentication Manager
 *
 * @version     Claroline 1.11 $Revision: 14536 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.auth
 */

// Get required libraries
require_once dirname(__FILE__) . '/../core/claroline.lib.php';
require_once dirname(__FILE__) . '/../database/database.lib.php';
require_once dirname(__FILE__) . '/../kernel/user.lib.php';

require_once dirname(__FILE__) . '/authdrivers.lib.php';
require_once dirname(__FILE__) . '/ldapauthdriver.lib.php';

if ( get_conf( 'claro_loadDeprecatedPearAuthDriver', true ) )
{
    require_once dirname(__FILE__) . '/pearauthdriver.lib.php';
}

require_once dirname(__FILE__) . '/authprofile.lib.php';

class AuthManager
{
    protected static $extraMessage = null;
    
    public static function getFailureMessage()
    {
        return self::$extraMessage;
    }
    
    protected static function setFailureMessage( $message )
    {
        self::$extraMessage = $message;
    }
    
    public static function authenticate( $username, $password )
    {
        if ( !empty($username) && $authSource = AuthUserTable::getAuthSource( $username ) )
        {
            Console::debug("Found authentication source {$authSource} for {$username}");
            $driverList = array( AuthDriverManager::getDriver( $authSource ) );
        }
        else
        {
            // avoid issues with session collision when many users connect from
            // the same computer at the same time with the same browser session !
            if ( AuthUserTable::userExists( $username ) )
            {
                self::setFailureMessage( get_lang( "There is already an account with this username." ) );
                return false;
            }
            
            $authSource = null;
            $driverList = AuthDriverManager::getRegisteredDrivers();
        }
        
        foreach ( $driverList as $driver )
        {
            $driver->setAuthenticationParams( $username, $password );
            
            if ( $driver->authenticate() )
            {

                $uid = AuthUserTable::registered( $username, $driver->getAuthSource() );
                
                if ( $uid )
                {
                    if ( $driver->userUpdateAllowed() )
                    {
                        $userAttrList =  $driver->getFilteredUserData();
                        
                        if ( isset( $userAttrList['loginName'] ) )
                        {
                            $newUserName = $userAttrList['loginName'];
                            
                            if ( ! get_conf('claro_authUsernameCaseSensitive', true) )
                            {
                                $newUsername = strtolower($newUserName);
                                $username = strtolower($username);
                            }
                            
                            // avoid session collisions !
                            if ( $username != $newUserName )
                            {
                                Console::error( "EXTAUTH ERROR : try to overwrite an existing user {$username} with another one" . var_export($userAttrList, true) );
                            }
                            else
                            {
                                AuthUserTable::updateUser( $uid, $userAttrList );
                                Console::info( "EXTAUTH INFO : update user {$uid} {$username} with " . var_export($userAttrList, true) );
                            }
                        }
                        else
                        {
                            Console::error( "EXTAUTH ERROR : no loginName given for user {$username} by authSource " . $driver->getAuthSource() );
                        }
                    }
                    
                    return Claro_CurrentUser::getInstance( $uid, true );
                }
                elseif ( $driver->userRegistrationAllowed() )
                {
                    // duplicate code here to avoid issue with multiple requests on a busy server !
                    if ( AuthUserTable::userExists( $username ) )
                    {
                        self::setFailureMessage( get_lang( "There is already an account with this username." ) );
                        return false;
                    }
                    
                    $uid = AuthUserTable::createUser( $driver->getUserData() );
                    
                    return Claro_CurrentUser::getInstance( $uid, true );
                }
            }
            elseif ( $authSource )
            {
                self::setFailureMessage( $driver->getFailureMessage() );
            }
        }
        
        // authentication failed
        return false;
    }
}

class AuthUserTable
{
    public static function userExists( $username )
    {
        $tbl = claro_sql_get_main_tbl();

        $sql = "SELECT user_id, authSource\n"
            . "FROM `{$tbl['user']}`\n"
            . "WHERE "
            . ( get_conf('claro_authUsernameCaseSensitive',true) ? 'BINARY ' : '')
            . "username = ". Claroline::getDatabase()->quote($username) . "\n"
            ;

        $res = Claroline::getDatabase()->query( $sql );

        if ( $res->numRows() )
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    public static function registered( $username, $authSourceName )
    {
        $tbl = claro_sql_get_main_tbl();
        
        $sql = "SELECT user_id\n"
            . "FROM `{$tbl['user']}`\n"
            . "WHERE "
            . ( get_conf('claro_authUsernameCaseSensitive',true) ? 'BINARY ' : '')
            . "username = ". Claroline::getDatabase()->quote($username) . "\n"
            . "AND\n"
            . "authSource = " . Claroline::getDatabase()->quote($authSourceName) . "\n"
            . "ORDER BY user_id DESC LIMIT 1"
            ;
            
        $res = Claroline::getDatabase()->query( $sql );
        
        if ( $res->numRows() )
        {
            $uidArr = $res->fetch();
            
            return (int) $uidArr['user_id'];
        }
        else
        {
            return false;
        }
    }
    
    public static function getAuthSource( $username )
    {
        $tbl = claro_sql_get_main_tbl();
        
        $sql = "SELECT authSource\n"
            . "FROM `{$tbl['user']}`\n"
            . "WHERE "
            . ( get_conf('claro_authUsernameCaseSensitive',true) ? "BINARY " : "" )
            . "username = ". Claroline::getDatabase()->quote($username) . "\n"
            . "ORDER BY user_id DESC LIMIT 1"
            ;
            
        return  Claroline::getDatabase()->query( $sql )->fetch(Database_ResultSet::FETCH_VALUE);
    }
    
    public static function createUser( $userAttrList )
    {
        return self::registerUser( $userAttrList, null );
    }
    
    public static function updateUser( $uid, $userAttrList )
    {
        return self::registerUser( $userAttrList, $uid );
    }
    
    protected static function registerUser( $userAttrList, $uid = null )
    {
        $preparedList = array();
        
        // Map database fields
        $dbFieldToClaroMap = array(
            'nom' => 'lastname',
            'prenom' => 'firstname',
            'username' => 'loginName',
            'email' => 'email',
            'officialCode' => 'officialCode',
            'phoneNumber' => 'phoneNumber',
            'isCourseCreator' => 'isCourseCreator',
            'authSource' => 'authSource');
        
        // Do not overwrite username and authsource for an existing user !!!
        if ( ! is_null( $uid ) )
        {
            unset( $dbFieldToClaroMap['username'] );
            unset( $dbFieldToClaroMap['authSource'] );
        }

        
        foreach ( $dbFieldToClaroMap as $dbFieldName => $claroAttribName )
        {
            if ( isset($userAttrList[$claroAttribName])
                && ! is_null($userAttrList[$claroAttribName]) )
            {
                $preparedList[] = $dbFieldName
                    . ' = '
                    . Claroline::getDatabase()->quote($userAttrList[$claroAttribName])
                    ;
            }
        }
        
        if ( empty( $preparedList ) )
        {
            return false;
        }
        
        $tbl = claro_sql_get_main_tbl();
        
        $sql = ( $uid ? 'UPDATE' : 'INSERT INTO' )
            . " `{$tbl['user']}`\n"
            . "SET " . implode(",\n", $preparedList ) . "\n"
            . ( $uid ? "WHERE  user_id = " . (int) $uid : '' )
            ;
        
        Claroline::getDatabase()->exec($sql);
        
        if ( ! $uid )
        {
            $uid = Claroline::getDatabase()->insertId();
        }
        
        return $uid;
    }
}

class AuthDriverManager
{
    protected static $drivers = false;
    protected static $driversAllowingLostPassword = false;
    
    public static function getRegisteredDrivers()
    {
        if ( ! self::$drivers )
        {
            self::initDriverList();
        }
        
        return  self::$drivers;
    }
    
    public static function getDriver( $authSource )
    {
        if ( ! self::$drivers )
        {
            self::initDriverList();
        }
        
        if ( array_key_exists( $authSource, self::$drivers ) )
        {
            return self::$drivers[$authSource];
        }
        else
        {
            throw new Exception("No auth driver found for {$authSource} !");
        }
    }
    
    protected static function loadDriver ( $driverConfigPath )
    {
        if ( !file_exists( $driverConfigPath ) )
        {
            if ( claro_debug_mode() )
            {
                throw new Exception("Driver configuration {$driverConfigPath} not found");
            }

            Console::error( "Driver configuration {$driverConfigPath} not found" );
            
            return;
        }
        
        $driverConfig  = array();
        
        include $driverConfigPath;
        
        if ( $driverConfig['driver']['enabled'] == true )
        {
            $driverClass = $driverConfig['driver']['class'];
            
            // search for kernel drivers
            if ( class_exists( $driverClass ) )
            {
                $driver = new $driverClass;
                $driver->setDriverOptions( $driverConfig );
                self::$drivers[$driverConfig['driver']['authSourceName']] = $driver;
            }
            // search for user defined drivers
            else
            {
                // load dynamic drivers
                if ( ! file_exists ( get_path('rootSys') . 'platform/conf/extauth/drivers' ) )
                {
                    FromKernel::uses('fileManage.lib');
                    claro_mkdir(get_path('rootSys') . 'platform/conf/extauth/drivers', CLARO_FILE_PERMISSIONS, true );
                }
                
                $driverPath = get_path('rootSys') 
                    . 'platform/conf/extauth/drivers/' 
                    . strtolower($driverClass).'.drv.php';

                if ( file_exists($driverPath) )
                {
                    require_once $driverPath;

                    if ( class_exists( $driverClass ) )
                    {
                        $driver = new $driverClass;
                        $driver->setDriverOptions( $driverConfig );
                        self::$drivers[$driverConfig['driver']['authSourceName']] = $driver;

                    }
                    else
                    {
                        if ( claro_debug_mode() )
                        {
                            throw new Exception("Driver class {$driverClass} not found");
                        }

                        Console::error( "Driver class {$driverClass} not found" );
                    }
                }
                else
                {
                    if ( claro_debug_mode() )
                    {
                        throw new Exception("Driver class {$driverClass} not found");
                    }

                    Console::error( "Driver class {$driverClass} not found" );
                }
            }
        }
        
        if ( isset($driverConfig['driver']['lostPasswordAllowed']) && $driverConfig['driver']['lostPasswordAllowed'] == true )
        {
            self::$driversAllowingLostPassword[$driverConfig['driver']['authSourceName']] = $driverConfig['driver']['authSourceName'];
        }
    }
    
    public static function getDriversAllowingLostPassword()
    {
        if ( ! self::$drivers )
        {
            self::initDriverList();
        }
        
        return self::$driversAllowingLostPassword;
    }
    
    public static function checkIfDriverSupportsLostPassword( $authSourceName )
    {
        if ( ! self::$drivers )
        {
            self::initDriverList();
        }
        
        if ( isset( self::$driversAllowingLostPassword[$authSourceName] ) )
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    protected static function initDriverList()
    {
        // load static drivers
        self::$drivers = array(
            'claroline' => new ClarolineLocalAuthDriver(),
            'disabled' => new UserDisabledAuthDriver(),
            'temp' => new TemporaryAccountAuthDriver()
        );
        
        self::$driversAllowingLostPassword = array(
            'claroline' => 'claroline',
            'clarocrypt' => 'clarocrypt'
        );
        
        // load dynamic drivers
        if ( ! file_exists ( get_path('rootSys') . 'platform/conf/extauth' ) )
        {
            FromKernel::uses('fileManage.lib');
            claro_mkdir(get_path('rootSys') . 'platform/conf/extauth', CLARO_FILE_PERMISSIONS, true );
        }
        
        if ( get_conf( 'claro_authDriversAutoDiscovery', true ) )
        {
            $driversToLoad = array();
            
            $it = new DirectoryIterator( get_path('rootSys') . 'platform/conf/extauth' );
            
            foreach ( $it as $file )
            {
                if ( $file->isFile() && substr( $file->getFilename(), -9 ) == '.conf.php' )
                {
                    $driversToLoad[] = $file->getPathname();
                }          
            }

            sort( $driversToLoad );

            foreach ( $driversToLoad as $driverFile )
            {
                self::loadDriver($driverFile);
            }        
        }
        else
        {
            if ( file_exists( get_path('rootSys') . 'platform/conf/extauth/drivers.list' ) )
            {
                $authDriverList = file( get_path('rootSys') . 'platform/conf/extauth/drivers.list' );
                
                foreach ( $authDriverList as $authDriver )
                {
                    $authDriver = trim($authDriver);
                    
                    if ( ! empty( $authDriver ) )
                    {
                        self::loadDriver(ltrim(rtrim(get_path('rootSys') . 'platform/conf/extauth/'.$authDriver)));
                    }
                }
            }
        }
    }
}
