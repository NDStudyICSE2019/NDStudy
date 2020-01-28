<?php // $Id: pearauthdriver.lib.php 13495 2011-08-31 13:54:58Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * PEAR-based Authentication Driver
 *
 * @version     1.11 $Revision: 13495 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.auth
 * @deprecated  since Claroline 1.11
 */

class PearAuthDriver extends AbstractAuthDriver
{
    protected $driverConfig;
    protected $authType;
    protected $authSourceName;
    protected $userRegistrationAllowed;
    protected $userUpdateAllowed;
    protected $extAuthOptionList;
    protected $extAuthAttribNameList;
    protected $extAuthAttribTreatmentList;
    
    protected $auth;
    
    public function authenticate()
    {
        if ( empty( $this->username ) || empty( $this->password ) )
        {
            return false;
        }
        
        $_POST['username'] = $this->username;
        $_POST['password'] = $this->password;
        
        if ( $this->authType === 'LDAP')
        {
            // CASUAL PATCH (Nov 21 2005) : due to a sort of bug in the
            // PEAR AUTH LDAP container, we add a specific option wich forces
            // to return attributes to a format compatible with the attribute
            // format of the other AUTH containers

            $this->extAuthOptionList ['attrformat'] = 'AUTH';
        }
        
        require_once 'Auth/Auth.php';

        $this->auth = new Auth( $this->authType, $this->extAuthOptionList, '', false);

        $this->auth->start();
        
        return $this->auth->getAuth();
    }
    
    public function getUserData()
    {
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
                $userAttrList[$claroAttribName] = $this->auth->getAuthData($extAuthAttribName);
            }
        }
        
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

        $userAttrList['loginName' ] = $this->auth->getUsername();
        $userAttrList['authSource'] = $this->authSourceName;
        
        if ( isset($userAttrList['status']) )
        {
            $userAttrList['isCourseCreator'] = ($userAttrList['status'] == 1) ? 1 : 0;
        }
        
        return $userAttrList;
    }
}
