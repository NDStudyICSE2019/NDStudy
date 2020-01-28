<?php // $Id: edit_exercise.php 14419 2013-04-12 12:21:09Z zefredz $

/**
 * CLAROLINE
 *
 * @version     $Revision: 14419 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 */

$tlabelReq = 'CLQWZ';

require '../../inc/claro_init_global.inc.php';

if ( !$_cid || !$is_courseAllowed ) claro_disp_auth_form(true);

$is_allowedToEdit = claro_is_allowed_to_edit();

// courseadmin reserved page
if( !$is_allowedToEdit )
{
    header('Location: '. Url::Contextualize('../exercise.php' ));
    exit();
}

// tool libraries
include_once '../lib/exercise.class.php';

include_once '../lib/exercise.lib.php';

// claroline libraries
include_once $includePath . '/lib/form.lib.php';

/*
 * Init request vars
 */
if ( isset($_REQUEST['cmd']) )    $cmd = $_REQUEST['cmd'];
else                            $cmd = '';

if( isset($_REQUEST['exId']) && is_numeric($_REQUEST['exId']) ) $exId = (int) $_REQUEST['exId'];
else                                                            $exId = null;

if( isset($_REQUEST['quId']) && is_numeric($_REQUEST['quId']) ) $quId = (int) $_REQUEST['quId'];
else                                                            $quId = null;

/*
 * Init other vars
 */
$exercise = new Exercise();

if( !is_null($exId) && !$exercise->load($exId) )
{
    $cmd = 'rqEdit';
}

$dialogBox = new DialogBox();
$displayForm = false;
$displaySettings = true;

/*
 * Execute commands
 */
if( $cmd == 'rmQu' && !is_null($quId) )
{
    $exercise->removeQuestion($quId);
}

if( $cmd == 'mvUp' && !is_null($quId) )
{
    $exercise->moveQuestionUp($quId);
}

if( $cmd == 'mvDown' && !is_null($quId) )
{
    $exercise->moveQuestionDown($quId);
}

if( $cmd == 'exEdit' )
{
    $exercise->setTitle($_REQUEST['title']);
    $exercise->setDescription($_REQUEST['description']);
    $exercise->setDisplayType($_REQUEST['displayType']);

    if( isset($_REQUEST['randomize']) && $_REQUEST['randomize'] )
    {
        $exercise->setShuffle($_REQUEST['questionDrawn']);
    }
    else
    {
        $exercise->setShuffle(0);
    }
    
    if( isset( $_REQUEST['useSameShuffle'] ) && $_REQUEST['useSameShuffle'] )
    {
        $exercise->setUseSameShuffle( $_REQUEST['useSameShuffle'] );
    }
    else
    {
        $exercise->setUseSameShuffle(0);
    }
    
    $exercise->setShowAnswers($_REQUEST['showAnswers']);

    $exercise->setStartDate( mktime($_REQUEST['startHour'],$_REQUEST['startMinute'],0,$_REQUEST['startMonth'],$_REQUEST['startDay'],$_REQUEST['startYear']) );

    if( isset($_REQUEST['useEndDate']) && $_REQUEST['useEndDate'] )
    {
        $exercise->setEndDate( mktime($_REQUEST['endHour'],$_REQUEST['endMinute'],0,$_REQUEST['endMonth'],$_REQUEST['endDay'],$_REQUEST['endYear']) );
    }
    else
    {
        $exercise->setEndDate(null);
    }

    if( isset($_REQUEST['useTimeLimit']) && $_REQUEST['useTimeLimit'] )
    {
        $exercise->setTimeLimit( $_REQUEST['timeLimitMin']*60 + $_REQUEST['timeLimitSec'] );
    }
    else
    {
        $exercise->setTimeLimit(0);
    }

    $exercise->setAttempts($_REQUEST['attempts']);
    $exercise->setAnonymousAttempts($_REQUEST['anonymousAttempts']);
    
    $exercise->setQuizEndMessage($_REQUEST['quizEndMessage']);

    if( $exercise->validate() )
    {
        if( $insertedId = $exercise->save() )
        {
            if( is_null($exId) )
            {
                $dialogBox->success( get_lang('Exercise added') );
                $eventNotifier->notifyCourseEvent("exercise_added",claro_get_current_course_id(), claro_get_current_tool_id(), $insertedId, claro_get_current_group_id(), "0");
                $exId = $insertedId;
            }
            else
            {
                $dialogBox->success( get_lang('Exercise modified') );
                $eventNotifier->notifyCourseEvent("exercise_updated",claro_get_current_course_id(), claro_get_current_tool_id(), $insertedId, claro_get_current_group_id(), "0");
            }
            $displaySettings = true;
        }
        else
        {
            // sql error in save() ?
            $cmd = 'rqEdit';
        }

    }
    else
    {
        if( claro_failure::get_last_failure() == 'exercise_no_title' )
        {
            $dialogBox->error( get_lang('Field \'%name\' is required', array('%name' => get_lang('Title'))) );
        }
        elseif( claro_failure::get_last_failure() == 'exercise_incorrect_dates')
        {
            $dialogBox->error( get_lang('Start date must be before end date ...') );
        }
        $cmd = 'rqEdit';
    }
}

if( $cmd == 'rqEdit' )
{
    $form['title']                 = $exercise->getTitle();
    $form['description']         = $exercise->getDescription();
    $form['displayType']         = $exercise->getDisplayType();
    $form['randomize']             = (boolean) $exercise->getShuffle() > 0;
    $form['questionDrawn']        = $exercise->getShuffle();
    $form['useSameShuffle']      = (boolean) $exercise->getUseSameShuffle();
    $form['showAnswers']         = $exercise->getShowAnswers();

    $form['startDate']             = $exercise->getStartDate(); // unix

    if( is_null($exercise->getEndDate()) )
    {
        $form['useEndDate']        = false;
        $form['endDate']         = 0;
    }
    else
    {
        $form['useEndDate']        = true;
        $form['endDate']         = $exercise->getEndDate();
    }

    $form['useTimeLimit']         = (boolean) $exercise->getTimeLimit();
    $form['timeLimitSec']       = $exercise->getTimeLimit() % 60 ;
    $form['timeLimitMin']         = ($exercise->getTimeLimit() - $form['timeLimitSec']) / 60;

    $form['attempts']             = $exercise->getAttempts();
    $form['anonymousAttempts']     = $exercise->getAnonymousAttempts();
    
    $form['quizEndMessage'] = $exercise->getQuizEndMessage();

    $displayForm = true;
}

// Command list
$cmdList = array();

$cmdList[] = array(
    'img' => 'back',
    'name' => get_lang('Back to the exercise list'),
    'url' => claro_htmlspecialchars(Url::Contextualize('../exercise.php'))
);
$cmdList[] = array(
    'img' => 'edit',
    'name' => get_lang('Edit exercise settings'),
    'url' => claro_htmlspecialchars(Url::Contextualize('./edit_exercise.php?exId='.$exId.'&cmd=rqEdit'))
);
$cmdList[] = array(
    'img' => 'default_new',
    'name' => get_lang('New question'),
    'url' => claro_htmlspecialchars(Url::Contextualize('./edit_question.php?exId='.$exId.'&cmd=rqEdit'))
);

if ( $exId )
{
    $cmdList[] = array(
        'name' => get_lang('Get a question from another exercise'),
        'url' => claro_htmlspecialchars(Url::Contextualize('./question_pool.php?exId='.$exId))
    );
}


/*
 * Output
 */


if( is_null($exId) )
{
    $nameTools = get_lang('New exercise');
    $toolTitle = $nameTools;
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Exercises'), Url::Contextualize( get_module_url('CLQWZ').'/exercise.php') );
    ClaroBreadCrumbs::getInstance()->setCurrent( $nameTools, Url::Contextualize('./edit_exercise.php?cmd=rqEdit') );
}
elseif( $cmd == 'rqEdit' )
{
    $nameTools = get_lang('Edit exercise');
    $toolTitle['mainTitle'] = $nameTools;
    $toolTitle['subTitle'] = $exercise->getTitle();
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Exercise'), Url::Contextualize('./edit_exercise.php?exId='.$exId) );
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Exercises'), Url::Contextualize(get_module_url('CLQWZ').'/exercise.php') );
    ClaroBreadCrumbs::getInstance()->setCurrent( $nameTools, Url::Contextualize('./edit_exercise.php?cmd=rqEdit&amp;exId='.$exId) );
}
else
{
    $nameTools = get_lang('Exercise');
    $toolTitle['mainTitle'] = $nameTools;
    $toolTitle['subTitle'] = $exercise->getTitle();
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Exercises'), Url::Contextualize(get_module_url('CLQWZ').'/exercise.php') );
    ClaroBreadCrumbs::getInstance()->setCurrent( $nameTools, Url::Contextualize('./edit_exercise.php?exId='.$exId) );
}

CssLoader::getInstance()->load( 'exercise', 'screen');

$out = '';

$out .= claro_html_tool_title($toolTitle, null, $cmdList);

// dialog box if required
$out .= $dialogBox->render();

if( $displayForm )
{
    // -- edit form
    $display = new ModuleTemplate( 'CLQWZ' , 'exercise_form.tpl.php' );
    $display->assign( 'exId', $exId );
    $display->assign( 'data', $form );
    $display->assign( 'relayContext', claro_form_relay_context() );
    $display->assign( 'questionCount', count( $exercise->getQuestionList() ) );
    $out .= $display->render();
}
else
{
    //-- exercise settings

    $detailsDisplay = new ModuleTemplate( 'CLQWZ' , 'exercise_details.tpl.php' );
    $detailsDisplay->assign( 'exercise', $exercise );
    $detailsDisplay->assign( 'dateTimeFormatLong', $dateTimeFormatLong );
    $detailsDisplay->assign( 'questionList', $exercise->getQuestionList() );
    $detailsDisplay->assign( 'localizedQuestionType', get_localized_question_type() );
    $out .= $detailsDisplay->render();

    $qlistDisplay = new ModuleTemplate( 'CLQWZ' , 'question_list.tpl.php' );
    $qlistDisplay->assign( 'exId', $exercise->getId() );
    $qlistDisplay->assign( 'questionList', $exercise->getQuestionList() );
    $qlistDisplay->assign( 'context', 'exercise' );
    $qlistDisplay->assign( 'localizedQuestionType', get_localized_question_type() );
    $out .= $qlistDisplay->render();

    
}

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
