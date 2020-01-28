<?php // $Id: edit_answers.php 14064 2012-03-19 15:10:37Z zefredz $

/**
 * CLAROLINE
 *
 * @version     $Revision: 14064 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 */

$tlabelReq = 'CLQWZ';

require '../../inc/claro_init_global.inc.php';

if ( ! claro_is_in_a_course() || !claro_is_course_allowed() ) claro_disp_auth_form(true);

$is_allowedToEdit = claro_is_allowed_to_edit();

// courseadmin reserved page
if( !$is_allowedToEdit )
{
    header("Location: " . Url::Contextualize('../exercise.php') );
    exit();
}

// tool libraries
include_once '../lib/exercise.class.php';
include_once '../lib/question.class.php';
// answer class should be inclulde by question class

include_once '../lib/exercise.lib.php';

// claroline libraries
include_once get_path('incRepositorySys') . '/lib/fileManage.lib.php';
include_once get_path('incRepositorySys') . '/lib/form.lib.php';
include_once get_path('incRepositorySys') . '/lib/htmlxtra.lib.php';

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
$question = new Question();

if( is_null($quId) || !$question->load($quId) )
{
    header('Location: '. Url::Contextualize('../exercise.php' ));
    exit();
}

if( !is_null($exId) )
{
    $exercise = new Exercise();
    // if exercise cannot be load set exId to null , it probably don't exist
    if( !$exercise->load($exId) ) $exId = null;
}

$askDuplicate = false;
// do not duplicate when there is no $exId, it means that we modify the question from pool
// do not duplicate when there is no $quId, it means that question is a new one
// check that question is used in several exercises
if( count_exercise_using_question($quId) > 1
    && !is_null($quId) && !is_null($exId)
    )
{
    $askDuplicate = true;
}

$dialogBox = new DialogBox();

/*
 * Execute commands
 */
if( $cmd == 'exEdit' )
{
    // add or remove answer, change step,...
    // should return true if form is really submitted
    if( $question->answer->handleForm() )
    {
        // form has to be saved, check input validity
        if( $question->answer->validate() )
        {
            if( count_exercise_using_question($quId) > 1
                && !is_null($quId) && !is_null($exId)
                && isset($_REQUEST['duplicate']) && $_REQUEST['duplicate'] == 'true'
                )
            {
                // duplicate object if used in several exercises
                $duplicated = $question->duplicate();

                // make exercise use the new created question object instead of the new one
                $exercise->removeQuestion($quId);
                $quId = $duplicated->getId(); // and reset $quId
                $exercise->addQuestion($quId);

                $question = $duplicated;
            }

            if( $question->answer->save() )
            {
                // update grade in question
                $question->setGrade($question->answer->getGrade());
                $question->save();

                header("Location: " . Url::Contextualize("./edit_question.php?exId=".$exId."&quId=".$quId) );
                exit();
            }
        }
    }

    if( $question->answer->getErrorList() )
    {
        $dialogBox->error( implode('<br />' . "\n", $question->answer->getErrorList()) );
    }
    // if we were not redirected it means form must be displayed
    $cmd =    'rqEdit';
}

/*
 * Output
 */

if( !is_null($exId) )
{
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Question'), Url::Contextualize('./edit_question.php?exId='.$exId.'&amp;quId='.$quId) );
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Exercise'), Url::Contextualize('./edit_exercise.php?exId='.$exId) );
}
else
{
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Question pool'), Url::Contextualize('./question_pool.php') );
}

ClaroBreadCrumbs::getInstance()->prepend( get_lang('Exercises'), Url::Contextualize( get_module_url('CLQWZ').'/exercise.php' ) );

$out = '';
if( !is_null($quId) )     $_SERVER['QUERY_STRING'] = 'exId='.$exId.'&amp;quId='.$quId;
else                    $_SERVER['QUERY_STRING'] = '';

$nameTools = get_lang('Edit answers');

$out .= claro_html_tool_title($nameTools);
// dialog box if required
$out .= $dialogBox->render();
$out .= $question->getQuestionHtml();
$out .= $question->answer->getFormHtml($exId,$askDuplicate);

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
