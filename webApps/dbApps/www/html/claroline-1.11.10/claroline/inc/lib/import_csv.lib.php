<?php // $Id: import_csv.lib.php 14314 2012-11-07 09:09:19Z zefredz $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * Library for import of csv user list
 *
 * @version     1.9 $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 * @author      Guillaume Lederer <guillaume@claroline.net>
 * @since       1.6
 */


/**
 * INVERT A MATRIX function :
 *
 * this function allows to invert cols and rows of a 2D array
 * needed to treat the potentialy new users to add form a CSV file
 * @param origMartix array source array to be reverted
 * @param $presumedColKeyList array contain the minimum list of colum in the builded array
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 */

function array_swap_cols_and_rows( $origMatrix, $presumedColKeyList)
{
    $revertedMatrix = array();

    foreach($origMatrix as $thisRow)
    {
        $actualColKeyList = array();

        foreach($thisRow as $thisColKey => $thisColValue)
        {
            $revertedMatrix[$thisColKey][] = $thisColValue;

            $actualColKeyList[] = $thisColKey;
        }

        // IN case of missing columns, fill them with NULL

        $missingColKeyList = array_diff($presumedColKeyList, $actualColKeyList);

        if (count($missingColKeyList) > 0)
        {
            foreach($missingColKeyList as $thisColKey)
            {
                $revertedMatrix[$thisColKey][] = NULL;
            }

        }
    }
    return $revertedMatrix;
}
/**
 * test if the given format is correct to be used in claroline to add user,
 * if all the complusory fields are present.
 *
 * @param format to test
 *
 * @return boolean TRUE if format is acceptable
 *                 FALSE if format is not acceptable (missing a needed field)
 *
 */

function claro_CSV_format_ok($format, $delim, $enclosedBy)
{
    $fieldarray = explode($delim,$format);
    if ($enclosedBy == 'dbquote') $enclosedBy = '"';

    $username_found = FALSE;
    $password_found = FALSE;
    $firstname_found  = FALSE;
    $lastname_found     = FALSE;

    foreach ($fieldarray as $field)
    {

        if (!empty($enclosedBy))
        {
            $fieldTempArray = explode($enclosedBy,$field);
            if (isset($fieldTempArray[1])) $field = $fieldTempArray[1];
        }
        if ( trim($field) == 'firstname' )
        {
            $firstname_found = TRUE;
        }
        if (trim($field)=='lastname')
        {
            $lastname_found = TRUE;
        }
        if (trim($field)=='username')
        {
            $username_found = TRUE;
        }
        if ( trim($field) == 'password' )
        {
            $password_found = TRUE;
        }
    }
    return ($username_found && $password_found && $firstname_found && $lastname_found);
}

/**
 * Check ERRORS in a CSV file uploaded of potential new user to add in Claroline
 *
 * format used for line of CSV file must be stored in SESSION to use this function properly: ...
 *
 * @param  $uploadTempDir : place where the folder is stored
 * @param  $useFirstLine  : boolean true if parser should user the first line of file to know where the format is
 *                                  false otherwise
 * @param $format : the used format, if empty, this means that we use first line format mode
 *
 * @return a 2D array with the users found in the file is stored in session, 7 boolean errors arrays are created for each type of possible errors, they are stored in session too:
 *
 *      $_SESSION['claro_csv_userlist'] for the users to add
 *
 *      $_SESSION['claro_mail_synthax_error']               for mail synthax error
 *      $_SESSION['claro_mail_used_error']                  for mail used in campus error
 *      $_SESSION['claro_username_used_error']              for username used in campus error
 *      $_SESSION['claro_officialcode_used_error']          for official code used error
 *      $_SESSION['claro_password_error']                   for password error
 *      $_SESSION['claro_mail_duplicate_error']             for mail duplicate error
 *      $_SESSION['claro_username_duplicate_error']         for username duplicate error
 *      $_SESSION['claro_officialcode_duplicate_error']     for officialcode duplicate error
 *
 * @author Guillaume Lederer <lederer@cerdecam.be>
 *
 */

function claro_check_campus_CSV_File($uploadTempDir, $useFirstLine, $usedFormat, $fieldSeparator, $enclosedBy)
{
    //open file

    fopen($_FILES['CSVfile']['tmp_name'],'r') or die ('Impossible to open file ' . $_FILES['CSVfile']['name']);

    //Read each ligne : we put one user in an array, and build an array of arrays for the list of user.

      //see where the line format must be found and which seperator and enclosion must be used

    if ($useFirstLine)          $usedFormat = 'FIRSTLINE';
    if ($enclosedBy=='dbquote') $enclosedBy = '"';

    //create file Parser

    $CSVParser = new CSV($_FILES['CSVfile']['tmp_name'],$fieldSeparator,$usedFormat,$enclosedBy);

    if ($CSVParser->validFormat==false)
    {
        $_SESSION['claro_invalid_format_error']               =  true;
        return;
    }
    else
    {
        $_SESSION['claro_invalid_format_error']               =  false;
    }
    $userlist = $CSVParser->results;

    //save this 2D array userlist in session

    $_SESSION['claro_csv_userlist'] = $userlist;

    // test for each user if it is addable, get possible errors messages in tables

       //first, we inverse the 2D array containing the lines of CSV file just parsed
       //because it is much easier and faster to have line numbers of the CSV file as second indice in the array

    $cols[] = 'firstname';
    $cols[] = 'lastname';
    $cols[] = 'email';
    $cols[] = 'phone';
    $cols[] = 'username';
    $cols[] = 'password';
    $cols[] = 'officialCode';

    $working2Darray = array_swap_cols_and_rows($_SESSION['claro_csv_userlist'],$cols);

    //look for possible new errors

    $mail_synthax_error           = check_email_synthax_userlist($working2Darray);
    $mail_used_error              = check_mail_used_userlist($working2Darray);
    $username_used_error          = check_username_used_userlist($working2Darray);
    $officialcode_used_error      = check_officialcode_used_userlist($working2Darray);

    if ( get_conf('SECURE_PASSWORD_REQUIRED') )
    {
        $password_error               = check_password_userlist($working2Darray);
    }
    else
    {
        $password_error = array(); // no error ...
    }

    $mail_duplicate_error         = check_duplicate_mail_userlist($working2Darray);
    $username_duplicate_error     = check_duplicate_username_userlist($working2Darray);
    $officialcode_duplicate_error = check_duplicate_officialcode_userlist($working2Darray);

    //save error arrays in session (needed in second step)

    $_SESSION['claro_mail_synthax_error']               =  $mail_synthax_error;
    $_SESSION['claro_mail_used_error']                  =  $mail_used_error;
    $_SESSION['claro_username_used_error']              =  $username_used_error;
    $_SESSION['claro_officialcode_used_error']          =  $officialcode_used_error;
    $_SESSION['claro_password_error']                   =  $password_error;
    $_SESSION['claro_mail_duplicate_error']             =  $mail_duplicate_error;
    $_SESSION['claro_username_duplicate_error']         =  $username_duplicate_error;
    $_SESSION['claro_officialcode_duplicate_error']     =  $officialcode_duplicate_error;

    //delete the temp file

    @unlink($uploadTempDir.$_FILES['CSVfile']['name']);
}

/**
 * display the errors caused by a conflict with the platform after parsing the CSV file used to add new users found in the platform.
 * ERRORS and USERS must be saved in the session at these places :
 *
 *      $_SESSION['claro_csv_userlist'] for the users to add
 *
 *      $_SESSION['claro_mail_synthax_error']               for mail synthax error
 *      $_SESSION['claro_mail_used_error']                  for mail used in campus error
 *      $_SESSION['claro_username_used_error']              for username used in campus error
 *      $_SESSION['claro_officialcode_used_error']          for official code used error
 *      $_SESSION['claro_password_error']                   for password error
 *      $_SESSION['claro_mail_duplicate_error']             for mail duplicate error
 *      $_SESSION['claro_username_duplicate_error']         for username duplicate error
 *      $_SESSION['claro_officialcode_duplicate_error']     for officialcode duplicate error
 *
 * @author Guillaume Lederer <lederer@cerdecam.be>
 *
 *
 */

function claro_disp_CSV_error_backlog()
{

    if (isset($_SESSION['claro_invalid_format_error']) && $_SESSION['claro_invalid_format_error'] == true)
    {
       echo get_lang('ERROR: The format you gave is not compatible with Claroline').'<br />';
       return;
    }

    for ($i=0, $size=sizeof($_SESSION['claro_csv_userlist']); $i<$size; $i++)
    {
        $line=$i+1;

        if (isset($_SESSION['claro_mail_synthax_error'][$i]) && $_SESSION['claro_mail_synthax_error'][$i])
        {
            echo '<b>line ' . $line . ':</b> "' . $_SESSION['claro_csv_userlist'][$i]['email'] . '" <b>:</b>' . get_lang('Mail synthax error.') .  ' <br />';
        }
        if (isset($_SESSION['claro_mail_used_error'][$i]) && $_SESSION['claro_mail_used_error'][$i])
        {
            echo '<b>line ' . $line . ':</b> "' . $_SESSION['claro_csv_userlist'][$i]['email'].'" <b>:</b>'  . get_lang('Mail is already used by another user.') .  ' <br />' . "\n";
        }
        if (isset($_SESSION['claro_username_used_error'][$i]) && $_SESSION['claro_username_used_error'][$i])
        {
            echo '<b>line ' . $line . ':</b> "' . $_SESSION['claro_csv_userlist'][$i]['username']. '" <b>:</b>'  . get_lang('UsernameUsed') .  ' <br />' . "\n";
        }
        if (isset($_SESSION['claro_officialcode_used_error'][$i]) && $_SESSION['claro_officialcode_used_error'][$i])
        {
            echo '<b>line ' . $line . ':</b> "' . $_SESSION['claro_csv_userlist'][$i]['officialCode']. '" <b>:</b>'  . get_lang('This official code is already used by another user.') .  ' <br />' . "\n";
        }
        if (isset($_SESSION['claro_password_error'][$i]) && $_SESSION['claro_password_error'][$i])
        {
            echo '<b>line ' . $line . ':</b> "' . $_SESSION['claro_csv_userlist'][$i]['password']. '" <b>:</b>'  . get_lang('Password given is too simple or too close to the username.') .  ' <br />' . "\n";
        }
        if (isset($_SESSION['claro_mail_duplicate_error'][$i]) && $_SESSION['claro_mail_duplicate_error'][$i])
        {
            echo '<b>line ' . $line . ':</b> "' . $_SESSION['claro_csv_userlist'][$i]['email']. '" <b>:</b>'  . get_lang('This mail appears already in a previous line of the CSV file.')  . "<br />\n";
        }
        if (isset($_SESSION['claro_username_duplicate_error'][$i]) && $_SESSION['claro_username_duplicate_error'][$i])
        {
            echo '<b>line ' . $line . ':</b> "' . $_SESSION['claro_csv_userlist'][$i]['username']. '" <b>:</b>'  . get_lang('UsernameAppearAlready') . "<br />\n";
        }
        if (isset($_SESSION['claro_officialcode_duplicate_error'][$i]) && $_SESSION['claro_officialcode_duplicate_error'][$i])
        {
            echo '<b>line ' . $line . ':</b> "' . $_SESSION['claro_csv_userlist'][$i]['officialCode']. '" <b>:</b>'  . get_lang('This official code already appears in a previous line of the CSV file.') .  ' <br />' . "\n";
        }
    }
}

/**
 * Check EMAIL SYNTHAX : if new users in Claroline with the specified parameters contains synthax error
 * in mail given.
 *
 * @author Guillaume Lederer <lederer@cerdecam.be>
 *
 * @param  $userlist must be a 2D array with the list of potential new users :
 *         $userlist['email'][$i] for the email
 *
 *
 * @return $errors : an array of boolean where $errors[$i] is TRUE if there is an error with entry $i in the given array.
 *
 *
 */

function check_email_synthax_userlist($userlist)
{

    $errors = array();
    //CHECK: check email validity
    for ($i=0, $size=sizeof($userlist['email']); $i<$size; $i++)
    {
        if ((!empty($userlist['email'][$i])) && ! is_well_formed_email_address( $userlist['email'][$i] ))
          {
            $errors[$i] = TRUE;
        }
    }
    return $errors;
}

 /**
 * Check USERNAME NOT TAKEN YET : check if usernames are not already token by someone else
 *
 * @author Guillaume Lederer <lederer@cerdecam.be>
 *
 * @param  $userlist must be a 2D array with the list of potential new users :
 *         $userlist['username'][$i]   for the username
 *
 * @return $errors : an array of boolean where $errors[$i] is TRUE if there is an error with entry $i in the given array.
 *
 *
 */

function check_username_used_userlist($userlist)
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tbl_user      = $tbl_mdb_names['user'];

    //create an array with default values of errors

    $errors = array();

    //CHECK : check if usernames are not already token by someone else

    $sql = 'SELECT *
            FROM `'.$tbl_user.'`
            WHERE 1=0 ';

    for ($i=0, $size=sizeof($userlist['username']); $i<$size; $i++)
    {
        if (!empty($userlist['username'][$i]) && ($userlist['username'][$i]!=''))
        {
            $sql .= ' OR username="'.claro_sql_escape($userlist['username'][$i]).'"';
        }
    }

    //for each user found, report the potential problem in an error array returned

    // TODO USE Claro_sql function
    $foundUser = claro_sql_query($sql);

    while (false !== $list = mysql_fetch_array($foundUser))
    {
        $found = array_search($list['username'],$userlist['username']);
        if (!($found===FALSE))
        {
            $errors[$found] = TRUE;
        }
    }

    return $errors;
}

 /**
 * Check OFFICIAL CODE NOT TAKEN YET : check if admincode (officialCode) is not already taken by someone else
 *
 * @author Guillaume Lederer <lederer@cerdecam.be>
 *
 * @param  $userlist must be a 2D array with the list of potential new users :
 *
 *         $userlist['officialCode'][$i]  for the officialCode
 *
 * @return $errors : an array of boolean where $errors[$i] is TRUE if there is an error with entry $i in the given array.
 *
 *
 */

function check_officialcode_used_userlist($userlist)
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tbl_user      = $tbl_mdb_names['user'];

    //create an array with default values of errors

    $errors = array();

    //CHECK : check if admincode (officialCode) is not already taken by someone else
    $sql = 'SELECT *
            FROM `'.$tbl_user.'`
            WHERE 1=0 ';

    for ($i=0, $size=sizeof($userlist['officialCode']); $i<$size; $i++)
    {
        if (!empty($userlist['officialCode'][$i]) )
        {
            $sql .= ' OR officialCode="'.claro_sql_escape($userlist['officialCode'][$i]).'"';
        }
    }

    //for each user found, report the potential problem
    // TODO USE Claro_sql function
    $foundUser = claro_sql_query($sql);

    while (false !== $list = mysql_fetch_array($foundUser))
    {
        $found = array_search($list['officialCode'],$userlist['officialCode']);
        if (!($found===FALSE))
        {
            $errors[$found] = TRUE;
        }
    }
    return $errors;
}

/**
 * Check PASSWORD ACCEPTABLE  : check if password is sufficently complex for this user
 *
 * @author Guillaume Lederer <lederer@cerdecam.be>
 *
 * @param  $userlist must be a 2D array with the list of potential new users :
 *
 *         $userlist['username'][$i]   for the username
 *         $userlist['password'][$i]   for the password
 *
 * @return $errors : an array of boolean where $errors[$i] is TRUE if there is an error with entry $i in the given array.
 *
 *
 */

function check_password_userlist($userlist)
{
    $errors = array();

    for ($i=0, $size=sizeof($userlist['password']); $i<$size; $i++)
    {
        if ($userlist['password'][$i]==$userlist['username'][$i]) // do not allow to put username equals to password
        {
            $errors[$i] = TRUE;
        }
    }
    return $errors;
}

 /**
 * Check EMAIL NOT TAKEN YET : check if the e-mails are not already taken by someone in the plateform
 *
 * @author Guillaume Lederer <lederer@cerdecam.be>
 *
 * @param  $userlist must be a 2D array with the list of potential new users :
 *
 *         $userlist['email'][$i] for the email
 *
 * @return $errors : an array of boolean where $errors[$i] is TRUE if there is an error with entry $i in the given array.
 *
 *
 */

function check_mail_used_userlist($userlist)
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tbl_user             = $tbl_mdb_names['user'             ];

    //create an array with default values of errors

    $errors = array();

    //create SQL query to search in Claroline DB

    $sql = 'SELECT *
            FROM `'.$tbl_user.'`
            WHERE 1=0 ';

    for ($i=0, $size=sizeof($userlist['email']); $i<$size; $i++)
    {
        if (!empty($userlist['email'][$i]) && ($userlist['email'][$i]!=''))
        {
            $sql .= ' OR email="'.claro_sql_escape($userlist['email'][$i]).'"';
        }
    }

    //for each user found, report the potential problem for email
    // TODO USE Claro_sql function
    $foundUser = claro_sql_query($sql);
    while (false !== $list = mysql_fetch_array($foundUser))
    {
        $found = array_search($list['email'],$userlist['email']);
        if (!($found===FALSE))
        {
            $errors[$found] = TRUE;
        }
    }

    return $errors;
}

/**
 * Check DUPLICATE EMAIL OF ADDABLE USERS : take the 2D array in param and check if  email
 * are all different.
 *

 *
 * @param  $userlist must be a 2D array with the list of potential new users :
 *         $userlist['email'][$i] for the email
 *         $userlist['username'][$i] for the username
 *         $userlist['officialCode'][$i]  for the officialCod
 *
 * @return $errors : an array of boolean where $errors[$i] is TRUE if there is an error with entry $i in the given 2D array.
 *
 *
 */

function check_duplicate_mail_userlist($userlist)
{
    $errors = array();
    for ($i=0, $size=sizeof($userlist['username']); $i<$size; $i++)
    {
        //check email duplicata in the array

        if ($userlist['email'][$i] != '')
        {
            $found = array_search($userlist['email'][$i],$userlist['email']);
        }
        else
        {
            $found = FALSE; // do not check if email is empty
        }
        if (!($found===FALSE) && ($i!=$found))
        {
            $errors[$i] = TRUE;
        }
    }
    return $errors;
}

/**
 * Check DUPLICATE USERNAMES OF ADDABLE USERS : take the 2D array in param and check if username are all different.
 *
 * @author Guillaume Lederer <lederer@cerdecam.be>
 *
 * @param  $userlist must be a 2D array with the list of potential new users :
 *         $userlist['email'][$i] for the email
 *         $userlist['username'][$i] for the username
 *         $userlist['officialCode'][$i]  for the officialCod
 *
 * @return $errors : an array of boolean where $errors[$i] is TRUE if there is an error with entry $i in the given 2D array.
 *
 *
 */

function check_duplicate_username_userlist($userlist)
{
    $errors = array();
    for ($i=0, $size=sizeof($userlist['username']); $i<$size; $i++)
    {
        //check username duplicata in the array
        $found = array_search($userlist['username'][$i],$userlist['username']);
        if (!($found===FALSE) && ($i!=$found))
        {
            $errors[$i] = TRUE;
        }
    }

    return $errors;
}

/**
 * Check DUPLICATE OFFICIAL CODE OF ADDABLE USERS : take the 2D array in param and check if official codes are all different.
 *
 * @author Guillaume Lederer <lederer@cerdecam.be>
 *
 * @param  $userlist must be a 2D array with the list of potential new users :
 *         $userlist['email'][$i] for the email
 *         $userlist['username'][$i] for the username
 *         $userlist['officialCode'][$i]  for the officialCod
 *
 * @return $errors : an array of boolean where $errors[$i] is TRUE if there is an error with entry $i in the given 2D array.
 *
 *
 */

function check_duplicate_officialcode_userlist($userlist)
{

    $errors = array();

    for ( $i=0, $size=sizeof($userlist['officialCode']); $i<$size; $i++ )
    {
        //check officialCode duplicata in the array

        if ( !empty($userlist['officialCode'][$i]) )
        {
            $found = array_search($userlist['officialCode'][$i],$userlist['officialCode']);

            if (!($found===FALSE) && ($i!=$found))
            {
                $errors[$i] = TRUE;
            }
        }
    }
    return $errors;
}


/**
 * Class needed for parsing CSV files
 */
class CSV
{
    public $raw_data;
    public $new_data;
    public $mapping;
    public $results = array();
    public $errors = array();
    public $validFormat; //boolean variable set to true if the format useed in the file is usable in Claroline user database
    
    
    /**
     * Constructor.
     *
     * @param   $filename
     * @param   $delim
     * @param   $linedef FIRSTLINE means we take the first line of the file as the definition of the fields
     * @param   $enclosed_by
     * @param   $eol
     *
     * @return $errors : an array of boolean where $errors[$i] is TRUE if there is an error with entry $i in the given 2D array.
     */
    public function __construct ($filename, $delim=';', $linedef, $enclosed_by='', $eol="\n")
    {
        //open the file
        $this->raw_data = implode('',file($filename));
        
        // make sure all CRLF's are consistent
        $this->CRLFclean();
        // use custom $eol (if exists)
        if($eol!="\n" AND trim($eol)=='')
        {
            $this->error("Couldn't split data via empty \$eol, please specify a valid end of line character.");
        }
        else
        {
            $this->new_data = @explode($eol,$this->raw_data);
            if(count($this->new_data)==0)
            {
                $this->error("Couldn't split data via given \$eol.<li>\$eol='".$eol."'");
            }
        }
        // create data keys with the line definition given in params,
        // if linedef is not define, take first line of file to define it
        if ($linedef=='FIRSTLINE')
        {
            $linedef = $this->new_data[0];
            $skipFirstLine = TRUE;
        }
        else
        {
            $skipFirstLine = FALSE;
        }
        
        //Create array with the fields format in the file :
        
        $temp = @explode($delim,$linedef);
        if (!empty($enclosed_by))
        {
            $temporary = array();
            
            foreach ($temp as $tempfield)
            {
                $fieldTempArray = explode($enclosed_by,$tempfield);
                $temporary[] = preg_replace('/^("|\')/','',$tempfield);
            }
            $temp = $temporary;
        }
        
        //check if the used format is ok for Claroline
        
        $this->validFormat = claro_CSV_format_ok($linedef, $delim, $enclosed_by);
        
        if (!($this->validFormat)) return array();
        
        
        foreach($temp AS $field_index=>$field_value)
        {
            $this->mapping[] = $this->validKEY($field_value);
        }
        
        // fill the 2D array using the keys given
        
        foreach($this->new_data AS $index1=>$line)
        {
            if (trim($line)=='')
            {
                // skip empty lines
                continue;
            }
            
            // explode the line with the delimitator
            $temp = @explode($delim,$line);
            
            if ( count($temp)==0 )
            {
                // line didn't split properly so record error
                $this->errors[] = "Couldn't split data line ". claro_htmlspecialchars($line) ." via given \$delim.";
            }
            elseif (!(($index1==0) && ($skipFirstLine)))
            {
                $data_set = array();
                foreach($temp AS $field_index=>$field_value)
                {
                    // Remove enclose characters
                    $this->stripENCLOSED($field_value,$enclosed_by);
                    $data_set[$this->mapping[$field_index]] = $field_value;
                }
                if(count($data_set)>0)
                    $this->results[] = $data_set;
            }
            unset($data_set);
        }
        
        return $this->results;
    }
    
    
    public function CRLFclean()
    {
        $replace = array("\n", "\r", "\r\n");
        $this->raw_data = str_replace($replace, "\n", $this->raw_data);
    }
    
    
    public function validKEY($v)
    {
        return ereg_replace("[^a-zA-Z0-9_\s]", "", $v);
    }
    
    
    public function stripENCLOSED(&$v, $eb)
    {
        if($eb!='' AND strpos($v, $eb)!==false)
        {
            if($v[0]==$eb)
                $v = substr($v, 1, strlen($v));
            if($v[strlen($v)-1]==$eb)
                $v = substr($v, 0, -1);
                $v = stripslashes($v);
        }
        else
        {
            return;
        }
    }
    
    
    public function error($msg)
    {
        exit(
           '<hr size="1" noshade>'.
           '<font color="red" face="arial" size="3">'.
           '<h2>CSV Class Exception</h2>'.
           $msg.
           '<p><b>Script Halted</b>'.
           '</font>'.
           '<hr size="1" noshade>'
           );
    }
    
    
    public function help()
    {
        print(
           "<hr size=1 noshade>".
           "<font face=arial size=3>".
           "<h2>CSV Class Usage</h2>".
           "\$myVar = new CSV(\"path_to_my_file\",\"field delimeter\",\"fields enclosed by\",\"EOL character (defaults to \\n)\");<p>".
           "Output is a 2d result array (\$myVar->results)".
           "</font>".
           "<hr size=1 noshade>"
           );
    }
}