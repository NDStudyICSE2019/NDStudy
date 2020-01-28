<?php // $Id: track_exercise_details.php 14463 2013-06-03 05:52:14Z dkp1060 $

/**
 * CLAROLINE
 *
 * This page display global information about.
 *
 * @version     $Revision: 14463 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      claro team <info@claroline.net>
 */

require '../inc/claro_init_global.inc.php';

include_once get_path('incRepositorySys') . '/lib/statsUtils.lib.inc.php';
include_once get_path('incRepositorySys') . '/lib/htmlxtra.lib.php';

include_once dirname(__FILE__) . '/lib/exercise.class.php';
include_once dirname(__FILE__) . '/lib/question.class.php';
include_once dirname(__FILE__) . '/lib/answer_multiplechoice.class.php';
include_once dirname(__FILE__) . '/lib/answer_truefalse.class.php';
include_once dirname(__FILE__) . '/lib/answer_fib.class.php';
include_once dirname(__FILE__) . '/lib/answer_matching.class.php';

/**
 * extend Question class to add extract from tracking method to each answer type
 */
class TrackQuestion extends Question
{
    /**
     * Include the correct answer class and create answer
     */
    function setAnswer()
    {
        switch($this->type)
        {
            case 'MCUA' :
                $this->answer = new TrackAnswerMultipleChoice($this->id, false);
                break;
            case 'MCMA' :
                $this->answer = new TrackAnswerMultipleChoice($this->id, true);
                break;
            case 'TF' :
                $this->answer = new TrackAnswerTrueFalse($this->id);
                break;
            case 'FIB' :
                $this->answer = new TrackAnswerFillInBlanks($this->id);
                break;
            case 'MATCHING' :
                $this->answer = new TrackAnswerMatching($this->id);
                break;
            default :
                $this->answer = null;
                break;
        }

        return true;
    }
}

class TrackAnswerMultipleChoice extends answerMultipleChoice
{
    function extractResponseFromTracking( $attemptDetailsId )
    {
        $tbl_cdb_names = get_module_course_tbl( array( 'qwz_tracking_answers' ), claro_get_current_course_id() );
        $tbl_qwz_tracking_answers = $tbl_cdb_names['qwz_tracking_answers'];

        // get the answers the user has gaven for this question
        $sql = "SELECT `answer`
                FROM `" . $tbl_qwz_tracking_answers . "`
                WHERE `details_id` = " . (int) $attemptDetailsId . 
                " ORDER BY `id` ASC ";

        $trackedAnswers = claro_sql_query_fetch_all($sql);

        $this->response = array();

        foreach( $trackedAnswers as $trackedAnswer )
        {
            foreach( $this->answerList as $answer )
            {
                if( $answer['answer'] == $trackedAnswer['answer'] )
                {
                    $this->response[$answer['id']] = true;
                }
            }
        }

        return true;
    }
}

class TrackAnswerTrueFalse extends answerTrueFalse
{
    function extractResponseFromTracking( $attemptDetailsId )
    {
        $tbl_cdb_names = get_module_course_tbl( array( 'qwz_tracking_answers' ), claro_get_current_course_id() );
        $tbl_qwz_tracking_answers = $tbl_cdb_names['qwz_tracking_answers'];

        // get the answers the user has gaven for this question
        $sql = "SELECT `answer`
                FROM `" . $tbl_qwz_tracking_answers . "`
                WHERE `details_id` = " . (int) $attemptDetailsId . 
                " ORDER BY `id` ASC ";

        $this->response = claro_sql_query_get_single_value($sql);

        return true;
    }
}

class TrackAnswerFillInBlanks extends answerFillInBlanks
{
    function extractResponseFromTracking( $attemptDetailsId )
    {
        $tbl_cdb_names = get_module_course_tbl( array( 'qwz_tracking_answers' ), claro_get_current_course_id() );
        $tbl_qwz_tracking_answers = $tbl_cdb_names['qwz_tracking_answers'];

        // get the answers the user has gaven for this question
        $sql = "SELECT `answer`
                FROM `" . $tbl_qwz_tracking_answers . "`
                WHERE `details_id` = " . (int) $attemptDetailsId . 
                " ORDER BY `id` ASC ";

        $answers = claro_sql_query_fetch_all($sql);

        foreach( $answers as $answer )
        {
            $this->response[] = $answer['answer'];
        }

        return true;
    }
}

class TrackAnswerMatching extends answerMatching
{
    function extractResponseFromTracking( $attemptDetailsId )
    {
        $tbl_cdb_names = get_module_course_tbl( array( 'qwz_tracking_answers' ), claro_get_current_course_id() );
        $tbl_qwz_tracking_answers = $tbl_cdb_names['qwz_tracking_answers'];

        // get the answers the user has gaven for this question
        $sql = "SELECT `answer`
                FROM `" . $tbl_qwz_tracking_answers . "`
                WHERE `details_id` = " . (int) $attemptDetailsId . 
                " ORDER BY `id` ASC ";

        $trackedAnswers = claro_sql_query_fetch_all($sql);

        $answerCount = count($this->leftList);

        foreach( $trackedAnswers as $trackedAnswer )
        {
            list($leftProposal, $rightProposal) = explode(' -> ',$trackedAnswer['answer']);

            // find corresponding right code if exists
            $rightCode = '';
            if( isset($rightProposal) )
            {
                foreach( $this->rightList as $rightElt )
                {
                    if( $rightElt['answer'] == $rightProposal )
                    {
                        $rightCode = $rightElt['code'];
                        break;
                    }
                }
            }

            for( $i = 0; $i < $answerCount ; $i++ )
            {
                if( $this->leftList[$i]['answer'] == $leftProposal )
                {
                    $this->leftList[$i]['response'] = $rightCode;
                    break;
                }
            }
        }
        return true;
    }
}

/**
 * DB tables definition
 */
$tbl_mdb_names = claro_sql_get_main_tbl();
$tbl_rel_course_user = $tbl_mdb_names['rel_course_user'  ];
$tbl_user            = $tbl_mdb_names['user'             ];


$tbl_cdb_names = get_module_course_tbl( array( 'qwz_exercise', 'qwz_tracking', 'qwz_tracking_questions','qwz_tracking_answers' ), claro_get_current_course_id() );
$tbl_qwz_exercise = $tbl_cdb_names['qwz_exercise'];

$tbl_qwz_tracking = $tbl_cdb_names['qwz_tracking'];
$tbl_qwz_tracking_questions = $tbl_cdb_names['qwz_tracking_questions'];
$tbl_qwz_tracking_answers = $tbl_cdb_names['qwz_tracking_answers'];



// all I need from REQUEST is the track_id and it is required
if( isset($_REQUEST['trackedExId']) && is_numeric($_REQUEST['trackedExId']) )
{
    $trackedExId = (int) $_REQUEST['trackedExId'];
}
else
{
    claro_redirect("./exercise.php");
    exit();
}

$dialogBox = new DialogBox();


//-- get infos
// get infos about the exercise
// get infos about the user
// get infos about the exercise attempt
$sql = "SELECT `E`.`id`, `E`.`title`, `E`.`showAnswers`, `E`.`attempts`,
                `U`.`user_id`, `U`.`nom` as `lastname`, `U`.`prenom` as `firstname`,
                `TE`.`exo_id`, `TE`.`result`, `TE`.`time`, `TE`.`weighting`,
                UNIX_TIMESTAMP(`TE`.`date`) AS `unix_exe_date`
        FROM `".$tbl_qwz_exercise."` as `E`, `".$tbl_qwz_tracking."` as `TE`, `".$tbl_user."` as `U`
        WHERE `E`.`id` = `TE`.`exo_id`
        AND `TE`.`user_id` = `U`.`user_id`
        AND `TE`.`id` = ". $trackedExId;

if( ! $thisAttemptDetails = claro_sql_query_get_single_row($sql) )
{
    // sql error, let's get out of here !
    claro_redirect("./exercise.php");
    exit();
}

//-- permissions
// if a user want to see its own results the teacher must have allowed the students
// to see the answers at the end of the exercise
$is_allowedToTrack = false;

if( claro_is_user_authenticated() )
{
    if( claro_is_course_manager() )
    {
        $is_allowedToTrack = true;
    }
    elseif( claro_get_current_user_id() == $thisAttemptDetails['user_id'] )
    {
        if( $thisAttemptDetails['showAnswers'] == 'ALWAYS' )
        {
            $is_allowedToTrack = true;
        }
        elseif( $thisAttemptDetails['showAnswers'] == 'LASTTRY' )
        {
            // we must check that user has at least "max_attempt" results
            $sql = "SELECT COUNT(`id`)
                    FROM `".$tbl_qwz_tracking."`
                    WHERE `user_id` = " . (int) claro_get_current_user_id() . "
                    AND `exo_id` = ".$thisAttemptDetails['exo_id'];
            $userAttempts = claro_sql_query_get_single_value($sql);

            if( $userAttempts >= $thisAttemptDetails['attempts'] )
            {
                $is_allowedToTrack = true;
            }
            else
            {
                $dialogBox->error( get_lang('You must reach the maximum number of allowed attempts to view these statistics.') );
            }

        }
        else
        {
              // user cannot see its full results if show_answer == 'NEVER'
            $dialogBox->error( get_lang('Display of detailed answers is not authorized.') );
        }
    }
}


ClaroBreadCrumbs::getInstance()->prepend( get_lang('Exercises'), './exercise.php' );

$nameTools = get_lang('Statistics of exercise attempt');

$out = '';
// display title
$titleTab['mainTitle'] = $nameTools;

// Command list
$cmdList = array();
$cmdList[] = array(
	'img' => 'back',
	'name' => get_lang('Back'),
	'url' => claro_htmlspecialchars( Url::Contextualize('../tracking/userReport.php?userId='.$thisAttemptDetails['user_id'].'&amp;exId='.$thisAttemptDetails['id'] ) ));

$out .= claro_html_tool_title($titleTab, null, $cmdList);

if( $is_allowedToTrack && get_conf('is_trackingEnabled') )
{
    // get all question that user get for this attempt
    $sql = "SELECT TD.`id` as `trackId`, TD.`question_id`, TD.`result`
            FROM `".$tbl_qwz_tracking_questions."` as TD
            WHERE `exercise_track_id` = ". $trackedExId;

    $trackedQuestionList = claro_sql_query_fetch_all($sql);

    $i = 0;
    $totalResult = 0;
    $totalGrade = 0;
    $questionList = array();

    // for each question the user get
    foreach( $trackedQuestionList as $trackedQuestion )
    {
        $question = new TrackQuestion();

        if( $question->load($trackedQuestion['question_id']) )
        {
            // required by getGrade and getQuestionFeedbackHtml
            $question->answer->extractResponseFromTracking($trackedQuestion['trackId']);

            $questionResult[$i] = $question->answer->gradeResponse();
            $questionGrade[$i] = $question->getGrade();

            // sum of score
            $totalResult += $questionResult[$i];
            $totalGrade += $questionGrade[$i];

            // save question object in a list to reuse it later
            $questionList[$i] = $question;

            $i++;
        }
        // else skip question
    }

    // display

    // display infos about the details ...
    $out .= '<ul>' . "\n"
    .    '<li>' . get_lang('Last name') . ' : '.$thisAttemptDetails['lastname'] . '</li>' . "\n"
    .    '<li>' . get_lang('First name') . ' : '.$thisAttemptDetails['firstname'] . '</li>' . "\n"
    .    '<li>' . get_lang('Date') . ' : ' . claro_html_localised_date(get_locale('dateTimeFormatLong'),$thisAttemptDetails['unix_exe_date']) . '</li>' . "\n"
    .    '<li>' . get_lang('Score') . ' : ' . $thisAttemptDetails['result'] . '/' . $thisAttemptDetails['weighting'] . '</li>' . "\n"
    .    '<li>' . get_lang('Time') . ' : ' . claro_html_duration($thisAttemptDetails['time']) . '</li>' . "\n"
    .    '</ul>' . "\n\n"
    ;

    $out .= "\n" . '<table width="100%" border="0" cellpadding="1" cellspacing="0" class="claroTable">' . "\n\n";

    if( !empty($questionList) )
    {
        // foreach question
        $questionIterator = 1;
        $i = 0;

        foreach( $questionList as $question )
        {
            $out .= '<thead>'
            .   '<tr>' . "\n"
            .   '<th>'
            .   get_lang('Question') . ' ' . $questionIterator
            .   '</th>' . "\n"
            .   '</tr>' . "\n"
            .   '</thead>'."\n";

            $out .= '<tr>'
            .     '<td>' . "\n";

            $out .= $question->getQuestionFeedbackHtml();

            $out .= '</td>' . "\n"
            .     '</tr>' . "\n\n"

            .     '<tr>'
            .     '<td align="right">' . "\n"
            .     '<strong>'.get_lang('Score').' : '.$questionResult[$i].'/'.$questionGrade[$i].'</strong>'
            .     '</td>' . "\n"
            .     '</tr>' . "\n\n";

            $questionIterator++;
            $i++;
        }
    }

    $out .= '</table>' . "\n\n";

}
// not allowed
else
{
    if(!get_conf('is_trackingEnabled'))
    {
        $dialogBox->error( get_lang('Tracking has been disabled by system administrator.') );
    }
    $out .= $dialogBox->render();
}

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
