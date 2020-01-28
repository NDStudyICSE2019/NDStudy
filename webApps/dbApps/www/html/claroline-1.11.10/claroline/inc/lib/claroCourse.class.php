<?php // $Id: claroCourse.class.php 14543 2013-09-16 12:35:47Z zefredz $

/**
 * CLAROLINE
 *
 * Course Class.
 *
 * @version     $Revision: 14543 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     Kernel
 * @author      Claro Team <cvs@claroline.net>
 * @author      Mathieu Laurent <laurent@cerdecam.be>
 * @author      Sebastien Piraux <piraux@cerdecam.be>
 * @since       1.9
 */

require_once dirname(__FILE__) . '/backlog.class.php';
require_once dirname(__FILE__) . '/admin.lib.inc.php'; // for delete course function
require_once dirname(__FILE__) . '/clarocategory.class.php';
require_once dirname(__FILE__) . '/../../messaging/lib/message/messagetosend.lib.php';
require_once dirname(__FILE__) . '/../../messaging/lib/recipient/userlistrecipient.lib.php';

class ClaroCourse
{
    // Identifier
    public $id;
    
    // Code (sometimes named sysCode)
    public $courseId;
    
    // Boolean: 1 = source course, 0 = session course
    public $isSourceCourse;
    
    // Identifier of the source course (only for session courses)
    public $sourceCourseId;
    
    // Name
    public $title;
    
    // Official code
    public $officialCode;
    
    // Titular
    public $titular;
    
    // Email
    public $email;
    
    // Array of categories (clarocategory.class.php)
    public $categories;
    
    // Depatment Name
    public $departmentName;
    
    // Department Url
    public $extLinkUrl;
    
    // Language of the course
    public $language;
    
    // Course access (true = public, false = private)
    public $access;
    
    // Course visibility (true = shown, false = hidden)
    public $visibility;
    
    // registration (true = open, false = close)
    public $registration;
    
    // registration key
    public $registrationKey;
    
    // publicationDate
    public $publicationDate;
    
    // expirationDate
    public $expirationDate;
    
    // useExpiratioDate;
    public $useExpirationDate;
    
    // status (course "open", "closed", "pending", "date", ...)
    public $status;
    
    // userLimit
    public $userLimit;
    
    // Backlog object
    public $backlog;
    
    // List of GET or POST parameters
    public $htmlParamList = array();
    
    
    /**
     * Constructor
     */
    public function __construct ($creatorFirstName = '', $creatorLastName = '', $creatorEmail = '')
    {
        load_kernel_config('CLHOME');
        $this->id                   = null;
        $this->courseId             = '';
        $this->isSourceCourse       = null;
        $this->sourceCourseId       = null;
        $this->title                = '';
        $this->officialCode         = '';
        $this->titular              = $creatorFirstName . ' ' . $creatorLastName;
        $this->email                = $creatorEmail;
        $this->categories           = array();
        $this->departmentName       = '';
        $this->extLinkUrl           = '';
        $this->language             = get_conf('platformLanguage');
        # FIXME FIXME FIXME
        $this->access               = !(get_conf('allowPublicCourses', true) || claro_is_platform_admin())
            && get_conf('defaultAccessOnCourseCreation') == 'public'
            ? 'platform'
            : get_conf('defaultAccessOnCourseCreation')
            ;
        $this->visibility           = get_conf('defaultVisibilityOnCourseCreation');
        $this->registration         = get_conf('defaultRegistrationOnCourseCreation') ? 'open' : 'close' ;
        $this->registrationKey      = '';
        $this->publicationDate      = time();
        $this->expirationDate       = 0;
        $this->useExpirationDate    = false;
        $this->status               = 'enable';
        $this->userLimit            = 0;
        
        $this->backlog = new Backlog();
    }
    
    
    /**
     * load course data from database
     *
     * @param string    $courseId string course identifier
     * @return boolean  success
     */
    public function load ($courseId)
    {
        if ( ( $course_data = claro_get_course_data($courseId) ) !== false )
        {
            // Generate the array of categories (excepted for session courses)
            $categoriesList = array();
            //if (is_null($course_data['sourceCourseId']))
            //{
                foreach ($course_data['categories'] as $cat)
                {
                    $tempCat = new claroCategory();
                    $tempCat->load($cat['categoryId']);
                    $categoriesList[] = $tempCat;
                }
            //}
            
            // Assign
            $this->courseId           = $courseId;
            $this->id                 = $course_data['id'];
            $this->isSourceCourse     = $course_data['isSourceCourse'];
            $this->sourceCourseId     = $course_data['sourceCourseId'];
            $this->title              = $course_data['name'];
            $this->officialCode       = $course_data['officialCode'];
            $this->titular            = $course_data['titular'];
            $this->email              = $course_data['email'];
            $this->categories         = $categoriesList;
            $this->departmentName     = $course_data['extLinkName'];
            $this->extLinkUrl         = $course_data['extLinkUrl'];
            $this->language           = $course_data['language'];
            $this->access             = $course_data['access'];
            $this->visibility         = $course_data['visibility'];
            $this->registration       = $course_data['registrationAllowed'];
            $this->registrationKey    = $course_data['registrationKey'];
            $this->publicationDate    = $course_data['publicationDate'];
            $this->expirationDate     = $course_data['expirationDate'];
            $this->status             = $course_data['status'];
            $this->userLimit          = $course_data['userLimit'];
            
            $this->useExpirationDate = isset($this->expirationDate);
            
            return true;
        }
        else
        {
            return false;
        }
    }
    
    
    /**
     * insert or update course data
     *
     * @return boolean success
     */
    public function save ()
    {
        if ( empty($this->courseId) )
        {
            // Insert
            $keys = define_course_keys ($this->officialCode,'',get_conf('dbNamePrefix'));
            
            $courseSysCode      = trim($keys['currentCourseId']);
            $courseDbName       = trim($keys['currentCourseDbName']);
            $courseDirectory    = trim($keys['currentCourseRepository']);
            
            if ( empty($courseSysCode) || empty($courseDbName) || empty($courseDirectory) )
            {
                throw new Exception("Error missing data for course {$this->officialCode}");
            }
            
            if ( ! $this->useExpirationDate) $this->expirationDate = 'NULL';
            
            // Session courses are created without categories links:
            // so we duplicate the source course's categories links
            
            /*if ( !is_null($this->sourceCourseId) && !empty($this->sourceCourseId) )
            {
                $sourceCourse = new claroCourse();
                $sourceCourse->load(claroCourse::getCodeFromId($this->sourceCourseId));
                
                $this->categories = $sourceCourse->categories;
            }*/
            
            if ( !is_null($this->sourceCourseId) && !empty($this->sourceCourseId) )
            {
                $sourceCourse = new claroCourse();
                $sourceCourse->load(claroCourse::getCodeFromId($this->sourceCourseId));
                
                if( $sourceCourse->sourceCourseId )
                {
                    throw new Exception( 'You cannot create a course session from another course session' );
                }
            }
            
            if (   prepare_course_repository($courseDirectory, $courseSysCode)
                && register_course($courseSysCode
                   ,               $this->officialCode
                   ,               $this->sourceCourseId
                   ,               $courseDirectory
                   ,               $courseDbName
                   ,               $this->titular
                   ,               $this->email
                   ,               $this->categories
                   ,               $this->title
                   ,               $this->language
                   ,               $GLOBALS['_uid']
                   ,               $this->access
                   ,               $this->registration
                   ,               $this->registrationKey
                   ,               $this->visibility
                   ,               $this->departmentName
                   ,               $this->extLinkUrl
                   ,               $this->publicationDate
                   ,               $this->expirationDate
                   ,               $this->status
                   ,               $this->userLimit )
                && install_course_database( $courseDbName )
                && install_course_tools( $courseDbName, $this->language, $courseDirectory )
                )
            {
                $courseObj = new Claro_Course($courseSysCode);
                $courseObj->load();

                $courseRegistration = new Claro_CourseUserRegistration(
                    AuthProfileManager::getUserAuthProfile($GLOBALS['_uid']),
                    $courseObj,
                    null,
                    null
                );
                
                $courseRegistration->ignoreRegistrationKeyCheck();
                $courseRegistration->ignoreCategoryRegistrationCheck();
                
                $courseRegistration->setCourseAdmin();
                $courseRegistration->setCourseTutor();
                $courseRegistration->forceSuperUser();
                
                if ( $courseRegistration->addUser() )
                {
                
                    // Set course id
                    $this->courseId = $courseSysCode;

                    // Notify event manager
                    $args['courseSysCode'  ] = $courseSysCode;
                    $args['courseDbName'   ] = $courseDbName;
                    $args['courseDirectory'] = $courseDirectory;
                    $args['courseCategory' ] = $this->categories;

                    $GLOBALS['eventNotifier']->notifyEvent("course_created",$args);

                    return true;
                }
                else
                {
                    $this->backlog->failure( $courseRegistration->getErrorMessage() );
                    return false;
                }
            }
            else
            {
                $lastFailure = claro_failure::get_last_failure();
                $this->backlog->failure( 'Error : '. $lastFailure );
                return false;
            }

        }
        else
        {
            // Update
            $tbl_mdb_names = claro_sql_get_main_tbl();
            $tbl_course = $tbl_mdb_names['course'];
            $tbl_cdb_names = claro_sql_get_course_tbl();
            $tbl_course_properties = $tbl_cdb_names['course_properties'];
            
            if ( ! $this->useExpirationDate) $this->expirationDate = null;
            
            $sqlExpirationDate = is_null($this->expirationDate)
                ? 'NULL'
                : 'FROM_UNIXTIME(' . claro_sql_escape($this->expirationDate) . ')'
                ;
            
            $sqlCreationDate = is_null($this->publicationDate)
                ? 'NULL'
                : 'FROM_UNIXTIME(' . claro_sql_escape($this->publicationDate) . ')'
                ;
            
            $sql = "UPDATE `" . $tbl_course . "`
                    SET `intitule`             = '" . claro_sql_escape($this->title) . "',
                        `titulaires`           = '" . claro_sql_escape($this->titular) . "',
                        `administrativeNumber` = '" . claro_sql_escape($this->officialCode) . "',
                        `language`             = '" . claro_sql_escape($this->language) . "',
                        `extLinkName`          = '" . claro_sql_escape($this->departmentName) . "',
                        `extLinkUrl`           = '" . claro_sql_escape($this->extLinkUrl) . "',
                        `email`                = '" . claro_sql_escape($this->email) . "',
                        `visibility`           = '" . ($this->visibility ? 'visible':'invisible') . "',
                        `access`               = '" . claro_sql_escape( $this->access ) . "',
                        `registration`         = '" . claro_sql_escape($this->registration) . "',
                        `registrationKey`      = '" . claro_sql_escape($this->registrationKey) . "',
                        `lastEdit`             = NOW(),
                        `creationDate`         = " . $sqlCreationDate . ",
                        `expirationDate`       = " . $sqlExpirationDate . ",
                        `status`               = '" . claro_sql_escape($this->status)   . "',
                        `userLimit`            = '" . (int) $this->userLimit . "'
                    WHERE code='" . claro_sql_escape($this->courseId) . "'";
            
            // Handle categories
            // 1/ Remove all links in database
            $this->unlinkCategories();
            
            // 2/ Link new categories selection
            $this->linkCategories($this->categories);
            
            // If it's a source course, do the same for all its session courses
            if ( $this->isSourceCourse )
            {
                $sql2 = "SELECT cours_id FROM `" . $tbl_course . "`
                        WHERE sourceCourseId = " . $this->id;
                
                $sessionCourses = claro_sql_query_fetch_all_rows($sql2);
                
                foreach ($sessionCourses as $sessionCourse)
                {
                    unlink_course_categories ( $sessionCourse['cours_id'] );
                    link_course_categories ( $sessionCourse['cours_id'], $this->categories );
                }
            }
            
            return claro_sql_query($sql);
        }
    }
    
    
    /**
     * Check if the course has session courses.
     *
     * @return boolean  TRUE if the course is a source course
     *                  FALSE otherwise
     * @since 1.10
     */
    public static function isSourceCourse ($id)
    {
        // Declare needed tables
        $tbl_mdb_names              = claro_sql_get_main_tbl();
        $tbl_courses                 = $tbl_mdb_names['course'];
        
        $sql = "SELECT isSourceCourse
                FROM `" . $tbl_courses . "`
                WHERE cours_id = " . (int) $id;
        
        $res = claro_sql_query_get_single_row($sql);
        
        if ($res['isSourceCourse'] == 1)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    
    /**
     * Check if the course is a session of another course.
     *
     * @return boolean  TRUE if the course is a session course
     *                  FALSE otherwise
     * @since 1.10
     */
    public static function isSessionCourse ($id)
    {
        $sourceCourse = get_source_course($id);
        
        if (!empty($sourceCourse))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    
    /**
     * Create links between current course one or more categories.  If there
     * are no category specified, only the root category is linked.
     *
     * @param array of categories
     * @since 1.10
     */
    public function linkCategories ( $categories )
    {
        if ( !is_null($categories) && !empty($categories) )
        {
            link_course_categories ( $this->id, $categories );
        }
        else
        {
            $this->backlog->failure(get_lang('Categories list is empty'));
        }
    }
    
    
    /**
     * Delete links in database between current course one or more categories.
     * If there are no category specified, all categories are unlinked.
     *
     * @param array of categories (leave it empty to unlink all categories)
     * @since 1.10
     */
    public function unlinkCategories ( $categories = array() )
    {
        unlink_course_categories ( $this->id, $categories );
    }
    
    
    /**
     * Count the number of categories linked to the current course.
     *
     * @return int      number of categories
     * @since 1.10
     */
    public function countCategoriesLinks ()
    {
        return (count_course_categories ( $this->id ));
    }
    
    
    /**
     * delete course data and content
     *
     * @return boolean success
     */
    public function delete ()
    {
        return delete_course($this->courseId, $this->sourceCourseId);
    }
    
    
    /**
     * Get all session courses for the current course (if any).
     *
     * @return array    session courses
     * @since 1.10
     */
    public function getSessionCourses ()
    {
        $sessionCourses = get_session_courses($this->id);
        
        if (!empty($sessionCourses))
            return $sessionCourses;
        else
            return array();
    }
    
    
    /**
     * Get any related course to the current course (parent or child).
     *
     * @return array    courses
     * @since 1.10
     */
    public function getRelatedCourses()
    {
        // Declare needed tables
        $tbl_mdb_names              = claro_sql_get_main_tbl();
        $tbl_course                 = $tbl_mdb_names['course'];
        
        $sql = "SELECT c.cours_id               AS id,
                       c.titulaires             AS titular,
                       c.code                   AS sysCode,
                       c.isSourceCourse         AS isSourceCourse,
                       c.sourceCourseId         AS sourceCourseId,
                       c.intitule               AS title,
                       c.administrativeNumber   AS officialCode,
                       c.language,
                       c.directory,
                       c.visibility,
                       c.access,
                       c.registration,
                       c.email,
                       c.status,
                       c.userLimit
                FROM `" . $tbl_course . "` AS c
                WHERE c.sourceCourseId = " . $this->id . "
                OR c.cours_id = " . $this->id;
        
        if (!empty($this->sourceCourseId))
        {
            $sql .= "
                OR cours_id = " . $this->sourceCourseId . "
                OR c.sourceCourseId = " . $this->sourceCourseId;
        }
        
        $sql .= "
            ORDER BY c.isSourceCourse DESC
        ";
        
        return claro_sql_query_fetch_all($sql);
    }
    
    
    /**
     * Get related course to the current course (parent or child) for a
     * given user.
     *
     * @return array    courses
     * @since 1.11
     */
    public function getRelatedUserCourses($userId)
    {
        // Declare needed tables
        $tbl_mdb_names              = claro_sql_get_main_tbl();
        $tbl_course                 = $tbl_mdb_names['course'];
        $tbl_rel_user_courses       = $tbl_mdb_names['rel_course_user'];
        
        $sql = "SELECT c.cours_id               AS id,
                       c.titulaires             AS titular,
                       c.code                   AS sysCode,
                       c.isSourceCourse         AS isSourceCourse,
                       c.sourceCourseId         AS sourceCourseId,
                       c.intitule               AS title,
                       c.administrativeNumber   AS officialCode,
                       c.language,
                       c.directory,
                       c.visibility,
                       c.access,
                       c.registration,
                       c.email,
                       c.status,
                       c.userLimit
                FROM `" . $tbl_course . "` AS c
                
                RIGHT JOIN `" . $tbl_rel_user_courses . "` AS rcu
                ON rcu.user_id = " . (int) $userId . "
                AND rcu.code_cours = c.code
                
                WHERE c.sourceCourseId = " . $this->id . "
                OR c.cours_id = " . $this->id;
        
        if (!empty($this->sourceCourseId))
        {
            $sql .= "
                OR cours_id = " . $this->sourceCourseId . "
                OR c.sourceCourseId = " . $this->sourceCourseId;
        }
        
        $sql .= "
                ORDER BY c.isSourceCourse DESC, c.intitule ASC";
        
        return claro_sql_query_fetch_all($sql);
    }
    
    
    /**
     * Get all courses in database ordered by label.  If a category identifier
     * is specified, only get courses linked to this category.  You can also
     * specify visibility.
     *
     * @param int       identifier of category (default: null)
     * @param bool      visibility (1 = only visible, 0 = only invisible, null = all; default: null)
     * @since 1.10
     */
    public static function getAllCourses ($categoryId = null, $visibility = null)
    {
        return claro_get_all_courses ($categoryId, $visibility);
    }
    
    
    /**
     * Get courses that can be displayed to normal users.  More restricted
     * than getAllCourses() method.
     *
     * @param int       identifier of category (default: null)
     * @param int       identifier of user (default: null)
     * @since 1.10
     */
    public static function getRestrictedCourses ($categoryId = null, $userId = null)
    {
        return claro_get_restricted_courses ($categoryId, $userId);
    }
    
    
    /**
     * Retrieve course data from a course's form.
     */
    public function handleForm ()
    {
        /*
         * Manage the multiple select.
         * If it has been left empty (no selection), create an array with
         * the identifier of the root category (0).
         * If it has been serialized in the progress URL, unserialized it.
         */
        if ( isset($_REQUEST['linked_categories']) )
        {
            $_REQUEST['linked_categories'] = is_array($_REQUEST['linked_categories']) ?
                ($_REQUEST['linked_categories']) :
                (unserialize($_REQUEST['linked_categories']));
        }
        else
        {
            $_REQUEST['linked_categories'] = array(0);
        }
        
        if ( isset($_REQUEST['isSourceCourse']) )   $this->title = trim(strip_tags($_REQUEST['isSourceCourse']));
        if ( isset($_REQUEST['sourceCourseId']) )   $this->title = trim(strip_tags($_REQUEST['sourceCourseId']));
        if ( isset($_REQUEST['course_title']) )     $this->title = trim(strip_tags($_REQUEST['course_title']));
        
        if ( isset($_REQUEST['course_officialCode']) )
        {
            $this->officialCode = trim(strip_tags($_REQUEST['course_officialCode']));
            $this->officialCode = preg_replace('/[^A-Za-z0-9_]/', '', $this->officialCode);
            switch (get_conf('forceCodeCase'))
            {
                case 'upper':
                    $this->officialCode = strtoupper($this->officialCode);
                    break;
                case 'lower':
                    $this->officialCode = strtolower($this->officialCode);
                    break;
                case 'nochange':
                    break;
                default:
                    break;
            }
        }
        
        if ( isset($_REQUEST['course_titular']) )   $this->titular = trim(strip_tags($_REQUEST['course_titular']));
        if ( isset($_REQUEST['course_email']) )     $this->email = trim(strip_tags($_REQUEST['course_email']));
        if ( count($_REQUEST['linked_categories']) > 0 )
        {
            $categoriesList = array();
            foreach( $_REQUEST['linked_categories'] as $category )
            {
                // Bypass the loading page "course creating, please wait"
                $categoryId = (is_a($category, 'claroCategory')) ? (strip_tags($category->id)) : (strip_tags($category));
                $tempCat = new claroCategory();
                $tempCat->load($categoryId);
                $categoriesList[] = $tempCat;
            }
            
            $this->categories = $categoriesList;
        }
        else
        {
            $rootCat = new claroCategory();
            $rootCat->load(0);
            $this->categories = array($rootCat);
        }
        
        if ( isset($_REQUEST['course_departmentName']) )    $this->departmentName = trim(strip_tags($_REQUEST['course_departmentName']));
        if ( isset($_REQUEST['course_extLinkUrl']) )        $this->extLinkUrl = trim(strip_tags($_REQUEST['course_extLinkUrl']));
        if ( isset($_REQUEST['course_language']) )          $this->language = trim(strip_tags($_REQUEST['course_language']));
        if ( isset($_REQUEST['course_visibility']) )        $this->visibility  = (bool) $_REQUEST['course_visibility'];
        if ( isset($_REQUEST['course_access']) )            $this->access = $_REQUEST['course_access'];
        
        if ( isset($_REQUEST['course_registration']) )
        {
            if ( isset($_REQUEST['registration_validation']) && $_REQUEST['registration_validation'] == 'on' )
            {
                $this->registration = 'validation';
            }
            else
            {
                $this->registration = trim(strip_tags($_REQUEST['course_registration']));
            }
        }
        
        if ( isset($_REQUEST['registration_key']) || isset($_REQUEST['course_registrationKey']) )
        {
            $this->registrationKey = trim(strip_tags($_REQUEST['course_registrationKey']));
        }
        else
        {
            $this->registrationKey = null;
        }
        
        if ( isset($_REQUEST['course_status_selection']))
        {
            if ($_REQUEST['course_status_selection'] == 'disable')
            {
                $this->status = isset($_REQUEST['course_status'])
                    ? trim($_REQUEST['course_status'])
                    : null
                    ;
            }
            elseif ($_REQUEST['course_status_selection'] == 'date' )
            {
                $this->status = 'date';
                    
                if ( isset($_REQUEST['course_publicationDate' ]) )
                {
                    $this->publicationDate = trim(strip_tags($_REQUEST['course_publicationDate']));
                }
                elseif (isset($_REQUEST['course_publicationYear'])
                    && isset($_REQUEST['course_publicationMonth'])
                    && isset($_REQUEST['course_publicationDay']))
                {
                    $this->publicationDate = mktime(
                        0,0,0,
                        $_REQUEST['course_publicationMonth'],
                        $_REQUEST['course_publicationDay'],
                        $_REQUEST['course_publicationYear'] );
                }
                else
                {
                    $this->publicationDate = mktime(23,59,59);
                }
                
                $this->useExpirationDate = (bool) (isset($_REQUEST['useExpirationDate']) && $_REQUEST['useExpirationDate']);
                
                if ( $this->useExpirationDate )
                {
                    if ( isset($_REQUEST['course_expirationDate']) )
                    {
                        $this->expirationDate = trim(strip_tags($_REQUEST['course_expirationDate']));
                    }
                    elseif ( isset($_REQUEST['course_expirationYear'])
                        && isset($_REQUEST['course_expirationMonth'])
                        && isset($_REQUEST['course_expirationDay']) )
                    {
                        $this->expirationDate = mktime(
                            23,59,59,
                            $_REQUEST['course_expirationMonth'],
                            $_REQUEST['course_expirationDay'],
                            $_REQUEST['course_expirationYear'] );
                    }
                    else
                    {
                        $this->expirationDate = mktime(0,0,0);
                    }
                }
            }
            else
            {
                $this->status = 'enable';
            }
        }
        
        if (isset($_REQUEST['course_userLimit']) && $_REQUEST['course_userLimit'] > 0)
        {
            $this->userLimit = (int) $_REQUEST['course_userLimit'];
        }
        else
        {
            $this->userLimit = 0;
        }
    }
    
    
    /**
     * Validate data from object.  Error handling with a backlog object.
     *
     * @return boolean success
     */
    public function validate ()
    {
        $success = true ;
        
        /**
         * Configuration array , define here which field can be left empty or not
         */
        
        $fieldRequiredStateList['title'         ] = get_conf('human_label_needed');
        $fieldRequiredStateList['officialCode'  ] = get_conf('human_code_needed');
        $fieldRequiredStateList['titular'       ] = false;
        $fieldRequiredStateList['email'         ] = get_conf('course_email_needed');
        $fieldRequiredStateList['categories'    ] = false; // Can be left blank (no category associated)
        $fieldRequiredStateList['language'      ] = true;
        $fieldRequiredStateList['departmentName'] = get_conf('extLinkNameNeeded');
        $fieldRequiredStateList['extLinkUrl'    ] = get_conf('extLinkUrlNeeded');
        $fieldRequiredStateList['publicationDate'] = $this->status == 'date';
        $fieldRequiredStateList['expirationDate'] = $this->status == 'date' && $this->useExpirationDate;
        
        // Validate course access
        if ( empty($this->access) || ! in_array($this->access, array('public','private','platform')) )
        {
            $this->backlog->failure(get_lang('Missing or invalid course access'));
            $success = false ;
            
            if ( !$this->courseId
                && $this->access == 'public'
                && !( get_conf('allowPublicCourses', true) || claro_is_platform_admin() ) )
            {
                $this->backlog->failure(get_lang('You are not allowed to create a public course'));
                $success = false ;
            }
        }
        
        // Validate course title
        if ( empty($this->title) && $fieldRequiredStateList['title'] )
        {
            $this->backlog->failure(get_lang('Course title needed'));
            $success = false ;
        }
        
        // Validate course code
        if ( empty($this->officialCode) && $fieldRequiredStateList['officialCode'])
        {
            $this->backlog->failure(get_lang('Course code needed'));
            $success = false ;
        }
        
        // Check course length
        if( strlen($this->officialCode) > 40 )
        {
            $this->backlog->failure(get_lang('Course code too long'));
            $success = false;
        }
        
        // Validate email
        if ( empty($this->email) && $fieldRequiredStateList['email'])
        {
            $this->backlog->failure(get_lang('Email needed'));
            $success = false ;
        }
        else
        {
            if ( ! $this->validateEmailList() )
            {
                $this->backlog->failure(get_lang('The email address is not valid'));
                $success = false;
            }
        }
        
        // Validate course language
        if ( empty($this->language) && $fieldRequiredStateList['language'])
        {
            $this->backlog->failure(get_lang('Language needed'));
            $success = false ;
        }
        
        // Validate course departmentName
        if ( empty($this->departmentName) && $fieldRequiredStateList['departmentName'])
        {
            $this->backlog->failure(get_lang('Department needed'));
            $success = false ;
        }
        
        // Validate course extLinkUrl
        if ( empty($this->extLinkUrl) && $fieldRequiredStateList['extLinkUrl'])
        {
            $this->backlog->failure(get_lang('Department url needed'));
            $success = false ;
        }
        
        // Validate department url
        if ( ! $this->validateExtLinkUrl() )
        {
            $this->backlog->failure(get_lang('Department URL is not valid'));
            $success = false ;
        }
        
        // Validate course publication date
        if ( empty($this->publicationDate) && $fieldRequiredStateList['publicationDate'])
        {
            $this->backlog->failure(get_lang('Publication date needed'));
            $success = false ;
        }
        
        //TODO check expirationDate
        if ( empty($this->expirationDate) && $fieldRequiredStateList['expirationDate'])
        {
            $this->backlog->failure(get_lang('Expiration date needed'));
            $success = false ;
        }
        
        if ( !empty($this->expirationDate) && $fieldRequiredStateList['expirationDate'] )
        {
            if ( $this->publicationDate > $this->expirationDate )
            {
                $this->backlog->failure(get_lang('Publication date must precede expiration date'));
                $success = false ;
            }
        }
        
        // Validate categories
        foreach ($this->categories as $category)
        {
            if ( !get_conf ( 'clcrs_rootCategoryAllowed', true ) && $category->id == 0 && !claro_is_platform_admin() )
            {
                $this->backlog->failure(get_lang('You need to choose at least one category for this course'));
                $success = false ;
            }
            elseif ( !$category->canHaveCoursesChild && !claro_is_platform_admin() )
            {
                $this->backlog->failure(get_lang('The category <i>%category</i> can\'t contain courses', array('%category' => $category->name)));
                $success = false ;
            }
        }
        
        return $success;
    }
    
    
    /**
     * Validate url and try to repair it if no protocol specified.
     *
     * @return boolean success
     */
    protected function validateExtLinkUrl ()
    {
        if ( empty($this->extLinkUrl) ) return true;
        
        $regexp = "!^(http|https|ftp)\://[a-zA-Z0-9\.-]+\.[a-zA-Z0-9]{1,3}(:[a-zA-Z0-9]*)?/?([a-zA-Z0-9\._\?\,\'/\\\+&%\$#\=~-])*$!i";
        
        if ( ! preg_match($regexp,$this->extLinkUrl) )
        {
            // Problem with url. try to repair
            // if  it  only the protocol missing add http
            $fixed_url = 'http://' . $this->extLinkUrl;
            if ( preg_match($regexp, $fixed_url))
            {
                $this->extLinkUrl = $fixed_url;
            }
            else
            {
                 return false;
            }
        }
        
        return true;
    }
    
    
    /**
     * validate email ( and semi-column separated email list )
     *
     * @return boolean success
     */
    protected function validateEmailList ()
    {
        // empty email is valide as we already checked if field was required
        if( empty($this->email) ) return true;
        
        $emailControlList = strtr($this->email,', ',';');
        $emailControlList = preg_replace( '/;+/', ';', $emailControlList );
        
        $emailControlList = explode(';',$emailControlList);
        
        $emailValidList = array();
        
        foreach ( $emailControlList as $emailControl )
        {
            $emailControl = trim($emailControl);
            
            if ( ! is_well_formed_email_address( $emailControl ) )
            {
                return false;
            }
            else
            {
                $emailValidList[] = $emailControl;
            }
        }
        
        $this->email = implode(';',$emailValidList);
        return true;
    }
    
    
    /**
     * Display form
     *
     * @param $cancelUrl string url of the cancel button
     * @return string html output of form
     */
    public function displayForm ($cancelUrl=null)
    {
        JavascriptLoader::getInstance()->load('course_form');
        
        $languageList = get_language_to_display_list('availableLanguagesForCourses');
        $categoriesList = claroCategory::getAllCategoriesFlat();
        
        $linkedCategoriesListHtml   = ''; // Categories linked to the course
        $unlinkedCategoriesListHtml = ''; // Other categories (not linked to the course)
        foreach ( $categoriesList as $category )
        {
            // Is that category linked to the current course or not ?
            $match = false;
            foreach ( $this->categories as $searchCategory )
            {
                if ( $category['id'] == (int) $searchCategory->id )
                {
                    $match = true;
                    break;
                }
                else
                {
                    $match = false;
                }
            }
            
            // Dispatch in the lists
            if ( $match )
            {
                $linkedCategoriesListHtml .= '<option '
                    . (!$category['visible'] ? 'class="hidden" ' : '')
                    . 'value="'
                    . $category['id'] . '">' . $category['path']
                    . '</option>' . "\n";
            }
            else
            {
                if ($category['canHaveCoursesChild'] || claro_is_platform_admin())
                {
                    $unlinkedCategoriesListHtml .= '<option '
                        . (!$category['visible'] ? 'class="hidden" ' : '')
                        . 'value="'
                        . $category['id'] . '">' . $category['path']
                        . '</option>' . "\n";
                }
            }
        }
        
        $publicDisabled = !(get_conf('allowPublicCourses', true) || claro_is_platform_admin())
            ? ' disabled="disabled"'
            : '';
        
        $publicCssClass = !(get_conf('allowPublicCourses', true) || claro_is_platform_admin())
            ? ' class="notice"'
            : '';
        
        $publicMessage = $this->access != 'public' && !(get_conf('allowPublicCourses', true) || claro_is_platform_admin())
            ? '<br /><span class="notice">'
                . get_lang('If you need to create a public course, please contact the platform administrator')
                . '</span>'
            : '';
        
        $cancelUrl = is_null($cancelUrl) ?
            get_path('clarolineRepositoryWeb') . 'course/index.php?cid=' . claro_htmlspecialchars($this->courseId) :
            $cancelUrl;
        
        $template = new CoreTemplate('course_form.tpl.php');
        $template->assign('formAction', $_SERVER['PHP_SELF']);
        $template->assign('relayContext', claro_form_relay_context());
        $template->assign('course', $this);
        $template->assign('linkedCategoriesListHtml', $linkedCategoriesListHtml);
        $template->assign('unlinkedCategoriesListHtml', $unlinkedCategoriesListHtml);
        $template->assign('languageList', $languageList);
        $template->assign('publicDisabled', $publicDisabled);
        $template->assign('publicCssClass', $publicCssClass);
        $template->assign('publicMessage', $publicMessage);
        $template->assign('cancelUrl', $cancelUrl);
        $template->assign('nonRootCategoryRequired', !get_conf ( 'clcrs_rootCategoryAllowed', true ) );
        
        return $template->render();
    }
    
    
    /**
     * Display question of delete confirmation
     *
     * @param $cancelUrl string url of the cancel button
     * @return string html output of form
     */
    public function displayDeleteConfirmation ()
    {
        $paramString = $this->getHtmlParamList('GET');
        
        $deleteUrl = './settings.php?cmd=exDelete&amp;'.$paramString;
        $cancelUrl = './settings.php?'.$paramString ;
        
        $html = '';
        
        $html .= '<p>'
        .    '<font color="#CC0000">'
        .    get_lang('Deleting this course will permanently delete all its documents and unenroll all its students.')
        .    get_lang('Are you sure to delete the course "%course_name" ( %course_code ) ?', array('%course_name' => $this->title,
                                                                                                         '%course_code' => $this->officialCode ))
        .    '</font>'
        .    '</p>'
        .    '<p>'
        .    '<font color="#CC0000">'
        .    '<a href="'.$deleteUrl.'">'.get_lang('Yes').'</a>'
        .    '&nbsp;|&nbsp;'
        .    '<a href="'.$cancelUrl.'">'.get_lang('No').'</a>'
        .    '</font>'
        .    '</p>'
        ;
        
        return $html;
    }
    
    
    /**
     * Add html parameter to list
     *
     * @param $name string input name
     * @param $value string input value
     *
     *
     */
    public function addHtmlParam($name, $value)
    {
        $this->htmlParamList[$name] = $value;
    }
    
    
    /**
     * Get html representing parameter list depending on method (POST for form, GET for URL's')
     *
     * @param $method string GET OR POST
     * @return string html output of params for $method method
     */
    public function getHtmlParamList($method = 'GET')
    {
        if ( empty($this->htmlParamList) ) return '';
        
        $html = '';
        
        if ( $method == 'POST' )
        {
            foreach ( $this->htmlParamList as $name => $value )
            {
                $html .= '<input type="hidden" name="' . claro_htmlspecialchars($name) . '" value="' . claro_htmlspecialchars($value) . '" />' . "\n" ;
            }
        }
        else // GET
        {
            $params = array();
            foreach ( $this->htmlParamList as $name => $value )
            {
                $params[] = rawurlencode($name) . '=' . rawurlencode($value);
            }
            
            $html = implode('&amp;', $params );
        }
        
        return $html;
    }
    
    
    /**
     * Get visibility
     *
     * @param $access string
     * @param $registration string
     * @return integer value of visibility field
     *
     * @deprecated 1.9
     */
    public function getVisibility ( $access, $registration )
    {
        $visibility = 0 ;
        
        if     ( ! $access && ! $registration ) $visibility = 0;
        elseif ( ! $access &&   $registration ) $visibility = 1;
        elseif (   $access && ! $registration ) $visibility = 3;
        elseif (   $access &&   $registration ) $visibility = 2;
        
        return $visibility ;
    }
    
    
    /**
     * Get access value from visibility field
     *
     * @param $visbility integer value of field
     * @return boolean public true, private false
     */
    public function getAccess ( $visibility )
    {
        if ( $visibility >= 2 ) return true ;
        else                    return false ;
    }
    
    
    /**
     * Get registration value from visibility field
     *
     * @param $visbility integer value of field
     * @return boolean open true, close false
     */
    public function getRegistration ( $visibility )
    {
        if ( $visibility == 1 || $visibility == 2 ) return true ;
        else                                        return false;
    }
    
    
    /**
     * Courses are often identified through their code (sysCode).  This
     * method permits to easily get the code of a course based on
     * its identifier (integer).
     *
     * @param int       course identifier
     * @return string   course code (sysCode)
     * @since 1.10
     */
    public static function getCodeFromId ( $id )
    {
        // Declare needed tables
        $tbl_mdb_names              = claro_sql_get_main_tbl();
        $tbl_course                 = $tbl_mdb_names['course'];
        
        $sql = "SELECT c.code AS sysCode
                
                FROM `" . $tbl_course . "` AS c
                WHERE c.cours_id = " . (int) $id;
        
        if ($result = claro_sql_query_get_single_row($sql))
        {
            return $result['sysCode'];
        }
        else
        {
            return null;
        }
    }
    
    
    /**
     * Courses are often identified through their code (sysCode).  But
     * sometimes their identifier (integer) can be useful.  This
     * method permits to easily get the id of a course based on
     * its code.
     *
     * @param string    course code (sysCode)
     * @return int      course identifier
     * @since 1.10
     */
    public static function getIdFromCode ( $code )
    {
        // Declare needed tables
        $tbl_mdb_names              = claro_sql_get_main_tbl();
        $tbl_course                 = $tbl_mdb_names['course'];
        
        $sql = "SELECT c.cours_id AS id
                
                FROM `" . $tbl_course . "` AS c
                WHERE c.code = '" . $code . "'";
        
        if ($result = claro_sql_query_get_single_row($sql))
        {
            return $result['id'];
        }
        else
        {
            return null;
        }
    }
    
    
    /**
     * Send course creation information by mail to all platform administrators
     *
     * @param string creator firstName
     * @param string creator lastname
     * @param string creator email
     */
    public function mailAdministratorOnCourseCreation ($creatorFirstName, $creatorLastName, $creatorEmail)
    {
        $subject = get_lang('Course created : %course_name',array('%course_name'=> $this->title));
        
        $categoryCodeList = array();
        
        foreach ($this->categories as $category)
        {
            $categoryCodeList[] = $category->name;
        }
        
        $body = nl2br(get_block('blockCourseCreationEmailMessage', array( '%date' => claro_html_localised_date(get_locale('dateTimeFormatLong')),
                                '%sitename' => get_conf('siteName'),
                                '%user_firstname' => $creatorFirstName,
                                '%user_lastname' => $creatorLastName,
                                '%user_email' => $creatorEmail,
                                '%course_code' => $this->officialCode,
                                '%course_title' => $this->title,
                                '%course_lecturers' => $this->titular,
                                '%course_email' => $this->email,
                                '%course_categories' => (!empty($this->categories) ? implode(', ', $categoryCodeList) : get_lang('No category')),
                                '%course_language' => $this->language,
                                '%course_url' => get_path('rootWeb') . 'claroline/course/index.php?cid=' . claro_htmlspecialchars($this->courseId)) ) );
        
        // Get the concerned senders of the email
        $mailToUidList = claro_get_uid_of_system_notification_recipient();
        if(empty($mailToUidList)) $mailToUidList = claro_get_uid_of_platform_admin();
        
        $message = new MessageToSend(claro_get_current_user_id(),$subject,$body);
        
        $recipient = new UserListRecipient();
        $recipient->addUserIdList($mailToUidList);
        
        //$message->sendTo($recipient);
        $recipient->sendMessage($message);
        
    }
    
    
    /**
     * Build progress param url
     *
     * @return string url
     */
    public function buildProgressUrl ()
    {
        $url = $_SERVER['PHP_SELF'] . '?cmd=exEdit';
        
        $paramList = array();
        
        $paramList['course_isSourceCourse']     = $this->isSourceCourse;
        $paramList['course_sourceCourseId']     = $this->sourceCourseId;
        $paramList['course_title']              = $this->title;
        $paramList['course_officialCode']       = $this->officialCode;
        $paramList['course_titular']            = $this->titular;
        $paramList['course_email']              = $this->email;
        $paramList['linked_categories']         = serialize($this->categories); // Serialize array to put it into an URL
        $paramList['course_departmentName']     = $this->departmentName;
        $paramList['course_extLinkUrl']         = $this->extLinkUrl;
        $paramList['course_language']           = $this->language;
        $paramList['course_visibility']         = $this->visibility;
        $paramList['course_access']             = $this->access;
        $paramList['course_registration']       = $this->registration;
        $paramList['course_registrationKey']    = $this->registrationKey;
        $paramList['course_publicationDate']    = $this->publicationDate;
        $paramList['course_expirationDate']     = $this->expirationDate;
        $paramList['useExpirationDate']         = $this->useExpirationDate;
        $paramList['course_status']             = $this->status;
        $paramList['course_userLimit']          = $this->userLimit;
        
        $paramList = array_merge($paramList, $this->htmlParamList);
        
        foreach ($paramList as $key => $value)
        {
            $url .= '&amp;' . rawurlencode($key) . '=' . rawurlencode($value);
        }
        
        return $url;
    }
}