<?php // $Id: csvimport.class.php 14516 2013-08-14 07:46:57Z zefredz $

/**
 * CLAROLINE
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @author Sebastien Piraux
 */


class CsvImport extends parseCSV
{
    /*
    static public $defaultFields = array( 'userId'
                                        , 'lastname'
                                        , 'firstname'
                                        , 'username'
                                        , 'email'
                                        , 'officialCode'
                                        , 'groupId'
                                        , 'groupName' );
    static public $defaultEnclosure = '"';
    static public $defaultSeparator = ",";
    
    public $fields;
    
    public function __construct ( $fields = null , $enclosure = null , $separator = null )
    {
        $this->fields = $fields ? $fields : self::$defaultFields;
        $this->separator = $separator ? $separator : self::$defaultSeparator;
        $this->enclosure = $enclosure ? $enclosure : self::$defaultEnclosure;
        
        parent::__construct();
    }*/
    
    static public $requiredFields = array( 'lastname'
                                          ,'firstname'
                                          ,'username'
                                          ,'email' );
    
    public function importUsers( $userList = array(), $class_id, $updateUserProperties, $sendEmail = 0 )
    {
        if( empty( $this->data ) )
        {
            return false;
        }
        
        if( empty( $userList ) )
        {
            return false;
        }
        
        $logs = array();
        
        $tbl_mdb_names  = claro_sql_get_main_tbl();
        $tbl_user       = $tbl_mdb_names['user'];
        $tbl_course_user = $tbl_mdb_names['rel_course_user'];
        
        $tbl_cdb_names = claro_sql_get_course_tbl();
        $tbl_group_rel_team_user     = $tbl_cdb_names['group_rel_team_user'];
        
        $groupsImported = array();
        $userInfo = array();
        
        foreach( $userList as $user_id )
        {
            if(!isset($this->data[$user_id]))
            {
                $logs['errors'][] = get_lang('Unable to find the user in the csv');
            }
            else
            {
                $userInfo['username'] = $this->data[$user_id]['username'];
                $userInfo['firstname'] = $this->data[$user_id]['firstname'];
                $userInfo['lastname'] = $this->data[$user_id]['lastname'];
                $userInfo['email'] = isset( $this->data[$user_id]['email'] )
                                     && ! empty( $this->data[$user_id]['email'] )
                                     ? $this->data[$user_id]['email'] : '';
                $userInfo['password'] = isset( $this->data[$user_id]['password'] )
                                     && ! empty( $this->data[$user_id]['password'] )
                                     ? $this->data[$user_id]['password'] : mk_password( 8 );
                $userInfo['officialCode'] = isset( $this->data[$user_id]['officialCode'] ) ? $this->data[$user_id]['officialCode'] : '';
                
                //check user existe if not create is asked
                $resultSearch = user_search( array( 'username' => $userInfo['username'] ), null, true, true );
                if( !empty($resultSearch))
                {
                    $userId = $resultSearch[0]['uid'];
                    if (get_conf('update_user_properties') && $updateUserProperties)
                    {
                       //  never update password
                       unset($userInfo['password']);
                      
                        if (user_set_properties($userId, $userInfo))
                        $logs['success'][] = get_lang( 'User profile %username updated successfully', array( '%username' => $userInfo['username'] ) );
                        if ( $sendEmail )
                        {
                            user_send_registration_mail ($userId, $userInfo);
                        }
                    }
                    else
                    {
                        $logs['errors'][] = get_lang( 'User %username not created because it already exists in the database', array( '%username' => $userInfo['username'] ) );
                    }
                }
                else
                {
                    $userId = user_create( $userInfo );
                    if( $userId != 0 )
                    {
                        $newUserInfo = user_get_properties($userId);
                        if ($newUserInfo['username'] != $userInfo['username'])
                        {
                            // if the username fixed is the csv file is too long -> get correct one before sending
                            $userInfo['username'] = $newUserInfo['username'];
                        }
                        $logs['success'][] = get_lang( 'User %username created successfully', array( '%username' => $userInfo['username'] ) );
                        if ( $sendEmail )
                        {
                            user_send_registration_mail ($userId, $userInfo);
                        }
                    }
                    else
                    {
                        $logs['errors'][] = get_lang( 'Unable to create user %username', array('%username' => $userInfo['username'] ) );
                    }
                }
                
                if( $userId )
                {
                  //join class if needed
                  if( $class_id )
                  {
                    if( ! $return = user_add_to_class( $userId, $class_id ) )
                    {
                      $logs['errors'][] = get_lang( 'Unable to add %username in the selected class', array( '%username' => $userInfo['username'] ) );
                    }
                    else
                    {
                      $logs['success'][] = get_lang( 'User %username added in the selected class', array( '%username' => $userInfo['username'] ) );
                    }
                  }
                }
            }
        }
        
        return $logs;
    }
    
    
    /**
     * Import users in course.
     *
     * @author Dimitri Rambout <dimitri.rambout@gmail.com>
     * @param $courseId id of the course
     *
     * @return boolean
     */
    public function importUsersInCourse( $userList, $courseId, $canCreateUser = true, $enrollUserInCourse = true, $class_id = 0, $sendEmail = 0 )
    {
        if( empty( $this->data ) )
        {
            return false;
        }
        
        if( !(isset($userList) && count($userList) ) )
        {
            return false;
        }
        
        $logs = array();
        
        $tbl_mdb_names  = claro_sql_get_main_tbl();
        $tbl_user       = $tbl_mdb_names['user'];
        $tbl_course_user = $tbl_mdb_names['rel_course_user'];
        
        $tbl_cdb_names = claro_sql_get_course_tbl();
        $tbl_group_rel_team_user     = $tbl_cdb_names['group_rel_team_user'];
        
        $groupsImported = array();
        $userInfo = array();
        
        foreach( $userList as $user_id )
        {
            if( !isset( $this->data[$user_id] ) )
            {
                $logs['errors'][] = get_lang('Unable to find the user in the csv');
            }
            else
            {
                $userInfo['username'] = $this->data[$user_id]['username'];
                $userInfo['firstname'] = $this->data[$user_id]['firstname'];
                $userInfo['lastname'] = $this->data[$user_id]['lastname'];
                $userInfo['email'] = isset( $this->data[$user_id]['email'] )
                                     && ! empty( $this->data[$user_id]['email'] )
                                     ? $this->data[$user_id]['email'] : '';
                $userInfo['password'] = isset( $this->data[$user_id]['password'] )
                                     && ! empty( $this->data[$user_id]['password'] )
                                     ? $this->data[$user_id]['password'] : mk_password( 8 );
                $userInfo['officialCode'] = isset( $this->data[$user_id]['officialCode'] ) ? $this->data[$user_id]['officialCode'] : '';
                
                if( isset( $this->data[$user_id]['groupName'] ) )
                {
                    $groupNames = $this->data[$user_id]['groupName'];
                }
                else
                {
                    $groupNames = null;
                }
                
                
                //check user existe if not create is asked
                $resultSearch = user_search( array( 'username' => $userInfo['username'] ), null, true, true );
                
                if( empty($resultSearch))
                {
                    if( !$canCreateUser )
                    {
                        $userId = 0;
                        $logs['errors'][] = get_lang( 'Unable to create user %username, option is disabled in configuration', array('%username' => $userInfo['username'] ) );
                    }
                    else
                    {
                        $userId = user_create( $userInfo );
                        if( $userId != 0 )
                        {
                            $logs['success'][] = get_lang( 'User profile %username created successfully', array( '%username' => $userInfo['username'] ) );
                            if ( $sendEmail )
                            {
                                user_send_registration_mail ($userId, $userInfo);
                            }
                        }
                        else
                        {
                            $logs['errors'][] = get_lang( 'Unable to create user %username', array('%username' => $userInfo['username'] ) );
                        }
                    }
                }
                else
                {
                    $userId = $resultSearch[0]['uid'];
                    $logs['errors'][] = get_lang( 'User %username not created because it already exists in the database', array( '%username' => $userInfo['username'] ) );
                }
                
                if( $userId == 0)
                {
                    $logs['errors'][] = get_lang( 'Unable to add user %username in this course', array('%username' => $userInfo['username'] ) );
                }
                else
                {
                    if( !$enrollUserInCourse )
                    {
                        $logs['errors'][] = get_lang( 'Unable to add user %username in this course, option is disabled in configuration', array('%username' => $userInfo['username'] ) );
                    }
                    else
                    {
                        if( !user_add_to_course( $userId, $courseId, false, false, null) )
                        {
                            $logs['errors'][] = get_lang( 'Unable to add user %username in this course', array('%username' => $userInfo['username'] ) );
                        }
                        else
                        {
                            $logs['success'][] = get_lang( 'User %username added in course %courseId', array('%username' => $userInfo['username'], '%courseId' => $courseId ));
                            //join class if needed
                            if( $class_id )
                            {
                                if( ! $return = user_add_to_class( $userId, $class_id ) )
                                {
                                    $logs['errors'][] = get_lang( 'Unable to add %username in the selected class', array( '%username' => $userInfo['username'] ) );
                                }
                                else
                                {
                                    $logs['success'][] = get_lang( 'User %username added in the selected class', array( '%username' => $userInfo['username'] ) );
                                }
                            }
                            //join group
                            $groups = explode(',', $groupNames);
                            if( is_array( $groups ) )
                            {
                                foreach( $groups as $group)
                                {
                                    $group = trim($group);
                                    if( !empty($group) )
                                    {
                                    $groupsImported[$group][] = $userId;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        foreach( $groupsImported as $group => $users)
        {
            $GLOBALS['currentCourseRepository'] = claro_get_course_path( $courseId );
            $groupId = create_group($group, null);
            if( $groupId == 0 )
            {
                $logs['errors'][] = get_lang( 'Unable to create group %groupname', array( '%groupname' => $group) );
            }
            else
            {
                foreach( $users as $userId)
                {
                    $sql = "INSERT INTO `" . $tbl_group_rel_team_user . "`
                            SET user = " . (int) $userId . ",
                                team = " . (int) $groupId ;
                    if( !claro_sql_query( $sql ) )
                    {
                        $logs['errors'][] = get_lang( 'Unable to add user in group %groupname', array('%groupname' => $group) );
                    }
                }
            }
        }
        
        return $logs;
        
    }
    
    /**
     * Gets csv datas
     * @return array $this->data
     */
    public function getCSVContent()
    {
        return $this->data;
    }
    
    /**
     * Gets the first line of csv
     * @return array $firstLine
     */
    public function getFirstLine()
    {
        return implode( ',' , $this->titles );
    }
    
    /**
     * Puts datas
     * @param array $data
     */
    public function put( $data )
    {
        $this->data = $data;
    }
    
    /**
     * Validate fields name
     * @return boolean
     */
    public function validateFields()
    {
        if( count( array_intersect( self::$requiredFields , $this->titles ) ) == 4 )
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    /**
     * Create an array that you can use to generate CSV (array of array).
     *
     * @param $content array that need to be changed in an usable array
     * @param $useFirstLine use the first line of the array to define cols
     * @param $keys
     * @return $useableArray converted array
     */
    public function createArrayForCsvUsage($useFirstLine = true, $keys = null)
    {
        $content = $this->data;
        
        if( $useFirstLine )
        {
            $keys = $content[0];
            unset($content[0]);
        }
        
        if( !(!is_null( $keys ) && is_array( $keys ) && count( $keys )) )
        {
            return false;
        }
        
        $useableArray = array();
        foreach( $keys as $col )
        {
            $useableArray[$col] = array();
        }
        
        foreach( $content as $i => $row)
        {
            foreach( $row as $j => $r)
            {
                foreach( $keys as $col => $val )
                {
                    if( $j == $col )
                    {
                        $useableArray[$val][$i] = $r;
                    }
                }
            }
        }
        
        return $useableArray;
    }
    
    /**
     * Check the value of user id field
     * (must be a number, avoid duplication).
     *
     * @param $data user id value
     * @return array containing errors
     */
    static public function checkUserIdField( $data )
    {
        $errors = array();
        
        foreach( $data as $key => $value )
        {
            if( !(is_numeric( $value ) && $value >= 0) )
            {
                $errors[] = get_lang('User ID must be a number at line %key', array( '%key' => $key ));
            }
            elseif( array_search( $value, $data) != $key )
            {
                $errors[] = get_lang('User ID seems to be duplicate at line %key', array( '%key' => $key ));
            }
        }
        
        return $errors;
    }
    
    /**
     * Check the value of the email field
     * (must be an email address, avoid duplication).
     *
     * @param $data email value
     * @return array containing errors
     **/
    static public function checkEmailField( $data )
    {
        $errors = array();
        foreach( $data as $key => $value )
        {
            if( !empty( $value ) )
            {
                if( !is_well_formed_email_address( $value ) )
                {
                    $errors[] = get_lang('Invalid email address at line %key', array( '%key' => $key ));
                }
                elseif( array_search( $value, $data) != $key )
                {
                    $errors[] = get_lang('Email address seems to be duplicate at line %key', array( '%key' => $key ));
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Check each field content based on the key of the array.
     *
     * @param $content array of values from the csv file
     *
     * @return boolean
     */
    static public function checkFieldsErrors( $content )
    {
        $errors = array();
        
        foreach( $content as $key => $values )
        {
            switch( $key )
            {
                /*case 'userId' :
                {
                    $error = self::checkUserIdField( $values );
                    if( !is_null( $error ) )
                    {
                        $errors[$key] = $error;
                    }
                }
                break;*/
            
                case 'email' :
                {
                    $error = self::checkEmailField( $values );
                    if( !empty( $error ) )
                    {
                        $errors[$key] = $error;
                    }
                }
                break;
            
                case 'username' :
                {
                    $error = self::checkUserNameField( $values );
                    if( !is_null( $error ) )
                    {
                        $errors[$key] = $error;
                    }
                }
                break;
            
                case 'groupName' :
                {
                    $error = self::checkUserGroup( $values );
                    if( !is_null( $error ) )
                    {
                        $errors[$key] = $error;
                    }
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Check the defined format.
     *
     * @param $format format used in the csv
     * @param $delim field delimiter
     *
     * @return boolean if all requiered fields are defined, return true
     */
    static public function format_ok( $format , $delim = ',' )
    {
        if( is_array( $format ) )
        {
            $fieldarray = array_keys( $format );
        }
        else
        {
            $fieldarray = explode( $delim , $format );
        }
        
        $username_found     = false;
        $password_found     = false;
        $firstname_found    = false;
        $lastname_found     = false;
        
        foreach ( $fieldarray as $field )
        {
            if( trim($field) == 'firstname' )
            {
                $firstname_found = true;
            }
            
            if( trim($field)=='lastname' )
            {
                $lastname_found = true;
            }
            
            if( trim($field)=='username' )
            {
                $username_found = true;
            }
        }
        
        return ($username_found && $firstname_found && $lastname_found);
    }
    
    static public function checkUserNameField( $data )
    {
        $errors = array();
        
        $tbl_mdb_names = claro_sql_get_main_tbl();
        $tbl_user      = $tbl_mdb_names['user'];
        
        foreach( $data as $key => $value )
        {
            if( empty( $value) )
            {
                $errors[] = get_lang('Username is empty at line %key', array( '%key' => $key ));
            }
            elseif( array_search( $value, $data) != $key )
            {
                $errors[] = get_lang('Username seems to be duplicate at line %key', array( '%key' => $key ));
            }
            else
            {
                $sql = "SELECT `user_id` FROM `". $tbl_user ."` WHERE 1=0 ";
                $sql .= " OR `username` like '" . claro_sql_escape( $value ) . "'";
                $userId = claro_sql_query_fetch_single_value( $sql );
                
                if( $userId && !is_null( $userId ) )
                {
                    $errors[] = get_lang('Username already exists in the database at line %key', array( '%key' => $key));
                }
            }
        }
        
        return $errors;
    }
    
    
   static public function checkUserGroup( $groupNames )
    {
        return null;
    }
}