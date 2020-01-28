<?php // $Id: exercise.inc.php 14314 2012-11-07 09:09:19Z zefredz $
if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * @version     $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Piraux Sebastien <pir@cerdecam.be>
 * @author      Lederer Guillaume <led@cerdecam.be>
 * @package     CLLNP
 * @since       1.8
 */

function lp_display_exercise($cmd, $TABLELEARNPATHMODULE, $TABLEMODULE, $TABLEASSET, $tbl_quiz_exercise)
{
    $out = '';
    
    if( isset($cmd) && $cmd = "raw" )
    {
        // change raw if value is a number between 0 and 100
        if (isset($_POST['newRaw']) && is_num($_POST['newRaw']) && $_POST['newRaw'] <= 100 && $_POST['newRaw'] >= 0 )
        {
            $sql = "UPDATE `".$TABLELEARNPATHMODULE."`
                    SET `raw_to_pass` = ". (int)$_POST['newRaw']."
                    WHERE `module_id` = ". (int)$_SESSION['module_id']."
                    AND `learnPath_id` = ". (int)$_SESSION['path_id'];
            claro_sql_query($sql);
    
            $dialogBoxContent = get_lang('Minimum raw to pass has been changed');
        }
    }
    
    
    $out .= '<hr noshade="noshade" size="1" />';
    
    //####################################################################################\\
    //############################### DIALOG BOX SECTION #################################\\
    //####################################################################################\\
    if( !empty($dialogBoxContent) )
    {
        $dialogBox = new DialogBox();
        $dialogBox->success( $dialogBoxContent );
        $out .= $dialogBox->render();
    }
    
    // form to change raw needed to pass the exercise
    $sql = "SELECT `lock`, `raw_to_pass`
            FROM `".$TABLELEARNPATHMODULE."` AS LPM
           WHERE LPM.`module_id` = ". (int)$_SESSION['module_id']."
             AND LPM.`learnPath_id` = ". (int)$_SESSION['path_id'];
    
    $learningPath_module = claro_sql_query_get_single_row($sql);
    
    // if this module blocks the user if he doesn't complete
    if( isset($learningPath_module['lock'])
        && $learningPath_module['lock'] == 'CLOSE'
        && isset($learningPath_module['raw_to_pass']) )
    {
        $out .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'">'."\n"
        .    claro_form_relay_context()
            .'<label for="newRaw">'.get_lang('Change minimum raw mark to pass this module (percentage) :').' </label>'."\n"
            .'<input type="text" value="'.claro_htmlspecialchars( $learningPath_module['raw_to_pass'] ).'" name="newRaw" id="newRaw" size="3" maxlength="3" /> % '."\n"
            .'<input type="hidden" name="cmd" value="raw" />'."\n"
            .'<input type="submit" value="'.get_lang('Ok').'" />'."\n"
            .'</form>'."\n\n";
    }
    
    // display current exercise info and change comment link
    $sql = "SELECT `E`.`id` AS `exerciseId`, `M`.`name`
            FROM `".$TABLEMODULE."` AS `M`,
                 `".$TABLEASSET."`  AS `A`,
                 `".$tbl_quiz_exercise."` AS `E`
           WHERE `A`.`module_id` = M.`module_id`
             AND `M`.`module_id` = ". (int) $_SESSION['module_id']."
             AND `E`.`id` = `A`.`path`";
    
    $module = claro_sql_query_get_single_row($sql);
    if( $module )
    {
        $out .= "\n\n".'<h4>'.get_lang('Exercise in module').' :</h4>'."\n"
            .'<p>'."\n"
            .claro_htmlspecialchars($module['name'])
            .'<a href="../exercise/admin/edit_exercise.php?exId='.$module['exerciseId'].'">'
            .'<img src="' . get_icon_url('edit') . '" alt="'.get_lang('Modify').'" />'
            .'</a>'."\n"
            .'</p>'."\n";
    } // else sql error, do nothing except in debug mode, where claro_sql_query_fetch_all will show the error
    
    return $out;
}

