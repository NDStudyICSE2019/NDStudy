<?php // $Id: courseregistration.lib.php 14684 2014-02-11 10:00:41Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

require_once dirname(__FILE__).'/userlist.lib.php';

/**
 * This library provides a new course registration
 * mechanism that uses those profile to enrol a user in a course.
 *
 * @version     1.11 $Revision: 14684 $
 * @copyright   2001-2012 Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.auth
 * @since       1.11
 */

/**
 * Class toregister a user to a course
 */
class Claro_CourseUserRegistration
{
    const
        STATUS_OK = 0,
        STATUS_REGISTRATION_FAILED = 1,
        STATUS_KEYVALIDATION_FAILED = 2,
        STATUS_SYSTEM_ERROR = 4,
        STATUS_REGISTRATION_NOTAVAILABLE = 8,
        STATUS_MANAGER_CANNOT_UNREGISTER_ITSELF = 16,
        STATUS_UNREGISTRATION_NOTAVAILABLE = 32,
        STATUS_CANNOT_UNREGISTER_LASTMANAGER = 64;
    
    protected
        $admin = false,
        $tutor = false,
        $registerByClass = false,
        $userAuthProfile,
        $course,
        $givenCourseKey,
        $registerToSourceCourse = false,
        $categoryId,
        $ignoreRegistrationKeyCheck = false,
        $ignoreCategoryRegistrationCheck = false,
        $profileId = null,
        $class = null,
        $forceUnregOfManager = false;
    
    protected $status = 0, $errorMessage = '';
    
    /**
     *
     * @param AuthProfile $userAuthProfile profile of the user we want to enrol to the cours
     * @param Claro_Course $course kernel object representing the course
     * @param type $givenCourseKey optionnal given registration key (default null)
     * @param type $categoryId optionnal given categoryId (default null)
     */
    public function __construct(
        AuthProfile $userAuthProfile,
        Claro_Course $course,
        $givenCourseKey = null,
        $categoryId = null )
    {
        $this->userAuthProfile = $userAuthProfile;
        $this->course = $course;
        $this->givenCourseKey = $givenCourseKey;
        $this->categoryId = $categoryId;
        
        // is the user doing the registration a super user ?
        if ( claro_is_in_a_course()
            && claro_get_current_course_id() == $this->course->courseId )
        {
            $this->isSuperUser = claro_is_platform_admin()
                || claro_is_course_manager()
                || claro_is_allowed_tool_edit( get_module_data( 'CLUSER', 'id' ) );
        }
        else
        {
            $this->isSuperUser = claro_is_platform_admin();
        }
    }
    
    /**
     * Get the status of the user registration
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
    
    /**
     * Return the reason why addUser returned false
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
    
    public function setUserRegistrationKey( $registrationKey )
    {
        $this->givenCourseKey = $registrationKey;
    }
    
    public function setCategoryId( $categoryId )
    {
        $this->categoryId = $categoryId;
    }
    
    public function ignoreRegistrationKeyCheck()
    {
        $this->ignoreRegistrationKeyCheck = true;
    }
    
    public function ignoreCategoryRegistrationCheck()
    {
        $this->ignoreCategoryRegistrationCheck = true;
    }
    
    /**
     * User should be added as a course admin
     */
    public function setCourseAdmin()
    {
        $this->admin = true;
    }
    
    /**
     * User should be added as a course tutor
     */
    public function setCourseTutor()
    {
        $this->tutor = true;
    }
    
    /**
     * User should be added as a course tutor
     */
    public function setUserProfileIdInCourse( $profileId )
    {
        $this->profileId = (int) $profileId;
        
        if ( $profileId == claro_get_profile_id('manager') )
        {
            $this->setCourseAdmin();
        }
    }
    
    /**
     * User added through a class
     * @param Claro_Class $class
     * @since 1.11.9
     */
    public function setClass( Claro_Class $class )
    {
        $this->class = $class;
        $this->setClassRegistrationMode();
    }
    
    /**
     * User added through a class
     * @since removed since 1.11.9
     */
    protected function setClassRegistrationMode()
    {
        $this->registerByClass = true;
    }
    
    /**
     * Force super user (for example at course creation)
     */
    public function forceSuperUser()
    {
        $this->isSuperUser = true;
    }
    
    public function forceUnregistrationOfManager()
    {
        $this->forceUnregOfManager = true;
    }
    
    public function forceRemoveUser( $keepTrackingData = true, $moduleDataToPurge = array() )
    {
        if ( ! $this->isUnregistrationAllowed () )
        {
            return false;
        }
        
        if (  ! $this->isUserRegisteredToCourse () )
            
        {
            $this->status = self::STATUS_SYSTEM_ERROR;
            $this->errorMessage = get_lang('User not found in course');
            
            return false;
        }
        else
        {  
            $batchRegistration = new Claro_BatchCourseRegistration( $this->course );
        
            $batchRegistration->forceRemoveUserIdListFromCourse(
                array($this->userAuthProfile->getUserId()),
                $keepTrackingData, 
                $moduleDataToPurge, 
                true );
            
            if ( $batchRegistration->hasError () )
            {
                Console::error(var_export($batchRegistration->getErrorLog(), true));
                return false;
            }
            else
            {
                return true;
            }
        }
    }
    
    public function removeUser( $keepTrackingData = true, $moduleDataToPurge = array() )
    {
        if ( ! $this->isUnregistrationAllowed () )
        {
            return false;
        }
        
        if (  ! $this->isUserRegisteredToCourse () )
            
        {
            $this->status = self::STATUS_SYSTEM_ERROR;
            $this->errorMessage = get_lang('User not found in course');
            
            return false;
        }
        else
        {  
            $batchRegistration = new Claro_BatchCourseRegistration( $this->course );
        
            $batchRegistration->removeUserIdListFromCourse(
                array($this->userAuthProfile->getUserId()), 
                $this->class, 
                $keepTrackingData, 
                $moduleDataToPurge, 
                true );
            
            if ( $batchRegistration->hasError () )
            {
                Console::error(var_export($batchRegistration->getErrorLog(), true));
                return false;
            }
            else
            {
                return true;
            }
        }
    }
    
    /**
     * Subscribe a specific user to a specific course.  If this course is a session
     * course, the user will also be subscribed to the source course.
     * @return boolean TRUE  if it succeeds, FALSE otherwise
     */
    public function addUser()
    {
        if ( !$this->isRegistrationAllowed() )
        {
            
            return false;
        }
        
        $userId = $this->userAuthProfile->getUserId();
        $courseCode = $this->course->courseId;
        
        $tbl_mdb_names          = claro_sql_get_main_tbl();
        $tbl_user               = $tbl_mdb_names['user'];
        $tbl_rel_course_user    = $tbl_mdb_names['rel_course_user'];

        if (  Claroline::getDatabase()->query("
            SELECT
                user_id
            FROM
                `{$tbl_user}`
            WHERE
                user_id = " . Claroline::getDatabase()->escape($userId) )->numRows() == 0 )
        {
            $this->status = self::STATUS_SYSTEM_ERROR;
            $this->errorMessage = get_lang('User not found');
            return false;
        }
        else
        {
            // Previously check if the user isn't already subscribed to the course
            $courseUserListResultSet = Claroline::getDatabase()->query( "
                SELECT
                    count_user_enrol, count_class_enrol
                FROM
                    `{$tbl_rel_course_user}`
                WHERE
                    user_id = " . Claroline::getDatabase()->escape($userId) . "
                AND
                    code_cours = " . Claroline::getDatabase()->quote($courseCode) );

            if ( $courseUserListResultSet->numRows() > 0 )
            {
                $course_user_list = $courseUserListResultSet->fetch(Mysql_ResultSet::FETCH_OBJECT);
                
                $count_user_enrol = (int) $course_user_list->count_user_enrol;
                $count_class_enrol = (int) $course_user_list->count_class_enrol;

                // Increment the count of registration by the user or class
                if ( ! $this->registerByClass )
                {
                    $count_user_enrol = 1;
                }
                else
                {
                    $count_class_enrol++;
                }

                if ( !Claroline::getDatabase()->exec("
                    UPDATE
                        `{$tbl_rel_course_user}`
                    SET
                        `count_user_enrol` = " . $count_user_enrol . ",
                        `count_class_enrol` = " . $count_class_enrol . "
                    WHERE
                        user_id = " . Claroline::getDatabase()->escape($userId) . "
                    AND
                        code_cours = " . Claroline::getDatabase()->quote($courseCode)
                ) )
                {
                    $this->status = self::STATUS_SYSTEM_ERROR;
                    $this->errorMessage = get_lang('Cannot register user in course');
                    return false;
                }
                else
                {
                    return true;
                }
            }
            else
            {
                // First registration to the course
                $count_user_enrol = 0;
                $count_class_enrol = 0;

                // If a validation is requested for this course: isPending is true
                // If the current user is course manager: isPending is false
                $isPending = !$this->admin && $this->isValidationRequired() ? true : false;


                if ( ! $this->registerByClass )
                {
                    $count_user_enrol = 1;
                }
                else
                {
                    $count_class_enrol = 1;
                }

                if ( $this->admin )
                {
                    $profileId = claro_get_profile_id('manager');
                }
                elseif ( $this->profileId )
                {
                    $profileId = $this->profileId;
                }
                else
                {
                    $profileId = claro_get_profile_id($this->getCourseProfile());
                }

                // if this course is a session course, enrol to the source course
                
                if ( $this->course->sourceCourseId )
                {
                    $sourceCourseCode = $this->course->getSourceCourseCode();
                    
                    // only enrol the user to the source course only if he is not already there
                    
                    $sourceCourseUserListResultSet = Claroline::getDatabase()->query( "
                        SELECT
                            count_user_enrol, count_class_enrol
                        FROM
                            `{$tbl_rel_course_user}`
                        WHERE
                            user_id = " . Claroline::getDatabase()->escape($userId) . "
                        AND
                            code_cours = " . Claroline::getDatabase()->quote($sourceCourseCode) );

                    if ( $sourceCourseUserListResultSet->numRows() == 0 )
                    {
                        if ( !Claroline::getDatabase()->exec("INSERT INTO `" . $tbl_rel_course_user . "`
                                SET code_cours      = " . Claroline::getDatabase()->quote( $sourceCourseCode )  . ",
                                    user_id         = " . (int) $userId . ",
                                    profile_id      = " . (int) $profileId . ",
                                    isCourseManager = " . (int) ($this->admin ? 1 : 0 ) . ",
                                    isPending       = " . (int) ($isPending ? 1 : 0) . ",
                                    tutor           = " . (int) ($this->tutor ? 1 : 0) . ",
                                    count_user_enrol = " . $count_user_enrol . ",
                                    count_class_enrol = " . $count_class_enrol . ",
                                    enrollment_date = NOW()" ) )
                        {
                            $this->status = self::STATUS_SYSTEM_ERROR;
                            $this->errorMessage = get_lang('Cannot register user in source course');
                            return false;
                        }
                    }
                }
                
                // register user to new session course                
                if ( !Claroline::getDatabase()->exec("INSERT INTO `" . $tbl_rel_course_user . "`
                        SET code_cours      = " . Claroline::getDatabase()->quote( $courseCode )  . ",
                            user_id         = " . (int) $userId . ",
                            profile_id      = " . (int) $profileId . ",
                            isCourseManager = " . (int) ($this->admin ? 1 : 0 ) . ",
                            isPending       = " . (int) ($isPending ? 1 : 0) . ",
                            tutor           = " . (int) ($this->tutor ? 1 : 0) . ",
                            count_user_enrol = " . $count_user_enrol . ",
                            count_class_enrol = " . $count_class_enrol  . ",
                            enrollment_date = NOW()" ) )
                {
                    $this->status = self::STATUS_SYSTEM_ERROR;
                    $this->errorMessage = get_lang('Cannot register user in source course');
                    return false;
                }
                else
                {
                    return true;
                }
            }
        } // end else user register in the platform
    }
    
    // business logic...
    
    protected function isUserRegisteredToCourse()
    {
        $tbl_mdb_names          = claro_sql_get_main_tbl();
        $tbl_rel_course_user    = $tbl_mdb_names['rel_course_user'];
        
        $sqlCourseCode = Claroline::getDatabase()->quote( $this->course->courseId );
        $sqlUserId = Claroline::getDatabase()->escape($this->userAuthProfile->getUserId());
        
        if (  Claroline::getDatabase()->query("
            SELECT
                user_id
            FROM
                `{$tbl_rel_course_user}`
            WHERE
                user_id = {$sqlUserId}
            AND 
                code_cours = {$sqlCourseCode}" )->numRows() == 0 )
            
        {
            return false;
        }
        else
        {
            return true;
        }
    }
    
    /**
     * Get user enrolment mode
     * @return 'string'pending', 'auto' or null
     */
    protected function getCourseRegistrationMode()
    {
        if ( $this->isSuperUser )
        {
            return 'open';
        }
        
        $authProfileRegistrationMode = $this->userAuthProfile->getCourseRegistrationMode();
        
        if ( empty( $authProfileRegistrationMode ) )
        {
            return $this->course->registration;
        }
        else
        {
            return $authProfileRegistrationMode;
        }
    }
    
    /**
     * Is the user allowed to enrol to the course
     * @return boolean
     */
    protected function isRegistrationAllowed()
    {
        if ( $this->isSuperUser )
        {
            return true;
        }

        if ( $this->userAuthProfile->isProfileAllowedToRegisterInCourse() )
        {

            if( $this->isCourseRegistrationAllowed() )
            {
                if ( $this->ignoreRegistrationKeyCheck )
                {
                    return true;
                }
                else
                {
                    if ( $this->checkRegistrationKey() )
                    {
                        return true;
                    }
                    else
                    {
                        $this->status = self::STATUS_KEYVALIDATION_FAILED;
                        return false;
                    }
                }
            }
            else
            {
                return false;
            }
        }
        else
        {
            $this->status = self::STATUS_REGISTRATION_NOTAVAILABLE;
            $this->errorMessage = get_lang('Your profile does not allow you to register to course.');
            return false;
        }
    }
    
    protected function onlyOneCourseManagerLeft( )
    {
        $tbl_mdb_names          = claro_sql_get_main_tbl();
        $tbl_rel_course_user    = $tbl_mdb_names['rel_course_user'];
        
        $sqlCourseCode = Claroline::getDatabase()->quote( $this->course->courseId );
        
        if ( Claroline::getDatabase ()->query("
            SELECT
                user_id
            FROM
                `{$tbl_rel_course_user}`
            WHERE
                isCourseManager = 1
            AND 
                code_cours = {$sqlCourseCode}" )->numRows() == 1 )
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    /**
     * Is the user allowed to enrol to the course
     * @return boolean
     */
    protected function isUnregistrationAllowed()
    {
        $coursePrivileges = new CourseUserPrivileges( $this->course->courseId, $this->userAuthProfile->getUserId() );
        
        if ( !$this->forceUnregOfManager && $coursePrivileges->isCourseManager () && $this->userAuthProfile->getUserId() == claro_get_current_user_id () && !claro_is_platform_admin () )
        {
            $this->status = self::STATUS_MANAGER_CANNOT_UNREGISTER_ITSELF;
            $this->errorMessage = get_lang('Course manager cannot unsubscribe himself'); // unless he is platform admin too
            return false;
        }
        
        // check if is thelast course manager
        
        if ( $coursePrivileges->isCourseManager () && $this->onlyOneCourseManagerLeft () )
        {
            $this->status = self::STATUS_CANNOT_UNREGISTER_LASTMANAGER;
            $this->errorMessage = get_lang('You cannot unsubscribe the last course manager of the course'); // even if platform admin...
            return false;
        }
        
        
        if ( $this->isSuperUser )
        {
            return true;
        }

        if ( $this->userAuthProfile->isProfileAllowedToRegisterInCourse() )
        {
            return $this->isCourseUnregistrationAllowed();
        }
        else
        {
            $this->status = self::STATUS_UNREGISTRATION_NOTAVAILABLE;
            $this->errorMessage = get_lang('Your profile does not allow you to unregister from this course.');
            return false;
        }
    }
    
    /**
     * Is the validation required
     * @return boolean
     */
    public function isValidationRequired()
    {
        return !$this->isSuperUser && ($this->getCourseRegistrationMode() == 'validation');
    }
    
    
    /**
     * Get the profile name in the course
     * @return string
     */
    protected function getCourseProfile ()
    {
        return $this->userAuthProfile->getCourseProfile();
    }
    
    protected function checkRegistrationKey()
    {
        if ( $this->getCourseRegistrationMode() == 'open' && !empty( $this->course->registrationKey ) )
        {
            if( empty( $this->givenCourseKey ) )
            {
                $this->errorMessage = get_lang('This course requires a key for enrolment');
                $this->status = self::STATUS_KEYVALIDATION_FAILED;
                return false;
            }
            if ( $this->givenCourseKey != $this->course->registrationKey )
            {
                $this->errorMessage = get_lang('Invalid enrolment key given');
                $this->status = self::STATUS_KEYVALIDATION_FAILED;
                return false;
            }
            else
            {
                return true;
            }
        }
        else
        {
            return true;
        }
    }
    
    /**
     * Is the registration allowed in the current course
     * @return boolean
     */
    protected function isCourseRegistrationAllowed()
    {
        $curdate = claro_time();
        
        if ( !$this->ignoreCategoryRegistrationCheck 
            && !is_null( $this->categoryId ) 
            && ! $this->isAllowedToRegisterToCategory() )
        {
            $this->status = self::STATUS_REGISTRATION_FAILED;
            $isUserAllowedToEnrol = false;
            $this->errorMessage = get_lang('You have to be registered to this course\'s category in order to enrol the course');
        }
        elseif ( $this->isUserLimitExceeded() )
        {
            $this->status = self::STATUS_REGISTRATION_FAILED;
            $isUserAllowedToEnrol = false;
            $this->errorMessage = get_lang('The users limit for this course has been reached');
        }
        elseif ( !in_array( $this->getCourseRegistrationMode(), array('open', 'validation') ) )
        {
            $isUserAllowedToEnrol = false;
            $this->status = self::STATUS_REGISTRATION_NOTAVAILABLE;
            $this->errorMessage = get_lang(
                'This course currently does not allow new enrolments (registration: %registration)',
                array('%registration' => $this->getCourseRegistrationMode()) );
        }
        elseif ( !in_array( $this->course->status, array('enable', 'date') ) )
        {
            $isUserAllowedToEnrol = false;
            $this->status = self::STATUS_REGISTRATION_NOTAVAILABLE;
            $this->errorMessage = get_lang(
                'This course currently does not allow new enrolments (status: %status)',
                array('%status' => $this->course->status));
        }
        elseif ( $this->course->status == 'date' && !empty($this->course->publicationDate) && $this->course->publicationDate >= $curdate )
        {
            $isUserAllowedToEnrol = false;
            $this->status = self::STATUS_REGISTRATION_NOTAVAILABLE;
            $this->errorMessage = get_lang(
                'This course will be enabled on the %date',
                array('%date' => claro_date('d/m/Y', $this->course->publicationDate)));
        }
        elseif ( $this->course->status == 'date' && !empty($this->course->expirationDate) && $this->course->expirationDate <= $curdate )
        {
            $isUserAllowedToEnrol = false;
            $this->status = self::STATUS_REGISTRATION_NOTAVAILABLE;
            $this->errorMessage = get_lang(
                'This course has been deactivated on the %date',
                array('%date' => claro_date('d/m/Y', $this->course->expirationDate)));
            
        }
        elseif ( $this->course->status == 'date'
            && ( empty($this->course->expirationDate) && empty($this->course->publicationDate) ) )
        {
            $isUserAllowedToEnrol = false;
            $this->status = self::STATUS_SYSTEM_ERROR;
            $this->errorMessage = get_lang('This course is not available');
            Console::error(
                "Invalid publication and expiration date for course " . $this->course->courseId );
        }
        else
        {
            $isUserAllowedToEnrol = true;
        }
        
        return $isUserAllowedToEnrol;
    }
    
    protected function isCourseUnregistrationAllowed()
    {
        // Check if course available or option set to allow unregistration from unavailable course
        if ( get_conf('crslist_UserCanUnregFromInactiveCourses', false ) )
        {
            $isUserAllowedToUnenrol = true;
        }
        else
        {
            $curdate = claro_time();
            
            if ( !in_array( $this->course->status, array('enable', 'date') ) )
            {
                $isUserAllowedToUnenrol = false;
                $this->status = self::STATUS_UNREGISTRATION_NOTAVAILABLE;
                $this->errorMessage = get_lang(
                    'This course currently does not allow to unenrol (status: %status)',
                    array('%status' => $this->course->status));
            }
            elseif ( $this->course->status == 'date' && !empty($this->course->publicationDate) && $this->course->publicationDate >= $curdate )
            {
                $isUserAllowedToUnenrol = false;
                $this->status = self::STATUS_UNREGISTRATION_NOTAVAILABLE;
                $this->errorMessage = get_lang(
                    'This course will be enabled on the %date',
                    array('%date' => claro_date('d/m/Y', $this->course->publicationDate)));
            }
            elseif ( $this->course->status == 'date' && !empty($this->course->expirationDate) && $this->course->expirationDate <= $curdate )
            {
                $isUserAllowedToUnenrol = false;
                $this->status = self::STATUS_UNREGISTRATION_NOTAVAILABLE;
                $this->errorMessage = get_lang(
                    'This course has been deactivated on the %date',
                    array('%date' => claro_date('d/m/Y', $this->course->expirationDate)));

            }
            elseif ( $this->course->status == 'date'
                && ( empty($this->course->expirationDate) && empty($this->course->publicationDate) ) )
            {
                $isUserAllowedToUnenrol = false;
                $this->status = self::STATUS_SYSTEM_ERROR;
                $this->errorMessage = get_lang('This course is not available');
                Console::error(
                    "Invalid publication and expiration date for course " . $this->course->courseId );
            }
            else
            {
                $isUserAllowedToUnenrol = true;
            }
        }

        return $isUserAllowedToUnenrol;
    }
    
    /**
     * If the course registration requires registration to the course category,
     * check if the user is register to the category
     * @return boolean
     */
    protected function isAllowedToRegisterToCategory()
    {
        if ( $this->isSuperUser )
        {
            return true;
        }
        
        if( get_conf( 'registrationRestrictedThroughCategories', false ) )
        {
            if ( !ClaroCategory::isRegistredToCategory( $this->userAuthProfile->getUserId(), $this->categoryId ) )
            {
                return false;
            }
            else
            {
                return true;
            }
        }
        else
        {
            return true;
        }
    }
    
    /**
     * Check if there the user number limit is not exceded in the course
     * @return type
     */
    protected function isUserLimitExceeded()
    {
        if ( $this->course->userLimit != 0
            && $this->countCourseUsers() >= $this->course->userLimit )
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    /**
     * Count the number of non manager users in the course
     * @return boolean
     */
    protected function countCourseUsers()
    {
        $tbl_mdb_names          = claro_sql_get_main_tbl();
        $tbl_rel_course_user    = $tbl_mdb_names['rel_course_user'];
        
        return Claroline::getDatabase()->query("
            SELECT *
            FROM `{$tbl_rel_course_user}`
            WHERE code_cours = " . Claroline::getDatabase()->quote($this->course->courseId) . "
            AND tutor = 0
            AND isCourseManager = 0")->numRows();
    }
}
