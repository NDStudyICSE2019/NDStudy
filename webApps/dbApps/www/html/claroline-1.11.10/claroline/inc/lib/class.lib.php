<?php // $Id: class.lib.php 14690 2014-02-13 11:12:49Z zefredz $

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * Library for class
 *
 * @version 1.9 $Revision: 14690 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author Claro Team <cvs@claroline.net>
 * @author Guillaume Lederer <guillaume@claroline.net>
 * @since 1.6
 */

require_once dirname(__FILE__) . '/user.lib.php' ;
require_once dirname(__FILE__) . '/course_user.lib.php' ;

/**
 * Get class data on the platform
 *
 * @param integer $classId
 *
 * @return  array( `id`, `name`, `class_parent_id`, `class_level`)
 */

function class_get_properties ( $classId )
{
    $tbl = claro_sql_get_main_tbl();

    $sql = "SELECT id,
                   name,
                   class_parent_id,
                   class_level
            FROM `" . $tbl['class'] . "`
            WHERE `id`= ". (int) $classId ;

    $result = claro_sql_query_get_single_row($sql);

    if ( $result ) return $result;
    else           return claro_failure::set_failure('class_not_found');
}

/**
 * Add a new class
 *
 * @param $className
 * @param $parentId
 *
 */

function class_create ( $className, $parentId )
{
    $tbl = claro_sql_get_main_tbl();

    $className = trim($className);
    $parentId = (int) $parentId;

    if ($parentId != 0)
    {
        $parent_class_properties = class_get_properties ( $parentId );
        $class_level = $parent_class_properties['class_level']+1;
       
    }
    else
    {
        $class_level= 1;
    }

    $sql = "INSERT INTO `" . $tbl['class'] . "`
            SET `name`='". claro_sql_escape($className) ."',
                `class_level`='".(int)$class_level."'";

    if ( $parentId != 0 )
    {
        $sql.=", `class_parent_id`= ". (int) $parentId;
    }
    return claro_sql_query($sql);
}

/**
 * Update class data
 * @param $classId integer
 * @param $className string
 * @param $parentId integer
 */

function class_set_properties ( $classId, $className, $parentId = 0 )
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tbl_class     = $tbl_mdb_names['class'];

    $className = trim($className);

    $sql = "UPDATE `". $tbl_class ."`
            SET name='". claro_sql_escape($className) ."'";

    if ( $parentId != 0 )
    {
        $sql.=", `class_parent_id`= ". (int) $parentId;
    }

    $sql .= "WHERE id= " . (int) $classId ;

    return claro_sql_query($sql);
}

/**
 * This function delete a class and all this trace
 *
 * @author Damien Garros <dgarros@univ-catholyon.fr>
 *
 * @param  int class_id
 *
 * @return true if everything is good or an error string
*/

function delete_class($class_id)
{
    

    $tbl_mdb_names      = claro_sql_get_main_tbl();
    $tbl_user           = $tbl_mdb_names['user'];
    $tbl_class_user     = $tbl_mdb_names['rel_class_user'];
    $tbl_course_class   = $tbl_mdb_names['rel_course_class'];
    $tbl_class          = $tbl_mdb_names['class'];
    $tbl_course         = $tbl_mdb_names['course'];

    // 1 - See if there is a class with such ID in the main DB

    $sql = "SELECT `id`
            FROM `" . $tbl_class . "`
            WHERE `id` = '" . $class_id . "' ";
    $result = claro_sql_query_fetch_all($sql);

    if ( !isset($result[0]['id']) )
    {
        return claro_failure::set_failure('class_not_found'); // the class doesn't exist
    }

    // 2 - check if class contains some children

    $sql = "SELECT count(id)
            FROM `" . $tbl_class . "`
            WHERE class_parent_id = " . (int) $class_id ;
    $has_children = (bool) claro_sql_query_get_single_value($sql);

    if ($has_children)
    {
        return claro_failure::set_failure('class_has_sub_classes'); // the class has sub classes
    }
    else
    {
        // 3 - Get the list of user and remove each from class

        $sql = "SELECT *
            FROM `".$tbl_class_user."` `rel_c_u`, `".$tbl_user."` `u`
            WHERE `class_id`='". (int) $class_id ."'
            AND `rel_c_u`.`user_id` = `u`.`user_id`";

        $userList = claro_sql_query_fetch_all($sql);

        // 4 - Get the list of course
        $sql = "SELECT *
            FROM `".$tbl_course_class."` `rel_c_c`, `".$tbl_course."` `c`
            WHERE `rel_c_c`.`classId`='". (int) $class_id ."'
            AND `rel_c_c`.`courseId` = `c`.`code`";

        $courseList = claro_sql_query_fetch_all($sql);

        // Unsuscribe each user to each course

        foreach ($userList as $user)
        {
            foreach ($courseList as $course)
            {
                user_remove_from_course($user['user_id'], $course['code'], false , true);
            }
        }

        // Clean the class table
        $sql = "DELETE FROM `" . $tbl_class . "`
                WHERE id = " . (int) $class_id ;

        claro_sql_query($sql);

        // Clean the rel_course_class
        $sql = "DELETE FROM `" . $tbl_course_class . "`
                WHERE classId = " . (int) $class_id ;

        claro_sql_query($sql);

        // Clean the rel_class_user
        $sql = "DELETE FROM `" . $tbl_class_user . "`
                WHERE class_id = " . (int) $class_id;
        claro_sql_query($sql);

        return true;
    }
}

/**
 * This function move a class in the class tree
 *
 * @author Damien Garros <dgarros@univ-catholyon.fr>
 *
 * @param  int class_id , id of the class what you want to move
 * @param  int class_id_towards, id of the parent destination class
 *
 * @return true if everything is good or an error string
 **/

function move_class($class_id, $class_id_towards)
{
    $tbl_mdb_names      = claro_sql_get_main_tbl();
    $tbl_user           = $tbl_mdb_names['user'];
    $tbl_class_user     = $tbl_mdb_names['rel_class_user'];
    $tbl_class          = $tbl_mdb_names['class'];

    // 1 - Check if $class_id is different with $move_class_id
    if ($class_id == $class_id_towards)
    {
        return claro_failure::set_failure('move_same_class');
    }

    // 2 - Check if $class_id and $moved_class_id are in the main DB

    $sql = "SELECT `id`,`class_parent_id`
            FROM `" . $tbl_class . "`
            WHERE `id` = '" . (int) $class_id . "' ";

    $result = claro_sql_query_fetch_all($sql);

    if ( !isset($result[0]['id']))
    {
        return claro_failure::set_failure('class_not_found'); // the class doesn't exist
    }

    if ( $class_id_towards != 0 )
    {
    	 $sql = "SELECT `id`, `class_level`
                FROM `" . $tbl_class . "`
                WHERE `id` = '" .(int) $class_id_towards . "' ";
        $result = claro_sql_query_fetch_all($sql);

        if ( !isset($result[0]['id']))
        {
            return claro_failure::set_failure('class_not_found'); // the parent class doesn't exist
        }
        else
        {
            $class_level = $result[0]['class_level'] +1;
        }
    }
    else
    {
        //if $class_id_parent is root
        $class_id_towards = "NULL";
        $class_level = 1;
    }

    //Move class
    $sql_update="UPDATE `" . $tbl_class . "`
                 SET class_parent_id= " . $class_id_towards . ",
                 class_level = ". $class_level ."
                 WHERE id= " . (int) $class_id;
    claro_sql_query($sql_update);

    //Get user list
    $sql = "SELECT *
        FROM `".$tbl_class_user."` `rel_c_u`, `".$tbl_user."` `u`
        WHERE `class_id`='". (int) $class_id ."'
        AND `rel_c_u`.`user_id` = `u`.`user_id`";

    $userList = claro_sql_query_fetch_all($sql);

    //suscribe each user to parent class
    foreach($userList as $user)
    {
        user_add_to_class($user['user_id'],$class_id_towards);
    }

    return true ;
}

/**
 * helper to register a class (and recursively register all its subclasses) to a course
 * @param Claro_Class $claroClass
 * @param Claro_Course $courseObj
 * @param Claro_BatchRegistrationResult $result
 * @return Claro_BatchRegistrationResult
 * @since Claroline 1.11.9
 */
function object_register_class_to_course( $claroClass, $courseObj, $result )
{
    if ( ! $claroClass->isRegisteredToCourse ( $courseObj->courseId ) )
    {
        $claroClass->registerToCourse( $courseObj->courseId );

        $claroClassUserList = new Claro_ClassUserList( $claroClass );

        $courseUserList = new Claro_BatchCourseRegistration($courseObj, Claroline::getDatabase (), $result );

        $userAlreadyInClass = $claroClassUserList->getClassUserIdList( true );

        $courseUserList->addUserIdListToCourse( 
            $claroClassUserList->getClassUserIdList (), 
            $claroClass, 
            true, 
            $userAlreadyInClass,
            true );
        
        if ( $claroClass->hasSubclasses () )
        {
            pushClaroMessage("Class has subclass",'debug');
            
            foreach ( $claroClass->getSubClassesIterator() as $subClass )
            {
                pushClaroMessage("Process subclass{$subClass->getName()}",'debug');
                $result = object_register_class_to_course( $subClass, $courseObj, $result );
            }
        }
        else
        {
            pushClaroMessage("Class has no subclass",'debug');
        }

        return $result;
    }
    else
    {
        // already registered
        return $result;
    }
}

/**
 * helper to register a class to a course
 *
 * @param integer $class_id
 * @param string $course_code
 * @return Claro_BatchRegistrationResult
 */
function register_class_to_course($class_id, $course_code)
{
    $courseObj = new Claro_Course( $course_code );
    $courseObj->load();
    
    $claroClass = new Claro_Class();
    $claroClass->load( $class_id );
    
    $result = new Claro_BatchRegistrationResult();
    
    return object_register_class_to_course( $claroClass, $courseObj, $result );
}

/**
 * helper to unregister a class (and recursively unregister all its subclasses) from a course
 * @param Claro_Class $claroClass
 * @param Claro_Course $courseObj
 * @param Claro_BatchRegistrationResult $result
 * @return Claro_BatchRegistrationResult
 * @since Claroline 1.11.9
 */
function object_unregister_class_from_course( $claroClass, $courseObj, $result )
{
    if ( $claroClass->isRegisteredToCourse ( $courseObj->courseId ) )
    {
        $classUserIdList = $claroClass->getClassUserList()->getClassUserIdList();

        $courseBatchRegistretion = new Claro_BatchCourseRegistration( $courseObj );

        $courseBatchRegistretion->removeUserIdListFromCourse( $classUserIdList, $claroClass );

        if ( $claroClass->hasSubclasses () )
        {
            pushClaroMessage("Class has subclass",'debug');
            // recursion !
            foreach ( $claroClass->getSubClassesIterator() as $subClass )
            {
                pushClaroMessage("Process subclass{$subClass->getName()}",'debug');
                $result = object_unregister_class_from_course( $subClass, $courseObj, $result );
            }
        }
        else
        {
            pushClaroMessage("Class has no subclass",'debug');
        }
        
        $claroClass->unregisterFromCourse( $courseObj->courseId );

        return $result;
    }
    else
    {
        return $result;
    }
}

/**
 * helper to unregister a class from course
 *
 * @author Damien Garros <dgarros@univ-catholyon.fr>
 *
 * @param int class_id
 * @param string course_code
 *
 * @return Claro_BatchRegistrationResult
 */

function unregister_class_to_course($class_id, $course_code)
{
    $claroClass = new Claro_Class( Claroline::getDatabase() );
    $claroClass->load( $class_id );

    $courseObj = new Claro_Course( $course_code );
    $courseObj->load();
    
    $result = new Claro_BatchRegistrationResult();
    
    return object_unregister_class_from_course( $claroClass, $courseObj, $result );
}

/**
 * subscribe a specific user to a class
 *
 * @author Guillaume Lederer < guillaume@claroline.net >
 *
 * @param int $user_id user ID from the course_user table
 * @param int $class_id class id from the class table
 *
 * @return boolean TRUE  if subscribtion succeed
 *         boolean FALSE otherwise.
 */

function user_add_to_class($user_id,$class_id)
{
    $user_id  = (int)$user_id;
    $class_id = (int)$class_id;

    // get database information

    $tbl_mdb_names       = claro_sql_get_main_tbl();
    $tbl_rel_class_user  = $tbl_mdb_names['rel_class_user'];
    $tbl_class           = $tbl_mdb_names['class'];
    $tbl_course_class    = $tbl_mdb_names['rel_course_class'];
    $tbl_course          = $tbl_mdb_names['course'];

    // 1. See if there is a user with such ID in the main database

    $user_data = user_get_properties($user_id);

    if ( !$user_data )
    {
        return claro_failure::get_last_failure('USER_NOT_FOUND');
    }

    // 2. See if there is a class with such ID in the main DB

    $sql = "SELECT `class_parent_id`
            FROM `" . $tbl_class . "`
            WHERE `id` = '" . $class_id . "' ";
    $result = claro_sql_query_get_single_row($sql);

    if ( count($result) == 0 )
    {
        return claro_failure::set_failure('CLASS_NOT_FOUND'); // the class doesn't exist
    }

    // 3. See if user is not already in class

    $sql = "SELECT `user_id`
            FROM `" . $tbl_rel_class_user . "`
            WHERE `user_id` = '" . $user_id . "'
            AND `class_id` = '" . $class_id . "'";
    $handle = claro_sql_query($sql);

    if ( mysql_num_rows($handle) > 0 )
    {
        return claro_failure::set_failure('USER_ALREADY_IN_CLASS'); // the user is already subscrided to the class
    }

    // 4. Add user to class in the rel_class_user table

    $sql = "INSERT INTO `" . $tbl_rel_class_user . "`
            SET `user_id` = '" . $user_id . "',
               `class_id` = '" . $class_id . "' ";
    claro_sql_query($sql);

    // 5. Add user to each course whose link with class

    $sql = "SELECT `c`.`code`
            FROM `".$tbl_course_class."` `cc`, `".$tbl_course."` `c`
            WHERE `cc`.`courseId` = `c`.`code`
            AND `cc`.`classId` = " . $class_id ;

    $courseList = claro_sql_query_fetch_all($sql);

    foreach ( $courseList as $course )
    {
        //check if every think is good
        if( !user_add_to_course($user_id, $course['code'], false, false, $class_id) )
        {
            return claro_failure::set_failure('PROBLEM_WITH_COURSE_SUBSCRIBE');
            //TODO : ameliorer la  gestion d'erreur ...
        }
    }

      return true;
}

/**
 * unsubscribe a specific user to a class
 *
 * @author damien Garros <dgarros@univ-catholyon.fr>
 *
 * @param int $user_id user ID from the course_user table
 * @param int $class_id course code from the class table
 *
 * @return boolean TRUE  if subscribtion succeed
 *         boolean FALSE otherwise.
 */

function user_remove_to_class($user_id,$class_id)
{
    $user_id  = (int)$user_id;
      $class_id = (int)$class_id;

      $tbl_mdb_names     = claro_sql_get_main_tbl();
      $tbl_class         = $tbl_mdb_names['class'];
      $tbl_course_class  = $tbl_mdb_names['rel_course_class'];
      $tbl_course        = $tbl_mdb_names['course'];
      $tbl_class_user    = $tbl_mdb_names['rel_class_user'];

      // 1. See if there is a user with such ID in the main database

    $user_data = user_get_properties($user_id);

      if ( !$user_data )
      {
          return claro_failure::get_last_failure('USER_NOT_FOUND');
      }

      // 2. See if there is a class with such ID in the main DB

      $sql = "SELECT `id`
              FROM `" . $tbl_class . "`
              WHERE `id` = '" . $class_id . "' ";
      $result = claro_sql_query_fetch_all($sql);

      if ( !isset($result[0]['id']))
      {
          return claro_failure::set_failure('CLASS_NOT_FOUND'); // the class doesn't exist
      }

      // 3 - Check if user is subscribe to class and if class exist

    $sql = "SELECT  cu.id
              FROM `".$tbl_class_user."` cu, `".$tbl_class."` c
              WHERE cu.`class_id` = c.`id`
              AND cu.`class_id` = ". (int)$class_id."
              AND cu.`user_id` = ". (int)$user_id;

      if ( is_null(claro_sql_query_get_single_value($sql)))
      {
          return claro_failure::set_failure('USER_NOT_SUSCRIBE_TO_CLASS');
      }

      // 4 - Get the child class from this class and call the fonction recursively

    $sql =" SELECT `id`
              FROM `".$tbl_class."`
              WHERE `class_parent_id` = ". $class_id;

      $classList = claro_sql_query_fetch_all($sql);

      foreach ($classList as $class)
      {
          if ( isset($class['id']) )
          {
              user_remove_to_class($user_id, $class['id']);
              //TODO Bug tracking ? !
          }
      }

      //3 - remove user to class in rel_class_user

      $sql = "DELETE FROM `".$tbl_class_user."`
              WHERE `user_id` = ". (int) $user_id."
              AND `class_id` = ". (int) $class_id;

      claro_sql_query($sql);

      //4 - Get the list of course whose link with class and unsubscribe user for each

    $sql = "SELECT c.`code`
              FROM `".$tbl_course_class."` cc, `".$tbl_course."` c
              WHERE cc.`courseId` = c.`code`
              AND cc.`classId` = ".$class_id;

      $courseList = claro_sql_query_fetch_all($sql);

    foreach ($courseList as $course)
    {
            if (isset($course['code']))
            {
                    //Check the return value of the function.
                    if ( !user_remove_from_course($user_id, $course['code'], false, false, $class_id) )
                    {
                            return claro_failure::set_failure('PROBLEM_WITH_COURSE_UNSUSCRIBTION');
                            //TODO : ameliorer la detection d'erreur
                    }
            }
    }
    return true;
}

/**
 * unsubscribe all users of the class
 *
 * @author damien Garros <dgarros@univ-catholyon.fr>
 *
 * @param int $class_id course code from the class table
 *
 * @return boolean TRUE  if subscribtion succeed
 *         boolean FALSE otherwise.
 */

function class_remove_all_users ($classId)
{
      $tbl_mdb_names     = claro_sql_get_main_tbl();
      $tbl_class_user    = $tbl_mdb_names['rel_class_user'];

    $sql = "SELECT user_id
            FROM `" . $tbl_class_user . "` AS `rel_c_u`
                    WHERE `class_id`= " . (int) $classId ;

    $userList = claro_sql_query_fetch_all($sql);

    foreach ( $userList as $user )
    {
        $userId = $user['user_id'];
        user_remove_to_class($userId,$classId);
    }

    return true ;
}

/**
 * Display the tree of classes
 *
 * @param unknown_type $class_list list of all the classes informations of the platform
 * @param unknown_type $parent_class
 * @param unknown_type $deep
 * @return unknown
 */

function display_tree_class_in_admin ($class_list, $parent_class = null, $deep = 0)
{
    // Global variables needed


    $html_form = '';

    foreach ( $class_list as $cur_class )
    {

        if ( ( $parent_class == $cur_class['class_parent_id']) )
        {

            // Set space characters to add in name display

            $blankspace = '&nbsp;&nbsp;&nbsp;';

            for ( $i = 0; $i < $deep; $i++ )
            {
                $blankspace .= '&nbsp;&nbsp;&nbsp;';
            }

            // See if current class to display has children

            $has_children = FALSE;

            foreach ($class_list as $search_parent)
            {
                if ($cur_class['id'] == $search_parent['class_parent_id'])
                {
                    $has_children = TRUE;
                    break;
                }
            }

            //Set link to open or close current class

            if ($has_children)
            {
                if (isset($_SESSION['admin_visible_class'][$cur_class['id']]) && $_SESSION['admin_visible_class'][$cur_class['id']]=="open")
                {
                    $open_close_link = '<a href="' . $_SERVER['PHP_SELF'] . '?cmd=exClose&amp;class_id=' . $cur_class['id'] . '">' . "\n"
                    .                  '<img src="' . get_icon_url('collapse') . '" alt="" />' . "\n"
                    .                  '</a>' . "\n"
                    ;
                }
                else
                {
                    $open_close_link = '<a href="' . $_SERVER['PHP_SELF'] . '?cmd=exOpen&amp;class_id=' . $cur_class['id'] . '">' . "\n"
                    .                  '<img src="' . get_icon_url('expand') . '" alt="" />' . "\n"
                    .                  '</a>' . "\n"
                    ;
                }
            }
            else
            {
                $open_close_link = ' &deg; ';
            }

            // DISPLAY CURRENT ELEMENT (CLASS)

            //Name
            $qty_user = get_class_user_number($cur_class['id']);
            $qty_cours = get_class_cours_number($cur_class['id']);

            $html_form .= '<tr>' . "\n"
                .    '<td>' . "\n"
                .    '    ' . $blankspace . $open_close_link . ' ' . $cur_class['name']
                .    '</td>' . "\n"
                .    '<td align="center">' . "\n"
                .    '<a href="' . get_path('clarolineRepositoryWeb') . 'admin/admin_class_user.php?class_id=' . $cur_class['id'] . '">' . "\n"
                .    '<img src="' . get_icon_url('user') . '" alt="' . get_lang('User') . '" />' . "\n"
                .    '(' . $qty_user . '  ' . get_lang('UsersMin') . ')' . "\n"
                .    '</a>' . "\n"
                .    '</td>' . "\n"
                .    '<td align="center">' . "\n"
                  .    '<a href="'.get_path('clarolineRepositoryWeb').'admin/admin_class_cours.php?class_id='.$cur_class['id'].'">' . "\n"
                  .    '<img src="' . get_icon_url('course') . '" alt="' . get_lang('Course') . '" /> '
                  .    '('.$qty_cours.'  '.get_lang('Course').') ' . "\n"
                  .    '</a>' . "\n"
                  .    '</td>' . "\n"
                .    '<td align="center">' . "\n"
                .    '<a href="' . $_SERVER['PHP_SELF'] . '?cmd=rqEdit&amp;class_id=' . $cur_class['id'] . '">' . "\n"
                .    '<img src="' . get_icon_url('edit') . '" alt="' . get_lang('Edit') . '" />' . "\n"
                .    '</a>' . "\n"
                .    '</td>' . "\n"
                .    '<td align="center">' . "\n"
                .    '<a href="' . $_SERVER['PHP_SELF'] . '?cmd=rqMove&amp;class_id=' . $cur_class['id'] . '&class_name=' . $cur_class['name'] . '">' . "\n"
                .    '<img src="' . get_icon_url('move') . '" alt="' . get_lang('Move') . '" />' . "\n"
                .    '</a>' . "\n"
                .    '</td>' . "\n"
                .    '<td align="center">' . "\n"
                .    '<a href="' . $_SERVER['PHP_SELF'] . '?cmd=exDelete&amp;class_id=' . $cur_class['id'] . '"'
                .    ' onclick="return ADMIN.confirmationDel(\'' . clean_str_for_javascript($cur_class['name']) . '\');">' . "\n"
                .    '<img src="' . get_icon_url('delete') . '" alt="' . get_lang('Delete') . '" />' . "\n"
                .    '</a>' . "\n"
                .    '</td>' . "\n"
                .    '</tr>' . "\n"
                ;

            // RECURSIVE CALL TO DISPLAY CHILDREN

            if ( isset($_SESSION['admin_visible_class'][$cur_class['id']]) && ($_SESSION['admin_visible_class'][$cur_class['id']]=="open") )
            {
                $html_form .= display_tree_class_in_admin($class_list, $cur_class['id'], $deep+1);
            }
        }
    }

    return $html_form ;
}

/**
 * Get the number of users in a class, including sublclasses
 *
 * @author Guillaume Lederer
 * @param  id of the (parent) class ffrom which we want to know the number of users
 * @return (int) number of users in this class and its subclasses
 *
 */

function get_class_user_number($class_id)
{
    $tbl_mdb_names  = claro_sql_get_main_tbl();
    $tbl_class_user = $tbl_mdb_names['rel_class_user'];
    $tbl_class      = $tbl_mdb_names['class'];
    //1- get class users number

    $sqlcount = "SELECT COUNT(`user_id`) AS qty_user
                 FROM `" . $tbl_class_user . "`
                 WHERE `class_id`=" . (int) $class_id;

    $qty_user =  claro_sql_query_get_single_value($sqlcount);

    $sql = "SELECT `id`
            FROM `" . $tbl_class . "`
            WHERE `class_parent_id`=" . (int) $class_id;

    $subClassesList = claro_sql_query_fetch_all($sql);

    //2- recursive call to get subclasses'users too

    foreach ($subClassesList as $subClass)
    {
        $qty_user += get_class_user_number($subClass['id']);
    }

    //3- return result of counts and recursive calls

    return $qty_user;
}

/**
 * Get the number of cours link with class
 *
 * @author Damien Garros <dgarros@univ-catholyon.fr>
 *
 * @param  id of the class from which we want to know the number of cours
 *
 * @return (int) number of cours in this class
 *
*/

function get_class_cours_number($class_id)
{
    $tbl_mdb_names   = claro_sql_get_main_tbl();
    $tbl_course_class = $tbl_mdb_names['rel_course_class'];

    // 1- get class users number

    $sqlcount = " SELECT COUNT(`courseId`) AS qty_cours
                  FROM `".$tbl_course_class ."`
                  WHERE `classId`='" . (int)$class_id . "'";

    $resultcount = claro_sql_query_fetch_all($sqlcount);

    $qty_cours = $resultcount[0]['qty_cours'];

    return $qty_cours;
}

/**
 * Display the tree of classes
 *
 * @author Guillaume Lederer
 * @param  list of all the classes informations of the platform
 * @param  list of the classes that must be visible
 * @return
 *
 * @see
 *
 */

function display_tree_class_in_user($class_list, $course_code, $parent_class = null, $deep = 0)
{

    $tbl_mdb_names  = claro_sql_get_main_tbl();

    $tbl_course       = $tbl_mdb_names['course'];

    $html_form = '';

    // Get the course id with cours code
    $sql = "SELECT `C`.`cours_id`
            FROM `" . $tbl_course . "` as C
            WHERE `code` = '".$course_code."'";

    claro_sql_query_get_single_value($sql);

    foreach ($class_list as $cur_class)
    {
        if (($parent_class==$cur_class['class_parent_id']))
        {
            // Set space characters to add in name display

            $blankspace = '&nbsp;&nbsp;&nbsp;';
            for ($i = 0; $i < $deep; $i++)
            {
                $blankspace .= '&nbsp;&nbsp;&nbsp;';
            }

            // See if current class to display has children

            $has_children = FALSE;
            foreach ($class_list as $search_parent)
            {
                if ($cur_class['id'] == $search_parent['class_parent_id'])
                {
                    $has_children = TRUE;
                    break;
                }
            }

            // Set link to open or close current class

            if ($has_children)
            {
                if (isset($_SESSION['class_add_visible_class'][$cur_class['id']]) && $_SESSION['class_add_visible_class'][$cur_class['id']]=="open")
                {
                    $open_close_link = '<a href="' . $_SERVER['PHP_SELF']
                    .                  '?cmd=exClose&amp;class_id=' . $cur_class['id'] . '">' . "\n"
                    .                  '<img src="' . get_icon_url('collapse') . '" alt="" />' . "\n"
                    .                  '</a>' . "\n"
                    ;
                }
                else
                {
                    $open_close_link = '<a href="' . $_SERVER['PHP_SELF'] . '?cmd=exOpen&amp;class_id=' . $cur_class['id'] . '">' . "\n"
                    .                  '<img src="' . get_icon_url('expand') . '" alt="" />' . "\n"
                    .                  '</a>' . "\n"
                    ;
                }
            }
            else
            {
                $open_close_link = ' &deg; ';
            }

            // Display current class

            $qty_user = get_class_user_number($cur_class['id']) ; // Need some optimisation here ...

            $html_form .= '<tr>' . "\n"
            .    '<td>' . "\n"
            .    $blankspace.$open_close_link." ".$cur_class['name']
            .    '</td>' . "\n"
            .    '<td align="center">' . "\n"
            .    $qty_user . '  ' . get_lang('UsersMin')
            .    '</td>' . "\n"
            .    '<td align="center">' . "\n" ;

            if ( empty($cur_class['course_id']) )
            {
                $html_form .= '<a href="' . $_SERVER['PHP_SELF'] . '?cmd=exEnrol&amp;class_id=' . $cur_class['id'] . '"'
                .    ' onclick="return confirmation_enrol(\'' . clean_str_for_javascript($cur_class['name']) . '\');">'
                .    '<img src="' . get_icon_url('enroll') . '" alt="' . get_lang('Enrol to course') . '" />' . "\n"
                .    '</a>' . "\n";
            }
            else
            {
                $html_form .= '<a href="' . $_SERVER['PHP_SELF'] . '?cmd=exUnenrol&amp;class_id=' . $cur_class['id'] . '"'
                .    ' onclick="return confirmation_unenrol(\'' . clean_str_for_javascript($cur_class['name']) . '\');">'
                .    '<img src="' . get_icon_url('unenroll') . '" alt="' . get_lang('Unenrol from course') . '" />' . "\n"
                .    '</a>' . "\n";
            }

            $html_form .= '</td>' . "\n"
            .    '</tr>' . "\n"
            ;
            // RECURSIVE CALL TO DISPLAY CHILDREN

            if ( isset($_SESSION['class_add_visible_class'][$cur_class['id']]) && ($_SESSION['class_add_visible_class'][$cur_class['id']]=='open'))
            {
                $html_form .= display_tree_class_in_user($class_list, $course_code, $cur_class['id'], $deep+1);
            }
        }
    }
    return $html_form;
}


/**
 * This function create the select box to choose the parent class
 *
 * @param  the pre-selected class'id in the select box
 * @param  space to display for children to show deepness
 * @global $tbl_class
 * @global get_lang('TopLevel')
 * @return void
*/

function displaySelectBox($selected=null,$space="&nbsp;&nbsp;&nbsp;")
{
    $tbl_mdb_names  = claro_sql_get_main_tbl();
    $tbl_class      = $tbl_mdb_names['class'];

    $sql = " SELECT *
             FROM `" . $tbl_class . "`
             ORDER BY `name`";
    $classes = claro_sql_query_fetch_all($sql);

    $result = '<select name="class_parent_id">' . "\n"
    .         '<option value="0">' . get_lang('Root') . '</option>';
    $result .= buildSelectClass($classes,$selected,null,$space);
    $result .= '</select>' . "\n";
    return $result;
}

/**
 * This function create the list for the select box to choose the parent class
 *
 * @author Guillaume Lederer
 * @param  tab containing at least all the classes with their id, parent_id and name
 * @param  parent_id of the class we want to display the children of
 * @param  the pre-selected class'id in the select box
 * @param  space to display for children to show deepness
 * @return string to output
 *
*/
function buildSelectClass($classes,$selected,$father=null,$space="&nbsp;&nbsp;&nbsp;")
{
    $result = '';
    if($classes)
    {
        foreach($classes as $one_class)
        {
            //echo $one_class["class_parent_id"]." versus ".$father."<br>";

            if($one_class['class_parent_id']==$father)
            {
                $result .= '<option value="'.$one_class['id'].'" ';
                if ($one_class['id'] == $selected)
                {
                    $result .= 'selected ';
                }
                $result .= '> '.$space.$one_class['name'].' </option>'."\n";
                $result .=  buildSelectClass($classes,$selected,$one_class['id'],$space.'&nbsp;&nbsp;&nbsp;');
            }
        }
    }
    return $result;
}

/**
 * return subClass of a given class
 *
 * @param unknown_type $class_id
 * @return unknown
 *
 * @since 1.8.0
 */
function getSubClasses($class_id)
{
    $tbl = claro_sql_get_main_tbl();

    $sub_classes_list = array();

    $sql = "SELECT `id`
            FROM `" . $tbl['class'] . "`
            WHERE `class_parent_id`=" . (int) $class_id;

    $query_result = claro_sql_query($sql);

    while ( ( $this_sub_class = mysql_fetch_array($query_result) ) )
    {
        // add this subclass id to array
        $sub_classes_list[] = $this_sub_class['id'];
        // add children of this subclass id to array
        $this_sub_classes_list = getSubClasses($this_sub_class['id']);
        $sub_classes_list = array_merge($this_sub_classes_list,$sub_classes_list);
    }

    return $sub_classes_list;
}


/**
 * return list of class.
 *
 * @since 1.11
 * @return array(`id`,`name`,`class_parent_id`,`course_id`)
 */

function get_class_list()
{
    $tbl = claro_sql_get_main_tbl();
    
    $sql = "SELECT id,
                   class_parent_id,
                   name
            FROM `" . $tbl['class'] . "`
            ORDER BY `name`";
    
    return claro_sql_query_fetch_all($sql);
}


/**
 * return list of class subscribed to a given course.
 *
 * @param string $courseId
 * @since 1.8.1
 * @return array(`id`,`name`,`class_parent_id`,`course_id`)
 */

function get_class_list_by_course($courseId)
{
    $tbl = claro_sql_get_main_tbl();

    $sql = "
        SELECT C.id              AS `id`,
               C.name            AS `name`,
               C.class_parent_id AS `class_parent_id`,
               CC.courseId       AS `course_id`
        FROM `" . $tbl['class'] . "` C
        LEFT JOIN `" . $tbl['rel_course_class'] . "` CC
               ON CC.`classId` = C.`id`
              AND CC.`courseId` = '" . claro_sql_escape($courseId) . "'
        ORDER BY C.`name`";
    
    return claro_sql_query_fetch_all($sql);
}

/**
 * return list of class subscribed to a given course.
 * similar to get_class_list_by_course($courseId) except that
 * this function return only the enrolled class to the course
 *
 * @param string $courseId
 * @since 1.9.1
 * @return array(`id`,`name`,`class_parent_id`)
 */
function get_class_list_of_course($courseId)
{
    $tbl = claro_sql_get_main_tbl();

    $sql = "
        SELECT C.id              AS `id`,
               C.name            AS `name`,
               C.class_parent_id AS `class_parent_id`
        FROM `" . $tbl['class'] . "` C
        LEFT JOIN `" . $tbl['rel_course_class'] . "` CC
               ON CC.`classId` = C.`id`
        WHERE CC.`courseId` = '" . claro_sql_escape($courseId) . "'
        ORDER BY C.`name`";
    
    return claro_sql_query_fetch_all($sql);
}

/**
 * return list of user_id of the users enrolled in a class.
 *
 * @param string $classId
 * @since 1.9.1
 */

function get_class_list_user_id_list($classId)
{
    $classIdList = implode(', ',$classId);
    
    $tbl = claro_sql_get_main_tbl();
    

     $sql = "
        SELECT DISTINCT user_id
        FROM `" .  $tbl['rel_class_user'] . "`
        WHERE `class_id`
            in (" . $classIdList . ")";
     
    $classMemberListUserId = claro_sql_query_fetch_all($sql);
    
    $userIdList = array();
    foreach ($classMemberListUserId as $UserId)
    {
        $userIdList[] = $UserId['user_id'];
    }
    
    return $userIdList;
}

/**
 * return class list for user_id
 *
 * @param integer $userId
 * @return array
 */
function get_class_list_for_user_id($userId)
{
    $tbl = claro_sql_get_main_tbl();
    
    $sql = "
        SELECT DISTINCT c.id    AS id,
                        c.name  AS name
        FROM `" .  $tbl['rel_class_user'] . "` AS cr
        JOIN `" .  $tbl['class'] . "` AS c
        ON c.id = cr.class_id
        WHERE cr.user_id
            in (" . $userId . ")";
    $resultClassList = claro_sql_query_fetch_all($sql);
    
    $classList = array();
    
    foreach ($resultClassList as $class)
    {
        $classList[] = array('id' => $class['id'], 'name' => $class['name']);
    }
    
    return $classList;
}

/**
 * This function delete all classes,
 * remove link between courses and classes
 * remove link between classes and users
 * delete related users from related courses
 *
 */
function delete_all_classes()
{
    $tbl = claro_sql_get_main_tbl();
    
    $sql = "
        SELECT id FROM `" . $tbl['class'] . "` ORDER BY class_level DESC";
    $searchResultList = claro_sql_query_fetch_all($sql);
    foreach ($searchResultList  as $thisClass)
    {
        $classId = $thisClass['id'];
        // find all the students enrolled in that class
        $sql2 = "
            SELECT user_id from `" . $tbl['rel_class_user'] . "`
            WHERE class_id = '" . claro_sql_escape($classId) . "'";
        $thisClassUser = claro_sql_query_fetch_all($sql2);
         
        // Find all the courses to whom the class is enrolled
        $sql2 = "
            SELECT courseId
            FROM `" . $tbl['rel_course_class'] . "`
            WHERE classId = '" . claro_sql_escape($classId) . "'";
        $searchResultList2 = claro_sql_query_fetch_all($sql2);
        foreach ($searchResultList2  as $thisCourse)
        {
            $courseCode = $thisCourse['courseId'];
            foreach ($thisClassUser as $thisUser)
            {
                 $user_id = $thisUser['user_id'];
                if ( !user_remove_from_course($user_id, $courseCode, false, false, $classId) )
                {
                    return claro_failure::set_failure('PROBLEM_WITH_COURSE_UNSUSCRIBTION');
                            //TODO : ameliorer la detection d'erreur
                }
            }
        }
         delete_class($classId);
    }
    return true;
}

/**
 * This function empties all classes,
 * remove link between classes and users
 * delete related users from related courses
 */
function empty_all_class()
{
    $tbl = claro_sql_get_main_tbl();
    
    $sql = "
        SELECT id FROM `" . $tbl['class'] . "`";
    $searchResultList = claro_sql_query_fetch_all($sql);
    foreach ($searchResultList  as $thisClass)
    {
        $classId = $thisClass['id'];
        // find all the students enrolled in that class
        $sql2 = "
            SELECT user_id from `" . $tbl['rel_class_user'] . "`
            WHERE class_id = '" . claro_sql_escape($classId) . "'";
        $thisClassUser = claro_sql_query_fetch_all($sql2);
         
        // Find all the courses to whom the class is enrolled
        $sql2 = "
            SELECT courseId
            FROM `" . $tbl['rel_course_class'] . "`
            WHERE classId = '" . claro_sql_escape($classId) . "'";
        $searchResultList2 = claro_sql_query_fetch_all($sql2);
        foreach ($searchResultList2  as $thisCourse)
        {
            $courseCode = $thisCourse['courseId'];
            foreach ($thisClassUser as $thisUser)
            {
                $user_id = $thisUser['user_id'];
                if ( !user_remove_from_course($user_id, $courseCode, false, false, $classId) )
                {
                    return claro_failure::set_failure('PROBLEM_WITH_COURSE_UNSUSCRIBTION ' . $user_id . ' '. $courseCode);
                    //TODO : ameliorer la detection d'erreur
                }
            }
            
        }
        class_remove_all_users ($classId);
    }

    return true;
}

function class_exist ()
{
    $tbl = claro_sql_get_main_tbl();
    $sql = "
        SELECT id FROM `" . $tbl['class'] . "`";
    return claro_sql_query_fetch_all($sql);
}