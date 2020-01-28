<?php // $Id: fileManage.lib.php 14387 2013-02-11 14:09:40Z zefredz $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * @version     1.9 $Revision: 14387 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/config_def/
 * @package     KERNEL
 * @author      Claro Team <cvs@claroline.net>
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 */

/**
 * Checks a file or a directory actually exist at this location
 *
 * @param  - filePath (string) - path of the presume existing file or dir
 * @return - boolean TRUE if the file or the directory exists
 *           boolean FALSE otherwise.
 */

function check_name_exist($filePath)
{
    clearstatcache();
    return file_exists($filePath);
}

/**
 * Delete a file or a directory (and its whole content)
 *
 * @param  - $filePath (String) - the path of file or directory to delete
 * @return - boolean - true if the delete succeed
 *           boolean - false otherwise.
 */

function claro_delete_file($filePath)
{
    if( is_file($filePath) )
    {
        return unlink($filePath);
    }
    elseif( is_dir($filePath) )
    {
        $dirHandle = @opendir($filePath);

        if ( ! $dirHandle )
        {
            function_exists('claro_html_debug_backtrace') && pushClaroMessage( claro_html_debug_backtrace());
            return false;
        }

        $removableFileList = array();

        while ( false !== ($file = readdir($dirHandle) ) )
        {
            if ( $file == '.' || $file == '..') continue;

            $removableFileList[] = $filePath . '/' . $file;
        }

        closedir($dirHandle); // impossible to test, closedir return void ...

        if ( sizeof($removableFileList) > 0)
        {
            foreach($removableFileList as $thisFile)
            {
                if ( ! claro_delete_file($thisFile) ) return false;
            }
        }

        clearstatcache();
        if (is_writable($filePath)) return @rmdir($filePath);
        else
        {
            function_exists('claro_html_debug_backtrace') && pushClaroMessage( claro_html_debug_backtrace());
            return false;
        }

    } // end elseif is_dir()
}

//------------------------------------------------------------------------------


/**
 * Rename a file or a directory
 *
 * @param  - $filePath (string) - complete path of the file or the directory
 * @param  - $newFileName (string) - new name for the file or the directory
 * @return - string  - new file path if it succeeds
 *         - boolean - false otherwise
 * @see    - rename() uses the check_name_exist() and php2phps() functions
 */

function claro_rename_file($oldFilePath, $newFilePath)
{
    if (realpath($oldFilePath) == realpath($newFilePath) ) return true;

    /* CHECK IF THE NEW NAME HAS AN EXTENSION */
    if ( ! is_dir( $oldFilePath ) )
    {
        $ext_new = get_file_extension( $newFilePath );
        $ext_old = get_file_extension( $oldFilePath );
        
        if( empty($ext_new) && !empty($ext_old) )
        {
            $newFilePath .= '.' . $ext_old;
        }
    }

    /* PREVENT FILE NAME WITH PHP EXTENSION */

    $newFilePath = get_secure_file_name($newFilePath);

    /* REPLACE CHARACTER POTENTIALY DANGEROUS FOR THE SYSTEM */

    $newFilePath = dirname($newFilePath).'/'
    .              replace_dangerous_char(basename($newFilePath))
    ;

    if (check_name_exist($newFilePath)
        && $newFilePath != $oldFilePath)
    {
        return false;
    }
    else
    {
        if(check_name_exist( $oldFilePath) )
        {
            if ( rename($oldFilePath, $newFilePath) )
            {
                return $newFilePath;
            }
            else
            {
                return false;
            }   
        }
        else
        {
            return false;
        }
    }
}

//------------------------------------------------------------------------------


/**
 * Move a file or a directory to an other area
 *
 * @param  - $sourcePath (String) - the path of file or directory to move
 * @param  - $targetPath (String) - the path of the new area
 * @return - boolean - true if the move succeed
 *           boolean - false otherwise.
 */


function claro_move_file($sourcePath, $targetPath)
{
    if (realpath($sourcePath) == realpath($targetPath) ) return true;

    // check to not copy a directory inside itself
    if (   is_dir($sourcePath)
        && preg_match('/^' . str_replace( '/' , '\/' , $sourcePath ) . '\//', $targetPath . '/') )
        return claro_failure::set_failure('MOVE INSIDE ITSELF');

    $sourceFileName = basename($sourcePath);

    if (   $sourcePath == $targetPath
        || file_exists($targetPath . '/' . $sourceFileName) )
         return claro_failure::set_failure('FILE EXISTS');

    if ( is_dir($targetPath) )
    {
        return rename($sourcePath, $targetPath . '/' . $sourceFileName);
    }
    else
    {
        return rename($sourcePath, $targetPath);
    }
}

//------------------------------------------------------------------------------


/**
 * Copy a file or a directory and its content to an other directory
 *
 * @param  - $sourcePath (String) - the path of the directory or the path of the file to move
 * @param  - $targetPath (String) - the path of the destination directory
 * @return - void no return !!
 */

function claro_copy_file($sourcePath, $targetPath)
{
    $fileName = basename($sourcePath);
    
    $sourcePath = realpath ($sourcePath);
    $targetPath = realpath ($targetPath);
    if (! is_readable($sourcePath)) Console::warning($sourcePath . 'not readable');
    if (! is_writable($targetPath)) Console::warning($targetPath . 'not writable');
    
    if ( is_file($sourcePath) )
    {
        return copy($sourcePath , $targetPath . '/' . $fileName);
    }
    elseif ( is_dir($sourcePath) )
    {
        if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' )
        {
            // fix windows path for regexp
            $sourcePath = str_replace( '\\', '\\\\', $sourcePath );
            $targetPath = str_replace( '\\', '\\\\', $targetPath );
        }
        
        // check to not copy the directory inside itself
        if ( preg_match('/^'.str_replace( '/', '\/', $sourcePath ) . '\//', str_replace( '/', '\/', $targetPath ) . '/') ) return false;

        if ( ! claro_mkdir($targetPath . '/' . $fileName, CLARO_FILE_PERMISSIONS) )   return false;
        
        $dirHandle = opendir($sourcePath);

        if ( ! $dirHandle ) return false;

        $copiableFileList = array();

        while ( false !== ( $element = readdir($dirHandle) ) )
        {
            if ( $element == '.' || $element == '..' || $element == '.svn') continue;
            
            $copiableFileList[] = $sourcePath . '/' . $element;
        }

        closedir($dirHandle);
        
        if ( count($copiableFileList) > 0 )
        {
            foreach($copiableFileList as $thisFile)
            {
                if ( ! claro_copy_file($thisFile, $targetPath . '/' . $fileName) ) return false;
            }
        }

        return true;
    } // end elseif is_dir()
}

//------------------------------------------------------------------------------


/**
 * returns the dir path of a specific file or directory
 *
 * @param string $filePath
 * @return string dir name
 */

function claro_dirname($filePath)
{
     return str_replace('\\', '', dirname($filePath) );

     // str_replace is necessary because, when there is no
     // dirname, PHP leaves a ' \ ' (at least on windows)
}

/* NOTE: These functions batch is used to automatically build HTML forms
 * with a list of the directories contained on the course Directory.
 *
 * From a thechnical point of view, form_dir_lists calls sort_dir wich calls index_dir
 */

/**
 * Indexes all the directories and subdirectories
 * contented in a given directory
 *
 * @param  dirPath (string) - directory path of the one to index
 * @param  mode (string) - ALL, FILE, DIR : specify what will be listed
 * @return an array containing the path of all the subdirectories
 */

function index_dir($dirPath, $mode = 'ALL' )
{
    $files = array();
    if( is_dir($dirPath) ) 
    {
        $fh = opendir($dirPath);
        while( ( $fileName = readdir($fh) ) !== false ) 
        {
            // loop through the files, skipping . and .., and recursing if necessary
            if( $fileName == '.' || $fileName == '..' || $fileName == 'CVS' ) continue;
            
            $filePath = $dirPath . '/' . $fileName;
            
            if( is_dir($filePath) )
            {
                if( $mode != 'FILE' ) array_push($files, $filePath); // mode == DIR or ALL : store dirname
                $files = array_merge($files, index_dir($filePath, $mode));
            }
            elseif( $mode != 'DIR' )
            {
                // mode == FILE or ALL : store filename
                array_push($files, $filePath);
            }
        }
        closedir($fh);
    } 
    else
    {
        // false if the function was called with an invalid non-directory argument
        Console::warning($dirPath . 'not a dir');
        $files = false;
    }
    return $files;
}

/**
 * Indexes all the directories and subdirectories
 * contented in a given directory, and sort them alphabetically
 *
 * @param  - path (string) - directory path of the one to index
 * @return - an array containing the path of all the subdirectories sorted
 *           false, if there is no directory
 * @see    - index_and_sort_dir uses the index_dir() function
 */

function index_and_sort_dir($path)
{
    $dir_list = index_dir($path, 'DIR');

    if ($dir_list)
    {
        sort($dir_list);
        return $dir_list;
    }
    else
    {
        return false;
    }
}


/**
 * build an html form listing all directories of a given directory and file to move
 *
 * @param file        string: filename to o move
 * @param baseWorkDir string: complete path to root directory to prupose as target for move
 */

function form_dir_list($file, $baseWorkDir)
{

    $dirList = index_and_sort_dir($baseWorkDir);

    $dialogBox = '<strong>' . get_lang('Move') . '</strong>' . "\n" 
    ."<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">\n"
                 .    claro_form_relay_context()
                 ."<input type=\"hidden\" name=\"cmd\" value=\"exMv\" />\n"
                 ."<input type=\"hidden\" name=\"file\" value=\"".base64_encode($file)."\" />\n"
                 ."<label for=\"destiantion\">"
                 . get_lang('Move <i>%filename</i> to', array('%filename' => basename($file) ))
                 ."</label> \n"
                 ."<select name=\"destination\">\n";

    if ( dirname($file) == '/' || dirname($file) == '\\')
    {
        $dialogBox .= '<option value="" class="invisible">root</option>' . "\n";
    }
    else
    {
        $dialogBox .= '<option value="" >root</option>' . "\n";
    }


    $bwdLen = strlen($baseWorkDir) ;    // base directories length, used under

    /* build html form inputs */

    if ($dirList)
    {
        while (list( , $pathValue) = each($dirList) )
        {

            $pathValue = substr ( $pathValue , $bwdLen );        // truncate confidential informations
            $dirname = basename ($pathValue);                    // extract $pathValue directory name

            /* compute de the display tab */

            $tab = '';                                        // $tab reinitialisation
            $depth = substr_count($pathValue, '/');            // The number of nombre '/' indicates the directory deepness

            for ($h = 0; $h < $depth; $h++)
            {
                $tab .= '&nbsp;&nbsp';
            }

            if ($file == $pathValue OR dirname($file) == $pathValue)
            {
                $dialogBox .= '<option class="invisible" value="'.$pathValue.'">'.$tab.' &gt; '.$dirname.'</option>'."\n";
            }
            else
            {
                $dialogBox .= '<option value="'.$pathValue.'">'.$tab.' &gt; '.$dirname.'</option>'."\n";
            }
        }
    }

    $dialogBox .= '</select>' . "\n"
               .  '<br /><br />'
               .  '<input type="submit" value="'.get_lang('Ok').'" />&nbsp;'
               .  claro_html_button($_SERVER['PHP_SELF'].'?cmd=exChDir&file='.claro_htmlspecialchars(claro_dirname($file)), get_lang('Cancel'))
               .  '</form>' . "\n";

    return $dialogBox;
}

//------------------------------------------------------------------------------



/**
 * create directories path
 *
 * @param string  $pathname
 * @param int     $mode directory permission (optional)
 * @param boolean $recursive (optional)
 * @return boolean TRUE if succeed, false otherwise
 */

function claro_mkdir($pathName, $mode = 0777, $recursive = false)
{
    if ($recursive)
    {
        if ( strstr($pathName,get_path('rootSys')) !== false )
        {
            /* Remove rootSys path from pathName for system with safe_mode or open_basedir restrictions
               Functions (like file_exists, mkdir, ...) return false for files inaccessible with these restrictions
            */

            $pathName = str_replace(get_path('rootSys'),'',$pathName);
            $dirTrail = get_path('rootSys') ;
        }
        else
        {
            $dirTrail = '';
        }

        $dirList = explode( '/', str_replace('\\', '/', $pathName) );

        $dirList[0] = empty($dirList[0]) ? '/' : $dirList[0];

        foreach($dirList as $thisDir)
        {
            $dirTrail .= empty($dirTrail) ? $thisDir : '/'.$thisDir;

            if ( file_exists($dirTrail) )
            {
                if ( is_dir($dirTrail) ) continue;
                else                     return false;
            }
            else
            {

                if ( ! @mkdir($dirTrail , $mode) ) return false;
            }

        }
        return true;
    }
    else
    {
        // remove trailing slash
        if( substr($pathName, -1) == '/' )
        {
            $pathName = substr($pathName, 0, -1);
        }

        return @mkdir($pathName, $mode);
    }
}

/**
 * create a tmp directory
 *
 * @param string  $dir
 * @param string  $prefix 
 * @param int     $mode  
 * @return string full pathname
 */
function claro_mkdir_tmp($dir, $prefix = 'tmp', $mode = 0777)
{
    if (substr($dir, -1) != '/') $dir .= '/';

    do
    {
        $path = $dir.$prefix.mt_rand(0, 9999999);
    } while ( !claro_mkdir($path, $mode) );

    return $path;
}


/**
 * to compute the size of the directory
 *
 * @returns integer size
 * @param     string    $path path to size
 * @param     boolean $recursive if true , include subdir in total
 */

function claro_get_file_size($filePath)
{
    if     ( is_file($filePath) ) return filesize($filePath);
    elseif ( is_dir($filePath)  ) return disk_total_space($filePath);
    else                          return 0;
}

/**
 * search files or directory whose name fit a pattern
 *
 * @param string $searchPattern - Perl compatible regex to search on file name
 * @param string $baseDirPath - directory path where to start the search
 * @param string $fileType (optional) - filter allowing to restrict search
 *        on files or directories (allowed value are 'ALL', 'FILE', 'DIR').
 * @param array $excludedPathList (optional) - list of files or directories
 *        that have to be excluded from the search
 * @return array path list of the files fitting the search pattern
 */

function claro_search_file($searchPattern             , $baseDirPath,
                           $recursive        = false , $fileType = 'ALL',
                           $excludedPathList = array()                    )
{
        $searchResultList = array();

        //$baseDirPath unexisting is  a devel error or a data incoherence,
        if (! file_exists($baseDirPath))
        {
            // TODO would push an error but return empty array instead of false.
            return claro_failure::set_failure('BASE_DIR_DONT_EXIST');
        }

        $dirPt = opendir($baseDirPath);

        //can't be false as if (! file_exists($baseDirPath))  have make a good control
        //if ( ! $dirPt) return false;

        while ( $fileName = readdir($dirPt) )
        {
            $filePath = $baseDirPath.'/'.$fileName;

            if (   $fileName == '.' || $fileName == '..'
                || in_array('/'.ltrim($filePath,'/'), $excludedPathList ) )
            {
                continue;
            }
            else
            {

                if ( is_dir($filePath) ) $dirList[] = $filePath;

                if ( $fileType == 'DIR'  && is_file($filePath) )
                {
                    continue;
                }

                if ( $fileType == 'FILE' && is_dir($filePath) )
                {
                    continue;
                }

                if ( preg_match($searchPattern, $fileName) )
                {
                    $searchResultList[] = $filePath;
                }

            }
        }

        closedir($dirPt);

        if ( $recursive && isset($dirList) && count($dirList) > 0)
        {
            foreach($dirList as $thisDir)
            {
                $searchResultList =
                    array_merge( $searchResultList,
                                 claro_search_file($searchPattern, $thisDir,
                                                   $recursive, $fileType,
                                                   $excludedPathList)
                               );
            }
        }

        return $searchResultList;
}

/**
 * convert search string coming from the user interface
 * to a Perl Compatible Regular Expression (PCRE)
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @param string search string
 * @return string Perl Compatible Regular Expression
 */


function search_string_to_pcre($searchPattern)
{
    $searchPattern   = str_replace('.', '\\.', $searchPattern);
    $searchPattern   = str_replace('*', '.*' , $searchPattern);
    $searchPattern   = str_replace('?', '.?' , $searchPattern);
    $searchPattern   = '|'.$searchPattern.'|i';
    return $searchPattern;
}

/**
 * Get the list of invisible documents of the current course
 *
 * @param $baseWorkDir path document
 * @param $cidReq course identifier
 * @return list of invisible document
 */

function getInvisibleDocumentList ( $baseWorkDir, $cidReq = null )
{
    $documentList = array();

    if ( is_null($cidReq) ) $cid = claro_get_current_course_id() ;
    else                    $cid = $cidReq ;

    $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($cid));
    $tbl_document = $tbl_cdb_names['document'];

    $sql = "SELECT path
            FROM `". $tbl_document ."`
            WHERE visibility = 'i'";

    $documentList = claro_sql_query_fetch_all_cols($sql);
    $documentList = $documentList['path'];

    for( $i=0; $i < count($documentList); $i++ )
    {
        $documentList[$i] = '/' . ltrim($baseWorkDir.$documentList[$i],'/');
    }

    return $documentList ;
}

/**
 * get the url written in files specially created by Claroline
 * to redirect to a specific url
 *
 * @param param string $file complete file path
 * @return string url
 */

function get_link_file_url($file)
{
   $fileContent = implode("\n", file ($file));
   $matchList =  array();

   if (preg_match('~>([^<]+)</a>~',
                  $fileContent,
                  $matchList))
   {
        return $matchList[1];
   }
   else
   {
       return null;
   }
}

//------------------------------------------------------------------------------


/**
 * Update the file or directory path in the document db document table
 *
 * @param  String action    - action type require : 'delete' or 'update'
 * @param  String filePath  - original path of the file
 * @param  String $newParam - new param of the file, can contain
 *                              'path', 'visibility' and 'comment'
 *
 */

function update_db_info($action, $filePath, $newParamList = array())
{
    global $dbTable; // table 'document'
    
    if (strtoupper(substr(PHP_OS, 0, 3)) == "WIN")
    {
        $modifier = '';
    }
    else
    {
        $modifier = 'BINARY ';
    }

    $newComment    = (isset($newParamList['comment'   ]) ? trim($newParamList['comment'   ]) : null);
    $newVisibility = (isset($newParamList['visibility']) ? trim($newParamList['visibility']) : null);
    $newPath       = (isset($newParamList['path'      ]) ? trim($newParamList['path'      ]) : null);

    if ($action == 'delete') // case for a delete
    {
        $theQuery = "DELETE FROM `".$dbTable."`
                     WHERE path=\"".claro_sql_escape($filePath)."\"
                     OR    path LIKE \"".claro_sql_escape($filePath)."/%\"";

        claro_sql_query($theQuery);
    }
    elseif ($action == 'update')
    {
        // GET OLD PARAMETERS IF THEY EXIST

        $sql = "SELECT path, comment, visibility
                FROM `".$dbTable."`
                WHERE {$modifier} path=\"".claro_sql_escape($filePath)."\"";

        $result = claro_sql_query_fetch_all($sql);
        if ( count($result) > 0 ) list($oldAttributeList) = $result;
        else                      $oldAttributeList = null;

        if ( ! $oldAttributeList ) // NO RECORD CONCERNING THIS FILE YET ...
        {
            if ( $newComment || $newVisibility == 'i' )
            {
                if ( $newVisibility != 'i' ) $newVisibility = 'v';
                $insertedPath = ( $newPath ? $newPath : $filePath);

                $theQuery = "INSERT INTO `".$dbTable."`
                             SET path       = \"".claro_sql_escape($insertedPath)."\",
                                 comment    = \"".claro_sql_escape($newComment)."\",
                                 visibility = \"".claro_sql_escape($newVisibility)."\"";
            } // else noop

        }
        else // ALREADY A RECORD CONCERNING THIS FILE
        {
            if ( is_null($newVisibility ) ) $newVisibility = $oldAttributeList['visibility'];
            if ( is_null($newComment    ) ) $newComment    = $oldAttributeList['comment'   ];

            if ( empty($newComment) && $newVisibility == 'v')
            {
                // NO RELEVANT PARAMETERS ANYMORE => DELETE THE RECORD
                $theQuery = "DELETE FROM `".$dbTable."`
                             WHERE {$modifier} path=\"".$filePath."\"";
            }
            else
            {
                $theQuery = "UPDATE `" . $dbTable . "`
                             SET   comment    = '" . claro_sql_escape($newComment) . "',
                                   visibility = '" . claro_sql_escape($newVisibility) . "'
                             WHERE {$modifier} path     = '" . claro_sql_escape($filePath) . "'";
            }
        } // end else if ! $oldAttributeList

        if( isset($theQuery ) ) claro_sql_query($theQuery);

        if ( $newPath )
        {
            $theQuery = "UPDATE `" . $dbTable . "`
                        SET path = CONCAT('" . claro_sql_escape($newPath) . "',
                                   SUBSTRING(path, LENGTH('" . claro_sql_escape($filePath) . "')+1) )
                        WHERE {$modifier} path = '" . claro_sql_escape($filePath) . "'
                        OR {$modifier} path LIKE '" . claro_sql_escape($filePath) . "/%'";

            claro_sql_query($theQuery);
        }
    } // end else if action == update
}

//------------------------------------------------------------------------------


function update_Doc_Path_in_Assets($type, $oldPath, $newPath)
{
    global $TABLEASSET, $TABLELEARNPATHMODULE,
           $TABLEUSERMODULEPROGRESS, $TABLEMODULE;
           
    if (strtoupper(substr(PHP_OS, 0, 3)) == "WIN")
    {
        $modifier = '';
    }
    else
    {
        $modifier = 'BINARY ';
    }

    switch ($type)
    {
        case 'update' :

            // if the path did not change, don't change it !
            if ( empty($newPath) )
            {
                return false;
            }

            // Find and update assets that are concerned by this move
            $sql = "SELECT `path` FROM `" . $TABLEASSET . "` WHERE {$modifier} `path` = '" . claro_sql_escape($oldPath) . "%'";

            $result = claro_sql_query($sql);

            $num = mysql_num_rows($result);

            // the document with the exact path exists
            if ( $num )
            {

                $sql = "UPDATE `" . $TABLEASSET . "`
                        SET `path` = '" . claro_sql_escape($newPath) . "'
                        WHERE {$modifier} `path` = '" . claro_sql_escape($oldPath) . "'";
            }
            // a document in the renamed directory exists
            else
            {
                $sql = "UPDATE `" . $TABLEASSET . "`
                        SET path = CONCAT('" . claro_sql_escape($newPath) . "',
                                   SUBSTRING(path, LENGTH('" . claro_sql_escape($oldPath) . "')+1) )
                        WHERE {$modifier} path = '" . claro_sql_escape($oldPath) . "'
                        OR {$modifier} path LIKE '" . claro_sql_escape($oldPath) . "/%'";
            }

            claro_sql_query($sql);

            break;

        case 'delete' :

            // delete assets, modules, learning path modules, and userprogress that are based on this document

            // find all assets concerned by this deletion

            $sql ="SELECT *
                   FROM `" . $TABLEASSET . "`
                   WHERE {$modifier} `path` LIKE '" . claro_sql_escape($oldPath) . "%'
                   ";

            $result = claro_sql_query($sql);

            $num = mysql_num_rows($result);
            if ($num != 0)
            {
                  //find all learning path module concerned by the deletion

                  $sqllpm ="SELECT *
                            FROM `" . $TABLELEARNPATHMODULE . "`
                            WHERE 0=1
                            ";

                  while ($list=mysql_fetch_array($result))
                  {
                     $sqllpm.= " OR `module_id` = '" . (int)$list['module_id'] . "' ";
                  }

                  $result2 = claro_sql_query($sqllpm);

                  //delete the learning path module(s)

                  $sql1 ="DELETE
                          FROM `" . $TABLELEARNPATHMODULE . "`
                          WHERE 0=1
                          ";

                  // delete the module(s) concerned
                  $sql2 ="DELETE
                          FROM `" . $TABLEMODULE . "`
                          WHERE 0=1
                         ";

                  $result = mysql_query($sqllpm);//:to reset result resused

                  while ($list=mysql_fetch_array($result))
                  {
                     $sql1.= " OR `module_id` = '" . (int)$list['module_id'] . "' ";
                     $sql2.= " OR `module_id` = '" . (int)$list['module_id'] . "' ";
                  }

                  claro_sql_query($sql1);
                  claro_sql_query($sql2);

                  //delete the user module progress concerned

                  $sql ="DELETE
                         FROM `" . $TABLEUSERMODULEPROGRESS . "`
                         WHERE 0=1
                         ";
                  while ($list=mysql_fetch_array($result2))
                  {
                     $sql.= " OR `learnPath_module_id` = '" . (int)$list['learnPath_module_id'] . "' ";
                  }

                  claro_sql_query($sql);

                  // delete the assets

                  $sql ="DELETE
                         FROM `" . $TABLEASSET . "`
                         WHERE
                         {$modifier} `path` LIKE '" . claro_sql_escape($oldPath) . "%'
                         ";

                  claro_sql_query($sql);
            } //end if($num !=0)
            break;
     }

}

/*
 * Return html content between <body> and </body> from $html
 *
 * @param string $html html content
 *
 * @return string html body content
 */

function get_html_body_content($html)
{
    $body_open_pattern = '/<body[^<>]*>/';
    $body_close_pattern = '/<\/body>/';

    // remove html before <body>
    $split_html = preg_split($body_open_pattern,$html);

    if ( count($split_html) > 1 )
    {
        $html = $split_html[1];
    }

    // remove html after </body>
    $split_html = preg_split($body_close_pattern,$html);

    if ( count($split_html) > 0 )
    {
        $html = $split_html[0];
    }

    return $html;
}
