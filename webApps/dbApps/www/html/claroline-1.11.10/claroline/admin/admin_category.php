<?php //$Id: admin_class_cours.php 12608 2010-09-15 11:20:46Z abourguignon $

/**
 * CLAROLINE
 *
 * Management tools for categories' tree.
 *
 * @version     $Revision: 11767 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 * @author      Antonin Bourguignon <antonin.bourguignon@claroline.net>
 * @since       1.10
 */

// Reset session variables
$cidReset = true; // course id
$gidReset = true; // group id
$tidReset = true; // tool id

// Load Claroline kernel
require_once dirname(__FILE__) . '/../inc/claro_init_global.inc.php';

// Security check: is the user logged as administrator ?
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

// Initialisation of global variables and used classes and libraries
require_once get_path('incRepositorySys') . '/lib/clarocategory.class.php';
include claro_get_conf_repository() . 'CLHOME.conf.php';

// Instanciate dialog box
$dialogBox = new DialogBox();

// Build the breadcrumb
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );
$nameTools = get_lang('Categories');

// Get the cmd and id arguments
$cmd   = isset($_REQUEST['cmd'])?$_REQUEST['cmd']:null;
$id    = isset($_REQUEST['categoryId'])?$_REQUEST['categoryId']:null;

// Javascript confirm pop up declaration for header
JavascriptLanguage::getInstance ()->addLangVar('Are you sure to delete %name ?');

JavascriptLoader::getInstance()->load('admin');

switch ( $cmd )
{
    // Display form to create a new category
    case 'rqAdd' :
        $category = new claroCategory();
        $dialogBox->form( $category->displayForm() );
    break;
    
    // Create a new category
    case 'exAdd' :
        $category = new claroCategory();
        $category->handleForm();
        
        if ( $category->validate() )
        {
            $category->save();
            $dialogBox->success( get_lang('Category created') );
        }
        else
        {
            if ( claro_failure::get_last_failure() == 'category_duplicate_code')
            {
                $dialogBox->error( get_lang('This code already exists') );
            }
            elseif ( claro_failure::get_last_failure() == 'category_missing_field')
            {
                $dialogBox->error( get_lang('Some fields are missing') );
            }
            
            $dialogBox->form( $category->displayForm() );
        }
    break;
    
    // Display form to edit a category
    case 'rqEdit' :
        $category = new claroCategory();
        if ($category->load($id))
            $dialogBox->form( $category->displayForm() );
        else
            $dialogBox->error( get_lang('Category not found') );
    break;
    
    // Edit a new category
    case 'exEdit' :
        $category = new claroCategory();
        $category->handleForm();
        
        if ( $category->validate() )
        {
            $category->save();
            $dialogBox->success( get_lang('Category modified') );
        }
        else
        {
            if ( claro_failure::get_last_failure() == 'category_duplicate_code' )
            {
                $dialogBox->error( get_lang('This code already exists') );
            }
            elseif ( claro_failure::get_last_failure() == 'category_self_linked' )
            {
                $dialogBox->error( get_lang('Category can\'t be its own parent') );
            }
            elseif ( claro_failure::get_last_failure() == 'category_child_linked' )
            {
                $dialogBox->error( get_lang('Category can\'t be linked to one of its own children') );
            }
            elseif ( claro_failure::get_last_failure() == 'category_missing_field' )
            {
                $dialogBox->error( get_lang('Some fields are missing') );
            }
            
            $dialogBox->form( $category->displayForm() );
        }
    break;
    
    // Delete an existing category
    case 'exDelete' :
        $category = new claroCategory();
        if ($category->load($id))
            if ( ClaroCategory::countSubCategories($category->id) > 0 )
            {
                $dialogBox->error( get_lang('You cannot delete a category having sub categories') );
            }
            else
            {
                $category->delete();
                $dialogBox->success( get_lang('Category deleted.  Courses linked to this category have been linked to the root category.') );
            }
        else
            $dialogBox->error( get_lang('Category not found') );
    break;
    
    // Shift or displace category (up)
    case 'exMoveUp' :
        $category = new claroCategory();
        
        if ($category->load($id))
        {
            $category->decreaseRank();
            
            if ( claro_failure::get_last_failure() == 'category_no_predecessor')
            {
                $dialogBox->error( get_lang('This category can\'t be moved up') );
            }
            else
            {
                $dialogBox->success( get_lang('Category moved up') );
            }
        }
        else
            $dialogBox->error( get_lang('Category not found') );
    break;
    
    // Shift or displace category (down)
    case 'exMoveDown' :
        $category = new claroCategory();
        
        if ($category->load($id))
        {
            $category->increaseRank();
            
            if ( claro_failure::get_last_failure() == 'category_no_successor')
            {
                $dialogBox->error( get_lang('This category can\'t be moved down') );
            }
            else
            {
                $dialogBox->success( get_lang('Category moved down') );
            }
        }
        else
            $dialogBox->error( get_lang('Category not found') );
    break;
    
    // Change the visibility of a category
    case 'exVisibility' :
        $category = new claroCategory(null, null, null, null, null, null, null, null);
        
        if ($category->load($id))
        {
            if( $category->swapVisibility())
            {
                $dialogBox->success( get_lang('Category\'s visibility modified') );
            }
            else
            {
                switch ( claro_failure::get_last_failure() )
                {
                    case 'category_not_found' :
                        $dialogBox->error( get_lang('Error : Category not found') );
                        break;
                }
            }
        }
        else
            $dialogBox->error( get_lang('Category not found') );
    break;
}

// Get categories
$categories = claroCategory::getAllCategories();

// Command list
$cmdList = array();

$cmdList[] = array(
    'img' => 'category_new',
    'name' => get_lang('Create a category'),
    'url' => $_SERVER['PHP_SELF'] . '?cmd=rqAdd'
);

// Display
$template = new CoreTemplate('admin_category.tpl.php');

$template->assign('title', claro_html_tool_title($nameTools, null, $cmdList));
$template->assign('dialogBox', $dialogBox);
$template->assign('categories', $categories);

// Append output
Claroline::getDisplay()->body->appendContent($template->render());

// Generate output
echo Claroline::getDisplay()->render();