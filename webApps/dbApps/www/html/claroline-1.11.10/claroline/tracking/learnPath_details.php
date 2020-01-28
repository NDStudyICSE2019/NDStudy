<?php // $Id: learnPath_details.php 14314 2012-11-07 09:09:19Z zefredz $
/**
 * CLAROLINE
 *
 * @version 1.11 $Revision: 14314 $
 *
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package CLSTAT
 *
 * @author Claro Team <cvs@claroline.net>
 *
 */
 
require '../inc/claro_init_global.inc.php';

load_module_config('CLLNP');

require_once(get_path('incRepositorySys').'/lib/class.lib.php');

if ( ! claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form(true);
if ( ! claro_is_course_manager() ) claro_die(get_lang('Not allowed')) ;

// path id can not be empty, return to the list of learning paths
if( empty($_REQUEST['path_id']) )
{
    claro_redirect("../learnPath/learningPathList.php");
    exit();
}

$nameTools = get_lang('Learning paths tracking');

ClaroBreadCrumbs::getInstance()->setCurrent( $nameTools, Url::Contextualize('learnPath_details.php?path_id='.$_REQUEST['path_id']) );
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Learning path list'), Url::Contextualize(get_module_url('CLLNP') . '/learningPathList.php') );

// regroup table names for maintenance purpose
/*
 * DB tables definition
 */

$tbl_cdb_names               = claro_sql_get_course_tbl();
$tbl_mdb_names               = claro_sql_get_main_tbl();
$tbl_rel_course_user         = $tbl_mdb_names['rel_course_user'  ];
$tbl_user                    = $tbl_mdb_names['user'             ];
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

$TABLECOURSUSER            = $tbl_rel_course_user;
$TABLEUSER              = $tbl_user;

require_once(get_path('incRepositorySys').'/lib/statsUtils.lib.inc.php');
require_once(get_path('incRepositorySys').'/lib/learnPath.lib.inc.php');

$cmd = ( isset($_REQUEST['cmd']) )? $_REQUEST['cmd'] : '';

if ( get_conf( 'cllnp_resetByUserAllowed', false ) || claro_is_allowed_to_edit() )
{
    switch($cmd)
    {
        case "resetLearnPath" :
            $learnPath_id = ( isset($_GET['path_id']) )? $_GET['path_id'] : '';
            $user_id = ( isset($_GET['user_id']) )? $_GET['user_id'] : '';
            if(!empty($learnPath_id) && !empty($user_id))
            {
                $dialogBox = new DialogBox();
                if(resetModuleProgressionByPathId($user_id, $learnPath_id))
                {
                    $dialogBox->success( get_lang('Learning path reset successful') );
                }
                else
                {
                    $dialogBox->error( get_lang('An error occured while resetting learning path ') . $learnPath_id  );
                }
            }
            unset($learnPath_id);
            unset($user_id);
        break;
    }
}

$out = '';
if(!empty($dialogBox))
{
    $out .= $dialogBox->render();
    unset($dialogBox);
}

if ( get_conf('is_trackingEnabled') )
{

    if ( !empty($_REQUEST['path_id']) )
    {
        $path_id = (int) $_REQUEST['path_id'];
        
        $classList = get_class_list_of_course(claro_get_current_course_id());
        
        if ( !empty( $classList ) )
        {
            $groupBy = empty($_GET['groupBy']) ? '' : $_GET['groupBy'];
            
            $groupByCmdList = array();
            $groupByCmdList['class'] = array(
                    'name' => get_lang('Display grouped by class'),
                    'url' => $_SERVER['PHP_SELF'] . '?path_id=' . (int)$path_id . '&groupBy=class'
                );

            if(!empty($groupBy))
            {
                unset($groupByCmdList[$groupBy]);
                $groupByCmdList[] = array(
                    'name' => get_lang('Display ungrouped'),
                    'url' => $_SERVER['PHP_SELF'] . '?path_id=' . (int)$path_id
                );
            }
        
        
            $cmdList = array_values($groupByCmdList);
        }
        else
        {
            $cmdList = array();
            $groupBy = '';
        }
        
        // get infos about the learningPath
        $sql = "SELECT `name`
                FROM `".$TABLELEARNPATH."`
                WHERE `learnPath_id` = ". (int)$path_id;

        $learnPathName = claro_sql_query_get_single_value($sql);
    
        if( $learnPathName )
        {
            // display title
            $titleTab['mainTitle'] = $nameTools;
            $titleTab['subTitle'] = claro_htmlspecialchars($learnPathName);
            $out .= claro_html_tool_title($titleTab, null, $cmdList);

            // display a list of user and their respective progress
            $sql = "SELECT U.`nom`, U.`prenom`, U.`user_id`
                    FROM `".$TABLEUSER."` AS U,
                         `".$TABLECOURSUSER."` AS CU
                    WHERE U.`user_id`= CU.`user_id`
                    AND CU.`code_cours` = '". claro_sql_escape(claro_get_current_course_id()) ."'";

            $usersList = claro_sql_query_fetch_all($sql);
    
            switch ($groupBy) 
            {
                case 'class':
                    $out .= getLearnPathDetailByClass($path_id, $usersList);
                    break;
                default:
                    $out .= getLearnPathDetailTable($path_id, $usersList);
                    break;
            }
        }
    }
}
// not allowed
else
{
    $dialogBox = new DialogBox();
    $dialogBox->success( get_lang('Tracking has been disabled by system administrator.') );
    $out .= $dialogBox->render();
}

$claroline->display->body->appendContent($out);

echo $claroline->display->render();

//******************
function getLearnpathProgressStudentRow($path_id, $user)
{
    if(!isLearnPathProgressionEmpty($user['user_id'], $path_id))
    {
        $groupBy = empty($_GET['groupBy']) ? '' : $_GET['groupBy'];
        $resetCell = '<td align="center"><a href="'. Url::Contextualize($_SERVER['PHP_SELF'] .'?cmd=resetLearnPath&path_id='. (int)$path_id . '&user_id='. (int)$user['user_id'] . '&groupBy=' . $groupBy) .'" onclick="return confirm(\'' . clean_str_for_javascript(get_lang('Do you really want to reset the learning path of ') . $user['prenom'].' '.$user['nom']) .  '?\');"><img src="' . get_icon_url('delete') . '" alt="' . get_lang('Reset') . '" /></a></td>'."\n";
    }
    else
    {
        $resetCell = '<td align="center">' . get_lang('No results available') . '</td>'."\n";
    }
    
    $lpProgress = get_learnPath_progress($path_id,$user['user_id']);
    $out = '<tr>'."\n"
        .'<td><a href="lp_modules_details.php?uInfo='.$user['user_id'].'&amp;path_id='.$path_id.'">'.$user['nom'].' '.$user['prenom'].'</a></td>'."\n"
        .'<td align="right">'
        .claro_html_progress_bar($lpProgress, 1)
          .'</td>'."\n"
        .'<td align="left"><small>'.$lpProgress.'%</small></td>'."\n"
		.$resetCell
        .'</tr>'."\n\n";
        
    return $out;
}
//******************
function getLearnPathDetailTable($path_id, $userList)
{
    // display tab header
    $out = '<table class="claroTable emphaseLine" width="100%" border="0" cellspacing="2">'."\n\n"
           .'<tr class="headerX" align="center" valign="top">'."\n"
        .'<th>'.get_lang('Student').'</th>'."\n"
        .'<th colspan="2">'.get_lang('Progress').'</th>'."\n"
		.'<th>'.get_lang('Reset').'</th>'."\n"
        .'</tr>'."\n\n"
        .'<tbody>'."\n\n";

    // display tab content
    foreach ( $userList as $user )
    {
        $out .= getLearnpathProgressStudentRow($path_id, $user);
    }
    
    // foot of table
    $out .= '</tbody>'."\n\n".'</table><br />'."\n\n";
    
    return $out;
}
//***********************
function getLearnPathDetailByClass($path_id, $courseUserList)
{    
    $classList = get_class_list_of_course(claro_get_current_course_id());
    
    foreach($courseUserList as $user)
    {
        $userList[$user['user_id']] = $user;
    }
    
    //prepare userlist per class while keeping track of classless users
    $classlessUserList = $userList;
    foreach($classList as $classKey => $class)
    {
        $classList[$classKey]['userList'] = array_intersect_key($userList, array_flip(get_class_list_user_id_list(array($class['id']))));
        $classList[$classKey]['name'] = ucfirst(get_lang('class')) . ' ' . $classList[$classKey]['name'];
        $classlessUserList = array_diff_key($classlessUserList, $classList[$classKey]['userList']);
    }
    
    //add remaining users to a "classless" class
    array_unshift($classList, array('name' => '', 'userList' => $classlessUserList));

    $out = '';
    foreach($classList as $class)
    {
        if(empty($class['userList']))
        {
            continue;
        }
        
        if(!empty($class['name']))
        {
            $out .= '<span style="font-weight: bold;">' . $class['name'] . '</span><br />';
        }
        
        $out .= getLearnPathDetailTable($path_id, $class['userList']);
    }
    
    return $out;
}
