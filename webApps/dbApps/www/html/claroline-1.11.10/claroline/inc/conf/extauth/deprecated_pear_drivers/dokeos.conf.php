<?php // $Id: dokeos.conf.php 13480 2011-08-29 12:02:59Z zefredz $

/**
 * Dokeos authentication driver
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
    'authSourceName' => 'dokeos',
    'userRegistrationAllowed' => true,
    'userUpdateAllowed' => true
);

// you can change the driver from this point

$driverConfig['extAuthOptionList'] = array(
    // PUT HERE THE CORRECT DSN FOR YOUR DB SYSTEM
    'dsn'         => 'mysql://dbuser:dbpassword@domain/dokeos',
    'table'       => 'user', // warning ! table prefix can change from one system to another 
    'usernamecol' => 'username',
    'passwordcol' => 'password',
    'db_fields'   => array('prenom', 'nom', 'email', 'phoneNumber', 'statut', 'officialCode', 'pictureUri'),
    'cryptType'   => 'none'
);

$driverConfig['extAuthAttribNameList'] = array(
    'firstname'    => 'prenom',
    'lastname'     => 'nom',
    'email'        => 'email',
    'phoneNumber'  => 'phoneNumber',
    'statut'       => 'statut',
    'officialCode' => 'officialCode',
    'pictureUri'   => 'pictureUri',
    'status'      => 'statut'
);

$driverConfig['extAuthAttribTreatmentList'] = array (
    // 'field in claroline' => 'treatment function or value'
);

$driverConfig['extAuthAttribToIgnore'] = array(
    // 'isCourseCreator'
);
?>