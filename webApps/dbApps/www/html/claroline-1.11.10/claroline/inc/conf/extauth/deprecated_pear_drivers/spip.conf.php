<?php // $Id: spip.conf.php 13480 2011-08-29 12:02:59Z zefredz $

/**
 * SPIP authentication driver
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

if ( ! function_exists('manage_user_status_from_spip_to_claroline') )
{
    function manage_user_status_from_spip_to_claroline($spipStatus)
    {
        $spipStatus = (int) $spipStatus;
    
        switch ($spipStatus)
        {
            case 0:         // spip administrator
                return 1;   // claroline course manager
                break;
            case 1:         // spip writer
                return 5;   // claroline student
                break;
            case 5:         // spip trashed user
                die('<center>user not allowed</center>');
                break;
            case 6:         // spip forum user
                return 5;   // claroline student
                break;
            default:
                return 5;   // claroline student
        }
    }
}

// do not change the following section
$driverConfig['driver'] = array(
    'enabled' => true,
    'class' => 'PearAuthDriver',
    'authSourceType' => 'DB',
    'authSourceName' => 'spip',
    'userRegistrationAllowed' => true,
    'userUpdateAllowed' => true
);

// you can change the driver from this point

$driverConfig['extAuthOptionList'] = array(
    'dsn'         => 'mysql://root:@localhost/spip', 
    'table'       => 'spip_auteurs', // warning ! table prefix can change from one system to another 
    'usernamecol' => 'login',
    'passwordcol' => 'pass',
    'db_fields'   => array('nom', 'email', 'statut'),
    'cryptType'   => 'md5'
);

$driverConfig['extAuthAttribNameList'] = array(
    'lastname'     => 'nom',
    'email'        => 'email',
    'status'       => 'statut'
);

$driverConfig['extAuthAttribTreatmentList'] = array (
    'status' => 'manage_user_status_from_spip_to_claroline'
);

$driverConfig['extAuthAttribToIgnore'] = array(
    // 'isCourseCreator'
);
?>