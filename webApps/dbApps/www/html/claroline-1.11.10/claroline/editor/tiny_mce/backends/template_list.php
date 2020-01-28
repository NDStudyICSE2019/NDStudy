<?php
/*
 * This file is loaded by tiny_mce using 
 *      template_external_list_url : baseURI + "../../backends/template_list.php"
 * in tinyMCE.init
 * 
 * It loads the list of available templates and the list is read by the template module of
 * tiny_mce
 */
/*
 * Kernel
 */
require_once dirname( __FILE__ ) . '/../../../inc/claro_init_global.inc.php';

/*
 * Prepare data
 */
$layoutDirUrl = get_path('rootWeb') . 'web/tiny_mce/layout';
$layoutDirPath = get_path('rootSys') . 'web/tiny_mce/layout';

$it = new DirectoryIterator($layoutDirPath);
$layoutList = array();

foreach( $it as $file )
{
    if( !$file->isDir() && !$file->isDot() )
    {
        $name = $file->getFileName();
        
        $url = $layoutDirUrl . '/' . $file->getFileName();
        
        
        $layoutList[] = '["'.$name.'", "'.$url.'", ""]'; 
    }
}

natcasesort($layoutList);

/*
 * Output
 * 
 */

header('Content-type: text/javascript'); 

echo 'var tinyMCETemplateList = [' . "\n"
.    '// name, url, description' . "\n"
.    implode( ',' . "\n", $layoutList) . "\n"
.    '];' . "\n";
?>