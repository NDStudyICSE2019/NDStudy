<?php // $Id: insertMyExercise.php 14314 2012-11-07 09:09:19Z zefredz $

/**
 * CLAROLINE
 *
 * @version     1.11 $Revision: 14314 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Piraux Sebastien <pir@cerdecam.be>
 * @author      Lederer Guillaume <led@cerdecam.be>
 * @package     CLLNP
 */

/*======================================
       CLAROLINE MAIN
 ======================================*/

$tlabelReq = 'CLLNP';
require '../inc/claro_init_global.inc.php';
$msgList = array();

// main page
$is_allowedToEdit = claro_is_allowed_to_edit();

if (! claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form(true);

if (! $is_allowedToEdit ) claro_die(get_lang('Not allowed'));

ClaroBreadCrumbs::getInstance()->prepend(
    get_lang('Learning path'), 
    Url::Contextualize(get_module_url('CLLNP') . '/learningPathAdmin.php') 
);

ClaroBreadCrumbs::getInstance()->prepend( 
    get_lang('Learning path list'), 
    Url::Contextualize(get_module_url('CLLNP') . '/learningPathList.php') 
);

$nameTools = get_lang('Add an exercise');

// Command list
$cmdList = array();

$cmdList[] = array(
    'img' => 'back',
    'name' => get_lang('Back to learning path administration'),
    'url' => claro_htmlspecialchars(Url::Contextualize('learningPathAdmin.php'))
);

$out = '';

require_once get_path('incRepositorySys') . '/lib/fileDisplay.lib.php';

// tables names
$tbl_cdb_names = claro_sql_get_course_tbl();

$TABLELEARNPATH         = $tbl_cdb_names['lp_learnPath'];
$TABLEMODULE            = $tbl_cdb_names['lp_module'];
$TABLELEARNPATHMODULE   = $tbl_cdb_names['lp_rel_learnPath_module'];
$TABLEASSET             = $tbl_cdb_names['lp_asset'];
$TABLEUSERMODULEPROGRESS= $tbl_cdb_names['lp_user_module_progress'];

// exercises
$tbl_quiz_exercise = $tbl_cdb_names['qwz_exercise'];

if (!isset($dialogBox)) $dialogBox = "";

//lib of this tool
require_once(get_path('incRepositorySys')."/lib/learnPath.lib.inc.php");

// $_SESSION
if ( !isset($_SESSION['path_id']) )
{
      die ("<center> Not allowed ! (path_id not set :@ )</center>");
}

/*======================================
       CLAROLINE MAIN
  ======================================*/

// Display title
$out .= claro_html_tool_title($nameTools, null, $cmdList);

// see checked exercises to add

$sql = "SELECT `id`, `title`
        FROM `".$tbl_quiz_exercise."`";
$exerciseList = claro_sql_query_fetch_all($sql);

// for each exercise checked, try to add it to the learning path.

foreach( $exerciseList as $exercise )
{
    if (isset($_REQUEST['insertExercise']) && isset($_REQUEST['check_'.$exercise['id']]) )  //add
    {
        // check if a module of this course already used the same exercise
        $sql = "SELECT M.`module_id`
                FROM `".$TABLEMODULE."` AS M, `".$TABLEASSET."` AS A
                WHERE A.`module_id` = M.`module_id`
                  AND A.`path` LIKE '". (int) $exercise['id']."'
                  AND M.`contentType` = '".CTEXERCISE_."'";


        $existingModule = claro_sql_query_get_single_row($sql);

        // no module exists using this exercise
        if( !$existingModule )
        {
            // create new module
            $sql = "INSERT INTO `".$TABLEMODULE."`
                    (`name` , `comment`, `contentType`, `launch_data`)
                    VALUES ('".claro_sql_escape($exercise['title'])."' , '".claro_sql_escape(get_block('blockDefaultModuleComment'))."', '".CTEXERCISE_."', '')";

            $moduleId = claro_sql_query_insert_id($sql);


            // create new asset
            $sql = "INSERT INTO `".$TABLEASSET."`
                    (`path` , `module_id` , `comment`)
                    VALUES ('". (int)$exercise['id']."', ". (int)$moduleId ." , '')";

            $assetId = claro_sql_query_insert_id($sql);

            // update start asset id in module
            $sql = "UPDATE `".$TABLEMODULE."`
                       SET `startAsset_id` = ". (int)$assetId."
                     WHERE `module_id` = ". (int)$moduleId;

            claro_sql_query($sql);

            // determine the default order of this Learning path
            $sql = "SELECT MAX(`rank`)
                    FROM `".$TABLELEARNPATHMODULE."`";

            $orderMax = claro_sql_query_get_single_value($sql);

            $order = $orderMax + 1;

            // finally : insert in learning path
            $sql = "INSERT INTO `".$TABLELEARNPATHMODULE."`
                    (`learnPath_id`, `module_id`, `specificComment`, `rank`, `lock`)
                    VALUES ('". (int)$_SESSION['path_id']."', '".(int)$moduleId."','".claro_sql_escape(get_block('blockDefaultModuleAddedComment'))."', ".$order.",'OPEN')";
            claro_sql_query($sql);

            $msgList['info'][] = get_lang("%moduleName has been added as module", array('%moduleName' => $exercise['title'])).'<br />' . "\n";
        }
        else    // exercise is already used as a module in another learning path , so reuse its reference
        {
            // check if this is this LP that used this exercise as a module
            $sql = "SELECT COUNT(*)
                      FROM `".$TABLELEARNPATHMODULE."` AS LPM,
                           `".$TABLEMODULE."` AS M,
                           `".$TABLEASSET."` AS A
                     WHERE M.`module_id` =  LPM.`module_id`
                       AND M.`startAsset_id` = A.`asset_id`
                       AND A.`path` = ". (int)$exercise['id']."
                       AND LPM.`learnPath_id` = ". (int)$_SESSION['path_id'];

            $num = claro_sql_query_get_single_value($sql);

            if( $num == 0 )     // used in another LP but not in this one, so reuse the module id reference instead of creating a new one
            {
                // determine the default order of this Learning path
                $sql = "SELECT MAX(`rank`)
                        FROM `".$TABLELEARNPATHMODULE."`";

                $orderMax = claro_sql_query_get_single_value($sql);

                $order = $orderMax + 1;

                // finally : insert in learning path
                $sql = "INSERT INTO `".$TABLELEARNPATHMODULE."`
                        (`learnPath_id`, `module_id`, `specificComment`, `rank`, `lock`)
                        VALUES (".(int)$_SESSION['path_id'].", ".(int)$existingModule['module_id'].",'".claro_sql_escape(get_block('blockDefaultModuleAddedComment'))."', ".$order.", 'OPEN')";
                $query = claro_sql_query($sql);

                $msgList['info'][] = get_lang("%moduleName has been added as module", array('%moduleName' => $exercise['title'])).'<br />' . "\n";
            }
            else
            {
                $msgList['info'][] = get_lang("%moduleName is already used as a module in this learning path", array('%moduleName' => $exercise['title'])).'<br />' . "\n";
            }
        }
    }
} //end while

//STEP ONE : display form to add an exercise
$out .= claro_html_msg_list($msgList);
$out .= display_my_exercises($dialogBox);

//STEP TWO : display learning path content
$out .= claro_html_tool_title(get_lang('Learning path content'));

// display list of modules used by this learning path
$out .= display_path_content();

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
