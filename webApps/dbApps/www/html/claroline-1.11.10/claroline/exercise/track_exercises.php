<?php // $Id: track_exercises.php 14314 2012-11-07 09:09:19Z zefredz $

/**
 * CLAROLINE
 *
 * @version     $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLTRACK
 * @author      Claro Team <cvs@claroline.net>
 */

$tlabelReq = 'CLQWZ';

require '../inc/claro_init_global.inc.php';

include_once dirname(__FILE__) . '/lib/exercise.class.php';

FromKernel::uses( 'utils/input.lib' );

if ( ! claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form(true);
if ( ! claro_is_course_manager() ) claro_die(get_lang('Not allowed'));

$userInput = Claro_UserInput::getInstance();

$exId = (int)$userInput->get( 'exId' );
$src = $userInput->get( 'scr' );
$exportCsv = (int)$userInput->get( 'exportCsv' );

if( $exId == 0 )
{
    claro_redirect("exercise.php");
    exit();
}

/**
 * DB tables definition
 */
$tbl_mdb_names = claro_sql_get_main_tbl();
$tbl_rel_course_user = $tbl_mdb_names['rel_course_user'  ];
$tbl_user            = $tbl_mdb_names['user'             ];

$tbl_cdb_names = get_module_course_tbl( array( 'qwz_exercise',
                                               'qwz_question',
                                               'qwz_rel_exercise_question',
                                               'qwz_tracking',
                                               'qwz_tracking_questions'
                                        ),
                                        claro_get_current_course_id() );
                                        
$tbl_qwz_exercise = $tbl_cdb_names['qwz_exercise'];
$tbl_qwz_question = $tbl_cdb_names['qwz_question'];
$tbl_qwz_rel_exercise_question = $tbl_cdb_names['qwz_rel_exercise_question'];
$tbl_qwz_tracking = $tbl_cdb_names['qwz_tracking'];
$tbl_qwz_tracking_questions = $tbl_cdb_names['qwz_tracking_questions'];


// get exercise details
$exercise = new Exercise();
$exercise->load($exId);

if( $src == 'ex' )
{
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Exercises'), Url::Contextualize('./exercise.php') );
    $src = claro_htmlspecialchars( '&src=ex' );
}
else
{
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Statistics'), Url::Contextualize('../tracking/courseReport.php') );
    $src = '';
}

$nameTools = get_lang('Statistics of exercise');

ClaroBreadCrumbs::getInstance()->setCurrent( $nameTools, Url::Contextualize('./track_exercises.php?exId='.$exId . $src) );


// get the tracking of a question as a csv file
if( get_conf( 'is_trackingEnabled' ) )
{
    require_once( dirname(__FILE__) . '/lib/export_tracking.class.php');
    
    // contruction of XML flow
    switch ( $exportCsv )
    {
        case 1 :
            $csv = export_exercise_tracking( $exId );
            $csvFileName = 'exercise_'. $exId;
            break;
        
        case 2 :
            $exoExport = new ExoExportByUser( $exId );
            $csv = $exoExport->buildCsv();
            $csvFileName = 'exercise_'. $exId.'_results_by_user';
            break;
        
        case 3 :
            $exoExport = new ExoExportByQuestion( $exId );
            $csv = $exoExport->buildCsv();
            $csvFileName = 'exercise_'. $exId.'_results_by_question';
            break;
    }
    
    if( isset($csv) )
    {
        header("Content-type: application/csv");
        header('Content-Disposition: attachment; filename="'. $csvFileName . '.csv"');
        echo $csv;
        exit;
    }
}

$out = '';

// display title
$titleTab['mainTitle'] = $nameTools;
$titleTab['subTitle'] = $exercise->getTitle();

$out .= claro_html_tool_title($titleTab);

if ( get_conf('is_trackingEnabled') )
{
    // get global infos about scores in the exercise
    $sql = "SELECT  MIN(TEX.`result`) AS `minimum`,
                MAX(TEX.`result`) AS `maximum`,
                AVG(TEX.`result`) AS `average`,
                MAX(TEX.`weighting`) AS `weighting` ,
                COUNT(DISTINCT TEX.`user_id`) AS `users`,
                COUNT(TEX.`user_id`) AS `tusers`,
                AVG(`TEX`.`time`) AS `avgTime`
        FROM `".$tbl_qwz_tracking."` AS TEX
        WHERE TEX.`exo_id` = ". (int)$exercise->getId()."
                AND TEX.`user_id` IS NOT NULL";

    $exo_scores_details = claro_sql_query_get_single_row($sql);

    if ( ! isset($exo_scores_details['minimum']) )
    {
        $exo_scores_details['minimum'] = 0;
        $exo_scores_details['maximum'] = 0;
        $exo_scores_details['average'] = 0;
    }
    else
    {
        // round average number for a better display
        $exo_scores_details['average'] = (round($exo_scores_details['average']*100)/100);
    }

    if (isset($exo_score_details['weighting']) || $exo_scores_details['weighting'] != '')
        $displayedWeighting = '/'.$exo_scores_details['weighting'];
    else
        $displayedWeighting = '';

    $out .= '<ul>'."\n"
    .'<li>'.get_lang('Worst score').' : '.$exo_scores_details['minimum'].$displayedWeighting.'</li>'."\n"
    .'<li>'.get_lang('Best score').' : '.$exo_scores_details['maximum'].$displayedWeighting.'</li>'."\n"
    .'<li>'.get_lang('Average score').' : '.$exo_scores_details['average'].$displayedWeighting.'</li>'."\n"
    .'<li>'.get_lang('Average Time').' : '.claro_html_duration(floor($exo_scores_details['avgTime'])).'</li>'."\n"
    .'</ul>'."\n\n"
    .'<ul>'."\n"
    .'<li>'.get_lang('User attempts').' : '.$exo_scores_details['users'].'</li>'."\n"
    .'<li>'.get_lang('Total attempts').' : '.$exo_scores_details['tusers'].'</li>'."\n"
    .'</ul>'."\n\n";

    if ( claro_is_allowed_to_edit() )
    {
        $out .= '<ul>'."\n"
                .'<li><a href="'.claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'].'?exportCsv=1&exId='.$exId ) ).'">'.get_lang('Get tracking data in a CSV file').'</a></li>'."\n"
                .'</ul>'."\n\n";
    }
    

    //-- display details : USERS VIEW
    $sql = "SELECT `U`.`nom`, `U`.`prenom`, `U`.`user_id`,
            MIN(TE.`result`) AS `minimum`,
            MAX(TE.`result`) AS `maximum`,
            AVG(TE.`result`) AS `average`,
            COUNT(TE.`result`) AS `attempts`,
            AVG(TE.`time`) AS `avgTime`
    FROM (`".$tbl_user."` AS `U`, `".$tbl_rel_course_user."` AS `CU`, `".$tbl_qwz_exercise."` AS `QT`)
    LEFT JOIN `".$tbl_qwz_tracking."` AS `TE`
          ON `CU`.`user_id` = `TE`.`user_id`
          AND `QT`.`id` = `TE`.`exo_id`
    WHERE `CU`.`user_id` = `U`.`user_id`
      AND `CU`.`code_cours` = '" . claro_sql_escape(claro_get_current_course_id()) . "'
      AND (
            `TE`.`exo_id` = ". (int)$exercise->getId()."
            OR
            `TE`.`exo_id` IS NULL
          )
    GROUP BY `U`.`user_id`
    ORDER BY `U`.`nom` ASC, `U`.`prenom` ASC";


    $exo_users_details = claro_sql_query_fetch_all($sql);

    $out .= '<p><b>'.get_lang('Statistics by user').'</b>&nbsp;'.
            '<a href="'.claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'].'?exportCsv=2&exId='.$exId ) ).'">['.get_lang('Export').']</a></p>'."\n";
    // display tab header
    $out .= '<table class="claroTable emphaseLine" width="100%" border="0" cellspacing="2">'."\n\n"
        .'<thead>'."\n"
        .'<tr align="center" valign="top">'."\n"
        .'<th>'.get_lang('Student').'</th>'."\n"
        .'<th>'.get_lang('Worst score').'</th>'."\n"
        .'<th>'.get_lang('Best score').'</th>'."\n"
        .'<th>'.get_lang('Average score').'</th>'."\n"
        .'<th>'.get_lang('Attempts').'</th>'."\n"
        .'<th>'.get_lang('Average Time').'</th>'."\n"
        .'</tr>'."\n"
        .'</thead>'."\n"
        .'<tbody>'."\n\n";

    // display tab content
    foreach( $exo_users_details as $exo_users_detail )
    {
        if ( $exo_users_detail['attempts'] == 0 )
        {
            $exo_users_detail['minimum'] = '-';
            $exo_users_detail['maximum'] = '-';
            $displayedAverage = '-';
            $displayedAvgTime = '-';
        }
        else
        {
            $displayedAverage = round($exo_users_detail['average']*100)/100;
            $displayedAvgTime = claro_html_duration(floor($exo_users_detail['avgTime']));
        }
        $out .=      '<tr>'."\n"
                  .'<td><a href="'.claro_htmlspecialchars( Url::Contextualize('../tracking/userReport.php?userId='.$exo_users_detail['user_id'].'&exId='.$exercise->getId() ) ).'">'."\n"
                .$exo_users_detail['nom'].' '.$exo_users_detail['prenom'].'</a></td>'."\n"
                  .'<td>'.$exo_users_detail['minimum'].'</td>'."\n"
                  .'<td>'.$exo_users_detail['maximum'].'</td>'."\n"
                  .'<td>'.$displayedAverage.'</td>'."\n"
                  .'<td>'.$exo_users_detail['attempts'].'</td>'."\n"
                  .'<td>'.$displayedAvgTime.'</td>'."\n"
                .'</tr>'."\n\n";
    }
    // foot of table
    $out .= '</tbody>'."\n".'</table>'."\n\n";

    // display details : QUESTIONS VIEW
    $sql = "SELECT `Q`.`id`, `Q`.`title`, `Q`.`type`, `Q`.`grade`,
                  MIN(TED.`result`) AS `minimum`,
                MAX(TED.`result`) AS `maximum`,
                AVG(TED.`result`) AS `average`
        FROM (`".$tbl_qwz_question."` AS `Q`, `".$tbl_qwz_rel_exercise_question."` AS `RTQ`)
        LEFT JOIN `".$tbl_qwz_tracking."` AS `TE`
            ON `TE`.`exo_id` = `RTQ`.`exerciseId`
        LEFT JOIN `".$tbl_qwz_tracking_questions."` AS `TED`
              ON `TED`.`exercise_track_id` = `TE`.`id`
            AND `TED`.`question_id` = `Q`.`id`
        WHERE `Q`.`id` = `RTQ`.`questionId`
            AND `RTQ`.`exerciseId` = ". (int)$exercise->getId()."
        GROUP BY `Q`.`id`
        ORDER BY `RTQ`.`rank` ASC";

    $exo_questions_details = claro_sql_query_fetch_all($sql);

    $out .= '<p><b>'.get_lang('Statistics by question').'</b>&nbsp;'."\n".
            '<a href="'.claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'].'?exportCsv=3&exId='.$exId ) ).'">['.get_lang('Export').']</a></p>'."\n";
    // display tab header
    $out .= '<table class="claroTable emphaseLine" width="100%" border="0" cellspacing="2">'."\n"
        .'<thead>'."\n"
        .'<tr align="center" valign="top">'."\n"
        .'<th>'.get_lang('Question title').'</th>'."\n"
        .'<th>'.get_lang('Worst score').'</th>'."\n"
        .'<th>'.get_lang('Best score').'</th>'."\n"
        .'<th>'.get_lang('Average score').'</th>'."\n"
        .'</tr>'."\n"
        .'</thead>'."\n\n"
        .'<tbody>'."\n\n";
    // display tab content
    foreach ( $exo_questions_details as $exo_questions_detail )
    {
        if ( $exo_questions_detail['minimum'] == '' )
        {
            $exo_questions_detail['minimum'] = 0;
            $exo_questions_detail['maximum'] = 0;
        }
        $out .=      '<tr>'."\n"
                  .'<td><a href="'.claro_htmlspecialchars( Url::Contextualize( 'track_questions.php?question_id='.$exo_questions_detail['id'].'&exId='.$exId.$src ) ).'">'.$exo_questions_detail['title'].'</a></td>'."\n"
                  .'<td>'.$exo_questions_detail['minimum'].'/'.$exo_questions_detail['grade'].'</td>'."\n"
                  .'<td>'.$exo_questions_detail['maximum'].'/'.$exo_questions_detail['grade'].'</td>'."\n"
                  .'<td>'.(round($exo_questions_detail['average']*100)/100).'/'.$exo_questions_detail['grade'].'</td>'."\n"
                .'</tr>'."\n\n";
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
