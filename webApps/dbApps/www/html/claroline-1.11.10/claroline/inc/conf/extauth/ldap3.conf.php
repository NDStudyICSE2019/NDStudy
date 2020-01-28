<?php

/**
 * LDAP authentication driver version 2
 * This is an example using a given dn and password for LDAP search
 *
 * @version     1.11 $Revision: 14352 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     CLAUTH
 */

$driverConfig['driver'] = array(
    'enabled' => true,
    'class' => 'ClaroLdapAuthDriver',
    'authSourceName' => 'ldap',
    'userRegistrationAllowed' => true,
    'userUpdateAllowed' => true
);

$driverConfig['extAuthOptionList'] = array(
    'url'      => 'ldap://ldap.example.com',
    'port'     => 389,
    'version'  => 3,
    'basedn'   => 'ou=people,o=example,c=com',
    'userattr' => 'uid',
    'userfilter' => '(objectClass=person)',
    'useBindDn' => true, // set to true if your server does not allow anonymous bind
    'binddn' => 'uid=login,ou=people,o=example,c=com', // dn for non anonymous search
    'binbpw' => 'password' // password for noanonymous search
);

$driverConfig['extAuthAttribNameList'] = array(
    'lastname'     => 'sn',
    'firstname'    => 'givenname',
    'email'        => 'mail',
    'phoneNumber'  => 'telephonenumber',
    'authSource'   => 'ldap',
    'officialCode' => 'employeenumber'
);

$driverConfig['extAuthAttribTreatmentList'] = array (
);

$driverConfig['extAuthAttribToIgnore'] = array(
    'isCourseCreator'
);
