<?php // $Id: image.php 13751 2011-10-26 12:15:26Z abourguignon $

/**
 * CLAROLINE
 *
 * $Revision: 13751 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package CLPAGES
 *
 * @author Claroline team <info@claroline.net>
 *
 *
 * TODO :
 * Use backend for secure download of images ?
 * show in list which image is selected
 * select image after upload
 * other stuff ? :p
 */

// load Claroline kernel
require_once dirname(__FILE__) . '/../../../../../inc/claro_init_global.inc.php';

require_once get_path('incRepositorySys') . '/lib/fileDisplay.lib.php';
require_once get_path('incRepositorySys') . '/lib/image.lib.php';

/*
 * Permissions
 */

if( claro_is_in_a_course() && !claro_is_in_a_group() )
{
    // course context
    $is_allowedToEdit = claro_is_allowed_to_edit();
    $pathSys = get_path('coursesRepositorySys') . claro_get_course_path().'/document/';
    $pathWeb = get_path('coursesRepositoryWeb') . claro_get_course_path() . '/document/';
    
    require claro_get_conf_repository() . 'CLDOC.conf.php';
    $maxFilledSpace = get_conf('maxFilledSpace_for_course');
}
elseif( claro_is_in_a_group() )
{
    // course context
    $is_allowedToEdit = claro_is_allowed_to_edit();
    $pathSys = get_path('coursesRepositorySys') . claro_get_course_path().'/group/'
                . claro_get_current_group_data('directory');
    $pathWeb = get_path('coursesRepositoryWeb') . claro_get_course_path() . '/group/'
                . claro_get_current_group_data('directory');
    
    require claro_get_conf_repository() . 'CLDOC.conf.php';
    $maxFilledSpace = get_conf('maxFilledSpace_for_course');
}
else
{
    // platform context
    $is_allowedToEdit = claro_is_platform_admin();
    $pathSys = get_path('rootSys') . 'platform/document/';
    $pathWeb = get_path('rootWeb') . 'platform/document/';
}

/*
 * Libraries
 */
include_once($includePath.'/lib/fileUpload.lib.php');
include_once($includePath.'/lib/fileManage.lib.php');

/*
 * Init directory
 */
if( !file_exists($pathSys) )
{
    claro_mkdir($pathSys);
}


/*
 * Init request vars
 */
if( !empty($_REQUEST['relativePath']) && $_REQUEST['relativePath'] != '/' && $_REQUEST['relativePath'] != '.' )
{
    $relativePath = str_replace('..','',$_REQUEST['relativePath']) . '/';
}
else
{
    $relativePath = '/';
}

/*
 * Handle upload
 */
if( $is_allowedToEdit && isset($_FILES['sentFile']['tmp_name']) && is_uploaded_file($_FILES['sentFile']['tmp_name']) )
{
    $imgFile = $_FILES['sentFile'];
    $imgFile['name'] = replace_dangerous_char($imgFile['name'],'strict');
    $imgFile['name'] = get_secure_file_name($imgFile['name']);
    
    if( claro_is_in_a_course() )
    {
        $enoughSize = enough_size($_FILES['sentFile']['size'], $pathSys, $maxFilledSpace);
    }
    else
    {
        $enoughSize = true;
    }
    
    if( is_image($imgFile['name']) && $enoughSize )
    {
        // rename if file already exists
        if(file_exists($pathSys . $relativePath .  $imgFile['name']))
        {
            $pieceList = explode('.', $imgFile['name']);
            $base = $pieceList[0];
            $ext = $pieceList[1];
            $i=1;
            while(file_exists($pathSys . $relativePath . $base . '_' . $i . '.' .  $ext))
            {
                $i++;
            }
            $imgFile['name'] = $base . '_' . $i . '.' .  $ext;
            $alertMessage = get_lang('A file with this name already exists.') . "\n"
            .    get_lang('Your file has been renamed to %filename', array('%filename' => $imgFile['name']) );
        }
        
        if ( move_uploaded_file($imgFile['tmp_name'], $pathSys .  $relativePath . $imgFile['name'] ) )
        {
            $imgUrl = str_replace('//','/', $pathWeb . $relativePath . $imgFile['name']);
        }
        else
        {
            $imgUrl = null;
            $alertMessage = get_lang('Cannot upload file');
        }
    }
    else
    {
        $imgUrl = null;
        
        if( ! $enoughSize )
        {
            $alertMessage = get_lang('The upload has failed. There is not enough space in your directory');
        }
        else
        {
            $alertMessage = get_lang('Uploaded file should be an image');
        }
        
    }
}

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php echo get_lang('Image manager'); ?></title>
    <script type="text/javascript" src="<?php echo get_path('rootWeb'); ?>/web/js/jquery.js"></script>
    <script type="text/javascript" src="../../tiny_mce_popup.js"></script>
    <script type="text/javascript" src="../../utils/mctabs.js"></script>
    <script type="text/javascript" src="../../utils/form_utils.js"></script>
    <script type="text/javascript" src="../../utils/validate.js"></script>
    <script type="text/javascript" src="../../utils/editable_selects.js"></script>
    <script type="text/javascript" src="js/image.js"></script>
    <script type="text/javascript" src="js/ajax.js"></script>
    <script type="text/javascript">
       var relativePath = '<?php echo addslashes($relativePath); ?>';
       <?php
            if( !empty($alertMessage) )
            {
                echo 'alert("'.clean_str_for_javascript($alertMessage).'");' . "\n";
            }
            
            if( !empty($imgUrl) )
            {
                echo 'selectImage( "'.$imgUrl.'" );'. "\n";
            }
       ?>
    </script>
    <link href="css/advimage.css" rel="stylesheet" type="text/css" />
    <base target="_self" />
</head>
<body id="advimage" style="display: none">
    <form action="#" method="post" enctype="multipart/form-data">
        <div class="tabs">
            <ul>
                <li id="general_tab" class="current"><span><a href="javascript:mcTabs.displayTab('general_tab','general_panel');" onmousedown="return false;"><?php echo get_lang('Main'); ?></a></span></li>
                <li id="advanced_tab"><span><a href="javascript:mcTabs.displayTab('advanced_tab','advanced_panel');" onmousedown="return false;"><?php echo get_lang('Advanced'); ?></a></span></li>
            </ul>
        </div>
        <div class="panel_wrapper">
            <div id="general_panel" class="panel current">
                <fieldset>
                        <legend><?php echo get_lang('Available images'); ?></legend>
                        <div id="displayedPath"><?php echo get_lang('Path'); ?>: <span id="path"></span></div>
                        <div id="image_list">
                        </div>
                        <div>
                            <div id="processing"><img src="<?php echo get_icon_url('loading'); ?>" /></div>
                            <?php
                            if( $is_allowedToEdit )
                            {
                            ?>
                            <label for="sentfile"><?php echo get_lang('Add an image'); ?></label><br />
                            <input type="hidden" id="relativePath" name="relativePath" value="<?php echo $relativePath ?>" />
                            <input id="sentFile" type="file" name="sentFile" size="25" value="" />
                            <input id="upload" type="submit" name="upload" value="<?php echo get_lang('Upload'); ?>" />
                            <?php
                            }
                            ?>
                        </div>
                </fieldset>

            </div>
            
            
            <div id="advanced_panel" class="panel">
                <fieldset>
                    <legend><?php echo get_lang('Advanced'); ?></legend>

                    <table border="0" cellpadding="4" cellspacing="0" width="100%">
                        <tr>
                            <td class="column1"><label id="srclabel" for="src"><?php echo get_lang('Image URL'); ?></label></td>
                            <td colspan="2"><table border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                  <td><input name="src" type="text" id="src" value="" class="mceFocus" onchange="ImageDialog.showPreviewImage(this.value);" /></td>
                                  <td id="srcbrowsercontainer">&nbsp;</td>
                                </tr>
                              </table></td>
                        </tr>
                        <tr>
                            <td class="column1"><label id="widthlabel" for="width"><?php echo get_lang('Dimensions'); ?></label></td>
                            <td colspan="2" nowrap="nowrap">
                                <input name="width" type="text" id="width" value="" size="5" maxlength="5" class="size" onchange="ImageDialog.changeHeight();" /> x
                                <input name="height" type="text" id="height" value="" size="5" maxlength="5" class="size" onchange="ImageDialog.changeWidth();" /> px
                            </td>
                        </tr>

                        <tr>
                            <td>&nbsp;</td>
                            <td colspan="2"><table border="0" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td><input id="constrain" type="checkbox" name="constrain" class="checkbox" /></td>
                                        <td><label id="constrainlabel" for="constrain"><?php echo get_lang('Constrain proportions'); ?></label></td>
                                    </tr>
                                </table></td>
                        </tr>
    
                        <tr>
                            <td class="column1"><label id="alignlabel" for="align"><?php echo get_lang('Alignment'); ?></label></td>
                            <td><select id="align" name="align" onchange="ImageDialog.updateStyle('align');ImageDialog.changeAppearance();">
                                    <option value=""></option>
                                    <option value="baseline"><?php echo get_lang('Baseline') ?></option>
                                    <option value="top"><?php echo get_lang('Top') ?></option>
                                    <option value="middle"><?php echo get_lang('Middle') ?></option>
                                    <option value="bottom"><?php echo get_lang('Bottom') ?></option>
                                    <option value="text-top"><?php echo get_lang('Text top') ?></option>
                                    <option value="text-bottom"><?php echo get_lang('Text bottom') ?></option>
                                    <option value="left"><?php echo get_lang('Left') ?></option>
                                    <option value="right"><?php echo get_lang('Right') ?></option>
                                </select>
                            </td>
                            <td rowspan="6" valign="top">
                                <div class="alignPreview">
                                    <img id="alignSampleImg" src="img/sample.gif" alt="<?php echo get_lang('Sample image'); ?>" />
                                    Lorem ipsum, Dolor sit amet, consectetuer adipiscing loreum ipsum edipiscing elit, sed diam
                                    nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.Loreum ipsum
                                    edipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam
                                    erat volutpat.
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <td class="column1"><label id="altlabel" for="alt"><?php echo get_lang('Image description'); ?></label></td>
                            <td><input id="alt" name="alt" type="text" value="" /></td>
                        </tr>
                        <tr>
                            <td class="column1"><label id="titlelabel" for="title"><?php echo get_lang('Title'); ?></label></td>
                            <td><input id="title" name="title" type="text" value="" /></td>
                        </tr>
                            
                        <tr>
                            <td class="column1"><label id="vspacelabel" for="vspace"><?php echo get_lang('Vertical space'); ?></label></td>
                            <td><input name="vspace" type="text" id="vspace" value="" size="3" maxlength="3" class="number" onchange="ImageDialog.updateStyle('vspace');ImageDialog.changeAppearance();" onblur="ImageDialog.updateStyle('vspace');ImageDialog.changeAppearance();" />
                            </td>
                        </tr>

                        <tr>
                            <td class="column1"><label id="hspacelabel" for="hspace"><?php echo get_lang('Horizontal space'); ?></label></td>
                            <td><input name="hspace" type="text" id="hspace" value="" size="3" maxlength="3" class="number" onchange="ImageDialog.updateStyle('hspace');ImageDialog.changeAppearance();" onblur="ImageDialog.updateStyle('hspace');ImageDialog.changeAppearance();" /></td>
                        </tr>

                        <tr>
                            <td class="column1"><label id="borderlabel" for="border"><?php echo get_lang('Border'); ?></label></td>
                            <td><input id="border" name="border" type="text" value="" size="3" maxlength="3" class="number" onchange="ImageDialog.updateStyle('border');ImageDialog.changeAppearance();" onblur="ImageDialog.updateStyle('border');ImageDialog.changeAppearance();" /></td>
                        </tr>

                        <tr>
                            <td class="column1"><label id="stylelabel" for="style"><?php echo get_lang('Style'); ?></label></td>
                            <td colspan="2"><input id="style" name="style" type="text" value="" onchange="ImageDialog.changeAppearance();" /></td>
                        </tr>
                    </table>
                </fieldset>
            </div>
            
            <fieldset>
                <legend><?php echo get_lang('Preview'); ?></legend>
                <div id="prev"></div>
                
            </fieldset>
        </div>

        <div class="mceActionPanel">
            <div style="float: left">
                <input type="submit" onclick="ImageDialog.insert();return false;" id="insert" name="insert" value="<?php echo get_lang('Ok'); ?>" />
            </div>

            <div style="float: right">
                <input type="button" id="cancel" name="cancel" value="<?php echo get_lang('Cancel'); ?>" onclick="tinyMCEPopup.close();" />
            </div>
        </div>
    </form>
</body>
</html>
