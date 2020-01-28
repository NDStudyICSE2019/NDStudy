<?php

/**
 * Sample local auth driver for alumnus
 *
 * @version     1.11 $Revision: 14517 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     CLAUTH
 */

if ( ! class_exists( 'AlumniAuthDriver' ) )
{
    class AlumniAuthDriver extends LocalDatabaseAuthDriver{};
}

// do not change the following section
$driverConfig['driver'] = array(
    'enabled' => true,
    'class' => 'AlumniAuthDriver',
    'authSourceType' => 'local',
    'authSourceName' => 'alumni',
    'userRegistrationAllowed' => false,
    'lostPasswordAllowed' => true
);

// you can change the driver from this point

$driverConfig['extAuthOptionList'] = array(
);

$driverConfig['extAuthAttribNameList'] = array(
);

$driverConfig['extAuthAttribTreatmentList'] = array (
);

$driverConfig['extAuthAttribToIgnore'] = array(
);

$driverConfig['authProfileOptions'] = array(
    'courseRegistrationAllowed' => true,
    'courseEnrolmentMode' => 'validation',
    'defaultCourseProfile' => 'guest',
    'editableProfileFields' => array('email')
);
