<?php // $Id: modules_pool.php 14314 2012-11-07 09:09:19Z zefredz $

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
 * This is the page where the list of modules of the course present
 * on the platform can be browsed
 * user allowed to edit the course can
 * delete the modules form this page
 */

/*======================================
       CLAROLINE MAIN
  ======================================*/

$tlabelReq = 'CLLNP';
require '../inc/claro_init_global.inc.php';

$is_allowedToEdit = claro_is_allowed_to_edit();
if ( ! claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form(true);
if ( ! $is_allowedToEdit ) claro_die(get_lang('Not allowed'));

$htmlHeadXtra[] =
        '<script type="text/javascript">
        function confirmation (name, timeUsed)
        {
            if (confirm("'.clean_str_for_javascript(get_block('blockConfirmDeleteModule'))
                        . ' \n" + name + "\n '
                        . clean_str_for_javascript(get_lang('Number of learning paths using this module : '))
                        . '" + timeUsed))
                {return true;}
            else
                {return false;}
        }
        </script>';

ClaroBreadCrumbs::getInstance()->prepend( get_lang('Learning path list'), Url::Contextualize(get_module_url('CLLNP') . '/learningPathList.php') );
$nameTools = get_lang('Pool of modules');

// tables names
/*
 * DB tables definition
 */

$tbl_cdb_names = claro_sql_get_course_tbl();
$tbl_lp_learnPath            = $tbl_cdb_names['lp_learnPath'           ];
$tbl_lp_rel_learnPath_module = $tbl_cdb_names['lp_rel_learnPath_module'];
$tbl_lp_user_module_progress = $tbl_cdb_names['lp_user_module_progress'];
$tbl_lp_module               = $tbl_cdb_names['lp_module'              ];
$tbl_lp_asset                = $tbl_cdb_names['lp_asset'               ];

//this bloc would be removed later by direct use of var name
$TABLELEARNPATH         = $tbl_lp_learnPath;
$TABLEMODULE            = $tbl_lp_module;
$TABLELEARNPATHMODULE   = $tbl_lp_rel_learnPath_module;
$TABLEASSET             = $tbl_lp_asset;
$TABLEUSERMODULEPROGRESS= $tbl_lp_user_module_progress;


//lib of this tool
require_once(get_path('incRepositorySys')."/lib/learnPath.lib.inc.php");

/*======================================
       CLAROLINE MAIN
  ======================================*/

$out = '';
// display title
$out .= claro_html_tool_title($nameTools);

// display use explication text
$out .= get_block('blockModulePoolHelp')."<br /><br />";

// HANDLE COMMANDS:
$cmd = ( isset($_REQUEST['cmd']) )? $_REQUEST['cmd'] : '';

switch( $cmd )
{
    // MODULE DELETE
    case "eraseModule" :
        // used to physically delete the module  from server
        require_once(get_path('incRepositorySys')."/lib/fileManage.lib.php");

        $moduleDir   = claro_get_course_path() . '/modules';
        $moduleWorkDir = get_path('coursesRepositorySys').$moduleDir;

        // delete all assets of this module
        $sql = "DELETE
                FROM `".$TABLEASSET."`
                WHERE `module_id` = ". (int)$_REQUEST['cmdid'];
        claro_sql_query($sql);

        // delete from all learning path of this course but keep there id before
        $sql = "SELECT *
                FROM `".$TABLELEARNPATHMODULE."`
                WHERE `module_id` = ". (int)$_REQUEST['cmdid'];
        $result = claro_sql_query($sql);

        $sql = "DELETE
                FROM `".$TABLELEARNPATHMODULE."`
                WHERE `module_id` = ". (int)$_REQUEST['cmdid'];
        claro_sql_query($sql);

        // delete the module in modules table
        $sql = "DELETE
                FROM `".$TABLEMODULE."`
                WHERE `module_id` = ". (int)$_REQUEST['cmdid'];
        claro_sql_query($sql);

        //delete all user progression concerning this module
        $sql = "DELETE
                FROM `".$TABLEUSERMODULEPROGRESS."`
                WHERE 1=0 ";

        while ($list = mysql_fetch_array($result))
        {
            $sql.=" OR `learnPath_module_id`=". (int)$list['learnPath_module_id'];
        }
        
        claro_sql_query($sql);

        // This query does the same as the 3 previous queries but does not work with MySQL versions before 4.0.0
        // delete all asset, all learning path module, and from module table
        /*
        claro_sql_query("DELETE
                       FROM `".$TABLEASSET."`, `".$TABLELEARNPATHMODULE."`, `".$TABLEMODULE."`
                      WHERE `module_id` = ".$_REQUEST['cmdid'] );
        */

        // delete directory and it content
        claro_delete_file($moduleWorkDir."/module_".(int)$_REQUEST['cmdid']);
        break;

    // COMMAND RENAME :
    //display the form to enter new name
    case "rqRename" :
        //get current name from DB
        $query= "SELECT `name`
                 FROM `".$TABLEMODULE."`
                 WHERE `module_id` = '". (int)$_REQUEST['module_id']."'";
        $result = claro_sql_query($query);
        
        $list = mysql_fetch_array($result);
        
        $out .= "\n"
            . '<form method="post" name="rename" action="'.$_SERVER['PHP_SELF'].'">' . "\n"
            . claro_form_relay_context()
            . '<label for="newName">'.get_lang('Insert new name').'</label> :' . "\n"
            . '<input type="text" name="newName" id="newName" value="'.claro_htmlspecialchars($list['name']).'" />' . "\n"
            . '<input type="submit" value="'.get_lang('Ok').'" name="submit" />' . "\n"
            . '<input type="hidden" name="cmd" value="exRename" />' . "\n"
            . '<input type="hidden" name="module_id" value="'.$_REQUEST['module_id'].'" />' . "\n"
            . '</form>' . "\n\n"
            ;
        
        break;

     //try to change name for selected module
    case "exRename" :
        //check if newname is empty
        if( isset($_REQUEST["newName"]) && $_REQUEST["newName"] != "" )
        {
            //check if newname is not already used in another module of the same course
            $sql="SELECT `name`
                  FROM `".$TABLEMODULE."`
                  WHERE `name` = '". claro_sql_escape($_POST['newName'])."'
                    AND `module_id` != '". (int)$_REQUEST['module_id']."'";

            $query = claro_sql_query($sql);
            $num = mysql_num_rows($query);
            if($num == 0 ) // "name" doesn't already exist
            {
                // if no error occurred, update module's name in the database
                $query="UPDATE `".$TABLEMODULE."`
                        SET `name`= '". claro_sql_escape($_POST['newName'])."'
                        WHERE `module_id` = '". (int)$_REQUEST['module_id']."'";

                $result = claro_sql_query($query);
            }
            else
            {
                $dialogBox = new DialogBox();
                $dialogBox->error( get_lang('Error : Name already exists in the learning path or in the module pool') );
                $out .= $dialogBox->render();
            }
        }
        else
        {
            $dialogBox = new DialogBox();
            $dialogBox->error(get_lang('Name cannot be empty'));
            $out .= $dialogBox->render();
        }
        break;

    //display the form to modify the comment
    case "rqComment" :
        if( isset($_REQUEST['module_id']) )
        {
            //get current comment from DB
            $query="SELECT `comment`
                    FROM `".$TABLEMODULE."`
                    WHERE `module_id` = '". (int)$_REQUEST['module_id']."'";
            $result = claro_sql_query($query);
            $comment = mysql_fetch_array($result);

            if( isset($comment['comment']) )
            {
                $out .= '<form method="get" action="' . $_SERVER['PHP_SELF'] . '">' . "\n"
                    . claro_form_relay_context()
                    . claro_html_textarea_editor('comment', $comment['comment'], 15, 55) . "\n"
                    . '<br />' . "\n"
                    . '<input type="hidden" name="cmd" value="exComment" />' . "\n"
                    . '<input type="hidden" name="module_id" value="' . $_REQUEST['module_id'] . '" />' . "\n"
                    . '<input type="submit" value="' . get_lang('Ok') . '" />' . "\n"
                    . '<br /><br />' . "\n"
                    . '</form>' . "\n"
                    ;
            }
        } // else no module_id
        break;

    //make update to change the comment in the database for this module
    case "exComment":
        if( isset($_REQUEST['module_id']) && isset($_REQUEST['comment']) )
        {
            $sql = "UPDATE `".$TABLEMODULE."`
                    SET `comment` = '". claro_sql_escape($_REQUEST['comment']) . "'
                    WHERE `module_id` = " . (int)$_REQUEST['module_id'];
            claro_sql_query($sql);
        }
        break;
}

$sql = "SELECT M.*,
               count(M.`module_id`) AS timesUsed
        FROM `" . $TABLEMODULE . "` AS M
          LEFT JOIN `".$TABLELEARNPATHMODULE."` AS LPM ON LPM.`module_id` = M.`module_id`
        WHERE M.`contentType` != '".CTSCORM_."'
          AND M.`contentType` != '".CTLABEL_."'
        GROUP BY M.`module_id`
        ORDER BY M.`name` ASC, M.`contentType`ASC, M.`accessibility` ASC";

$result = claro_sql_query($sql);
$atleastOne = false;


$out .= '<table class="claroTable" width="100%" border="0" cellspacing="2">'
    . '<thead>' . "\n"
    . '<tr class="headerX" align="center" valign="top">' . "\n"
    . '<th>' . "\n"
    . get_lang('Module') . "\n"
    . '</th>' . "\n"
    . '<th>' . "\n"
    . get_lang('Delete') . "\n"
    . '</th>' . "\n"
    . '<th>' . "\n"
    . get_lang('Rename') . "\n"
    . '</th>' . "\n"
    . '<th>' . "\n"
    . get_lang('Comment') . "\n"
    . '</th>' . "\n"
    . '</tr>' . "\n"
    . '</thead>' . "\n"
    . '<tbody>' . "\n"
    ;

// Display modules of the pool of this course

while ($list = mysql_fetch_array($result))
{
    //DELETE , RENAME, COMMENT

    $contentType_img = selectImage($list['contentType']);
    $contentType_alt = selectAlt($list['contentType']);
    $out .= '<tr>' . "\n"
        . '<td align="left">' . "\n"
        . '<img src="' . $contentType_img . '" alt="'.$contentType_alt.'" /> '.$list['name'] . "\n"
        . '</td>' . "\n"
        . '<td align="center">' . "\n"
        . '<a href="'.claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'].'?cmd=eraseModule&cmdid='.$list['module_id'])).'"'
        . ' onclick="return confirmation(\''.clean_str_for_javascript($list['name']).'\', \''.$list['timesUsed'] .'\');">'
        . '<img src="' . get_icon_url('delete') . '" alt="'.get_lang('Delete').'" />'
        . '</a>' . "\n"
        . '</td>' . "\n"
        . '<td align="center">' . "\n"
        . '<a href="'.claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'].'?cmd=rqRename&module_id='.$list['module_id'])).'">'
        . '<img src="' . get_icon_url('edit') . '" alt="'.get_lang('Rename').'" />'
        . '</a>' . "\n"
        . '</td>' . "\n"
        . '<td align="center">' . "\n"
        . '<a href="'.claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'].'?cmd=rqComment&module_id='.$list['module_id'])).'">'
        . '<img src="' . get_icon_url('comment') . '" alt="'.get_lang('Comment').'" />'
        . '</a>' . "\n"
        . '</td>' . "\n"
        . '</tr>' . "\n\n"
        ;

    if ( isset($list['comment']) )
    {
        $out .= '<tr>'
            . '<td colspan="5">'
            . '<small>' . $list['comment'] . '</small>'
            . '</td>'
            . '</tr>'
            ;
    }

    $atleastOne = true;

} //end while another module to display

if ($atleastOne == false)
{
    $out .= '<tr><td align="center" colspan="5">'.get_lang('No module').'</td></tr>' . "\n";
}

// Display button to add selected modules

$out .= '</tbody>' . "\n"
    . '</table>'
    ;

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
