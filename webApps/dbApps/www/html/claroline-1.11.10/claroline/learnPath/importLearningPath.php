<?php // $Id: importLearningPath.php 14315 2012-11-08 14:51:17Z zefredz $

/**
 * CLAROLINE
 *
 * @version     1.11 $Revision: 14315 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Piraux Sebastien <pir@cerdecam.be>
 * @author      Lederer Guillaume <led@cerdecam.be>
 * @package     CLLNP
 */

function get_xml_charset( $xml )
{
    $regex = '/<\?xml(?:.*?)encoding="([a-zA-Z0-9\-]+)"(?:.*?)\s*\?>/';
    if( preg_match( $regex, $xml, $matches ) )
    {
        return $matches[1];
    }
    else
    {
        return get_conf( 'charset' );
    }
}

/*======================================
       CLAROLINE MAIN
  ======================================*/
$tlabelReq = 'CLLNP';
require '../inc/claro_init_global.inc.php';

$is_allowedToEdit = claro_is_allowed_to_edit();

if (! claro_is_in_a_course() || !claro_is_course_allowed() ) claro_disp_auth_form(true);

if (! $is_allowedToEdit ) claro_die(get_lang('Not allowed'));

ClaroBreadCrumbs::getInstance()->prepend( 
    get_lang('Learning path list'), 
    Url::Contextualize(get_module_url('CLLNP') . '/learningPathList.php') 
);

$nameTools = get_lang('Import a learning path');

$out = '';

/*
* DB tables definition
*/

$tbl_cdb_names = claro_sql_get_course_tbl();
$tbl_lp_learnPath            = $tbl_cdb_names['lp_learnPath'           ];
$tbl_lp_rel_learnPath_module = $tbl_cdb_names['lp_rel_learnPath_module'];
$tbl_lp_user_module_progress = $tbl_cdb_names['lp_user_module_progress'];
$tbl_lp_module               = $tbl_cdb_names['lp_module'              ];
$tbl_lp_asset                = $tbl_cdb_names['lp_asset'               ];

//These tables names still use on library, Need to be change.
$TABLELEARNPATH         = $tbl_lp_learnPath;
$TABLEMODULE            = $tbl_lp_module;
$TABLELEARNPATHMODULE   = $tbl_lp_rel_learnPath_module;
$TABLEASSET             = $tbl_lp_asset;
$TABLEUSERMODULEPROGRESS= $tbl_lp_user_module_progress;


//lib of this tool
include_once get_path('incRepositorySys') . '/lib/learnPath.lib.inc.php';
include_once get_path('incRepositorySys') . '/lib/fileManage.lib.php';
include_once get_path('incRepositorySys') . '/lib/fileUpload.lib.php';
include_once get_path('incRepositorySys') . '/lib/fileDisplay.lib.php';


// error handling
$errorFound = false;

 /*--------------------------------------------------------
      Functions
   --------------------------------------------------------*/

/**
 * Function used by the SAX xml parser when the parser meets a opening tag
 * exemple :
 *          <manifest identifier="samplescorm" version="1.1">
 *      will give
 *          $name == "manifest"
 *          attributes["identifier"] == "samplescorm"
 *          attributes["version"]    == "1.1"
 *
 * @param $parser xml parser created with "xml_parser_create()"
 * @param $name name of the element
 * @param $attributes array with the attributes of the element
 */
function startElement($parser,$name,$attributes)
{
    global $elementsPile;
    global $itemsPile;
    global $manifestData;
    global $flagTag;


    array_push($elementsPile,$name);

    switch ($name)
    {
        case "MANIFEST" :
            if (isset($attributes['XML:BASE'])) $manifestData['xml:base']['manifest'] = $attributes['XML:BASE'];
            break;
        case "RESOURCES" :
            if (isset($attributes['XML:BASE'])) $manifestData['xml:base']['resources'] = $attributes['XML:BASE'];
            $flagTag['type'] = "resources";
            break;
        case "RESOURCE" :
            if ( isset($attributes['ADLCP:SCORMTYPE']) && $attributes['ADLCP:SCORMTYPE'] == 'sco' )
            {
                if (isset($attributes['HREF'])) $manifestData['scos'][$attributes['IDENTIFIER']]['href'] = $attributes['HREF'];
                if (isset($attributes['XML:BASE'])) $manifestData['scos'][$attributes['IDENTIFIER']]['xml:base'] = $attributes['XML:BASE'];
                $flagTag['type'] = "sco";
                $flagTag['value'] = $attributes['IDENTIFIER'];
            }
            elseif(isset($attributes['ADLCP:SCORMTYPE'])&& $attributes['ADLCP:SCORMTYPE'] == 'asset' )
            {
                if (isset($attributes['HREF']))     $manifestData['assets'][$attributes['IDENTIFIER']]['href'] = $attributes['HREF'];
                if (isset($attributes['XML:BASE'])) $manifestData['assets'][$attributes['IDENTIFIER']]['xml:base'] = $attributes['XML:BASE'];
                $flagTag['type'] = "asset";
                if (isset($attributes['IDENTIFIER'])) $flagTag['value'] = $attributes['IDENTIFIER'];
            }
            else // check in $manifestData['items'] if this ressource identifier is used
            {
                foreach ($manifestData['items'] as $itemToCheck )
                {
                    if ( isset($itemToCheck['identifierref']) && $itemToCheck['identifierref'] == $attributes['IDENTIFIER'] )
                    {
                        if (isset($attributes['HREF'])) $manifestData['scos'][$attributes['IDENTIFIER']]['href'] = $attributes['HREF'];

                        if (isset($attributes['XML:BASE'])) $manifestData['scos'][$attributes['IDENTIFIER']]['xml:base'] = $attributes['XML:BASE'];
                    }
                }
            }
            break;

        case "ITEM" :
            if (isset($attributes['IDENTIFIER']))
            {
                   $manifestData['items'][$attributes['IDENTIFIER']]['itemIdentifier'] = $attributes['IDENTIFIER'];

                if (isset($attributes['IDENTIFIERREF'])) $manifestData['items'][$attributes['IDENTIFIER']]['identifierref'] = $attributes['IDENTIFIERREF'];
                if (isset($attributes['PARAMETERS']))    $manifestData['items'][$attributes['IDENTIFIER']]['parameters'] = $attributes['PARAMETERS'];
                if (isset($attributes['ISVISIBLE']))     $manifestData['items'][$attributes['IDENTIFIER']]['isvisible'] = $attributes['ISVISIBLE'];

                if ( count($itemsPile) > 0)
                    $manifestData['items'][$attributes['IDENTIFIER']]['parent'] = $itemsPile[count($itemsPile)-1];

                array_push($itemsPile, $attributes['IDENTIFIER']);

                if ( $flagTag['type'] == "item" )
                {
                    $flagTag['deep']++;
                }
                else
                {
                    $flagTag['type'] = "item";
                    $flagTag['deep'] = 0;
                }
                $manifestData['items'][$attributes['IDENTIFIER']]['deep'] = $flagTag['deep'];
                $flagTag['value'] = $attributes['IDENTIFIER'];
            }
            break;

        case "ORGANIZATIONS" :
            if( isset($attributes['DEFAULT']) ) $manifestData['defaultOrganization'] = $attributes['DEFAULT'];
            else                                $manifestData['defaultOrganization'] = '';
            break;
        case "ORGANIZATION" :
            $flagTag['type'] = "organization";
            $flagTag['value'] = $attributes['IDENTIFIER'];
            break;
        case "ADLCP:LOCATION" :
            // when finding this tag we read the specified XML file so the data structure doesn't even
            // 'see' that this is another file
            // for that we remove this element from the pile so it doesn't appear when we compare the
            // pile with the position of an element
            // $poped = array_pop($elementsPile);
            break;

    }


}

/**
 * Function used by the SAX xml parser when the parser meets a closing tag
 *
 * @param $parser xml parser created with "xml_parser_create()"
 * @param $name name of the element
 */
function endElement($parser,$name)
{
    global $elementsPile;
    global $itemsPile;
    global $flagTag;

    switch($name)
    {
        case "ITEM" :
            array_pop($itemsPile);
            if ( $flagTag['type'] == "item" && $flagTag['deep'] > 0 )
            {
                $flagTag['deep']--;
            }
            else
            {
                $flagTag['type'] = "endItem";
            }
            break;
        case "RESOURCES" :
            $flagTag['type'] = "endResources";
            break;
        case "RESOURCE" :
            $flagTag['type'] = "endResource";
            break;

    }

    array_pop($elementsPile);
}

/**
 * Function used by the SAX xml parser when the parser meets something that's not a tag
 *
 * @param $parser xml parser created with "xml_parser_create()"
 * @param $data "what is not a tag"
 */
function elementData($parser,$data)
{
    global $elementsPile;
    global $itemsPile;
    global $manifestData;
    global $flagTag;
    global $errorFound;
    global $zipFile;
    global $errorMsgs,$okMsgs;
    global $pathToManifest;

    //$data = trim(claro_utf8_decode($data));
    
    if (!isset($data)) $data="";

    switch ( $elementsPile[count($elementsPile)-1] )
    {

        case "RESOURCE" :
            //echo "Resource : ".$data;
            break;
        case "TITLE" :
            // $data == '' (empty string) means that title tag contains elements (<langstring> for an exemple), so it's not the title we need
            if( $data != '' )
            {
                if ( $flagTag['type'] == "item" ) // item title check
                {
                    if (!isset($manifestData['items'][$flagTag['value']]['title'])) $manifestData['items'][$flagTag['value']]['title'] = "";
                    $manifestData['items'][$flagTag['value']]['title'] .= $data;
                }


                // get title of package if it was not find in the manifest metadata in the default organization
                if ( $elementsPile[sizeof($elementsPile)-2]  == "ORGANIZATION" && $flagTag['type'] == "organization" && $flagTag['value'] == $manifestData['defaultOrganization'])
                {
                    // if we do not find this title
                    //  - the metadata title has been set as package title
                    //  - if there was nor title for metadata nor for default organization set 'unnamed path'
                    // If we are here it means we have found the title in organization, this is the best to chose
                    if (!isset($manifestData['packageTitle'])) $manifestData['packageTitle'] = "";
                    $manifestData['packageTitle'] .= $data;
                }
            }
            break;
        case "DESCRIPTION" :
            if( $data != '' )
            {
                if( $elementsPile[sizeof($elementsPile)-2] == "ORGANIZATION" && $flagTag['type'] == "organization" && $flagTag['value'] == $manifestData['defaultOrganization'])
                {
                   $manifestData['packageDesc'] = $data;
                }
            }
            break;
        case "ITEM" :
            break;

        case "ADLCP:DATAFROMLMS" :
            $manifestData['items'][$flagTag['value']]['datafromlms'] = $data;
            break;

        // found a link to another XML file, parse it ...
        case "ADLCP:LOCATION" :
            if (!$errorFound)
            {
                $xml_parser = xml_parser_create();
                xml_set_element_handler($xml_parser, "startElement", "endElement");
                xml_set_character_data_handler($xml_parser, "elementData");

                $file = $data; //url of secondary manifest files is relative to the position of the base imsmanifest.xml

                // we try to extract only the required file
                $unzippingState = $zipFile->extract(PCLZIP_OPT_BY_NAME,$pathToManifest.$file, PCLZIP_OPT_REMOVE_PATH, $pathToManifest);

                if( $unzippingState != 0 && file_exists( $pathToManifest.$file ) )
                {
                    array_push ($okMsgs, get_lang('Secondary manifest found in zip file : ').$pathToManifest.$file );

                    $readData = file_get_contents($pathToManifest.$file);

                    if( !xml_parse($xml_parser, $readData) )
                    {
                        // if reading of the xml file in not successfull :
                        // set errorFound, set error msg, break while statement
                        $errorFound = true;
                        array_push ($errorMsgs, get_lang('Error reading a secondary initialisation file : ').$pathToManifest.$file );
                    }
                }
                else
                {
                    $errorFound = true;
                    array_push ($errorMsgs, get_lang('Cannot find secondary initialisation file in the package.<br /> File not found : ').$pathToManifest.$file );
                }
            }
            break;

        case "LANGSTRING" :
            switch ( $flagTag['type'] )
            {
                case "item" :
                    // DESCRIPTION
                    // if the langstring tag is a children of a description tag
                    if ( $elementsPile[sizeof($elementsPile)-2] == "DESCRIPTION" && $elementsPile[sizeof($elementsPile)-3] == "GENERAL" )
                    {
                        if (!isset($manifestData['items'][$flagTag['value']]['description'])) $manifestData['items'][$flagTag['value']]['description'] = "";
                        $manifestData['items'][$flagTag['value']]['description'] .= $data;
                    }
                    // title found in metadata of an item (only if we haven't already one title for this sco)
                    if( $manifestData['items'][$flagTag['value']]['title'] == '' || !isset( $manifestData['items'][$flagTag['value']]['title'] ) )
                    {
                        if ( $elementsPile[sizeof($elementsPile)-2] == "TITLE" && $elementsPile[sizeof($elementsPile)-3] == "GENERAL" )
                        {
                            $manifestData['items'][$flagTag['value']]['title'] .= $data;
                        }
                    }
                    break;
                case "sco" :
                    // DESCRIPTION
                    // if the langstring tag is a children of a description tag
                    if ( $elementsPile[sizeof($elementsPile)-2] == "DESCRIPTION" && $elementsPile[sizeof($elementsPile)-3] == "GENERAL" )
                    {
                        if (isset($manifestData['scos'][$flagTag['value']]['description'])) $manifestData['scos'][$flagTag['value']]['description'] .= $data;
                        else
                        $manifestData['scos'][$flagTag['value']]['description'] = $data;
                    }
                    // title found in metadata of an item (only if we haven't already one title for this sco)
                    if (!isset($manifestData['scos'][$flagTag['value']]['title']) || $manifestData['scos'][$flagTag['value']]['title'] == '')
                    {
                        if ( $elementsPile[sizeof($elementsPile)-2] == "TITLE" && $elementsPile[sizeof($elementsPile)-3] == "GENERAL" )
                        {
                            $manifestData['scos'][$flagTag['value']]['title'] = $data;
                        }
                    }
                    break;
                case "asset" :
                    // DESCRIPTION
                    // if the langstring tag is a children of a description tag
                    if ( $elementsPile[sizeof($elementsPile)-2] == "DESCRIPTION" && $elementsPile[sizeof($elementsPile)-3] == "GENERAL" )
                    {
                        if (isset($manifestData['assets'][$flagTag['value']]['description']))
                        $manifestData['assets'][$flagTag['value']]['description'] .= $data;
                        else
                        $manifestData['assets'][$flagTag['value']]['description'] = $data;

                    }
                    // title found in metadata of an item (only if we haven't already one title for this sco)
                    if(!isset( $manifestData['assets'][$flagTag['value']]['title'] ) || $manifestData['assets'][$flagTag['value']]['title'] == '')
                    {
                        if ( $elementsPile[sizeof($elementsPile)-2] == "TITLE" && $elementsPile[sizeof($elementsPile)-3] == "GENERAL" )
                        {
                            if (isset($manifestData['assets'][$flagTag['value']]['title']))
                            $manifestData['assets'][$flagTag['value']]['title'] .= $data;
                            else
                            $manifestData['assets'][$flagTag['value']]['title'] = $data;
                        }
                    }
                    break;
                default :
                    // DESCRIPTION
                    $posPackageDesc = array("MANIFEST", "METADATA", "LOM", "GENERAL", "DESCRIPTION");
                    if(compareArrays($posPackageDesc,$elementsPile))
                    {
                        if (!isset($manifestData['packageDesc'])) $manifestData['packageDesc'] = "";
                        $manifestData['packageDesc'] .= $data;
                    }

                    if (!isset($manifestData['packageTitle']) || $manifestData['packageTitle'] == '' )
                    {
                        $posPackageTitle = array("MANIFEST", "METADATA","LOM","GENERAL","TITLE");
                        if (compareArrays($posPackageTitle,$elementsPile))
                        {
                            $manifestData['packageTitle'] = $data;
                        }
                    }
                    break;

            } // end switch ( $flagTag['type'] )

            break;

        default :
            break;
    } // end switch ($elementsPile[count($elementsPile)-1] )

}


/**
 * This function checks in elementpile if the sequence of markup is the same as in array2Compare
 * Checks if the sequence is the same in the begining of pile.
 * If the sequences are the same then it means that the elementdata is the one we were looking for.
 *
 * @param $array1 list xml markups upper than the requesting markup
 * @return true if arrays are the same, false otherwise
 */
function compareArrays($array1, $array2)
{
    // sizeof(array2) so we do not compare the last tag, this is the one we are in, so we not that already.
    for ($i = 0; $i < sizeof($array2)-1; $i++)
    {
        if ( $array1[$i] != $array2[$i] ) return false;
    }
    return true;
}


/*======================================
       CLAROLINE MAIN
  ======================================*/

// main page

$out .= claro_html_tool_title(get_lang('Import a learning path'));

// init msg arays
$okMsgs   = array();
$errorMsgs = array();

$maxFilledSpace = get_conf('maxFilledSpace_for_import', 100000000);

$courseDir   = claro_get_course_path() . '/scormPackages/';
$baseWorkDir = get_path('coursesRepositorySys').$courseDir; // path_id
// handle upload
// if the post is done a second time, the claroformid mecanism
// will set $_POST to NULL, so we need to check it
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !is_null($_POST) )
{

    // arrays used to store inserted ids in case
    // will be used to build delete queries for mysql < 4.0.0
    $insertedModule_id = array();
    $insertedAsset_id = array();

    $lpName = get_lang('Unnamed path');

    // we need a new path_id for this learning path so we prepare a line in DB
    // this line will be removed if an error occurs
    $sql = "SELECT MAX(`rank`)
            FROM `".$TABLELEARNPATH."`";
    $result = claro_sql_query($sql);

    list($rankMax) = mysql_fetch_row($result);

    $sql = "INSERT INTO `".$TABLELEARNPATH."`
            (`name`,`visibility`,`rank`,`comment`)
            VALUES ('". claro_sql_escape( claro_utf8_decode( $lpName, get_conf( 'charset' ) ) ) ."','HIDE',".($rankMax+1).", '')";
    claro_sql_query($sql);

    $tempPathId = claro_sql_insert_id();
    $baseWorkDir .= "path_".$tempPathId;

    if (!is_dir($baseWorkDir)) claro_mkdir($baseWorkDir, CLARO_FILE_PERMISSIONS );

    // unzip package
    include_once get_path('incRepositorySys')."/lib/thirdparty/pclzip/pclzip.lib.php";

    /*
     * Check if the file is valide (not to big and exists)
     */
    if( !isset($_FILES['uploadedPackage']) || !is_uploaded_file($_FILES['uploadedPackage']['tmp_name']))
    {
        $errorFound = true;
        array_push ($errorMsgs, get_lang('The file to upload is not valid.') . '<br />'
                    . get_lang('Max file size : %size', array('%size' => format_file_size( get_max_upload_size($maxFilledSpace,$baseWorkDir) ) ) )
                );

    }

    /*
    * Check the file size doesn't exceed
     * the maximum file size authorized in the directory
     */

    elseif ( ! enough_size($_FILES['uploadedPackage']['size'], $baseWorkDir, $maxFilledSpace))
    {
        $errorFound = true;
        array_push ($errorMsgs, get_lang('The upload has failed. There is not enough space in your directory') ) ;
    }

    /*
     * Unzipping stage
     */

    elseif ( preg_match("/.zip$/i", $_FILES['uploadedPackage']['name']) )
    {
        array_push ($okMsgs, get_lang('File received : %filename', array('%filename' => basename($_FILES['uploadedPackage']['name']) )));

        if(!function_exists('gzopen'))
        {
            $errorFound = true;
            array_push ($errorMsgs,get_lang('Zlib php extension is required to use this tool. Please contact your platform administrator.') );
        }
        else
        {
            $zipFile = new pclZip($_FILES['uploadedPackage']['tmp_name']);
            $is_allowedToUnzip = true ; // default initialisation

            // Check the zip content (real size and file extension)

            $zipContentArray = $zipFile->listContent();

            if ($zipContentArray == 0)
            {
              $errorFound = true;
              array_push ($errorMsgs,get_lang('Error reading zip file.') );
            }

            $pathToManifest  = ""; // empty by default because we can expect that the manifest.xml is in the root of zip file
            $pathToManifestFound = false;
            $realFileSize = 0;

            foreach($zipContentArray as $thisContent)
            {
                if ( preg_match('/.(php[[:digit:]]?|phtml)$/i', $thisContent['filename']) )
                {
                        $errorFound = true;
                        array_push ($errorMsgs, get_lang('The zip file can not contain .PHP files') );
                        $is_allowedToUnzip = false;
                        break;
                }

                if ( strtolower(substr($thisContent['filename'], -15)) == "imsmanifest.xml" )
                {
                    // this check exists to find the less deep imsmanifest.xml in the zip if there are several imsmanifest.xml
                    // if this is the first imsmanifest.xml we found OR path to the new manifest found is shorter (less deep)
                    if ( !$pathToManifestFound
                         || ( count(explode('/', $thisContent['filename'])) < count(explode('/', $pathToManifest."imsmanifest.xml")) )
                       )
                    {
                        $pathToManifest = substr($thisContent['filename'],0,-15) ;
                        $pathToManifestFound = true;
                    }
                }
                $realFileSize += $thisContent['size'];
            }

            if (!isset($alreadyFilledSpace)) $alreadyFilledSpace = 0;

            if ( ($realFileSize + $alreadyFilledSpace) > $maxFilledSpace) // check the real size.
            {
                $errorFound = true;
                array_push ($errorMsgs, get_lang('The upload has failed. There is not enough space in your directory') ) ;
                $is_allowedToUnzip = false;
            }

            if ($is_allowedToUnzip && !$errorFound)
            {
                // PHP extraction of zip file using zlib

                chdir($baseWorkDir);
                // we try to extract the manifest file
                $unzippingState = $zipFile->extract( PCLZIP_OPT_BY_NAME, $pathToManifest."imsmanifest.xml",
                                                     PCLZIP_OPT_PATH, '',
                                                     PCLZIP_OPT_REMOVE_PATH, $pathToManifest );
                if ( $unzippingState == 0 )
                {
                    $errorFound = true;
                    array_push ($errorMsgs, get_lang('Cannot extract manifest from zip file (corrupted file ? ).') );
                }
            } //end of if ($is_allowedToUnzip)
        } // end of if (!function_exists...
    }
    else
    {
        $errorFound = true;
        array_push ($errorMsgs, get_lang('File must be a zip file (.zip)') );
    }
    // find xmlmanifest (must be in root else ==> cancel operation, delete files)

    // parse xml manifest to find :
    // package name - learning path name
    // SCO list
    // start asset path

    if ( !$errorFound )
    {
        $elementsPile = array(); // array used to remember where we are in the arborescence of the XML file
        $itemsPile = array(); // array used to remember parents items
        // declaration of global arrays used for extracting needed info from manifest for the new modules/SCO
        $manifestData = array();   // for global data  of the learning path
        $manifestData['items'] = array(); // item tags content (attributes + some child elements data (title for an example)
        $manifestData['scos'] = array();  // for path of start asset id of each new module to create

        $xml_parser = xml_parser_create();
        xml_set_element_handler($xml_parser, "startElement", "endElement");
        xml_set_character_data_handler($xml_parser, "elementData");

        // this file has to exist in a SCORM conformant package
        // this file must be in the root the sent zip
        $file = "imsmanifest.xml";

        if( file_exists($file) )
        {
            if (!isset($manifestPath)) $manifestPath = "";

            array_push ($okMsgs, get_lang('Manifest found in zip file : ').$manifestPath."imsmanifest.xml" );
            
            $fileContent = file_get_contents($manifestPath.$file);
            $charset = get_xml_charset( $fileContent );
            $data = claro_html_entity_decode(urldecode( $fileContent ), ENT_COMPAT, $charset);
            
            if( !xml_parse($xml_parser, $data) )
            {
                // if reading of the xml file in not successfull :
                // set errorFound, set error msg, break while statement

                $errorFound = true;
                array_push ($errorMsgs, get_lang('Error reading <i>manifest</i> file') );

                if ( claro_debug_mode() )
                {
                    $debugMessage = strtr( 'Debug : %message (error code %code) on line %line and column %column' ,
                                 array( '%message' => xml_error_string( xml_get_error_code($xml_parser) ) ,
                                        '%code' => xml_get_error_code($xml_parser) ,
                                        '%line' => xml_get_current_line_number($xml_parser) ,
                                        '%column' => xml_get_current_column_number($xml_parser) ) );

                    array_push ($errorMsgs, $debugMessage);
                }

            }
        }
        else
        {
            $errorFound = true;
            array_push ($errorMsgs, get_lang('Cannot find <i>manifest</i> file in the package.<br /> File not found : imsmanifest.xml') );
        }


         // liberate parser ressources
         xml_parser_free($xml_parser);

    } //if (!$errorFound)

    // check if all starts assets files exist in the zip file
    if ( !$errorFound )
    {
        array_push ($okMsgs, get_lang('Manifest read.') );

        if ( sizeof($manifestData['items']) > 0 )
        {
            // if there is items in manifest we look for sco type resources referenced in idientifierref
            foreach ( $manifestData['items'] as $item )
            {
                if ( !isset($item['identifierref']) || $item['identifierref'] == '') break; // skip if no ressource reference in item (item is probably a chapter head)
                // find the file in the zip file
                $scoPathFound = false;

                for ( $i = 0 ; $i < sizeof($zipContentArray) ; $i++)
                {
                    if ( isset($zipContentArray[$i]["filename"]) &&
                        ( ( isset($manifestData['scos'][$item['identifierref']]['href'] )
                            && $zipContentArray[$i]["filename"] == $pathToManifest.$manifestData['scos'][$item['identifierref']]['href'])
                         || (isset($manifestData['assets'][$item['identifierref']]['href'])
                         && $zipContentArray[$i]["filename"] == $pathToManifest.$manifestData['assets'][$item['identifierref']]['href'])
                        )
                       )
                    {
                        $scoPathFound = true;
                        break;
                    }
                }

                if ( !$scoPathFound )
                {
                    $errorFound = true;
                    array_push ($errorMsgs, get_lang('Asset not found : %asset', array('%asset' =>$manifestData['scos'][$item['identifierref']]['href'])) );
                    break;
                }
            }
        } //if (sizeof ...)
        elseif( sizeof($manifestData['scos']) > 0 )
        {
            // if there ie no items in the manifest file
            // check for scos in resources

            foreach ( $manifestData['scos'] as $sco )
            {
                // find the file in the zip file

                // create a fake item so that the rest of the procedure (add infos of in db) can remains the same
                $manifestData['items'][$sco['href']]['identifierref'] = $sco['href'];
                $manifestData['items'][$sco['href']]['parameters'] = '';
                $manifestData['items'][$sco['href']]['isvisible'] = "true";
                $manifestData['items'][$sco['href']]['title'] =  claro_utf8_decode( $sco['title'], get_conf( 'charset' ) );
                $manifestData['items'][$sco['href']]['description'] = claro_utf8_decode( $sco['description'], get_conf( 'charset' ) );
                $manifestData['items'][$attributes['IDENTIFIER']]['parent'] = 0;

                $scoPathFound = false;

                for ( $i = 0 ; $i < sizeof($zipContentArray) ; $i++)
                {
                    if ( $zipContentArray[$i]["filename"] == $sco['href'] )
                    {
                        $scoPathFound = true;
                        break;
                    }
                }
                if ( !$scoPathFound )
                {
                    $errorFound = true;
                    array_push ($errorMsgs, get_lang('Asset not found : %asset', array('%asset' => $sco['href'])));
                    break;
                }
            }
        } // if sizeof()
        else
        {
            $errorFound = true;
            array_push ($errorMsgs, get_lang('No module in package') );
        }
    }// if errorFound

    // unzip all files
    // &&
    // insert corresponding entries in database

    if ( !$errorFound )
    {
        // PHP extraction of zip file using zlib
        chdir($baseWorkDir);

        // PCLZIP_OPT_PATH is the path where files will be extracted ( '' )
        // PLZIP_OPT_REMOVE_PATH suppress a part of the path of the file ( $pathToManifest )
        // the result is that the manifest is in th eroot of the path_# directory and all files will have a path related to the root
        $unzippingState = $zipFile->extract(PCLZIP_OPT_PATH, '',PCLZIP_OPT_REMOVE_PATH, $pathToManifest);

        // insert informations in DB :
        //        - 1 learning path ( already added because we needed its id to create the package directory )
        //        - n modules
        //        - n asset as start asset of modules

        if( $unzippingState == 0 )
        {
            $errorFound = true;
            array_push ($errorMsgs, get_lang('Cannot extract files.') );
        }
        elseif ( sizeof( $manifestData['items'] ) == 0 )
        {
            $errorFound = true;
            array_push ($errorMsgs, get_lang('No module in package') );
        }
        else
        {
            $i = 0;
            $insertedLPMid = array(); // array of learnPath_module_id && order of related group
            $inRootRank = 1; // default rank for root module (parent == 0)

            foreach ( $manifestData['items'] as $item )
            {
                if ( isset($item['parent']) && isset($insertedLPMid[$item['parent']]) )
                {
                    $parent = $insertedLPMid[$item['parent']]['LPMid'];
                    $rank = $insertedLPMid[$item['parent']]['rank']++;
                }
                else
                {
                    $parent = 0;
                    $rank = $inRootRank++;
                }

                //-------------------------------------------------------------------------------
                // add chapter head
                //-------------------------------------------------------------------------------

                if( (!isset($item['identifierref']) || $item['identifierref'] == '') && isset($item['title']) && $item['title'] !='')
                {
                    // add title as a module
                    $chapterTitle = $item['title'];

                    $sql = "INSERT INTO `".$TABLEMODULE."`
                            (`name` , `comment`, `contentType`, `launch_data`)
                            VALUES ('".claro_sql_escape( claro_utf8_decode( $chapterTitle, get_conf( 'charset' ) ) )."' , '', '".CTLABEL_."','')";

                    $query = claro_sql_query($sql);

                    if ( claro_sql_error() )
                    {
                        $errorFound = true;
                        array_push($errorMsgs, get_lang('Error in SQL statement'));
                        break;
                    }
                    $insertedModule_id[$i] = claro_sql_insert_id();  // array of all inserted module ids

                    // visibility
                    if ( isset($item['isvisible']) && $item['isvisible'] != '' )
                    {
                        ( $item['isvisible'] == "true" )? $visibility = "SHOW": $visibility = "HIDE";
                    }
                    else
                    {
                        $visibility = 'SHOW'; // IMS consider that the default value of 'isvisible' is true
                    }

                    // add title module in the learning path
                    // finally : insert in learning path
                    $sql = "INSERT INTO `".$TABLELEARNPATHMODULE."`
                            (`learnPath_id`, `module_id`,`rank`, `visibility`, `parent`)
                            VALUES ('".$tempPathId."', '".$insertedModule_id[$i]."', ".$rank.", '".$visibility."', ".$parent.")";
                    $query = claro_sql_query($sql);

                    // get the inserted id of the learnPath_module rel to allow 'parent' link in next inserts
                    $insertedLPMid[$item['itemIdentifier']]['LPMid'] = claro_sql_insert_id();
                    $insertedLPMid[$item['itemIdentifier']]['rank'] = 1;

                    if ( claro_sql_error() )
                    {
                        $errorFound = true;
                        array_push($errorMsgs, get_lang('Error in SQL statement'));
                        break;
                    }
                    if (!$errorFound)
                    {
                        array_push ($okMsgs, get_lang('Title added : ')."<i>".$chapterTitle."</i>" ) ;
                    }
                    $i++;
                    continue;
                }

                // use found title of module or use default title
                if ( !isset( $item['title'] ) || $item['title'] == '')
                {
                    $moduleName = get_lang('Unnamed module');
                }
                else
                {
                    $moduleName = $item['title'];
                }

                // set description as comment or default comment
                // look fo description in item description or in sco (resource) description
                // don't remember why I checked for parameters string ... so comment it
                if ( ( !isset( $item['description'] ) || $item['description'] == '' )
                        &&
                               ( !isset($manifestData['scos'][$item['identifierref']]['description']) /*|| $manifestData['scos'][$item['identifierref']]['parameters'] == ''*/ )
                       )
                {
                    $description = get_block('blockDefaultModuleComment');
                }
                else
                {
                    if (  isset( $item['description'] ) && $item['description'] != '' )
                    {
                        $description = $item['description'];
                    }
                    else
                    {
                        $description = $manifestData['scos'][$item['identifierref']]['description'];
                    }
                }

                // insert modules and their start asset
                // create new module

                if (!isset($item['datafromlms'])) $item['datafromlms'] = "";

                $sql = "INSERT INTO `".$TABLEMODULE."`
                        (`name` , `comment`, `contentType`, `launch_data`)
                        VALUES ('".claro_sql_escape( claro_utf8_decode( $moduleName, get_conf( 'charset' ) ) )."' , '".claro_sql_escape( claro_utf8_decode( $description, get_conf( 'charset' ) ) )."', '".CTSCORM_."', '".claro_sql_escape($item['datafromlms'])."')";
                $query = claro_sql_query($sql);

                if ( claro_sql_error() )
                {
                    $errorFound = true;
                    array_push($errorMsgs, get_lang('Error in SQL statement'));
                    break;
                }

                $insertedModule_id[$i] = claro_sql_insert_id();  // array of all inserted module ids

                // build asset path
                // a $manifestData['scos'][$item['identifierref']] __SHOULD__ not exist if a $manifestData['assets'][$item['identifierref']] exists
                // so according to IMS we can say that one is empty if the other is filled, so we concat them without more verification than if the var exists.

                if (!isset($manifestData['xml:base']['manifest']))   $manifestData['xml:base']['manifest'] = "";
                if (!isset($manifestData['xml:base']['ressources'])) $manifestData['xml:base']['ressources'] = "";
                if (!isset($manifestData['scos'][$item['identifierref']]['href'])) $manifestData['scos'][$item['identifierref']]['href'] = "";
                if (!isset($manifestData['assets'][$item['identifierref']]['href'])) $manifestData['assets'][$item['identifierref']]['href'] = "";
                if (!isset($manifestData['scos'][$item['identifierref']]['parameters'])) $manifestData['scos'][$item['identifierref']]['parameters'] = "";
                if (!isset($manifestData['assets'][$item['identifierref']]['parameters'])) $manifestData['assets'][$item['identifierref']]['parameters'] = "";

                $assetPath = "/"
                             .$manifestData['xml:base']['manifest']
                             .$manifestData['xml:base']['ressources']
                             .$manifestData['scos'][$item['identifierref']]['href']
                             .$manifestData['assets'][$item['identifierref']]['href']
                             .$manifestData['scos'][$item['identifierref']]['parameters']
                             .$manifestData['assets'][$item['identifierref']]['parameters'];

                // create new asset
                $sql = "INSERT INTO `".$TABLEASSET."`
                        (`path` , `module_id` , `comment`)
                        VALUES ('". claro_sql_escape($assetPath) ."', ".$insertedModule_id[$i]." , '')";

                $query = claro_sql_query($sql);
                if ( claro_sql_error() )
                {
                    $errorFound = true;
                    array_push($errorMsgs, get_lang('Error in SQL statement'));
                    break;
                }

                $insertedAsset_id[$i] = claro_sql_insert_id(); // array of all inserted asset ids

                // update of module with correct start asset id
                $sql = "UPDATE `".$TABLEMODULE."`
                        SET `startAsset_id` = ". (int)$insertedAsset_id[$i]."
                        WHERE `module_id` = ". (int)$insertedModule_id[$i];
                $query = claro_sql_query($sql);

                if ( claro_sql_error() )
                {
                    $errorFound = true;
                    array_push($errorMsgs, get_lang('Error in SQL statement'));
                    break;
                }

                // visibility
                if ( isset($item['isvisible']) && $item['isvisible'] != '' )
                {
                    ( $item['isvisible'] == "true" )? $visibility = "SHOW": $visibility = "HIDE";
                }
                else
                {
                    $visibility = 'SHOW'; // IMS consider that the default value of 'isvisible' is true
                }

                // finally : insert in learning path
                $sql = "INSERT INTO `".$TABLELEARNPATHMODULE."`
                        (`learnPath_id`, `module_id`, `specificComment`, `rank`, `visibility`, `lock`, `parent`)
                        VALUES ('".$tempPathId."', '".$insertedModule_id[$i]."','".claro_sql_escape(get_block('blockDefaultModuleAddedComment'))."', ".$rank.", '".$visibility."', 'OPEN', ".$parent.")";
                $query = claro_sql_query($sql);

                // get the inserted id of the learnPath_module rel to allow 'parent' link in next inserts
                $insertedLPMid[$item['itemIdentifier']]['LPMid']  = claro_sql_insert_id();
                $insertedLPMid[$item['itemIdentifier']]['rank']  = 1;


                if ( claro_sql_error() )
                {
                    $errorFound = true;
                    array_push($errorMsgs, get_lang('Error in SQL statement'));
                    break;
                }

                if (!$errorFound)
                {
                    array_push ($okMsgs, get_lang('Module added : ')."<i>".$moduleName."</i>" ) ;
                }
                $i++;
            }//foreach
        } // if sizeof($manifestData['items'] == 0 )

    } // if errorFound


    // last step
    // - delete all added files/directories/records in db
    // or
    // - update the learning path record

    if ( $errorFound )
    {

        // delete all database entries of this "module"

        /*
        //this query should work with mysql > 4 to replace
        $sql = "DELETE
                  FROM `".$TABLELEARNPATHMODULE."`,
                       `".$TABLELEARNPATH."`
                  WHERE `".$TABLELEARNPATHMODULE."`.`learnPath_id` = `".$TABLELEARNPATH."`.`learnPath_id`
                    AND `".$TABLELEARNPATH."`.`learnPath_id` = ".$_GET['path_id'] ;
        */

        // queries for mysql previous to 4.0.0

        // delete modules and assets (build query)
        // delete assets
        $sqlDelAssets = "DELETE FROM `".$TABLEASSET."`
                        WHERE 1 = 0";
        foreach ( $insertedAsset_id as $insertedAsset )
        {
            $sqlDelAssets .= " OR `asset_id` = ". (int)$insertedAsset;
        }
        claro_sql_query($sqlDelAssets);

        // delete modules
        $sqlDelModules = "DELETE FROM `".$TABLEMODULE."`
                          WHERE 1 = 0";
        foreach ( $insertedModule_id as $insertedModule )
        {
             $sqlDelModules .= " OR `module_id` = ". (int)$insertedModule;
        }
        claro_sql_query($sqlDelModules);

        // delete learningPath_module
        $sqlDelLPM = "DELETE FROM `".$TABLELEARNPATHMODULE."`
                      WHERE `learnPath_id` = ". (int)$tempPathId;
        claro_sql_query($sqlDelLPM);

        // delete learning path
        $sqlDelLP = "DELETE FROM `".$TABLELEARNPATH."`
                     WHERE `learnPath_id` = ". (int)$tempPathId;
        claro_sql_query($sqlDelLP);

        // delete the directory (and files) of this learning path and all its content
        claro_delete_file($baseWorkDir);

    }
    else
    {
        // finalize insertion : update the empty learning path insert that was made to find its id
        $sql = "SELECT MAX(`rank`)
                FROM `".$TABLELEARNPATH."`";
        $result = claro_sql_query($sql);

        list($rankMax) = mysql_fetch_row($result);

        if ( isset($manifestData['packageTitle']) )
        {
            $lpName = $manifestData['packageTitle'] ;
        }
        else
        {
            array_push($okMsgs, get_lang('warning : Installation cannot find the name of the learning path and has set a default name. You should change it.') );
        }

        if ( isset($manifestData['packageDesc']) )
        {
            $lpComment = $manifestData['packageDesc'];
        }
        else
        {
            $lpComment = get_block('blockDefaultLearningPathComment');
            array_push($okMsgs, get_lang('warning : Installation cannot find the description of the learning path and has set a default comment. You should change it') );
        }

        $sql = "UPDATE `".$TABLELEARNPATH."`
                SET `rank` = ".($rankMax+1).",
                    `name` = '".claro_sql_escape($lpName)."',
                    `comment` = '".claro_sql_escape(claro_utf8_decode( $lpComment, get_conf( 'charset' ) ))."',
                    `visibility` = 'SHOW'
                WHERE `learnPath_id` = ". (int)$tempPathId;
        claro_sql_query($sql);

    }

    /*--------------------------------------
      status messages
     --------------------------------------*/

    foreach ( $okMsgs as $msg)
    {
        $out .= "\n<span class=\"correct\">v</span>&nbsp;&nbsp;&nbsp;".$msg."<br />";
    }

    foreach ( $errorMsgs as $msg)
    {
        $out .= "\n<span class=\"error\">x</span>&nbsp;&nbsp;&nbsp;".$msg."<br />";
    }

    // installation completed or not message
    if ( !$errorFound )
    {
        $out .= "\n<br /><center><b>".get_lang('Learning path has been successfully imported.')."</b></center>";
        $out .= "\n<br /><br ><center><a href=\"".claro_htmlspecialchars(Url::Contextualize("learningPathAdmin.php?path_id=".$tempPathId))."\">".$lpName."</a></center>";
    }
    else
    {
        $out .= "\n<br /><center><b>".get_lang('An error occurred. Learning Path import failed.')."</b></center>";
    }
    $out .= "\n<br /><a href=\"".claro_htmlspecialchars(Url::Contextualize("learningPathList.php"))."\">&lt;&lt; ".get_lang('Back')."</a>";

}
else // if method == 'post'
{
    // don't display the form if user already sent it

    /*--------------------------------------
      UPLOAD FORM
     --------------------------------------*/
    $out .= "\n\n".get_lang('Imported packages must consist of a zip file and be SCORM 1.2 conformable');
    
    $out .= '<br /><br />

            <form enctype="multipart/form-data" action="'. $_SERVER['PHP_SELF'] .'" method="post">
            <input type="hidden" name="claroFormId" value="'. uniqid('') .'" />
            '. claro_form_relay_context() .'

            <input type="file" name="uploadedPackage" /><br />
            <small>'. get_lang(
                'Max file size : %size', 
                array('%size' => format_file_size( get_max_upload_size($maxFilledSpace,$baseWorkDir) ) ) ) 
            .'</small>

            <p>
            <input type="submit" value="'. get_lang('Import') .'" />&nbsp;
            '. claro_html_button( Url::Contextualize('./learningPathList.php'), get_lang('Cancel')) . "\n"
            . '</p>'
            . '</form>'
        ;

} // else if method == 'post'

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
