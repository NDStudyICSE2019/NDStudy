<?php // $Id: ldapauthdriver.lib.php 14511 2013-08-12 06:56:21Z zefredz $

/**
 * LDAP Authentication Driver
 *
 * @version     2.5 $Revision: 14511 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU AFFERO GENERAL PUBLIC LICENSE version 3
 */

require_once dirname(__FILE__) . '/ldap.lib.php';
require_once dirname(__FILE__) . '/authdrivers.lib.php';

class ClaroLdapAuthDriver extends AbstractAuthDriver
{
    protected $driverConfig;
    
    protected $authSourceName;
    
    protected
        $userRegistrationAllowed,
        $userUpdateAllowed;
        
    protected
        $extAuthOptionList,
        $extAuthAttribNameList,
        $extAuthAttribTreatmentList,
        $userAttr,
        $userFilter,
        $userSelfBindAuth,
        $useBindDn;
        
    protected $user;
    
    public function setDriverOptions( $driverConfig )
    {
        $this->driverConfig = $driverConfig;
        $this->authSourceName = $driverConfig['driver']['authSourceName'];
        
        $this->userRegistrationAllowed = isset( $driverConfig['driver']['userRegistrationAllowed'] )
            ? $driverConfig['driver']['userRegistrationAllowed']
            : false
            ;
        $this->userUpdateAllowed = isset( $driverConfig['driver']['userUpdateAllowed'] )
            ? $driverConfig['driver']['userUpdateAllowed']
            : false
            ;
            
        $this->extAuthOptionList = $driverConfig['extAuthOptionList'];
        $this->extAuthAttribNameList = $driverConfig['extAuthAttribNameList'];
        $this->extAuthAttribTreatmentList = $driverConfig['extAuthAttribTreatmentList'];
        $this->extAuthIgnoreUpdateList = $driverConfig['extAuthAttribToIgnore'];

        // @since 1.9.9 
        $defaultProfileOptions = array( 
                'courseRegistrationAllowed' => null,
                'courseEnrolmentMode' => null, 
                'defaultCourseProfile' => null, 
                'editableProfileFields' => null,
                'readonlyProfileFields' => array( 'login', 'password', 'phone', 'email', 'officalCode' ) );
        
        // @since 1.11 
        $this->authProfileOptions = isset($driverConfig['authProfileOptions'])
            ? array_merge( $defaultProfileOptions, $driverConfig['authProfileOptions'] )
            : $defaultProfileOptions
            ;
        
        $this->userSelfBindAuth = isset( $driverConfig['extAuthOptionList']['userSelfBindAuth'] )
            ? $driverConfig['extAuthOptionList']['userSelfBindAuth'] 
            : false
            ;
        
        $this->useBindDn = isset( $driverConfig['extAuthOptionList']['useBindDn'] )
            ? $driverConfig['extAuthOptionList']['useBindDn'] 
            : false
            ;
        
        $this->userAttr = isset($this->extAuthOptionList['userattr']) 
            ? $this->extAuthOptionList['userattr'] 
            : 'uid'
            ;
            
        $this->userFilter = isset($this->extAuthOptionList['userfilter']) 
            ? $this->extAuthOptionList['userfilter'] 
            : null
            ;
    }
    
    public function authenticate()
    {
        $auth = new Claro_Ldap(
            $this->extAuthOptionList['url'],
            $this->extAuthOptionList['port'],
            $this->extAuthOptionList['basedn']
        );
        
        try
        {
            $auth->connect();
            
            // no anonymous bind
            // user can search
            if( $this->userSelfBindAuth == 'true')
            {
                $searchdn = "{$this->userAttr}={$this->username},".$this->extAuthOptionList['basedn'];
                $searchpw = $this->password;
                
                $auth->bind( $searchdn, $searchpw );
            }
            // user cannot search
            elseif ( $this->useBindDn )
            {
                $searchdn = $this->extAuthOptionList['binddn'];
                $searchpw = $this->extAuthOptionList['bindpw']; 
            
                $auth->bind( $searchdn, $searchpw );
            }
            
            // search user
            
            $user = $auth->getUser($this->username, $this->userFilter, $this->userAttr);
            
            if ( $user )
            {
                if( $this->userSelfBindAuth == 'true')
                {
                    $binddn = "{$this->userAttr}={$this->username},".$this->extAuthOptionList['basedn'];
                }
                else
                {
                    $binddn = $user->getDn();
                }
                
                
                if( $auth->authenticate( $binddn, $this->password ) )
                {
                    $this->user = $user;
                    return true;
                }
                else
                {
                    return false;
                }
            }
        }
        catch ( Exception $e )
        {
            $this->setFailureMessage($e->getMessage());
            
            if ( claro_debug_mode () )
            {
                Console::error($e->__toString());
            }
            else
            {
                Console::error($e->getMessage());
            }
            
            return false;
        }
    }
    
    public function userRegistrationAllowed()
    {
        return $this->userRegistrationAllowed;
    }
    
    public function userUpdateAllowed()
    {
        return $this->userUpdateAllowed;
    }
    
    public function getAuthSource()
    {
        return $this->authSourceName;
    }
    
    public function getUserData()
    {
        //$userData = $this->user->getData();
        
        $userAttrList = array('lastname'     => NULL,
                          'firstname'    => NULL,
                          'loginName'    => NULL,
                          'email'        => NULL,
                          'officialCode' => NULL,
                          'phoneNumber'  => NULL,
                          'isCourseCreator' => NULL,
                          'authSource'   => NULL);
        
        foreach($this->extAuthAttribNameList as $claroAttribName => $extAuthAttribName)
        {
            if ( ! is_null($extAuthAttribName) )
            {
                if ( ! is_null($this->user->$extAuthAttribName) )
                {
                    $userAttrList[$claroAttribName] = $this->user->$extAuthAttribName;
                }
            }
        }
        
        // $userAttrList = array_merge( $userData, $userAttrList );
        
        foreach($userAttrList as $claroAttribName => $claroAttribValue)
        {
            if ( array_key_exists($claroAttribName, $this->extAuthAttribTreatmentList ) )
            {
                $treatmentCallback = $this->extAuthAttribTreatmentList[$claroAttribName];

                if ( is_callable( $treatmentCallback ) )
                {
                    $claroAttribValue = $treatmentCallback($claroAttribValue);
                }
                else
                {
                    $claroAttribValue = $treatmentCallback;
                }
            }

            $userAttrList[$claroAttribName] = $claroAttribValue;
        } // end foreach

        /* Two fields retrieving info from another source ... */

        $userAttrList['loginName' ] = $this->user->getUid();
        $userAttrList['authSource'] = $this->authSourceName;
        
        return $userAttrList;
    }
}
