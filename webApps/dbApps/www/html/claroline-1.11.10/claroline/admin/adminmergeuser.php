<?php // $Id: adminmergeuser.php 13945 2012-01-18 14:05:54Z zefredz $

/**
 * CLAROLINE
 *
 * Merge two user accounts.
 *
 * @version     $Revision: 13945 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      see 'credits' file
 * @package     ADMIN
 * @since       1.9
 */

require '../inc/claro_init_global.inc.php';

// Security check

if ( ! claro_is_user_authenticated() )
{
    claro_disp_auth_form();
}

if ( ! claro_is_platform_admin() )
{
    claro_die(get_lang('Not allowed'));
}

FromKernel::uses( 'utils/input.lib', 'utils/validator.lib', 'display/dialogBox.lib', 'admin/mergeuser.lib', 'user.lib' );

try
{
    $dialogBox = new DialogBox;
    $userInput = Claro_UserInput::getInstance();
    
    $userInput->setValidator( 'cmd', new Claro_Validator_AllowedList( array( 'rqMerge', 'chkMerge', 'exMerge' ) ) );
    
    $cmd = $userInput->get( 'cmd', 'rqMerge' );
    
    if ( $cmd == 'rqMerge' )
    {
        $dialogBox->warning( get_lang('Merging user accounts is not a reversible operation so be careful !') );
        
        $form = '<form action="'.$_SERVER['PHP_SELF'].'?cmd=chkMerge" method="post">' . "\n"
            . '<input type="hidden" name="claroFormId" value="' . uniqid('') . '" />' . "\n"
            . '<fieldset>'
            . '<legend>' . get_lang('Accounts to merge') . '</legend>'
            . '<label for="uidToRemove">'.get_lang('Id of the user to remove') . ' : </label><input type="text" name="uidToRemove" id="uidToRemove" value="" /><br />' . "\n"
            . '<label for="uidToKeep">'.get_lang('Id of the user to keep') . ' : </label><input type="text" name="uidToKeep" id="uidToKeep" value="" /><br />' . "\n"
            . '</fieldset>'
            . '<br />'
            . '<input type="submit" name="merge" value="' . get_lang('Merge') . '" />' . "\n"
            . '</form>'
            ;
            
        $dialogBox->form( $form );
    }
    
    if ( $cmd == 'chkMerge' )
    {
        $uidToKeep = $userInput->getMandatory('uidToKeep');
        $uidToRemove = $userInput->getMandatory('uidToRemove');
        
        if ( $uidToKeep == $uidToRemove )
        {
            throw new Exception( get_lang('Cannot merge one user account with itself') );
        }
        
        if ( ! user_get_properties( $uidToKeep ) )
        {
            throw new Exception( get_lang('User to keep not found') );
        }
        
        if ( ! user_get_properties( $uidToRemove ) )
        {
            throw new Exception( get_lang('User to remove not found') );
        }
        
        $question = '<p>'
            . get_lang('Merging users will alter the user data and cannot be undone. Are you sure to want to continue ?')
            . '</p>' . "\n"
            . '<form action="'.$_SERVER['PHP_SELF'].'?cmd=exMerge" method="post">' . "\n"
            // . '<input type="hidden" name="claroFormId" value="' . uniqid('') . '" />' . "\n"
            . '<input type="hidden" name="uidToRemove" id="uidToRemove" value="'.$uidToRemove.'" />' . "\n"
            . '<input type="hidden" name="uidToKeep" id="uidToKeep" value="'.$uidToKeep.'" />' . "\n"
            . '<input type="submit" name="continue" value="' . get_lang('Yes') . '" />' . "\n"
            . '<a href="'.$_SERVER['PHP_SELF'].'"><input type="button" name="cancel" value="' . get_lang('No') . '" /></a>' . "\n"
            . '</form>'
            ;
            
        $dialogBox->question( $question );
    }
    
    if ( $cmd == 'exMerge' )
    {
        $uidToKeep = $userInput->getMandatory('uidToKeep');
        $uidToRemove = $userInput->getMandatory('uidToRemove');
        
        if ( $uidToKeep == $uidToRemove )
        {
            throw new Exception( get_lang('Cannot merge one user account with itself') );
        }
        
        if ( ! user_get_properties( $uidToKeep ) )
        {
            throw new Exception( get_lang('User to keep not found') );
        }
        
        if ( ! user_get_properties( $uidToRemove ) )
        {
            throw new Exception( get_lang('User to remove not found') );
        }
        
        $mergeUser = new MergeUser;
        $mergeUser->merge( $uidToRemove, $uidToKeep );
        
        if ( $mergeUser->hasError() )
        {
            $dialogBox->error( get_lang('Some errors have occured while merging those user account, check the log table in the platform main database for more details') );
        }
        else
        {
            $dialogBox->success( get_lang('User accounts merged') );
        }
    }
}
catch( Exception $e )
{
    $dialogBox->error( get_lang('Cannot perform the requested action')
        . ' : <br />' . $e->getMessage() );
    pushClaroMessage('<pre>'.$e->__toString().'</pre>');
}

ClaroBreadCrumbs::getInstance()->prepend(get_lang('Administration'), get_path('rootAdminWeb'));
ClaroBreadCrumbs::getInstance()->setCurrent(get_lang('Merge user accounts'), php_self());

ClaroBody::getInstance()->appendContent(claro_html_tool_title(get_lang('Merge user accounts')));
ClaroBody::getInstance()->appendContent($dialogBox->render());

echo Claroline::getInstance()->display->render();