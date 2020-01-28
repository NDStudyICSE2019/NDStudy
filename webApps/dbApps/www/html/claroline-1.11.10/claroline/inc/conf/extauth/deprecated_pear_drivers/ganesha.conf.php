<?php // $Id: ganesha.conf.php 13480 2011-08-29 12:02:59Z zefredz $

/**
 * Ganesha authentication driver
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

if ( !function_exists('manage_user_status_from_ganesha_to_claroline') )
{
    function manage_user_status_from_ganesha_to_claroline($ganeshaStatus)
    {
        switch ($ganeshaStatus)
        {
            case 2:       // ganesha administrator
                return 1; // claroline course manager
                break;
    
            case 3:       // ganesha tutor
                return 1; // claroline course manager
    
            case 0:       // ganesha trainee
                return 5; // claroline simple user
            default:
                return 5;
        }
    }
}

// do not change the following section
$driverConfig['driver'] = array(
    'enabled' => true,
    'class' => 'PearAuthDriver',
    'authSourceType' => 'DB',
    'authSourceName' => 'ganesha',
    'userRegistrationAllowed' => true,
    'userUpdateAllowed' => true
);

// you can change the driver from this point

$driverConfig['extAuthOptionList'] = array(
    'dsn'         => 'mysql://dbuser:dbpassword@domain/ganesha',
    'table'       => 'membres', // warning ! table prefix can change from one system to another 
    'usernamecol' => 'login',
    'passwordcol' => 'password',
    'db_fields'   => array('prenom', 'nom', 'type', 'email'),
    'cryptType'   => 'none'
);

$driverConfig['extAuthAttribNameList'] = array(
    'lastname'  => 'nom',
    'firstname' => 'prenom',
    'email'     => 'email',
    'status'    => 'type'
);

$driverConfig['extAuthAttribTreatmentList'] = array (
    'status' => 'manage_user_status_from_ganesha_to_claroline'
);

$driverConfig['extAuthAttribToIgnore'] = array(
    // 'isCourseCreator'
);
?>