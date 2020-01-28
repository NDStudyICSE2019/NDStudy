<?php // $Id: authprofile.lib.php 14556 2013-10-09 07:25:57Z zefredz $

require_once dirname(__FILE__) . '/../kernel/course.lib.php';
require_once dirname(__FILE__) . '/authmanager.lib.php';

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Authentication Profiles API is used to give specific rights to a user based
 * on his authentication source. This library provides a new course registration
 * mechanism that uses those profile to enrol a user in a course.
 *
 * @version     1.11 $Revision: 14556 $
 * @copyright   2001-2012 Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.auth
 * @since       1.11
 */

/**
 * Authentication Profile class
 */
class AuthProfile
{
    protected
        $courseRegistrationAllowed = null,
        $defaultCourseProfile = null,
        $courseEnrolmentMode = null,
        $editableProfileFields = null,
        $userId,
        $authSource;
    
    /**
     *
     * @param int $userId
     * @param string $authSource
     */
    public function __construct( $userId, $authSource )
    {
        $this->userId = $userId;
        $this->authSource = $authSource;
    }
    
    /**
     * Get the user id
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }
    
    /**
     * Set the authentication profile options. Contains
     *  $data['courseRegistrationAllowed'] with value true, false or null (let the platform decide)
     *  $data['courseEnrolmentMode'] with value 'open', 'close', 'validation' or null (let the platform decide)
     *  $data['defaultCourseProfile'] profile attributed by default to the user when registering to a new course or null
     *  $data['editableProfileFields'] array of editable fileds in the user profile or null
     * @return $this
     */
    public function setAuthDriverOptions( $data )
    {
        if ( isset($data['courseEnrolmentMode']) )
        {
            $this->courseEnrolmentMode = $data['courseEnrolmentMode'];
        }
        else
        {
            $this->courseEnrolmentMode = null;
        }

        if ( isset($data['defaultCourseProfile']) && ! is_null($data['defaultCourseProfile']) )
        {
            $this->defaultCourseProfile = $data['defaultCourseProfile'];
        }
        else
        {
            $this->defaultCourseProfile = 'user';
        }

        if ( isset($data['editableProfileFields']) && ! empty($data['editableProfileFields']) )
        {
            $this->editableProfileFields = $data['editableProfileFields'];
        }
        else
        {
            load_kernel_config('user_profile');
            
            if ( empty ( $data['readonlyProfileFields'] ) )
            {
                $this->editableProfileFields = get_conf('profile_editable');
            }
            else
            {
                $baseProfileFeilds = get_conf('profile_editable');
                $this->editableProfileFields = array();
                
                foreach ( $baseProfileFeilds as $profileField )
                {
                    if ( !in_array( $profileField, $data['readonlyProfileFields'] ) )
                    {
                        $this->editableProfileFields[] = $profileField;
                    }
                }
            }
        }
        
        if ( isset ( $data['courseRegistrationAllowed'] ) && ! is_null($data['courseRegistrationAllowed']) )
        {
            $this->courseRegistrationAllowed = $data['courseRegistrationAllowed'];
        }
        else
        {
            $this->courseRegistrationAllowed = get_conf( 'allowToSelfEnroll', true );
        }
        
        return $this;
    }
    
    /**
     * Get the profile to give to the user when enroled in a course
     * @return string
     */
    public function getCourseProfile()
    {
        return $this->defaultCourseProfile;
    }
    
    /**
     * Get user enrolment mode
     * @return 'string'pending', 'auto' or null
     */
    public function getCourseRegistrationMode()
    {
        return $this->courseEnrolmentMode;
    }
    
    /**
     * Get the list of user profile fields editable for this user
     * @return array of string, editable field list
     */
    public function getEditableProfileFields()
    {
        return $this->editableProfileFields;
    }
    
    /**
     * Is the user allowed to enrol in a course
     * @return boolean
     */
    public function isProfileAllowedToRegisterInCourse()
    {
        return $this->courseRegistrationAllowed;
    }
}

/**
 * Auth profile factory
 */
class AuthProfileManager
{
    /**
     * Get the authentication profile for the given user id
     * @param int $userId
     * @return AuthProfile
     */
    public static function getUserAuthProfile( $userId )
    {
        if ( $userId != claro_get_current_user_id() )
        {
            $user = new Claro_User($userId);
            $user->loadFromDatabase();
        }
        else
        {
            $user = Claro_CurrentUser::getInstance();
        }
        
        $authSource = $user->authSource;
        
        if ( ! $authSource )
        {
            throw new Exception("Cannot find user authentication source for user {$userId}");
        }
        
        try
        {
            $profileOptions = AuthDriverManager::getDriver( $authSource )->getAuthProfileOptions();
        }
        catch ( Exception $e )
        {
            if ( claro_is_platform_admin () || ( claro_is_in_a_course() && claro_is_course_manager () && $userId != claro_get_current_user_id () ) )
            {
                Console::warning("Cannot find user authentication source for user {$userId}, use claroline default options instead");
                $profileOptions = AuthDriverManager::getDriver( 'claroline' )->getAuthProfileOptions();
            }
            else
            {
                throw $e;
            }
        }
        
        $authProfile = new AuthProfile( $userId, $authSource );
        $authProfile->setAuthDriverOptions($profileOptions);

        if ( claro_debug_mode() )
        {
            pushClaroMessage(var_export($profileOptions, true), 'debug');
        }

        return $authProfile;
    }
}
