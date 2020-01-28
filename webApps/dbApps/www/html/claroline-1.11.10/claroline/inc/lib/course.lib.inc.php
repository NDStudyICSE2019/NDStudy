<?php // $Id: course.lib.inc.php 13840 2011-11-23 14:28:18Z abourguignon $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 * 
 * A few functions and helpers dedicated to courses.
 *
 * @version     $Revision: 13840 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     COURSE
 * @author      Claro Team <cvs@claroline.net>
 * @author      Muret Benoit <muret_ben@hotmail.com>
 */


/**
  * Delete a directory.
  *
  * @param string $dir    the directory deleting
  * @return boolean whether success true
  *
  */
function delete_directory($dir)
{
    $deleteOk = true;
    
    $current_dir = opendir($dir);
    
    while($entryname = readdir($current_dir))
    {
        if(is_dir("$dir/$entryname") && ($entryname != "." && $entryname != '..'))
        {
            delete_directory("${dir}/${entryname}");
        }
        elseif($entryname != '.' && $entryname != '..')
        {
            unlink("${dir}/${entryname}");
        }
    }
    
    closedir($current_dir);
    rmdir(${dir}."/");
    return $deleteOk;
}


/**
  * Create a command to create a selectBox with the language.
  *
  * @param string $selected the language selected
  * @return the command to create the selectBox
  * @todo merge this with  claro_disp_select_box
  */
function create_select_box_language($selected=NULL)
{
    $arrayLanguage = language_exists();
    foreach($arrayLanguage as $entries)
    {
        $selectBox .= '<option value="' . $entries . '" ';

        if ($entries == $selected)
            $selectBox .= ' selected ';

        $selectBox .= '>' . $entries;

        global $langNameOfLang;
        if (!empty($langNameOfLang[$entries]) && $langNameOfLang[$entries] != '' && $langNameOfLang[$entries] != $entries)
            $selectBox .= ' - ' . $langNameOfLang[$entries];

        $selectBox .= '</option>' . "\n";
    }

    return $selectBox;
}


/**
  * Return an array with the language.
  *
  * @return an array with the language
  */
function language_exists()
{
    $dirname = get_path('clarolineRepositorySys') . 'lang/';

    if($dirname[strlen($dirname)-1]!='/')
        $dirname.='/';

    //Open the repertoy
    $handle=opendir($dirname);

    //For each reportery in the repertory /lang/
    while ($entries = readdir($handle))
    {
        //If . or .. or CVS continue
        if ($entries=='.' || $entries=='..' || $entries=='CVS')
            continue;

        //else it is a repertory of a language
        if (is_dir($dirname.$entries))
        {
            $arrayLanguage[] = $entries;
        }
    }
    closedir($handle);

    return $arrayLanguage;
}


/**
 * Build the <option> element with categories where we can create/have courses.
 *
 * @param the code of the preselected categorie
 * @param the separator used between a cat and its paretn cat to display in the <select>
 * @return echo all the <option> elements needed for a <select>.
 *
 */
function build_editable_cat_table($selectedCat = null, $separator = "&gt;")
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tbl_category        = $tbl_mdb_names['category'];

    $sql = " SELECT code, code_P, name, canHaveCoursesChild
               FROM `" . $tbl_category . "`
               ORDER BY `name`";
    $result = claro_sql_query($sql);
    // first we get the categories available in DB from the SQL query result in parameter

    while ($myfac = mysql_fetch_array($result))
    {
        $categories[$myfac['code']]['code']   = $myfac['code'];
        $categories[$myfac['code']]['parent'] = $myfac['code_P'];
        $categories[$myfac['code']]['name']   = $myfac['name'];
        $categories[$myfac['code']]['childs'] = $myfac['canHaveCoursesChild'];
    }

    // then we build the table we need : full path of editable cats in an array

    $tableToDisplay = array();
    echo '<select name="faculte" id="faculte">' . "\n";
    foreach ($categories as $cat)
    {
        if ( $cat["childs"] == 'TRUE' )
        {

            echo '<option value="' . $cat['code'] . '"';
            if ($cat["code"]==$selectedCat) echo ' selected ';
            echo '>';
            $tableToDisplay[$cat['code']]= $cat;
            $parentPath  = get_full_path($categories, $cat['code'], $separator);

            $tableToDisplay[$cat['code']]['fullpath'] = $parentPath;
            echo '(' . $tableToDisplay[$cat['code']]['fullpath'] . ') ' . $cat['name'];
            echo '</option>' . "\n";
        }
    }
    echo '</select>' . "\n";

    return $tableToDisplay;
}


/**
 * Build the <option> element with categories where we can create/have courses.
 *
 * @param the code of the preselected categorie
 * @param the separator used between a cat and its paretn cat to display in the <select>
 * @return echo all the <option> elements needed for a <select>.
 */
function claro_get_cat_list()
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tbl_category  = $tbl_mdb_names['category'];

    $sql = " SELECT code, code_P, name, canHaveCoursesChild, treePos
               FROM `" . $tbl_category . "`
               ORDER BY `treePos`";
    return claro_sql_query_fetch_all($sql);
}


/**
 * Recursive function to get the full categories path of a specified categorie.
 *
 * @param table of all the categories, 2 dimension tables, first dimension for cat codes, second for names,
 *  parent's cat code.
 * @param $catcode   string the categorie we want to have its full path from root categorie
 * @param $separator string
 * @return void
 */
function get_full_path($categories, $catcode = NULL, $separator = ' > ')
{
    //Find parent code

    $parent = null;

    foreach ($categories as $currentCat)
    {
        if (( $currentCat['code'] == $catcode))
        {
            $parent       = $currentCat['parent'];
            $childTreePos = $currentCat['treePos']; // for protection anti loop
        }
    }
    // RECURSION : find parent categorie in table
    if ($parent == null)
    {
        return $catcode;
    }

    foreach ($categories as $currentCat)
    {
        if (($currentCat['code'] == $parent))
        {

            if ($currentCat['treePos'] >= $childTreePos ) return claro_failure::set_failure('loop_in_structure');
            if ($parent == $catcode ) return claro_failure::set_failure('loop_in_structure');

            return get_full_path($categories, $parent, $separator)
            .      $separator
            .      $catcode
            ;

        }
    }
}


function claro_get_lang_flat_list()
{
    $language_array = claro_get_language_list();

    // following foreach  build the array of selectable  items
    if(is_array($language_array))
    foreach ($language_array as $languageCode => $this_language)
    {
        $languageLabel = '';
        if (   !empty($this_language['langNameCurrentLang'])
            && $this_language['langNameCurrentLang'] != ''
            && $this_language['langNameCurrentLang'] != $this_language['langNameLocaleLang'])
            $languageLabel  .=  $this_language['langNameCurrentLang'] . ' - ';
        $languageLabel .=  $this_language['langNameLocaleLang'];

        $language_flat_list[ucwords($languageLabel)] = $languageCode;
    }
    asort($language_flat_list);
    return $language_flat_list;
}


/**
 * Return all manager id of a course.
 *
 * @param String course id
 * @return Array array of int
 */
function claro_get_course_manager_id($cid = NULL)
{
    if(is_null($cid))
    {
        if(!claro_is_in_a_course())
        {
            return false;
        }
        
        $cid = claro_get_current_course_id();
    }
    
     $tableName = get_module_main_tbl(array('rel_course_user'));
     
    $sql = "SELECT user_id "
         . "FROM `". $tableName['rel_course_user']."` "
         . "WHERE code_cours='".claro_sql_escape($cid)."' "
         . "AND isCourseManager = 1"
            ;
    
    $result = claro_sql_query_fetch_all_cols($sql);
    
    return $result['user_id'];
    
}


/**
 * Get an icon url according to a course access mode ('public', 'private' or 'platform')
 *
 * @param String label of the access mode for which an icon is asked for
 * @return String the url to the icon
 */
function get_course_access_icon($access)
{
    switch($access)
    {
        case 'private' :
            $iconUrl = get_icon_url('access_locked');
            break;
        case 'platform' :
            $iconUrl = get_icon_url('access_platform');
            break;
        case 'public' :
            $iconUrl = get_icon_url('access_open');
            break;
        default :
            $iconUrl = get_icon_url('course');
    }
    
    return $iconUrl;
}


/**
 * Get an icon url according to a course access mode ('public', 'private' or 'platform')
 *
 * @param String label of the access mode for which an icon is asked for
 * @return String caption
 */
function get_course_access_mode_caption($access)
{
    switch($access)
    {
        case 'private' :
            $caption = get_lang('Access allowed only to course members (people on the course user list)');
            break;
        case 'platform' :
            $caption = get_lang('Access allowed only to platform members (user registered to the platform)');
            break;
        case 'public' :
            $caption = get_lang('Access allowed to anybody (even without login)');
            break;
        default :
            $caption = $access;
    }
    
    return $caption;
}


/**
 * Localize the name of a lang
 *
 * @param String lang name
 * @return String localized lang name
 */
function get_course_locale_lang($language)
{
    $langNameOfLang = get_locale('langNameOfLang');
    
    $localeLang = (!empty($langNameOfLang[$language])) ?
        (ucfirst($langNameOfLang[$language])) :
        (ucfirst($language));
    
    return $localeLang;
}
