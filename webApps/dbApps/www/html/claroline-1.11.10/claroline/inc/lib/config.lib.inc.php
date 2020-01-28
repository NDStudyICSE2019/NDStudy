<?php // $Id: config.lib.inc.php 13553 2011-09-07 13:18:33Z zefredz $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * Config lib contain function to manage conf file
 *
 * @version 1.8 $Revision: 13553 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see     http://www.claroline.net/wiki/config_def/
 * @package CONFIG
 * @author  Claro Team <cvs@claroline.net>
 * @author  Christophe Gesch� <moosh@claroline.net>
 * @author  Mathieu Laurent <laurent@cerdecam.be>
 */

require_once dirname(__FILE__) . '/config.class.php';
require_once dirname(__FILE__) . '/module/manage.lib.php';

/**
 * Proceed to rename conf.php.dist file in unexisting .conf.php files
 *
 * @param string $file syspath:complete path to .dist file
 *
 * @return boolean whether succes return true
 *
 * @author Mathieu Laurent <laurent@cerdecam.be>
 *
 */

function claro_undist_file ($distFile,$destinationPath='')
{
    if ( !empty($destinationPath) )
    {
        // get destination path
        $destinationPath = realpath($destinationPath);
    }
    else
    {
        // get directory of file.dist
        $destinationPath = dirname($distFile);
    }

    $filename = basename($distFile);
    $distFile = $distFile . '.dist';
    $undistFile = $destinationPath . '/' . $filename;

    if ( !file_exists($undistFile))
    {
        if ( file_exists($distFile))
        {
            /**
             * @var $perms file permission of dist file are keep to set perms of new file
             */

            $perms = fileperms($distFile);

            /**
             * @var $group internal var for affect same group to new file
             */

            $group = filegroup($distFile);

            // $perms|bindec(110000) <- preserve perms but force rw right on group
            @copy($distFile,$undistFile);
            @chmod($undistFile,$perms|bindec(110000));
            @chgrp($undistFile,$group);

            if (file_exists($undistFile))
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }
    else
    {
        return true;
    }
}

/**
 * The boolean value as string
 *
 * @param $booleanState boolean
 *
 * @return string boolean value as string
 *
 */

function trueFalse($booleanState)
{
    return ($booleanState?'TRUE':'FALSE');
}

/**
 * return the path of a def file following the configCode
 *
 * @param string $configCode
 * @return path
 *
 * @todo $centralizedDef won't be hardcoded.
 */

function claro_get_conf_def_file($configCode)
{
    $centralizedDef = array('CLCRS','CLAUTH', 'CLMSG', 'CLSSO',  'CLCAS', 'CLHOME', 'CLKCACHE','CLLINKER','CLMAIN','CLPROFIL' ,'CLRSS','CLICAL','CLGRP');
    if(in_array($configCode,$centralizedDef)) return realpath(get_path('incRepositorySys') . '/conf/def/') . '/' ;
    else                                      return get_module_path($configCode) . '/conf/def/';
}

/**
 * Generate the conf for a given config
 *
 * @param  object $config instance of config to manage.
 * @param  array $properties array of properties to changes
 *
 * @return array list of messages and error tag
 */

function generate_conf(&$config,$properties = null)
{
    // load configuration if not loaded before
    if ( !$config->def_loaded )
    {
        if ( !$config->load() )
        {
            // error loading the configuration
            $message = $config->backlog->output();
            return array($message , false);
        }
    }

    $config_code = $config->conf_def['config_code'];
    $config_name = $config->conf_def['config_name'];

    // validate config
    if ( $config->validate($properties) )
    {
        // save config file
        $config->save();
        $message = get_lang('Properties for %config_name, (%config_code) are now effective on server.'
        , array('%config_name' => $config_name, '%config_code' => $config_code));
    }
    else
    {
        // no valid
        $error = true ;
        $message = $config->backlog->output();
    }

    if (!empty($error))
    {
        return array ($message, true);
    }
    else
    {
        return array ($message, false);
    }
}

/**
 * Return list of folder where we can retrieve definition configuration file
 */

function get_def_folder_list ( $type = 'all' )
{
    $folderList = array();

    // Kernel folder configuration folder
    if ( $type == 'kernel' || $type == 'all') $folderList[] = get_path('incRepositorySys') . '/conf/def';

    // Module folder configuration folder
    if ($type == 'module' || $type == 'all')
    {
        $moduleList = get_installed_module_list();

        foreach ($moduleList as $module)
        {
            $modulePath = get_module_path($module) . '/conf/def';
            if (is_dir($modulePath)) $folderList[] = $modulePath;
        }
    }

    return $folderList ;
}

/**
 * Return array list of found definition files
 * @return array list of found definition files
 */

function get_config_code_list($type = 'all')
{
    $configCodeList = array();
    $defFolderList = get_def_folder_list($type);

    foreach ($defFolderList as $defFolder)
    {
        if ( is_dir($defFolder) && $handle = opendir($defFolder))
        {
            while ( ($file = readdir($handle)) !== false )
            {
                if ($file != "." && $file != ".." && substr($file, -17)=='.def.conf.inc.php')
                {
                    $config_code = str_replace('.def.conf.inc.php','',$file);
                    $configCodeList[] = $config_code ;
                }
            }
            closedir($handle);
        }
    }

    return $configCodeList;
}

/**
 * Return config code list with name and class of the configuration
 */

function get_config_code_class_list ( $type = 'all' )
{
    $configCodeClassList = array();
    $configCodeList = get_config_code_list($type);

    foreach ( $configCodeList as $config_code )
    {
        $config = new Config($config_code);

        if ( $config->load() )
        {
            $configCodeClassList[$config_code]['name'] = $config->get_conf_name();
            $configCodeClassList[$config_code]['class'] = $config->get_conf_class();
        }
    }

    return $configCodeClassList ;
}
