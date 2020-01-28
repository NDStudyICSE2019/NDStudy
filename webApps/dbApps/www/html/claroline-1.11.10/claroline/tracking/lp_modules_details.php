<?php  // $Id: lp_modules_details.php 14403 2013-02-18 08:18:35Z kitan1982 $

/**
 * CLAROLINE
 *
 * @version     $Revision: 14403 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 */

require '../inc/claro_init_global.inc.php';

if( empty($_REQUEST['uInfo']) )
{
    claro_redirect("./userReport.php");
    exit();
}
    
if( empty($_REQUEST['path_id']) )
{
      claro_redirect("./userReport.php?uInfo=".$_REQUEST['uInfo']."&view=0010000");
      exit();
}

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

// table names
$TABLELEARNPATH         = $tbl_lp_learnPath;
$TABLEMODULE            = $tbl_lp_module;
$TABLELEARNPATHMODULE   = $tbl_lp_rel_learnPath_module;
$TABLEASSET             = $tbl_lp_asset;
$TABLEUSERMODULEPROGRESS= $tbl_lp_user_module_progress;

$TABLECOURSUSER            = $tbl_rel_course_user;
$TABLEUSER              = $tbl_user;

require_once(get_path('incRepositorySys')."/lib/statsUtils.lib.inc.php");

// lib of learning path tool
require_once(get_path('incRepositorySys')."/lib/learnPath.lib.inc.php");
//lib of document tool
require_once(get_path('incRepositorySys')."/lib/fileDisplay.lib.php");

// only the course administrator or the student himself can view the tracking
$is_allowedToTrack = claro_is_course_manager();
if (isset($uInfo) && claro_is_user_authenticated()) $is_allowedToTrack = $is_allowedToTrack || ($uInfo == claro_get_current_user_id());

// get infos about the user
$sql = "SELECT `nom` AS `lastname`, `prenom` as `firstname`, `email`
        FROM `".$TABLEUSER."`
       WHERE `user_id` = ". (int)$_REQUEST['uInfo'];
$uDetails = claro_sql_query_get_single_row($sql);

// get infos about the learningPath
$sql = "SELECT `name`
        FROM `".$TABLELEARNPATH."`
       WHERE `learnPath_id` = ". (int)$_REQUEST['path_id'];
$lpDetails = claro_sql_query_get_single_row($sql);

////////////////////
////// OUTPUT //////
////////////////////

$interbredcrump[]= array ("url"=>"../learnPath/learningPathList.php", "name"=> get_lang('Learning path list'));
$interbredcrump[]= array ("url"=>"learnPath_details.php?path_id=".$_REQUEST['path_id'], "name"=> get_lang('Statistics'));

$nameTools = get_lang('Modules');

$_SERVER['QUERY_STRING'] = 'uInfo='.$_REQUEST['uInfo']."&path_id=".$_REQUEST['path_id'];

$out = '';

// display title
$titleTab['mainTitle'] = $nameTools;
$titleTab['subTitle'] = $lpDetails['name'];
$out .= claro_html_tool_title($titleTab);


if($is_allowedToTrack && get_conf('is_trackingEnabled'))
{
    //### PREPARE LIST OF ELEMENTS TO DISPLAY #################################

    $sql = "SELECT LPM.`learnPath_module_id`,
                LPM.`parent`,
                LPM.`lock`,
                M.`module_id`,
                M.`contentType`,
                M.`name`,
                UMP.`lesson_status`, UMP.`raw`,
                UMP.`scoreMax`, UMP.`credit`,
                UMP.`session_time`, UMP.`total_time`,
                A.`path`
             FROM (
                 `".$TABLELEARNPATHMODULE."` AS LPM,
                `".$TABLEMODULE."` AS M
                )
       LEFT JOIN `".$TABLEUSERMODULEPROGRESS."` AS UMP
               ON UMP.`learnPath_module_id` = LPM.`learnPath_module_id`
               AND UMP.`user_id` = ". (int)$_REQUEST['uInfo']."
       LEFT JOIN `".$TABLEASSET."` AS A
              ON M.`startAsset_id` = A.`asset_id`
            WHERE LPM.`module_id` = M.`module_id`
              AND LPM.`learnPath_id` = ". (int)$_REQUEST['path_id']."
              AND LPM.`visibility` = 'SHOW'
              AND LPM.`module_id` = M.`module_id`
         GROUP BY LPM.`module_id`
         ORDER BY LPM.`rank`";

    $moduleList = claro_sql_query_fetch_all($sql);

    $extendedList = array();
    foreach( $moduleList as $module )
    {
        $extendedList[] = $module;
    }
  
    // build the array of modules
    // build_element_list return a multi-level array, where children is an array with all nested modules
    // build_display_element_list return an 1-level array where children is the deep of the module
    $flatElementList = build_display_element_list(build_element_list($extendedList, 'parent', 'learnPath_module_id'));

    $moduleNb = 0;
    $globalProg = 0;
    $global_time = "0000:00:00";
   
    // look for maxDeep
    $maxDeep = 1; // used to compute colspan of <td> cells
    for ( $i = 0 ; $i < sizeof($flatElementList) ; $i++ )
    {
        if ($flatElementList[$i]['children'] > $maxDeep) $maxDeep = $flatElementList[$i]['children'] ;
    }
  
    //### SOME USER DETAILS ###########################################
    $out .= get_lang('User') .' : <br />'."\n"
        .'<ul>'."\n"
        .'<li>'.get_lang('Last name').' : '.$uDetails['lastname'].'</li>'."\n"
        .'<li>'.get_lang('First name').' : '.$uDetails['firstname'].'</li>'."\n"
          .'<li>'.get_lang('Email').' : '.$uDetails['email'].'</li>'."\n"
        .'</ul>'."\n\n";

    //### TABLE HEADER ################################################
    $out .= '<br />'."\n"
        .'<table class="claroTable emphaseLine" width="100%" border="0" cellspacing="2">'."\n"
        .'<tr class="headerX" align="center" valign="top">'."\n"
        .'<th colspan="'.($maxDeep+1).'">'.get_lang('Module').'</th>'."\n"
        .'<th>'.get_lang('Last session time').'</th>'."\n"
        .'<th>'.get_lang('Total time').'</th>'."\n"
        .'<th>'.get_lang('Module status').'</th>'."\n"
        .'<th colspan="2">'.get_lang('Progress').'</th>'."\n"
        .'<th>'.get_lang('View student anwsers').'</th>'."\n"
        .'</tr>'."\n"
        .'<tbody>'."\n\n";

    //### DISPLAY LIST OF ELEMENTS #####################################
    foreach ($flatElementList as $module)
    {
        if( $module['scoreMax'] > 0 )
        {
            $progress = @round($module['raw']/$module['scoreMax']*100);
        }
        else
        {
            $progress = 0;
        }

        if ( $module['contentType'] == CTSCORM_ && $module['scoreMax'] <= 0 )
        {
            if ( $module['lesson_status'] == 'COMPLETED' || $module['lesson_status'] == 'PASSED')
            {
                 $progress = 100;
            }
            else
            {
                 $progress = 0;
            }
        }
          
          
        // display the current module name

        $spacingString = '';
        for($i = 0; $i < $module['children']; $i++)
        $spacingString .= '<td width="5">&nbsp;</td>';
        $colspan = $maxDeep - $module['children']+1;

        $out .= '<tr align="center">'."\n".$spacingString.'<td colspan="'.$colspan.'" align="left">';
        //-- if chapter head
        if ( $module['contentType'] == CTLABEL_ )
        {
            $out .= '<b>' . claro_utf8_decode ( $module[ 'name' ] , get_conf ( 'charset' ) ) . '</b>';
        }
        //-- if user can access module
        else
        {
            if($module['contentType'] == CTEXERCISE_ )
            $moduleImgUrl = get_icon_url( 'quiz', 'CLQWZ' );
            else
            $moduleImgUrl = get_icon_url( choose_image(basename($module['path'])) );

            $contentType_alt = selectAlt($module['contentType']);
            $out .= '<img src="' . $moduleImgUrl . '" alt="' . $contentType_alt . '" />'
                . claro_utf8_decode ( $module[ 'name' ] , get_conf ( 'charset' ) );

        }
          
          $out .= '</td>'."\n";
          
          if ($module['contentType'] == CTSCORM_)
          {
              $session_time = preg_replace("/\.[0-9]{0,2}/", "", $module['session_time']);
              $total_time = preg_replace("/\.[0-9]{0,2}/", "", $module['total_time']);
              $global_time = addScormTime($global_time,$total_time);
          }
          elseif($module['contentType'] == CTLABEL_ || $module['contentType'] == CTEXERCISE_ || $module['contentType'] == CTDOCUMENT_)
          {
              $session_time = $module['session_time'];
              $total_time = $module['total_time'];
          }
          else
          {
              // if no progression has been recorded for this module
              // leave
              if($module['lesson_status'] == "")
              {
                $session_time = "&nbsp;";
                $total_time = "&nbsp;";
              }
              else // columns are n/a
              {
                $session_time = "-";
                $total_time = "-";
              }
          }
          //-- session_time
          $out .= '<td>'.$session_time.'</td>'."\n";
          //-- total_time
          $out .= '<td>'.$total_time.'</td>'."\n";
          //-- status
          $out .= '<td>';
          if($module['contentType'] == CTEXERCISE_ && $module['lesson_status'] != "" )
            $out .= ' <a href="userReport.php?uInfo='.$_REQUEST['uInfo'].'&amp;view=0100000&amp;exoDet='.$module['path'].'">'.strtolower($module['lesson_status']).'</a>';
          else
            $out .= strtolower($module['lesson_status']);
          $out .= '</td>'."\n";
          //-- progression
          if($module['contentType'] != CTLABEL_ )
          {
                // display the progress value for current module
                
                $out .= '<td align="right">'.claro_html_progress_bar($progress, 1).'</td>'."\n";
                $out .= '<td align="left"><small>&nbsp;'.$progress.'%</small></td>'."\n";
          }
          else // label
          {
            $out .= '<td colspan="2">&nbsp;</td>'."\n";
          }
          
          if(isAnwsersViewingSupported($module['contentType']) )
          {
                if(claro_get_current_user_id() != (int)$_REQUEST['uInfo'])
                {
                    if(getModuleProgression((int)$_REQUEST['uInfo'], (int)$_REQUEST['path_id'], (int)$module['module_id']))
                    {
                        $out .= '<td>' . "\n"
                        .    '<a href="' . get_path('clarolineRepositoryWeb') . 'learnPath/module.php?cidReset=true&cidReq=' . claro_get_current_course_id() . '&module_id=' . (int)$module['module_id'] . '&path_id=' . (int)$_REQUEST['path_id'] . '&copyFrom=' . (int)$_REQUEST['uInfo'] . '" '
                        .    'onclick="return confirm(\'' . clean_str_for_javascript(get_lang('This will copy the learning path user progression over your own. Do you want to proceed anyway?')) . '\');">' . "\n"
                        .    '<img src="' . get_icon_url('login_as') . '" alt="' . get_lang('Consult') . '" />' . "\n"
                        .    '</a>' . "\n"
                        .    '</td>' . "\n"
                        ;
                    }
                    else
                    {
                        $out .= '<td>' . get_lang('No results available') . '</td>'."\n";
                    }
                }
                else
                {
                    $out .= '<td>' . get_lang('Consulting your own results is not allowed') . '</td>'."\n";
                }
          }
          else
          {
              $out .= '<td>' . get_lang('Unsupported module type') . '</td>'."\n";
          }
          
          if ($progress > 0)
          {
            $globalProg += $progress;
          }
          
          if($module['contentType'] != CTLABEL_)
              $moduleNb++; // increment number of modules used to compute global progression except if the module is a title
           
          $out .= '</tr>'."\n\n";
  }
  $out .= '</tbody>'."\n".'<tfoot>'."\n";
  
  if ($moduleNb == 0)
  {
          $out .= '<tr><td align="center" colspan="6">'.get_lang('No module').'</td></tr>';
  }
  elseif($moduleNb > 0)
  {
            // add a blank line between module progression and global progression
            $out .= '<tr><td colspan="'.($maxDeep+6).'">&nbsp;</td></tr>'."\n";
            // display global stats
            $out .= '<tr>'."\n".'<small>'."\n"
                .'<td colspan="'.($maxDeep+1).'">&nbsp;</td>'."\n"
                .'<td align="right">'.(($global_time != "0000:00:00")? get_lang('Time in learning path') : '&nbsp;').'</td>'."\n"
                .'<td align="center">'.(($global_time != "0000:00:00")? preg_replace("/\.[0-9]{0,2}/", "", $global_time) : '&nbsp;').'</td>'."\n"
                .'<td align="right">'.get_lang('Learning path progression : ').'</td>'."\n"
                .'<td align="right">'
                .claro_html_progress_bar(round($globalProg / ($moduleNb) ), 1)
                .'</td>'."\n"
                .'<td align="left"><small>&nbsp;'.round($globalProg / ($moduleNb) ) .'%</small></td>'."\n"
                .'</tr>';
  }
  $out .= "\n".'</tfoot>'."\n".'</table>'."\n";
}
// not allowed
else
{
    if(!get_conf('is_trackingEnabled'))
    {
        $out .= get_lang('Tracking has been disabled by system administrator.');
    }
    else
    {
        $out .= get_lang('Not allowed');
    }
}

$claroline->display->body->appendContent($out);

echo $claroline->display->render();


//**********************************
function isAnwsersViewingSupported($moduleType = '')
{
    $supportedTypes = array(CTSCORM_);
    
    if(empty($moduleType))
    {
        return false;
    }
    
    if(in_array($moduleType, $supportedTypes))
    {
        return true;
    }
    
    return false;
}
