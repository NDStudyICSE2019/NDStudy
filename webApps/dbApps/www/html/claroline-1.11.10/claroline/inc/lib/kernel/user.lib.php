<?php // $Id: user.lib.php 14328 2012-11-16 09:47:37Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Objects used to represent a user in the platform.
 *
 * @version     Claroline 1.11 $Revision: 14328 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.objects
 */

require_once dirname(__FILE__) . '/object.lib.php';
require_once dirname(__FILE__) . '/../core/claroline.lib.php';
require_once dirname(__FILE__) . '/../database/database.lib.php';

/**
 * Object used to load and represent a user.
 * WARNING : this object is read only.
 */
class Claro_User extends KernelObject
{
    protected $_userId;

    /**
     * Constructor
     * @param int $userId
     */
    public function __construct( $userId )
    {
        $this->_userId = $userId;
        $this->sessionVarName = '_user';
    }

    /**
     * Load user properties from database
     */
    public function loadFromDatabase()
    {
        $tbl = claro_sql_get_main_tbl();
        
        $sqlUserId = (int) $this->_userId;
        
        $sql = "SELECT "
            . "`user`.`user_id` AS userId,\n"
            . "`user`.`username`,\n"
            . "`user`.`prenom` AS firstName,\n"
            . "`user`.`nom` AS lastName,\n"
            . "`user`.`email`AS `mail`,\n"
            . "`user`.`officialEmail` AS `officialEmail`,\n"
            . "`user`.`language`,\n"
            . "`user`.`isCourseCreator`,\n"
            . "`user`.`isPlatformAdmin`,\n"
            . "`user`.`creatorId` AS creatorId,\n"
            . "`user`.`officialCode`,\n"
            . "`user`.`language`,\n"
            . "`user`.`authSource`,\n"
            . "`user`.`phoneNumber` AS `phone`,\n"
            . "`user`.`pictureUri` AS `picture`,\n"
            
            . ( get_conf('is_trackingEnabled')
                ? "UNIX_TIMESTAMP(`tracking`.`date`) "
                : "DATE_SUB(CURDATE(), INTERVAL 1 DAY) " )
                
            . "AS lastLogin\n"
            . "FROM `{$tbl['user']}` AS `user`\n"
            
            . ( get_conf('is_trackingEnabled')
                ? "LEFT JOIN `{$tbl['tracking_event']}` AS `tracking`\n"
                . "ON `user`.`user_id`  = `tracking`.`user_id`\n"
                . "AND `tracking`.`type` = 'user_login'\n"
                : '')
                
            . "WHERE `user`.`user_id` = ".$sqlUserId."\n"
            
            . ( get_conf('is_trackingEnabled')
                ? "ORDER BY `tracking`.`date` DESC LIMIT 1"
                : '')
            ;

        $userData = Claroline::getDatabase()->query( $sql )->fetch();
        
        if ( ! $userData )
        {
            throw new Exception("Cannot load user data for {$this->_userId}");
        }
        else
        {
            $userData['isPlatformAdmin'] = (bool) $userData['isPlatformAdmin'];
            $userData['isCourseCreator'] = (bool) $userData['isCourseCreator'];
            
            $this->_rawData = $userData;
            pushClaroMessage( "User {$this->_userId} loaded from database", 'debug' );
            
            $this->loadUserProperties();
        }
    }

    /**
     * Load additional properties from the database
     */
    public function loadUserProperties()
    {
        $tbl = claro_sql_get_main_tbl();
            
        $userProperties = Claroline::getDatabase()->query("
            SELECT
                propertyId AS name,
                propertyValue AS value,
                scope
            FROM
                `{$tbl['user_property']}`
            WHERE
                userId = " . (int) $this->_userId . ";
        ");

        $userProperties->setFetchMode(Database_ResultSet::FETCH_OBJECT);
        
        $this->_rawData['userProperties'] = array();
        
        foreach ( $userProperties as $property )
        {
            if ( ! array_key_exists( $property->name, $this->_rawData['userProperties'] )
                || ! is_array( $this->_rawData['userProperties'][$property->name] ) )
            {
                $this->_rawData['userProperties'][$property->name] = array();
            }
            
            $this->_rawData['userProperties'][$property->name][$property->scope] = $property->value;
        }
    }

    /**
     * Get an additionnal user property
     * @param string $name property name
     * @param string $scope property scope
     * @return mixed property value
     */
    public function getUserProperty( $name, $scope )
    {
        if ( array_key_exists( $name, $this->_rawData['userProperties'] )
            && array_key_exists( $scope, $this->_rawData['userProperties'][$property->name] )
        )
        {
            return $this->_rawData['userProperties'][$property->name][$property->scope];
        }
        else
        {
            return null;
        }
    }
}

/**
 * Object to represent the current user. This class is a singleton.
 */
class Claro_CurrentUser extends Claro_User
{
    public function __construct( $userId = null )
    {
        $userId = empty( $userId )
            ? claro_get_current_user_id()
            : $userId
            ;
            
        parent::__construct( $userId );
    }

    /**
     * Is it the first time the user log in to the platform ?
     * @todo the creator id should not be used for this purpose
     * @return boolean
     */
    public function firstLogin()
    {
        return ($this->_userId != $this->creatorId);
    }

    /**
     * Change the creator id of the user to the user itself to indicate that
     * the user has already logged in to the the platform
     * @todo the creator id should not be used for this purpose
     * @return void
     */
    public function updateCreatorId()
    {
        $tbl = claro_sql_get_main_tbl();
        
        $sql = "UPDATE `{$tbl['user']}`\n"
            . "SET   creatorId = user_id\n"
            . "WHERE user_id = " . (int)$this->_userId
            ;
            
        pushClaroMessage( "Creator id updated for user {$this->_userId}", 'debug' );
    
        return Claroline::getDatabase()->exec($sql);
    }
    
    protected static $instance = false;

    /**
     * Singleton constructor
     * @todo avoid using the singleton pattern and use a factory instead ?
     * @param int $uid user id
     * @param boolean $forceReload force reloading the data
     * @return Claro_CurrentUser current user
     */
    public static function getInstance( $uid = null, $forceReload = false )
    {
        if ( $forceReload || ! self::$instance )
        {
            self::$instance = new self( $uid );
            
            if ( !$forceReload && claro_is_user_authenticated() )
            {
                self::$instance->loadFromSession();
            }
            else
            {
                self::$instance->load( $forceReload );
            }
        }
        
        return self::$instance;
    }
}
