<?php // $Id: moodle.conf.php 13480 2011-08-29 12:02:59Z zefredz $

/**
 * Moodle authentication driver
 *
 * @version     1.9 $Revision: 13480 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     CLAUTH
 * @deprecated  since Claroline 1.11
 */

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

// do not change the following section
$driverConfig['driver'] = array(
    'enabled' => true,
    'class' => 'PearAuthDriver',
    'authSourceType' => 'DB', 
    'authSourceName' => 'moodle',
    'userRegistrationAllowed' => true,
    'userUpdateAllowed' => true
);

// you can change the driver from this point

$driverConfig['extAuthOptionList'] = array(
    // PUT HERE THE CORRECT DSN FOR YOUR DB SYSTEM
    'dsn'         => 'mysql://dbuser:dbpassword@domain/moodle',
    'table'       => 'mdl_user', // warning ! table prefix can change from one system to another 
    'usernamecol' => 'username',
    'passwordcol' => 'password',
    'db_fields'   => array('firstname', 'lastname', 'email', 'phone1'),
    'cryptType'   => 'md5'
);

$driverConfig['extAuthAttribNameList'] = array(
    'firstname'    => 'firstname',
    'lastname'     => 'lastname',
    'email'        => 'email',
    'phoneNumber'  => 'phone1'
);

$driverConfig['extAuthAttribTreatmentList'] = array (
    'status' => 5
);

$driverConfig['extAuthAttribToIgnore'] = array(
    // 'isCourseCreator'
);
?>