<?php // $Id: learningPathAdmin.php 14587 2013-11-08 12:47:41Z zefredz $

/**
 * CLAROLINE 1.11
 *
 * @version     $Revision: 14587 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claroline Team
 * @package     CLLNP
 *
 *  DESCRIPTION:
 *  ***********
 *
 *  This file is available only if is course admin
 *
 *  It allow course admin to :
 *  - change learning path name
 *  - change learning path comment
 *  - links to
 *    - create empty module
 *    - use document as module
 *    - use exercice as module
 *    - re-use a module of the same course
 *    - import (upload) a module
 *    - use a module from another course
 *  - remove modules from learning path (it doesn't delete it ! )
 *  - change locking , visibility, order
 *  - access to config page of modules in this learning path
 */

/*======================================
       CLAROLINE MAIN
  ======================================*/

$tlabelReq = 'CLLNP';
require '../inc/claro_init_global.inc.php';

if ( ! claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form(true);

// Add a few Javascript to the header
$htmlHeadXtra[] =
            "<script>
            function confirmation (txt)
            {
                if (confirm(txt))
                    {return true;}
                else
                    {return false;}
            }
            </script>";

// Manage breadcrumbs
ClaroBreadCrumbs::getInstance()->prepend( 
    get_lang('Learning path list'), 
    Url::Contextualize(get_module_url('CLLNP') . '/learningPathList.php') 
);

$nameTools = get_lang('Learning path');
$_SERVER['QUERY_STRING'] =''; // used for the breadcrumb
                              // when one need to add a parameter after the filename

// use viewMode
claro_set_display_mode_available(true);

// permissions
$is_allowedToEdit = claro_is_allowed_to_edit();

// lib of document tool
require_once get_path('incRepositorySys') . '/lib/fileDisplay.lib.php';

// tables names

// TODO use claro_sql_get_main_tbl()
$TABLELEARNPATH         = claro_get_current_course_data('dbNameGlu') . "lp_learnPath";
$TABLEMODULE            = claro_get_current_course_data('dbNameGlu') . "lp_module";
$TABLELEARNPATHMODULE   = claro_get_current_course_data('dbNameGlu') . "lp_rel_learnPath_module";
$TABLEASSET             = claro_get_current_course_data('dbNameGlu') . "lp_asset";
$TABLEUSERMODULEPROGRESS= claro_get_current_course_data('dbNameGlu') . "lp_user_module_progress";

$dialogBox = new DialogBox();

// lib of this tool
require_once get_path('incRepositorySys') . '/lib/learnPath.lib.inc.php';

// $_SESSION
if ( isset($_GET['path_id']) && $_GET['path_id'] > 0 )
{
      $_SESSION['path_id'] = (int) $_GET['path_id'];
}

// get user out of here if he is not allowed to edit
if ( !$is_allowedToEdit )
{
    if ( isset($_SESSION['path_id']) )
    {
        header("Location: ".Url::Contextualize("./learningPath.php?path_id=".$_SESSION['path_id']));
    }
    else
    {
        header("Location: ".Url::Contextualize("./learningPathList.php"));
    }
    exit();
}

// select details of learning path to display
$sql = "SELECT lp.`learnPath_id`,
        lp.`name`,
        lp.`comment`,
        lp.`lock`,
        lp.`visibility`,
        lp.`rank`
        FROM `".$TABLELEARNPATH."` AS lp
        WHERE lp.`learnPath_id` = ". (int) $_SESSION['path_id'];
$query = claro_sql_query($sql);

$LPDetails = mysql_fetch_array($query);

// parse commands
$cmd = ( isset($_REQUEST['cmd']) ) ? $_REQUEST['cmd'] : '';

switch($cmd)
{
    // REQUEST EDIT
    case "rqEdit" :
        if (isset($_SESSION['path_id']))
        {
            $dialogBox->form( "\n\n"
            . '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">' . "\n"
            . '<fieldset>' . "\n"
            . claro_form_relay_context()
            . '<h4>' . get_lang('Edit this learning path') . '</h4>' . "\n"
            . '<dl>' . "\n"
            . '<dt><label for="newName">' . get_lang('Title') . '</label></dt>' . "\n"
            . '<dd>' . "\n"
            . '<input type="text" name="newName" id="newName" size="50" maxlength="255" value="'.claro_htmlspecialchars( claro_utf8_decode( $LPDetails['name'], get_conf( 'charset' ) ) ).'" />' . "\n"
            . '</dd>' . "\n"
            . '<dt><label for="newComment">' . get_lang('Comment') . '</label></dt>' . "\n"
            . '<dd>' . "\n"
            . claro_html_textarea_editor('newComment', $LPDetails['comment'], 15, 55)
            . '</dd>' . "\n"
            . '</dl>' . "\n"
            . '</fieldset>' . "\n"
            . '<input type="hidden" name="cmd" value="exEdit" />' . "\n"
            . '<input type="submit" value="' . get_lang('Ok') . '" />&nbsp;' . "\n"
            . claro_html_button(Url::Contextualize($_SERVER['PHP_SELF']), get_lang('Cancel'))
            . '</form>' . "\n"
            );
        }
        else
        {
            $dialogBox->error( get_lang('Wrong operation') );
        }

        break;
    
    // EXECUTE EDIT
    case "exEdit" :
        // Name must be unique
        $sql = "SELECT COUNT(`name`)
                             FROM `" . $TABLELEARNPATH . "`
                            WHERE `name` = '" . claro_sql_escape($_POST['newName']) . "'
                              AND !(`learnPath_id` = " . (int) $_SESSION['path_id'] . ")";
        $num = claro_sql_query_get_single_value($sql);
        
        if ($num == 0)  // name doesn't already exists
        {
            $sql = "UPDATE `" . $TABLELEARNPATH . "`
                    SET `name` = '" . claro_sql_escape($_POST['newName']) ."',
                        `comment` = '" . claro_sql_escape($_POST['newComment']) . "'
                    WHERE `learnPath_id` = " . (int) $_SESSION['path_id'];
            
            if (claro_sql_query($sql))
            {
                $dialogBox->success(get_lang('Learning path updated'));
            }
        }
        else
        {
            $dialogBox->error(get_lang('Error : Name already exists in the learning path or in the module pool') . '<br />');
        }
        
        
        break;
    
    // MODULE DELETE
    case "delModule" :
        //--- BUILD ARBORESCENCE OF MODULES IN LEARNING PATH
        $sql = "SELECT M.*, LPM.*
                FROM `".$TABLEMODULE."` AS M, `".$TABLELEARNPATHMODULE."` AS LPM
                WHERE M.`module_id` = LPM.`module_id`
                AND LPM.`learnPath_id` = ". (int)$_SESSION['path_id']."
                ORDER BY LPM.`rank` ASC";
        $result = claro_sql_query($sql);

        $extendedList = array();
        while ($list = mysql_fetch_array($result, MYSQL_ASSOC))
        {
            $extendedList[] = $list;
        }

        //-- delete module cmdid and his children if it is a label
        // get the modules tree ( cmdid module and all its children)
        $temp[0] = get_module_tree( 
            build_element_list($extendedList, 'parent', 'learnPath_module_id'), 
            $_REQUEST['cmdid'] , 
            'learnPath_module_id' );
        // delete the tree
        delete_module_tree($temp);

        break;

    // VISIBILITY COMMAND
    case "mkVisibl" :
    case "mkInvisibl" :
        $cmd == "mkVisibl" ? $visibility = 'SHOW' : $visibility = 'HIDE';
        //--- BUILD ARBORESCENCE OF MODULES IN LEARNING PATH
        $sql = "SELECT M.*, LPM.*
                FROM `".$TABLEMODULE."` AS M, `".$TABLELEARNPATHMODULE."` AS LPM
                WHERE M.`module_id` = LPM.`module_id`
                AND LPM.`learnPath_id` = ". (int)$_SESSION['path_id'] ."
                ORDER BY LPM.`rank` ASC";
        $result = claro_sql_query($sql);

        $extendedList = array();
        while ($list = mysql_fetch_array($result, MYSQL_ASSOC))
        {
            $extendedList[] = $list;
        }

        //-- set the visibility for module cmdid and his children if it is a label
        // get the modules tree ( cmdid module and all its children)
        $temp[0] = get_module_tree( build_element_list($extendedList, 'parent', 'learnPath_module_id'), $_REQUEST['cmdid'] );
        // change the visibility according to the new father visibility
        set_module_tree_visibility( $temp, $visibility);

        break;

    // ACCESSIBILITY COMMAND
    case "mkBlock" :
    case "mkUnblock" :
        $cmd == "mkBlock" ? $blocking = 'CLOSE' : $blocking = 'OPEN';
        $sql = "UPDATE `".$TABLELEARNPATHMODULE."`
                SET `lock` = '$blocking'
                WHERE `learnPath_module_id` = ". (int)$_REQUEST['cmdid']."
                AND `lock` != '$blocking'";
        $query = claro_sql_query ($sql);
        break;

    // ORDER COMMAND
    case "changePos" :
        // changePos form sent
        if( isset($_POST["newPos"]) && $_POST["newPos"] != "")
        {
            // get order of parent module
            $sql = "SELECT *
                    FROM `".$TABLELEARNPATHMODULE."`
                    WHERE `learnPath_module_id` = ". (int)$_REQUEST['cmdid'];
            $temp = claro_sql_query_fetch_all($sql);
            $movedModule = $temp[0];

            // if origin and target are the same ... cancel operation
            if ($movedModule['learnPath_module_id'] == $_POST['newPos'])
            {
                $dialogBox->error( get_lang('Wrong operation') );
            }
            else
            {
                //--
                // select max order
                // get the max rank of the children of the new parent of this module
                $sql = "SELECT MAX(`rank`)
                        FROM `".$TABLELEARNPATHMODULE."`
                        WHERE `parent` = ". (int)$_POST['newPos'];

                $result = claro_sql_query($sql);

                list($orderMax) = mysql_fetch_row($result);
                $order = $orderMax + 1;

                // change parent module reference in the moved module and set order (added to the end of target group)
                $sql = "UPDATE `".$TABLELEARNPATHMODULE."`
                        SET `parent` = ". (int)$_POST['newPos'].",
                            `rank` = " . (int)$order . "
                        WHERE `learnPath_module_id` = ". (int)$_REQUEST['cmdid'];
                $query = claro_sql_query($sql);
                $dialogBox->success( get_lang('Module moved') );
            }

        }
        else  // create form requested
        {
            // create elementList
            $sql = "SELECT M.*, LPM.*
                    FROM `".$TABLEMODULE."` AS M, `".$TABLELEARNPATHMODULE."` AS LPM
                    WHERE M.`module_id` = LPM.`module_id`
                      AND LPM.`learnPath_id` = ". (int)$_SESSION['path_id']."
                      AND M.`contentType` = \"".CTLABEL_."\"
                    ORDER BY LPM.`rank` ASC";
            $result = claro_sql_query($sql);
            $i=0;
            $extendedList = array();
            while ($list = mysql_fetch_array($result))
            {
                // this array will display target for the "move" command
                // so don't add the module itself build_element_list will ignore all childre so that
                // children of the moved module won't be shown, a parent cannot be a child of its own children
                if ( $list['learnPath_module_id'] != $_REQUEST['cmdid'] ) $extendedList[] = $list;
            }

            // build the array that will be used by the claro_build_nested_select_menu function
            $elementList = array();
            $elementList = build_element_list($extendedList, 'parent', 'learnPath_module_id');

            $topElement['name'] = get_lang('Root');
            $topElement['value'] = 0;    // value is required by claro_nested_build_select_menu
            if (!is_array($elementList)) $elementList = array();
            array_unshift($elementList,$topElement);

            // get infos about the moved module
            $sql = "SELECT M.`name`
                    FROM `".$TABLELEARNPATHMODULE."` AS LPM,
                         `".$TABLEMODULE."` AS M
                    WHERE LPM.`module_id` = M.`module_id`
                      AND LPM.`learnPath_module_id` = ". (int)$_REQUEST['cmdid'];
            $temp = claro_sql_query_fetch_all($sql);
            $moduleInfos = $temp[0];

            $displayChangePosForm = true; // the form code comes after name and comment boxes section
        }
        break;

    case "moveUp" :
        $thisLPMId = $_REQUEST['cmdid'];
        $sortDirection = "DESC";
        break;

    case "moveDown" :
        $thisLPMId = $_REQUEST['cmdid'];
        $sortDirection = "ASC";
        break;

    case "createLabel" :
        // create form sent
        if( isset($_REQUEST["newLabel"]) && trim($_REQUEST["newLabel"]) != "")
        {
            // determine the default order of this Learning path ( a new label is a root child)
            $sql = "SELECT MAX(`rank`)
                    FROM `".$TABLELEARNPATHMODULE."`
                    WHERE `parent` = 0";
            $result = claro_sql_query($sql);

            list($orderMax) = mysql_fetch_row($result);
            $order = $orderMax + 1;

            // create new module
            $sql = "INSERT INTO `".$TABLEMODULE."`
                   (`name`, `comment`, `contentType`, `launch_data`)
                   VALUES ('". claro_sql_escape($_POST['newLabel']) ."','', '".CTLABEL_."', '')";
            $query = claro_sql_query($sql);

            // request ID of the last inserted row (module_id in $TABLEMODULE) to add it in $TABLELEARNPATHMODULE
            $thisInsertedModuleId = claro_sql_insert_id();

            // create new learning path module
            $sql = "INSERT INTO `".$TABLELEARNPATHMODULE."`
                   (`learnPath_id`, `module_id`, `specificComment`, `rank`, `parent`)
                   VALUES ('". (int)$_SESSION['path_id']."', '". (int)$thisInsertedModuleId."','', " . (int)$order . ", 0)";
            $query = claro_sql_query($sql);
        }
        else  // create form requested
        {
            $displayCreateLabelForm = true; // the form code comes after name and comment boxes section
        }
        break;

     default:
        break;

}

// change sorting if required
if (isset($sortDirection) && $sortDirection)
{
    // get list of modules with same parent as the moved module
    $sql = "SELECT LPM.`learnPath_module_id`, LPM.`rank`
            FROM (`".$TABLELEARNPATHMODULE."` AS LPM, `".$TABLELEARNPATH."` AS LP)
              LEFT JOIN `".$TABLELEARNPATHMODULE."` AS LPM2 ON LPM2.`parent` = LPM.`parent`
            WHERE LPM2.`learnPath_module_id` = ". (int)$thisLPMId."
              AND LPM.`learnPath_id` = LP.`learnPath_id`
              AND LP.`learnPath_id` = ". (int)$_SESSION['path_id']."
            ORDER BY LPM.`rank` $sortDirection";

    $listModules  = claro_sql_query_fetch_all($sql);

    // LP = learningPath
    foreach( $listModules as $module)
    {
        // STEP 2 : FOUND THE NEXT ANNOUNCEMENT ID AND ORDER.
        //          COMMIT ORDER SWAP ON THE DB
        if (isset($thisLPMOrderFound)&& $thisLPMOrderFound == true)
        {

            $nextLPMId = $module['learnPath_module_id'];
            $nextLPMOrder =  $module['rank'];

            $sql = "UPDATE `".$TABLELEARNPATHMODULE."`
                    SET `rank` = \"" . (int)$nextLPMOrder . "\"
                    WHERE `learnPath_module_id` =  \"" . (int)$thisLPMId . "\"";
            claro_sql_query($sql);

            $sql = "UPDATE `".$TABLELEARNPATHMODULE."`
                    SET `rank` = \"" . (int)$thisLPMOrder . "\"
                    WHERE `learnPath_module_id` =  \"" . (int)$nextLPMId . "\"";
            claro_sql_query($sql);

            break;
        }

        // STEP 1 : FIND THE ORDER OF THE ANNOUNCEMENT
        if ($module['learnPath_module_id'] == $thisLPMId)
        {
            $thisLPMOrder = $module['rank'];
            $thisLPMOrderFound = true;
        }
    }
}

//####################################################################################\\
//############################ create label && change pos forms  #####################\\
//####################################################################################\\

if (isset($displayCreateLabelForm) && $displayCreateLabelForm)
{
    $dialogBox->form( '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">'
        . claro_form_relay_context()
        . '<h4>'
        . '<label for="newLabel">'
        . get_lang('Create a new label / title in this learning path')
        . '</label>'
        . '</h4>' . "\n"
        . '<input type="text" name="newLabel" id="newLabel" maxlength="255" />' . "\n"
        . '<input type="hidden" name="cmd" value="createLabel" />' . "\n"
        . '<input type="submit" value="' . get_lang('Ok') . '" />' . "\n"
        . '</form>'
    );
}

if (isset($displayChangePosForm) && $displayChangePosForm)
{
    $dialogBox->form( '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">'
        . claro_form_relay_context()
        . '<h4>'
        . get_lang('Move "%moduleName" to', array('%moduleName' => $moduleInfos['name'])) . '</h4>'

        // Build select input - $elementList has been declared in the previous big cmd case
        . claro_build_nested_select_menu("newPos",$elementList)
        . '<input type="hidden" name="cmd" value="changePos" />' . "\n"
        . '<input type="hidden" name="cmdid" value="' . $_REQUEST['cmdid'] . '" />' . "\n"
        . '<input type="submit" value="' . get_lang('Ok') . '" />' . "\n"
        . '</form>'
    );
}

// Command list
$cmdList = array();

$cmdList[] = array(
    'img' => 'add',
    'name' => get_lang('Add a document'),
    'url' => claro_htmlspecialchars(Url::Contextualize('insertMyDoc.php'))
);
$cmdList[] = array(
    'img' => 'add',
    'name' => get_lang('Add an exercise'),
    'url' => claro_htmlspecialchars(Url::Contextualize('insertMyExercise.php'))
);
$cmdList[] = array(
    'img' => 'add',
    'name' => get_lang('Add a module of this course'),
    'url' => claro_htmlspecialchars(Url::Contextualize('insertMyModule.php'))
);
$cmdList[] = array(
    'img' => 'add',
    'name' => get_lang('Create label'),
    'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=createLabel'))
);


// Display
$out = '';

// Render title
$titleParts = array(
    'mainTitle' => $nameTools,
    'subTitle' => $LPDetails['name']);

$out .= claro_html_tool_title($titleParts, null, $cmdList);

// Render dialog box
$out .= $dialogBox->render();

//####################################################################################\\
//############################ LEARNING PATH NAME BOX ################################\\
//####################################################################################\\

if ( $cmd == "updateName" )
{
    $out .= nameBox(LEARNINGPATH_, UPDATE_);
}
else
{
    $out .= nameBox(LEARNINGPATH_, DISPLAY_);
}

//####################################################################################\\
//############################ LEARNING PATH COMMENT BOX #############################\\
//####################################################################################\\

if ( $cmd == "updatecomment" )
{
    $out .= commentBox(LEARNINGPATH_, UPDATE_);
}
elseif ($cmd == "delcomment" )
{
    $out .= commentBox(LEARNINGPATH_, DELETE_);
}
else
{
    $out .= commentBox(LEARNINGPATH_, DISPLAY_);
}

//####################################################################################\\
//######################### LEARNING PATH LIST CONTENT ###############################\\
//####################################################################################\\

//--- BUILD ARBORESCENCE OF MODULES IN LEARNING PATH
// TODO  do not  use *
$sql = "SELECT M.*, LPM.*, A.`path`
        FROM (`".$TABLEMODULE."` AS M,
             `".$TABLELEARNPATHMODULE."` AS LPM)
        LEFT JOIN `".$TABLEASSET."` AS A ON M.`startAsset_id` = A.`asset_id`
        WHERE M.`module_id` = LPM.`module_id`
          AND LPM.`learnPath_id` = ". (int)$_SESSION['path_id']."
        ORDER BY LPM.`rank` ASC";

$result = claro_sql_query($sql);

$extendedList = array();
while ($list = mysql_fetch_array($result, MYSQL_ASSOC))
{
    $extendedList[] = $list;
}

// build the array of modules
// build_element_list return a multi-level array, where children is an array with all nested modules
// build_display_element_list return an 1-level array where children is the deep of the module

$flatElementList = build_display_element_list(build_element_list($extendedList, 'parent', 'learnPath_module_id'));

$iterator = 1;
$atleastOne = false;
$i = 0;

// look for maxDeep
$maxDeep = 1; // used to compute colspan of <td> cells
for ($i=0 ; $i < sizeof($flatElementList) ; $i++)
{
    if ($flatElementList[$i]['children'] > $maxDeep) $maxDeep = $flatElementList[$i]['children'] ;
}

//####################################################################################\\
//######################### LEARNING PATH LIST HEADER ################################\\
//####################################################################################\\

$out .= '<table class="claroTable emphaseLine" width="100%" border="0" cellspacing="2">' . "\n"
. '<thead>' . "\n"
. '<tr class="headerX" align="center" valign="top">' . "\n"
. '<th colspan="' . ($maxDeep+1) . '">'. get_lang('Module') .'</th>' . "\n"
. '<th>'. get_lang('Modify') .'</th>' . "\n"
. '<th>'. get_lang('Remove') .'</th>' . "\n"
. '<th>'. get_lang('Block') .'</th>' . "\n"
. '<th>'. get_lang('Visibility') .'</th>' . "\n"
. '<th>'. get_lang('Move') .'</th>' . "\n"
. '<th colspan="2">'. get_lang('Order') .'</th>' . "\n"
. '</tr>' . "\n"
. '</thead>' . "\n"
. '<tbody>' . "\n"
;

//####################################################################################\\
//######################### LEARNING PATH LIST DISPLAY ###############################\\
//####################################################################################\\

foreach ($flatElementList as $module)
{
    //-------------visibility-----------------------------
    if ( $module['visibility'] == 'HIDE' )
    {
        if ($is_allowedToEdit)
        {
            $style=" class=\"invisible\"";
        }
        else
        {
            continue; // skip the display of this file
        }
    }
    else
    {
        $style="";
    }

    $spacingString = "";

    for($i = 0; $i < $module['children']; $i++)
           $spacingString .= "<td width='5'>&nbsp;</td>";

    $colspan = $maxDeep - $module['children']+1;

    $out .= '<tr align="center"'.$style.'>' . "\n" .$spacingString. '<td colspan="'.$colspan.'" align="left">' . "\n";

    if ($module['contentType'] == CTLABEL_) // chapter head
    {
        $out .= "<b>".claro_htmlspecialchars( claro_utf8_decode( $module['name'], get_conf( 'charset' ) ) )."</b>\n";
    }
    else // module
    {
        if($module['contentType'] == CTEXERCISE_ )
            $moduleImg = get_icon_url( 'quiz', 'CLQWZ' );
        else
            $moduleImg = get_icon_url( choose_image(basename($module['path'])) );

        $contentType_alt = selectAlt($module['contentType']);
        $out .= "<a href=\"".claro_htmlspecialchars(Url::Contextualize('module.php?module_id='.$module['module_id']))."\">"
            . "<img src=\"" . $moduleImg . "\" alt=\"".$contentType_alt."\" > "
            . claro_htmlspecialchars( claro_utf8_decode( $module['name'], get_conf( 'charset' ) ) )
            . "</a>";
    }
    $out .= "</td>"; // end of td of module name

    // Modify command / go to other page
    $out .= "<td>
          <a href=\"".claro_htmlspecialchars(Url::Contextualize('module.php?module_id='.$module['module_id']))."\">".
         "<img src=\"" . get_icon_url('edit') . "\" alt=\"".get_lang('Modify')."\" />".
         "</a>
         </td>";

    // DELETE ROW

   //in case of SCORM module, the pop-up window to confirm must be different as the action will be different on the server
    $out .= "<td>
          <a href=\"".claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']."?cmd=delModule&cmdid=".$module['learnPath_module_id']))."\" ".
         "onclick=\"return confirmation('".clean_str_for_javascript(get_lang('Are you sure you want to remove the following module from the learning path : ')." ".$module['name'])." ? ";

    if ($module['contentType'] == CTSCORM_)
        $out .= clean_str_for_javascript(get_lang('SCORM conformant modules are definitively removed from server when deleted in their learning path.')) ;
    elseif ( $module['contentType'] == CTLABEL_ )
        $out.= clean_str_for_javascript(get_lang('By deleting a label you will delete all modules or label it contains.'));
    else
        $out .= clean_str_for_javascript(get_lang('The module will still be available in the pool of modules.'));

    $out .=   "');\"
    ><img src=\"" . get_icon_url('delete') . "\" alt=\"".get_lang('Remove')."\" /></a>
       </td>";

    // LOCK
    $out .=    '<td>';

    if ( $module['contentType'] == CTLABEL_)
    {
        $out .= "&nbsp;";
    }
    elseif ( $module['lock'] == 'OPEN')
    {
        $out .= "<a href=\"".claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']."?cmd=mkBlock&cmdid=".$module['learnPath_module_id']))."\">".
             "<img src=\"" . get_icon_url('unblock') . "\" alt=\"" . get_lang('Block') . "\" />".
             "</a>";
    }
    elseif( $module['lock'] == 'CLOSE')
    {
        $out .= "<a href=\"".claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']."?cmd=mkUnblock&cmdid=".$module['learnPath_module_id']))."\">".
             "<img src=\"" . get_icon_url('block') . "\" alt=\"" . get_lang('Unblock') . "\" />".
             "</a>";
    }
    $out .= "</td>";

    // VISIBILITY
    $out .= "<td>";

    if ( $module['visibility'] == 'HIDE')
    {
        $out .= "<a href=\"".claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']."?cmd=mkVisibl&cmdid=".$module['module_id']))."\">".
             "<img src=\"" . get_icon_url('invisible') . "\" alt=\"" . get_lang('Make visible') . "\" />".
             "</a>";
    }
    else
    {
        if( $module['lock'] == 'CLOSE' )
        {
            $onclick = "onclick=\"return confirmation('".clean_str_for_javascript(get_block('blockConfirmBlockingModuleMadeInvisible'))."');\"";
        }
        else
        {
            $onclick = "";
        }
        $out .= "<a href=\"".claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']."?cmd=mkInvisibl&cmdid=".$module['module_id']))."\" ".$onclick. " >".
             "<img src=\"" . get_icon_url('visible') . "\" alt=\"" . get_lang('Make invisible') . "\" />".
             "</a>";
    }

    $out .= "</td>";

    // ORDER COMMANDS
    // DISPLAY CATEGORY MOVE COMMAND
    $out .= "<td>".
         "<a href=\"".claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']."?cmd=changePos&cmdid=".$module['learnPath_module_id']))."\">".
         "<img src=\"" . get_icon_url('move') . "\" alt=\"" . get_lang('Move'). "\" />".
         "</a>".
         "</td>";

    // DISPLAY MOVE UP COMMAND only if it is not the top learning path
    if ($module['up'])
    {
        $out .= "<td>".
             "<a href=\"".claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']."?cmd=moveUp&cmdid=".$module['learnPath_module_id']))."\">".
             "<img src=\"" . get_icon_url('move_up') . "\" alt=\"" . get_lang('Move up') . "\" />".
             "</a>".
             "</td>";
    }
    else
    {
        $out .= "<td>&nbsp;</td>";
    }

    // DISPLAY MOVE DOWN COMMAND only if it is not the bottom learning path
    if ($module['down'])
    {
        $out .= "<td>".
             "<a href=\"".claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']."?cmd=moveDown&cmdid=".$module['learnPath_module_id']))."\">".
             "<img src=\"" . get_icon_url('move_down') . "\" alt=\"" . get_lang('Move down') . "\" />".
             "</a>".
             "</td>";
    }
    else
    {
        $out .= "<td>&nbsp;</td>";
    }

    $out .= "\n</tr>\n";
    $iterator++;
    $atleastOne = true;
}

$out .= "</tbody>";

$out .= "<tfoot>";

if ($atleastOne == false)
{
    $out .= "<tr><td align=\"center\" colspan=\"7\">".get_lang('No module')."</td></tr>";
}

$out .= "</tfoot>";

//display table footer
$out .= "</table>";

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
