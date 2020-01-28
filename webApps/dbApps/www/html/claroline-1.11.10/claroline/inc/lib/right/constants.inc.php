<?php // $Id: constants.inc.php 12923 2011-03-03 14:23:57Z abourguignon $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * Constants of the right package
 *
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     RIGHT
 * @author      Claro Team <cvs@claroline.net>
 */

DEFINE('PROFILE_TYPE_COURSE','COURSE');

/**
 * Anonymous
 */

get_lang('Anonymous');
get_lang('Course visitor (the user has no account on the platform)');

DEFINE('ANONYMOUS_PROFILE','anonymous');

/**
 * Guest
 */

get_lang('Guest');
get_lang('Course visitor (the user has an account on the platform, but is not enrolled in the course)');

DEFINE('GUEST_PROFILE','guest');

/**
 * User
 */

get_lang('User');
get_lang('Course member (the user is actually enrolled in the course)');

DEFINE('USER_PROFILE','user');

/**
 * Manager
 */

get_lang('Manager');
get_lang('Course Administrator');

DEFINE('MANAGER_PROFILE','manager');

/**
 * Prefix for custom profile
 */

DEFINE('CUSTOM_PROFILE_PREFIX','profile_');
