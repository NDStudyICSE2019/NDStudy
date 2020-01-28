<?php // $Id: advanced_course_search.php 12969 2011-03-14 14:40:42Z abourguignon $

/**
 * CLAROLINE
 *
 * Offers a multifield search tool for courses.
 *
 * @version     $Revision: 12969 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     COURSE
 * @subpackage  CLADMIN
 * @author      Claro Team <cvs@claroline.net>
 */

$cidReset = true;
$gidReset = true;
$tidReset = true;

require '../inc/claro_init_global.inc.php';

// Security check
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

include_once get_path('incRepositorySys') . '/lib/admin.lib.inc.php';
include_once get_path('incRepositorySys') . '/lib/course.lib.inc.php';
include_once get_path('incRepositorySys') . '/lib/form.lib.php';
include_once get_path('incRepositorySys') . '/lib/clarocategory.class.php';

//declare needed tables
$tbl_mdb_names    = claro_sql_get_main_tbl();
$tbl_course_nodes = $tbl_mdb_names['category'];

// Deal with interbredcrumps  and title variable

ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );
$nameTools = get_lang('Advanced course search');

//--------------------------------------------------------------------------------------------
//  USED SESSION VARIABLES
//--------------------------------------------------------------------------------------------
// clean session of possible previous search information.

unset($_SESSION['admin_course_code'        ]);
unset($_SESSION['admin_course_letter'      ]);
unset($_SESSION['admin_course_search'      ]);
unset($_SESSION['admin_course_intitule'    ]);
unset($_SESSION['admin_course_category'    ]);
unset($_SESSION['admin_course_language'    ]);
unset($_SESSION['admin_course_access'      ]);
unset($_SESSION['admin_course_subscription']);
unset($_SESSION['admin_course_order_crit']);

//retrieve needed parameters from URL to prefill search form

if (isset($_REQUEST['access']))        $access        = $_REQUEST['access'];        else $access       = "all";
if (isset($_REQUEST['subscription']))  $subscription  = $_REQUEST['subscription'];  else $subscription = "all";
if (isset($_REQUEST['visibility']))    $visibility    = $_REQUEST['visibility'];    else $visibility   = "all";
if (isset($_REQUEST['code']))          $code          = $_REQUEST['code'];          else $code         = "";
if (isset($_REQUEST['intitule']))      $intitule      = $_REQUEST['intitule'];      else $intitule     = "";
if (isset($_REQUEST['category']))      $category      = $_REQUEST['category'];      else $category     = "";
if (isset($_REQUEST['searchLang']))    $searchLang    = $_REQUEST['searchLang'];    else $searchLang   = "";

// Search needed info in db to create the right formulaire
$arrayFaculty   = course_category_get_list();
$category_array = ClaroCategory::getAllCategoriesFlat();
$language_list  = claro_get_lang_flat_list();
$language_list  = array_merge(array(get_lang('All') => ''),$language_list);

// Structure the categories array as follow: array(category_label => category_value)
$structuredCatArray = array(get_lang('All') => ''); // Default choice
foreach ($category_array as $category)
{
    $structuredCatArray[$category['path']] = $category['id'];
}

//----------------------------------
// DISPLAY
//----------------------------------

$out = '';

//tool title

$out .= claro_html_tool_title($nameTools . ' : ');

$tpl = new CoreTemplate('advanced_course_search.tpl.php');

$tpl->assign('code', $code);
$tpl->assign('intitule', $intitule);
$tpl->assign('category_array', $structuredCatArray);
$tpl->assign('language_list', $language_list);
$tpl->assign('access', $access);
$tpl->assign('subscription', $subscription);
$tpl->assign('visibility', $visibility);

$out .= $tpl->render();

$claroline->display->body->appendContent($out);

echo $claroline->display->render();

//NEEDED FUNCTION (to be moved in libraries)


/**
 *This function create de select box to choose categories
 *
 * @author  - < Benoît Muret >
 * @param   - elem            array     :     the faculties
 * @param   - father        string    :    the father of the faculty
 * @param    - $editFather    string    :    the faculty editing
 * @param    - $space        string    :    space to the bom of the faculty

 * @return  - void
 *
 * @desc : create de select box categories
 */

function build_select_faculty($elem,$father,$editFather,$space)
{
    if($elem)
    {
        $space.="&nbsp;&nbsp;&nbsp;";
        foreach($elem as $one_faculty)
        {
            if(!strcmp($one_faculty["code_P"],$father))
            {
                echo "<option value=\"".$one_faculty['code']."\" ".
                        ($one_faculty['code']==$editFather?"selected ":"")
                ."> ".$space.$one_faculty['code']." </option>
                ";
                build_select_faculty($elem,$one_faculty["code"],$editFather,$space);
            }
        }
    }
}


/**
 * Return all courses category order by treepos.
 *
 * @return array (id, name, code, idParent, rank, visible, canHaveCoursesChild)
 */
function  course_category_get_list()
{
    $tbl_mdb_names  = claro_sql_get_main_tbl();
    $tbl_category   = $tbl_mdb_names['category'];
    $sql_searchCategory = "
SELECT
    id,
    name,
    code,
    idParent,
    rank,
    visible,
    canHaveCoursesChild
FROM `" . $tbl_category . "`
ORDER BY idParent, rank";

    return claro_sql_query_fetch_all($sql_searchCategory);
}