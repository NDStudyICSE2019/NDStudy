<?php // $Id: edit_question.php 14314 2012-11-07 09:09:19Z zefredz $

/**
 * CLAROLINE
 *
 * @version     $Revision: 14314 $
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
    header("Location: " . Url::Contextualize("../exercise.php"));
    exit();
}

// tool libraries
include_once '../lib/exercise.class.php';
include_once '../lib/question.class.php';

include_once '../lib/exercise.lib.php';

// claroline libraries
include_once get_path('incRepositorySys') . '/lib/form.lib.php';
include_once get_path('incRepositorySys') . '/lib/fileDisplay.lib.php';
include_once get_path('incRepositorySys') . '/lib/fileUpload.lib.php';
include_once get_path('incRepositorySys') . '/lib/fileManage.lib.php';
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

if( !is_null($quId) && !$question->load($quId) )
{
    // question cannot be load, display new question creation form
    $cmd = 'rqEdit';
    $quId = null;
}

if( !is_null($exId) )
{
    $exercise = new Exercise();
    // if exercise cannot be load set exId to null , it probably don't exist
    if( !$exercise->load($exId) ) $exId = null;
}

$askDuplicate = false;
// quId and exId have been specified and load operations worked
if( !is_null($quId) && !is_null($exId) )
{
    // do not duplicate when there is no $exId,
    // it means that we modify the question from pool

    // do not duplicate when there is no $quId,
    // it means that question is a new one

    // check that question is used in several exercises
    if( count_exercise_using_question($quId) > 1 )
    {
        if( isset($_REQUEST['duplicate']) && $_REQUEST['duplicate'] == 'true' )
        {
            // duplicate object if used in several exercises
            $duplicated = $question->duplicate();

            // make exercise use the new created question object instead of the new one
            $exercise->removeQuestion($quId);
            $quId = $duplicated->getId(); // and reset $quId
            $exercise->addQuestion($quId);

            $question = $duplicated;
        }
        else
        {
            $askDuplicate = true;
        }
    }
}

$dialogBox = new DialogBox();
$displayForm = false;

/*
 * Execute commands
 */
if( $cmd == 'exEdit' )
{
    // if quId is null it means that we create a new question

    $question->setTitle($_REQUEST['title']);
    $question->setDescription($_REQUEST['description']);
    $question->setCategoryId( isset( $_REQUEST['categoryId'] ) && is_numeric( $_REQUEST['categoryId'] ) ? (int)$_REQUEST['categoryId'] : null );
    
    if( is_null($quId) ) $question->setType($_REQUEST['type']);

    // delete previous file if required
    if( isset($_REQUEST['delAttachment']) && !is_null($quId) )
    {
        $question->deleteAttachment();
    }

    if( $question->validate() )
    {
        // handle uploaded file after validation of other fields
        if( isset($_FILES['attachment']['tmp_name']) && is_uploaded_file($_FILES['attachment']['tmp_name']) )
        {
            if( !$question->setAttachment($_FILES['attachment']) )
            {
                // throw error
                $dialogBox->error( get_lang(claro_failure::get_last_failure()  ) );
            }
        }

        $insertedId = $question->save();
        if( $insertedId )
        {
            // if create a new question in exercise context
            if( is_null($quId) && !is_null($exId) )
            {
                $exercise->addQuestion($insertedId);
            }

            // create a new question
            if( is_null($quId) )
            {
                // Go to answer edition
                header('Location: '. Url::Contextualize('edit_answers.php?exId='.$exId.'&quId='.$insertedId));
                exit();
            }
        }
        else
        {
            // sql error in save() ?
            $cmd = 'rqEdit';
        }
    }
    else
    {
        if( claro_failure::get_last_failure() == 'question_no_title' )
        {
            $dialogBox->error( get_lang('Field \'%name\' is required', array('%name' => get_lang('Title'))) );
        }
        $cmd = 'rqEdit';
    }

}

if( $cmd == 'rqEdit' )
{
    $form['title']                 = $question->getTitle();
    $form['description']         = $question->getDescription();
    $form['attachment']            = $question->getAttachment();
    $form['type']                 = $question->getType();
    $form['categoryId']           = $question->getCategoryId();

    $displayForm = true;
}

/*
 * Output
 */
if( is_null($quId) )
{
    $nameTools = get_lang('New question');
    ClaroBreadCrumbs::getInstance()->setCurrent( $nameTools, Url::Contextualize('./edit_question.php?exId='.$exId . '&amp;cmd=rqEdit' ) );
}
elseif( $cmd == 'rqEdit' )
{
    $nameTools = get_lang('Edit question');
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Question'), Url::Contextualize('./edit_question.php?exId='.$exId.'&amp;quId='.$quId ) );
    ClaroBreadCrumbs::getInstance()->setCurrent( $nameTools, Url::Contextualize('./edit_question.php?exId='.$exId.'&amp;quId='.$quId.'&amp;cmd=rqEdit') );
}
else
{
    $nameTools = get_lang('Question');
    ClaroBreadCrumbs::getInstance()->setCurrent( $nameTools, Url::Contextualize('./edit_question.php?exId='.$exId.'&amp;quId='.$quId) );
}

if( !is_null($exId) )
{
    ClaroBreadCrumbs::getInstance()->prepend( $exercise->getTitle(), Url::Contextualize( './edit_exercise.php?exId=' . $exId ) );
    //ClaroBreadCrumbs::getInstance()->prepend( get_lang('Exercise'), './edit_exercise.php?exId='.$exId );
}
else
{
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Question pool'), Url::Contextualize('./question_pool.php') );
}
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Exercises'), Url::Contextualize(get_module_url('CLQWZ').'/exercise.php') );

// Command list
$cmdList = array();

$cmdList[] = array(
    'img' => 'edit',
    'name' => get_lang('Edit question'),
    'url' => claro_htmlspecialchars(Url::Contextualize('./edit_question.php?exId='.$exId.'&cmd=rqEdit&quId='.$quId))
);

$cmdList[] = array(
    'img' => 'edit',
    'name' => get_lang('Edit answers'),
    'url' => claro_htmlspecialchars(Url::Contextualize('./edit_answers.php?exId='.$exId.'&cmd=rqEdit&quId='.$quId))
);

$cmdList[] = array(
    'img' => 'default_new',
    'name' => get_lang('New question'),
    'url' => claro_htmlspecialchars(Url::Contextualize('./edit_question.php?exId='.$exId.'&cmd=rqEdit'))
);

$out = '';

$out .= claro_html_tool_title($nameTools, null, $cmdList);

// dialog box if required
$out .= $dialogBox->render();


$localizedQuestionType = get_localized_question_type();

if( $displayForm )
{
    //-- edit form
    $display = new ModuleTemplate( 'CLQWZ' , 'question_form.tpl.php' );
    $display->assign( 'question', $question );
    $display->assign( 'exId', $exId );
    $display->assign( 'data', $form );
    $display->assign( 'relayContext', claro_form_relay_context() );
    $display->assign( 'askDuplicate', $askDuplicate );
    $display->assign( 'categoryList', getQuestionCategoryList() );
    $display->assign( 'questionType', get_localized_question_type() );
    $out .= $display->render();
}
else
{
    $out .= $question->getQuestionAnswerHtml();
}

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
