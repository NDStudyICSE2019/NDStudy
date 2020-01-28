<?php // $Id: insertMyDoc.php 14659 2014-01-22 15:25:44Z zefredz $

/**
 * CLAROLINE
 *
 * @version     1.11 $Revision: 14659 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Piraux Sebastien <pir@cerdecam.be>
 * @author      Lederer Guillaume <led@cerdecam.be>
 * @package     CLLNP
 */

/*======================================
       CLAROLINE MAIN
  ======================================*/
$tlabelReq = 'CLLNP';
require '../inc/claro_init_global.inc.php';

// if there is an auth information missing redirect to the first page of lp tool
// this page will do the necessary to auth the user,
// when leaving a course all the LP sessions infos are cleared so we use this trick to avoid other errors

if ( ! claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form(true);

$is_allowedToEdit = claro_is_allowed_to_edit();

if ( ! $is_allowedToEdit ) claro_die(get_lang('Not allowed'));

ClaroBreadCrumbs::getInstance()->prepend( 
    get_lang('Learning path'), 
    Url::Contextualize(get_module_url('CLLNP') . '/learningPathAdmin.php') 
);

ClaroBreadCrumbs::getInstance()->prepend( 
    get_lang('Learning path list'), 
    Url::Contextualize(get_module_url('CLLNP') . '/learningPathList.php') 
);

$nameTools = get_lang('Add a document');

// Command list
$cmdList = array();

$cmdList[] = array(
    'img' => 'back',
    'name' => get_lang('Back to learning path administration'),
    'url' => claro_htmlspecialchars(Url::Contextualize('learningPathAdmin.php'))
);

$out = '';

// tables names

$tbl_cdb_names = claro_sql_get_course_tbl();

$tbl_lp_learnPath            = $tbl_cdb_names['lp_learnPath'           ];
$tbl_lp_rel_learnPath_module = $tbl_cdb_names['lp_rel_learnPath_module'];
$tbl_lp_user_module_progress = $tbl_cdb_names['lp_user_module_progress'];
$tbl_lp_module               = $tbl_cdb_names['lp_module'              ];
$tbl_lp_asset                = $tbl_cdb_names['lp_asset'               ];

$TABLELEARNPATH            = $tbl_lp_learnPath;
$TABLELEARNPATHMODULE      = $tbl_lp_rel_learnPath_module;
$TABLEUSERMODULEPROGRESS   = $tbl_lp_user_module_progress;
$TABLEMODULE               = $tbl_lp_module;
$TABLEASSET                = $tbl_lp_asset;

$dbTable = $tbl_cdb_names['document'];

// document browser vars
$TABLEDOCUMENT = claro_get_current_course_data('dbNameGlu') . 'document';

$courseDir   = claro_get_course_path() . '/document';
$moduleDir   = claro_get_course_path() . '/modules';
$baseWorkDir = get_path('coursesRepositorySys').$courseDir;
$moduleWorkDir = get_path('coursesRepositorySys').$moduleDir;

//lib of this tool
require_once(get_path('incRepositorySys') . "/lib/learnPath.lib.inc.php");

require_once(get_path('incRepositorySys') . "/lib/fileDisplay.lib.php");
require_once(get_path('incRepositorySys') . "/lib/fileManage.lib.php");

// $_SESSION
if ( !isset($_SESSION['path_id']) )
{
      claro_redirect(Url::Contextualize("./learningPath.php"));
}

/*======================================
       CLAROLINE MAIN
 ======================================*/

// FUNCTION NEEDED TO BUILD THE QUERY TO SELECT THE MODULES THAT MUST BE AVAILABLE

// 1)  We select first the modules that must not be displayed because
// as they are already in this learning path

function buildRequestModules()
{

 global $TABLELEARNPATHMODULE;
 global $TABLEMODULE;

 $firstSql = "SELECT `module_id`
              FROM `".$TABLELEARNPATHMODULE."` AS LPM
              WHERE LPM.`learnPath_id` = ". (int)$_SESSION['path_id'];

 $firstResult = claro_sql_query($firstSql);

 // 2) We build the request to get the modules we need

 $sql = "SELECT M.*
         FROM `".$TABLEMODULE."` AS M
         WHERE 1 = 1";

 while ($list=mysql_fetch_array($firstResult))
 {
    $sql .=" AND M.`module_id` != ". (int)$list['module_id'];
 }

 /** To find which module must displayed we can also proceed  with only one query.
  * But this implies to use some features of MySQL not available in the version 3.23, so we use
  * two differents queries to get the right list.
  * Here is how to proceed with only one

  $query = "SELECT *
             FROM `".$TABLEMODULE."` AS M
             WHERE NOT EXISTS(SELECT * FROM `".$TABLELEARNPATHMODULE."` AS TLPM
             WHERE TLPM.`module_id` = M.`module_id`)";
  */

  return $sql;

}//end function

//####################################################################################\\
//################################ DOCUMENTS LIST ####################################\\
//####################################################################################\\

// display title

$out .= claro_html_tool_title($nameTools, null, $cmdList);

// FORM SENT
/*
 *
 * SET THE DOCUMENT AS A MODULE OF THIS LEARNING PATH
 *
 */

// evaluate how many form could be sent
if (!isset($dialogBox)) $dialogBox = "";

$iterator = 0;

if (!isset($_REQUEST['maxDocForm'])) $_REQUEST['maxDocForm'] = 0;

if (strtoupper(substr(PHP_OS, 0, 3)) == "WIN")
{
    $modifier = '';
}
else
{
    $modifier = 'BINARY ';
}

while ($iterator <= $_REQUEST['maxDocForm'])
{
    $iterator++;

    if (isset($_REQUEST['submitInsertedDocument']) && isset($_POST['insertDocument_'.$iterator]) )
    {
        $insertDocument = str_replace('..', '',$_POST['insertDocument_'.$iterator]);

        $sourceDoc = $baseWorkDir.$insertDocument;

        if ( check_name_exist($sourceDoc) ) // source file exists ?
        {
            // check if a module of this course already used the same document
            $sql = "SELECT *
                    FROM `".$TABLEMODULE."` AS M, `".$TABLEASSET."` AS A
                    WHERE A.`module_id` = M.`module_id`
                      AND {$modifier} A.`path` LIKE \"". claro_sql_escape($insertDocument)."\"
                      AND M.`contentType` = \"".CTDOCUMENT_."\"";
            $query = claro_sql_query($sql);
            $num = mysql_num_rows($query);
            $basename = substr($insertDocument, strrpos($insertDocument, '/') + 1);

            if($num == 0)
            {
                // create new module
                $sql = "INSERT INTO `".$TABLEMODULE."`
                        (`name` , `comment`, `contentType`, 'launch_data')
                        VALUES ('". claro_sql_escape($basename) ."' , '". claro_sql_escape(get_block('blockDefaultModuleComment')) . "', '".CTDOCUMENT_. "', '' )";
                $query = claro_sql_query($sql);

                $insertedModule_id = claro_sql_insert_id();

                // create new asset
                $sql = "INSERT INTO `".$TABLEASSET."`
                        (`path` , `module_id` , `comment`)
                        VALUES ('". claro_sql_escape($insertDocument)."', " . (int)$insertedModule_id . ", '')";
                $query = claro_sql_query($sql);

                $insertedAsset_id = claro_sql_insert_id();

                $sql = "UPDATE `".$TABLEMODULE."`
                        SET `startAsset_id` = " . (int)$insertedAsset_id . "
                        WHERE `module_id` = " . (int)$insertedModule_id . "";
                $query = claro_sql_query($sql);

                // determine the default order of this Learning path
                $sql = "SELECT MAX(`rank`)
                        FROM `".$TABLELEARNPATHMODULE."`";
                $result = claro_sql_query($sql);

                list($orderMax) = mysql_fetch_row($result);
                $order = $orderMax + 1;

                // finally : insert in learning path
                $sql = "INSERT INTO `".$TABLELEARNPATHMODULE."`
                        (`learnPath_id`, `module_id`, `specificComment`, `rank`, `lock`)
                        VALUES ('". (int)$_SESSION['path_id']."', '".(int)$insertedModule_id."','".claro_sql_escape(get_block('blockDefaultModuleAddedComment'))."', ".(int)$order.", 'OPEN')";
                $query = claro_sql_query($sql);

                $dialogBox .= get_lang("%moduleName has been added as module", array('%moduleName' => $basename)).'<br />' . "\n";
            }
            else
            {
                // check if this is this LP that used this document as a module
                $sql = "SELECT *
                        FROM `".$TABLELEARNPATHMODULE."` AS LPM,
                             `".$TABLEMODULE."` AS M,
                             `".$TABLEASSET."` AS A
                        WHERE M.`module_id` =  LPM.`module_id`
                          AND M.`startAsset_id` = A.`asset_id`
                          AND {$modifier} A.`path` = '". claro_sql_escape($insertDocument)."'
                          AND LPM.`learnPath_id` = ". (int)$_SESSION['path_id'];
                $query2 = claro_sql_query($sql);
                $num = mysql_num_rows($query2);
                if ($num == 0)     // used in another LP but not in this one, so reuse the module id reference instead of creating a new one
                {
                    $thisDocumentModule = mysql_fetch_array($query);
                    // determine the default order of this Learning path
                    $sql = "SELECT MAX(`rank`)
                            FROM `".$TABLELEARNPATHMODULE."`";
                    $result = claro_sql_query($sql);

                    list($orderMax) = mysql_fetch_row($result);
                    $order = $orderMax + 1;
                    // finally : insert in learning path
                    $sql = "INSERT INTO `".$TABLELEARNPATHMODULE."`
                            (`learnPath_id`, `module_id`, `specificComment`, `rank`,`lock`)
                            VALUES ('". (int)$_SESSION['path_id']."', '". (int)$thisDocumentModule['module_id']."','".claro_sql_escape(get_block('blockDefaultModuleAddedComment'))."', ".(int)$order.",'OPEN')";
                    $query = claro_sql_query($sql);

                    $dialogBox .= get_lang("%moduleName has been added as module", array('%moduleName' => $basename)).'<br />' . "\n";
                }
                else
                {
                    $dialogBox .= get_lang("%moduleName is already used as a module in this learning path", array('%moduleName' => $basename)).'<br />' . "\n";
                }
            }
        }
    }
}

/*======================================
  DEFINE CURRENT DIRECTORY
 ======================================*/

if (isset($_REQUEST['openDir']) ) // $newDirPath is from createDir command (step 2) and $uploadPath from upload command
{
    $curDirPath = $_REQUEST['openDir'];
    /*
     * NOTE: Actually, only one of these variables is set.
     * By concatenating them, we eschew a long list of "if" statements
     */
}
else
{
    $curDirPath="";
}

if ($curDirPath == "/" || $curDirPath == "\\" || strstr($curDirPath, ".."))
{
    $curDirPath =""; // manage the root directory problem

    /*
     * The strstr($curDirPath, "..") prevent malicious users to go to the root directory
     */
}

$curDirName = basename($curDirPath);
$parentDir  = dirname($curDirPath);

if ($parentDir == "/" || $parentDir == "\\")
{
        $parentDir =""; // manage the root directory problem
}

/*======================================
        READ CURRENT DIRECTORY CONTENT
  ======================================*/

/*--------------------------------------
  SEARCHING FILES & DIRECTORIES INFOS
              ON THE DB
  --------------------------------------*/

/* Search infos in the DB about the current directory the user is in */

$sql = "SELECT *
        FROM `".$TABLEDOCUMENT."`
        WHERE {$modifier} `path` LIKE '". claro_sql_escape($curDirPath) ."/%'
        AND {$modifier} `path` NOT LIKE '". claro_sql_escape($curDirPath) ."/%/%'";
$result = claro_sql_query($sql);

$attribute = array();

while($row = mysql_fetch_array($result, MYSQL_ASSOC))
{
    $attribute['path'      ][] = $row['path'      ];
    $attribute['visibility'][] = $row['visibility'];
    $attribute['comment'   ][] = $row['comment'   ];
}

/*--------------------------------------
  LOAD FILES AND DIRECTORIES INTO ARRAYS
  --------------------------------------*/
@chdir (realpath($baseWorkDir.$curDirPath)) or die("<center>
    <b>Wrong directory !</b>
    <br /> Please contact your platform administrator.</center>");

$handle = opendir(".");

define('A_DIRECTORY', 1);
define('A_FILE',      2);

$fileList = array();

while ($file = readdir($handle))
{
    if ($file == "." || $file == "..")
    {
        continue; // Skip current and parent directories
    }

    $fileList['name'][] = $file;

    if(is_dir($file))
    {
        $fileList['type'][] = A_DIRECTORY;
        $fileList['size'][] = false;
        $fileList['date'][] = false;
    }
    elseif(is_file($file))
    {
        $fileList['type'][] = A_FILE;
        $fileList['size'][] = filesize($file);
        $fileList['date'][] = filectime($file);
    }

    /*
     * Make the correspondance between
     * info given by the file system
     * and info given by the DB
     */

    if (!isset($dirNameList)) $dirNameList = array();
    $keyDir = sizeof($dirNameList)-1;

    if (isset($attribute))
    {
        if (isset($attribute['path']))
        {
            $keyAttribute = array_search($curDirPath."/".$file, $attribute['path']);
        }
        else
        {
            $keyAttribute = false;
        }
    }

    if ($keyAttribute !== false)
    {
        $fileList['comment'   ][] = $attribute['comment'   ][$keyAttribute];
        $fileList['visibility'][] = $attribute['visibility'][$keyAttribute];
    }
    else
    {
        $fileList['comment'   ][] = false;
        $fileList['visibility'][] = false;
    }
} // end while ($file = readdir($handle))

/*
 * Sort alphabetically the File list
 */

if ($fileList)
{
    array_multisort($fileList['type'], $fileList['name'],
                    $fileList['size'], $fileList['date'],
                    $fileList['comment'],$fileList['visibility']);
}

/*----------------------------------------
        CHECK BASE INTEGRITY
--------------------------------------*/

if (isset($attribute))
{
    /*
     * check if the number of DB records is greater
     * than the numbers of files attributes previously given
     */

    if ( isset($attribute['path']) && isset($fileList['comment'])
         && ( sizeof($attribute['path']) > (sizeof($fileList['comment']) + sizeof($fileList['visibility'])) ) )
    {
        /* SEARCH DB RECORDS WICH HAVE NOT CORRESPONDANCE ON THE DIRECTORY */
        foreach( $attribute['path'] as $chekinFile)
        {
            if (isset($dirNameList) && in_array(basename($chekinFile), $dirNameList))
                continue;
            elseif (isset($fileNameList) && $fileNameList && in_array(basename($chekinFile), $fileNameList))
                continue;
            else
                $recToDel[]= $chekinFile; // add chekinFile to the list of records to delete
        }

        /* BUILD THE QUERY TO DELETE DEPRECATED DB RECORDS */
        $nbrRecToDel = sizeof ($recToDel);
        $queryClause = "";

        for ($i=0; $i < $nbrRecToDel ;$i++)
        {
            $queryClause .= "{$modifier} path LIKE \"". claro_sql_escape($recToDel[$i]) ."%\"";
            
            if ($i < $nbrRecToDel-1)
            {
                $queryClause .=" OR ";
            }
        }

        $sql = "DELETE
                FROM `".$dbTable."`
                WHERE ".$queryClause;
        claro_sql_query($sql);

        $sql = "DELETE
                FROM `".$dbTable."`
                WHERE `comment` LIKE ''
                  AND `visibility` LIKE 'v'";
        claro_sql_query($sql);

        /* The second query clean the DB 'in case of' empty records (no comment an visibility=v)
           These kind of records should'nt be there, but we never know... */

    }
} // end if (isset($attribute))

closedir($handle);
unset($attribute);

// display list of available documents

$out .= display_my_documents($dialogBox) ;

//####################################################################################\\
//################################## MODULES LIST ####################################\\
//####################################################################################\\

$out .= claro_html_tool_title(get_lang('Learning path content'));

// display list of modules used by this learning path
$out .= display_path_content();

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
