<?php // $Id: index.php 13910 2012-01-04 15:57:30Z abourguignon $

/**
 * CLAROLINE
 *
 * Forum tool.
 *
 * Entry point for forum tool, handling display and administration of forums and forum categories
 * As from Claroline 1.9.6, includes functionality of deprecated script admin.php
 *
 * @version     $Revision: 13910 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      FUNDP - WebCampus <webcampus@fundp.ac.be>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     CLFRM
 *
 * @TODO        $last_post would be always a timestamp
 */

$tlabelReq = 'CLFRM';
$gidReq = null;
$gidReset = true;

//load Claroline kernel
require_once dirname( __FILE__ ) . '/../../claroline/inc/claro_init_global.inc.php';
//security check
if( !claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form( true );

//load required libraries
require_once get_path( 'incRepositorySys' ) . '/lib/forum.lib.php';
require_once get_path( 'incRepositorySys' ) . '/lib/group.lib.inc.php';

//init general purpose vars
claro_set_display_mode_available( true );
$is_allowedToEdit = claro_is_allowed_to_edit();
$dialogBox = new DialogBox();

//handle user input and possible associated exceptions
try
{
    $userInput = Claro_UserInput::getInstance();
    //admin only commands
    if( $is_allowedToEdit )
    {
        //set validators for user input
        $userInput->setValidator( 'cmd', new Claro_Validator_AllowedList( array(
            'show',
            'exMkCat', 'rqMkCat',
            'exEdCat', 'rqEdCat',
            'exDelCat',
            'exMvUpCat', 'exMvDownCat',
            'exMkForum', 'rqMkForum',
            'exEdForum', 'rqEdForum',
            'exDelForum',
            'exEmptyForum',
            'exMvUpForum', 'exMvDownForum',
            'rqSearch'
        ) ) );
        $userInput->setValidator( 'forumId', new Claro_Validator_ValueType( 'numeric' ) );
        $userInput->setValidator( 'forumId', new Claro_Validator_NotEmpty() );
        $userInput->setValidator( 'catId', new Claro_Validator_ValueType( 'numeric' ) );
        $userInput->setValidator( 'catId', new Claro_Validator_NotEmpty() );
        $userInput->setValidator( 'forumName', new Claro_Validator_ValueType( 'string' ) );
        $userInput->setValidator( 'catName', new Claro_Validator_ValueType( 'string' ) );
        $userInput->setValidator( 'forumDesc', new Claro_Validator_ValueType( 'string' ) );
        $userInput->setValidator( 'forumPostUnallowed', new Claro_Validator_ValueType( 'string' ) );
        $userInput->setValidator( 'anonymity', new Claro_Validator_ValueType( 'string' ) );
    }
    else
    {
        $userInput->setValidator( 'cmd', new Claro_Validator_AllowedList( array( 'show', 'rqSearch' ) ) );
    }
    //collect user input
    $cmd = $userInput->get( 'cmd', 'show' );
    switch( $cmd )
    {
        case 'exMkCat' :
            $catName = trim( $userInput->getMandatory( 'catName' ) );
            break;
        case 'exEdCat' :
            $catId = $userInput->getMandatory( 'catId' );
            $catName = trim( $userInput->getMandatory( 'catName' ) );
            break;
        case 'rqEdCat' :
            $catId = $userInput->getMandatory( 'catId' );
            break;
        case 'exDelCat' :
            $catId = $userInput->getMandatory( 'catId' );
            break;
        case 'exMvUpCat' :
            $catId = $userInput->getMandatory( 'catId' );
            break;
        case 'exMvDownCat' :
            $catId = $userInput->getMandatory( 'catId' );
            break;
        case 'exMkForum' :
            $forumPostAllowed = $userInput->get( 'forumPostUnallowed', 'off' ) == 'on' ? false : true;
            $anonymityType = get_conf( 'clfrm_anonymity_enabled', 'TRUE' ) == 'TRUE'
                            ? $userInput->getMandatory( 'anonymity' )
                            : 'forbidden';
            $forumName = trim( $userInput->getMandatory( 'forumName' ) );
            $catId = $userInput->getMandatory( 'catId' );
            $forumDesc = trim( $userInput->get( 'forumDesc', '' ) );
            break;
        case 'exEdForum' :
            $forumId = $userInput->getMandatory( 'forumId' );
            $forumPostAllowed = $userInput->get( 'forumPostUnallowed' , 'off' ) == 'on' ? false : true;
            $anonymityType = get_conf( 'clfrm_anonymity_enabled', 'TRUE' ) == 'TRUE'
                            ? $userInput->getMandatory( 'anonymity' )
                            : 'forbidden';
            $forumName = trim( $userInput->getMandatory( 'forumName' ) );
            $catId = $userInput->getMandatory( 'catId' );
            $forumDesc = trim( $userInput->get( 'forumDesc', '' ) );
            break;
        case 'rqEdForum' :
            $forumId = $userInput->getMandatory( 'forumId' );
            break;
        case 'exDelForum' :
            $forumId = $userInput->getMandatory( 'forumId' );
            break;
        case 'exEmptyForum' :
            $forumId = $userInput->getMandatory( 'forumId' );
            break;
        case 'exMvUpForum' :
            $forumId = $userInput->getMandatory( 'forumId' );
            break;
        case 'exMvDownForum' :
            $forumId = $userInput->getMandatory( 'forumId' );
            break;
        default : break;
    }
}
catch( Exception $ex )
{
    if( claro_debug_mode() )
    {
        $dialogBox->error( '<pre>' . $ex->__toString() . '</pre>' );
    }
    if( $ex instanceof Claro_Validator_Exception )
    {
        if( !isset( $cmd ) ) $cmd = 'cmd';
        switch( $cmd )
        {
            case 'cmd' :
                $cmd = 'show';
                break;
            case 'exMkCat' :
                $dialogBox->error( get_lang( 'Category name cannot be empty' ) );
                $cmd = 'rqMkCat';
                break;
            case 'exEdCat' :
                $dialogBox->error( get_lang( 'Category name cannot be empty' ) );
                $cmd = 'rqEdCat';
                break;
            case 'rqEdCat' :
                $dialogBox->error( get_lang( 'Category name cannot be empty' ) );
                $cmd = 'show';
                break;
            case 'exDelCat' :
                $dialogBox->error( get_lang( 'Unknown category' ) );
                $cmd = 'show';
                break;
            case 'exMvUpCat' :
                $dialogBox->error( get_lang( 'Unknown category' ) );
                $cmd = 'show';
                break;
            case 'exMvDownCat' :
                $dialogBox->error( get_lang( 'Unknown category' ) );
                $cmd = 'show';
                break;
            case 'exMkForum' :
                $dialogBox->error( get_lang( 'Missing field(s)' ) );
                $cmd = 'rqMkForum';
                break;
            case 'exEdForum' :
                $dialogBox->error( get_lang( 'Forum name cannot be empty' ) );
                $cmd = 'rqEdForum';
                break;
            case 'rqEdForum' :
                $dialogBox->error( get_lang( 'Unknown forum' ) );
                $cmd = 'show';
                break;
            case 'exDelForum' :
                $dialogBox->error( get_lang( 'Unknown forum' ) );
                $cmd = 'show';
                break;
            case 'exEmptyForum' :
                $dialogBox->error( get_lang( 'Unknown forum' ) );
                $cmd = 'show';
                break;
            case 'exMvUpForum' :
                $dialogBox->error( get_lang( 'Unknown forum' ) );
                $cmd = 'show';
                break;
            case 'exMvDownForum' :
                $dialogBox->error( get_lang( 'Unknown forum' ) );
                $cmd = 'show';
                break;
            default : break;
        }
    }
    elseif( $ex instanceof Claro_Input_Exception )
    {
        $dialogBox->error( get_lang( 'Unset input variable' ) );
        $cmd = 'show';
    }
    else
    {
        $dialogBox->error( get_lang( 'Unexpected error' ) );
        $cmd = 'show';
    }
}
    
//handle admin commands
if( $is_allowedToEdit )
{
    if( 'exMkCat' == $cmd )
    {
        if( create_category( $catName ) )
        {
           $dialogBox->success( get_lang( 'The new category has been created.' ) );
        }
        else
        {
            $dialogBox->error( get_lang( 'Unable to create category' ) );
            $cmd = 'rqMkCat';
        }
    }
        
    if( 'rqMkCat' == $cmd  )
    {
        try
        {
            $form = new ModuleTemplate( 'CLFRM', 'forum_editcat.tpl.php' );
            $form->assign( 'header', get_lang( 'Add a category' ) );
            $form->assign( 'catName', '' );
            $form->assign( 'nextCommand', 'exMkCat' );
            $form->assign( 'catId', 0 );
            $dialogBox->form( $form->render() );
        }
        catch( Exception $ex )
        {
            if( claro_debug_mode() )
            {
                $dialogBox->error( '<pre>' . $ex->__toString() . '</pre>' );
            }
            else
            {
                $dialogBox->error( $ex->getMessage() );
            }
        }
    }
    
    if( 'exEdCat' == $cmd )
    {
        if( update_category_title( $catId, $catName ) )
        {
            $dialogBox->success( get_lang( 'Category updated' ) );
        }
        else
        {
            $dialogBox->error( get_lang( 'Unable to update category' ) );
        }
    }
    
    if( 'rqEdCat' == $cmd )
    {
        $categorySettingList = get_category_settings( $catId );
    
        if( $categorySettingList )
        {
            try
            {
                $form = new ModuleTemplate( 'CLFRM', 'forum_editcat.tpl.php' );
                $form->assign( 'header', get_lang( 'Edit category' ) );
                $form->assign( 'catName', $categorySettingList['cat_title'] );
                $form->assign( 'nextCommand', 'exEdCat' );
                $form->assign( 'catId', $catId );
                $dialogBox->form( $form->render() );
            }
            catch( Exception $ex )
            {
                if( claro_debug_mode() )
                {
                    $dialogBox->error( '<pre>' . $ex->__toString() . '</pre>' );
                }
                else
                {
                    $dialogBox->error( $ex->getMessage() );
                }
            }
        }
        else
        {
            $dialogBox->error( get_lang( 'Unknown category' ) );
        }
    }
    
    if( 'exMkForum' == $cmd )
    {
        if( $catId != 0 )
        {
            if( create_forum( $forumName, $forumDesc, $forumPostAllowed, $catId, $anonymityType ) )
            {
               $dialogBox->success( get_lang( 'Forum created' ) );
            }
            else
            {
               $dialogBox->error( get_lang( 'Unable to create forum' ) );
               $cmd = 'rqMkForum';
            }
        }
        else
        {
            $dialogBox->error( get_lang( 'Unknown category' ) );
            $cmd = 'rqMkForum';
        }
    }
        
    if( 'rqMkForum' == $cmd )
    {
        $categoryList = get_category_list();
    
        if( count( $categoryList ) > 0 )
        {
            try
            {
                $form = new ModuleTemplate( 'CLFRM', 'forum_editforum.tpl.php' );
                $form->assign( 'header', get_lang( 'Add forum' ) );
                $form->assign( 'forumName', '' );
                $form->assign( 'forumDesc', '' );
                $form->assign( 'forumId', 0 );
                $form->assign( 'nextCommand', 'exMkForum' );
                $form->assign( 'catId', 0 );
                $form->assign( 'categoryList', $categoryList );
                $form->assign( 'anonymity_enabled', get_conf( 'clfrm_anonymity_enabled', true ) ? true : false );
                $form->assign( 'anonymity', 'forbidden' );
                $form->assign( 'is_postAllowed', true );
        
                $dialogBox->form( $form->render() );
            }
            catch( Exception $ex )
            {
                if( claro_debug_mode() )
                {
                    $dialogBox->error( '<pre>' . $ex->__toString() . '</pre>' );
                }
                else
                {
                    $dialogBox->error( $ex->getMessage() );
                }
            }
        }
        else
        {
            $dialogBox->warning( get_lang( 'There are currently no forum categories!' )
                                 . '<br/>'
                                 . get_lang( 'Please create a category first' ) );
            $cmd = 'show';
        }
    }
    if( 'exEdForum' == $cmd )
    {
        if( update_forum_settings( $forumId, $forumName, $forumDesc, $forumPostAllowed, $catId, $anonymityType ) )
        {
            $dialogBox->success( get_lang( 'Forum updated' ) );
        }
        else
        {
            $dialogBox->error( get_lang( 'Unable to update forum' ) );
            $cmd = 'rqEdForum';
        }
    }
    
    if( 'rqEdForum' == $cmd )
    {
        $forumSettingList = get_forum_settings( $forumId );
        $categoryList = get_category_list();
    
        if( count( $categoryList ) > 0 )
        {
            try
            {
                $form = new ModuleTemplate( 'CLFRM', 'forum_editforum.tpl.php' );
                $form->assign( 'header', get_lang( 'Edit forum' ) );
                $form->assign( 'forumId', $forumId );
                $form->assign( 'forumName', $forumSettingList['forum_name'] );
                $form->assign( 'forumDesc', $forumSettingList['forum_desc'] );
                $form->assign( 'nextCommand', 'exEdForum' );
                $form->assign( 'catId', $forumSettingList['cat_id'] );
                $form->assign( 'categoryList', $categoryList );
                $form->assign( 'anonymity_enabled', get_conf( 'clfrm_anonymity_enabled', true ) ? true : false );
                $form->assign( 'anonymity', $forumSettingList['anonymity'] );
                $form->assign( 'is_postAllowed', $forumSettingList['forum_access'] != 0 ? true : false );
        
                $dialogBox->form( $form->render() );
            }
            catch( Exception $ex )
            {
                if( claro_debug_mode() )
                {
                    $dialogBox->error( '<pre>' . $ex->__toString() . '</pre>' );
                }
                else
                {
                    $dialogBox->error( $ex->getMessage() );
                }
            }
        }
        else
        {
            $dialogBox->warning( get_lang( 'There are currently no forum categories!' )
                                 . '<br/>'
                                 . get_lang( 'Please create a category first' ) );
            $cmd = 'show';
        }
    }
    if( 'exDelCat' == $cmd )
    {
        if( delete_category( $catId ) )
        {
            $dialogBox->success( get_lang( 'Category deleted' ) );
        }
        else
        {
            $dialogBox->error( get_lang( 'Unable to delete category' ) );
    
            if( claro_failure::get_last_failure() == 'GROUP_FORUMS_CATEGORY_REMOVALE_FORBIDDEN' )
            {
                $dialogBox->error( get_lang( 'Group forums category can\'t be deleted' ) );
            }
            elseif( claro_failure::get_last_failure() == 'GROUP_FORUM_REMOVALE_FORBIDDEN' )
            {
                $dialogBox->error( get_lang( 'You can not remove a group forum. You have to remove the group first' ) );
            }
        }
    }
    
    if( 'exDelForum' == $cmd )
    {
        $forumSettingList = get_forum_settings( $forumId );
    
        if( is_null( $forumSettingList['idGroup'] ) )
        {
            if( delete_forum( $forumId ) )
            {
                $dialogBox->success( get_lang( 'Forum deleted' ) );
            }
            else
            {
                $dialogBox->error( get_lang( 'Unable to delete Forum' ) );
            }
        }
        else
        {
            $dialogBox->error( get_lang( 'You can\'t remove a group forum. You have to remove the group first' ) );
        }
    }
    if( 'exEmptyForum' == $cmd )
    {
        if( delete_all_post_in_forum( $forumId ) )
        {
            $dialogBox->success( get_lang( 'Forum emptied' ) );
        }
        else
        {
            $dialogBox->error( get_lang( 'Unable to empty forum' ) );
        }
    }
    if( 'exMvUpCat' == $cmd )
    {
        move_up_category( $catId );
    }
    
    if( 'exMvDownCat' == $cmd )
    {
        move_down_category( $catId );
    }
    
    if( 'exMvUpForum' == $cmd )
    {
        move_up_forum( $forumId );
    }
    
    if( 'exMvDownForum' == $cmd )
    {
        move_down_forum( $forumId );
    }
}
//end of admin commands

//load category and forum lists
$categories       = get_category_list();
$total_categories = count( $categories );

$forum_list = get_forum_list();

if( claro_is_user_authenticated() )
{
    $userGroupList  = get_user_group_list( claro_get_current_user_id() );
    $userGroupList  = array_keys( $userGroupList );
    $tutorGroupList = get_tutor_group_list( claro_get_current_user_id() );
}
else
{
    $userGroupList = array();
    $tutorGroupList = array();
}
    
//add javascript control for "dangerous" commands (delete-empty)
$htmlHeadXtra[] =
    "<script type=\"text/javascript\">
    function confirm_delete(name)
    {
       if(confirm('". clean_str_for_javascript( get_lang( 'Are you sure to delete' ) ) . " ' + name + ' ?'))
       {return true;}
       else
       {return false;}
    }
    
    function confirm_empty(name)
    {
       if(confirm('". clean_str_for_javascript( get_lang( 'Delete all messages of' ) ) . " ' + name + ' ?'))
       {return true;}
       else
       {return false;}
    }
    </script>";

//prepare display

$nameTools = get_lang( 'Forums' );

$pagetype  = 'index';

$helpUrl = $is_allowedToEdit ? get_help_page_url('blockForumsHelp','CLFRM') : null;

$toolList = disp_forum_toolbar_array( $pagetype, 0, 0, 0 );

$out = '';
$out .= claro_html_tool_title( $nameTools, $helpUrl, $toolList);
$out .= disp_search_box();
$out .= $dialogBox->render();

// Forum toolbar
$displayList = array();

foreach( $forum_list as $this_forum )
{
    //temporary fix for 1.9 releases : avoids change in database definition (using unused 'forum_type' field)
    //TODO : use a specific enum field (field name: anonymity) in bb_forum table
    switch( $this_forum['forum_type'] )
    {
        case 0 : $this_forum['anonymity'] = 'forbidden'; break;
        case 1 : $this_forum['anonymity'] = 'allowed'; break;
        case 2 : $this_forum['anonymity'] = 'default'; break;
        default : $this_forum['anonymity'] = 'forbidden'; break;
    }

    // Visit only my group forum if not admin or tutor.
    // If tutor, see all groups but indicate my groups.
    // Group Category == 1
    
    $displayList[] = $this_forum;
}
        
try
{
    $display = new ModuleTemplate( 'CLFRM' , 'forum_index.tpl.php' );
    $display->assign( 'categoryList', $categories );
    $display->assign( 'forumList', $forum_list );
    $display->assign( 'is_allowedToEdit', $is_allowedToEdit );
    $display->assign( 'claro_notifier', $claro_notifier );
    
    $out .= $display->render();
}
catch( Exception $ex )
{
    if( claro_debug_mode() )
    {
        $dialogBox->error( '<pre>' . $ex->__toString() . '</pre>' );
    }
    else
    {
        $dialogBox->error( $ex->getMessage() );
    }
    $out .= $dialogBox->render();
}

ClaroBreadCrumbs::getInstance()->setCurrent( $nameTools, 'index.php' );

$claroline->display->body->appendContent( $out );

echo $claroline->display->render();
