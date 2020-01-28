<?php // $Id: question_category.php 14314 2012-11-07 09:09:19Z zefredz $

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

if ( !claro_is_in_a_course() || !claro_is_course_allowed() ) claro_disp_auth_form(true);

require_once '../lib/add_missing_table.lib.php';
init_qwz_questions_categories ();

claro_set_display_mode_available(true);

$is_allowedToEdit = claro_is_allowed_to_edit();

if ( !$is_allowedToEdit ) claro_disp_auth_form(true);

$is_allowedToTrack = claro_is_allowed_to_edit() && get_conf('is_trackingEnabled');

// tool libraries
include_once '../lib/question.class.php';


// claroline libraries
include_once get_path('incRepositorySys').'/lib/pager.lib.php';

/*
 * DB tables definition
 */

$tbl_cdb_names = get_module_course_tbl( array( 'qwz_questions_categories' ), claro_get_current_course_id() );
$tbl_qwz_question_categorie = $tbl_cdb_names['qwz_questions_categories'];


// init request vars

$cmd = (isset($_REQUEST['cmd']))?$_REQUEST['cmd']:null;
$catId = (isset($_REQUEST['catId']))?$_REQUEST['catId']:null;

$dialogBox = new DialogBox();

if( !is_null($cmd) )
{
    $questionCategory = new QuestionCategory();

    if (!is_null($catId))
    {
        $questionCategory->setId($catId);
        if ( ( $cmd=='rqEdit' ) || ( $cmd=='exEdit' ))
        {
            $questionCategory->load();
            if ($cmd == 'rqEdit' )
            {
                $form['title']                 = $questionCategory->getTitle();
                $form['description']           = $questionCategory->getDescription();
            }
            
            if ($cmd == 'exEdit'  && $catId)
            {
                $questionCategory->setTitle($_REQUEST['title']);
                $questionCategory->setDescription($_REQUEST['description']);
        
                if( $questionCategory->validate() )
                {
                    if( $questionCategory->save() )
                    {
                        if( $catId == -1 )
                        {
                            $dialogBox->success( get_lang('Category created') );
                        }
                        else
                        {
                            $dialogBox->success( get_lang('Category updated') );
                        }
                    }
                }
                else
                {
                    if( claro_failure::get_last_failure() == 'category_no_title' )
                    {
                        $dialogBox->error( get_lang('Field \'%name\' is required', array('%name' => get_lang('Title'))) );
                    }
                    else if( claro_failure::get_last_failure() == 'category_already_exists' )
                    {
                        $dialogBox->error( get_lang('Category alreday exists') );
                    }
                }
            }
        }
    }
    if( $cmd=='add' )
    {
        $questionCategory->load();
        $catId = -1;
        $form['title']                 = '';
        $form['description']           = '';
        $cmd = 'rqEdit';
    }
    
    if( $cmd == 'exDel' && $catId )
    {
        $questionCategory = new QuestionCategory();
        $questionCategory->setId($catId);
        $questionCategory->load();

        if (!$questionCategory->delete())
        {
            if ($questionCategory->id != -1)
            {
                $dialogBox->error( get_lang('Cannot delete category because used in questions') );
            }
            else
            {
                // pb in delete ?
                 $dialogBox->error( get_lang('Cannot delete category') );
            }
        }
        else
        {
                $dialogBox->success( get_lang('Category deleted') );
        }
    }
}

/*
 * Get list
 */
// pager initialisation
if( !isset($_REQUEST['offset']) )    $offset = 0;
else                                $offset = $_REQUEST['offset'];

$displayForm = ($cmd == 'rqEdit')?true:false;

// prepare query

    // we need to check if exercise is used as a module in a learning path
    // to display a more complete confirm message for delete aciton
    $sql = "SELECT `id`, `title`, `description`
              FROM `".$tbl_qwz_question_categorie."`
             ORDER BY `title`";
    

$myPager = new claro_sql_pager($sql, $offset, get_conf('exercisesPerPage',25));
$questionCategoryList = $myPager->get_result_list();

/*
 * Output
 */

ClaroBreadCrumbs::getInstance()->prepend( get_lang('Exercises'), Url::Contextualize('../exercise.php') );

$nameTools = get_lang('Question categories');

$noQUERY_STRING = true;

// Tool list
$toolList = array();

if($is_allowedToEdit)
{
    $toolList[] = array(
        'img' => 'quiz_new',
        'name' => get_lang('New category'),
        'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'].'?cmd=add'))
    );
}

$out = '';
$out .=  $dialogBox->render();
$out .= claro_html_tool_title($nameTools, null, $toolList);


if( $displayForm )
{
    $out .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'" >' . "\n\n"
    .    claro_form_relay_context()
    .     '<input type="hidden" name="cmd" value="exEdit" />' . "\n"
    .     '<input type="hidden" name="catId" value="'.$catId.'" />' . "\n"
    .     '<input type="hidden" name="claroFormId" value="'.uniqid('').'" />' . "\n";

    $out .= '<table border="0" cellpadding="5">' . "\n";

    //--
    // title
    $out .= '<tr>' . "\n"
    .     '<td valign="top"><label for="title">'.get_lang('Title').'&nbsp;<span class="required">*</span>&nbsp;:</label></td>' . "\n"
    .     '<td><input type="text" name="title" id="title" size="60" maxlength="200" value="'.$form['title'].'" /></td>' . "\n"
    .     '</tr>' . "\n\n";

    // description
    $out .= '<tr>' . "\n"
    .     '<td valign="top"><label for="description">'.get_lang('Description').'&nbsp;:</label></td>' . "\n"
    .     '<td>'.claro_html_textarea_editor('description', $form['description']).'</td>' . "\n"
    .     '</tr>' . "\n\n";


    //--
    $out .= '<tr>' . "\n"
    .     '<td>&nbsp;</td>' . "\n"
    .     '<td><small>' . get_lang('<span class="required">*</span> denotes required field') . '</small></td>' . "\n"
    .     '</tr>' . "\n\n";

    //-- buttons
    $out .= '<tr>' . "\n"
    .     '<td>&nbsp;</td>' . "\n"
    .     '<td>'
    .     '<input type="submit" name="" id="" value="'.get_lang('Ok').'" />&nbsp;&nbsp;'
    .     claro_html_button(Url::Contextualize($_SERVER['PHP_SELF']) , get_lang("Cancel") )
    .     '</td>' . "\n"
    .     '</tr>' . "\n\n";

    $out .= '</table>' . "\n\n"
    .     '</form>' . "\n\n";
}

//-- pager
$out .= $myPager->disp_pager_tool_bar($_SERVER['PHP_SELF']);

//-- list

$out .= '<table class="claroTable emphaseLine" border="0" align="center" cellpadding="2" cellspacing="2" width="100%">' . "\n\n"
.     '<thead>' . "\n"
.     '<tr>' . "\n"
.     '<th>' . get_lang('Title') . '</th>' . "\n";

$colspan = 1;

if( $is_allowedToEdit )
{
    $out .= '<th>' . get_lang('Modify') . '</th>' . "\n"
    .     '<th>' . get_lang('Delete') . '</th>' . "\n";
    
    $colspan = 3;

}

$out .= '</tr>' . "\n"
.     '</thead>' . "\n\n"
.     '<tbody>' . "\n\n";


if( !empty($questionCategoryList) )
{
    foreach( $questionCategoryList as $aCategory )
    {
        $out .= '<tr>' . "\n"
        .     '<td>'
        .     $aCategory['title']
        .     '</a>'
        .     '</td>' . "\n";

            $out .= '<td align="center">'
            .     '<a href="'.claro_htmlspecialchars(Url::Contextualize('question_category.php?cmd=rqEdit&amp;catId='.$aCategory['id'] ) ).'">'
            .     '<img src="' . get_icon_url('edit') . '" alt="'.get_lang('Modify').'" />'
            .     '</a>'
            .     '</td>' . "\n";

            $confirmString = get_lang('Are you sure you want to delete this category ?');

            $out .= '<td align="center">'
            .     '<a href="'.claro_htmlspecialchars(Url::Contextualize('question_category.php?catId='.$aCategory['id'].'&amp;cmd=exDel' ) ).'" onclick="javascript:if(!confirm(\''.clean_str_for_javascript($confirmString).'\')) return false;">'
            .     '<img src="' . get_icon_url('delete') . '" alt="'.get_lang('Delete').'" />'
            .     '</a>'
            .     '</td>' . "\n";

        $out .= '</tr>' . "\n\n";
    }
}
else
{
    $out .= '<tr>' . "\n"
    .     '<td colspan="'.$colspan.'">' . get_lang('Empty') . '</td>' . "\n"
    .     '</tr>' . "\n\n";
}

$out .= '</tbody>' . "\n\n"
.     '</table>' . "\n\n";


//-- pager
$out .= $myPager->disp_pager_tool_bar($_SERVER['PHP_SELF']);

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
