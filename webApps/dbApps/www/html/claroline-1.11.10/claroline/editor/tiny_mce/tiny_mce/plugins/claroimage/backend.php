<?php // $Id: backend.php 14314 2012-11-07 09:09:19Z zefredz $
/**
 * CLAROLINE
 *
 * $Revision: 14314 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package CLPAGES
 *
 * @author Claroline team <info@claroline.net>
 *
 */
    // load Claroline kernel
    require_once dirname(__FILE__) . '/../../../../../inc/claro_init_global.inc.php';
    
    require_once get_path('incRepositorySys') . '/lib/fileDisplay.lib.php';
    require_once get_path('incRepositorySys') . '/lib/image.lib.php';
    
    /*
     * init request vars
     */
    $acceptedCmdList = array( 'getFileList' );
    
    if( isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'],$acceptedCmdList) ) 
    {
        $cmd = $_REQUEST['cmd'];
    }
    else                
    {
        $cmd = null;
    }
    
    if( !empty($_REQUEST['relPath']) && $_REQUEST['relPath'] != '/' && $_REQUEST['relPath'] != '.' )
    {
        $relPath = str_replace('..', '', $_REQUEST['relPath']).'/';
    }
    else
    {
        $relPath = '';
    }
    
    /*
     * Init other vars
     */
    if( claro_is_in_a_course() && !claro_is_in_a_group() )
    {
        $course_data = claro_get_course_data();
        // course context
        $is_allowedToEdit = claro_is_allowed_to_edit();
        $pathSys = get_path('coursesRepositorySys') . claro_get_course_path().'/document/';
        $pathWeb = get_path('coursesRepositoryWeb') . claro_get_course_path() . '/document/';
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
    
    if( !claro_is_user_authenticated() )
    {
        claro_disp_auth_form(true);
    }
    
    /*
     * Libraries
     */
    include_once($includePath.'/lib/fileUpload.lib.php');
    include_once($includePath.'/lib/fileManage.lib.php');
    
    if( !file_exists($pathSys) )
    {
        claro_mkdir($pathSys);
    }
    

    
    
    header('Content-Type: text/html; charset=UTF-8'); // Charset
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
    
    
    if( $cmd == 'getFileList' )
    {
        $it = new DirectoryIterator($pathSys . $relPath);
        /*
         * Output
         */
        
        $out = "\n" . '<ul id="files">' . "\n";;
        
        if( !empty($relPath) )
        {
            $parentPath = dirname($relPath);
            $out .= '<li>'  . "\n"
            .    '<a href="#" onclick="setFileList(\''.$parentPath.'\')">'
            .    '<img src="'.get_icon_url('parent').'" />'
            .    '..'
            .    '</a>'
            .    '</li>' . "\n";
        }
        
        
        // directories
        foreach( $it as $file )
        {
            if( $file->isDir() && !$file->isDot() )
            {
                // get relative path from allowed root (document/img or platform/document) to target
                $relativePath = str_replace(realpath($pathSys),'',realpath($file->getPathname()));
                
                $out .= '<li>'  . "\n"
                .    '<a href="#" class="selectFolder" onclick="setFileList(\''.str_replace( '\\', '/', $relativePath ).'\')">'
                .    '<img src="'.get_icon_url('folder').'" />'
                .    claro_htmlspecialchars($file->getFileName()) 
                .    '</a>'
                .    '</li>' . "\n";
            }
        }
        
        // then the files
        foreach( $it as $file )
        {
            if( $file->isFile() && is_image($file->getFileName()) )
            {
                $path = '/' . $relPath . $file->getFileName();
                
                $url = claro_get_file_download_url( $path );
                
                $out .= '<li>'  . "\n"
                .    '<a href="#" onclick="selectImage(\''.$url.'\')">'
                .    '<img src="'.get_icon_url( choose_image($file->getFileName()) ).'" />' 
                .    claro_htmlspecialchars($file->getFileName())
                .    '</a>'
                .    '</li>' . "\n";
            }
        }
        
        $out .= '</ul>' . "\n";
        
        echo claro_utf8_encode($out);
    }
?>