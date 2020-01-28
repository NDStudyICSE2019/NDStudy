<?php  // $Id: learningPathList.php 14314 2012-11-07 09:09:19Z zefredz $

/**
 * CLAROLINE
 *
 * @version     1.11 $Revision: 14314 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Piraux Sebastien <pir@cerdecam.be>
 * @author      Lederer Guillaume <led@cerdecam.be>
 * @package     CLLNP
 *
 * DESCRIPTION:
 * ************
 * This file display the list of all learning paths availables for the
 * course.
 *
 *  Display :
 *  - Name of tool
 *  - Introduction text for learning paths
 *  - (admin of course) link to create new empty learning path
 *  - (admin of course) link to import (upload) a learning path
 *  - list of available learning paths
 *    - (student) only visible learning paths
 *    - (student) the % of progression into each learning path
 *    - (admin of course) all learning paths with
 *       - modify, delete, statistics, visibility and order, options
 */

/*======================================
       CLAROLINE MAIN
  ======================================*/

$tlabelReq = 'CLLNP';
require '../inc/claro_init_global.inc.php';

if ( ! claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form(true);

/*
 * DB tables definition
 */

$tbl_cdb_names = claro_sql_get_course_tbl();
$tbl_lp_learnPath            = $tbl_cdb_names['lp_learnPath'           ];
$tbl_lp_rel_learnPath_module = $tbl_cdb_names['lp_rel_learnPath_module'];
$tbl_lp_user_module_progress = $tbl_cdb_names['lp_user_module_progress'];
$tbl_lp_module               = $tbl_cdb_names['lp_module'              ];
$tbl_lp_asset                = $tbl_cdb_names['lp_asset'               ];

$TABLELEARNPATH         = $tbl_lp_learnPath;
$TABLEMODULE            = $tbl_lp_module;
$TABLELEARNPATHMODULE   = $tbl_lp_rel_learnPath_module;
$TABLEASSET             = $tbl_lp_asset;
$TABLEUSERMODULEPROGRESS= $tbl_lp_user_module_progress;

//lib of this tool
include_once (get_path('incRepositorySys') . '/lib/learnPath.lib.inc.php');

//lib needed to delete packages
include_once (get_path('incRepositorySys') . '/lib/fileManage.lib.php');

$dialogBox = new DialogBox();

$htmlHeadXtra[] =
          '<script type="text/javascript">
          function confirmation (name)
          {
              if (confirm("'. clean_str_for_javascript(get_lang('Modules of this path will still be available in the pool of modules'))
                            . '\n'
                            . clean_str_for_javascript(get_lang('Are you sure to delete') . ' ?' )
                            . '\n'
                            . '" + name))
                  {return true;}
              else
                  {return false;}
          }
          </script>' . "\n";
$htmlHeadXtra[] =
'<script type="text/javascript">
          function scormConfirmation (name)
          {
              if (confirm("'. clean_str_for_javascript(get_block('blockConfirmDeleteScorm')) . '\n" + name ))
                  {return true;}
              else
                  {return false;}
          }
          </script>' . "\n";

$nameTools = get_lang('Learning path list');

$cmd = ( isset($_REQUEST['cmd']) )? $_REQUEST['cmd'] : '';

if ( $cmd == 'export' )
{
    require_once ('include/scormExport.inc.php');
    
    $scorm = new ScormExport($_REQUEST['path_id']);
    
    if ( !$scorm->export() )
    {
        $dialogBox->title( get_lang('Error exporting SCORM package') );
        foreach( $scorm->getError() as $error)
        {
            $dialogBox->error( $error );
        }
    }
} // endif $cmd == export

// use viewMode
claro_set_display_mode_available(true);

// main page
$is_allowedToEdit = claro_is_allowed_to_edit();
$lpUid = claro_get_current_user_id();

// display introduction
$moduleId = claro_get_current_tool_id(); // Id of the Learning Path introduction Area
$helpAddIntroText = get_block('blockIntroLearningPath');

// execution of commands
switch ( $cmd )
{
    // DELETE COMMAND
    case "delete" :

        // delete learning path
        // have to delete also learningPath_module using this learningPath
        // The first multiple-table delete format is supported starting from MySQL 4.0.0. The second multiple-table delete format is supported starting from MySQL 4.0.2.
        /*  this query should work with mysql > 4
        $sql = "DELETE
        FROM `".$TABLELEARNPATHMODULE."`,
        `".$TABLEUSERMODULEPROGRESS."`,
        `".$TABLELEARNPATH."`
        WHERE `".$TABLELEARNPATHMODULE."`.`learnPath_module_id` = `".$TABLEUSERMODULEPROGRESS."`.`learnPath_module_id`
        AND `".$TABLELEARNPATHMODULE."`.`learnPath_id` = `".$TABLELEARNPATH."`.`learnPath_id`
        AND `".$TABLELEARNPATH."`.`learnPath_id` = ".$_GET['path_id'] ;
        */
        // so we use a multiple query method


        // in case of a learning path made by SCORM, we completely remove files and use in others path of the imported package
        // First we save the module_id of the SCORM modules in a table in case of a SCORM imported package
        //TODO use claro_get_course_data_repository()
        if (is_dir(get_path('coursesRepositorySys').claro_get_course_path() . '/scormPackages/path_' . $_GET['del_path_id']))
        {
            $findsql = "SELECT M.`module_id`
                            FROM  `".$TABLELEARNPATHMODULE."` AS LPM,
                                      `".$TABLEMODULE."` AS M
                            WHERE LPM.`learnPath_id` = ". (int)$_GET['del_path_id']."
                              AND
                                    ( M.`contentType` = '".CTSCORM_."'
                                      OR
                                      M.`contentType` = '".CTLABEL_."'
                                    )
                              AND LPM.`module_id` = M.`module_id`
                                ";
            $findResult =claro_sql_query($findsql);

            // Delete the startAssets

            $delAssetSql = "DELETE
                                FROM `".$TABLEASSET."`
                                WHERE 1=0
                               ";

            while ($delList = mysql_fetch_array($findResult))
            {
                $delAssetSql .= " OR `module_id`=". (int)$delList['module_id'];
            }

            claro_sql_query($delAssetSql);

            // DELETE the SCORM modules

            $delModuleSql = "DELETE
                                 FROM `".$TABLEMODULE."`
                                 WHERE (`contentType` = '".CTSCORM_."' OR `contentType` = '".CTLABEL_."')
                                 AND (1=0
                                 ";

            if (mysql_num_rows($findResult)>0)
            {
                mysql_data_seek($findResult,0);
            }

            while ($delList = mysql_fetch_array($findResult))
            {
                $delModuleSql .= " OR `module_id`=". (int)$delList['module_id'];
            }
            $delModuleSql .= ")";

            claro_sql_query($delModuleSql);

            // DELETE the directory containing the package and all its content
            //TODO use claro_get_course_data_repository()
            $real = realpath(get_path('coursesRepositorySys').claro_get_course_path() . '/scormPackages/path_' . $_GET['del_path_id']);
            
            claro_delete_file($real);

        }   // end of dealing with the case of a scorm learning path.
        else
        {
            $findsql = "SELECT M.`module_id`
                                 FROM  `".$TABLELEARNPATHMODULE."` AS LPM,
                                      `".$TABLEMODULE."` AS M
                                 WHERE LPM.`learnPath_id` = ". (int)$_GET['del_path_id']."
                                 AND M.`contentType` = '".CTLABEL_."'
                                 AND LPM.`module_id` = M.`module_id`
                                 ";
            //echo $findsql;
            $findResult =claro_sql_query($findsql);
            // delete labels of non scorm learning path
            $delLabelModuleSql = "DELETE
                                     FROM `".$TABLEMODULE."`
                                     WHERE 1=0
                                  ";

            while ($delList = mysql_fetch_array($findResult))
            {
                $delLabelModuleSql .= " OR `module_id`=". (int)$delList['module_id'];
            }
            //echo $delLabelModuleSql;
            $query = claro_sql_query($delLabelModuleSql);
        }

        // delete everything for this path (common to normal and scorm paths) concerning modules, progression and path

        // delete all user progression
        $sql1 = "DELETE
                       FROM `".$TABLEUSERMODULEPROGRESS."`
                       WHERE `learnPath_id` = ". (int)$_GET['del_path_id'];
        $query = claro_sql_query($sql1);

        // delete all relation between modules and the deleted learning path
        $sql2 = "DELETE
                       FROM `".$TABLELEARNPATHMODULE."`
                       WHERE `learnPath_id` = ". (int)$_GET['del_path_id'];
        $query = claro_sql_query($sql2);

        // delete the learning path
        $sql3 = "DELETE
                          FROM `".$TABLELEARNPATH."`
                          WHERE `learnPath_id` = ". (int)$_GET['del_path_id'] ;

        $query = claro_sql_query($sql3);

        // notify the event manager with the deletion
        $eventNotifier->notifyCourseEvent("learningpath_deleted",claro_get_current_course_id(), claro_get_current_tool_id(), $_GET['del_path_id'], claro_get_current_group_id(), "0");
        break;

        // ACCESSIBILITY COMMAND
    case "mkBlock" :
    case "mkUnblock" :
        $cmd == "mkBlock" ? $blocking = 'CLOSE' : $blocking = 'OPEN';
        $sql = "UPDATE `".$TABLELEARNPATH."`
                    SET `lock` = '$blocking'
                    WHERE `learnPath_id` = ". (int)$_GET['cmdid']."
                      AND `lock` != '$blocking'";
        $query = claro_sql_query ($sql);
        break;

        // VISIBILITY COMMAND
    case "mkVisibl" :
    case "mkInvisibl" :
        $cmd == "mkVisibl" ? $visibility = 'SHOW' : $visibility = 'HIDE';
        $sql = "UPDATE `".$TABLELEARNPATH."`
                       SET `visibility` = '$visibility'
                     WHERE `learnPath_id` = ". (int)$_GET['visibility_path_id']."
                       AND `visibility` != '$visibility'";
        $query = claro_sql_query ($sql);

        //notify the event manager with the event of new visibility

        if ($visibility == 'SHOW')
        {
            $eventNotifier->notifyCourseEvent("learningpath_visible",claro_get_current_course_id(), claro_get_current_tool_id(), $_GET['visibility_path_id'], claro_get_current_group_id(), "0");
        }
        else
        {
            $eventNotifier->notifyCourseEvent("learningpath_invisible",claro_get_current_course_id(), claro_get_current_tool_id(), $_GET['visibility_path_id'], claro_get_current_group_id(), "0");
        }

        break;

        // ORDER COMMAND
    case "moveUp" :
        $thisLearningPathId = $_GET['move_path_id'];
        $sortDirection = "DESC";
        break;

    case "moveDown" :
        $thisLearningPathId = $_GET['move_path_id'];
        $sortDirection = "ASC";
        break;

    case "changeOrder" :
        // $sortedTab = new Order => id learning path
        $sortedTab = setOrderTab( $_POST['id2sort'] );
        if ($sortedTab)
        {
            foreach ( $sortedTab as $order => $LP_id )
            {
                // `order` is set to $order+1 only for display later
                $sql = "UPDATE `".$TABLELEARNPATH."`
                               SET `rank` = ".($order+1)."
                             WHERE `learnPath_id` = ". (int)$LP_id;
                claro_sql_query($sql);
            }
        }
        break;

        // CREATE COMMAND
    case "create" :
        // create form sent
        if( isset($_POST["newPathName"]) && $_POST["newPathName"] != "")
        {

            // check if name already exists
            $sql = "SELECT `name`
                         FROM `".$TABLELEARNPATH."`
                        WHERE `name` = '". claro_sql_escape($_POST['newPathName']) ."'";
            $query = claro_sql_query($sql);
            $num = mysql_num_rows($query);
            if($num == 0 ) // "name" doesn't already exist
            {
                // determine the default order of this Learning path
                $result = claro_sql_query("SELECT MAX(`rank`)
                                               FROM `".$TABLELEARNPATH."`");

                list($orderMax) = mysql_fetch_row($result);
                $order = $orderMax + 1;

                // create new learning path
                $sql = "INSERT
                              INTO `".$TABLELEARNPATH."`
                                     (`name`, `comment`, `rank`)
                              VALUES ('". claro_sql_escape($_POST['newPathName']) ."','" . claro_sql_escape(trim($_POST['newComment']))."',".(int)$order.")";
                //echo $sql;
                $lp_id = claro_sql_query_insert_id($sql);

                // notify the creation to eventmanager
                $eventNotifier->notifyCourseEvent("learningpath_created",claro_get_current_course_id(), claro_get_current_tool_id(), $lp_id, claro_get_current_group_id(), "0");
            }
            else
            {
                // display error message
                $dialogBox->error( get_lang('Error : Name already exists in the learning path or in the module pool') );
            }
        }
        else  // create form requested
        {
            $dialogBox->form( "\n\n"
                . '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">' . "\n"
                . '<fieldset>'
                . claro_form_relay_context()
                . '<h4>' . get_lang('Create a new learning path') . '</h4>' . "\n"
                . '<dl>'
                . '<dt><label for="newPathName">' . get_lang('Title') . '</label></dt>' . "\n"
                . '<dd><input type="text" name="newPathName" id="newPathName" maxlength="255" /></dd>' . "\n"
                . '<dt><label for="newComment">' . get_lang('Comment') . '</label></dt>' . "\n"
                . '<dd>' . claro_html_textarea_editor('newComment', '', 15, 55) . '</dd>'
                . '</dl>' . "\n"
                . '</fieldset>' . "\n"
                . '<input type="hidden" name="cmd" value="create" />' . "\n"
                . '<input type="submit" value="' . get_lang('Ok') . '" />&nbsp;' . "\n"
                . claro_html_button('learningPathList.php', get_lang('Cancel'))
                . '</form>' . "\n"
            );
        }
        break;
}

// IF ORDER COMMAND RECEIVED
// CHANGE ORDER
if (isset($sortDirection) && $sortDirection)
{
    $sql = "SELECT `learnPath_id`, `rank`
            FROM `".$TABLELEARNPATH."`
            ORDER BY `rank` $sortDirection";
    $result = claro_sql_query($sql);

    // LP = learningPath
    while (list ($LPId, $LPOrder) = mysql_fetch_row($result))
    {
        // STEP 2 : FOUND THE NEXT ANNOUNCEMENT ID AND ORDER.
        //          COMMIT ORDER SWAP ON THE DB

        if (isset($thisLPOrderFound)&&$thisLPOrderFound == true)
        {
            $nextLPId = $LPId;
            $nextLPOrder = $LPOrder;

            // move 1 to a temporary rank
            $sql = "UPDATE `".$TABLELEARNPATH."`
                    SET `rank` = \"-1337\"
                    WHERE `learnPath_id` =  \"" . (int)$thisLearningPathId . "\"";
            claro_sql_query($sql);

            // move 2 to the previous rank of 1
            $sql = "UPDATE `".$TABLELEARNPATH."`
                     SET `rank` = \"" . (int)$thisLPOrder . "\"
                     WHERE `learnPath_id` =  \"" . (int)$nextLPId . "\"";
            claro_sql_query($sql);

            // move 1 to previous rank of 2
            $sql = "UPDATE `".$TABLELEARNPATH."`
                             SET `rank` = \"" . (int)$nextLPOrder . "\"
                           WHERE `learnPath_id` =  \"" . (int)$thisLearningPathId . "\"";
            claro_sql_query($sql);

            break;
        }

        // STEP 1 : FIND THE ORDER OF THE ANNOUNCEMENT
        if ($LPId == $thisLearningPathId)
        {
            $thisLPOrder = $LPOrder;
            $thisLPOrderFound = true;
        }
    }
}
// DISPLAY
// Command list
$cmdList = array();

if($is_allowedToEdit)
{
    $cmdList[] = array(
        'img' => 'default_new',
        'name' => get_lang('Create a new learning path'),
        'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] .'?cmd=create'))
    );
    
    $cmdList[] = array(
        'img' => 'import',
        'name' => get_lang('Import a learning path'),
        'url' => claro_htmlspecialchars(Url::Contextualize('importLearningPath.php'))
    );
    
    $cmdList[] = array(
        'img' => 'module_pool',
        'name' => get_lang('Pool of modules'),
        'url' => claro_htmlspecialchars(Url::Contextualize('modules_pool.php'))
    );
    
    $cmdList[] = array(
        'img' => 'statistics',
        'name' => get_lang('User tracking'),
        'url' => claro_htmlspecialchars(Url::Contextualize(get_path('clarolineRepositoryWeb') . 'tracking/learnPath_detailsAllPath.php'))
    );
}

$out = '';
$out .= claro_html_tool_title($nameTools, null, $cmdList ); //, 2);
$out .= $dialogBox->render();


// Display list of available training paths

/*
This is for dealing with the block in the sequence of learning path,  the idea is to make only one request to get the credit
of last module of learning paths to know if the rest of the sequence mut be blocked or not, does NOT work yet ;) ...

$sql="SELECT LPM.`learnPath_module_id` AS LPMID, LPM.`learnpath_id`, MAX(`rank`) AS M, UMP.`credit` AS UMPC
FROM `".$TABLELEARNPATHMODULE."` AS LPM
RIGHT JOIN `".$TABLEUSERMODULEPROGRESS."` AS UMP
ON LPM.`learnPath_module_id` = UMP.`learnPath_module_id`
WHERE `user_id` = ".$lpUid."
GROUP BY LPM.`learnpath_id`
";


echo $sql."<br />";
$resultB = claro_sql_query($sql);

echo mysql_error();

while ($listB = mysql_fetch_array($resultB))
{
echo "LPMID : ".$listB['LPMID']." rank : ".$listB['M']." LPID : ".$listB['learnpath_id']." credit : ".$listB['UMPC']."<br />";
}

$resultB = claro_sql_query($sql);
*/

if (claro_is_user_authenticated()) $date = $claro_notifier->get_notification_date(claro_get_current_user_id()); // get date for notified "as new" paths

$out .= '<table class="claroTable emphaseLine" width="100%" border="0" cellspacing="2">
 <thead>
 <tr class="headerX" align="center" valign="top">
  <th>' . get_lang('Learning path') . '</th>';

if($is_allowedToEdit)
{
    // Titles for teachers
    $out .= "<th>".get_lang('Modify')."</th>"
    ."<th>".get_lang('Delete')."</th>"
    ."<th>".get_lang('Block')."</th>"
    ."<th>".get_lang('Visibility')."</th>"
    ."<th colspan=\"2\">".get_lang('Order')."</th>"
    ."<th>".get_lang('Export')."</th>";

    if( get_conf('is_trackingEnabled') ) $out .= "<th>".get_lang('Tracking')."</th>";
}
elseif($lpUid)
{
    // display progression only if user is not teacher && not anonymous
    $out .= "<th colspan=\"2\">".get_lang('Progress')."</th>";
}
// close title line
$out .= "</tr>\n</thead>\n<tbody>";

// display invisible learning paths only if user is courseAdmin
if ($is_allowedToEdit)
{
    $visibility = "";
}
else
{
    $visibility = " AND LP.`visibility` = 'SHOW' ";
}
// check if user is anonymous
if($lpUid)
{
    $uidCheckString = "AND UMP.`user_id` = ". (int)$lpUid;
}
else // anonymous
{
    $uidCheckString = "AND UMP.`user_id` IS NULL ";
}

// list available learning paths
$sql = "SELECT LP.* , MIN(UMP.`raw`) AS minRaw, LP.`lock`
           FROM `".$TABLELEARNPATH."` AS LP
     LEFT JOIN `".$TABLELEARNPATHMODULE."` AS LPM
            ON LPM.`learnPath_id` = LP.`learnPath_id`
     LEFT JOIN `".$TABLEUSERMODULEPROGRESS."` AS UMP
            ON UMP.`learnPath_module_id` = LPM.`learnPath_module_id`
            ".$uidCheckString."
         WHERE 1=1
             ".$visibility."
      GROUP BY LP.`learnPath_id`
      ORDER BY LP.`rank`";

$result = claro_sql_query($sql);

// used to know if the down array (for order) has to be displayed
$LPNumber = mysql_num_rows($result);
$iterator = 1;

$is_blocked = false;
while ( $list = mysql_fetch_array($result) ) // while ... learning path list
{
    //modify style if the file is recently added since last login

    if (claro_is_user_authenticated() && $claro_notifier->is_a_notified_ressource(claro_get_current_course_id(), $date, claro_get_current_user_id(), claro_get_current_group_id(), claro_get_current_tool_id(), $list['learnPath_id']))
    {
        $classItem=' hot';
    }
    else // otherwise just display its name normally
    {
        $classItem='';
    }


    if ( $list['visibility'] == 'HIDE' )
    {
        if ($is_allowedToEdit)
        {
            $style=" class=\"invisible\"";
        }
        else
        {
            continue; // skip the display of this file
        }
    }
    else
    {
        $style="";
    }

    $out .= "<tr align=\"center\"".$style.">";

    //Display current learning path name

    if ( !$is_blocked )
    {
        $out .= "<td align=\"left\"><a class=\"item".$classItem."\" href=\"learningPath.php?path_id="
        .$list['learnPath_id']."\"><img src=\"" . get_icon_url('learnpath') . "\" alt=\"\"
             />  ".claro_htmlspecialchars($list['name'])."</a></td>";

        /*
        if( $list['lock'] == 'CLOSE' && ( $list['minRaw'] == -1 || $list['minRaw'] == "" ) )
        {
        if($lpUid)
        {
        if ( !$is_allowedToEdit )
        {
        $is_blocked = true;
        } // never blocked if allowed to edit
        }
        else // anonymous : don't display the modules that are unreachable
        {
        break ;
        }
        } */

        // --------------TEST IF FOLLOWING PATH MUST BE BLOCKED------------------
        // ---------------------(MUST BE OPTIMIZED)------------------------------

        // step 1. find last visible module of the current learning path in DB

        $blocksql = "SELECT `learnPath_module_id`
                     FROM `".$TABLELEARNPATHMODULE."`
                     WHERE `learnPath_id`=". (int)$list['learnPath_id']."
                     AND `visibility` = \"SHOW\"
                     ORDER BY `rank` DESC
                     LIMIT 1
                    ";

        //echo $blocksql;

        $resultblock = claro_sql_query($blocksql);

        // step 2. see if there is a user progression in db concerning this module of the current learning path

        $number = mysql_num_rows($resultblock);
        if ($number != 0)
        {
            $listblock = mysql_fetch_array($resultblock);
            $blocksql2 = "SELECT `credit`
                          FROM `".$TABLEUSERMODULEPROGRESS."`
                          WHERE `learnPath_module_id`=". (int)$listblock['learnPath_module_id']."
                          AND `user_id`='". (int)$lpUid."'
                         ";

            $resultblock2 = claro_sql_query($blocksql2);
            $moduleNumber = mysql_num_rows($resultblock2);
        }
        else
        {
            //echo "no module in this path!";
            $moduleNumber = 0;
        }

        //2.1 no progression found in DB

        if (($moduleNumber == 0)  && ($list['lock'] == 'CLOSE'))
        {
            //must block next path because last module of this path never tried!

            if($lpUid)
            {
                if ( !$is_allowedToEdit )
                {
                    $is_blocked = true;
                } // never blocked if allowed to edit
            }
            else // anonymous : don't display the modules that are unreachable
            {
                $iterator++; // trick to avoid having the "no modules" msg to be displayed
                break;
            }
        }

        //2.2. deal with progression found in DB if at leats one module in this path

        if ($moduleNumber!=0)
        {
            $listblock2 = mysql_fetch_array($resultblock2);

            if (($listblock2['credit']=="NO-CREDIT") && ($list['lock'] == 'CLOSE'))
            {
                //must block next path because last module of this path not credited yet!
                if($lpUid)
                {
                    if ( !$is_allowedToEdit )
                    {
                        $is_blocked = true;
                    } // never blocked if allowed to edit
                }
                else // anonymous : don't display the modules that are unreachable
                {
                    break ;
                }
            }
        }

        //----------------------------------------------------------------------


        /*   This is for dealing with the block in the sequence of learning path,  the idea is to make only one request to get the credit
        of last module of learning paths to know if the rest of the sequence mut be blocked or not, does NOT work yet ;) ...

        if (mysql_num_rows($resultB) != 0) {mysql_data_seek($resultB,0);}

        while ($listB = mysql_fetch_array($resultB))
        {
        echo  "lp_id listB: ".$listB['learnpath_id']." lp_id list: ".$list['learnPath_id']." creditUMP: ".$listB['UMPC']." Lplock: ".$list['lock']."<br />";

        if (($listB['learnpath_id']==$list['learnPath_id']) && ($listB['UMPC']=="NO-CREDIT") && ($list['lock'] == "CLOSE"))
        {
        echo "ok";
        if($lpUid)
        {
        if ( !$is_allowedToEdit )
        {
        echo "on va bloquer pour LPMID : ".$listB['LPMID'];
        $is_blocked = true;
        } // never blocked if allowed to edit
        }
        else // anonymous : don't display the modules that are unreachable
        {
        break ;
        }
        }
        }

        //must also block if no usermoduleprogress exists in DB for this user.

        $LPMNumberB = mysql_num_rows($resultB);
        if (($LPMNumberB == 0) && ($list['lock'] == "CLOSE"))
        {
        echo "ok2";
        if($lpUid)
        {
        if ( !$is_allowedToEdit )
        {
        echo "on va bloquer pour LPMID : ".$listB['LPMID'];
        $is_blocked = true;
        } // never blocked if allowed to edit
        }
        else // anonymous : don't display the modules that are unreachable
        {
        break ;
        }
        }

        */
        //------------------------------------------------------------------------

    }
    else   //else of !$is_blocked condition , we have already been blocked before, so we continue beeing blocked : we don't display any links to next paths any longer
    {
        $out .= '<td align="left">'
        . '<img src="' . get_icon_url('learnpath') . '" alt="" />'
        . $list['name']
        . $list['minRaw']
        . '</td>' . "\n"
        ;
    }

    // DISPLAY ADMIN LINK-----------------------------------------------------------

    if($is_allowedToEdit)
    {
        // 5 administration columns

        // Modify command / go to other page
        $out .= '<td>' . "\n"
        . '<a href="'.claro_htmlspecialchars(Url::Contextualize('learningPathAdmin.php?path_id=' . $list['learnPath_id'])) . '">' . "\n"
        . '<img src="' . get_icon_url('edit') . '" alt="' . get_lang('Modify') . '" />' . "\n"
        . '</a>'
        . '</td>' . "\n"
        ;

        // DELETE link
        $real = realpath(get_path('coursesRepositorySys') . claro_get_course_path() . '/scormPackages/path_' . $list['learnPath_id']);

        // check if the learning path is of a Scorm import package and add right popup:

        if (is_dir($real))
        {
            $out .= '<td>' . "\n"
            . '<a href="' . claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=delete&del_path_id=' . $list['learnPath_id'])) . '" '
            . ' onclick="return scormConfirmation(\'' . clean_str_for_javascript($list['name']) . '\');">' . "\n"
            . '<img src="' . get_icon_url('delete') . '" alt="' . get_lang('Delete') . '" />' . "\n"
            . '</a>' . "\n"
            . '</td>' . "\n"
            ;

        }
        else
        {
            $out .= '<td>' . "\n"
            . '<a href="' . claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=delete&del_path_id=' . $list['learnPath_id'])) . '" '
            . 'onclick="return confirmation(\'' . clean_str_for_javascript($list['name']) . '\');">' . "\n"
            . '<img src="' . get_icon_url('delete') . '" alt="' . get_lang('Delete') . '" />' . "\n"
            . '</a>' . "\n"
            . '</td>' . "\n"
            ;
        }

        // LOCK link

        $out .= "<td>";

        if ( $list['lock'] == 'OPEN')
        {

            $out .= '<a href="' . claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']
            . '?cmd=mkBlock'
            . '&cmdid=' . $list['learnPath_id'] )) . '">' . "\n"
            . '<img src="' . get_icon_url('unblock') . '" '
            . 'alt="' . get_lang('Block') . '" />'. "\n"
            . '</a>' . "\n"
            ;
        }
        else
        {
            $out .= '<a href="' . claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=mkUnblock&cmdid=' . $list['learnPath_id'])) . '">' . "\n"
            . '<img src="' . get_icon_url('block') . '" alt="' . get_lang('Unblock') . '" />' . "\n"
            . '</a>' . "\n"
            ;
        }

        $out .= '</td>' . "\n"
        // VISIBILITY link
        . '<td>' . "\n"
        ;

        if ( $list['visibility'] == 'HIDE')
        {

            $out .= '<a href="' . claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=mkVisibl&visibility_path_id=' . $list['learnPath_id'])) . '">' . "\n"
            . '<img src="' . get_icon_url('invisible') . '" alt="' . get_lang('Make visible') . '" />' . "\n"
            . '</a>' . "\n"
            ;
        }
        else
        {
            if ($list['lock']=='CLOSE')
            {
                $onclick = "onclick=\"return confirm('" . clean_str_for_javascript(get_block('blockConfirmBlockingPathMadeInvisible')) . "');\"";
            }
            else
            {
                $onclick = "";
            }

            $out .= '<a href="' . claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=mkInvisibl&visibility_path_id=' . $list['learnPath_id'])) . '" ' . $onclick . ' >' . "\n"
            . '<img src="' . get_icon_url('visible') . '" alt="' . get_lang('Make invisible') . '" />' . "\n"
            . '</a>' . "\n"
            ;
        }
        $out .=  "</td>\n";

        // ORDER links

        // DISPLAY MOVE UP COMMAND only if it is not the top learning path
        if ($iterator != 1)
        {
            $out .= '<td>' . "\n"
            . '<a href="' . claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=moveUp&move_path_id=' . $list['learnPath_id'])) . '">' . "\n"
            . '<img src="' . get_icon_url('move_up') . '" alt="' . get_lang('Move up') . '" />' . "\n"
            . '</a>' . "\n"
            . '</td>' . "\n"
            ;
        }
        else
        {
            $out .= '<td>&nbsp;</td>' . "\n";
        }

        // DISPLAY MOVE DOWN COMMAND only if it is not the bottom learning path
        if($iterator < $LPNumber)
        {
            $out .= '<td>' . "\n"
            . '<a href="' . claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=moveDown&move_path_id=' . $list['learnPath_id'])) . '">' . "\n"
            . '<img src="' . get_icon_url('move_down') . '" alt="' . get_lang('Move down') . '" />' . "\n"
            . '</a>' . "\n"
            . '</td>' . "\n"
            ;
        }
        else
        {
            $out .= '<td>&nbsp;</td>' . "\n";
        }

        // EXPORT links
        $out .= '<td>' . "\n"
        . '<a href="' . claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=export&path_id=' . $list['learnPath_id'])) . '" >'
        . '<img src="' . get_icon_url('export') . '" alt="' . get_lang('Export') . '" />'
        . '</a>' . "\n"
        . '</td>' . "\n"
        ;

        if( get_conf('is_trackingEnabled') )
        {
            // statistics links
            $out .= '<td>' . "\n"
            . '<a href="' . claro_htmlspecialchars( Url::Contextualize(get_path('clarolineRepositoryWeb') . 'tracking/learnPath_details.php?path_id=' . $list['learnPath_id'])) . '">' . "\n"
            . '<img src="' . get_icon_url('statistics') . '" alt="' . get_lang('Tracking') . '" />'
            . '</a>' . "\n"
            . '</td>'. "\n"
            ;
        }
    }
    elseif($lpUid)
    {
        // % progress
        $prog = get_learnPath_progress($list['learnPath_id'], $lpUid);
        if (!isset($globalprog)) $globalprog = 0;
        if ($prog >= 0)
        {
            $globalprog += $prog;
        }
        $out .= '<td align="right">'
        . claro_html_progress_bar($prog, 1)
        . '</td>' . "\n"
        . '<td align="left">'
        . '<small>' . $prog . '% </small>'
        . '</td>'
        ;
    }
    $out .= '</tr>' . "\n";
    $iterator++;

} // end while

$out .= '</tbody>' . "\n"
. '<tfoot>'
;

if( $iterator == 1 )
{
    $out .= '<tr>' . "\n"
    . '<td align="center" colspan="8">' . "\n"
    . get_lang('No learning path')
    . '</td>' . "\n"
    . '</tr>'
    ;
}
elseif (!claro_is_allowed_to_edit() && $iterator != 1 && $lpUid)
{
    // add a blank line between module progression and global progression
    $total = round($globalprog/($iterator-1));
    $out .= '<tr>' . "\n"
    . '<td colspan="3">' . "\n"
    . '&nbsp;' . "\n"
    . '</td>' . "\n"
    . '</tr>' . "\n"
    . '<tr>' . "\n"
    . '<td align ="right">' . "\n"
    . get_lang('Course progression') . "\n"
    . ' :' . "\n"
    . '</td>' . "\n"
    . '<td align="right" >' . "\n"
    . claro_html_progress_bar($total, 1)
    . '</td>' . "\n"
    . '<td align="left">' . "\n"
    . '<small>'
    . $total . '%' . "\n"
    . '</small>' . "\n"
    . '</td>' . "\n"
    . '</tr>' . "\n"
    ;
}
$out .= '</tfoot>' . "\n"
. '</table>' . "\n"
;

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
