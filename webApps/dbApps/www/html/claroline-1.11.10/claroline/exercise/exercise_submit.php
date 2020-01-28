<?php // $Id: exercise_submit.php 14314 2012-11-07 09:09:19Z zefredz $
/**
 * CLAROLINE
 *
 * @version 1.8 $Revision: 14314 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @author Claro Team <cvs@claroline.net>
 *
 */

$tlabelReq = 'CLQWZ';

require '../inc/claro_init_global.inc.php';

if ( ! claro_is_in_a_course() || !claro_is_course_allowed() ) claro_disp_auth_form(true);

claro_set_display_mode_available(true);

$is_allowedToEdit = claro_is_allowed_to_edit();

// tool libraries
include_once './lib/exercise.class.php';
include_once './lib/question.class.php';
include_once './lib/exercise.lib.php';

// following includes are not really clean as the question object already includes the one it needs
// but for the moment it is required by unserialize
include_once './lib/answer_truefalse.class.php';
include_once './lib/answer_multiplechoice.class.php';
include_once './lib/answer_fib.class.php';
include_once './lib/answer_matching.class.php';

// claroline libraries
include_once get_path('incRepositorySys') . '/lib/htmlxtra.lib.php';
include_once get_path('incRepositorySys') . '/lib/form.lib.php';
include_once get_path('incRepositorySys') . '/lib/module.lib.php';

// TODO find a better way to get table from this module and from LP module
$tblList = get_module_course_tbl( array( 'qwz_tracking' ), claro_get_current_course_id() );
$tbl_qwz_tracking = $tblList['qwz_tracking'];

$tbl_cdb_names = claro_sql_get_course_tbl();

// learning path
// new module CLLP
$inLP = (claro_called_from() == 'CLLP')? true : false;
$inOldLP = ( isset($_SESSION['inPathMode']) &&  $_SESSION['inPathMode'] );

// old learning path tool
if( $inOldLP )
{
    require_once get_path('incRepositorySys') . '/lib/learnPath.lib.inc.php';

    $tbl_lp_learnPath            = $tbl_cdb_names['lp_learnPath'           ];
    $tbl_lp_rel_learnPath_module = $tbl_cdb_names['lp_rel_learnPath_module'];
    $tbl_lp_user_module_progress = $tbl_cdb_names['lp_user_module_progress'];
    $tbl_lp_module               = $tbl_cdb_names['lp_module'              ];
    $tbl_lp_asset                = $tbl_cdb_names['lp_asset'               ];

    $claroline->setDisplayType(Claroline::FRAME);
}

// Command list
$cmdList = array();

if (claro_is_allowed_to_edit())
{
	$cmdList[] = array(
   		'img' => 'back',
    	'name' => get_lang('Back to the exercise list'),
    	'url' => claro_htmlspecialchars(Url::Contextualize('exercise.php')));
}

/*
 * Execute commands
 */
if( isset($_REQUEST['exId']) && is_numeric($_REQUEST['exId']) ) $exId = (int) $_REQUEST['exId'];
else                                                            $exId = null;

if( isset($_REQUEST['step']) && is_numeric($_REQUEST['step']) ) $step = (int) $_REQUEST['step'];
else                                                            $step = 0;

$dialogBox = new DialogBox();

/**
 * Handle SESSION
 * - refresh data in session if required
 * - copy session content locally to use local var in script
 * -
 */
$resetQuestionList = false;

// if exercise is not in session try to load it.
// if exId has been defined in request force refresh of exercise in session
if( !isset($_SESSION['serializedExercise']) || !is_null($exId) )
{
    // clean previous exercise if any
    unset($_SESSION['serializedExercise']);

    $exercise = new Exercise();

    if( is_null($exId) || !$exercise->load($exId) )
    {
        // exercise is required
        header("Location: " . Url::Contextualize('./exercise.php') );
        exit();
    }
    else
    {
        // load successfull
        // exercise must be visible or in learning path to be displayed to a student
        if( $exercise->getVisibility() != 'VISIBLE' && !$is_allowedToEdit
            && !( $inOldLP ||  $inLP ) )
        {
            $dialogBox->error( get_lang( 'The exercise is not available' ) );

            $content = $dialogBox->render();

            $claroline->display->body->appendContent($content);

            echo $claroline->display->render();
            //header("Location: ./exercise.php");
            exit();
        }
        else
        {
            $_SESSION['serializedExercise'] = serialize($exercise);
            $resetQuestionList = true;
        }
    }
}
else
{
    // get it back from session
    $exercise = unserialize($_SESSION['serializedExercise']);
    $exId = $exercise->getId();
}

// delete Random Question List
if( isset( $_REQUEST['cmd'] )  && $_REQUEST['cmd'] == 'deleteRandomQuestionList' )
{
    if( isset( $_REQUEST['listId'] ) && is_numeric( $_REQUEST['listId'] ) )
    {
        $listId = (int) $_REQUEST['listId'];
    }
    else
    {
        $listId = null;
    }

    if( !is_null( $listId ) )
    {
        if( !$exercise->deleteRandomQuestionList( $listId, $_SESSION['_user']['userId'], $exercise->getId() ) )
        {
            $dialogBox->error( get_lang( 'Error: unable to delete this list.' ) );
        }
        else
        {
            $dialogBox->success( get_lang ( 'List deleted successfully.' ) );
        }
    }
    else
    {
        $dialogBox->error( get_lang( 'Error: unable to delete this list.' ) );
    }
}

// load Random Question List
if( isset( $_REQUEST['cmd'] ) && $_REQUEST['cmd'] == 'loadRandomQuestionList' )
{
    if( isset( $_REQUEST['listId'] ) && is_numeric( $_REQUEST['listId'] ) )
    {
        $listId = (int) $_REQUEST['listId'];
    }
    else
    {
        $listId = null;
    }

    if( !is_null($listId) )
    {
        $loadRandomQuestionsList = @unserialize( $exercise->loadRandomQuestionList( $listId, $_SESSION['_user']['userId'], $exercise->getId() ) );
        if( $loadRandomQuestionsList )
        {
            $resetQuestionList = false;
        }
        else
        {
            $loadRandomQuestionsList = $exercise->getRandomQuestionList();
            // save random question list for the user
            $_SESSION['lastRandomQuestionList'] = serialize( $loadRandomQuestionsList );
            $resetQuestionList = false;
        }

    }
    else
    {
        // load new list
        $loadRandomQuestionsList = $exercise->getRandomQuestionList();
        // save random question list for the user
        $_SESSION['lastRandomQuestionList'] = serialize( $loadRandomQuestionsList );
        $resetQuestionList = false;
    }
}

$startExercise = true;
//-- get question list
if( $resetQuestionList || !isset($_SESSION['serializedQuestionList']) || !is_array($_SESSION['serializedQuestionList']) )
{
    if( $exercise->getShuffle() == 0 )
    {
        $qList = $exercise->getQuestionList();
    }
    else
    {
        if( $exercise->getUseSameShuffle() )
        {
            // load last Random question list for the user
            //$qList = $exercise->getLastRandomQuestionList( $_SESSION['_user']['userId'], $exercise->getId() );
            // load Rand Questions Lists for the user
            $qLists = $exercise->loadRandomQuestionLists( $_SESSION['_user']['userId'], $exercise->getId() );
            // if question list is empty, load a new Random question list
            if( !$qLists )
            {
                $qList = $exercise->getRandomQuestionList();
                // save random question list for the user
                $_SESSION['lastRandomQuestionList'] = serialize( $qList );

            }
            elseif( count($qLists) )
            {
                $qList = array();
                $startExercise = false;
            }
            // $exercise->saveRandomQuestionList( $_SESSION['_user']['userId'], $exercise->getId(), $qList );

        }
        else
        {
            $qList = $exercise->getRandomQuestionList();
        }

    }

    $questionList = array();
    $_SESSION['serializedQuestionList'] = array();
    // get all question objects and store them serialized in session
    foreach( $qList as $question )
    {
        $questionObj = new Question();
        $questionObj->setExerciseId($exId);

        if( $questionObj->load($question['id']) )
        {
            $_SESSION['serializedQuestionList'][] = serialize($questionObj);
            $questionList[] = $questionObj;
        }
        unset($questionObj);
    }
}
elseif( isset( $loadRandomQuestionsList ) && is_array( $loadRandomQuestionsList) )
{
    $questionList = array();
    if( isset($loadRandomQuestionsList['questions'] ) && is_array( $loadRandomQuestionsList['questions'] ) )
    {
        $questions = $loadRandomQuestionsList['questions'];
    }
    elseif( is_array( $loadRandomQuestionsList ) )
    {
        $questions = $loadRandomQuestionsList;
    }
    else
    {
        $questions = array();
    }

    foreach( $questions as $question )
    {
        $questionObj = new Question();
        $questionObj->setExerciseId( $exId );

        if( $questionObj->load( $question['id'] ) )
        {
            $_SESSION['serializedQuestionList'][] = serialize($questionObj);
            $questionList[] = $questionObj;
        }

    }
}
else
{
    $questionList = array();
    foreach( $_SESSION['serializedQuestionList'] as $serializedQuestion )
    {
        $questionList[] = unserialize($serializedQuestion);
    }
}

$questionCount = count($questionList);


$now = time();

if( !isset($_SESSION['exeStartTime']) )
{
    if( $startExercise )
    {
        $_SESSION['exeStartTime'] = $now;
    }
    $currentTime = 0;
}
else
{
    $currentTime = $now - $_SESSION['exeStartTime'];
}

if( $startExercise)
{
    $exeStartTime = $_SESSION['exeStartTime'];
}
else
{
    $exeStartTime = 0;
}

//-- exercise properties

if( claro_is_user_authenticated() )
{
    // count number of attempts of the user
    $sql="SELECT count(`result`) AS `tryQty`
            FROM `".$tbl_qwz_tracking."`
           WHERE `user_id` = '".(int) claro_get_current_user_id()."'
             AND `exo_id` = ".(int) $exId."
           GROUP BY `user_id`";

    $userAttemptCount = claro_sql_query_get_single_value($sql);

    if( $userAttemptCount )    $userAttemptCount++;
    else                     $userAttemptCount = 1; // first try
}
else
{
    $userAttemptCount = 1;
}


$exerciseIsAvailable = true;

if( !$is_allowedToEdit )
{
    // do the checks only if user has no edit right
    // check if exercise can be displayed
    if( $exercise->getStartDate() > $now
        || ( !is_null($exercise->getEndDate()) && $exercise->getEndDate() < $now )
       )
    {
        // not yet available, no more available
        $dialogBox->error( get_lang('Exercise not available') );
        $exerciseIsAvailable = false;
    }
    elseif( $exercise->getAttempts() > 0 && $userAttemptCount > $exercise->getAttempts() ) // attempt #
    {
        $dialogBox->error( get_lang('You have reached the maximum of %allowedAttempts allowed attempts.',
                                    array( '%allowedAttempts' => $exercise->getAttempts() )
                                   )
                          );
        $exerciseIsAvailable = false;
    }
}



// exercise is submitted - GRADE EXERCISE
if( isset($_REQUEST['cmdOk']) && $_REQUEST['cmdOk'] && $exerciseIsAvailable )
{
    $timeToCompleteExe =  $currentTime;
    $recordResults = true;

    // the time limit is set and the user take too much time to complete exercice
    if ( $exercise->getTimeLimit() > 0 && $exercise->getTimeLimit() < $timeToCompleteExe )
    {
        $showAnswers = false;
        $recordResults = false;
    }
    else
    {
        if ( $exercise->getShowAnswers()  == 'ALWAYS' )
        {
            $showAnswers = true;
        }
        elseif ( $exercise->getShowAnswers() == 'LASTTRY' && $userAttemptCount >= $exercise->getAttempts() )
        {
            $showAnswers = true;
        }
        else
        {
            // $exercise->getShowAnswers()  == 'NEVER'
            $showAnswers = false;
        }
    }

    // clean session to avoid receiving same exercise next time
    unset($_SESSION['serializedExercise']);
    unset($_SESSION['serializedQuestionList']);
    unset($_SESSION['exeStartTime']);

    $showResult = true;
    $showSubmitForm = false;

    if( $recordResults )
    {
        // compute scores
        $totalResult = 0;
        $totalGrade = 0;

        for( $i = 0 ; $i < count($questionList); $i++)
        {
            // required by getGrade and getQuestionFeedbackHtml
            $questionList[$i]->answer->extractResponseFromRequest();

            $questionResult[$i] = $questionList[$i]->answer->gradeResponse();
            $questionGrade[$i] = $questionList[$i]->getGrade();

            // sum of score
            $totalResult += $questionResult[$i];
            $totalGrade += $questionGrade[$i];
        }

        //-- tracking
        // if anonymous attempts are authorised : record anonymous user stats, record authentified user stats without uid
        if ( $exercise->getAnonymousAttempts() == 'ALLOWED' )
        {
            $exerciseTrackId = track_exercice($exId,$totalResult,$totalGrade,$timeToCompleteExe );
        }
        elseif( claro_is_in_a_course() ) // anonymous attempts not allowed, record stats with uid only if uid is set
        {
            $exerciseTrackId = track_exercice($exId,$totalResult,$totalGrade,$timeToCompleteExe, claro_get_current_user_id() );
        }

        if( isset($exerciseTrackId) && $exerciseTrackId && !empty($questionList) )
        {
            $i = 0;
            foreach ( $questionList as $question )
            {
                track_exercise_details($exerciseTrackId,$question->getId(),$question->answer->getTrackingValues(),$questionResult[$i]);
                $i++;
            }
        }

        // learning path
        // new module CLLP
        if( $inLP )
        {
            // include some utils functions
            include_once get_module_path('CLLP') . '/lib/utils.lib.php';
            require_once get_module_path('CLLP') . '/lib/item.class.php';
            if( $totalGrade > 0 )
            {
                $scoreRaw = $totalResult / $totalGrade * 100;
                $scoreMin = 0;
                $scoreMax = 100;
            }
            else
            {
                $scoreRaw = $scoreMin = $scoreMax = 0;
                $completionStatus = 'incomplete';
            }

            $completionStatus = 'incomplete';
            if(isset($_SESSION['thisItemId']))
            {
                $itemId = (int) $_SESSION['thisItemId'];
                $item = new item();
                if( $item->load( $itemId) )
                {
                    if( $scoreRaw >= (int) $item->getCompletionThreshold() )
                    {
                        $completionStatus = 'completed';
                    }
                }
            }

            $sessionTime = unixToScormTime($timeToCompleteExe);

            $jsForLP = ''
            .   'doSetValue("cmi.score.raw","'.$scoreRaw.'");' . "\n"
            .   'doSetValue("cmi.score.min","'.$scoreMin.'");' . "\n"
            .   'doSetValue("cmi.score.max","'.$scoreMax.'");' . "\n"
            .   'doSetValue("cmi.session_time","'.$sessionTime.'");' . "\n"
            .   'doSetValue("cmi.completion_status","'.$completionStatus.'");' . "\n"

            //.   'doCommit();' . "\n"
            .   'doTerminate();' . "\n"
            ;
        }
        // old learning path tool
        if( isset($_SESSION['inPathMode']) && $_SESSION['inPathMode'] )
        {
            set_learning_path_progression($totalResult,$totalGrade,$timeToCompleteExe,claro_get_current_user_id());
        }
    }
}
elseif( ! $exerciseIsAvailable )
{
    $showResult = false;
    $showSubmitForm = false;
}
else
{
    $showResult = false;
    $showSubmitForm = true;
}
//-- update step
if( isset($_REQUEST['cmdBack']) )     $step--;
else                                $step++;

/*
 * Output
 */

// learning path
// new module CLLP
if( $inLP )
{
    $jsloader = JavascriptLoader::getInstance();
    $jsloader->load('jquery');
    // load functions required to be able to discuss with API
    $jsloader->loadFromModule('CLLP', 'connector13');

    $jsloader->load('cllp.cnr');

    if( !empty($jsForLP) )
    {
        $claroline->display->header->addInlineJavascript($jsForLP);
    }
}


ClaroBreadCrumbs::getInstance()->prepend( get_lang('Exercises'), 'exercise.php' );

$out = '';

$nameTools = $exercise->getTitle();

//-- display properties
if( trim($exercise->getDescription()) != '' || ( !( $showResult && !$recordResults)  && ($exercise->getTimeLimit() > $currentTime) ) )
{
    $out .= '<blockquote>' . "\n" . claro_parse_user_text($exercise->getDescription()) . "\n" . '</blockquote>' . "\n";
}

$out .= '<ul style="font-size:small">' . "\n";
if( $exercise->getDisplayType() == 'SEQUENTIAL'  && $exercise->getTimeLimit() > 0 && ( !$exercise->getAttempts() || $userAttemptCount <= $exercise->getAttempts() ) && !( $showResult && !$recordResults) && ($exercise->getTimeLimit() > $currentTime) && $startExercise )
{
    $out .= '<li>' . get_lang('Current time').' : <span id="currentTime">'. claro_html_duration($currentTime) . '</span></li>' . "\n";
}

if( $exercise->getTimeLimit() > 0 && ( !$exercise->getAttempts() || $userAttemptCount <= $exercise->getAttempts() ) && !( $showResult && !$recordResults) && ($exercise->getTimeLimit() > $currentTime) )
{
    $out .= '<li>' . get_lang('Time limit')." : ".claro_html_duration($exercise->getTimeLimit()) . '</li>' . "\n";
}

if( claro_is_user_authenticated() && isset($userAttemptCount) && ( !$exercise->getAttempts() || $userAttemptCount <= $exercise->getAttempts() ) && !( $showResult && !$recordResults) && ($exercise->getTimeLimit() > $currentTime) )
{
    if ( $exercise->getAttempts() > 0 )
    {
        $out .= '<li>' . get_lang('Attempt %attemptCount on %attempts', array('%attemptCount'=> $userAttemptCount, '%attempts' =>$exercise->getAttempts())) . '</li>' . "\n" ;
    }
}

if( !is_null($exercise->getEndDate()) && !( $showResult && !$recordResults) && ($exercise->getTimeLimit() > $currentTime) )
{
    $out .= '<li>' . get_lang('Available from %startDate until %endDate',
                                array(
                                    '%startDate' => claro_html_localised_date(get_locale('dateTimeFormatLong'), $exercise->getStartDate()),
                                    '%endDate' => claro_html_localised_date(get_locale('dateTimeFormatLong'), $exercise->getEndDate())
                                )
                             )
    . '</li>' . "\n";
}

$out .= '</ul>' .  "\n\n";

if( $showResult )
{
    if( isset($_SESSION['inPathMode']) && $_SESSION['inPathMode'] )
    {
        // old learning path tool
        $out .= '<form method="get" action="../learnPath/navigation/backFromExercise.php">' . "\n"
        .    '<input type="hidden" name="op" value="finish" />';
    }
    elseif( !$inLP )
    {
        // standard exercise mode
        $out .= '<form method="get" action="exercise.php">';
    } // if inLP do not allow to navigate away : user should use LP navigation to go to another module

    $out .= claro_form_relay_context() . "\n";

    //  Display results

    /*if( $exercise->getShuffle() && $exercise->getUseSameShuffle() && isset( $_SESSION['lastRandomQuestionList'] ) )
    {
        $out .= '<div style="font-weight: bold;">' . "\n"
        .   '<a href="exercise.php?exId=' . $exercise->getId() .'&cmd=exSaveQwz'.( $inLP ? '&calledFrom=CLLP&embedded=true' : '' ).'">' . get_lang('Save this questions list') . '</a>'
        .   '</div>'
        ;
    }*/
    if( $recordResults )
    {
       $dialogBoxResults = new DialogBox();
       $outDialogbox = '';
       if( $exercise->getTimeLimit() > 0 )
       {
            $outDialogbox .= get_lang('Your time is %time', array('%time' => claro_html_duration($timeToCompleteExe)) )
            .     '<br />' . "\n";
       }
       $outDialogbox .= '<strong>' . get_lang('Your total score is %score', array('%score' => $totalResult."/".$totalGrade ) ) . '</strong>';
       $dialogBoxResults->info( $outDialogbox );
       $out .= $dialogBoxResults->render();
    }
    else
    {
        $contentDialogBox = '';
        if( $exercise->getTimeLimit() > 0 )
        {
            $contentDialogBox .= get_lang('Your time is %time', array('%time' => claro_html_duration($timeToCompleteExe)) )
            .                   '<br />' . "\n";
        }
        $contentDialogBox .= get_lang( 'Time is over, results not submitted.' );
        $dialogBox->error( $contentDialogBox );
        $dialogBox->info('<a href="'.claro_htmlspecialchars( Url::Contextualize('./exercise.php' ) ).'">&lt;&lt; '.get_lang('Back').'</a>');

    }

    //-- question(s)
    if( !empty($questionList) )
    {
        $out .= "\n" . '<table width="100%" border="0" cellpadding="1" cellspacing="0" class="claroTable">' . "\n\n";

        // foreach question
        $questionIterator = 1;
        $i = 0;

        foreach( $questionList as $question )
        {
            if( $showAnswers )
            {
                $out .= '<thead>'
                .   '<tr>' . "\n"
                .   '<th>'
                .   get_lang('Question') . ' ' . $questionIterator
                .   '</th>' . "\n"
                .   '</tr>' . "\n"
                .   '</thead>' . "\n";

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
            }
            $questionIterator++;
            $i++;
        }

        $out .= '</table>' . "\n\n";
    }
    else
    {
        $dialogBox->info(
            get_lang('No question to display')
            .'<br />'
            .'<a href="'.claro_htmlspecialchars(
                Url::Contextualize('./exercise.php' ) ).'">&lt;&lt; '.get_lang('Back').'</a>'
        );
    }

    //  Display results
    if( $recordResults )
    {
        $out .= $dialogBoxResults->render();

        if( !is_null($exercise->getQuizEndMessage()) )
        {
            $out .= '<blockquote>' . "\n" . claro_parse_user_text($exercise->getQuizEndMessage()) . "\n" . '</blockquote>' . "\n";
        }
    }

    if( $exercise->getShuffle() && $exercise->getUseSameShuffle() && isset( $_SESSION['lastRandomQuestionList'] ) )
    {
        $out .= '<div style="font-weight: bold;">' . "\n"
        .   '<a href="'.claro_htmlspecialchars( Url::Contextualize('exercise.php?exId=' . $exercise->getId() .'&cmd=exSaveQwz'.( $inLP ? '&calledFrom=CLLP&embedded=true' : '' ) ) ).'">' . get_lang('Save this questions list') . '</a>'
        .   '</div>'
        ;
    }
    // Display Finish/Continue
    $out .= '<div class="centerContent">'. "\n";

    if( !$inLP)
    {
        if( $recordResults )
        {
            $out .= '<input type="submit" value="'.get_lang('Finish').'" />';
        }
    }
    elseif( !( $exercise->getQuizEndMessage() ) )
    {
        $out .= get_lang('Exercise done, choose a module in the list to continue.');
    }

    $out .= '</div>' . "\n";


    if( !$inLP )
    {
        $out .= '</form>' . "\n\n";
    }

}
elseif( $showSubmitForm )
{
    // check if cmdBack or cmdNext can be performed  (time limit)
    $displayForm = true;
    if( isset($_REQUEST['cmdBack']) || isset($_REQUEST['cmdNext']) ){
        $timeToCompleteExe =  $currentTime;

        // the time limit is set and the user take too much time to complete exercice
        if ( $exercise->getTimeLimit() > 0 && $exercise->getTimeLimit() < $timeToCompleteExe )
        {
            $displayForm = false;

            unset($_SESSION['exeStartTime']);

            $contentDialogBox = '';
            $contentDialogBox .= get_lang('Your time is %time', array('%time' => claro_html_duration($timeToCompleteExe)) )
            .                   '<br />' . "\n";
            $contentDialogBox .= get_lang( 'Time is over, results not submitted.' );
            $dialogBox->error( $contentDialogBox );
            $dialogBox->info('<a href="'.claro_htmlspecialchars( Url::Contextualize('./exercise.php' ) ).'">&lt;&lt; '.get_lang('Back').'</a>');

        }
    }

    //-- question(s)
    if( !empty($questionList) && $displayForm )
    {
        // form header, table header
        $out .= '<form id="formExercise" method="post" action="./exercise_submit.php'.( $inLP ? '?calledFrom=CLLP&embedded=true' : '' ).'">' . "\n"
        .   claro_form_relay_context() . "\n";

        if( $exercise->getDisplayType() == 'SEQUENTIAL' )
        {
            $out .= '<input type="hidden" name="step" value="'.$step.'" />' . "\n";
        }

        $out .= "\n" . '<table width="100%" border="0" cellpadding="1" cellspacing="0" class="claroTable">' . "\n\n";

        // foreach question
        $questionIterator = 0;

        foreach( $questionList as $question )
        {
            $questionIterator++;

            if( $exercise->getDisplayType() == 'SEQUENTIAL' )
            {
                // get response if something has already been sent
                $question->answer->extractResponseFromRequest();

                if( $step != $questionIterator )
                {
                    // only echo hidden form field
                    $out .= $question->answer->getHiddenAnswerHtml();
                }
                else
                {
                    $out .= '<thead>'
                    .   '<tr>' . "\n"
                    .   '<th>'
                    .   get_lang('Question') . ' ' . $questionIterator
                    .   ' / '.$questionCount
                    .   '</th>' . "\n"
                    .   '</tr>' . "\n"
                    .   '</thead>' . "\n";

                    $out .= '<tr>'
                    .     '<td>' . "\n"

                    .     $question->getQuestionAnswerHtml()

                    .     '</td>' . "\n"
                    .     '</tr>' . "\n\n";
                }
            }
            else // all questions on on page
            {
                $out .= '<thead>'
                .   '<tr>' . "\n"
                .   '<th>'
                .   get_lang('Question') . ' ' . $questionIterator
                .   '</th>' . "\n"
                .   '</tr>' . "\n"
                .   '</thead>' . "\n";

                $out .= '<tr>'
                .     '<td>' . "\n"

                .     $question->getQuestionAnswerHtml()

                .     '</td>' . "\n"
                .     '</tr>' . "\n\n";
            }

        }
        // table footer, form footer
        $out .= '</table>' . "\n\n";

        $out .= '<div class="centerContent">' . "\n";

        if( $exercise->getDisplayType() == 'SEQUENTIAL' )
        {
            if( $step > 1 )
            {
                $out .= '<input type="submit" name="cmdBack" value="&lt; '.get_lang('Previous question').'" />&nbsp;' . "\n";
            }

            if( $step < $questionCount )
            {
                $out .= '<input type="submit" name="cmdNext" value="'.get_lang('Next question').' &gt;" />' . "\n";
            }

            $out .= '<p><input type="submit" name="cmdOk" value="'.get_lang('Submit all and finish').'" /></p>' . "\n";
        }
        else
        {
            $out .= '<input type="submit" name="cmdOk" value="'.get_lang('Finish the test').'" />' . "\n";
        }

        $out .= '</div>' . "\n"
        .     '</form>' . "\n\n";

    }
    elseif( isset( $qLists ) && count( $qLists ) )
    {
        $out .= '<div>' . get_lang( 'Some questions lists are saved in memory. Do you want to load one of them ?' ) . '</div>' . "\n";

        foreach( $qLists as $i => $qList )
        {
            $questionsList = @unserialize( $qList['questions'] );
            $out .= '<div id="questionsList' . $i++ . '" class="collapsible collapsed" style="padding: 3px 0 3px 0;">' . "\n"
            .   '<a href="#" class="doCollapse" style="font-weight: bold;">' . get_lang( 'Question list %id (saved the %date)', array( '%id' => $i, '%date' => date( 'Y/m/d - H:i', $questionsList['date'] ) ) ) . '</a>' . "\n"
            .   ' - ' . "\n"
            .   '<a href="'.claro_htmlspecialchars( Url::Contextualize('exercise_submit.php?exId=' . $exId . '&cmd=loadRandomQuestionList'.( $inLP ? '&calledFrom=CLLP&embedded=true' : '' ).'&listId=' . $qList['id'] ) ). '">' . get_lang( 'Load this list' ) . '</a>' . "\n"
            .   ' - ' . "\n"
            .   '<a href="'.claro_htmlspecialchars( Url::Contextualize('exercise_submit.php?exId=' . $exId . '&cmd=deleteRandomQuestionList'.( $inLP ? '&calledFrom=CLLP&embedded=true' : '' ).'&listId=' . $qList['id'] ) ). '">' . get_lang( 'Delete') . '</a>'
            .   '<div class="collapsible-wrapper">' . "\n";
            if( is_array( $questionsList['questions']) && count( $questionsList['questions']) )
            {
                $out .= '<ol>';
                foreach( $questionsList['questions'] as $question)
                {
                    $out .= '<li>'
                    .   $question['title']
                    .   '</li>';
                }
                $out .= '</ol>';
            }
            else
            {
                $out .= get_lang( 'List is empty' );
            }
            $out .=   '</div>'
            .   '</div>'
            ;
        }

        $out .= '<div> <br />'
        .   '<a href="'.claro_htmlspecialchars( Url::Contextualize('exercise_submit.php?exId=' . $exId . '&cmd=loadRandomQuestionList'.( $inLP ? '&calledFrom=CLLP&embedded=true' : '' ) ) ) .'" style="font-weight: bold;">' . get_lang( 'Load a new list' ) . '</a>'
        .   '</div>'
        ;

    }
    else
    {
        $dialogBox->info(
            get_lang('No question to display')
            .'<br />'
            .'<a href="'.claro_htmlspecialchars(
                Url::Contextualize('./exercise.php' ) ).'">&lt;&lt; '.get_lang('Back').'</a>'
        );
    }
}
else // ! $showSubmitForm
{

    if( (!isset($_SESSION['inPathMode']) || !$_SESSION['inPathMode']) && !$inLP )
    {
        $dialogBox->info('<a href="'.claro_htmlspecialchars( Url::Contextualize('./exercise.php' ) ).'">&lt;&lt; '.get_lang('Back').'</a>');
    }
}

/**
 * Output
 */
// add a function used to simulate <label> tag on QCM as using label around answer is not valid since
// there can be p tags etc in it
$htmlHeaders = "\n".'
<script type="text/javascript">
    $(document).ready(function() {
        $(".labelizer").click(function() {
            if( $(this).parent("tr").find("input").attr("type") == "checkbox" )
            {
                if( $(this).parent("tr").find("input").attr("checked") )
                {
                    $(this).parent("tr").find("input").removeAttr("checked");
                }
                else
                {
                    $(this).parent("tr").find("input").attr("checked","checked");
                }
            }
            else if( $(this).parent("tr").find("input").attr("type") == "radio" )
            {
                // uncheck all input on the same level
                $(this).parent().parent().children().find("input").removeAttr("checked");
                // check the corresponding one
                $(this).parent().find("input").attr("checked","checked");
            }
        })
    });

    clockStart = new Date();
    clockStart.setTime( '. $exeStartTime * 1000 .' );
    clockStart = clockStart.getTime();
    timeLimit = '.$exercise->getTimeLimit().';
    langMinShort = "'. get_lang('MinuteShort') .'";
    langSecShort = "'. get_lang('SecondShort') .'";
    langTimeWarning = "'. get_lang('Time exceeded') .'";
    submitForm = true;

    $(document).ready(function() {
        if($("#currentTime").length > 0 && $("#formExercise").length > 0){
           getSecs( clockStart, timeLimit, submitForm, langMinShort, langSecShort, langTimeWarning, "currentTime", "formExercise");
        }
    });

    function initStopwatch(clockStart){
         var myTime = new Date();
         var timeNow = myTime.getTime();
         var timeDiff = timeNow - clockStart;
         return(timeDiff/1000);
    }

    function getSecs( clockStart, timeLimit, submitForm, langMinShort, langSecShort, langTimeWarning, spanID, formID )
    {
        var mySecs = initStopwatch(clockStart);
        if(submitForm && mySecs > timeLimit)
        {
            $("#"+spanID).text(langTimeWarning);
            alert(langTimeWarning);
            //$("#"+formID).append("<input type=\"hidden\" name=\"cmdOk\" value=\"1\" />");
            //$("#"+formID).submit();
        }else
        {
           var mySecs = ""+mySecs;
           mySecs1= mySecs.substring(0,mySecs.indexOf("."));
           myMin = Math.floor(mySecs / 60);
           mySecs = Math.round(mySecs % 60);
           myTime = "";
           if(myMin > 0)
           {
               myTime = myTime + myMin + " " + langMinShort + " ";
           }
           myTime = myTime + mySecs + " " + langSecShort + " ";
           $("#"+spanID).text(myTime);
           window.setTimeout("getSecs( clockStart, timeLimit, " +  submitForm + ", langMinShort, langSecShort, langTimeWarning, \'" + spanID + "\', \'" + formID + "\' )", 1000);
        }
    }
</script>' . "\n\n";

$claroline->display->header->addHtmlHeader($htmlHeaders);

//-- title
$content = '';

if( $showResult )
{
    $content .= claro_html_tool_title(get_lang('Exercise results') . ' : ' . $nameTools, null, $cmdList);
}
else
{
    $content .= claro_html_tool_title(get_lang('Exercise') . ' : ' . $nameTools, null, $cmdList);
}

$content .= $dialogBox->render();

$content .= $out;

$claroline->display->body->appendContent($content);

echo $claroline->display->render();
