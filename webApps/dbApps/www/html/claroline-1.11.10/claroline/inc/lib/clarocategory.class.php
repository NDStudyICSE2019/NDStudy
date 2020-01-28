<?php // $Id: clarocategory.class.php 14314 2012-11-07 09:09:19Z zefredz $

/**
 * CLAROLINE
 *
 * ClaroCategory Class
 *
 * @version $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author Claro Team <cvs@claroline.net>
 * @author Antonin Bourguignon <antonin.bourguignon@claroline.net>
 * @since 1.10
 */


require_once dirname(__FILE__) . '/backlog.class.php'; // Manage the backlog entries
require_once dirname(__FILE__) . '/category.lib.inc.php'; // Contains all MySQL requests for this class
require_once dirname(__FILE__) . '/claroCourse.class.php';
require_once dirname(__FILE__) . '/course.lib.inc.php'; // Contains certain usefull functions for this class: claro_get_lang_flat_list(), ...
require_once dirname(__FILE__) . '/../../messaging/lib/message/messagetosend.lib.php';

class ClaroCategory
{
    // Identifier
    public $id;
    
    // Name
    public $name;
    
    // Code
    public $code;
    
    // Identifier of the parent category
    public $parentId;
    
    // Position in the tree's level
    public $rank;
    
    // Visibility
    public $visible;
    
    // Allowed to possess children (true = yes, false = no)
    public $canHaveCoursesChild;
    
    // Dedicated course (identifier of the course)
    public $rootCourse;
    
    // Backlog object
    public $backlog;
    
    
    /**
     * Constructor
     */
    public function __construct ($id = null, $name = null, $code = null, $parentId = null, $rank = null, $visible = 1, $canHaveCoursesChild = 1, $rootCourse = null)
    {
        $this->id                   = $id;
        $this->name                 = $name;
        $this->code                 = $code;
        $this->idParent             = $parentId;
        $this->rank                 = $rank;
        $this->visible              = $visible;
        $this->canHaveCoursesChild  = $canHaveCoursesChild;
        $this->rootCourse           = $rootCourse;
        $this->backlog              = new Backlog();
    }
    
    
    /**
     * Load category data from database in the current object.
     *
     * @param int       category identifier
     * @return bool     success
     */
    public function load ($id)
    {
        $data = claro_get_cat_datas($id);
        
        if ( !$data )
        {
            claro_failure::set_failure('category_not_found');
            return false;
        }
        else
        {
            $this->id                   = $id;
            $this->name                 = $data['name'];
            $this->code                 = $data['code'];
            $this->idParent             = $data['idParent'];
            $this->rank                 = $data['rank'];
            $this->visible              = $data['visible'];
            $this->canHaveCoursesChild  = $data['canHaveCoursesChild'] == '0' ? false : true;
            $this->rootCourse           = $data['rootCourse'];
            
            return true;
        }
    }
    
    
    /**
     * Insert or update current category data.
     *
     * @return bool     success
     */
    public function save ()
    {
        $canHaveCourseChild = $this->canHaveCoursesChild ? 1 : 0;
        
        if ( empty($this->id) )
        {
            // No id: it's a new category -> insert
            if( claro_insert_cat_datas($this->name, $this->code, $this->idParent, $this->rank, $this->visible, $canHaveCourseChild, $this->rootCourse) )
                return true;
            else
            {
                claro_failure::set_failure('category_not_saved');
                return false;
            }
        }
        else
        {
            // No id: it's a new category -> update
            if( claro_update_cat_datas($this->id, $this->name, $this->code, $this->idParent, $this->rank, $this->visible, $canHaveCourseChild, $this->rootCourse) )
                return true;
            else
            {
                claro_failure::set_failure('category_not_saved');
                return false;
            }
        }
    }
    
    
    /**
     * Delete datas of a category and unlinks all courses linked to it.
     *
     * @return bool     success
     */
    public function delete ()
    {
        if ( claro_delete_cat_datas($this->id) )
            return true;
        else
            return false;
    }
    
    
    /**
     * Get the complete path of a category.
     *
     * @return string   path.
     * @example echo getPath($categoryId, $categoriesList) will show "Category A > Category B > Category C"
     */
    public static function getPath ( $categoryId, $categoriesList = null, $separator = ' > ' )
    {
        if ( is_null($categoriesList) )
        {
            $categoriesList = self::getAllCategories();
        }
        
        if ( !get_conf( 'clcrs_displayShortCategoryPath', false ) )
        {
            $path   = null;
            $findId = $categoryId;

            while ( !is_null($findId) && $findId != 0 )
            {
                foreach ( $categoriesList as $category )
                {
                    if ( $category['id'] == $findId )
                    {
                        $path = $category['name'] . ((!is_null($path))?(' ' . $separator . ' ' . $path):(null));
                        $findId = $category['idParent'];
                        break;
                    }
                }
            }

            return $path;
        }
        else
        {
            $path   = null;
            $findId = $categoryId;
            
            $thisCategoryName = '';

            while ( !is_null($findId) && $findId != 0 )
            {
                foreach ( $categoriesList as $category )
                {
                    if ( $category['id'] == $categoryId )
                    {
                        $thisCategoryName = $category['name'];
                    }
                    
                    if ( $category['id'] == $findId )
                    {
                        $path = $category['code'] . ((!is_null($path))?(' ' . $separator . ' ' . $path):(null));
                        $findId = $category['idParent'];
                        break;
                    }
                }
            }
            
            $path = "({$path}) {$thisCategoryName}";

            return $path;
        }
    }
    
    
    /**
     * Select categories in database for a specific parent.
     *
     * @param int       $parentId the parent from wich we want to get the categories
     * @param bool          $visibility (1 = only visible, 0 = only invisible, null = all; default: null)
     * @return array    collection of categories ordered by rank
     */
    public static function getCategories ( $parentId, $visibility = null )
    {
        return claro_get_categories($parentId, $visibility);
    }
    
    
    /**
     * Select all categories in database from a certain point, ordered by rank. Use the attribute "level" for
     * display purpose.  For instance: echo str_repeat('&nbsp;', 4*$category['level']) . $category['name'];
     *
     * @param int           $start_node the parent from wich we want to get the categories tree (default: 0)
     * @param int           $start_level the level where we start (default: 0)
     * @param bool          $visibility (1 = only visible, 0 = only invisible, null = all; default: null)
     * @return iterator     containing all the categories organized hierarchically and ordered by rank
     */
    public static function getAllCategories ( $start_node = 0, $start_level = 0, $visibility = null )
    {
        return claro_get_all_categories($start_node, $start_level, $visibility);
    }
    
    
    /**
     * Select all categories in database and give an explicit path for each of them.
     *
     * @param string    $separator (default: ' > ')
     * @return array    collection of categories (id and path), ordered by rank
     */
    public static function getAllCategoriesFlat ( $separator = ' > ' )
    {
        $categoryList = self::getAllCategories();
        
        return ClaroCategory::flatCategoryList($categoryList, $separator);
    }
    
    
    /**
     * Return a list of categories associated to a list of courses
     *
     * @param array     list of courses
     * @return array    list of categories associated to courses
     */
    public static function getCoursesCategories ( $courses )
    {
        // Get table name
        $tbl_mdb_names              = claro_sql_get_main_tbl();
        $tbl_category               = $tbl_mdb_names['category'];
        $tbl_rel_course_category    = $tbl_mdb_names['rel_course_category'];
        
        if (!empty($courses))
        {
            // Isolate courses ids
            $coursesIds = array();
            foreach ($courses as $course)
            {
                $coursesIds[] = $course['courseId'];
            }
            
            $coursesIds = implode(', ', $coursesIds);
            
            $sql = "SELECT rcc.courseId, c.id AS categoryId, c.name, c.code,
                    c.rank, c.visible, rcc.rootCourse
                    FROM `" . $tbl_category . "` AS c
                    
                    LEFT JOIN `" . $tbl_rel_course_category . "` AS rcc
                    ON c.id = rcc.categoryId
                    
                    WHERE rcc.courseId IN ({$coursesIds})";
            
            if (get_conf('categories_order_by', 'rank') == 'rank')
            {
                $sql .= "
                    ORDER BY c.`rank`";
            }
            elseif  (get_conf('categories_order_by') == 'alpha_asc')
            {
                $sql .= "
                    ORDER BY c.`name` ASC";
            }
            else
            {
                $sql .= "
                    ORDER BY c.`name` DESC";
            }
            
            $result = Claroline::getDatabase()->query($sql);
            
            // Copy the result into an array
            $categories = array();
            foreach ($result as $category)
            {
                $categories[] = $category;
            }
            
            return $categories;
        }
        else
        {
            return array();
        }
        
    }


    /**
     * Return a list of categories associated to a list of courses
     *
     * @param int user id
     * @return array list of categories associated to the user
     */
    public static function getUserCategoriesFlat ( $userId, $separator = '>' )
    {
        // Get table name
        $tbl_mdb_names              = claro_sql_get_main_tbl();
        $tbl_course                 = $tbl_mdb_names['course'];
        $tbl_rel_course_user        = $tbl_mdb_names['rel_course_user'];
        $tbl_category               = $tbl_mdb_names['category'];
        $tbl_rel_course_category    = $tbl_mdb_names['rel_course_category'];

        $sql = "SELECT ca.id, 
                       ca.name, 
                       ca.visible, 
                       ca.canHaveCoursesChild,
                       ca.idParent

                FROM `{$tbl_category}` AS ca

                JOIN `{$tbl_rel_course_category}` AS rcc
                ON ca.id = rcc.categoryId

                JOIN `{$tbl_course}` AS co
                ON rcc.courseId = co.cours_id

                JOIN `{$tbl_rel_course_user}` AS rcu
                ON rcu.code_cours = co.code

                WHERE rcu.user_id = {$userId}

                GROUP BY ca.id";

        $result = Claroline::getDatabase()->query($sql);
        $result->setFetchMode(Mysql_ResultSet::FETCH_ASSOC);

        return ClaroCategory::flatCategoryList($result, $separator);
    }


    /**
     * Return a list of categories associated to a list of courses
     *
     * @param int user id
     * @return Database_ResultSet list of categories associated to the user
     */
    public static function getUserCategories ( $userId, $separator = '>' )
    {
        // Get table name
        $tbl_mdb_names              = claro_sql_get_main_tbl();
        $tbl_course                 = $tbl_mdb_names['course'];
        $tbl_rel_course_user        = $tbl_mdb_names['rel_course_user'];
        $tbl_category               = $tbl_mdb_names['category'];
        $tbl_rel_course_category    = $tbl_mdb_names['rel_course_category'];

        $sql = "SELECT ca.id, 
                       ca.name, 
                       ca.visible, 
                       ca.canHaveCoursesChild,
                       ca.idParent

                FROM `{$tbl_category}` AS ca

                JOIN `{$tbl_rel_course_category}` AS rcc
                ON ca.id = rcc.categoryId

                JOIN `{$tbl_course}` AS co
                ON rcc.courseId = co.cours_id

                JOIN `{$tbl_rel_course_user}` AS rcu
                ON rcu.code_cours = co.code

                WHERE rcu.user_id = {$userId}

                GROUP BY ca.id";

        $result = Claroline::getDatabase()->query($sql);
        $result->setFetchMode(Mysql_ResultSet::FETCH_ASSOC);

        return $result;
    }
    
    
    /**
     * Turn an array of categories into an array of "flat" categories 
     * (each array entry contains the whole path to a category).
     *
     * @return array
     */
    public static function flatCategoryList($categoryList, $separator)
    {
        $flatList = array();
        foreach ($categoryList as $category)
        {
            $flatList[] = array(
                'id' =>     $category['id'],
                'canHaveCoursesChild' => $category['canHaveCoursesChild'],
                'visible' => $category['visible'],
                'path' =>   self::getPath($category['id'], $categoryList, $separator)
            );
        }
        
        return $flatList;
    }
    
    
    /**
     * Count the number of courses for the specified category
     * (not recursive: only works on one level of the tree).
     * The count does NOT include root courses.
     *
     * @return int      number of courses
     */
    public static function countCourses ($id)
    {
        return claro_count_courses($id);
    }
    
    
    /**
     * Count the number of courses for the specified category
     * and all its sub categories (recursivly).
     * The count does NOT include root courses.
     *
     * @return int      number of courses
     */
    public static function countAllCourses ($id)
    {
        return claro_count_all_courses($id);
    }
    
    
    /**
     * Count the number of sub categories of the current category
     * (not recursive: only works on one level of the tree).
     *
     * @return int      number of sub categories
     */
    public static function countSubCategories ($id)
    {
        return claro_count_sub_categories($id);
    }
    
    
    /**
     * Count the number of sub categories of the current category (recursivly).
     *
     * @return int      number of sub categories
     */
    public static function countAllSubCategories ($id)
    {
        return claro_count_all_sub_categories($id);
    }
    
    
    /**
     * Check if a user is registered to a category.
     *
     * @param int       identifier of the user
     * @param int       identifier of the category
     * @return bool     user is registered to category
     */
    public static function isRegistredToCategory ($userId, $categoryId)
    {
        //TODO make it recursive
        if (is_null($userId) || is_null($categoryId))
        {
            claro_failure::set_failure('missing_user_or_category_id');
            return false;
        }
        else
        {
            // Get table name
            $tbl_mdb_names              = claro_sql_get_main_tbl();
            $tbl_course                 = $tbl_mdb_names['course'];
            $tbl_rel_course_category    = $tbl_mdb_names['rel_course_category'];
            $tbl_rel_course_user        = $tbl_mdb_names['rel_course_user'];
            
            $sql = "SELECT rcu.code_cours AS sysCode
                    FROM `" . $tbl_rel_course_user . "` AS rcu
                    
                    LEFT JOIN `" . $tbl_course . "` AS co
                    ON co.code = rcu.code_cours
                    
                    LEFT JOIN `" . $tbl_rel_course_category . "` AS rcc
                    ON rcc.courseId = co.cours_id
                    
                    WHERE rcu.user_id = " . (int) $userId . "
                    AND rcc.categoryId = " . (int) $categoryId . "
                    AND rcc.rootCourse = 1";
            
            $result = Claroline::getDatabase()->query($sql);
            $sysCode = $result->fetch(Database_ResultSet::FETCH_VALUE);
            
            if(!empty($sysCode))
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }
    
    
    /**
     * Swap the visibility value of a category (from TRUE to FALSE or
     * from FALSE to TRUE) and save it into the database.
     *
     * @return boo      success
     */
    public function swapVisibility ()
    {
        $this->visible = !$this->visible;
        
        if ( claro_set_cat_visibility($this->id, $this->visible) )
            return true;
        else
            return false;
    }
    
    
    /**
     * Exchange category's position with previous category of the same level.
     *
     * @return bool     success
     */
    public function decreaseRank ()
    {
        // Get the id of the previous category (if any)
        $idSwapCategory = claro_get_previous_cat_datas($this->rank, $this->idParent);
        if (!empty($idSwapCategory))
        {
            $this->exchangeRanks($idSwapCategory);
            return true;
        }
        else
        {
            claro_failure::set_failure('category_no_predecessor');
            return false;
        }
    }
    
    
    /**
     * Exchange category's position with following category of the same level.
     *
     * @return bool     success
     */
    public function increaseRank ()
    {
        // Get the id of the following category (if any)
        $idSwapCategory = claro_get_following_cat_datas($this->rank, $this->idParent);
        if (!empty($idSwapCategory))
        {
            $this->exchangeRanks($idSwapCategory);
            return true;
        }
        else
        {
            claro_failure::set_failure('category_no_successor');
            return false;
        }
    }
    
    
    /**
     * Exchange ranks between the current category and another one
     * and save the modification in database.
     *
     * @param int       identifier of the other category
     */
    public function exchangeRanks ($id)
    {
        // Get the other category
        $swapCategory = new claroCategory();
        $swapCategory->load($id);
        
        // Exchange the ranks
        $tempRank = $this->rank;
        $this->rank = $swapCategory->rank;
        $swapCategory->rank = $tempRank;
        
        // Save the modifications
        $this->save();
        $swapCategory->save();
    }
    
    
    /**
     * Check if the code of the category is unique (doesn't already
     * exists in database).
     *
     * @return bool     result: TRUE if the code is unique, FALSE if it's not
     */
    public function checkUniqueCode ()
    {
        if ( claro_count_code($this->id, $this->code) == 0 )
            return true;
        else
            return false;
    }
    
    
    /**
     * Check if the specified category is a child of the current category.
     *
     * @param int       identifier of the category we want to check
     * @return bool     result: TRUE if the specified category is the child
     *                  of the current category
     */
    public function checkIsChild ($id)
    {
         $ids = claro_get_parents_ids($id);
          
        if ( in_array($this->id, $ids) )
            return true;
        else
            return false;
    }
    
    
    /**
     * Retrieve category data from form and fill current category with it.
     */
    public function handleForm ()
    {
        if ( isset($_REQUEST['category_id']) )                  $this->id = trim(strip_tags($_REQUEST['category_id']));
        if ( isset($_REQUEST['category_name']) )                $this->name = trim(strip_tags($_REQUEST['category_name']));
        
        if ( isset($_REQUEST['category_code']) ) // Only capital letters and numbers
        {
            $this->code = trim(strip_tags($_REQUEST['category_code']));
            $this->code = preg_replace('/[^A-Za-z0-9_]/', '', $this->code);
            $this->code = strtoupper($this->code);
        }
        
        if ( isset($_REQUEST['category_parent']) )              $this->idParent = trim(strip_tags($_REQUEST['category_parent']));
        
        if ( isset($_REQUEST['category_rank']) )                $this->rank = trim(strip_tags($_REQUEST['category_rank']));
               
        if ( isset($_REQUEST['category_visible']) )             $this->visible = trim(strip_tags($_REQUEST['category_visible']));
        if ( isset($_REQUEST['category_can_have_courses']) )    $this->canHaveCoursesChild = trim(strip_tags($_REQUEST['category_can_have_courses']));
        if ( isset($_REQUEST['category_root_course']) )         $this->rootCourse = trim(strip_tags($_REQUEST['category_root_course']));
    }
    
    
    /**
     * Validate data from current object.  Error handling with
     * a backlog object.
     *
     * @return bool     success
     */
    public function validate ()
    {
        //TODO don't get how this function actually works
        
        $success = true ;
        
        // Configuration array, define here which field can be left empty or not
        //TODO make it more accurate using function get_conf('human_label_needed');
        $fieldRequiredStateList['name']                 = true;
        $fieldRequiredStateList['code']                 = true;
        $fieldRequiredStateList['idParent']             = true;
        $fieldRequiredStateList['rank']                 = false;
        $fieldRequiredStateList['visible']              = true;
        $fieldRequiredStateList['canHaveCoursesChild']  = true;
        
        // Validate category name
        if ( is_null($this->name) && $fieldRequiredStateList['name'] )
        {
            claro_failure::set_failure('category_missing_field_name');
            $this->backlog->failure(get_lang('Category name is required'));
            $success = false ;
        }
        
        // Validate category code
        if ( is_null($this->code) && $fieldRequiredStateList['code'] )
        {
            claro_failure::set_failure('category_missing_field_code');
            $this->backlog->failure(get_lang('Category code needed'));
            $success = false ;
        }
        
        // Check if the code is unique
        if ( !$this->checkUniqueCode() )
        {
            claro_failure::set_failure('category_duplicate_code');
               $this->backlog->failure(get_lang('This category already exists'));
            $success = false ;
        }
        
        // Validate parent identifier
        if ( is_null($this->idParent) && $fieldRequiredStateList['idParent'] )
        {
            claro_failure::set_failure('category_missing_field_idParent');
            $this->backlog->failure(get_lang('Category parent needed'));
            $success = false ;
        }
        
        // Category can't be its own parent
        if ( $this->idParent == $this->id )
        {
            claro_failure::set_failure('category_self_linked');
               $this->backlog->failure(get_lang('Category can\'t be its own parent'));
            $success = false ;
        }
        
        // Category can't be linked to one of its own children
        if ( $this->checkIsChild($this->idParent) )
        {
            claro_failure::set_failure('category_child_linked');
               $this->backlog->failure(get_lang('Category can\'t be linked to one of its own children'));
            $success = false ;
        }
        
        // Check authorisation to possess courses
        if ( is_null($this->visible) && $fieldRequiredStateList['visible'] )
        {
            claro_failure::set_failure('category_missing_field_visible');
            $this->backlog->failure(get_lang('Visibility of the category must be set'));
            $success = false;
        }
        
        // Check authorisation to possess courses
        if ( is_null($this->canHaveCoursesChild) && $fieldRequiredStateList['canHaveCoursesChild'] )
        {
            claro_failure::set_failure('category_missing_field_canHaveCoursesChild');
            $this->backlog->failure(get_lang('Category must be authorized or not to have courses children'));
            $success = false;
        }
        
        return $success;
    }
    
    
    /**
     * Display form.
     *
     * @param string        $cancelUrl url of the cancel button
     * @return string       html output of form
     */
    public function displayForm ($cancelUrl=null)
    {
        $languageList   = claro_get_lang_flat_list();
        $categoriesList = self::getAllCategories();
        $coursesList    = isset($this->id) ? (claroCourse::getAllCourses($this->id)) : (array());
        
        // Generate HTML options list for categories
        $categoriesHtmlList = '<option value="0">' . get_lang("None") . '</option>';
        $disabled   = false;
        $tempLevel  = null;
        foreach ( $categoriesList as $elmt )
        {
            // Enable/disable elements in the drop down list
            if ( !empty($elmt['id']) && $elmt['id'] == $this->id )
            {
                $disabled = true;
                $tempLevel = $elmt['level'];
            }
            elseif ( isset($tempLevel) && $elmt['level'] > $tempLevel )
            {
                $disabled = true;
            }
            else
            {
                $disabled = false;
                $tempLevel = null;
            }
            
            $categoriesHtmlList .= '<option value="' . $elmt['id'] . '" ' . ( ( !empty($elmt['id']) && $elmt['id'] == $this->idParent ) ? ('selected="selected"') : ('') ) . ( ( $disabled ) ? ('disabled="disabled"') : ('') ) . '>' . str_repeat('&nbsp;', 4*$elmt['level']) . $elmt['name'] . ' (' . $elmt['code'] . ') </option>';
        }
        
        // Generate HTML options list for courses
        $coursesHtmlList = '<option value="0">' . get_lang("None") . '</option>';
        foreach ( $coursesList as $elmt )
        {
            // Session courses can't become category courses
            if (is_null($elmt['sourceCourseId']))
            {
                $coursesHtmlList .= '<option value="' . $elmt['id'] . '" '
                                  . ( ( !empty($elmt['id']) && $elmt['id'] == $this->rootCourse ) ? ('selected="selected"') : ('') ) . '>'
                                  . $elmt['title'] . ' (' . $elmt['sysCode'] . ')</option>';
            }
        }
        
        // TODO use a template
        
        if ( is_null($cancelUrl) )
            $cancelUrl = get_path('clarolineRepositoryWeb') . 'course/index.php?cid=' . claro_htmlspecialchars($this->id);
        
        $html = '';
        
        $html .= '<form method="post" id="categorySettings" action="' . $_SERVER['PHP_SELF'] . '" >' . "\n"
            . claro_form_relay_context()
            . '<input type="hidden" name="cmd" value="' . (empty($this->id)?'exAdd':'exEdit') . '" />' . "\n"
            . '<input type="hidden" name="claroFormId" value="' . uniqid('') . '" />' . "\n";
        
        $html .= '<fieldset>' . "\n"
            . '<dl>' . "\n";
            
        // Category identifier
        $html .= '<input type="hidden" name="category_id" value="' . $this->id . '" />' . "\n";
        
        // Category name
        $html .= '<dt>'
            . '<label for="category_name">'
            . get_lang('Category name')
            . (get_conf('human_label_needed') ? '<span class="required">*</span> ':'')
            .'</label></dt>'
            . '<dd>'
            . '<input type="text" name="category_name" id="category_name" value="' . claro_htmlspecialchars($this->name) . '" size="30" maxlength="100" />'
            . (empty($this->id) ? '<br /><span class="notice">'.get_lang('e.g. <em>Sciences of Economics</em>').'</span>':'')
            . '</dd>' . "\n" ;
        
        // Category code
        $html .= '<dt>'
            . '<label for="category_code">'
            . get_lang('Category code')
            . '<span class="required">*</span> '
            . '</label></dt>'
            . '<dd><input type="text" id="category_code" name="category_code" value="' . claro_htmlspecialchars($this->code) . '" size="30" maxlength="12" />'
            . (empty($this->id) ? '<br /><span class="notice">'.get_lang('max. 12 characters, e.g. <em>ROM2121</em>').'</span>':'')
            . '</dd>' . "\n" ;
        
        // Category's parent
        $html .= '<dt>'
            . '<label for="category_parent">'
            . get_lang('Parent category')
            . '</label></dt>'
            . '<dd>'
            . '<select  id="category_parent" name="category_parent" />'
            . $categoriesHtmlList
            . '</select>'
            . '</dd>' . "\n" ;
        
        // Category's rank
        $html .= '<input type="hidden" name="category_rank" value="' . (empty($this->rank)?0:$this->rank) . '" />'."\n";
        
        // Category's visibility
        $html .= '<dt>'
            . get_lang('Category visibility')
            . '<span class="required">*</span>'
            . '</dt>'
            . '<dd>'
            . '<input type="radio" id="visible" name="category_visible" value="1" ' . (( $this->visible == 1 || !isset($this->visible) ) ? 'checked="checked"' : null ) . ' />'
            . '&nbsp;'
            . '<label for="visible">' . get_lang('Visible') . '</label><br />'
            . '<input type="radio" id="hidden" name="category_visible" value="0" ' . (( $this->visible == 0 && isset($this->visible) ) ? 'checked="checked"' : null ) . ' />'
            . '&nbsp;'
            . '<label for="hidden">' . get_lang('Hidden') . '</label>'
            . '</dd>' . "\n" ;
        
        // Category's right to possess courses
        $html .= '<dt>'
            . get_lang('Can have courses')
            . '<span class="required">*</span>'
            . '</dt>'
            . '<dd>'
            . '<input type="radio" id="can_have_courses" name="category_can_have_courses" value="1" ' . (( $this->canHaveCoursesChild == 1 || !isset($this->canHaveCoursesChild) ) ? 'checked="checked"':'' ) . ' />'
            . '&nbsp;'
            . '<label for="can_have_courses">' . get_lang('Yes') . '</label><br />'
            . '<input type="radio" id="cant_have_courses" name="category_can_have_courses" value="0" ' . (( $this->canHaveCoursesChild == 0 && isset($this->canHaveCoursesChild) ) ? 'checked="checked"':'' ) . ' />'
            . '&nbsp;'
            . '<label for="cant_have_courses">' . get_lang('No') . '</label><br />'
            . '<span class="notice">'.get_lang('Authorize the category to possess courses or not (opened or closed category)').'</span>'
            . '</dd>' . "\n" ;
            
        // Category's dedicated course/board
        $html .= '<dt>'
            . '<label for="category_root_course">'
            . get_lang('Category\'s board')
            . '</label></dt>'
            . '<dd>'
            . '<select  id="category_root_course" name="category_root_course" />'
            . $coursesHtmlList
            . '</select><br />'
            . '<span class="notice">'.get_lang('Dedicate a course to this category.  The course has to be linked to the category first.').'</span>'
            . '</dd>' . "\n" ;
            
        // Form's footer
        $html .= '</dl></fieldset>' . "\n"
            . '<span class="required">*</span>&nbsp;'.get_lang('Denotes required fields') . '<br />' . "\n"
            . '<input type="submit" value="' . get_lang('Ok') . '" />' . "\n"
            . claro_html_button($_SERVER['PHP_SELF'], get_lang('Cancel'))
            . '</form>' . "\n";
        
        return $html;
    }
    
    
    public function __toString()
    {
        return "[{$this->code}] {$this->name}";
    }

}