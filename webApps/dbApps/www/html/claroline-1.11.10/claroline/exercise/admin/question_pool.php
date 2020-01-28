<?php // $Id: question_pool.php 14420 2013-04-12 12:22:30Z zefredz $

/**
 * CLAROLINE
 *
 * @version     $Revision: 14420 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 */

$tlabelReq = 'CLQWZ';

require '../../inc/claro_init_global.inc.php';

if ( !claro_is_in_a_course() || !claro_is_course_allowed() ) claro_disp_auth_form(true);

$is_allowedToEdit = claro_is_allowed_to_edit();

// courseadmin reserved page
if( !$is_allowedToEdit )
{
    header("Location: ". Url::Contextualize("../exercise.php"));
    exit();
}

require_once '../lib/add_missing_table.lib.php';
init_qwz_questions_categories ();

// tool libraries
include_once '../lib/exercise.class.php';
include_once '../lib/question.class.php';
include_once '../lib/exercise.lib.php';

// claroline libraries
include_once get_path('incRepositorySys').'/lib/form.lib.php';
include_once get_path('incRepositorySys').'/lib/pager.lib.php';
include_once get_path('incRepositorySys').'/lib/fileManage.lib.php';

/*
 * DB tables definition for list query
 */
$tbl_cdb_names = get_module_course_tbl( array( 'qwz_exercise', 'qwz_question', 'qwz_rel_exercise_question' ), claro_get_current_course_id() );
$tbl_quiz_exercise = $tbl_cdb_names['qwz_exercise'];
$tbl_quiz_question = $tbl_cdb_names['qwz_question'];
$tbl_quiz_rel_exercise_question = $tbl_cdb_names['qwz_rel_exercise_question'];

/*
 * Init request vars
 */
if ( isset($_REQUEST['cmd']) )    $cmd = $_REQUEST['cmd'];
else                            $cmd = '';

if( isset($_REQUEST['exId']) && is_numeric($_REQUEST['exId']) ) $exId = (int) $_REQUEST['exId'];
else                                                            $exId = null;

if( isset($_REQUEST['quId']) && is_numeric($_REQUEST['quId']) ) $quId = (int) $_REQUEST['quId'];
else                                                            $quId = null;

if( isset($_REQUEST['filter']) )     $filter = $_REQUEST['filter'];
else                                $filter = 'all';

$categoryId = (substr($filter,0,10) == 'categoryId')&& is_numeric(substr($filter,10))?substr($filter,10):null;

/*
 * Init other vars
 */
$exercise = new Exercise();
if( !is_null($exId) )
{
    $exercise->load($exId);
}

$dialogBox = new DialogBox();

/*
 * Execute commands
 */
// use question in exercise
if( $cmd == 'rqUse' && !is_null($quId) && !is_null($exId) )
{
    if( $exercise->addQuestion($quId) )
    {
        // TODO show confirmation and back link
        header('Location: ' . Url::Contextualize( 'edit_exercise.php?exId='.$exId ));
    }
}

// delete question
if( $cmd == 'delQu' && !is_null($quId) )
{
    $question = new Question();
    if( $question->load($quId) )
    {
        if( !$question->delete() )
        {
            // TODO show confirmation and list
        }
    }
}

// export question
if( $cmd == 'exExport' && get_conf('enableExerciseExportQTI') )
{
    require_once '../export/qti2/qti2_export.php';
    require_once get_path('incRepositorySys') . '/lib/fileManage.lib.php';
    require_once get_path('incRepositorySys') . '/lib/file.lib.php';
    require_once get_path('incRepositorySys') . '/lib/thirdparty/pclzip/pclzip.lib.php';

    $question = new Qti2Question();
    $question->load($quId);

    // contruction of XML flow
    $xml = $question->export();

    // remove trailing slash
    if( substr($question->questionDirSys, -1) == '/' )
    {
        $question->questionDirSys = substr($question->questionDirSys, 0, -1);
    }

    //save question xml file
    if( !file_exists($question->questionDirSys) )
    {
        claro_mkdir($question->questionDirSys,CLARO_FILE_PERMISSIONS);
    }

    if( $fp = @fopen($question->questionDirSys."/question_".$quId.".xml", 'w') )
    {
        fwrite($fp, $xml);
        fclose($fp);
    }
    else
    {
        // interrupt process
    }

    // list of dirs to add in archive
    $filePathList[] = $question->questionDirSys;


    /*
     * BUILD THE ZIP ARCHIVE
     */

    // build and send the zip
    if( sendZip($question->getTitle(), $filePathList, $question->questionDirSys) )
    {
        exit();
    }
    else
    {
        $dialogBox->error( get_lang("Unable to send zip file") );
    }
}

if (($cmd == 'recupMultipleQuestions') && !is_null($exId))
{
	// add multiple question selection
	$sql = "SELECT `id` FROM `".  $tbl_quiz_question. "` ORDER BY `id`";
	$list = claro_sql_query_fetch_all_rows($sql);
	$ok = true;
	foreach ($list as $questionInfo)
	{
		$quId = $questionInfo['id'];

		if (isset($_REQUEST[$quId]))
		{
			if (!$exercise->addQuestion($quId) )
			{
				$ok = false;
			}
		}
	}
	if( $ok )
    {
        // TODO show confirmation and back link
        header('Location: ' . Url::Contextualize( 'edit_exercise.php?exId='.$exId ));
    }
}

/*
 * Get list
 */
//-- pager init
if( !isset($_REQUEST['offset']) )    $offset = 0;
else                                $offset = $_REQUEST['offset'];

//-- filters handling
if( !is_null($exId) )    $filterList = get_filter_list($exId);
else                    $filterList = get_filter_list();

if( is_numeric($filter) )
{
    $filterCondition = " AND REQ.`exerciseId` = ".$filter;
}
elseif( $filter == 'orphan' )
{
    $filterCondition = " AND REQ.`exerciseId` IS NULL ";
}
else if (! is_null($categoryId) )
{
    $filterCondition = "AND id_category='".(int)$categoryId."' ";
}
else // $filter == 'all'
{
    $filterCondition = "";
}

//-- prepare query
if ( !is_null($categoryId))
{
     // Filter on categories
         $sql = "SELECT Q.`id`, Q.`title`, Q.`type`, Q.`id_category`
              FROM `".$tbl_quiz_question."` AS Q
              WHERE 1 = 1
             " . $filterCondition . "
          GROUP BY Q.`id`
          ORDER BY Q.`title`, Q.`id`";
}
else if( !is_null($exId) )
{
    $questionList = $exercise->getQuestionList();

    if( is_array($questionList) && !empty($questionList) )
    {
        foreach( $questionList as $aQuestion )
        {
            $questionIdList[] = $aQuestion['id'];
        }
        $questionCondition = " AND Q.`id` NOT IN ("  . implode(', ', array_map( 'intval', $questionIdList) ) . ") ";
    }
    else
    {
        $questionCondition = "";
    }

    // TODO probably need to adapt query with a left join on rel_exercise_question for filter

    $sql = "SELECT Q.`id`, Q.`title`, Q.`type`, Q.`id_category`
              FROM `".$tbl_quiz_question."` AS Q
              LEFT JOIN `".$tbl_quiz_rel_exercise_question."` AS REQ
              ON REQ.`questionId` = Q.`id`
              WHERE 1 = 1
             " . $questionCondition . "
             " . $filterCondition . "
          GROUP BY Q.`id`
          ORDER BY Q.`title`, Q.`id`";

}
else
{
    $sql = "SELECT Q.`id`, Q.`title`, Q.`type`, Q.`id_category`
              FROM `".$tbl_quiz_question."` AS Q
              LEFT JOIN `".$tbl_quiz_rel_exercise_question."` AS REQ
              ON REQ.`questionId` = Q.`id`
              WHERE 1 = 1
             " . $filterCondition . "
          GROUP BY Q.`id`
          ORDER BY Q.`title`, Q.`id`";
}

// get list
$myPager = new claro_sql_pager($sql, $offset, get_conf('questionPoolPager',25));
$questionList = $myPager->get_result_list();

/*
 * Output
 */

if( !is_null($exId) )
{
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Exercise'), Url::Contextualize('./edit_exercise.php?exId='.$exId) );
    ClaroBreadCrumbs::getInstance()->setCurrent( get_lang('Question pool'), Url::Contextualize($_SERVER['PHP_SELF'].'?exId='.$exId) );
    $pagerUrl = Url::Contextualize($_SERVER['PHP_SELF'].'?exId='.$exId);
}
else if ( !is_null($categoryId) )
{
	$pagerUrl = Url::Contextualize($_SERVER['PHP_SELF'].'?filter='.$filter);
}
else
{
    ClaroBreadCrumbs::getInstance()->setCurrent( get_lang('Question pool'), Url::Contextualize($_SERVER['PHP_SELF']) );
    $pagerUrl = Url::Contextualize($_SERVER['PHP_SELF']);
}

ClaroBreadCrumbs::getInstance()->prepend( get_lang('Exercises'), Url::Contextualize(get_module_url('CLQWZ').'/exercise.php') );

$nameTools = get_lang('Question pool');

// Tool list
$toolList = array();

if( !is_null($exId) )
{
    $toolList[] = array(
        'img' => 'back',
        'name' => get_lang('Go back to the exercise'),
        'url' => claro_htmlspecialchars(Url::Contextualize('edit_exercise.php?exId='.$exId))
    );
}

$toolList[] = array(
    'img' => 'default_new',
    'name' => get_lang('New question'),
    'url' => claro_htmlspecialchars(Url::Contextualize('edit_question.php?cmd=rqEdit'))
);

$out = '';
$out .= claro_html_tool_title($nameTools, null, $toolList);
$out .= $dialogBox->render();


//-- filter listbox
$attr['onchange'] = 'filterForm.submit()';

$out .= "\n"
.     '<form method="get" name="filterForm" action="question_pool.php">' . "\n"
.     '<input type="hidden" name="exId" value="'.$exId.'" />' . "\n"
.     claro_form_relay_context() . "\n"
.     '<p align="right">' . "\n"
.     '<label for="filter">'.get_lang('Filter').'&nbsp;:&nbsp;</label>' . "\n"
.     claro_html_form_select('filter',$filterList, $filter, $attr) . "\n"
.     '<noscript>' . "\n"
.     '<input type="submit" value="'.get_lang('Ok').'" />' . "\n"
.     '</noscript>' . "\n"
.     '</p>' . "\n"
.     '</form>' . "\n\n";

//-- pager
$out .= $myPager->disp_pager_tool_bar($pagerUrl);

/*
 * enable multiple question selection
 */
 if ( !is_null($exId) )
 {
 	$out .= '<form method="post" name="QCMEncode" action="'.$_SERVER['PHP_SELF'].'?cmd=recupMultipleQuestions">' . "\n";
 	$out .= '<input type="hidden" name="exId" value="'.$exId.'" />' . "\n";
 }

//-- list
$display = new ModuleTemplate( 'CLQWZ' , 'question_list.tpl.php' );
$display->assign( 'exId', $exId );
$display->assign( 'questionList', $questionList );
$display->assign( 'context', is_null( $exId ) ? 'pool' : 'reuse' );
$display->assign( 'localizedQuestionType', get_localized_question_type() );
$display->assign( 'offset', $offset );
$out .= $display->render();

/*
 * enable multiple question selection
 */
 if ( !is_null($exId) )
 {
 	$out .= '<div align="center"><input type="submit" name="submit" value="'.get_lang('Ok').'" />' . "\n";
 	$out .= '<input type="reset" name="reset" value="'.get_lang('cancel').'" /></div>' . "\n";
 	$out .= '</form>' . "\n";
 }

//-- pager
$out .= $myPager->disp_pager_tool_bar($pagerUrl);

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
