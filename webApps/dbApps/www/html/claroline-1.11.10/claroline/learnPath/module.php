<?php // $Id: module.php 14400 2013-02-15 14:05:59Z kitan1982 $

/**
 * CLAROLINE 1.11
 *
 * @version     $Revision: 14400 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Piraux Sebastien <pir@cerdecam.be>
 * @author      Lederer Guillaume <led@cerdecam.be>
 * @package     CLLNP
 * @since       1.8
 */

/*======================================
       CLAROLINE MAIN
  ======================================*/

$tlabelReq = 'CLLNP';

require '../inc/claro_init_global.inc.php';

if ( ! claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form(true);

// $_SESSION
// path_id
if ( isset($_GET['path_id']) && $_GET['path_id'] != '' )
{
    $_SESSION['path_id'] = $_GET['path_id'];
}
// module_id
if ( isset($_GET['module_id']) && $_GET['module_id'] != '')
{
    $_SESSION['module_id'] = $_GET['module_id'];
}

// use viewMode
claro_set_display_mode_available(true);

$is_allowedToEdit = claro_is_allowed_to_edit();    // as teacher
//-- breadcrumbs

if ( $is_allowedToEdit )
{
    ClaroBreadCrumbs::getInstance()->prepend( 
        get_lang('Learning path'), 
        Url::Contextualize(get_module_url('CLLNP') . '/learningPathAdmin.php') 
    );
}
else
{
    ClaroBreadCrumbs::getInstance()->prepend( 
        get_lang('Learning path'), 
        Url::Contextualize(get_module_url('CLLNP') . '/learningPath.php') 
    );
}

ClaroBreadCrumbs::getInstance()->prepend( 
    get_lang('Learning path list'), 
    Url::Contextualize(get_module_url('CLLNP') . '/learningPathList.php') 
);

$nameTools = get_lang('Module');

// tables names
$tbl_cdb_names = claro_sql_get_course_tbl();

$TABLELEARNPATH         = $tbl_cdb_names['lp_learnPath'];
$TABLEMODULE            = $tbl_cdb_names['lp_module'];
$TABLELEARNPATHMODULE   = $tbl_cdb_names['lp_rel_learnPath_module'];
$TABLEASSET             = $tbl_cdb_names['lp_asset'];
$TABLEUSERMODULEPROGRESS= $tbl_cdb_names['lp_user_module_progress'];

// exercises
$tbl_quiz_exercise = $tbl_cdb_names['qwz_exercise'];

$dbTable = $TABLEASSET; // for old functions of document tool

//lib of this tool
require_once(get_path('incRepositorySys')."/lib/learnPath.lib.inc.php");

require_once(get_path('incRepositorySys')."/lib/fileDisplay.lib.php");
require_once(get_path('incRepositorySys')."/lib/fileManage.lib.php");
require_once(get_path('incRepositorySys')."/lib/fileUpload.lib.php");

// clean exercise session vars
unset($_SESSION['serializedExercise']);
unset($_SESSION['serializedQuestionList']);
unset($_SESSION['exeStartTime']);

if(!empty($_REQUEST['copyFrom']) && $is_allowedToEdit)
{
    $_SESSION['returnToTrackingUserId'] = (int)$_GET['copyFrom'];
    
    $copyError = false;
    //we could simply copy the requested module progression...
    //but since we can navigate between modules while completing a module,
    //we have to copy the whole learning path progression.
    if(!copyLearnPathProgression((int)$_SESSION['returnToTrackingUserId'], (int)claro_get_current_user_id(),  (int)$_SESSION['path_id']))
    {
    	$copyError = true;
    }
    
    $dialogBox = new DialogBox();
    if($copyError)
    {
        $dialogBox->error(get_lang('An error occured while accessing student module'));
        $claroline->display->body->appendContent($dialogBox->render());
        echo $claroline->display->render();
        exit();
    }
    else
    {
        $user_data = user_get_properties((int)$_SESSION['returnToTrackingUserId']);
        $dialogBox->success(get_lang('Currently viewing module of ') . $user_data['firstname'] . ' ' . $user_data['lastname']);
        unset($user_data);
    }

	unset($copyError);
}
else
{
    unset($_SESSION['returnToTrackingUserId']);
}

// main page
// FIRST WE SEE IF USER MUST SKIP THE PRESENTATION PAGE OR NOT
// triggers are : if there is no introdution text or no user module progression statistics yet and user is not admin,
// then there is nothing to show and we must enter in the module without displaying this page.

/*
 *  GET INFOS ABOUT MODULE and LEARNPATH_MODULE
 */

// check in the DB if there is a comment set for this module in general

$sql = "SELECT `comment`, `startAsset_id`, `contentType`
        FROM `".$TABLEMODULE."`
        WHERE `module_id` = ". (int)$_SESSION['module_id'];

$module = claro_sql_query_get_single_row($sql);

if( empty($module['comment']) || $module['comment'] == get_block('blockDefaultModuleComment') )
{
      $noModuleComment = true;
}
else
{
   $noModuleComment = false;
}


if( $module['startAsset_id'] == 0 )
{
    $noStartAsset = true;
}
else
{
    $noStartAsset = false;
}


// check if there is a specific comment for this module in this path
$sql = "SELECT `specificComment`
        FROM `".$TABLELEARNPATHMODULE."`
        WHERE `module_id` = ". (int)$_SESSION['module_id'];

$learnpath_module = claro_sql_query_get_single_row($sql);

if( empty($learnpath_module['specificComment']) || $learnpath_module['specificComment'] == get_block('blockDefaultModuleAddedComment') )
{
    $noModuleSpecificComment = true;
}
else
{
    $noModuleSpecificComment = false;
}

// check in DB if user has already browsed this module

$sql = "SELECT `contentType`,
                `total_time`,
                `session_time`,
                `scoreMax`,
                `raw`,
                `lesson_status`
        FROM `".$TABLEUSERMODULEPROGRESS."` AS UMP,
             `".$TABLELEARNPATHMODULE."` AS LPM,
             `".$TABLEMODULE."` AS M
        WHERE UMP.`user_id` = '" . (int) claro_get_current_user_id() . "'
          AND UMP.`learnPath_module_id` = LPM.`learnPath_module_id`
          AND LPM.`learnPath_id` = ".(int)$_SESSION['path_id']."
          AND LPM.`module_id` = ". (int)$_SESSION['module_id']."
          AND LPM.`module_id` = M.`module_id`
             ";
$resultBrowsed = claro_sql_query_get_single_row($sql);

// redirect user to the path browser if needed
if( !$is_allowedToEdit
    && ( !is_array($resultBrowsed) || !$resultBrowsed || count($resultBrowsed) <= 0 )
    && $noModuleComment
    && $noModuleSpecificComment
    && !$noStartAsset
    )
{
    header("Location: ".Url::Contextualize("./navigation/viewer.php"));
    exit();
}

// Back button
if(!empty($_SESSION['returnToTrackingUserId']))
{
    $pathBack = Url::Contextualize(
        get_path('clarolineRepositoryWeb') 
        . 'tracking/lp_modules_details.php?' 
        . 'uInfo='. (int)$_SESSION['returnToTrackingUserId'] 
        . '&path_id=' . (int)$_SESSION['path_id'] );
}
elseif ($is_allowedToEdit)
{
    $pathBack = Url::Contextualize("./learningPathAdmin.php");
}
else
{
    $pathBack = Url::Contextualize("./learningPath.php");
}

// Command list
$cmdList = array();

$cmdList[] = array(
    'img' => 'back',
    'name' => get_lang('Back to module list'),
    'url' => $pathBack);

// Display
$out = '';

if(!empty($dialogBox))
{
    $out .= $dialogBox->render();
}

$out .= claro_html_tool_title(get_lang('Module edition'), null, $cmdList);

//####################################################################################\\
//################################## MODULE NAME BOX #################################\\
//####################################################################################\\

$cmd = ( isset($_REQUEST['cmd']) )? $_REQUEST['cmd'] : '';

if ( $cmd == "updateName" )
{
    $out .= nameBox(MODULE_, UPDATE_);
}
else
{
    $out .= nameBox(MODULE_, DISPLAY_);
}

if($module['contentType'] != CTLABEL_ )
{
    //####################################################################################\\
    //############################### MODULE COMMENT BOX #################################\\
    //####################################################################################\\
    //#### COMMENT #### courseAdmin cannot modify this if this is a imported module ####\\
    // this the comment of the module in ALL learning paths
    if ( $cmd == "updatecomment" )
    {
        $out .= commentBox(MODULE_, UPDATE_);
    }
    elseif ($cmd == "delcomment" )
    {
        $out .= commentBox(MODULE_, DELETE_);
    }
    else
    {
        $out .= commentBox(MODULE_, DISPLAY_);
    }
    
    //#### ADDED COMMENT #### courseAdmin can always modify this ####\\
    // this is a comment for THIS module in THIS learning path
    if ( $cmd == "updatespecificComment" )
    {
        $out .= commentBox(LEARNINGPATHMODULE_, UPDATE_);
    }
    elseif ($cmd == "delspecificComment" )
    {
        $out .= commentBox(LEARNINGPATHMODULE_, DELETE_);
    }
    else
    {
        $out .= commentBox(LEARNINGPATHMODULE_, DISPLAY_);
    }
} //  if($module['contentType'] != CTLABEL_ )

//#####################################################################################################\\
//################################## DOCUMENT MODULE DEFAULT TIME BOX #################################\\
//#####################################################################################################\\
if( $is_allowedToEdit && $module['contentType'] == CTDOCUMENT_ )
{
    if ( $cmd == "updateDefaultTime" )
    {
        $out .= documentDefaultTimeBox( UPDATE_ );
    }
    else
    {
        $out .= documentDefaultTimeBox( DISPLAY_ );
    }
}

//####################################################################################\\
//############################ PROGRESS  AND  START LINK #############################\\
//####################################################################################\\

/* Display PROGRESS */

if($module['contentType'] != CTLABEL_) //
{
    if( $resultBrowsed && count($resultBrowsed) > 0 && $module['contentType'] != CTLABEL_)
    {
        $contentType_img = selectImage($resultBrowsed['contentType']);
        $contentType_alt = selectAlt($resultBrowsed['contentType']);

        if ($resultBrowsed['contentType']== CTSCORM_   ) { $contentDescType = get_lang('SCORM 1.2 conformable content');    }
        if ($resultBrowsed['contentType']== CTEXERCISE_ ) { $contentDescType = get_lang('Exercises'); }
        if ($resultBrowsed['contentType']== CTDOCUMENT_ ) { $contentDescType = get_lang('Document'); }

        $out .= '<b>'.get_lang('Your progression in this module').'</b><br /><br />'."\n\n"
            .'<table align="center" class="claroTable" border="0" cellspacing="2">'."\n"
            .'<thead>'."\n"
            .'<tr class="headerX">'."\n"
            .'<th>'.get_lang('Information').'</th>'."\n"
            .'<th>'.get_lang('Values').'</th>'."\n"
            .'</tr>'."\n"
            .'</thead>'."\n\n"
            .'<tbody>'."\n\n";

        //display type of the module
        $out .= '<tr>'."\n"
            .'<td>'.get_lang('Module type').'</td>'."\n"
            .'<td><img src="' . $contentType_img . '" alt="'.$contentType_alt.'" /> '.$contentDescType.'</td>'."\n"
            .'</tr>'."\n\n";

        //display total time already spent in the module
        $out .= '<tr>'."\n"
            .'<td>'.get_lang('Total time').'</td>'."\n"
            .'<td>'.$resultBrowsed['total_time'].'</td>'."\n"
            .'</tr>'."\n\n";

        //display time passed in last session
        $out .= '<tr>'."\n"
            .'<td>'.get_lang('Last session time').'</td>'."\n"
            .'<td>'.$resultBrowsed['session_time'].'</td>'."\n"
            .'</tr>'."\n\n";

        //display user best score
        if ($resultBrowsed['scoreMax'] > 0)
        {
            $raw = round($resultBrowsed['raw']/$resultBrowsed['scoreMax']*100);
        }
        else
        {
            $raw = 0;
        }

        $raw = max($raw, 0);

        if (($resultBrowsed['contentType'] == CTSCORM_ ) && ($resultBrowsed['scoreMax'] <= 0)
            &&  (  ( ($resultBrowsed['lesson_status'] == "COMPLETED") || ($resultBrowsed['lesson_status'] == "PASSED") ) || ($resultBrowsed['raw'] != -1) ) )
        {
            $raw = 100;
        }

        // no sens to display a score in case of a document module
        if (($resultBrowsed['contentType'] != CTDOCUMENT_))
        {
            $out .= '<tr>'."\n"
                .'<td>'.get_lang('Your best performance').'</td>'."\n"
                .'<td>'.claro_html_progress_bar($raw, 1).' '.$raw.'%</td>'."\n"
                .'</tr>'."\n\n";
        }

        //display lesson status

        // document are just browsed or not, but not completed or passed...

        if (($resultBrowsed['contentType']== CTDOCUMENT_))
        {
            if ($resultBrowsed['lesson_status']=="COMPLETED")
            {
                $statusToDisplay = get_lang('Already browsed');
            }
            else
            {
                $statusToDisplay = get_lang('Never browsed');
            }
        }
        else
        {
            $statusToDisplay = $resultBrowsed['lesson_status'];
        }
        $out .= '<tr>'."\n"
            .'<td>'.get_lang('Module status').'</td>'."\n"
            .'<td>'.$statusToDisplay.'</td>'."\n"
            .'</tr>'."\n\n"
            .'</tbody>'."\n\n"
            .'</table>'."\n\n";

    } //end display stats

    /* START */
    // check if module.startAssed_id is set and if an asset has the corresponding asset_id
    // asset_id exists ?  for the good module  ?
    $sql = "SELECT `asset_id`
              FROM `".$TABLEASSET."`
             WHERE `asset_id` = ". (int)$module['startAsset_id']."
               AND `module_id` = ". (int)$_SESSION['module_id'];

    $asset = claro_sql_query_get_single_row($sql);

    if( $module['startAsset_id'] != "" && $asset['asset_id'] == $module['startAsset_id'] )
    {

        $out .= '<center>'."\n"
              . '<form action="./navigation/viewer.php" method="post">' . "\n"
              . claro_form_relay_context()
              . '<input type="submit" value="' . get_lang('Start Module') . '" />'."\n"
              . '</form>' . "\n"
              . '</center>' . "\n\n"
              ;
    }
    else
    {
        $out .= '<p><center>'.get_lang('There is no start asset defined for this module.').'</center></p>'."\n";
    }
}// end if($module['contentType'] != CTLABEL_)
// if module is a label, only allow to change its name.

//####################################################################################\\
//################################# ADMIN DISPLAY ####################################\\
//####################################################################################\\

if( $is_allowedToEdit ) // for teacher only
{
    switch ($module['contentType'])
    {
        case CTDOCUMENT_ :
            require("./include/document.inc.php");
            $out .= lp_display_document($TABLEASSET);
            break;
        case CTEXERCISE_ :
            require("./include/exercise.inc.php");
            $out .= lp_display_exercise( $cmd, $TABLELEARNPATHMODULE, $TABLEMODULE, $TABLEASSET, $tbl_quiz_exercise);
            break;
        case CTSCORM_ :
            require("./include/scorm.inc.php");
            $out .= lp_display_scorm( $TABLELEARNPATHMODULE );
            break;
        case CTCLARODOC_ :
            break;
        case CTLABEL_ :
            break;
    }
} // if ($is_allowedToEdit)

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
