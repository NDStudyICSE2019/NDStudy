<?php // $Id: track_questions.php 14314 2012-11-07 09:09:19Z zefredz $

/**
 * CLAROLINE
 *
 * @version     $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLTRACK
 * @author      Claro Team <cvs@claroline.net>
 * @author      Sebastien Piraux <piraux@claroline.net>
 */

$tlabelReq = 'CLQWZ';

require '../inc/claro_init_global.inc.php';

// check if no anonymous
if ( ! claro_is_in_a_course() || ! claro_is_user_authenticated() ) claro_disp_auth_form(true);

// answer types
define('UNIQUE_ANSWER',  1);
define('MULTIPLE_ANSWER',2);
define('FILL_IN_BLANKS', 3);
define('MATCHING',     4);
define('TRUEFALSE',     5);

if( isset($_REQUEST['exId']) && is_numeric($_REQUEST['exId']) )
{
    $exId = (int) $_REQUEST['exId'];
}
else
{
    header("Location: ".Url::Contextualize( "../exercise/exercise.php" ) );
    exit();
}


require_once('../exercise/lib/question.class.php');

/**
 * DB tables definition
 */

$tbl_mdb_names = claro_sql_get_main_tbl();
$tbl_rel_course_user = $tbl_mdb_names['rel_course_user'  ];
$tbl_user            = $tbl_mdb_names['user'             ];


$tbl_cdb_names = get_module_course_tbl( array( 'qwz_exercise',
                                               'qwz_question',
                                               'qwz_rel_exercise_question',
                                               'qwz_answer_multiple_choice',
                                               'qwz_answer_truefalse',
                                               'qwz_answer_fib',
                                               'qwz_answer_matching',
                                               'qwz_tracking',
                                               'qwz_tracking_questions',
                                               'qwz_tracking_answers'
                                        ),
                                        claro_get_current_course_id() );

$tbl_qwz_question                 = $tbl_cdb_names['qwz_question'];
$tbl_qwz_rel_exercise_question     = $tbl_cdb_names['qwz_rel_exercise_question'];
$tbl_qwz_answer_multiple_choice     = $tbl_cdb_names['qwz_answer_multiple_choice'];
$tbl_qwz_answer_truefalse             = $tbl_cdb_names['qwz_answer_truefalse'];
$tbl_qwz_answer_fib                 = $tbl_cdb_names['qwz_answer_fib'];
$tbl_qwz_answer_matching             = $tbl_cdb_names['qwz_answer_matching'];

$tbl_qwz_tracking     = $tbl_cdb_names['qwz_tracking'];
$tbl_qwz_tracking_questions = $tbl_cdb_names['qwz_tracking_questions'];
$tbl_qwz_tracking_answers = $tbl_cdb_names['qwz_tracking_answers'];

$is_allowedToTrack = claro_is_course_manager();

if( isset($_REQUEST['src']) && $_REQUEST['src'] == 'ex' )
{
    $src = '&src=ex';
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Statistics of exercise'), Url::Contextualize('./track_exercises.php?exId='.$exId . $src ) );
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Exercises'), Url::Contextualize('./exercise.php') );

}
else
{
    $src = '';
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Statistics of exercise'), Url::Contextualize('./track_exercises.php?exId='.$exId));
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Statistics'), Url::Contextualize('../tracking/courseReport.php') );
}
$nameTools = get_lang('Statistics of question');
ClaroBreadCrumbs::getInstance()->setCurrent( $nameTools, Url::Contextualize('./track_questions.php?exId='.$exId . $src) );


// if the question_id is not set display the stats of all questions of this exercise
if( empty($_REQUEST['question_id']) )
{
    // show the list of all question when no one is specified
    // a contribution of Jeremy Audry
    $sql = "SELECT `questionId`
            FROM `".$tbl_qwz_rel_exercise_question."`
            WHERE `exerciseId` = ".(int) $exId;

    $questionList = claro_sql_query_fetch_all($sql);
    // store all question_id for the selected exercise in a tab
    foreach ( $questionList as $question )
    {
        $questionIdsToShow[] = $question['questionId'];
    }
}
// display only the stats of the requested question
else
{
    $questionIdsToShow[0] = (int) $_REQUEST['question_id'];
}


$out = '';
// display title
$titleTab['mainTitle'] = $nameTools;

// Command list
$cmdList = array();
	$cmdList[] = array(
   		'img' => 'back',
    	'name' => get_lang('Back'),
    	'url' => claro_htmlspecialchars(Url::Contextualize( './track_exercises.php?exId='.$exId.$src ) ));

$out .= claro_html_tool_title($titleTab, null, $cmdList);

// build back link
$backLink = "\n\n".'<a class="backLink" href="'.claro_htmlspecialchars( Url::Contextualize( './track_exercises.php?exId='.$exId.$src ) ).'">'
          . get_lang('Back').'</a>'."\n\n";

if($is_allowedToTrack && get_conf('is_trackingEnabled'))
{
    $out .= "\n"
    .     '<table width="100%" border="0" cellpadding="1" cellspacing="0" class="claroTable">' . "\n";

    if( count($questionIdsToShow) > 1 )
    {
        $questionIterator = 1;
    }

    foreach( $questionIdsToShow as $questionId )
    {
        // get infos about the question
        $question = new Question();

        if( !$question->load($questionId) ) break;

        // prepare list to display
        if( $question->getType() == 'MCUA'
            || $question->getType() == 'MCMA' )
        {
            // get the list of all possible answer and the number of times it was choose
            $sql = "SELECT `TEA`.`answer`, COUNT(`TEA`.`answer`) as `nbr`
                        FROM `".$tbl_qwz_tracking."` AS `TE`
                    LEFT JOIN `".$tbl_qwz_tracking_questions."` AS `TED`
                        ON `TED`.`exercise_track_id` = `TE`.`id`
                    LEFT JOIN `".$tbl_qwz_tracking_answers."` AS `TEA`
                        ON `TEA`.`details_id` = `TED`.`id`
                    WHERE `TED`.`question_id` = ".(int) $questionId."
                        AND `TE`.`exo_id` = ".(int) $exId."
                    GROUP BY `TEA`.`answer`";

            $trackedAnswers = claro_sql_query_fetch_all($sql);

            // we need to know the total number of answer given
            $multipleChoiceTotal = 0;
            $i = 0;
            foreach( $question->answer->answerList as $answer )
            {
                $results[$i] = $answer;
                $results[$i]['nbr'] = 0;

                foreach( $trackedAnswers as $trackedAnswer )
                {

                    if( $results[$i]['answer'] == $trackedAnswer['answer'] )
                    {
                        $results[$i]['nbr'] = $trackedAnswer['nbr'];
                        $multipleChoiceTotal += $trackedAnswer['nbr'];
                        break;
                    }
                }
                $i++;
            }

            $displayedStatement = $question->getDescription();
        }
        elseif( $question->getType() == 'TF' )
        {
            // get the list of all possible answer and the number of times it was choose
            $sql = "SELECT `TEA`.`answer`, COUNT(`TEA`.`answer`) as `nbr`
                        FROM (`".$tbl_qwz_question."` AS `Q` ,
                            `".$tbl_qwz_rel_exercise_question."` AS `RTQ`)
                    LEFT JOIN `".$tbl_qwz_tracking."` AS `TE`
                        ON `TE`.`exo_id` = `RTQ`.`exerciseId`
                    LEFT JOIN `".$tbl_qwz_tracking_questions."` AS `TED`
                        ON `TED`.`exercise_track_id` = `TE`.`id`
                        AND `TED`.`question_id` = `Q`.`id`
                    LEFT JOIN `".$tbl_qwz_tracking_answers."` AS `TEA`
                        ON `TEA`.`details_id` = `TED`.`id`
                    WHERE `Q`.`id` = `RTQ`.`questionId`
                        AND `Q`.`id` = ".(int) $questionId."
                        AND `RTQ`.`exerciseId` = ".(int) $exId."
                        AND ( `TEA`.`answer` = 'TRUE' OR `TEA`.`answer` = 'FALSE' )
                    GROUP BY `TEA`.`answer`";

            $results = claro_sql_query_fetch_all($sql);

            // we need to know the total number of answer given
            $multipleChoiceTotal = 0;
            foreach( $results as $result )
            {
                $multipleChoiceTotal += $result['nbr'];
            }

            $displayedStatement = $question->getDescription();
        }
        elseif( $question->getType() == 'FIB' )
        {
            // get the list of all word used in each blank
            // we take id to have a unique key for answer, answer with same id are
            // from the same attempt
            $sql = "SELECT `TED`.`id`,`TEA`.`answer`
                    FROM (
                        `".$tbl_qwz_rel_exercise_question."` AS `RTQ`,
                        `".$tbl_qwz_answer_fib."` AS `A`,
                        `".$tbl_qwz_tracking."` AS `TE`,
                        `".$tbl_qwz_tracking_questions."` AS `TED`,
                        `".$tbl_user."` AS `U`
                       )
                    LEFT JOIN `".$tbl_qwz_tracking_answers."` AS `TEA`
                        ON `TEA`.`details_id` = `TED`.`id`
                    WHERE `RTQ`.`questionId` = ".(int) $questionId."
                        AND `RTQ`.`questionId` = `A`.`questionId`
                        AND `RTQ`.`questionId` = `TED`.`question_id`
                        AND `RTQ`.`exerciseId` = `TE`.`exo_id`
                        AND `TE`.`id` = `TED`.`exercise_track_id`
                        AND `U`.`user_id` = `TE`.`user_id`
                        AND `RTQ`.`exerciseId` = '".(int) $exId."'
                    ORDER BY `TED`.`id` ASC, `TEA`.`id` ASC";

            $answers_details = claro_sql_query_fetch_all($sql);

            $answerText = $question->answer->answerText;
            $answerList = $question->answer->answerList;

            $nbrBlanks = count($answerList);


            $fillInBlanksTotal = array();
            $results = array();
            // in $answers_details we have the list of answers given, each line is one blank filling
            // all blanks of each answers are in the list so we have
            // attempt-blank1 ; attempt1-blank2; attempt2-blank1; attempt2-blank2; ...
            // so we will have to extract and group all blank1 and blank2
            $i = 1;
            foreach( $answers_details as $detail )
            {
                if( !isset($results[$i][$detail['answer']]) )
                {
                    $results[$i][$detail['answer']]['answer'] = $detail['answer'];
                    $results[$i][$detail['answer']]['nbr'] = 1;
                }
                else
                {
                    $results[$i][$detail['answer']]['nbr']++;
                }

                // for each blank we need to compute the number of answers
                if( !isset($fillInBlanksTotal[$i]) )     $fillInBlanksTotal[$i] = 1;
                else                                     $fillInBlanksTotal[$i]++;

                // change blank number until we have meet all blank for the same answer
                if( $i == $nbrBlanks )  $i = 1;
                else                    $i++;
            }

            $displayedStatement = $question->getDescription().'<br /><br />'."\n".'<i>'.claro_parse_user_text($question->answer->answerDecode($answerText)).'</i>'."\n";
        }
        elseif( $question->getType() == 'MATCHING' )
        {
            $displayedStatement = $question->getDescription();

            // get left and right proposals
            $leftList = $question->answer->leftList;
            $rightList = $question->answer->rightList;

            $nbrColumn = 0; // at least one column for headers
            $nbrRow = 0; // at least one row for headers

            foreach( $rightList as $rightElt )
            {
                $nbrColumn++;

                // right column , will be displayed in top headers
                $columnTitlePosition[$rightElt['code']] = $nbrColumn;// to know in which column is which id
                $results[0][$nbrColumn] = $rightElt['answer'];
            }

            foreach( $leftList as $leftElt )
            {
                $nbrRow++;

                // left column , will be displayed in left headers
                $rowTitlePosition[$leftElt['code']] = $nbrRow; // to know in which row is which id
                $results[$nbrRow][0] = $leftElt['answer'];
            }


            // get given answers
            $sql = "SELECT `TEA`.`answer`, COUNT(`TEA`.`answer`) as `nbr`
                        FROM (`".$tbl_qwz_question."` AS `Q` ,
                            `".$tbl_qwz_rel_exercise_question."` AS `RTQ`)
                    LEFT JOIN `".$tbl_qwz_tracking."` AS `TE`
                        ON `TE`.`exo_id` = `RTQ`.`exerciseId`
                    LEFT JOIN `".$tbl_qwz_tracking_questions."` AS `TED`
                        ON `TED`.`exercise_track_id` = `TE`.`id`
                        AND `TED`.`question_id` = `Q`.`id`
                    LEFT JOIN `".$tbl_qwz_tracking_answers."` AS `TEA`
                        ON `TEA`.`details_id` = `TED`.`id`
                    WHERE `Q`.`id` = `RTQ`.`questionId`
                        AND `Q`.`id` = ".(int) $questionId."
                        AND `RTQ`.`exerciseId` = ".(int) $exId."
                    GROUP BY `TEA`.`answer`";

             $trackedAnswers = claro_sql_query_fetch_all($sql);

             foreach( $trackedAnswers as $trackedAnswer )
             {
                if( !is_null($trackedAnswer['answer']) )
                {
                    list($leftProposal, $rightProposal) = explode(' -> ',$trackedAnswer['answer']);

                    // find right code
                    $rightCode = '';
                    if( isset($rightProposal) )
                    {
                        foreach( $rightList as $rightElt )
                        {
                            if( $rightElt['answer'] == $rightProposal )
                            {
                                $rightCode = $rightElt['code'];
                                break;
                            }
                        }
                    }

                    // find left code
                    $leftCode = '';
                    if( isset($leftProposal) )
                    {
                        foreach( $leftList as $leftElt )
                        {
                            if( $leftElt['answer'] == $leftProposal )
                            {
                                $leftCode = $leftElt['code'];
                                break;
                            }
                        }
                    }

                    if( !empty($rightCode) && !empty($leftCode) )
                    {
                        if( isset($rowTitlePosition[$leftCode]) && isset($columnTitlePosition[$rightCode]) )
                        {
                            $results[$rowTitlePosition[$leftCode]][$columnTitlePosition[$rightCode]] = $trackedAnswer['nbr'];
                        }
                    }
                }
            }
        }


        //-- DISPLAY (common)
        //-- display a resume of the selected question

        // several questions have to be shown on the page
        if( isset($questionIterator) )
        {
            $out .= '<tr class="headerX">' . "\n"
            .     '<th>'
            .     get_lang('Question') . ' ' . $questionIterator
            .     '</th>' . "\n"
            .     '</tr>' . "\n\n"
            .     '<tr>'
            .     '<td>' . "\n";

            $questionIterator++;
        }

        $out .= '<p><strong>'.$question->getTitle().'</strong></p>'."\n"
        .     '<blockquote>'.$displayedStatement.'</blockquote>'."\n\n"
        .     '<center>';
        //-- DISPLAY (by question type)
        // prepare list to display
        if( $question->getType() == 'MCUA' || $question->getType() == 'MCMA' )
        {
            // display tab header
            $out .= '<table class="claroTable emphaseLine" width="100%" border="0" cellspacing="2">'."\n"
                .'<tr class="headerX" align="center" valign="top">'."\n"
                .'<th>'.get_lang('Expected choice').'</th>'."\n"
                .'<th width="60%">'.get_lang('Answer').'</th>'."\n"
                .'<th colspan="2">#</th>'."\n"
                  .'</tr>'."\n"
                  .'<tbody>'."\n\n";

            // display tab content

            foreach( $results as $result )
            {
                $out .= '<tr>'."\n"
                        .'<td align="center">';
                // expected choice image
                $out .= '<img src="';
                // choose image to display
                if ($question->getType() != 'MCMA')
                {
                    if( $result['correct'] )    $out .= get_icon_url('radio_on') . '" alt="(X)"';
                    else                        $out .= get_icon_url('radio_off') . '" alt="( )"';
                }
                else
                {
                    if( $result['correct'] )    $out .= get_icon_url('checkbox_on') . '" alt="(X)"';
                    else                        $out .= get_icon_url('checkbox_off') . '" alt="( )"';
                }

                $out .= ' />';

                // compute pourcentage
                if( $result['nbr'] == 0 )    $pourcent = 0;
                else                        $pourcent = round(100 * $result['nbr'] / $multipleChoiceTotal);

                $out .= '</td>' . "\n"
                .    '<td>'
                .    $result['answer']
                .    '</td>' . "\n"
                .    '<td align="right">'
                .    claro_html_progress_bar($pourcent,1)
                .    '</td>' . "\n"
                .    '<td align="left"><small>'
                .    $result['nbr'] . '&nbsp;(&nbsp;' . $pourcent . '%&nbsp;)'
                .    '</small>'
                .    '</td>' . "\n"
                .    '</tr>' . "\n"
                ;
            }

            // foot of table
            $out .= '</tbody>'."\n".'</table>'."\n\n";

        }
        elseif( $question->getType() == 'TF' )
        {
            // display tab header
            $out .= '<table class="claroTable emphaseLine" width="100%" border="0" cellspacing="2">' . "\n"
            .    '<tr class="headerX" align="center" valign="top">' . "\n"
            .    '<th>'
            .    get_lang('Expected choice')
            .    '</th>'."\n"
            .    '<th width="60%">'
            .    get_lang('Answer')
            .    '</th>'."\n"
            .    '<th colspan="2">#</th>' . "\n"
            .    '</tr>'."\n"
            .    '<tbody>' . "\n\n"
            ;

            $truePourcent = 0; $trueSelected = 0;
            $falsePourcent = 0; $falseSelected = 0;
            foreach( $results as $result )
            {
                if( $result['answer'] == 'TRUE' )
                {
                    // compute pourcentage
                    if( $result['nbr'] > 0 ) $truePourcent = round(100 * $result['nbr'] / $multipleChoiceTotal);
                    $trueSelected = $result['nbr'];

                }
                elseif( $result['answer'] == 'FALSE' )
                {
                    // compute pourcentage
                    if( $result['nbr'] > 0 ) $falsePourcent = round(100 * $result['nbr'] / $multipleChoiceTotal);
                    $falseSelected = $result['nbr'];
                }
                // else ignore
            }

            // TRUE
            $out .= '<tr>'."\n"
            .    '<td align="center">'
            // expected choice image
            .    '<img src="'
            ;
            // choose image to display

            if( $question->answer->correctAnswer == 'TRUE' )    $out .= get_icon_url('radio_on').'" alt="(X)"';
            else                                                $out .= get_icon_url('radio_off').'" alt="( )"';

            $out .= ' />';



            $out .= '</td>'."\n"
            .    '<td>'.get_lang('True').'</td>'."\n"
            .    '<td align="right">'.claro_html_progress_bar($truePourcent,1).'</td>'."\n"
            .    '<td align="left"><small>'.$trueSelected.'&nbsp;(&nbsp;'.$truePourcent.'%&nbsp;)</small></td>'."\n"
            .    '</tr>' . "\n"


            // FALSE
            .    '<tr>' . "\n"
            .    '<td align="center">'

            // expected choice image
            .    '<img src="'
            ;
            // choose image to display

            if( $question->answer->correctAnswer == 'FALSE' )   $out .= get_icon_url('radio_on').'" alt="(X)"';
            else                                                $out .= get_icon_url('radio_off').'" alt="( )"';

            $out .= ' />';



            $out .= '</td>'."\n"
            .    '<td>'.get_lang('False').'</td>'."\n"
            .    '<td align="right">'.claro_html_progress_bar($falsePourcent,1).'</td>'."\n"
            .    '<td align="left"><small>'.$falseSelected.'&nbsp;(&nbsp;'.$falsePourcent.'%&nbsp;)</small></td>'."\n"
            .    '</tr>'."\n"



            // foot of table
            .    '</tbody>' . "\n"
            .    '</table>' . "\n\n"
            ;

        }
        elseif( $question->getType() == 'FIB' )
        {
            $i = 1;
            foreach( $answerList as $blank )
            {
                  $out .= '<table class="claroTable emphaseLine" width="100%" border="0" cellspacing="2">'."\n"
                      .'<tr class="headerX">'."\n"
                    .'<th>'.$blank.'</th>'."\n"
                    .'<th width="20%" colspan="2">#</th>'."\n"
                      .'</tr>'."\n";

                if( isset($results[$i]) )
                {
                    // sort array on answer given
                    ksort($results[$i]);
                    foreach( $results[$i] as $result )
                    {
                        // check if we need to use the 'correct' css class
                        if( $result['answer'] == $blank )   $class = ' class="correct" ';
                        else                                $class = '';

                        $out .= '<tr >'
                            .'<td '.$class.'>';
                        if( empty($result['answer']) )     $out .= '('.get_lang('Empty').')';
                        else                             $out .= $result['answer'];

                        if($result['nbr'] == 0 )    $pourcent = 0;
                        else                        $pourcent = round(100 * $result['nbr'] / $fillInBlanksTotal[$i]);

                        $out .= '</td>'."\n"
                            .'<td align="right">'.claro_html_progress_bar($pourcent,1).'</td>'."\n"
                            .'<td align="left"><small>'.$result['nbr'].'&nbsp;(&nbsp;'.$pourcent.'%&nbsp;)</small></td>'."\n"
                            .'</tr>';
                    }
                   }
                   else
                   {
                    $out .= '<tr >'
                        .'<td colspan="2" align="center">'.get_lang('No result').'</td>'."\n"
                        .'</tr>';
                }
                $out .= '</table>'."\n\n"
                    .'<br />'."\n\n";

                $i++;
            }
        }
        elseif( $question->getType() == 'MATCHING' )
        {
            // for each left proposal display the number of time each right proposal has been choosen
            $out .= '<table class="claroTable emphaseLine" border="0" cellspacing="2">'."\n"
                  .'<tr class="headerX">'."\n"
                .'<td>&nbsp;</td>'."\n";

            // these two values are used for numbering of columns and lines
            $letter = 'A';
            $number = 1;
            // display top headers
            for( $i = 1; $i <= $nbrColumn; $i++ )
            {
                $out .= '<th><b>'.$letter++.'.</b> '.$results[0][$i].'</th>'."\n";
            }

            $out .= '</tr>'."\n";

            for( $i = 1; $i <= $nbrRow; $i++ )
            {
                $out .= '<tr class="headerY">'."\n\n"
                    .'<th><b>'.$number++.'.</b> '.$results[$i][0].'</th>'."\n";

                for( $j = 1; $j <= $nbrColumn; $j++ )
                {
                    $out .= '<td align="center">';
                    if( !empty($results[$i][$j]) ) $out .= $results[$i][$j]; else $out .= '0';
                    $out .= '</td>'."\n";
                }
                $out .= '</tr>'."\n\n";
            }
            $out .= '</table>'."\n\n";
        }
        $out .= '</center>'."\n".'<br /><br />'."\n";

        // several questions have to be shown on the page
        if( isset($questionIterator) )
        {
            $out .= '</td>' . "\n"
            .     '</tr>' . "\n\n";
        }
    } // end of foreach( $questionIdsToShow as $questionId )

    $out .= '</table>' . "\n";

    $out .= $backLink;
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
