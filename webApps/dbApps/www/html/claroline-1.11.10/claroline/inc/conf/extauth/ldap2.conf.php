<?php

/**
 * LDAP authentication driver version 2
 * This is an example using the user dn and password for LDAP search
 *
 * @version     1.11 $Revision: 13959 $
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
    'userSelfBindAuth' => true // set to true if your server does not allow anonymous bind
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
