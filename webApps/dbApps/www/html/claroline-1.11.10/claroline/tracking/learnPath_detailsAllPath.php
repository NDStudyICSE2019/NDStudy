<?php // $Id: learnPath_detailsAllPath.php 13708 2011-10-19 10:46:34Z abourguignon $
/**
 * CLAROLINE
 *
 * @version 1.8 $Revision: 13708 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package CLSTAT
 *
 * @author Claro Team <cvs@claroline.net>
 *
 */
 
require '../inc/claro_init_global.inc.php';

if ( ! claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form(true);
if ( ! claro_is_course_manager() ) claro_die(get_lang('Not allowed'));

$nameTools = get_lang('Learning paths tracking');

ClaroBreadCrumbs::getInstance()->prepend( get_lang('Learning path list'), Url::Contextualize(get_module_url('CLLNP') . '/learningPathList.php') );


$tbl_cdb_names               = claro_sql_get_course_tbl();
$tbl_mdb_names               = claro_sql_get_main_tbl();

$tbl_rel_course_user         = $tbl_mdb_names['rel_course_user'  ];
$tbl_user                    = $tbl_mdb_names['user'             ];
$tbl_lp_learnPath            = $tbl_cdb_names['lp_learnPath'           ];
$tbl_lp_rel_learnPath_module = $tbl_cdb_names['lp_rel_learnPath_module'];
$tbl_lp_user_module_progress = $tbl_cdb_names['lp_user_module_progress'];
$tbl_lp_module               = $tbl_cdb_names['lp_module'              ];
$tbl_lp_asset                = $tbl_cdb_names['lp_asset'               ];

// keep old name for inside the library that use the vars in global
$TABLELEARNPATH         = $tbl_lp_learnPath;
$TABLEMODULE            = $tbl_lp_module;
$TABLELEARNPATHMODULE   = $tbl_lp_rel_learnPath_module;
$TABLEASSET             = $tbl_lp_asset;
$TABLEUSERMODULEPROGRESS= $tbl_lp_user_module_progress;

$TABLECOURSUSER            = $tbl_rel_course_user;
$TABLEUSER              = $tbl_user;

$out = '';

require_once get_path('incRepositorySys') . '/lib/statsUtils.lib.inc.php';


require_once get_path('incRepositorySys')."/lib/learnPath.lib.inc.php";

// display title
$titleTab['mainTitle'] = $nameTools;
$titleTab['subTitle'] = get_lang('Progression of users on all learning paths');

$out .= claro_html_tool_title($titleTab);

if ( get_conf('is_trackingEnabled') )
{
    // display a list of user and their respective progress
    
    $sql = "SELECT U.`nom`, U.`prenom`, U.`user_id`
          FROM `".$tbl_user."` AS U, `".$tbl_rel_course_user."`     AS CU
          WHERE U.`user_id`= CU.`user_id`
           AND CU.`code_cours` = '". claro_sql_escape(claro_get_current_course_id()) ."'";
    $usersList = claro_sql_query_fetch_all($sql);
    
    // display tab header
    $out .= '<table class="claroTable emphaseLine" width="100%" border="0" cellspacing="2">'."\n\n"
        .'<tr class="headerX" align="center" valign="top">'."\n"
        .'<th>'.get_lang('Student').'</th>'."\n"
        .'<th colspan="2">'.get_lang('Progress').'</th>'."\n"
        .'</tr>'."\n\n"
        .'<tbody>'."\n\n";
    
    
    // display tab content
    foreach ( $usersList as $user )
    {
        // list available learning paths
        $sql = "SELECT LP.`learnPath_id`
                 FROM `".$tbl_lp_learnPath."` AS LP";

        $learningPathList = claro_sql_query_fetch_all($sql);

        $iterator = 1;
        $globalprog = 0;

        foreach( $learningPathList as $learningPath )
        {
            // % progress
            $prog = get_learnPath_progress($learningPath['learnPath_id'], $user['user_id']);

            if ($prog >= 0)
            {
                $globalprog += $prog;
            }
            $iterator++;
        }


        if( $iterator == 1 )
        {
            $out .= '<tr><td align="center" colspan="8">'.get_lang('No learning path').'</td></tr>'."\n\n";
        }
        else
        {
            $total = round($globalprog/($iterator-1));
            $out .= '<tr>'."\n"
                .'<td><a href="'.get_path('clarolineRepositoryWeb').'tracking/userReport.php?userId='.$user['user_id'].'">'.$user['nom'].' '.$user['prenom'].'</a></td>'."\n"
                .'<td align="right">'
                .claro_html_progress_bar($total, 1)
                .'</td>'."\n"
                   .'<td align="left"><small>'.$total.'%</small></td>'."\n"
                .'</tr>'."\n\n";
        }

    }
    
    // foot of table
    $out .= '</tbody>'."\n\n".'</table>'."\n\n";
    
}
else
{
    $out .= get_lang('Tracking has been disabled by system administrator.');
}

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
