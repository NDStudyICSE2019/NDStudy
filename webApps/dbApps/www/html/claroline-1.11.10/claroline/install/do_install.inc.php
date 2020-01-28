<?php // $Id: do_install.inc.php 14712 2014-02-17 08:30:15Z zefredz $

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * This part of script is include on run_intall step of  setup tool.
 * in this  part. Script try to run Install
 * if  all is right $display still DISP_RUN_INSTALL_COMPLETE set on start
 * if  any problem happend, $display is switch to DISP_RUN_INSTALL_NOT_COMPLETE
 * and a  flag to mark what's happend is set.
 * in DISP_RUN_INSTALL_NOT_COMPLETE the screen show an explanation about problem and
 * prupose to back  to correct or to accept and continue.
 * First block is about database
 * Second block is  writing config
 * third block is building paths
 * Forth block check some right
 *
 * @version     $Revision: 14712 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/index.php/Install
 * @author      Claro Team <cvs@claroline.net>
 * @author      Christophe Gesche <moosh@claroline.net>
 * @package     INSTALL
 *
 * @todo check if dbexist would be improve for check if table exists, not if db exist.
 */

try
{
    include_once dirname(__FILE__) . '/installer.class.php';

    ! defined( 'CLARO_FILE_PERMISSIONS' ) && define( 'CLARO_FILE_PERMISSIONS', 0777 );
    $display = DISP_RUN_INSTALL_COMPLETE; //  if  all is righ $display don't change

     // PATCH TO ACCEPT Prefixed DBs
    $mainDbName     = $dbNameForm;
    $statsDbName    = $dbStatsForm;

    $runfillMainDb = FALSE;
    $runfillStatsDb = FALSE;

    /////////////////////////////////////////
    // MAIN DB                             //
    // DB with central info  of  Claroline //

    mysql_query("CREATE DATABASE `" . $mainDbName . "`");

    if (mysql_errno() >0)
    {
        if (mysql_errno() == 1007)
        {   // DB already exist
            if ($confirmUseExistingMainDb)
            {
                $runfillMainDb = TRUE;
                $mainDbSuccesfullCreated = TRUE;
            }
            else
            {
                $mainDbNameExist = TRUE;
                $display = DISP_DB_NAMES_SETTING;
            }
        }
        else
        {   // other error would  break install
            $mainDbNameCreationError
            = '<P class="setup_error">' . "\n"
            . '<font color="red">Warning !</font>' . "\n"
            . '<small>[' . mysql_errno() . '] - ' . mysql_error() . '</small>' . "\n"
            . '<br />' . "\n"
            . 'Error on creation ' . get_lang('Main database') . ' : <I>' . $dbHostForm . '</I>' . "\n"
            . '<br />' . "\n"
            . '<font color="blue">' . "\n"
            . 'Fix this problem before going further' . "\n"
            . '</font>' . "\n"
            . '<P>' . "\n"
            . '<input type="submit" name="' . $cmdName[DISP_DB_CONNECT_SETTING] . '" value="-&gt; ' . $panelTitle[DISP_DB_CONNECT_SETTING] . '" />' . "\n"
            . '</P>' . "\n"
            . '</P>' . "\n"
            ;
            $display = DISP_RUN_INSTALL_NOT_COMPLETE;
            $stepStatus[DISP_DB_CONNECT_SETTING] = 'X';
        }
    }
    else
    {
        $runfillMainDb = TRUE;
        $confirmUseExistingMainDb = TRUE;
    }


    /////////////////////////////////////////
    // STATS DB                            //
    // DB with tracking info of  Claroline //
    if($statsDbName != $mainDbName)
    {
        if(!$singleDbForm)
        {
            // multi DB mode AND tracking has its own DB so create it
            mysql_query("CREATE DATABASE `" . $statsDbName . "`");

            if (mysql_errno() >0)
            {
                if (mysql_errno() == 1007)
                {
                    if ($confirmUseExistingStatsDb)
                    {
                        $runfillStatsDb = TRUE;
                        $statsDbSuccesfullCreated = TRUE;
                    }
                    else
                    {
                        $statsDbNameExist = TRUE;
                        $display = DISP_RUN_INSTALL_NOT_COMPLETE;
                        $stepStatus[DISP_DB_CONNECT_SETTING] = 'X';
                    }
                }
                else
                {
                    $statsDbNameCreationError
                    = '<P class="setup_error">' . "\n"
                    . '<font color="red">Warning !</font>' . "\n"
                    . '<small>[' . mysql_errno() . '] - ' . mysql_error() . '</small>' . "\n"
                    . '<br />' . "\n"
                    . 'Error on creation ' . get_lang('Tracking database') . ' : <I>' . $dbStatsForm . '</I>' . "\n"
                    . '<br />' . "\n"
                    . '<font color="blue">' . "\n"
                    . 'Fix this problem before going further' . "\n"
                    . '</font>' . "\n"
                    . '<p>' . "\n"
                    . '<input type="submit" name="' . $cmdName[DISP_DB_CONNECT_SETTING] . '" value="-&gt; ' . $panelTitle[DISP_DB_CONNECT_SETTING] . '" />' . "\n"
                    . '</p>' . "\n"
                    . '</p>'
                    ;
                    $display = DISP_RUN_INSTALL_NOT_COMPLETE;
                    $stepStatus[DISP_DB_CONNECT_SETTING] = 'X';
                }
            }
            else
            {
                $runfillStatsDb = TRUE;
            }
        }
        else
        {
            // single DB mode so $statsDbName MUST BE the SAME than $mainDbName
            // because it's actually singleDB and not singleCourseDB
            $statsDbName = $mainDbName;
            $runfillStatsDb = TRUE;
        }
    }
    else
    {
        $runfillStatsDb = true;
        $confirmUseExistingStatsDb = TRUE;
    }

    $userPasswordCrypted = $encryptPassForm;
    $mainDbName     = $dbNameForm;
    $statsDbName    = $dbStatsForm;
    $mainTblPrefix  = $mainTblPrefixForm;
    $statsTblPrefix = $statsTblPrefixForm;
    $tbl_mdb_names = claro_sql_get_main_tbl();

    if ($runfillMainDb && $runfillStatsDb)
    {
        // initialise main and stats database

        $installer = new ClaroInstaller(
            $mainDbName.'`.`'.$mainTblPrefix,
            $statsDbName.'`.`'.$statsTblPrefix,
            'displayDbError'
        );
        // drop existing main and stats database tables
        $installer->executeSqlScript( file_get_contents( dirname(__FILE__) . '/uninstall.sql' ) );
        // create main and stats database tables
        $installer->executeSqlScript( file_get_contents( dirname(__FILE__) . '/install.sql' ) );

        // FILE SYSTEM OPERATION
        //
        // Build path

        $rootSys = rtrim( str_replace("\\","/",realpath($pathForm)), '/' ) ."/";

        $coursesRepositoryAppend    = '';
        $coursesRepositorySys       = $rootSys . $courseRepositoryForm;
        if (! file_exists($coursesRepositorySys)) claro_mkdir($coursesRepositorySys, CLARO_FILE_PERMISSIONS,true);
        $clarolineRepositoryAppend  = 'claroline/';
        $clarolineRepositorySys     = $rootSys . $clarolineRepositoryAppend;
        $garbageRepositorySys   = $rootSys  . 'tmp/garbage';
        if (! file_exists($garbageRepositorySys))       claro_mkdir($garbageRepositorySys, CLARO_FILE_PERMISSIONS,true);
        if (! file_exists($rootSys . 'platform/'))      claro_mkdir($rootSys . 'platform/', CLARO_FILE_PERMISSIONS,true);
        if (! file_exists(claro_get_conf_repository())) claro_mkdir( claro_get_conf_repository() , CLARO_FILE_PERMISSIONS,true);

        ########################## WRITE claro_main.conf.php ##################################
        // extract the path to append to the url
        // if Claroline is not installed on the web root directory

        //$urlAppendPath = ereg_replace ("claroline/install/index.php", "", $_SERVER['PHP_SELF']);

        // here I want find  something to get garbage out of documentRoot
        $configFilePath = claro_get_conf_repository() . $configFileName;

        $fd = @fopen($configFilePath, 'w');
        if (!$fd)
        {
            $fileConfigCreationError = true;
            $display = DISP_RUN_INSTALL_NOT_COMPLETE;
        }
        else
        {
            // get value form installer form
            $form_value_list['platform_id'] = md5(realpath(__FILE__));
            $form_value_list['rootWeb'] = $urlForm;
            $form_value_list['urlAppend'] = $urlAppendPath;
            $form_value_list['rootSys'] = $rootSys;
            $form_value_list['dbHost'] =  $dbHostForm;
            $form_value_list['dbLogin'] = $dbUsernameForm;
            $form_value_list['dbPass'] = $dbPassForm;
            $form_value_list['mainDbName'] = $mainDbName;
            $form_value_list['mainTblPrefix'] = $mainTblPrefixForm;
            $form_value_list['statsDbName'] = $statsDbName;
            $form_value_list['statsTblPrefix'] = $statsTblPrefixForm ;
            $form_value_list['dbNamePrefix'] = $dbPrefixForm;
            $form_value_list['is_trackingEnabled'] = (bool) ($enableTrackingForm);
            $form_value_list['singleDbEnabled'] = (bool) ($singleDbForm);
            $form_value_list['courseTablePrefix'] = $singleDbForm && empty($dbPrefixForm)?'crs_':'';
            $form_value_list['dbGlu'] = $singleDbForm?'_':'`.`';
            $form_value_list['clarolineRepositoryAppend'] = 'claroline/';
            $form_value_list['coursesRepositoryAppend'] = rtrim($courseRepositoryForm,'/').'/';
            $form_value_list['rootAdminAppend'] = 'admin/';
            $form_value_list['imgRepositoryAppend'] = $imgRepositoryAppendForm;
            $form_value_list['userImageRepositoryAppend'] = $userImageRepositoryAppendForm ;
            $form_value_list['clarolineRepositorySys'] = $rootSys.$clarolineRepositoryAppend;
            $form_value_list['clarolineRepositoryWeb'] = $urlAppendPath.'/'.$clarolineRepositoryAppend;
            $form_value_list['coursesRepositorySys'] = $rootSys.$coursesRepositoryAppend;
            $form_value_list['coursesRepositoryWeb'] = $urlAppendPath.'/'.$coursesRepositoryAppend;
            $form_value_list['rootAdminSys'] = $clarolineRepositorySys.$rootAdminAppend;
            $form_value_list['rootAdminWeb'] = $clarolineRepositoryWeb.$rootAdminAppend;
            $form_value_list['garbageRepositorySys'] = $garbageRepositorySys;
            $form_value_list['siteName'] = $campusForm;
            $form_value_list['administrator_name'] = $contactNameForm;
            $form_value_list['administrator_phone'] = $contactPhoneForm;
            $form_value_list['administrator_email'] = (empty($contactEmailForm)?$adminEmailForm:$contactEmailForm);
            $form_value_list['institution_name'] = $institutionForm;
            $form_value_list['institution_url'] = $institutionUrlForm;
            $form_value_list['userPasswordCrypted'] = (bool) $encryptPassForm;
            $form_value_list['allowSelfReg']     = (bool) $allowSelfReg;
            $form_value_list['platformLanguage'] = $languageForm ;
            $form_value_list['claro_stylesheet'] = 'classic';
            $form_value_list['clmain_serverTimezone'] = $clmain_serverTimezone;

            ######### DEALING WITH FILES #########################################

            /**
             * Config file to undist
             */

            /*$arr_file_to_undist =
            array (
            $newIncludePath . '../auth/extauth/drivers/auth.drivers.conf.php' => $rootSys . 'platform/conf'
            );

            foreach ($arr_file_to_undist as $undistFile => $undistPath)
            {
                claro_undist_file($undistFile,$undistPath);
            }*/

            /***
             * Generate kernel conf from definition files.
             */

            $includePath = $newIncludePath;
            $config_code_list = get_config_code_list('kernel');
            $configError=false;
            $messageConfigErrorList = array();

            if ( is_array($config_code_list) )
            {
                foreach ( $config_code_list as $config_code )
                {
                    // new config object
                    $config = new Config($config_code);

                    // generate conf
                    list ($message, $configKernelError) = generate_conf($config,$form_value_list);
                    if($configKernelError)
                    {
                        $configError = true;
                        $messageConfigErrorList[] = $message;
                    }

                }
                unset($configToolError);
            }
        }


        // write currentVersion.inc.php

        $fp_currentVersion = fopen($rootSys . 'platform/currentVersion.inc.php','w');
        $currentVersionStr = '<?php
        $clarolineVersion = "'.$new_version.'";
        $versionDb = "'.$new_version.'";
        ?>';
        fwrite($fp_currentVersion, $currentVersionStr);
        fclose($fp_currentVersion);

        // Check File System

        $coursesRepositorySysWriteProtected = FALSE;
        $coursesRepositorySysMissing        = FALSE;
        $garbageRepositorySysWriteProtected = FALSE;
        $garbageRepositorySysMissing        = FALSE;
        $platformConfigRepositorySysWriteProtected = FALSE;
        $platformConfigRepositorySysMissing        = FALSE;
        if (file_exists($coursesRepositorySys))
        {
            if (!is_writable($coursesRepositorySys))
            {
                $coursesRepositorySysWriteProtected = TRUE;
                $display = DISP_RUN_INSTALL_NOT_COMPLETE;
            }
        }
        else
        {
            $coursesRepositorySysMissing = TRUE;
            $display=DISP_RUN_INSTALL_NOT_COMPLETE;
        }


        if (file_exists($garbageRepositorySys))
        {
            if (!is_writable($garbageRepositorySys))
            {
                $garbageRepositorySysWriteProtected = TRUE;
                $display=DISP_RUN_INSTALL_NOT_COMPLETE;
            }
        }
        else
        {
            $garbageRepositorySysMissing = TRUE;
            $display=DISP_RUN_INSTALL_NOT_COMPLETE;
        }

        if (file_exists(claro_get_conf_repository()))
        {
            if (!is_writable(claro_get_conf_repository()))
            {
                $platformConfigRepositorySysWriteProtected = TRUE;
                $display=DISP_RUN_INSTALL_NOT_COMPLETE;
            }
        }
        else
        {
            $platformConfigRepositorySysMissing = TRUE;
            $display=DISP_RUN_INSTALL_NOT_COMPLETE;
        }

        /**
         * Initialise right profile
         */

        include_once('init_profile_right.lib.php');
        create_required_profile();

        /**
         * ADD MODULES
         */

        $preInstalledTools = array('CLDSC',
                                   'CLCAL',
                                   'CLANN',
                                   'CLDOC',
                                   'CLQWZ',
                                   'CLLNP',
                                   'CLWRK',
                                   'CLFRM',
                                   'CLGRP',
                                   'CLUSR',
                                   'CLWIKI');

        if ( file_exists( get_path('rootSys') . 'module' ) )
        {
            $moduleDirIterator = new DirectoryIterator( get_path('rootSys') . 'module' );

            foreach ( $moduleDirIterator as $moduleDir )
            {
                if ( $moduleDir->isDir() && ! $moduleDir->isDot() )
                {
                    $preInstalledTools[] = $moduleDir->getFilename();
                }
            }
        }

        foreach ( $preInstalledTools as $claroLabel )
        {
            $modulePath = get_module_path($claroLabel);

            if (file_exists($modulePath))
            {
                $moduleId = register_module( $modulePath );

                if (false !== activate_module($moduleId))
                {
                    trigger_error('module (id:' . $moduleId . ' ) not activated ',E_USER_WARNING );
                }

            }
            else
            {
                trigger_error('module path not found' ,E_USER_WARNING );
            }
        }

        // init default right profile
        init_default_right_profile();

        /***
         * Generate module conf from definition files.
         */

        $config_code_list = get_config_code_list('module');

        if ( is_array($config_code_list) )
        {
            foreach ( $config_code_list as $config_code )
            {
                // new config object
                $config = new Config($config_code);

                //generate conf
                list ($message, $configToolError) = generate_conf($config,$form_value_list);

                if ($configToolError)
                {
                    $configError = true;
                    $messageConfigErrorList[] = $message;
                }

            }
            unset($configToolError);
        }

        if ($configError)
        {
            $display = DISP_RUN_INSTALL_NOT_COMPLETE;
        }

        /**
         * Add administrator in user and admin table
         */

        include_once($newIncludePath . 'lib/user.lib.php');

        $user_data['lastname']      = $adminNameForm;
        $user_data['firstname']     = $adminSurnameForm;
        $user_data['username']      = $loginForm;
        $user_data['password']      = $passForm;
        $user_data['email']         = $adminEmailForm;
        $user_data['language']      = '';
        $user_data['isCourseCreator'] = 1;
        $user_data['officialCode']  = '';
        $user_data['officialEmail'] = '';
        $user_data['phone']         = '';

        $id_admin = user_create($user_data);

        if ($id_admin)
        {
            user_set_platform_admin(true, $id_admin);
        }
        else
        {
            echo 'error in admin account creation';
        }
    }
}
catch ( Exception $e )
{
    die( "AN EXCEPTION OCCURED : " . $e->getMessage() );
}
