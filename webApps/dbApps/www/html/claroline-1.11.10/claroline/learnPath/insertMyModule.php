<?php // $Id: insertMyModule.php 14314 2012-11-07 09:09:19Z zefredz $

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

$is_allowedToEdit = claro_is_allowed_to_edit();

if ( ! claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form(true);

if ( ! $is_allowedToEdit ) claro_die(get_lang('Not allowed'));

ClaroBreadCrumbs::getInstance()->prepend( 
    get_lang('Learning path'), 
    Url::Contextualize(get_module_url('CLLNP') . '/learningPathAdmin.php')
);

ClaroBreadCrumbs::getInstance()->prepend( 
    get_lang('Learning path list'), 
    Url::Contextualize(get_module_url('CLLNP') . '/learningPathList.php') 
);

$nameTools = get_lang('Add a module of this course');

// Command list
$cmdList = array();

$cmdList[] = array(
    'img' => 'back',
    'name' => get_lang('Back to learning path administration'),
    'url' => claro_htmlspecialchars(Url::Contextualize('learningPathAdmin.php'))
);

$out = '';

// tables names

$TABLELEARNPATH         = claro_get_current_course_data('dbNameGlu') . "lp_learnPath";
$TABLEMODULE            = claro_get_current_course_data('dbNameGlu') . "lp_module";
$TABLELEARNPATHMODULE   = claro_get_current_course_data('dbNameGlu') . "lp_rel_learnPath_module";
$TABLEASSET             = claro_get_current_course_data('dbNameGlu') . "lp_asset";
$TABLEUSERMODULEPROGRESS= claro_get_current_course_data('dbNameGlu') . "lp_user_module_progress";

//lib of this tool
require_once(get_path('incRepositorySys')."/lib/learnPath.lib.inc.php");

//lib of document tool
require_once(get_path('incRepositorySys')."/lib/fileDisplay.lib.php");

// $_SESSION
if ( !isset($_SESSION['path_id']) )
{
    die ("<center> Not allowed ! (path_id not set :@ )</center>");
}

/*======================================
       CLAROLINE MAIN
 ======================================*/

// FUNCTION NEEDED TO BUILD THE QUERY TO SELECT THE MODULES THAT MUST BE AVAILABLE

// 1)  We select first the modules that must not be displayed because
// as they are already in this learning path

function buildRequestModules()
{

 global $TABLELEARNPATHMODULE;
 global $TABLEMODULE;
 global $TABLEASSET;

 $firstSql = "SELECT LPM.`module_id`
              FROM `".$TABLELEARNPATHMODULE."` AS LPM
              WHERE LPM.`learnPath_id` = ". (int)$_SESSION['path_id'];

 $firstResult = claro_sql_query($firstSql);

 // 2) We build the request to get the modules we need

 $sql = "SELECT M.*, A.`path`
         FROM `".$TABLEMODULE."` AS M
           LEFT JOIN `".$TABLEASSET."` AS A ON M.`startAsset_id` = A.`asset_id`
         WHERE M.`contentType` != \"SCORM\"
           AND M.`contentType` != \"LABEL\"";

 while ($list=mysql_fetch_array($firstResult))
 {
    $sql .=" AND M.`module_id` != ". (int)$list['module_id'];
 }

 //$sql .= " AND M.`contentType` != \"".CTSCORM_."\"";

 /** To find which module must displayed we can also proceed  with only one query.
  * But this implies to use some features of MySQL not available in the version 3.23, so we use
  * two differents queries to get the right list.
  * Here is how to proceed with only one

  $query = "SELECT *
             FROM `".$TABLEMODULE."` AS M
             WHERE NOT EXISTS(SELECT * FROM `".$TABLELEARNPATHMODULE."` AS TLPM
             WHERE TLPM.`module_id` = M.`module_id`)";
 */

  return $sql;

}//end function

// display title

$out .= claro_html_tool_title($nameTools, null, $cmdList);

//COMMAND ADD SELECTED MODULE(S):

if (isset($_REQUEST['cmdglobal']) && ($_REQUEST['cmdglobal'] == 'add'))
{

    // select all 'addable' modules of this course for this learning path

    $result = claro_sql_query(buildRequestModules());
    $atLeastOne = FALSE;
    $nb=0;
    while ($list = mysql_fetch_array($result))
    {
        // see if check box was checked
        if (isset($_REQUEST['check_'.$list['module_id']]) && $_REQUEST['check_'.$list['module_id']])
        {
            // find the order place where the module has to be put in the learning path
            $sql = "SELECT MAX(`rank`)
                    FROM `".$TABLELEARNPATHMODULE."`
                    WHERE learnPath_id = " . (int)$_SESSION['path_id'];
            $result2 = claro_sql_query($sql);

            list($orderMax) = mysql_fetch_row($result2);
            $order = $orderMax + 1;

            //create and call the insertquery on the DB to add the checked module to the learning path

            $insertquery="INSERT INTO `".$TABLELEARNPATHMODULE."`
                          (`learnPath_id`, `module_id`, `specificComment`, `rank`, `lock` )
                          VALUES (". (int)$_SESSION['path_id'].", ". (int)$list['module_id'].", '',".$order.", 'OPEN')";
            claro_sql_query($insertquery);

            $atleastOne = TRUE;
            $nb++;
        }
    }
} //end if ADD command

//STEP ONE : display form to add module of the course that are not in this path yet
// this is the same SELECT as "select all 'addable' modules of this course for this learning path"
// **BUT** normally there is less 'addable' modules here than in the first one

$result = claro_sql_query(buildRequestModules());

// Display available modules
$out .= '<form name="addmodule" action="'.$_SERVER['PHP_SELF'].'?cmdglobal=add">'."\n"
      . claro_form_relay_context()."\n"
      . '<table class="claroTable">'."\n"
      . '<thead>'."\n"
      . '<tr align="center" valign="top">'."\n"
      . '<th width="10%">'
      . get_lang('Add module(s)')
      . '</th>'."\n"
      . '<th>'
      . get_lang('Module')
      . '</th>'."\n"
      . '</tr>'."\n"
      . '</thead>'."\n\n"
      . '<tbody>'."\n\n";

$atleastOne = false;

while ($list=mysql_fetch_array($result))
{
    //CHECKBOX, NAME, RENAME, COMMENT
    if($list['contentType'] == CTEXERCISE_ )
        $moduleImg = get_icon_url( 'quiz', 'CLQWZ' );
    else
        $moduleImg = get_icon_url( choose_image(basename($list['path'])) );

    $contentType_alt = selectAlt($list['contentType']);

    $out .= '<tr>'."\n"
          . '<td style="vertical-align:top; text-align: center;">'."\n"
          . '<input type="checkbox" name="check_'.$list['module_id'].'" id="check_'.$list['module_id'].'" />'."\n"
          . '</td>'."\n"
          . '<td align="left">'."\n"
          . '<label for="check_' . $list['module_id'] . '" >'
          . '<img hspace="5" src="' . $moduleImg . '" alt="' . $contentType_alt . '" /> '
          . $list['name']
          . '</label>' . "\n"
          . (($list['comment'] != null) ? '<div class="comment">'.$list['comment'].'</small>'.'</div>'."\n\n" : '')
          . '</td>'."\n"
          . '</tr>'."\n\n";
    
    $atleastOne = true;

}//end while another module to display

if ( !$atleastOne )
{
    $out .= '<tr>'."\n"
        . '<td colspan="2" align="center">'
        . get_lang('All modules of this course are already used in this learning path.')
        . '</td>'."\n"
        . '</tr>'."\n";
}

$out .= '</tbody>'."\n"
      . '</table>'."\n";

// Display button to add selected modules
if ( $atleastOne )
{
    $out .= '<input type="submit" value="'.get_lang('Add selection').'" />'."\n"
        . '<input type="hidden" name="cmdglobal" value="add" />'."\n";
}

$out .= '</form>'
      . '<br /><br />';

//####################################################################################\\
//################################## MODULES LIST ####################################\\
//####################################################################################\\

// display subtitle
$out .= claro_html_tool_title(get_lang('Learning path content'));

// display list of modules used by this learning path
$out .= display_path_content();

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
