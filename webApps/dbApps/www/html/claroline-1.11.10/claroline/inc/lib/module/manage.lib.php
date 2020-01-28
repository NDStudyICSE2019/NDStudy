<?php // $Id: manage.lib.php 14567 2013-10-23 08:44:05Z zefredz $

/**
 * CLAROLINE
 *
 * Claroline extension modules management library
 *
 * @version     $Revision: 14567 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html
 *      GNU GENERAL PUBLIC LICENSE version 2 or later
 * @author      Claro Team <cvs@claroline.net>
 * @package     kernel.module
 * @todo why do we need that much identifiers for a module ?!?
 */

require_once dirname(__FILE__) . '/../fileManage.lib.php';
require_once dirname(__FILE__) . '/../right/profileToolRight.class.php';
require_once dirname(__FILE__) . '/../right/right_profile.lib.php';
require_once dirname(__FILE__) . '/../backlog.class.php';
// Manifest Parser and functions
require_once dirname(__FILE__) . '/manifest.lib.php';

// INFORMATION AND UTILITY FUNCTIONS

/**
 * Return info of a module, including extra info and specific info for tool if case
 *
 * @param integer $moduleId
 * @return array (label, id, module_name,activation, type, script_url, icon,
 *  version, author, author_email, website, description, license)
 */
function get_module_info($moduleId)
{
    $tbl = claro_sql_get_tbl(array('module', 'module_info', 'course_tool'));

    $sql = "
        SELECT M.`label`  AS label,
               M.`id`         AS module_id,
               M.`name`       AS module_name,
               M.`activation` AS activation,
               M.`type`       AS type,
               M.`script_url` AS script_url,

               CT.`icon`       AS icon,
               CT.`add_in_course` AS activateInCourses,
               CT.`access_manager` AS accessManager,

               MI.version      AS version,
               MI.author       AS author,
               MI.author_email AS author_email ,
               MI.website      AS website,
               MI.description  AS description,
               MI.license      AS license

          FROM `" . $tbl['module']      . "` AS M

     LEFT JOIN `" . $tbl['module_info'] . "` AS MI
            ON M.`id` = MI . `module_id`

     LEFT JOIN `" . $tbl['course_tool'] . "` AS CT
            ON CT.`claro_label`= M.label

         WHERE M.`id` = " . (int) $moduleId;

    return claro_sql_query_get_single_row($sql);

}

/**
* Gest the list of module type already installed
* @return array of string
*
*/
function claro_get_module_types()
{
    $tbl = claro_sql_get_tbl('module');
    $sql = "SELECT distinct M.`type` AS `type`
           FROM `" . $tbl['module'] . "` AS M";
    $moduleType = claro_sql_query_fetch_all_cols($sql);
    return $moduleType['type'];
}

/**
 * Get installed module list, its effect is
 * to return an array containing the installed module's labels
 * @param string $type : type of the module that must be returned,
 *        if null, then all the modules are returned
 * @return array containing the labels of the modules installed
 *         on the platform
 *         false if query failed
 */
function get_installed_module_list($type = null)
{
    $tbl = claro_sql_get_main_tbl();

    $sql = "SELECT label FROM `" . $tbl['module'] . "`";

    if (!is_null($type))
    {
        $sql.= " WHERE `type`= '" . claro_sql_escape($type) . "'";
    }

    $moduleList = claro_sql_query_fetch_all_cols($sql);

    if ( is_array( $moduleList ) && array_key_exists('label', $moduleList ) )
    {
        return $moduleList['label'];
    }
    else
    {
        return array();
    }
}

/**
 * Get the tool id in a course (not the main tool id!) from the module label
 * @param the label of a course tool
 * @return the id in the course tool tabel
 *         false if tool not found
 */
function get_course_tool_id( $label )
{
    $tbl = claro_sql_get_main_tbl();

    $sql ="SELECT id FROM `" . $tbl['tool'] . "`
           WHERE claro_label='".$label."'";

    return claro_sql_query_get_single_value($sql);
}

// MODULE REPOSITORIES FUNCTIONS

/**
 * Get the list of the repositories found in the module repository
 * where all modules are installed, its effect is
 * returning the expected list
 *
 * @return an array with all the repositories found in the module repository
 * where all modules are installed
 */
function get_module_repositories()
{

    $moduleRepositorySys = get_path('rootSys') . 'module/';
    $folder_array = array();
    if(file_exists($moduleRepositorySys))
    {
        if (false !== ($handle = opendir($moduleRepositorySys)))
        {
            while (false !== ($file = readdir($handle)))
            {
                if ( $file == '.' || $file == '..' || $file == 'CVS' )
                {
                    continue;
                }
                elseif (!is_dir($moduleRepositorySys . $file) )
                {
                    continue ;
                }
                elseif( is_dir($file) && $file[0] == '.' )
                {
                    continue;
                }
                else
                {
                    $folder_array[] = $file;
                }
            }
        }

        closedir($handle);
    }
    return $folder_array;
}

/**
 * Check the presence of unexpected module repositories or unexpected module
 * in DB, its effect is returning a list of module not installed in DB but
 * present on server, or module installed in DB but not present on server.
 * @return an array two arrays :
 *            ['folder'] containing paths of the suspicious folders found that
 *                       did not correspond to an installed module in DB
 *            ['DB']     containing label of modules found in DB for which no
 *                       corresponding folder was found on server
 */
function check_module_repositories()
{
    $mistake_array           = array();
    $mistake_array['folder'] = array();
    $mistake_array['DB']     = array();

    $registredModuleList = get_installed_module_list();

    foreach ($registredModuleList as $registredModuleLabel)
    {
        $modulePath = get_module_path($registredModuleLabel);

        if ( !file_exists($modulePath) )
        {
            $mistake_array['DB'][] = $registredModuleLabel;
        }
    }

    $folders_found = get_module_repositories();

    foreach ($folders_found as $module_folder)
    {
        if (!in_array($module_folder,$registredModuleList))
        {
            $mistake_array['folder'][] = $module_folder;
        }
    }

    return $mistake_array;
}

// MODULE INSTALLATION AND ACTIVATION


/**
 * Unzip a module package archive and get the path of the unzipped files
 * @todo split this function and use unzip_package()
 * @todo remove the need of the Backlog and use Exceptions instead
 * @return string
 */
function get_and_unzip_uploaded_package()
{
    $backlog_message = array();

    //Check if the file is valid (not to big and exists)

    if( !isset($_FILES['uploadedModule'])
    || !is_uploaded_file($_FILES['uploadedModule']['tmp_name']))
    {
        $backlog_message[] = get_lang('Upload failed');
    }
    
    require_once dirname(__FILE__) . '/../thirdparty/pclzip/pclzip.lib.php';

    if (!function_exists('gzopen'))
    {
        $backlog_message[] = get_lang('Error : no zlib extension found');
        return claro_failure::set_failure($backlog_message);
    }

    //unzip files
    
    
    // $moduleRepositorySys is the place where go the installed module
    // $uploadDirFullPath is a temporary name of the dir in $moduleRepositorySys the module is unpack
    // $uploadDirFullPath would be renamed to $modulePath when install is done.
    
    $moduleRepositorySys = get_path('rootSys') . 'module/';
    //create temp dir for upload
    claro_mkdir($moduleRepositorySys, CLARO_FILE_PERMISSIONS, true);
    $uploadDirFullPath = claro_mkdir_tmp($moduleRepositorySys);
    $uploadDir         = str_replace(realpath($moduleRepositorySys),'',realpath($uploadDirFullPath));
    $modulePath        = realpath($moduleRepositorySys.$uploadDir.'/');

    //1- Unzip folder in a new repository in claroline/module

    // treat_uploaded_file : Executes all the necessary operation to upload the file in the document tool
    // TODO this function would be splited.
    
    if ( preg_match('/.zip$/i', $_FILES['uploadedModule']['name'])
      && treat_uploaded_file( $_FILES['uploadedModule']
                            , $moduleRepositorySys
                            , $uploadDir
                            , get_conf('maxFilledSpaceForModule' , 20000000)
                            , 'unzip'
                            , true)
                            )
    {
        $backlog_message[] = get_lang('Files dezipped sucessfully in %path', array ('%path' => $modulePath )) ;
    }
    else
    {
        $backlog_message[] = get_lang('Impossible to unzip file');
        claro_delete_file($modulePath);
        return claro_failure::set_failure($backlog_message);
    }
    
    return $modulePath;
}

/**
 * Unzip the module package
 * @param string $packageFileName
 * @return string module path
 * @todo use this function in get_and_unzip_uploaded_package()
 * @todo remove the need of the Backlog and use Exceptions instead
 */
function unzip_package( $packageFileName )
{
    $backlog_message = array();

    //1- Unzip folder in a new repository in claroline/module
    require_once dirname(__FILE__) . '/../thirdparty/pclzip/pclzip.lib.php';
    
    if (!function_exists('gzopen'))
    {
        $backlog_message[] = get_lang('Error : no zlib extension found');
        return claro_failure::set_failure($backlog_message);
    }

        //unzip files

    $moduleRepositorySys = get_path('rootSys') . 'module/';
    //create temp dir for upload
    claro_mkdir($moduleRepositorySys, CLARO_FILE_PERMISSIONS, true);
    $uploadDirFullPath = claro_mkdir_tmp($moduleRepositorySys);
    $uploadDir         = str_replace($moduleRepositorySys,'',$uploadDirFullPath);
    $modulePath        = $moduleRepositorySys.$uploadDir.'/';
    
    if ( preg_match('/.zip$/i', $packageFileName)
      && treat_secure_file_unzip($packageFileName, $uploadDir, $moduleRepositorySys, get_conf('maxFilledSpaceForModule' , 10000000),true))
    {
        $backlog_message[] = get_lang('Files dezipped sucessfully in %path', array ('%path' => $modulePath )) ;
    }
    else
    {
        $backlog_message[] = get_lang('Impossible to unzip file');
        claro_delete_file($modulePath);
        return claro_failure::set_failure($backlog_message);
    }
    return $modulePath;
}

function generate_module_names_translation_cache()
{
    $cacheRepositorySys = get_path('rootSys') . get_conf('cacheRepository', 'tmp/cache/');
    $moduleLangCache = $cacheRepositorySys . 'module_lang_cache';

    if ( ! file_exists($moduleLangCache) )
    {
        claro_mkdir( $moduleLangCache, CLARO_FILE_PERMISSIONS, true );
    }

    $tbl = claro_sql_get_main_tbl();
    $sql = "SELECT `name`, `label`
              FROM `" . $tbl['module'] . "`
             WHERE activation = 'activated'";

    $module_list = claro_sql_query_fetch_all($sql);

    $langVars = array();

    foreach ( $module_list as $module )
    {
        $langPath = get_module_path( $module['label'] ).'/lang/';

        if ( file_exists( $langPath ) )
        {
            $it = new DirectoryIterator( $langPath );

            foreach ( $it as $file )
            {
                if ( $file->isFile()
                    && preg_match('/^lang_\w+.php$/', $file->getFilename() ) )
                {
                    $langName = str_replace( 'lang_', '', $file->getFilename() );
                    $langName = str_replace( '.php', '', $langName );

                    if ( $langName != 'english' )
                    {
                        pushClaroMessage( $langName . ':' . $module['label'], 'debug');

                        $_lang = array();

                        ob_start();
                        include $file->getPathname();
                        ob_end_clean();

                        if ( ! isset( $langVars[$langName] ) )
                        {
                            $langVars[$langName] = '';
                        }

                        if ( isset($_lang[$module['name']]) )
                        {
                            $langVars[$langName] .= '$_lang[\''.$module['name'].'\'] = \''.str_replace( "'", "\\'", $_lang[ $module['name'] ]).'\';'."\n";
                        }
                    }
                }
            }
        }
    }

    foreach ( $langVars as $lgnNm => $contents )
    {
        $langFile = $moduleLangCache . '/'.$lgnNm.'.lang.php';

        if ( file_exists( $langFile ) )
        {
            unlink( $langFile );
        }

        file_put_contents( $langFile, "<?php\n".$contents );
    }
}

/**
 * Generate the cache php file with the needed include of activated module of the platform.
 * @return boolean true if succeed, false on failure
 */
function generate_module_cache()
{

    $module_cache_filename = get_conf('module_cache_filename','moduleCache.inc.php');
    $cacheRepositorySys = get_path('rootSys') . get_conf('cacheRepository', 'tmp/cache/');
    $module_cache_filepath = $cacheRepositorySys . $module_cache_filename;

    if ( ! file_exists( $cacheRepositorySys ) )
    {
        claro_mkdir($cacheRepositorySys, CLARO_FILE_PERMISSIONS, true);
    }

    $tbl = claro_sql_get_main_tbl();
    $sql = "SELECT `label`
              FROM `" . $tbl['module'] . "`
             WHERE activation = 'activated'";

    $module_list = claro_sql_query_fetch_all($sql);

    if (file_exists($cacheRepositorySys) && is_writable($cacheRepositorySys))
    {
        if ( file_exists( $module_cache_filepath ) && ! is_writable( $module_cache_filepath ) )
        {
            return claro_failure::set_failure('cannot write to cache file ' . $module_cache_filepath);
        }
        else
        {
            if ( false !== ( $handle = fopen($module_cache_filepath, 'w') ) )
            {
                $cache = '<?php #auto created by claroline modify it at your own risks'."\n";
                $cache .= 'if (count( get_included_files() ) == 1) die();'."\n";
                $cache .= "\n" . '# ---- start of cache ----'."\n\n";

                foreach($module_list as $module)
                {
                    $functionsFilePath = get_module_path($module['label']) . '/functions.php';

                    if (file_exists( $functionsFilePath ))
                    {
                        $cache .= '# ' . $module['label'] . "\n" ;
                        $cache .= 'if (file_exists(get_module_path("'.addslashes($module['label']).'")."/functions.php") ){' . "\n";
                        $cache .= 'set_current_module_label("'.addslashes($module['label']).'");' . "\n";
                        $cache .= 'load_module_config("'.addslashes($module['label']).'");' . "\n";
                        $cache .= 'language::load_module_translation("'.addslashes($module['label']).'","'.language::current_language().'");' . "\n";
                        $cache .= 'require get_module_path("'.addslashes($module['label']).'")."/functions.php";' . "\n";
                        $cache .= 'clear_current_module_label();'. "\n";
                        $cache .= '}' . "\n";
                    }
                }

                $cache .= "\n";

                fwrite( $handle, $cache );
                fclose( $handle );
            }
            else
            {
                return claro_failure::set_failure('Cannot open path %path', array('%path'=> $module_cache_filepath));
            }
        }
    }
    else
    {
        // FIXME E_USER_ERROR instead of E_USER_NOTICE
        return claro_failure::set_failure('Directory %directory is not writable', array('%directory' => $cacheRepositorySys) );
    }

    generate_module_names_translation_cache();

    return true;
}

/**
 * Install a specific module to the platform
 * @param string $modulePath path to the module
 * @param bool $skipCheckDir skip checking if module directory already exists (default false)
 * @return array( backlog, int )
 *      backlog object containing the messages
 *      int moduleId if the install process suceeded, false otherwise
 * @todo remove the need of the Backlog and use Exceptions instead
 */
function install_module($modulePath, $skipCheckDir = false, $registerModuleInCourses = false )
{
    $backlog = new Backlog;
    $moduleId = false;

    if (false === ($module_info = readModuleManifest($modulePath)))
    {
        claro_delete_file($modulePath);
        $backlog->failure( claro_failure::get_last_failure() );
    }
    else
    {
        //check if a module with the same LABEL is already installed, if yes, we cancel everything
        // TODO extract from install function should be tested BEFORE calling install_module
        if ( (!$skipCheckDir) && check_name_exist(get_module_path($module_info['LABEL']) . '/'))
        {
            $backlog->failure( get_lang('Module %module is already installed on your platform'
                , array('%module'=>$module_info['LABEL'])));
            // claro_delete_file($modulePath);
            // TODO : add code to point on existing instance of tool.
            // TODO : how to overwrite . prupose uninstall ?
        }
        else
        {
            //3- Save the module information into DB
            if ( false === ( $moduleId = register_module_core($module_info) ) )
            {
                claro_delete_file($modulePath);
                $backlog->failure(claro_failure::get_last_failure());
                $backlog->failure( get_lang('Module registration failed') );
            }
            else
            {
                //in case of tool type module, the dock can not be selected and must added also now

                if ('tool' == $module_info['TYPE'])
                {
                    // TODO FIXME handle failure
                    register_module_tool($moduleId,$module_info);
                }

                if (array_key_exists('DEFAULT_DOCK',$module_info))
                {
                    foreach($module_info['DEFAULT_DOCK'] as $dock)
                    {
                        // TODO FIXME handle failure
                        add_module_in_dock($moduleId, $dock);
                    }
                }

                //4- Rename the module repository with label
                $currentPlace = realpath($modulePath) . '/';
                $destPath = get_module_path( $module_info['LABEL'] );
                claro_mkdir(get_path('rootSys') . 'module/', CLARO_FILE_PERMISSIONS, true);
                if (!@rename( $currentPlace , $destPath ))
                {
                   $backlog->failure(get_lang("Error while renaming module folder").
                   ' from:' . $currentPlace  .
                   ' to:' . $destPath
                   );
                }
                else
                {
                    // force access rights on module root dir after rename() because some modules written on M$ Win$#!t are causing issues
                    chmod( $destPath,CLARO_FILE_PERMISSIONS );
                    
                    //5-Include the local 'install.sql' and 'install.php' file of the module if they exist
                    if ( isset( $installSqlScript ) ) unset ( $installSqlScript );
                    $installSqlScript = get_module_path( $module_info['LABEL'] ) . '/setup/install.sql';

                    if (file_exists( $installSqlScript ) )
                    {
                        $sql = file_get_contents( $installSqlScript );

                        if (!empty($sql))
                        {
                            $sql = str_replace ('__CL_MAIN__',get_conf('mainTblPrefix'), $sql);

                            if ( claro_sql_multi_query($sql) === false )
                            {
                                $backlog->failure(get_lang( 'Sql installation query failed' ));
                            }
                            else
                            {
                                $backlog->failure(get_lang( 'Sql installation query succeeded' ));
                            }
                        }
                    }
                    
                    // generate the conf if a def file exists
                    if ( file_exists( get_module_path($module_info['LABEL'])
                        . '/conf/def/'.$module_info['LABEL'].'.def.conf.inc.php' ) )
                    {
                        require_once dirname(__FILE__) . '/../config.lib.inc.php';
                        $config = new Config($module_info['LABEL']);
                        list ($confMessage, $status ) = generate_conf($config);

                        $backlog->info($confMessage);
                    }

                    // call install.php after initialising database in case it requires database to run
                    if ( isset( $installPhpScript ) ) unset ( $installPhpScript );
                    $installPhpScript = get_module_path($module_info['LABEL']) . '/setup/install.php';

                    if (file_exists($installPhpScript))
                    {
                        language::load_translation( );
                        language::load_locale_settings( );
                        language::load_module_translation( $module_info['LABEL'] );
                        load_module_config( $module_info['LABEL'] );
                        
                        // FIXME this is very dangerous !!!!
                        require $installPhpScript;
                        $backlog->info(get_lang( 'Module installation script called' ));
                    }

                    $moduleInfo =  get_module_info($moduleId);

                    if (($registerModuleInCourses && $moduleInfo['type'] =='tool') && $moduleId)
                    {
                        list ( $backlog2, $success2 ) = register_module_in_courses( $moduleId );

                        if ( $success2 )
                        {
                            $backlog->success( get_lang('Module installed in all courses') );
                        }
                        else
                        {
                            $backlog->append( $backlog2 );
                        }
                    }

                    //6- cache file with the module's include must be renewed after installation of the module

                    if ( ! generate_module_cache() )
                    {
                        $backlog->failure(get_lang( 'Module cache update failed' ));
                    }
                    else
                    {
                        $backlog->success(get_lang( 'Module cache update succeeded' ));
                    }
                }
            }
        }
    }

    return array( $backlog, $moduleId );
}

/**
 * Activate a module, its effect is
 * - to call the activation script of the module (if there is any)
 * - to modify the information in the main DB
 * @param  integer $moduleId : ID of the module that must be activated
 * @return array( backlog, boolean )
 *      backlog object
 *      boolean true if the activation process suceeded, false otherwise
 */
function activate_module($moduleId, $activateInAllCourses = false)
{
    $success = true;
    $backlog1 = new Backlog;
    // find module informations

    $tbl = claro_sql_get_main_tbl();
    $moduleInfo =  get_module_info($moduleId);

    list( $backlog2, $success ) = activate_module_in_platform($moduleId);
    
    if( ! $success )
    {
        return array( $backlog2, $success );
    }
    
    $backlog1->append($backlog2);
    
    if ( $activateInAllCourses && $moduleInfo['type'] == 'tool' /*&& $moduleInfo['activateInCourses'] == 'AUTOMATIC'*/ )
    {
        // FIXME : ONLY WHEN INSTALLING A MODULE !
        if ( activate_module_in_all_courses( $moduleInfo['label'] ) )
        {
            $success = true;
            $backlog1->success( get_lang('Module activation in courses succeeded'));
        }
        else
        {
            $success = false;
            $backlog1->failure( get_lang('Module activation in courses failed'));
        }
    }

    return array( $backlog1, $success );
}

/**
 * Activate the module for the plateforme
 * @param int $moduleId
 * @return array array( Backlog $backlog, boolean $success );
 * @todo remove the need of the Backlog and use Exceptions instead
 */
function activate_module_in_platform( $moduleId )
{
    $success = true;
    $backlog = new Backlog;
    // find module informations

    $tbl = claro_sql_get_main_tbl();

    // TODO : 1- call activation script (if any) from the module repository


    // 2- change related entry of module table in the main DB

    $sql = "UPDATE `" . $tbl['module']."`
            SET `activation` = 'activated'
            WHERE `id` = " . (int) $moduleId;

    $result = claro_sql_query($sql);

    if ( ! $result )
    {
        $success = false;
        $backlog->failure(get_lang( 'Cannot update database' ));
    }
    else
    {
        $backlog->success(get_lang( 'Database update successful' ));
        //5- cache file with the module's include must be renewed after activation of the module

        if ( generate_module_cache() )
        {
            $backlog->success(get_lang( 'Module cache update succeeded' ));
        }
        else
        {
            $backlog->failure(get_lang( 'Module cache update failed' ));
            $success = false;
        }
    }
    
    return array( $backlog, $success );
}

/**
 * Activate the module in all courses
 * @param string $moduleLabel
 * @return array array( Backlog $backlog, boolean $success );
 * @todo remove the need of the Backlog and use Exceptions instead
 */
function activate_module_in_all_courses( $toolLabel )
{
    $toolId = get_tool_id_from_module_label( $toolLabel );
    $tbl = claro_sql_get_main_tbl();
    
    $sql = "SELECT `code` FROM `" . $tbl['course'] . "`";

    $courseList = claro_sql_query_fetch_all( $sql );
    
    foreach ( $courseList as $course )
    {
        if ( ! update_course_tool_activation_in_course( $toolId,
            $course['code'],
            true ) )
        {
            return false;
        }
    }
    
    return true;
}

/**
 * Desactivate a module, its effect is
 *   - to call the desactivation script of the module (if there is any)
 *   - to modify the information in the main DB
 * @param  integer $moduleId : ID of the module that must be desactivated
 * @return array( backlog, boolean )
 *      backlog object
 *      boolean true if the deactivation process suceeded, false otherwise
 * @todo remove the need of the Backlog and use Exceptions instead
 */
function deactivate_module($moduleId)
{
    $success = true;
    $backlog = new Backlog;

    //find needed info :

    $moduleInfo =  get_module_info($moduleId);
    $tbl = claro_sql_get_main_tbl();

    // TODO : 1- call desactivation script (if any) from the module repository

    //4- change related entry in the main DB, module table

    $tbl = claro_sql_get_main_tbl();

    $sql = "UPDATE `" . $tbl['module'] . "`
            SET `activation` = 'desactivated'
            WHERE `id`= " . (int) $moduleId;

    $result = claro_sql_query($sql);

    if ( ! $result )
    {
        $success = false;
        $backlog->failure(get_lang( 'Cannot update database' ));
    }
    else
    {
        $backlog->success(get_lang( 'Database update successful' ));
        //5- cache file with the module's include must be renewed after desactivation of the module

        if ( generate_module_cache() )
        {
            $backlog->success(get_lang( 'Module cache update succeeded' ));
        }
        else
        {
            $backlog->failure(get_lang( 'Module cache update failed' ));
            $success = false;
        }
    }

    return array( $backlog, $success );
}

/**
 * Uninstall a specific module to the platform
 *
 * @param integer $moduleId the id of the module to uninstall
 * @return array( backlog, boolean )
 *      backlog object
 *      boolean true if the uninstall process suceeded, false otherwise
 * @todo remove the need of the Backlog and use Exceptions instead
 */
function uninstall_module($moduleId, $deleteModuleData = true)
{
    $success = true;
    $backlog = new Backlog;

    //first thing to do : deactivate the module

    // deactivate_module($moduleId);
    $moduleInfo =  get_module_info($moduleId);
    if ( ($moduleInfo['type'] =='tool') && $moduleId )
    {

        // 2- delete the module in the cours_tool table, used for every course creation

        list ( $backlog2, $success2 ) = unregister_module_from_courses( $moduleId );

        if ( $success2 )
        {
            $backlog->success( get_lang('Module uninstalled in all courses') );
        }
        else
        {
            $backlog->append( $backlog2 );
        }
    }

    //Needed tables and vars

    $tbl = claro_sql_get_main_tbl();

    $backlog = new Backlog;

    // 0- find info about the module to uninstall

    $sql = "SELECT `label`
              FROM `" . $tbl['module'] . "`
             WHERE `id` = " . (int) $moduleId;

    $module = claro_sql_query_get_single_row($sql);

    if ( $module == false )
    {
        $backlog->failure(get_lang("No module to uninstall"));
        $success = false;
    }
    else
    {
        // 1- Include the local 'uninstall.sql' and 'uninstall.php' file of the module if they exist

        // call uninstall.php first in case it requires module database schema to run
        if ( isset( $uninstallPhpScript ) ) unset ( $uninstallPhpScript );
        $uninstallPhpScript = get_module_path($module['label']) . '/setup/uninstall.php';
        if (file_exists( $uninstallPhpScript ))
        {
            language::load_translation( );
            language::load_locale_settings( );
            language::load_module_translation( $module['label'] );
            load_module_config( $module['label'] );
            
            require $uninstallPhpScript;
            
            $backlog->info( get_lang('Module uninstallation script called') );
        }

        if ( isset( $uninstallSqlScript ) ) unset ( $uninstallSqlScript );
        $uninstallSqlScript = get_module_path($module['label']) . '/setup/uninstall.sql';
        
        if ($deleteModuleData && file_exists( $uninstallSqlScript ))
        {
            $sql = file_get_contents( $uninstallSqlScript );
            if (!empty($sql))
            {
                $sql = str_replace ('__CL_MAIN__',get_conf('mainTblPrefix'), $sql);

                if ( false !== claro_sql_multi_query($sql) )
                {
                    $backlog->success(get_lang( 'Database uninstallation succeeded' ));
                }
                else
                {
                    $backlog->failure(get_lang( 'Database uninstallation failed' ));
                    $success = false;
                }
            }
        }
        elseif ( ! $deleteModuleData && file_exists( $uninstallSqlScript ) )
        {
            $backlog->info(get_lang( 'Database uninstallation skipped' ));
        }

        // 2- delete related files and folders

        $modulePath = get_module_path($module['label']);

        if ( file_exists($modulePath) )
        {
            if(claro_delete_file($modulePath))
            {
                $backlog->success( get_lang('Delete scripts of the module') );
            }
            else
            {
                $backlog->failure( get_lang('Error while deleting the scripts of the module') );
                $success = false;
            }
        }

        //  delete the module in the cours_tool table, used for every course creation

        //retrieve this module_id first

        $sql = "SELECT id as tool_id FROM `" . $tbl['tool']."`
                WHERE claro_label = '".$module['label']."'";
        $tool_to_delete = claro_sql_query_get_single_row($sql);
        $tool_id = $tool_to_delete['tool_id'];


        $sql = "DELETE FROM `" . $tbl['tool']."`
                WHERE claro_label = '".$module['label']."'
            ";

        claro_sql_query($sql);

        // 3- delete related entries in main DB

        $sql = "DELETE FROM `" . $tbl['module'] . "`
                WHERE `id` = ". (int) $moduleId;
        claro_sql_query($sql);

        $sql = "DELETE FROM `" . $tbl['module_info'] . "`
                WHERE `module_id` = " . (int) $moduleId;
        claro_sql_query($sql);
        
        $sql = "DELETE FROM `" . $tbl['module_contexts'] . "`
                WHERE `module_id` = " . (int) $moduleId;
        claro_sql_query($sql);

        // 4-Manage right - Delete read action
        $action = new RightToolAction();
        $action->setName('read');
        $action->setToolId($tool_id);
        $action->delete();

        // Manage right - Delete edit action
        $action = new RightToolAction();
        $action->setName('edit');
        $action->setToolId($tool_id);
        $action->delete();

        // 5- remove all docks entries in which the module displays
        // TODO FIXME handle failure
        remove_module_dock($moduleId, 'ALL');

        // 6- cache file with the module's include must be renewed after uninstallation of the module

        if ( ! generate_module_cache() )
        {
            $backlog->failure(get_lang( 'Module cache update failed' ));
            $success = false;
        }
        else
        {
            $backlog->success(get_lang( 'Module cache update succeeded' ));
        }
    }

    return array( $backlog, $success );

}

/**
 * Register module in all courses
 * @param int $moduleId
 * @return array( backlog, boolean )
 *      backlog object
 *      boolean true if suceeded, false otherwise
 * @todo remove the need of the Backlog and use Exceptions instead
 */
function register_module_in_courses( $moduleId )
{
    $backlog = new Backlog;
    $success = true;
    // TODO : remove fields script_url, claro_label, def_access, access_manager
    // TODO : rename def_rank to rank
    // TODO : secure this code against query failure
    $tbl = claro_sql_get_main_tbl();
    $moduleInfo =  get_module_info($moduleId);

    $tool_id = get_course_tool_id($moduleInfo['label'] );

    // 4- update every course tool list to add the tool if it is a tool

    // $module_type = $moduleInfo['type'];

    $sql = "SELECT `code` FROM `" . $tbl['course'] . "`";

    $course_list = claro_sql_query_fetch_all($sql);

    $default_visibility = false;

    foreach ($course_list as $course)
    {
        if ( false === register_module_in_single_course( $tool_id, $course['code'] ) )
        {
            $success = false;
            $backlog->failure(get_lang( 'Cannot update course database for %course'
                , array( '%course' => $course['code'] )));

            break;
        }
    }

    return array( $backlog, $success );
}

/**
 * Register module in a course
 * @param int $tool_id
 * @param string $course_id
 * @return boolean true if suceeded, false otherwise
 */
function register_module_in_single_course( $tool_id, $course_code )
{
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);
    $course_tbl = claro_sql_get_course_tbl($currentCourseDbNameGlu);
    $default_visibility = false;

    //find max rank in the tool_list

    $sql = "SELECT MAX(rank) AS maxrank FROM  `" . $course_tbl['tool'] . "`";
    $maxresult = claro_sql_query_get_single_row($sql);
    //insert the tool at the end of the list

    $sql = "INSERT INTO `" . $course_tbl['tool'] . "`
    SET tool_id      = " . $tool_id . ",
        rank         = (" . (int) $maxresult['maxrank'] . "+1),
        visibility   = '" . ( $default_visibility ? 1 : 0 ) . "',
        script_url   = NULL,
        script_name  = NULL,
        addedTool    = 'YES',
        `activated` = 'false',
        `installed` = 'false'";

    if ( false === claro_sql_query($sql) )
    {
        return false;
    }
    else
    {
        return true;
    }
}

/**
 * Unregister module in all courses
 * @param int $moduleId
 * @return array( backlog, boolean )
 *      backlog object
 *      boolean true if suceeded, false otherwise
 * @todo remove the need of the Backlog and use Exceptions instead
 */
function unregister_module_from_courses( $moduleId )
{
    $backlog = new Backlog;
    $success = true;
    //retrieve this module_id first

    $moduleInfo =  get_module_info($moduleId);
    $tbl = claro_sql_get_main_tbl();

    $sql = "SELECT id AS tool_id
              FROM `" . $tbl['tool']."`
             WHERE claro_label = '".$moduleInfo['label']."'";
    $tool_to_delete = claro_sql_query_get_single_row($sql);
    $tool_id = $tool_to_delete['tool_id'];


    // 3- update every course tool list to add the tool if it is a tool

    $sql = "SELECT `code` FROM `".$tbl['course']."`";
    $course_list = claro_sql_query_fetch_all($sql);


    foreach ($course_list as $course)
    {
        if ( false === unregister_module_from_single_course( $tool_id, $course['code'] ) )
        {
            $success = false;
            $backlog->failure(get_lang( 'Cannot update course database for %course'
                , array( '%course' => $course['code'] )));

            break;
        }
    }

    return array( $backlog, $success );
}

/**
 * Unregister module in a course
 * @param int $tool_id
 * @param string $course_code
 * @return boolean true if suceeded, false otherwise
 */
function unregister_module_from_single_course( $tool_id, $course_code )
{
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($course_code);
    $course_tbl = claro_sql_get_course_tbl($currentCourseDbNameGlu);

    $sql = "DELETE FROM `".$course_tbl['tool']."`
            WHERE  `tool_id` = " . (int)$tool_id;

    if ( false === claro_sql_query($sql) )
    {
        return false;
    }
    else
    {
        return true;
    }
}

/**
 * Set module visibility in all courses
 * @param int $moduleId id of the module
 * @param bool $visibility true for visible, false for invisible
 * @return array( backlog, boolean )
 *      backlog object
 *      boolean true if suceeded, false otherwise
 * @todo remove the need of the Backlog and use Exceptions instead
 */
function set_module_visibility( $moduleId, $visibility )
{
    $backlog = new Backlog;
    $success = true;

    $tbl = claro_sql_get_main_tbl();
    $moduleInfo =  get_module_info($moduleId);

    $tool_id = get_course_tool_id($moduleInfo['label'] );

    $sql = "SELECT `code` FROM `" . $tbl['course'] . "`";

    $course_list = claro_sql_query_fetch_all($sql);

    $default_visibility = false;

    foreach ($course_list as $course)
    {
        if ( false === set_module_visibility_in_course( $tool_id, $course['code'], $visibility ) )
        {
            $success = false;
            $backlog->failure(get_lang( 'Cannot change module visibility in %course'
                , array( '%course' => $course['code'] )));

            break;
        }
    }

    return array( $backlog, $success );
}

/**
 * Set module tool visibility in one course
 * @param int $tool_id id of the module tool
 * @param string $courseCode
 * @param bool $visibility true for visible, false for invisible
 * @return array( backlog, boolean )
 *      backlog object
 *      boolean true if suceeded, false otherwise
 * @todo remove the need of the Backlog and use Exceptions instead
 */
function set_module_visibility_in_course( $tool_id, $courseCode, $visibility )
{
    $currentCourseDbNameGlu = claro_get_course_db_name_glued( $courseCode );
    $course_tbl = claro_sql_get_course_tbl($currentCourseDbNameGlu);
    //$default_visibility = false;

    $sql = "UPDATE `" . $course_tbl['tool'] . "`
            SET visibility   = '" . ( $visibility ? 1 : 0 ) . "'
            WHERE `tool_id` = " . (int)$tool_id;

    if ( false === claro_sql_query($sql) )
    {
        return false;
    }
    else
    {
        return true;
    }
}

// MODULE REGISTRATION FUNCTIONS

/**
 * Add module in claroline, giving  its path
 *
 * @param string $modulePath
 * @return int module id or false
 * @todo remove the need of the Backlog and use Exceptions instead
 */
function register_module($modulePath)
{
    $backlog = new Backlog;
    if (file_exists($modulePath))
    {
        /*$parser = new ModuleManifestParser;
        $module_info = $parser->parse($modulePath.'/manifest.xml');*/
        
        $module_info = readModuleManifest( $modulePath );
        
        if ( false === $module_info )
        {
            $backlog->failure(get_lang( 'Cannot parse module manifest'));
            
            $moduleId = false;
        }
        elseif ( is_array($module_info)
            && false !== ($moduleId = register_module_core($module_info)) )
        {
            $backlog->failure(get_lang('Module %claroLabel registered',
                array('%claroLabel'=>$module_info['LABEL'])));
            
            if('TOOL' == strtoupper($module_info['TYPE']))
            {
                if (false !== ($toolId   = register_module_tool($moduleId,$module_info)))
                {
                    $backlog->failure(get_lang('Module %label registered as tool', array('%claroLabel'=>$module_info['LABEL'])));
            
                }
                else
                {
                    $backlog->failure( get_lang('Cannot register tool %label', array('%label' => $module_info['LABEL'])));
                }
            }
            elseif('APPLET' == strtoupper($module_info['TYPE']))
            {
                if ( array_key_exists('DEFAULT_DOCK',$module_info)
                    && is_array($module_info['DEFAULT_DOCK']) )
                {
                    foreach ( $module_info['DEFAULT_DOCK'] as $dock )
                    {
                        add_module_in_dock($moduleId, $dock);
                            $backlog->failure(get_lang('Module %label added in dock : %dock'
                            , array('%label' => $module_info['LABEL'], '%dock' => $dock)));
                        
                    }
                }
            }
        }
        else
        {
            $backlog->failure(get_lang('Cannot register module %label', array('%label' => $module_info['LABEL'])));
        }
    }
    else
    {
        $backlog->failure(get_lang('Cannot find module'));
    }

    return $moduleId;
}

/**
 * Add common info about a module in main module registry.
 * In Claroline this  info is split in two type of info
 * into two tables :
 * * module  for really use info,
 * * module_info for descriptive info
 *
 * @param array $module_info.
 * @return int moduleId in the registry.
 */
function register_module_core($module_info)
{
    $tbl             = claro_sql_get_tbl(array('module','module_info','tool','module_contexts'));
    $tbl_name        = claro_sql_get_main_tbl();

    $missingElement = array_diff(array('LABEL','NAME','TYPE'),array_keys($module_info));
    if (count($missingElement)>0)
    {
        return claro_failure::set_failure(get_lang('Missing elements in module Manifest : %MissingElements' , array('%MissingElements' => implode(',',$missingElement))));
    }
    
    if (isset($module_info['ENTRY']))
    {
        $script_url = $module_info['ENTRY'];
    }
    else
    {
        $script_url = 'entry.php';
    }

    $sql = "INSERT INTO `" . $tbl['module'] . "`
            SET label      = '" . claro_sql_escape($module_info['LABEL'      ]) . "',
                name       = '" . claro_sql_escape($module_info['NAME']) . "',
                type       = '" . claro_sql_escape($module_info['TYPE']) . "',
                script_url = '" . claro_sql_escape($script_url)."'
                ";
    $moduleId = claro_sql_query_insert_id($sql);

    $sql = "INSERT INTO `" . $tbl['module_info'] . "`
            SET module_id      = " . (int) $moduleId . ",
                version        = '" . claro_sql_escape($module_info['VERSION']) . "',
                author         = '" . claro_sql_escape($module_info['AUTHOR']['NAME']) . "',
                author_email   = '" . claro_sql_escape($module_info['AUTHOR']['EMAIL']) . "',
                author_website = '" . claro_sql_escape($module_info['AUTHOR']['WEB']) . "',
                website        = '" . claro_sql_escape($module_info['WEB']) . "',
                description    = '" . claro_sql_escape($module_info['DESCRIPTION']) . "',
                license        = '" . claro_sql_escape($module_info['LICENSE']) . "'";
    
    claro_sql_query($sql);
    
    foreach ( $module_info['CONTEXTS'] AS $context )
    {
        $sql = "INSERT INTO `{$tbl['module_contexts']}`\n"
            . "SET\n"
            . "  `module_id` = " . (int) $moduleId . ",\n"
            . "  `context` = '" . claro_sql_escape( $context ) . "'"
            ;
            
        claro_sql_query($sql);
    }

    return $moduleId;
}

/**
 * Store all unique info about a tool during install
 * @param integer $moduleId
 * @param array $moduleToolData, data from manifest
 * @return int tool id or false
 */

function register_module_tool($moduleId,$module_info)
{
    $tbl = claro_sql_get_tbl('course_tool');

    if ( is_array($module_info) )
    {
        $icon = array_key_exists('ICON',$module_info)
            ? "'" . claro_sql_escape($module_info['ICON']) . "'"
            : 'NULL'
            ;

        if ( !isset($module_info['ENTRY'])) $module_info['ENTRY'] = 'entry.php';

        // find max rank in the course_tool table

        $sql = "SELECT MAX(def_rank) AS maxrank FROM `" . $tbl['course_tool'] . "`";
        $maxresult = claro_sql_query_get_single_row($sql);

        // insert the new course tool

        $sql = "INSERT INTO `" . $tbl['course_tool'] ."`
                SET
                claro_label = '". claro_sql_escape($module_info['LABEL']) ."',
                script_url = '". claro_sql_escape($module_info['ENTRY']) ."',
                icon = " . $icon . ",
                def_access = 'ALL',
                def_rank = (". (int) $maxresult['maxrank']."+1),
                add_in_course = 'AUTOMATIC',
                access_manager = 'COURSE_ADMIN' ";

        $tool_id = claro_sql_query_insert_id($sql);

        // Init action/right

        // Manage right - Add read action
        $action = new RightToolAction();
        $action->setName('read');
        $action->setToolId($tool_id);
        $action->save();

        // Manage right - Add edit action
        $action = new RightToolAction();
        $action->setName('edit');
        $action->setToolId($tool_id);
        $action->save();

        // Init all profile/right

        $profileList = array_keys(claro_get_all_profile_name_list());

        foreach ( $profileList as $profileId )
        {
            $profile = new RightProfile();
            $profile->load($profileId);
            
            $profileRight = new RightProfileToolRight();
            $profileRight->load($profile);
            
            if ( claro_get_profile_id('manager') == $profileId )
            {
                $profileRight->setToolRight($tool_id,'manager');
            }
            else
            {
                $profileRight->setToolRight($tool_id,'user');
            }
            
            $profileRight->save();
        }

        return $tool_id;
    }
    else
    {
        return false ;
    }
}

// MODULE DOCK MANAGEMENT FUNCTIONS

/**
 * Set the dock in which the module displays its content
 * @param integer $moduleId id of the module to rename
 * @param string $newDockName new name  for the doc
 * @return boolean
 */
function add_module_in_dock( $moduleId, $newDockName )
{
    $tbl = claro_sql_get_main_tbl();

    //find info about this module occurence in this dock in the DB

    $sql = "SELECT D.`name`      AS dockname,
                   D.`rank`      AS oldRank
            FROM `" . $tbl['module'] . "` AS M
               , `" . $tbl['dock']   . "` AS D
            WHERE M.`id` = D.`module_id`
            AND M.`id` = " . (int) $moduleId . "
            AND D.`name` = '" . $newDockName . "'";
    $module = claro_sql_query_get_single_row($sql);

    //if the module is already in the dock ,we just do nothing and return true.

    if (isset($module['dockname']) && $module['dockname'] == $newDockName)
    {
        return true;
    }
    else
    {
        //find the highest rank already used in the new dock
        $max_rank = get_max_rank_in_dock($newDockName);
        // the module is not already in this dock, we just insert it into this in the DB

        $sql = "INSERT INTO `" . $tbl['dock'] . "`
                SET module_id = " . (int) $moduleId . ",
                    name    = '" . claro_sql_escape($newDockName) . "',
                    rank    = " . ((int) $max_rank + 1) ;
        $result = claro_sql_query($sql);

        // TODO FIXME handle failure
        generate_module_cache();

        return $result;
    }
}

/**
 * Remove a module from a dock in which the module displays
 * @param integer $moduleId
 * @param string  $dockName
 */

function remove_module_dock($moduleId, $dockName)
{
    $tbl = claro_sql_get_main_tbl();

    // call of this function to remove ALL occurence of the module in any dock

    if ('ALL' == $dockName)
    {
        //1- find all dock in which the dock displays

        $sql="SELECT `name` AS dockName
              FROM   `" . $tbl['dock'] . "`
              WHERE  `module_id` = " . (int) $moduleId;

        $dockList = claro_sql_query_fetch_all($sql);

        //2- re-call of this function which each dock concerned

        foreach($dockList as $dock)
        {
            remove_module_dock($moduleId,$dock['dockName']);
        }
    }
    else //call of this function to remove ONE SPECIFIC occurence of the module in the dock
    {
        //find the rank of the module in this dock :

        $sql = "SELECT `rank` AS oldRank
                FROM   `" . $tbl['dock'] . "`
                WHERE  `module_id` = " . (int) $moduleId . "
                AND    `name` = '" .$dockName . "'";
        $module = claro_sql_query_get_single_row($sql);

        //move up all modules displayed in this dock

        $sql = "UPDATE `" . $tbl['dock'] . "`
                SET `rank` = `rank` - 1
                WHERE `name` = '" . $dockName . "'
                AND `rank` > " . (int) $module['oldRank'];
        claro_sql_query($sql);

        //delete the module line in the dock table

        $sql = "DELETE FROM `" . $tbl['dock'] . "`
                WHERE `module_id` = " . (int) $moduleId. "
                AND   `name` = '" . $dockName . "'";
        claro_sql_query($sql);

        generate_module_cache();
    }
}

/**
 * Move a module inside its dock (change its position in the display
 * @param integer $moduleId
 * @param string $dockName
 * @param string $direction 'up' or 'down'
 */

function move_module_in_dock($moduleId, $dockName, $direction)
{
    $tbl = claro_sql_get_main_tbl();

    switch ($direction)
    {
        case 'up' :
        {
            //1-find value of current module rank in the dock
            $sql = "SELECT `rank`
                    FROM `" . $tbl['dock'] . "`
                    WHERE `module_id`=" . (int) $moduleId . "
                    AND `name`='" . claro_sql_escape($dockName) . "'";
            $result=claro_sql_query_get_single_value($sql);

            //2-move down above module
            $sql = "UPDATE `" . $tbl['dock'] . "`
                    SET `rank` = `rank`+1
                    WHERE `module_id` != " . (int) $moduleId . "
                    AND `name`       = '" . claro_sql_escape($dockName) . "'
                    AND `rank`       = " . (int) $result['rank'] . " -1 ";

            claro_sql_query($sql);

            //3-move up current module
            $sql = "UPDATE `" . $tbl['dock'] . "`
                    SET `rank` = `rank`-1
                    WHERE `module_id` = " . (int) $moduleId . "
                    AND `name`      = '" .  claro_sql_escape($dockName) . "'
                    AND `rank` > 1"; // this last condition is to avoid wrong update due to a page refreshment
            claro_sql_query($sql);

            break;
        }
        case 'down' :
        {
            //1-find value of current module rank in the dock
            $sql = "SELECT `rank`
                    FROM `" . $tbl['dock'] . "`
                    WHERE `module_id`=" . (int) $moduleId . "
                    AND `name`='" . claro_sql_escape($dockName) . "'";
            $result=claro_sql_query_get_single_value($sql);

            //this second query is to avoid a page refreshment wrong update

            $sqlmax= "SELECT MAX(`rank`) AS `max_rank`
                      FROM `" . $tbl['dock'] . "`
                      WHERE `name`='" .  claro_sql_escape($dockName) . "'";
            $resultmax=claro_sql_query_get_single_value($sqlmax);

            if ($resultmax['max_rank'] == $result['rank']) break;

            //2-move up above module
            $sql = "UPDATE `" . $tbl['dock'] . "`
                    SET `rank` = `rank` - 1
                    WHERE `module_id` != " . $moduleId . "
                    AND `name` = '" . claro_sql_escape($dockName) . "'
                    AND `rank` = " . (int) $result['rank'] . " + 1
                    AND `rank` > 1";
            claro_sql_query($sql);

            //3-move down current module
            $sql = "UPDATE `" . $tbl['dock'] . "`
                    SET `rank` = `rank` + 1
                    WHERE `module_id`=" . (int) $moduleId . "
                    AND `name`='" .  claro_sql_escape($dockName) . "'";
            claro_sql_query($sql);

            break;
        }
    }
    // TODO FIXME handle failure
    generate_module_cache();
}

/**
 * Function used by the SAX xml parser when the parser meets a opening tag
 * @param tring $dockName the dock from which we want this info
 * @return int the max rank used for this dock
 *
 */
function get_max_rank_in_dock($dockName)
{
    $tbl = claro_sql_get_main_tbl();


    $sql = "SELECT MAX(rank) AS mrank
            FROM `" . $tbl['dock'] . "` AS D
            WHERE D . `name` = '" . claro_sql_escape($dockName) . "'";
    $max_rank = claro_sql_query_get_single_value($sql);
    return (int) $max_rank;
}

/**
 * Return list of dock where a module is docked
 * @param integer $moduleId
 * @return array of array ( id, name)
 */
function get_module_dock_list($moduleId)
{
    static $dockListByModule = array();

    if(!array_key_exists($moduleId,$dockListByModule))
    {
        $tbl_name        = claro_sql_get_main_tbl();
        $sql = "SELECT `id`    AS dock_id,
                       `name`  AS dockname
            FROM `" . $tbl_name['dock'] . "`
            WHERE `module_id`=" . (int) $moduleId;
        $dockListByModule[$moduleId] = claro_sql_query_fetch_all($sql);

    }
    return $dockListByModule[$moduleId];
}

/**
 * Return list of dock aivailable for a given type
 * @param string $moduleType
 * @param string $context
 * @return array
 */
function get_dock_list($moduleType)
{
    $dockList   = array();
    switch($moduleType)
    {
        case 'applet' :
        {
            $dockList['campusBannerLeft'] = get_lang('Campus banner - left');
            $dockList['campusBannerRight'] = get_lang('Campus banner - right');
            $dockList['userBannerLeft'] = get_lang('User banner - left');
            $dockList['userBannerRight'] = get_lang('User banner - right');
            $dockList['courseBannerLeft'] = get_lang('Course banner - left');
            $dockList['courseBannerRight'] = get_lang('Course banner - right');
            $dockList['campusHomePageTop'] = get_lang('Campus homepage - top');
            $dockList['campusHomePageBottom'] = get_lang('Campus homepage - bottom');
            $dockList['campusHomePageRightMenu'] = get_lang('Campus homepage - right menu');
            $dockList['campusFooterCenter'] = get_lang('Campus footer - center');
            $dockList['campusFooterLeft'] = get_lang('Campus footer - left');
            $dockList['campusFooterRight'] = get_lang('Campus footer - right');
            $dockList['userProfileBox'] = get_lang('User profile box');
            break;
        }
        case 'tool' :
        {
            $dockList['commonToolList'] = get_lang('Tool list');
        }
    }
    return $dockList;
}

// COURSE TOOL RANK MANAGEMENT FUNCTIONS

/**
 * Move the place of the module in the module list
 * (it changes the value of def_rank in the course_tool table)
 * @param $moduleId id of the module tool to move
 * @param $sense should either 'up' or 'down' to know in which direction the module has to move in the list
 */
function move_module_tool($toolId, $sense)
{
   $tbl_mdb_names        = claro_sql_get_main_tbl();
   $tbl_tool_list        = $tbl_mdb_names['tool'];

   $current_rank = get_course_tool_rank($toolId);

   switch($sense)
   {
        case 'up':
        {
            $min_rank = get_course_tool_min_rank();
            if ($current_rank == $min_rank) //do not allow to move up if this is the first in the list
            {
                return false;
            }
            else
            {
                $before_rank = get_before_course_tool($current_rank);

                //SWAP the two ranks

                $sql = "UPDATE `".$tbl_tool_list."`
                        SET def_rank = '".$current_rank."' WHERE def_rank = '".$before_rank."'";
                claro_sql_query($sql);

                $sql = "UPDATE `".$tbl_tool_list."`
                        SET def_rank = '".$before_rank."' WHERE id = '".$toolId."'";
                claro_sql_query($sql);

            }
        }
        break;

        case 'down':
        {
            $max_rank = get_course_tool_max_rank();
            if ($current_rank == $max_rank) //do not allow to move down if this is the last in the list
            {
                return false;
            }
            else
            {
                $next_rank = get_next_course_tool($current_rank);

                //SWAP the two ranks

                $sql = "UPDATE `".$tbl_tool_list."`
                        SET def_rank = ".$current_rank." WHERE def_rank = ".$next_rank;
                claro_sql_query($sql);

                $sql = "UPDATE `".$tbl_tool_list."`
                        SET def_rank = ".$next_rank." WHERE id = ".$toolId;
                claro_sql_query($sql);
            }
        }
        break;
   }
   return true;
}

/**
 * Get the rank of the tool in the course tool list
 * @param int $toolId id of the course_tool module to get rank
 * @return the value of the rank (def_rank table)
 */
function get_course_tool_rank($toolId)
{
    $tbl_mdb_names        = claro_sql_get_main_tbl();
    $tbl_tool_list        = $tbl_mdb_names['tool'];

    $sql = "SELECT def_rank
            FROM `" . $tbl_tool_list . "`
            WHERE id=".(int)$toolId;
    return claro_sql_query_get_single_value($sql);
}

/**
 *
 * @return int the rank of the course_tool just after the course tool of rank
 *  $rank in the list
 */
function get_next_course_tool( $rank )
{
    $tbl_mdb_names        = claro_sql_get_main_tbl();
    $tbl_tool_list        = $tbl_mdb_names['tool'];

    $sql = "SELECT def_rank
            FROM `" . $tbl_tool_list . "`
            WHERE (def_rank>".(int)$rank.") ORDER BY def_rank";

    $result = claro_sql_query_get_single_value($sql);

    return $result;
}

/**
 *
 * @return integer the value of the rank of the course_tool just before the
 * course toolof rank $rank in the list
 */
function get_before_course_tool($rank)
{
    $tbl_mdb_names        = claro_sql_get_main_tbl();
    $tbl_tool_list        = $tbl_mdb_names['tool'];

    $sql = "SELECT def_rank
            FROM `" . $tbl_tool_list . "`
            WHERE (def_rank<".(int)$rank.") ORDER BY def_rank DESC";
    return claro_sql_query_get_single_value($sql);
}


/**
 * get maximum position already used in the course_tool of the def_rank value
 * @return : the maximum value
 */
function get_course_tool_max_rank()
{
    $tbl = claro_sql_get_main_tbl();

    $sql = "SELECT MAX(def_rank) as maxrank FROM `" . $tbl['tool'] . "`";
    return claro_sql_query_get_single_value($sql);
}

/**
 * get minimum position already used in the course_tool of the def_rank value
 * @return : the minimum value
 */
function get_course_tool_min_rank()
{
    $tbl = claro_sql_get_main_tbl();

    $sql = "SELECT MIN(def_rank) as minrank FROM `" . $tbl ['tool'] . "`";
    return claro_sql_query_get_single_value($sql);
}

/**
 * Check  if the  given  file path point on a claroline package file
 * @param string $packagePath
 * @return boolean
 */
function is_package_file($packagePath)
{
     $packagePath= realpath($packagePath);
     if (!file_exists($packagePath)) return false;
     if (!is_file($packagePath)) return false;
     if (is_dir($packagePath)) return false;
     if ('.zip' == strtolower(substr($packagePath,-4,4))) return true;
     return false;
}

/**
 * Is the tool already installed in the course
 * @param int $toolId main tool id
 * @param string $courseId course code
 * @return boolean
 */
function course_tool_already_installed( $toolId, $courseId )
{
    $tbl_cdb_names = claro_sql_get_course_tbl( claro_get_course_db_name_glued($courseId) );
    $tblCourseToolList = $tbl_cdb_names['tool'];
    
    $sql = "SELECT `installed`\n"
        . "FROM `{$tblCourseToolList}`\n"
        . "WHERE tool_id = " . (int) $toolId
        ;
    
    return claro_sql_query_fetch_single_value($sql) == 'true';
}

/**
 * Is the tool already registered in the course
 * @param int $toolId main tool id
 * @param string $courseId course code
 * @return boolean
 */
function is_tool_registered_in_course( $toolId, $courseId )
{
    $currentCourseDbNameGlu = claro_get_course_db_name_glued($courseId);
    $course_tbl = claro_sql_get_course_tbl($currentCourseDbNameGlu);
    $default_visibility = false;

    //find max rank in the tool_list

    $sql = "SELECT count(*) FROM `" . $course_tbl['tool'] . "`
    WHERE tool_id      = " . $toolId;

    return claro_sql_query_fetch_single_value($sql);
}

/**
 * Change the activation status for the given tool in the given course
 * @param int $toolId main tool id
 * @param string $courseId
 * @param boolean $activated
 * @return boolean
 */
function update_course_tool_activation_in_course( $toolId, $courseId, $activated )
{
    if ( $activated && !course_tool_already_installed($toolId,$courseId) )
    {
        $tLabel = get_module_label_from_tool_id( $toolId );

        if ( $tLabel )
        {
            if ( ! is_tool_registered_in_course( $toolId, $courseId ) )
            {
                register_module_in_single_course( $toolId, $courseId );
            }
            
            install_module_in_course( $tLabel, $courseId );
            update_tool_installation_in_course( $toolId, $courseId );
        }
    }
    
    $sql_activated = $activated ? "'true'" : "'false'";
    
    $tbl_cdb_names = claro_sql_get_course_tbl( claro_get_course_db_name_glued($courseId) );
    $tblCourseToolList = $tbl_cdb_names['tool'];
    
    $sql = "UPDATE `{$tblCourseToolList}`\n"
        . "SET `activated` = " . $sql_activated . "\n"
        . "WHERE tool_id = " . (int) $toolId
        ;
        
    if ( claro_sql_query( $sql ) )
    {
        return claro_sql_affected_rows();
    }
    else
    {
        false;
    }
}

/**
 * Change the tool installation status in the course
 * @param int $toolId main tool id
 * @param string $courseId
 * @return boolean
 */
function update_tool_installation_in_course( $toolId, $courseId )
{
    $tbl_cdb_names = claro_sql_get_course_tbl( claro_get_course_db_name_glued($courseId) );
    $tblCourseToolList = $tbl_cdb_names['tool'];
    
    $sql = "UPDATE `{$tblCourseToolList}`\n"
        . "SET `installed` = 'true'\n"
        . "WHERE tool_id = " . (int) $toolId
        ;
        
    if ( claro_sql_query( $sql ) )
    {
        return claro_sql_affected_rows() == 1;
    }
    else
    {
        false;
    }
}

/**
 * Is the module registered in the given course ?
 * @param int $toolId main tool id
 * @param string $courseId course code
 * @return boolean
 */
function is_module_registered_in_course( $toolId, $courseId )
{
    $tbl_cdb_names = claro_sql_get_course_tbl( claro_get_course_db_name_glued($courseId) );
    $tblCourseToolList = $tbl_cdb_names['tool'];
    
    $sql = "SELECT COUNT(*) FROM `{$tblCourseToolList}`\n"
        . "WHERE tool_id = " . (int) $toolId;
    
    $res = claro_sql_query_fetch_single_value( $sql );
    
    return $res;
}

/**
 * Get the list of modules that cannot be deactivated
 * @return array
 */
function get_not_deactivable_tool_list()
{
    return array(
        'CLDOC',
        'CLGRP',
        'CLUSR',
        'CLFRM',
        'CLLNP'
    );
}

/**
 * Get the list of modules that cannot be uninstalled
 * @return array
 */
function get_not_uninstallable_tool_list()
{
    return array(
        'CLANN',
        'CLCAL',
        'CLFRM',
        'CLCHT',
        'CLDOC',
        'CLDSC',
        'CLUSR',
        'CLLNP',
        'CLQWZ',
        'CLWRK',
        'CLWIKI',
        'CLLNK',
        'CLGRP'
    );
}

/**
 * Set the autoactivation the given module in courses
 * @param string $moduleLabel
 * @param boolean $value
 */
function set_module_autoactivation_in_course( $moduleLabel, $autoActivation )
{
    $sql_autoActivation = $autoActivation ? 'AUTOMATIC' : 'MANUAL';

    /* @todo move to a lib */
    $tbl_mdb_names        = claro_sql_get_main_tbl();
    $tbl_tool_list        = $tbl_mdb_names['tool'];

    return Claroline::getDatabase()->exec("
        UPDATE
            `{$tbl_tool_list}`
        SET
            add_in_course = '{$sql_autoActivation}'
        WHERE
            claro_label = " . Claroline::getDatabase()->quote( $moduleLabel ) . ";
    ");
}

/**
 * Allow course managers to activate the given module in their courses
 * @param string $moduleLabel
 * @param boolean $value
 */
function allow_module_activation_by_course_manager( $moduleLabel, $courseManagerCanActivate )
{
    $sql_accessManager = $courseManagerCanActivate ? 'COURSE_ADMIN' : 'PLATFORM_ADMIN';

    /* @todo move to a lib */
    $tbl_mdb_names        = claro_sql_get_main_tbl();
    $tbl_tool_list        = $tbl_mdb_names['tool'];

    return Claroline::getDatabase()->exec("
        UPDATE
            `{$tbl_tool_list}`
        SET
            access_manager = '{$sql_accessManager}'
        WHERE
            claro_label = " . Claroline::getDatabase()->quote( $moduleLabel ) . ";
    ");
}

/**
 * Get the count of modules by type.
 * @param bool $onlyActivated set to true to count only activated module 
 *      (default)
 * @return array [type => count]
 * @since Claroline 1.9.10, 1.10.7, 1.11
 */
function count_modules_by_type( $onlyActivated = true )
{
    $cnt = array();
    
    foreach ( get_available_module_types() as $moduleType )
    {
        $cnt[$moduleType] = 0;
    }
    
    $tbl = claro_sql_get_main_tbl();
    
    if ( $onlyActivated )
    {
        $activation = "WHERE `activation` = 'activated'";
    }
    else
    {
        $activation = "WHERE 1 = 1";
    }
    
    $rs = Claroline::getDatabase()->query("
        SELECT 
            `type`,
            COUNT(*) AS `count`
        FROM 
            `{$tbl['module']}`
        {$activation}
        GROUP BY `type`" );
    
    foreach ( $rs as $moduleTypeCount )
    {
        $cnt[$moduleTypeCount['type']] = $moduleTypeCount['count'];
    }
    
    return $cnt;
}

/**
 * Get the list of module types available on the platform
 * @return type 
 * @since Claroline 1.9.10, 1.10.7, 1.11
 */
function get_available_module_types()
{
    return array( 'tool', 'applet', 'crsmanage', 'admin' );
}

/**
 * Set the visibility this tool visible at the course creation
 * @param int moduleId
 * @param boolean visibility
 * since claroline 1.11
 */
function set_tool_visibility_at_course_creation($moduleId,$visibility)
{
    $tbl_mdb_names        = claro_sql_get_main_tbl();
    $tbl_tool_list        = $tbl_mdb_names['tool'];

    if ($visibility == 1) $def_access = 1;
    else $def_access = 5;
    
    return Claroline::getDatabase()->exec("
        UPDATE
            `{$tbl_tool_list}`
        SET
            def_access = '{$def_access}'
        WHERE
            id = " . Claroline::getDatabase()->quote( $moduleId ) . ";
    ");
}