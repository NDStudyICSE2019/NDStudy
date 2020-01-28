<?php // $Id: learnPath.lib.inc.php 14400 2013-02-15 14:05:59Z kitan1982 $

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * This functions library is used by most of the pages of the learning path tool.
 *
 * @version     $Revision: 14400 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Piraux Sébastien <pir@cerdecam.be>
 * @author      Lederer Guillaume <led@cerdecam.be>
 * @package     CLLNP
 */


/**
* content type
*/
define ( 'CTCLARODOC_', 'CLARODOC' );
/**
* content type
*/
define ( 'CTDOCUMENT_', 'DOCUMENT' );
/**
* content type
*/
define ( 'CTEXERCISE_', 'EXERCISE' );
/**
* content type
*/
define ( 'CTSCORM_', 'SCORM' );
/**
* content type
*/
define ( 'CTLABEL_', 'LABEL' );


/**
* mode used by {@link commentBox($type, $mode)} and {@link nameBox($type, $mode)}
*/
define ( 'DISPLAY_', 1 );
/**
* mode used by {@link commentBox($type, $mode)} and {@link nameBox($type, $mode)}
*/
define ( 'UPDATE_', 2 );
define ( 'UPDATENOTSHOWN_', 4 );

/**
* mode used by {@link commentBox($type, $mode)} and {@link nameBox($type, $mode)}
*/
define ( 'DELETE_', 3 );

/**
* type used by {@link commentBox($type, $mode)} and {@link nameBox($type, $mode)}
*/
define ( 'ASSET_', 1 );
/**
* type used by {@link commentBox($type, $mode)} and {@link nameBox($type, $mode)}
*/
define ( 'MODULE_', 2 );
define ( 'LEARNINGPATH_', 3 );
define ( 'LEARNINGPATHMODULE_', 4 );

/**
 * This function is used to display comments of module or learning path with admin links if needed.
 * Admin links are 'edit' and 'delete' links.
 *
 * @param string $type MODULE_ , LEARNINGPATH_ , LEARNINGPATHMODULE_
 * @param string $mode DISPLAY_ , UPDATE_ , DELETE_
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 * @author Lederer Guillaume <led@cerdecam.be>
 */
function commentBox($type, $mode)
{
    $tbl_cdb_names = claro_sql_get_course_tbl();
    $tbl_lp_learnPath            = $tbl_cdb_names['lp_learnPath'];
    $tbl_lp_rel_learnPath_module = $tbl_cdb_names['lp_rel_learnPath_module'];
    $tbl_lp_module               = $tbl_cdb_names['lp_module'];
    
    $out = '';
    
    // globals
    global $is_allowedToEdit;
    
    // will be set 'true' if the comment has to be displayed
    $dsp = false;
    
    // those vars will be used to build sql queries according to the comment type
    switch ( $type )
    {
        case MODULE_ :
            $defaultTxt = get_lang('blockDefaultModuleComment');
            $col_name = 'comment';
            $tbl_name = $tbl_lp_module;
            if ( isset($_REQUEST['module_id'] ) )
            {
                $module_id = $_REQUEST['module_id'];
            }
            else
            {
                $module_id = $_SESSION['module_id'];
            }
            $where_cond = "`module_id` = " . (int) $module_id;  // use backticks ( ` ) for col names and simple quote ( ' ) for string
            break;
        case LEARNINGPATH_ :
            $defaultTxt = get_lang('blockDefaultLearningPathComment');
            $col_name = 'comment';
            $tbl_name = $tbl_lp_learnPath;
            $where_cond = '`learnPath_id` = '. (int) $_SESSION['path_id'];  // use backticks ( ` ) for col names and simple quote ( ' ) for string
            break;
        case LEARNINGPATHMODULE_ :
            $defaultTxt = get_lang('blockDefaultModuleAddedComment');
            $col_name = 'specificComment';
            $tbl_name = $tbl_lp_rel_learnPath_module;
            $where_cond = "`learnPath_id` = " . (int) $_SESSION['path_id'] . "
                                        AND `module_id` = " . (int) $_SESSION['module_id'];  // use backticks ( ` ) for col names and simple quote ( ' ) for string
            break;
    }
    
    // update mode
    // allow to chose between
    // - update and show the comment and the pencil and the delete cross (UPDATE_)
    // - update and nothing displayed after form sent (UPDATENOTSHOWN_)
    if ( ( $mode == UPDATE_ || $mode == UPDATENOTSHOWN_ )  && $is_allowedToEdit )
    {
        if ( isset($_POST['insertCommentBox']) )
        {
            $sql = "UPDATE `" . $tbl_name . "`
                    SET `" . $col_name . "` = \"". claro_sql_escape($_POST['insertCommentBox'])."\"
                    WHERE " . $where_cond;
            claro_sql_query($sql);
            
            if($mode == UPDATE_)
                $dsp = true;
            elseif($mode == UPDATENOTSHOWN_)
                $dsp = false;
        }
        else // display form
        {
            // get info to fill the form in
            $sql = "SELECT `".$col_name."`
                       FROM `" . $tbl_name . "`
                      WHERE " . $where_cond;
            $oldComment = claro_sql_query_get_single_value($sql);
            
            $out .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'">' . "\n"
                  . claro_html_textarea_editor('insertCommentBox', $oldComment, 15, 55).'<br />' . "\n"
                  . '<input type="hidden" name="cmd" value="update' . $col_name . '" />'
                  . '<input type="submit" value="' . get_lang('Ok') . '" />' . "\n"
                  . '<br />' . "\n"
                  . '</form>' . "\n";
        }
    }
    
    // delete mode
    if ( $mode == DELETE_ && $is_allowedToEdit )
    {
        $sql =  "UPDATE `" . $tbl_name . "`
                 SET `" . $col_name . "` = ''
                 WHERE " . $where_cond;
        claro_sql_query($sql);
        $dsp = TRUE;
    }
    
    // display mode only or display was asked by delete mode or update mode
    if ( $mode == DISPLAY_ || $dsp == TRUE )
    {
        $sql = "SELECT `".$col_name."`
                FROM `" . $tbl_name . "`
                WHERE " . $where_cond;
        
        $currentComment = claro_sql_query_get_single_value($sql);
        
        // display nothing if this is default comment and not an admin
        if ( ($currentComment == $defaultTxt) && !$is_allowedToEdit ) return '';
        
        if ( empty($currentComment) )
        {
            // if no comment and user is admin : display link to add a comment
            if ( $is_allowedToEdit )
            {
                $textLink = '';
                if ($type == MODULE_)
                {
                    $textLink = get_lang('Add a comment to this module');
                }
                elseif ($type == LEARNINGPATHMODULE_)
                {
                    $textLink = get_lang('Add a specific comment to this module');
                }
                else
                {
                    $textLink = get_lang('Add a comment');
                }
                
                $out .= '<p>' . "\n"
                      . claro_html_cmd_link(
                            $_SERVER['PHP_SELF']
                            . '?cmd=update' . $col_name . claro_url_relay_context('&amp;'),
                            $textLink
                      )
                      . '</p>' . "\n";
            }
        }
        else
        {
            // display comment
            $out .= "<p>".claro_parse_user_text($currentComment)."</p>";
            // display edit and delete links if user as the right to see it
            if ( $is_allowedToEdit )
            {
                $out .= '<p>' . "\n"
                .    '<small>' . "\n"
                .    '<a href="' . $_SERVER['PHP_SELF'] . '?cmd=update' . $col_name . '">' . "\n"
                .    '<img src="' . get_icon_url('edit') . '" alt="' . get_lang('Modify') . '" />' . "\n"
                .    '</a>' . "\n"
                .    '<a href="' . $_SERVER['PHP_SELF'].'?cmd=del' . $col_name . '" '
                .    ' onclick="javascript:if(!confirm(\''.clean_str_for_javascript(get_lang('Please confirm your choice')).'\')) return false;">' . "\n"
                .    '<img src="' . get_icon_url('delete') . '" alt="' . get_lang('Delete') . '" />' . "\n"
                .    '</a>' . "\n"
                .    '</small>' . "\n"
                .    '</p>' . "\n"
                ;
            }
        }
    }

    return $out;
}

/**
  * This function is used to display name of module or learning path with admin links if needed
  *
  * @param string $type MODULE_ , LEARNINGPATH_
  * @param string $mode display(DISPLAY_) or update(UPDATE_) mode, no delete for a name
  * @author Piraux S�bastien <pir@cerdecam.be>
  * @author Lederer Guillaume <led@cerdecam.be>
  */
function nameBox($type, $mode)
{
    $tbl_cdb_names = claro_sql_get_course_tbl();
    $tbl_topics                  = $tbl_cdb_names['bb_topics'];
    $tbl_lp_learnPath            = $tbl_cdb_names['lp_learnPath'];
    $tbl_lp_module               = $tbl_cdb_names['lp_module'];
    
    $out = '';
    
    // globals
    global $is_allowedToEdit;
    global $urlAppend;

    // $dsp will be set 'true' if the comment has to be displayed
    $dsp = false;

    // those vars will be used to build sql queries according to the name type
    switch ( $type )
    {
        case MODULE_ :
            $col_name = 'name';
            $tbl_name = $tbl_lp_module;
            $where_cond = '`module_id` = ' . (int) $_SESSION['module_id'];
            break;
        case LEARNINGPATH_ :
            $col_name = 'name';
            $tbl_name = $tbl_lp_learnPath;
            $where_cond = '`learnPath_id` = ' . (int) $_SESSION['path_id'];
            break;
    }

    // update mode
    if ( $mode == UPDATE_ && $is_allowedToEdit)
    {
        if ( isset($_POST['newName']) && !empty($_POST['newName']) )
        {
            $sql = "SELECT COUNT(`" . $col_name . "`)
                                 FROM `" . $tbl_name . "`
                                WHERE `" . $col_name . "` = '" . claro_sql_escape($_POST['newName']) . "'
                                  AND !(" . $where_cond . ")";
            $num = claro_sql_query_get_single_value($sql);

            if ($num == 0)  // name doesn't already exists
            {
                $sql = "UPDATE `" . $tbl_name . "`
                                      SET `" . $col_name . "` = '" . claro_sql_escape($_POST['newName']) ."'
                                    WHERE " . $where_cond;
                claro_sql_query($sql);
                $dsp = true;
            }
            else
            {
                $out .= get_lang('Error : Name already exists in the learning path or in the module pool') . '<br />';
                $dsp = true;
            }
        }
        else // display form
        {
            $sql = "SELECT `name`
                    FROM `" . $tbl_name . "`
                    WHERE " . $where_cond;

            $oldName = claro_sql_query_get_single_value($sql);

            $out .= '<form method="post" action="' . $_SERVER['PHP_SELF'].'">' . "\n"
            .    '<input type="text" name="newName" size="50" maxlength="255" value="'.claro_htmlspecialchars( claro_utf8_decode( $oldName, get_conf( 'charset' ) ) ).'" />'
            .    '<br />' . "\n"
            .    '<input type="hidden" name="cmd" value="updateName" />' ."\n"
            .    '<input type="submit" value="' . get_lang('Ok') . '" />' . "\n"
            .    '<br />' . "\n"
            .    '</form>' . "\n"
            ;
        }

    }

    // display if display mode or asked by the update
    if ( $mode == DISPLAY_ || $dsp == true )
    {
        $sql = "SELECT `name`
                      FROM `" . $tbl_name . "`
                     WHERE " . $where_cond;

        $currentName = claro_sql_query_get_single_value($sql);

        $out .= '<h4>'
        .    claro_utf8_decode( $currentName, get_conf( 'charset' ) );

        if ( $is_allowedToEdit )
        {
            $out .= '<br /><a href="' . $_SERVER['PHP_SELF'] . '?cmd=updateName">'
            .    '<img src="' . get_icon_url('edit') . '" alt="' . get_lang('Modify') . '" />'
            .    '</a>' . "\n"
            ;
        }
        
        $out .=    '</h4>'."\n\n";
    }

    return $out;
}

/**
 * This function is used to display the default time associated to a DOCUMENT module
 * 
 * @param string $mode display(DISPLAY_) or update(UPDATE_) mode
 * @author Anh Thao PHAM <anhthao.pham@claroline.net>
 */
function documentDefaultTimeBox( $mode )
{
    $tbl = claro_sql_get_course_tbl();
    $tblModule = $tbl['lp_module'];
    
    $out = '';
    
    // globals
    global $is_allowedToEdit;

    $dsp = false;
    $colName = 'launch_data';
    $whereCond = '`module_id` = ' . (int) $_SESSION['module_id'];
    
    // update mode
    if ( $mode == UPDATE_ && $is_allowedToEdit )
    {
        if ( isset( $_POST['newTime'] ) )
        {
            $sql = "SELECT `" . $colName . "`
                      FROM `" . $tblModule . "`
                     WHERE `" . $colName . "` = '" . claro_sql_escape( $_POST['newTime'] ) . "'
                       AND " . $whereCond;
            $num = claro_sql_query_get_single_value($sql);

            if ($num == 0 && ( preg_match( '/^\d+$/', $_POST['newTime'] ) || empty( $_POST['newTime'] ) ) )  // default time doesn't already exists
            {
                $newTimeValue = '';
                if( preg_match( '/^\d+$/', $_POST['newTime'] ) )
                {
                    $newTimeValue = (int)$_POST['newTime'];
                }
                $sql = "UPDATE `" . $tblModule . "`
                           SET `" . $colName . "` = '" . claro_sql_escape( $newTimeValue ) ."'
                         WHERE " . $whereCond;
                claro_sql_query( $sql );
                $dsp = true;
            }
            else
            {
                $dsp = true;
            }
        }
        else // display form
        {
            $out .= '<b>' . get_lang( 'Document default time' ) . '</b><br />';
            
            $sql = "SELECT `" . $colName . "`
                    FROM `" . $tblModule . "`
                    WHERE " . $whereCond;

            $oldDefaultTime = claro_sql_query_get_single_value( $sql );

            $out .= '<form method="post" action="' . $_SERVER['PHP_SELF'].'">' . "\n"
            .    '<input type="text" name="newTime" size="8" maxlength="20" value="'.claro_htmlspecialchars( claro_utf8_decode( $oldDefaultTime, get_conf( 'charset' ) ) ).'" />'
            .    ' ' . get_lang( 'minute(s)' ) . '<br />' . "\n"
            .    '<input type="hidden" name="cmd" value="updateDefaultTime" />' ."\n"
            .    '<input type="submit" value="' . get_lang('Ok') . '" />' . "\n"
            .    '<br />' . "\n"
            .    '</form>' . "<br />"
            ;
        }

    }

    // display if display mode or asked by the update
    if ( $mode == DISPLAY_ || $dsp == true )
    {
        $sql = "SELECT `" . $colName . "`
                  FROM `" . $tblModule . "`
                 WHERE " . $whereCond;

        $currentDefaultTime = claro_sql_query_get_single_value( $sql );
        if( is_null( $currentDefaultTime ) || trim( $currentDefaultTime ) == '' )
        {
            $currentDefaultTime = get_conf( 'cllnp_documentDefaultTime' );
        }
        
        $out .= '<b>' . get_lang( 'Document default time' ) . '</b><br />';
        
        $out .= claro_utf8_decode( $currentDefaultTime, get_conf( 'charset' ) ) . ' ' . get_lang( 'minute(s)' );

        if ( $is_allowedToEdit )
        {
            $out .= '<br /><a href="' . $_SERVER['PHP_SELF'] . '?cmd=updateDefaultTime">'
            .    '<img src="' . get_icon_url('edit') . '" alt="' . get_lang('Modify') . '" />'
            .    '</a>' . "\n"
            ;
        }
        
        $out .= "<br /><br />";
    }

    return $out;
}

/**
  * This function is used to display the correct image in the modules lists
  * It looks for the correct type in the array, and return the corresponding image name if found
  * else it returns a default image
  *
  * @param  string $contentType type of content in learning path
  * @return string name of the image with extension
  * @author Piraux S�bastien <pir@cerdecam.be>
  * @author Lederer Guillaume <led@cerdecam.be>
  */
 function selectImage($contentType)
 {
    switch($contentType)
    {
        case CTDOCUMENT_ :
            return get_icon_url('document', 'CLDOC');
            break;
        case CTEXERCISE_ :
            return get_icon_url('quiz', 'CLQWZ');
            break;
        case CTSCORM_ :
            return get_icon_url('scorm');
            break;
        default :
            return get_icon_url('default');
            break;
    }
 }
 /**
  * This function is used to display the correct alt texte for image in the modules lists.
  * Mainly used at the same time than selectImage() to add an alternate text on the image.
  *
  * @param  string $contentType type of content in learning path
  * @return string text for the alt
  * @author Piraux S�bastien <pir@cerdecam.be>
  * @author Lederer Guillaume <led@cerdecam.be>
  */
 function selectAlt($contentType)
 {
      $altList[CTDOCUMENT_] = get_lang('Documents and Links');
      $altList[CTCLARODOC_] = get_lang('Clarodoc');
      $altList[CTEXERCISE_] = get_lang('Exercises');
      $altList[CTSCORM_] = get_lang('Scorm');

      if (array_key_exists( $contentType , $altList ))
      {
          return $altList[$contentType];
      }

      return "default.gif";
 }

/**
 * This function receives an array like $table['idOfThingToOrder'] = $requiredOrder and will return a sorted array
 * like $table[$i] = $idOfThingToOrder
 * the id list is sorted according to the $requiredOrder values
 *
 * @param  $formValuesTab array an array like these sent by the form on learingPathAdmin.php for an exemple
 *
 * @return array an array of the sorted list of ids
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 * @author Lederer Guillaume <led@cerdecam.be>
 */
function setOrderTab ( $formValuesTab )
{
    global $dialogBox;

    $tabOrder = array(); // declaration to avoid bug in "elseif (in_array ... "
    $i = 0;
    foreach ( $formValuesTab as $key => $requiredOrder)
    {
        // error if input is not a number
        if( !is_num($requiredOrder) )
        {
            $dialogBox .= get_lang('ErrorInvalidParms');
            return 0;
        }
        elseif( in_array($requiredOrder, $tabOrder) )
        {
            $dialogBox .= get_lang('Error : One or more values are doubled');
            return 0;
        }
        // $tabInvert = required order => id module
        $tabInvert[$requiredOrder] = $key;
        // $tabOrder = required order : unsorted
        $tabOrder[$i] = $requiredOrder;
        $i++;
    }
    // $tabOrder = required order : sorted
    sort($tabOrder);
    $i = 0;
    foreach ($tabOrder as $key => $order)
    {
        // $tabSorted = new Order => id learning path
        $tabSorted[$i] = $tabInvert[$order];
        $i++;
    }
    return $tabSorted;
}


/**
 * Check if an input string is a number
 *
 * @param string $var input to check
 * @return bool true if $var is a number, false otherwise
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 */
function is_num($var)
{
    for ( $i = 0; $i < strlen($var); $i++ )
    {
        $ascii = ord($var[$i]);

        // 48 to 57 are decimal ascii values for 0 to 9
        if ( $ascii >= 48 && $ascii <= 57)
            continue;
        else
            return false;
    }

    return true;
}


/**
 *  This function allows to display the modules content of a learning path.
 *  The function must be called from inside a learning path where the session variable path_id is known.
 */
function display_path_content()
{
    $tbl_cdb_names = claro_sql_get_course_tbl();
    $tbl_lp_learnPath            = $tbl_cdb_names['lp_learnPath'];
    $tbl_lp_rel_learnPath_module = $tbl_cdb_names['lp_rel_learnPath_module'];
    $tbl_lp_user_module_progress = $tbl_cdb_names['lp_user_module_progress'];
    $tbl_lp_module               = $tbl_cdb_names['lp_module'];
    $tbl_lp_asset                = $tbl_cdb_names['lp_asset'];

    $style = "";

    $sql = "SELECT M.`name`, M.`contentType`,
                   LPM.`learnPath_module_id`, LPM.`parent`,
                   A.`path`
            FROM `" . $tbl_lp_learnPath . "` AS LP,
                 `" . $tbl_lp_rel_learnPath_module . "` AS LPM,
                 `" . $tbl_lp_module . "` AS M
            LEFT JOIN `" . $tbl_lp_asset . "` AS A
              ON M.`startAsset_id` = A.`asset_id`
            WHERE LP.`learnPath_id` = " .  (int) $_SESSION['path_id'] . "
              AND LP.`learnPath_id` = LPM.`learnPath_id`
              AND LPM.`module_id` = M.`module_id`
            ORDER BY LPM.`rank`";
    $moduleList = claro_sql_query_fetch_all($sql);

    $extendedList = array();
    foreach( $moduleList as $module)
    {
        $extendedList[] = $module;
    }
    // build the array of modules
    // build_element_list return a multi-level array, where children is an array with all nested modules
    // build_display_element_list return an 1-level array where children is the deep of the module
    $flatElementList = build_display_element_list(build_element_list($extendedList, 'parent', 'learnPath_module_id'));

    // look for maxDeep
    $maxDeep = 1; // used to compute colspan of <td> cells
    for ($i = 0 ; $i < sizeof($flatElementList) ; $i++)
    {
        if ($flatElementList[$i]['children'] > $maxDeep) $maxDeep = $flatElementList[$i]['children'] ;
    }
    
    $out = '';
    
    $out .= "\n".'<table class="claroTable" width="100%"  border="0" cellspacing="2">'."\n\n"
    .    '<thead>'."\n"
    .    '<tr class="headerX" align="center" valign="top">'."\n"
    .    '<th colspan="' . ($maxDeep+1).'">' . get_lang('Module') . '</th>'."\n"
    .    '</tr>'."\n\n"
    .    '</thead>'."\n"
    .    '<tbody>'."\n"
    ;

    foreach ($flatElementList as $module)
    {
        $spacingString = '';
        for($i = 0; $i < $module['children']; $i++)
            $spacingString .= '<td width="5" >&nbsp;</td>' . "\n";
        $colspan = $maxDeep - $module['children'] + 1;

        $out .= '<tr align="center" ' . $style . '>' . "\n"
        .    $spacingString
        .    '<td colspan="' . $colspan . '" align="left">'
        ;

        if (CTLABEL_ == $module['contentType']) // chapter head
        {
            $out .= '<b>' . $module['name'] . '</b>';
        }
        else // module
        {
            if(CTEXERCISE_ == $module['contentType'] )
                $moduleImg = get_icon_url('quiz', 'CLQWZ');
            else
                $moduleImg = get_icon_url(choose_image(basename($module['path'])));

            $contentType_alt = selectAlt($module['contentType']);

            $out .= '<img src="' . $moduleImg . '" alt="' .$contentType_alt.'" /> '
            .    $module['name']
            ;
        }
        $out .= '</td>' . "\n"
        .     '</tr>' . "\n\n"
        ;
    }
    $out .= '</tbody>' . "\n\n"
    .     '</table>' . "\n\n"
    ;
    
    return $out;
}

/**
 * Compute the progression into the $lpid learning path in pourcent
 *
 * @param $lpid id of the learning path
 * @param $lpUid user id
 *
 * @return integer percentage of progression os user $mpUid in the learning path $lpid
 */
function get_learnPath_progress($lpid, $lpUid)
{
    $tbl_cdb_names = claro_sql_get_course_tbl();
    $tbl_lp_learnPath            = $tbl_cdb_names['lp_learnPath'];
    $tbl_lp_rel_learnPath_module = $tbl_cdb_names['lp_rel_learnPath_module'];
    $tbl_lp_user_module_progress = $tbl_cdb_names['lp_user_module_progress'];
    $tbl_lp_module               = $tbl_cdb_names['lp_module'];

    // find progression for this user in each module of the path

    $sql = "SELECT UMP.`raw` AS R, UMP.`scoreMax` AS SMax, M.`contentType` AS CTYPE, UMP.`lesson_status` AS STATUS
             FROM `" . $tbl_lp_learnPath . "` AS LP,
                  `" . $tbl_lp_rel_learnPath_module . "` AS LPM,
                  `" . $tbl_lp_user_module_progress . "` AS UMP,
                  `" . $tbl_lp_module . "` AS M
            WHERE LP.`learnPath_id` = LPM.`learnPath_id`
              AND LPM.`learnPath_module_id` = UMP.`learnPath_module_id`
              AND UMP.`user_id` = " . (int) $lpUid . "
              AND LP.`learnPath_id` = " . (int) $lpid . "
              AND LPM.`visibility` = 'SHOW'
              AND M.`module_id` = LPM.`module_id`
              AND M.`contentType` != '" . CTLABEL_ . "'";

    $modules = claro_sql_query_fetch_all($sql);

    $progress = 0;
    if( !is_array($modules) || empty($modules) )
    {
        $progression = 0;
    }
    else
    {
        // progression is calculated in pourcents
        foreach( $modules as $module )
        {
            if( $module['SMax'] <= 0 )
            {
                $modProgress = 0 ;
            }
            else
            {
                $raw = min($module['R'],$module['SMax']);
                $modProgress = @round($raw/$module['SMax']*100);
            }

            // in case of scorm module, progression depends on the lesson status value
            if (($module['CTYPE']=="SCORM") && ($module['SMax'] <= 0) && (( $module['STATUS'] == 'COMPLETED') || ($module['STATUS'] == 'PASSED')))
            {
                $modProgress = 100;
            }

            if ($modProgress >= 0)
            {
                $progress += $modProgress;
            }
        }
        // find number of visible modules in this path
        $sqlnum = "SELECT COUNT(M.`module_id`)
                    FROM `" . $tbl_lp_rel_learnPath_module . "` AS LPM,
                          `". $tbl_lp_module . "` AS M
                    WHERE LPM.`learnPath_id` = " . (int) $lpid . "
                    AND LPM.`visibility` = 'SHOW'
                    AND M.`contentType` != '" . CTLABEL_ . "'
                    AND M.`module_id` = LPM.`module_id`
                    ";
        $nbrOfVisibleModules = claro_sql_query_get_single_value($sqlnum);

        if( is_numeric($nbrOfVisibleModules) && $nbrOfVisibleModules > 0)
              $progression = @round($progress/$nbrOfVisibleModules);
        else
            $progression = 0;

    }
    return $progression;
}
/**
 * This function displays the list of available exercises in this course
 * With the form to add a selected exercise in the learning path
 *
 * @param string $dialogBox Error or confirmation text
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 * @author Lederer Guillaume <led@cerdecam.be>
 */
function display_my_exercises($dialogBox)
{
    $tbl_cdb_names = claro_sql_get_course_tbl();
    $tbl_quiz_exercise = $tbl_cdb_names['qwz_exercise'];

    $out = '';
    
    $out .= '<!-- display_my_exercises output -->' . "\n";
    /*--------------------------------------
    DIALOG BOX SECTION
    --------------------------------------*/
    $colspan = 4;
    if( !empty($dialogBox) )
    {
        $_dialogBox = new DialogBox();
        $_dialogBox->form( $dialogBox );
        $out .= $_dialogBox->render();
    }
    
    $out .= '<form method="post" name="addmodule" action="' . $_SERVER['PHP_SELF'] . '?cmdglobal=add">'."\n";
    
    $out .= '<table class="claroTable emphaseLine">'."\n\n"
    .    '<thead>'
    .    '<tr align="center" valign="top">'."\n"
    .    '<th width="10%">'
    .    get_lang('Add module(s)')
    .    '</th>'."\n"
    .    '<th>'
    .    get_lang('Exercises')
    .    '</th>'."\n"
    .    '</tr>'."\n"
    .    '</thead>'."\n\n";
    
    // Display available modules
    $atleastOne = false;
    $sql = "SELECT `id`, `title`, `description`
            FROM `" . $tbl_quiz_exercise . "`
            ORDER BY  `title`, `id`";
    $exercises = claro_sql_query_fetch_all($sql);

    if( is_array($exercises) && !empty($exercises) )
    {
        $out .= '<tbody>' . "\n\n";
        
        foreach ( $exercises as $exercise )
        {
            $out .= '<tr>'."\n"
            .    '<td style="vertical-align:top; text-align: center;">'
            .    '<input type="checkbox" name="check_' . $exercise['id'] . '" id="check_' . $exercise['id'] . '" value="' . $exercise['id'] . '" />'
            .    '</td>'."\n"
            .    '<td>'
            .    '<label for="check_'.$exercise['id'].'" >'
            .    '<img src="' . get_icon_url('quiz', 'CLQWZ') . '" alt="" /> '
            .    $exercise['title']
            .    '</label>'
            .    (!empty($exercise['description']) ? '<div class="comment">' . claro_parse_user_text($exercise['description']) . '</div>' : '')
            .    '</td>'."\n"
            .    '</tr>'."\n\n"
            ;
            
            $atleastOne = true;
        }//end while another module to display
        
        $out .= '</tbody>'."\n\n";
    }

    if( !$atleastOne )
    {
        $out .= '<tr>'."\n"
        .     '<td colspan="2" align="center">'
        .     get_lang('There is no exercise for the moment')
        .     '</td>'."\n"
        .     '</tr>'."\n\n"
        ;
    }
    
    $out .= '</table>'."\n\n";

    // Display button to add selected modules
    if( $atleastOne )
    {
        $out .= '<input type="submit" name="insertExercise" value="'.get_lang('Add selection').'" />';
    }
    
    $out .= '</form><br /><br />'."\n\n"
    .    '<!-- end of display_my_exercises output -->' . "\n"
    ;
    
    return $out;
}

/**
  * This function is used to display the list of document available in the course
  * It also displays the form used to add selected document in the learning path
  *
  * @param string $dialogBox Error or confirmation text
  * @return nothing
  * @author Piraux S�bastien <pir@cerdecam.be>
  * @author Lederer Guillaume <led@cerdecam.be>
  */

function display_my_documents($dialogBox)
{
    global $is_allowedToEdit;

    global $curDirName;
    global $curDirPath;
    global $parentDir;

    global $fileList;
    
    /**
     * DISPLAY
     */
    
    $out = '';
    
    $out .= '<!-- display_my_documents output -->' . "\n";

    $dspCurDirName = claro_htmlspecialchars($curDirName);
    $cmdCurDirPath = rawurlencode($curDirPath);
    $cmdParentDir  = rawurlencode($parentDir);

    $out .= '<br />'
    .    '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';

    /*--------------------------------------
    DIALOG BOX SECTION
    --------------------------------------*/
    $colspan = 4;
    if( !empty($dialogBox) )
    {
        $_dialogBox = new DialogBox();
        $_dialogBox->form( $dialogBox );
        $out .= $_dialogBox->render();
    }
    /*--------------------------------------
    CURRENT DIRECTORY LINE
    --------------------------------------*/

    /* GO TO PARENT DIRECTORY */
    if ($curDirName) /* if the $curDirName is empty, we're in the root point
    and we can't go to a parent dir */
    {
        $out .= '<a href="' . $_SERVER['PHP_SELF'] . '?cmd=exChDir&amp;file=' . $cmdParentDir . '">' . "\n"
        .    '<img src="' . get_icon_url('parent') . '" hspace="5" alt="" /> '."\n"
        .    '<small>' . get_lang('Up') . '</small>' . "\n"
        .    '</a>' . "\n";
    }
    /* CURRENT DIRECTORY */
    $out .= '<table class="claroTable emphaseLine">'
          . '<thead>';
    
    // If the $curDirName is empty, we're in the root point
    // and there is'nt a dir name to display
    if ( $curDirName )
    {
        $out .= '<!-- current dir name -->' . "\n"
        .    '<tr>' . "\n"
        .    '<th class="superHeader" colspan="' . $colspan . '" align="left">'. "\n"
        .    '<img src="' . get_icon_url('opendir') . '" vspace=2 hspace=5 alt="" /> ' . "\n"
        .    $dspCurDirName . "\n"
        .    '</td>' . "\n"
        .    '</tr>' . "\n";
    }
    
    $out .= '<tr align="center" valign="top">' . "\n"
    .    '<th width="10%">' . get_lang('Add module(s)') . '</th>' . "\n"
    .    '<th>' . get_lang('Name') . '</th>' . "\n"
    .    '<th>' . get_lang('Size') . '</th>' . "\n"
    .    '<th>' . get_lang('Date') . '</th>' . "\n"
    .    '</tr>'
    .    '</thead>'
    .    '<tbody>' . "\n";


    /*--------------------------------------
    DISPLAY FILE LIST
    --------------------------------------*/

    if ( $fileList )
    {
        $iterator = 0;

        while ( list( $fileKey, $fileName ) = each ( $fileList['name'] ) )
        {

            $dspFileName = claro_htmlspecialchars($fileName);
            $cmdFileName = str_replace("%2F","/",rawurlencode($curDirPath."/".$fileName));

            if ($fileList['visibility'][$fileKey] == "i")
            {
                if ($is_allowedToEdit)
                {
                    $style = ' class="invisible"';
                }
                else
                {
                    $style = "";
                    continue; // skip the display of this file
                }
            }
            else
            {
                $style="";
            }

            if ($fileList['type'][$fileKey] == A_FILE)
            {
                $image       = choose_image($fileName);
                $size        = format_file_size($fileList['size'][$fileKey]);
                $date        = format_date($fileList['date'][$fileKey]);

                if ( $GLOBALS['is_Apache'] && get_conf('secureDocumentDownload') )
                {
                    // slash argument method - only compatible with Apache
                    $doc_url = $cmdFileName;
                }
                else
                {
                    // question mark argument method, for IIS ...
                    $doc_url = '?url=' . $cmdFileName;
                }

                $urlFileName = get_path('clarolineRepositoryWeb') . 'backends/download.php'.$doc_url;
            }
            elseif ($fileList['type'][$fileKey] == A_DIRECTORY)
            {
                $image       = 'folder';
                $size        = '&nbsp;';
                $date        = '&nbsp;';
                $urlFileName = $_SERVER['PHP_SELF'] . '?openDir=' . $cmdFileName;
            }

            $out .= '<tr ' . $style . '>'."\n";

            if ($fileList['type'][$fileKey] == A_FILE)
            {
                $iterator++;
                $out .= '<td style="vertical-align:top; text-align: center;">'
                .    '<input type="checkbox" name="insertDocument_' . $iterator . '" id="insertDocument_' . $iterator . '" value="' . $curDirPath . "/" . $fileName . '" />'
                .    '</td>' . "\n"
                ;

            }
            else
            {
                $out .= '<td>&nbsp;</td>';
            }
            $out .= '<td>'
            .    '<a href="' . $urlFileName . '" ' . $style . '>'
            .    '<img src="' . get_icon_url( $image ) . '" hspace="5" alt="" /> ' . $dspFileName . '</a>';
            
            // Comments
            if ($fileList['comment'][$fileKey] != "" )
            {
                $fileList['comment'][$fileKey] = claro_htmlspecialchars($fileList['comment'][$fileKey]);
                $fileList['comment'][$fileKey] = claro_parse_user_text($fileList['comment'][$fileKey]);
                
                $out .= '<div class="comment">'
                      . $fileList['comment'][$fileKey]
                      . '</div>'."\n";
            }
            
            $out .= '</td>'."\n"
                  . '<td><small>' . $size . '</small></td>' . "\n"
                  . '<td><small>' . $date . '</small></td>' . "\n";

            /* NB : Before tracking implementation the url above was simply
            * "<a href=\"",$urlFileName,"\"",$style,">"
            */


            $out .= '</tr>' . "\n";
        }  // end each ($fileList)
        
        // form button
        $out .= '</tbody>'
              . '</table>'."\n\n"
              . '<input type="hidden" name="openDir" value="'.$curDirPath.'" />'."\n"
              . '<input type="hidden" name="maxDocForm" value ="'.$iterator.'" />'."\n"
              . '<input type="submit" name="submitInsertedDocument" value="'.get_lang('Add selection').'" />';
    } // end if ( $fileList)
    else
    {
        $out .= '<tr>'."\n"
        .     '<td colspan="2" align="center">'
        .    get_lang('There is no document for the moment')
        .    '</td>'."\n"
        .    '</tr>'."\n"
        .    '</tbody>'
        .    '</table>'."\n\n";
    }

    $out .= '</form>'."\n"
          . '<br /><br />'
          . '<!-- end of display_my_documents output -->'."\n";
    
    return $out;
}

/**
 * Recursive Function used to find the deep of a module in a learning path
 * DEPRECATED : no more since the display has been reorganised
 *
 * @param integer $id id_of_module that we are looking for deep
 * @param array $searchInarray of parents of modules in a learning path $searchIn[id_of_module] = parent_of_this_module
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 */
function find_deep($id, $searchIn)
{
    if ( $searchIn[$id] == 0 || !isset($searchIn[$id]) && $id == $searchIn[$id])
    return 0;
    else
    return find_deep($searchIn[$id],$searchIn) + 1;
}

/**
 * Build an tree of $list from $id using the 'parent'
 * table. (recursive function)
 * Rows with a father id not existing in the array will be ignored
 *
 * @param $list modules of the learning path list
 * @param $paramField name of the field containing the parent id
 * @param $idField name of the field containing the current id
 * @param $id learnPath_module_id of the node to build
 * @return tree of the learning path
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 */
function build_element_list($list, $parentField, $idField, $id = 0)
{
    $tree = array();

    if(is_array($list))
    {
        foreach ($list as $element)
        {
            if( $element[$idField] == $id )
            {
                $tree = $element; // keep all $list informations in the returned array
                // explicitly add 'name' and 'value' for the claro_build_nested_select_menu function
                //$tree['name'] = $element['name']; // useless since 'name' is the same word in db and in the  claro_build_nested_select_menu function
                $tree['value'] = $element[$idField];
                break;
            }
        }

        foreach ($list as $element)
        {
            if($element[$parentField] == $id && ( $element[$parentField] != $element[$idField] ))
            {
                if($id == 0)
                {
                    $tree[] = build_element_list($list, $parentField, $idField, $element[$idField]);
                }
                else
                {
                    $tree['children'][] = build_element_list($list, $parentField, $idField, $element[$idField]);
                }
            }
        }
    }
    return $tree;
}

/**
 * return a flattened tree of the modules of a learnPath after having add
 * 'up' and 'down' fields to let know if the up and down arrows have to be
 * displayed. (recursive function)
 *
 * @param $elementList a tree array as one returned by build_element_list
 * @param $deepness
 * @return array containing infos of the learningpath, each module is an element
    of this array and each one has 'up' and 'down' boolean and deepness added in
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 */
function build_display_element_list($elementList, $deepness = 0)
{
    $count = 0;
    $first = true;
    $last = false;
    $displayElementList = array();

    foreach($elementList as $thisElement)
    {
        $count++;

        // temporary save the children before overwritten it
        if (isset($thisElement['children']))
        $temp = $thisElement['children'];
        else
        $temp = NULL; // re init temp value if there is nothing to put in it

        // we use 'children' to calculate the deepness of the module, it will be displayed
        // using a spacing multiply by deepness
        $thisElement['children'] = $deepness;

        //--- up and down arrows displayed ?
        if ($count == count($elementList) )
        $last = true;

        $thisElement['up'] = $first ? false : true;
        $thisElement['down'] = $last ? false : true;

        //---
        $first = false;

        $displayElementList[] = $thisElement;

        if ( isset( $temp ) && sizeof( $temp ) > 0 )
        {
            $displayElementList = array_merge( $displayElementList,
            build_display_element_list($temp, $deepness + 1 ) );
        }
    }
    return  $displayElementList;
}

/**
 * This function set visibility for all the nodes of the tree module_tree
 *
 * @param $module_tree tree of modules we want to change the visibility
 * @param $visibility ths visibility string as requested by the DB
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 */
function set_module_tree_visibility($module_tree, $visibility)
{
    $tbl_cdb_names = claro_sql_get_course_tbl();
    $tbl_lp_rel_learnPath_module = $tbl_cdb_names['lp_rel_learnPath_module'];

    foreach($module_tree as $module)
    {
        if($module['visibility'] != $visibility)
        {
            $sql = "UPDATE `" . $tbl_lp_rel_learnPath_module . "`
                        SET `visibility` = '" . claro_sql_escape($visibility) . "'
                        WHERE `learnPath_module_id` = " . (int) $module['learnPath_module_id'] . "
                          AND `visibility` != '" . claro_sql_escape($visibility) . "'";
            claro_sql_query ($sql);
        }
        if (isset($module['children']) && is_array($module['children']) ) set_module_tree_visibility($module['children'], $visibility);
    }
}

/**
 * This function deletes all the nodes of the tree module_tree
 *
 * @param $module_tree tree of modules we want to change the visibility
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 */
function delete_module_tree($module_tree)
{
    $tbl_cdb_names = claro_sql_get_course_tbl();
    $tbl_lp_rel_learnPath_module = $tbl_cdb_names['lp_rel_learnPath_module'];
    $tbl_lp_user_module_progress = $tbl_cdb_names['lp_user_module_progress'];
    $tbl_lp_module               = $tbl_cdb_names['lp_module'];
    $tbl_lp_asset                = $tbl_cdb_names['lp_asset'];

    foreach($module_tree as $module)
    {
        switch($module['contentType'])
        {
            case CTSCORM_ :
                // delete asset if scorm
                $delAssetSql = "DELETE
                                    FROM `".$tbl_lp_asset."`
                                    WHERE `module_id` =  ". (int)$module['module_id']."
                                    ";
                claro_sql_query($delAssetSql);
                // no break; because we need to delete modul
            case CTLABEL_ : // delete module if scorm && if label
                $delModSql = "DELETE FROM `" . $tbl_lp_module . "`
                                     WHERE `module_id` =  ". (int)$module['module_id'];
                claro_sql_query($delModSql);
                // no break; because we need to delete LMP and UMP
            default : // always delete LPM and UMP
                claro_sql_query("DELETE FROM `" . $tbl_lp_rel_learnPath_module . "`
                                        WHERE `learnPath_module_id` = " . (int)$module['learnPath_module_id']);
                claro_sql_query("DELETE FROM `" . $tbl_lp_user_module_progress . "`
                                        WHERE `learnPath_module_id` = " . (int)$module['learnPath_module_id']);

                break;
        }
    }
    if ( isset($module['children']) &&  is_array($module['children']) ) delete_module_tree($module['children']);
}
/**
 * This function return the node with $module_id (recursive)
 *
 *
 * @param $lpModules array the tree of all modules in a learning path
 * @param $iid node we are looking for
 * @param $field type of node we are looking for (learnPath_module_id, module_id,...)
 *
 * @return array the requesting node (with all its children)
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 */
function get_module_tree( $lpModules , $id, $field = 'module_id')
{
    foreach( $lpModules as $module)
    {
        if( $module[$field] == $id)
        {
            return $module;
        }
        elseif ( isset($module['children']) && is_array($module['children']) )
        {
            $temp = get_module_tree($module['children'], $id, $field);
            if( is_array($temp) )
            return $temp;
            // else check next node
        }

    }
}

/**
 * Convert the time recorded in seconds to a scorm type
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 * @param $time time in seconds to convert to a scorm type time
 * @return string compatible scorm type (smaller format)
 */
function seconds_to_scorm_time($time)
{
    $hours     = floor( $time / 3600 );
    if( $hours < 10 )
    {
        $hours = "0".$hours;
    }
    $min     = floor( ( $time -($hours * 3600) ) / 60 );
    if( $min < 10)
    {
        $min = '0' . $min;
    }
    $sec    = $time - ($hours * 3600) - ($min * 60);
    if($sec < 10)
    {
        $sec = '0' . $sec;
    }

    return     $hours . ':' . $min . ':' . $sec;
}
/**
  * This function allow to see if a time string is the SCORM requested format : hhhh:mm:ss.cc
  *
  * @param $time a suspected SCORM time value, returned by the javascript API
  *
  * @author Lederer Guillaume <led@cerdecam.be>
  */
function isScormTime($time)
{
    $mask = "/^[0-9]{2,4}:[0-9]{2}:[0-9]{2}.?[0-9]?[0-9]?$/";
    if (preg_match($mask,$time))
     {
       return true;
     }

    return false;
}

 /**
  * This function allow to add times saved in the SCORM requested format : hhhh:mm:ss.cc
  *
  * @param $time1 a suspected SCORM time value, total_time,  in the API
  * @param $time2 a suspected SCORM time value, session_time to add, in the API
  *
  * @author Lederer Guillaume <led@cerdecam.be>
  *
  */
function addScormTime($time1, $time2)
{
       if (isScormTime($time2))
    {
          //extract hours, minutes, secondes, ... from time1 and time2

          $mask = "/^([0-9]{2,4}):([0-9]{2}):([0-9]{2}).?([0-9]?[0-9]?)$/";

          preg_match($mask,$time1, $matches);
          $hours1 = $matches[1];
          $minutes1 = $matches[2];
          $secondes1 = $matches[3];
          $primes1 = $matches[4];

          preg_match($mask,$time2, $matches);
          $hours2 = $matches[1];
          $minutes2 = $matches[2];
          $secondes2 = $matches[3];
          $primes2 = $matches[4];

          // calculate the resulting added hours, secondes, ... for result

          $primesReport = false;
          $secondesReport = false;
          $minutesReport = false;
          $hoursReport = false;

        //calculate primes

          if ($primes1 < 10) {$primes1 = $primes1*10;}
          if ($primes2 < 10) {$primes2 = $primes2*10;}
          $total_primes = $primes1 + $primes2;
          if ($total_primes >= 100)
          {
            $total_primes -= 100;
            $primesReport = true;
          }

        //calculate secondes

          $total_secondes = $secondes1 + $secondes2;
          if ($primesReport) {$total_secondes ++;}
          if ($total_secondes >= 60)
          {
            $total_secondes -= 60;
            $secondesReport = true;
          }

        //calculate minutes

          $total_minutes = $minutes1 + $minutes2;
          if ($secondesReport) {$total_minutes ++;}
          if ($total_minutes >= 60)
          {
            $total_minutes -= 60;
            $minutesReport = true;
          }

        //calculate hours

          $total_hours = $hours1 + $hours2;
          if ($minutesReport) {$total_hours ++;}
          if ($total_hours >= 10000)
          {
            $total_hours -= 10000;
            $hoursReport = true;
          }

        // construct and return result string

          if ($total_hours < 10) {$total_hours = "0" . $total_hours;}
          if ($total_minutes < 10) {$total_minutes = "0" . $total_minutes;}
          if ($total_secondes < 10) {$total_secondes = "0" . $total_secondes;}

        $total_time = $total_hours . ":" . $total_minutes . ":" . $total_secondes;
        // add primes only if != 0
        if ($total_primes != 0) {$total_time .= "." . $total_primes;}
        return $total_time;
       }
       else
       {
        return $time1;
    }
}


function delete_exercise_asset($exerciseId)
{
        $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued());
        $tbl_lp_module = $tbl_cdb_names['lp_module'];
        $tbl_lp_asset = $tbl_cdb_names['lp_asset'];
        $tbl_lp_rel_learnPath_module = $tbl_cdb_names['lp_rel_learnPath_module'];
        $tbl_lp_user_module_progress = $tbl_cdb_names['lp_user_module_progress'];

        // get id of all item to delete
        $sql = "SELECT `A`.`asset_id`, `M`.`module_id`,`LPM`.`learnPath_module_id`
                FROM `".$tbl_lp_asset."` AS `A`, `".$tbl_lp_module."` AS `M`,
                    `".$tbl_lp_rel_learnPath_module."` AS `LPM`
                WHERE `A`.`path` = '".$exerciseId."'
                 AND `A`.`asset_id` = `M`.`startAsset_id`
                 AND `M`.`module_id` = `LPM`.`module_id`";

        $deleteItemList = claro_sql_query_fetch_all($sql);

        if( is_array($deleteItemList) && !empty($deleteItemList) )
        {
            foreach( $deleteItemList as $row )
            {
                if( isset($row['asset_id']) ) $assetList[] = $row['asset_id'];
                if( isset($row['module_id']) ) $moduleList[] = $row['module_id'];
                if( isset($row['learnPath_module_id']) ) $learnPathModuleList[] = $row['learnPath_module_id'];
            }
            // remove doubled values
            $assetList = array_unique($assetList);
            $moduleList = array_unique($moduleList);
            $learnPathModuleList = array_unique($learnPathModuleList);

            // we should now have a list for each ressource type, build delete queries
            if( is_array($assetList) && !empty($assetList) )
            {
                $sql = "DELETE
                        FROM `".$tbl_lp_asset."`
                        WHERE `asset_id` IN (".implode(',',$assetList).")";

                if( claro_sql_query($sql) == false ) return false;
            }

            if( is_array($moduleList) && !empty($moduleList) )
            {
                $sql = "DELETE
                        FROM `".$tbl_lp_module."`
                        WHERE `module_id` IN (".implode(',',$moduleList).")";

                if( claro_sql_query($sql) == false ) return false;
            }

            if( is_array($learnPathModuleList) && !empty($learnPathModuleList) )
            {
                $sql = "DELETE
                        FROM `".$tbl_lp_rel_learnPath_module."`
                        WHERE `learnPath_module_id` IN (".implode(',',$learnPathModuleList).")";

                if( claro_sql_query($sql) == false ) return false;

                // and the user progression

                $sql = "DELETE
                        FROM `".$tbl_lp_user_module_progress."`
                        WHERE `learnPath_module_id` IN (".implode(',',$learnPathModuleList).")";

                if( claro_sql_query($sql) == false ) return false;

            }
        }
        else
        {
            return false;
        }

        return true;
}

/**
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @param $pathId integer id of a learnPath
 * @return boolean true if learnpath is blocked, false instead
 *
 **/

function is_learnpath_accessible( $pathId )
{
    $blocked = false;
    
    $tbl_cdb_names = claro_sql_get_course_tbl();
    $tbl_lp_learnPath            = $tbl_cdb_names['lp_learnPath'           ];
    $tbl_lp_rel_learnPath_module = $tbl_cdb_names['lp_rel_learnPath_module'];
    $tbl_lp_user_module_progress = $tbl_cdb_names['lp_user_module_progress'];
    $tbl_lp_module               = $tbl_cdb_names['lp_module'              ];
    $tbl_lp_asset                = $tbl_cdb_names['lp_asset'               ];
    
    // select all the LP upper than this one
    $sql = "SELECT `rank`, `visibility` FROM `".$tbl_lp_learnPath."` WHERE `learnPath_id` = ".(int) $pathId." LIMIT 1";
    $path = claro_sql_query_fetch_single_row( $sql );
    if( $path['visibility'] == 'HIDE' )
    {
        $blocked = true;
    }
    else
    {
        $sql = "SELECT `learnPath_id`, `lock`, `visibility` FROM `".$tbl_lp_learnPath."` WHERE `rank` < ".(int) $path['rank']." ORDER BY `rank` DESC";
        $upperPaths = claro_sql_query_fetch_all_rows( $sql );
        
        // get the first blocked LP
        $upperBlockId = 0;
        $upperLock = 'OPEN';
        foreach( $upperPaths as $upperPath )
        {
            if(strtolower($upperPath['lock']) == 'close')
            {
                $upperBlockId = $upperPath['learnPath_id'];
                $upperLock = $upperPath['lock'];
                break;
            }
        }
        
        if( !empty( $upperBlockId ) )
        {
            
            // step 1. find last visible module of the current learning path in DB
 
             $blocksql = "SELECT `learnPath_module_id`
                          FROM `".$tbl_lp_rel_learnPath_module."`
                          WHERE `learnPath_id`=". (int) $upperBlockId."
                          AND `visibility` = \"SHOW\"
                          ORDER BY `rank` DESC
                          LIMIT 1
                         ";
     
             $listblock = claro_sql_query_fetch_single_row($blocksql);
             
             // step 2. see if there is a user progression in db concerning this module of the current learning path
             if( $listblock && is_array($listblock) && count($listblock) )
             {
                 
                 $blocksql2 = "SELECT `credit`
                           FROM `".$tbl_lp_user_module_progress."`
                           WHERE `learnPath_module_id`=". (int)$listblock['learnPath_module_id']."
                           AND `user_id`='". (int) claro_get_current_user_id()."'
                          ";
                 
                 $resultblock2 = claro_sql_query($blocksql2);
                 $moduleNumber = mysql_num_rows($resultblock2);
             }
             else
             {
                 $moduleNumber = 0;
             }
             
             if ($moduleNumber!=0)
             {
                 $listblock2 = mysql_fetch_array($resultblock2);
     
                 if (($listblock2['credit']=="NO-CREDIT") && ($upperLock == 'CLOSE'))
                 {
                     $blocked = true;
                 }
             }
             elseif( $moduleNumber == 0 && $upperLock == 'CLOSE' )
             {
                 $blocked = true;
             }
            
        }
    }
    
    
    return $blocked;
}

function getModuleProgression($user_id = 0, $learnPath_id = 0 ,$learnPath_module_id = 0)
{
    if(empty($user_id) || empty($learnPath_module_id) || empty($learnPath_id))
    {
        return false;
    }
    
    $courseTableList = get_module_course_tbl(array('lp_user_module_progress'));
    
    $sql =  'SELECT * ' . 
            ' FROM `' . $courseTableList['lp_user_module_progress'] . '`' . 
            ' WHERE learnPath_module_id = ' . (int) $learnPath_module_id .
            ' AND learnPath_id = ' . (int) $learnPath_id .
            ' AND user_id = ' . (int) $user_id;
    
    return claro_sql_query_fetch_single_row($sql);
}

function getModuleProgressionList($user_id = 0, $learnPath_id = 0)
{
    if(empty($user_id) || empty($learnPath_id))
    {
        return false;
    }
    
    $courseTableList = get_module_course_tbl(array('lp_user_module_progress'));
    
    $sql =  'SELECT * ' . 
            ' FROM `' . $courseTableList['lp_user_module_progress'] . '`' .
            ' WHERE learnPath_id = ' . (int) $learnPath_id .
            ' AND user_id = ' . (int) $user_id;
            
    return claro_sql_query_fetch_all($sql);
}

function isLearnPathProgressionEmpty($user_id = 0, $learnPath_id = 0)
{
    if(empty($user_id) || empty($learnPath_id))
    {
        return true;
    }
    
    $moduleProgressionList = getModuleProgressionList($user_id, $learnPath_id);
    if(!empty($moduleProgressionList))
    {
        return false;
    }
    
    return true;
}

function resetModuleProgression($user_id = 0, $learnPath_id = 0, $learnPath_module_id = 0)
{
    if(empty($user_id) || empty($learnPath_module_id) || empty($learnPath_id))
    {
        return false;
    }
    
    $courseTableList = get_module_course_tbl(array('lp_user_module_progress'));
    
    $sql =  'DELETE ' . 
            ' FROM `' . $courseTableList['lp_user_module_progress'] . '`' . 
            ' WHERE learnPath_module_id = ' . (int) $learnPath_module_id .
            ' AND learnPath_id = ' . (int) $learnPath_id .
            ' AND user_id = ' . (int) $user_id;
            
    return claro_sql_query($sql);
}

function resetModuleProgressionByPathId($user_id = 0, $learnPath_id = 0)
{
    if(empty($user_id) || empty($learnPath_id))
    {
        return false;
    }
    
    $courseTableList = get_module_course_tbl(array('lp_user_module_progress'));
    
    $sql =  'DELETE ' . 
            ' FROM `' . $courseTableList['lp_user_module_progress'] . '`' . 
            ' WHERE learnPath_id = ' . (int) $learnPath_id .
            ' AND user_id = ' . (int) $user_id;
            
    return claro_sql_query($sql);
}

function copyModuleProgression($user_id_from = 0, $user_id_to = 0, $learnPath_id = 0, $learnPath_module_id = 0, $resetLocation = true)
{
    if(empty($learnPath_id) || empty($learnPath_module_id) || empty($user_id_from) || empty($user_id_to))
    {
        return false;
    }
    
    $newProgression = getModuleProgression($user_id_from,  $learnPath_id, $learnPath_module_id);
    if(!is_array($newProgression))
    {
        return false;
    }

    if($resetLocation)
    {
        $newProgression['lesson_location'] = '!!!EMPTY_LOCATION!!!';
    }
    
    if(!resetModuleProgression($user_id_to, $learnPath_id, $learnPath_module_id) || !updateModuleProgression($user_id_to, $newProgression, $learnPath_id ,$learnPath_module_id))
    {
        return false;
    }
    
    return true;
}

function updateModuleProgression($user_id = 0, $user_progression = array(), $learnPath_id = 0, $learnPath_module_id = 0)
{
    if(empty($learnPath_id) || empty($learnPath_module_id) || empty($user_id) || !is_array($user_progression))
    {
        return false;
    }
    
    $courseTableList = get_module_course_tbl(array('lp_user_module_progress'));
    
    if(!getModuleProgression($user_id, $learnPath_id , $learnPath_module_id))
    {
        $sql = "INSERT INTO `". $courseTableList['lp_user_module_progress'] ."`
                ( `user_id` , `learnPath_id` , `learnPath_module_id`, `suspend_data` )
                VALUES ( " . (int)claro_get_current_user_id() . " , ". (int)$learnPath_id." , ". (int)$learnPath_module_id.", '')";
        if(!claro_sql_query($sql))
        {
            return false;
        }
    }
    
    foreach(array('lesson_location', 'lesson_status', 'entry', 'raw', 'scoreMin', 'scoreMax', 'total_time', 'session_time', 'suspend_data', 'credit') as $key)
    {
        if(!isset($user_progression[$key]))
        {
            $user_progression[$key] = '';
        }
    }
    
    $sql = "UPDATE `".$courseTableList['lp_user_module_progress']."` 
            SET 
                `lesson_location` = '". claro_sql_escape($user_progression['lesson_location']) ."',
                `lesson_status` = '". claro_sql_escape($user_progression['lesson_status']) ."',
                `entry` = '". claro_sql_escape($user_progression['entry']) ."',
                `raw` = '". claro_sql_escape($user_progression['raw']) ."',
                `scoreMin` = '". claro_sql_escape($user_progression['scoreMin']) ."',
                `scoreMax` = '". claro_sql_escape($user_progression['scoreMax']) ."',
                `total_time` = '". claro_sql_escape($user_progression['total_time']) ."',
                `session_time` = '". claro_sql_escape($user_progression['session_time']) ."',
                `suspend_data` = '". claro_sql_escape($user_progression['suspend_data']) ."',
                `credit` = '". claro_sql_escape($user_progression['credit']) ."'
          WHERE `learnPath_module_id` = ". (int)$learnPath_module_id ."
          AND   `learnPath_id` = ". (int)$learnPath_id  . "
          AND   `user_id` = ". (int)$user_id;
          
    return claro_sql_query($sql);
}

function copyLearnPathProgression($user_id_from = 0, $user_id_to = 0, $learnPath_id = 0, $resetLocation = true)
{
    if(empty($learnPath_id) || empty($user_id_from) || empty($user_id_to))
    {
        return false;
    }
    
    $moduleProgressionList = getModuleProgressionList($user_id_from, $learnPath_id);
    if(empty($moduleProgressionList) || !is_array($moduleProgressionList) )
    {
        return false;
    }
    
    if(!resetModuleProgressionByPathId($user_id_to, $learnPath_id))
    {
        return false;
    }
    
    foreach($moduleProgressionList as $moduleProgression)
    {
        if(!copyModuleProgression($user_id_from, $user_id_to, $learnPath_id, $moduleProgression['learnPath_module_id'], $resetLocation))
        {
            return false;
        }
    }
    
    return true;
}
