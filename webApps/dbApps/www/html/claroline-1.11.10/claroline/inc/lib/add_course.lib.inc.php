<?php // $Id: add_course.lib.inc.php 14314 2012-11-07 09:09:19Z zefredz $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * add_course lib contain function to add a course
 * add is, find keys names aivailable, build the the course database
 * fill the course database, build the content directorys, build the index page
 * build the directory tree, register the course.
 *
 * @version 1.9 $Revision: 14314 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @see http://www.claroline.net/wiki/CLCRS/
 *
 * @package COURSE
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Christophe Gesche <moosh@claroline.net>
 * @author Frederic Minne <zefredz@claroline.net>
 *
 */

require_once dirname(__FILE__) . '/course_user.lib.php';


/**
 * with  the WantedCode we can define the 4 keys  to find courses datas
 *
 * @param string $wantedCode initial model
 * @param string $prefix4all       prefix added  for ALL keys
 * @param string $prefix4baseName  prefix added  for basename key (after the $prefix4all)
 * @param string $prefix4path      prefix added  for repository key (after the $prefix4all)
 * @param string $addUniquePrefix  prefix randomly generated prepend to model
 * @param boolean $useCodeInDepedentKeys   whether not ignore $wantedCode param. If FALSE use an empty model.
 * @param boolean $addUniqueSuffix suffix randomly generated append to model
 * @return array
 * - ["currentCourseCode"]          : Must be alphaNumeric and outputable in HTML System
 * - ["currentCourseId"]            : Must be unique in mainDb.course it's the primary key
 * - ["currentCourseDbName"]        : Must be unique it's the database name.
 * - ["currentCourseRepository"]    : Must be unique in /get_path('coursesRepositorySys')/
 *
 * @todo actually if suffix is not unique the next append and not replace
 * @todo add param listing keyg wich wouldbe identical
 * @todo manage an error on brake for too many try
 * @todo $keysCourseCode is always
 */
function define_course_keys ($wantedCode,
                             $prefix4all = '',
                             $prefix4baseName = '',
                             $prefix4path = '',
                             $addUniquePrefix = false,
                             $useCodeInDepedentKeys = true,
                             $addUniqueSuffix = false

                             )
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tbl_course    = $tbl_mdb_names['course'];


    $nbCharFinalSuffix = get_conf('nbCharFinalSuffix','3');

    // $keys["currentCourseCode"] is the "public code"
    
    //FIXME FIXME FIXME
    /*
    $wantedCode =  strtr($wantedCode,
    '�����������������������������������������������������������',
    'AAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');
    */

    //$wantedCode = strtoupper($wantedCode);
    $charToReplaceByUnderscore = '- ';
    $wantedCode = preg_replace('/['.$charToReplaceByUnderscore.']/', '_', $wantedCode);
    $wantedCode = preg_replace('/[^A-Za-z0-9_]/', '', $wantedCode);

    if ($wantedCode=='') $wantedCode = get_conf('prefixAntiEmpty');

    $keysCourseCode    = $wantedCode;

    if (!$useCodeInDepedentKeys) $wantedCode = '';
    // $keys['currentCourseId'] would Became $cid in normal using.

    if ($addUniquePrefix) $uniquePrefix =  substr(md5 (uniqid('')),0,10);
    else                  $uniquePrefix = '';

    if ($addUniqueSuffix) $uniqueSuffix =  substr(md5 (uniqid('')),0,10);

    else                  $uniqueSuffix = '';

    $keysAreUnique = false;

    $finalSuffix = array('CourseId'=>''
                        ,'CourseDb'=>''
                        ,'CourseDir'=>''
                        );
                        
    $tryNewFSCId = $tryNewFSCDb = $tryNewFSCDir = 0;

    while (!$keysAreUnique)
    {
         $keysCourseId     = $prefix4all
         .                   $uniquePrefix
         .                   strtoupper($wantedCode)
         .                   $uniqueSuffix
         .                   ($finalSuffix['CourseId'] > 0?
                             sprintf("_%0" . $nbCharFinalSuffix . "s", $finalSuffix['CourseId']):'')
         ;

         $keysCourseDbName = $prefix4baseName
         .                   $uniquePrefix
         .                   strtoupper($wantedCode)
         .                   $uniqueSuffix
         .                   ($finalSuffix['CourseDb'] > 0?
                             sprintf("_%0" . $nbCharFinalSuffix . "s", $finalSuffix['CourseDb']):'')
         ;

         $keysCourseRepository = $prefix4path
         .                       $uniquePrefix
         .                       strtoupper($wantedCode)
         .                       $uniqueSuffix
         .                       ($finalSuffix['CourseDir'] > 0?
                                 sprintf("_%0" . $nbCharFinalSuffix . "s", $finalSuffix['CourseDir']):'')
         ;

        $keysAreUnique = true;
        // Now we go to check if there are unique

        $sqlCheckCourseId    = "SELECT COUNT(code) AS existAllready
                                FROM `" . $tbl_course . "`
                                WHERE code = '" . $keysCourseId  ."'";

        $resCheckCourseId    = claro_sql_query ($sqlCheckCourseId);
        $isCheckCourseIdUsed = mysql_fetch_array($resCheckCourseId);

        if (isset($isCheckCourseIdUsed[0]['existAllready']) && $isCheckCourseIdUsed[0]['existAllready'] > 0)
        {
            $keysAreUnique = false;
            $tryNewFSCId++;
            $finalSuffix['CourseId']++;
        };

        if (get_conf('singleDbEnabled'))
        {
            $sqlCheckCourseDb = "SHOW TABLES LIKE '".$keysCourseDbName."%'";
        }
        else
        {
            $sqlCheckCourseDb = "SHOW DATABASES LIKE '".$keysCourseDbName."'";
        }

        $resCheckCourseDb = claro_sql_query ($sqlCheckCourseDb);

        $isCheckCourseDbUsed = mysql_num_rows($resCheckCourseDb);

        if ($isCheckCourseDbUsed > 0)
        {
            $keysAreUnique = false;
            $tryNewFSCDb++;
            $finalSuffix['CourseDb']++;
        };

        if (file_exists(get_path('coursesRepositorySys') . '/' . $keysCourseRepository))
        {
            $keysAreUnique = false;
            $tryNewFSCDir++;
            $finalSuffix['CourseDir']++;

        };

        if(!$keysAreUnique)
        {
            $finalSuffix['CourseDir'] = max($finalSuffix);
            $finalSuffix['CourseId']  = $finalSuffix['CourseDir'];
            $finalSuffix['CourseDb']  = $finalSuffix['CourseDir'];
        }

        // here  we can add a counter to exit if need too many try
        $limitQtyTry = 128;

        if (($tryNewFSCId+$tryNewFSCDb+$tryNewFSCDir > $limitQtyTry)
                or ($tryNewFSCId > $limitQtyTry / 2 )
                or ($tryNewFSCDb > $limitQtyTry / 2 )
                or ($tryNewFSCDir > $limitQtyTry / 2 )
            )
        {
            trigger_error('too many try for ' .  $wantedCode ,E_USER_WARNING);
            return false;

        }
    }

    // dbName Can't begin with a number
    if (!strstr("abcdefghijklmnopqrstuvwyzABCDEFGHIJKLMNOPQRSTUVWXYZ",$keysCourseDbName[0]))
    {
        $keysCourseDbName = get_conf('prefixAntiNumber') . $keysCourseDbName;
    }

    $keys['currentCourseCode'      ] = $keysCourseCode;      // screen code
    $keys['currentCourseId'        ] = $keysCourseId;        // sysCode
    $keys['currentCourseDbName'    ] = $keysCourseDbName;    // dbname
    $keys['currentCourseRepository'] = $keysCourseRepository;// append to course repository

    return $keys;
};


/**
 * Create directories used by course.
 *
 * @param  string $courseRepository path from $coursesRepositorySys to root of course
 * @param  string $courseId         sysId of course
 * @return boolean
 * @author Christophe Gesche <moosh@claroline.net>
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @author Frederic Minne <zefredz@claroline.net>
 */
function prepare_course_repository($courseRepository, $courseId)
{

    if( ! is_dir(get_path('coursesRepositorySys')) )
    {
        claro_mkdir(get_path('coursesRepositorySys'), CLARO_FILE_PERMISSIONS, true);
    }

    $courseDirPath = get_path('coursesRepositorySys') . $courseRepository;

    if ( ! is_writable(get_path('coursesRepositorySys')) )
    {
        return claro_failure::set_failure(
            get_lang( 'Folder %folder is not writable'
                , array( '%folder' => get_path('coursesRepositorySys') ) ) );
    }

    $folderList = array(
        $courseDirPath ,
        $courseDirPath . '/document',
        $courseDirPath . '/group'
    );

    foreach ( $folderList as $folder )
    {
        if ( ! claro_mkdir($folder, CLARO_FILE_PERMISSIONS,true) )
        {
            return claro_failure::set_failure(
                get_lang( 'Unable to create folder %folder'
                    ,array( '%folder' => $folder ) ) );
        }
    }

    // build index.php of course
    $courseIndex = $courseDirPath . '/index.php';
    
    $courseIndexContent = '<?php ' . "\n"
        . 'header (\'Location: '. get_path('clarolineRepositoryWeb')
        . 'course/index.php?cid=' . claro_htmlspecialchars($courseId) . '\') ;' . "\n"
        . '?' . '>' . "\n"
        ;
    
    if ( ! file_put_contents( $courseIndex, $courseIndexContent ) )
    {
        return claro_failure::set_failure(
            get_lang('Unable to create file %file'
                , array('%file' => 'index.php' ) ) );
    }

    $groupIndex = get_path('coursesRepositorySys')
        . $courseRepository . '/group/index.php'
        ;

    $groupIndexContent = '<?php session_start(); ?'.'>';

    if ( ! file_put_contents( $groupIndex, $groupIndexContent ) )
    {
        return claro_failure::set_failure(
            get_lang('Unable to create file %file'
                , array('%file' => 'group/index.php' ) ) );
    }

    return true;
}


/**
 * Create course database and tables
 *
 * @param  string courseDbName partial dbName form course table tu build real DbName
 * @return boolean
 * @author Christophe Gesche <moosh@claroline.net>
 * @author Frederic Minne <zefredz@claroline.net>
 */
function install_course_database( $courseDbName )
{
    if ( ! create_course_database( $courseDbName ) )
    {
        return false;
    }
    
    if ( ! create_course_tables( $courseDbName ) )
    {
        return false;
    }
    
    if ( ! fill_course_properties( $courseDbName ) )
    {
        return false;
    }
    
    return true;
}


/**
 * Install course tool modules
 *
 * @param string courseDbName partial dbName form course table tu build real DbName
 * @param string language course language
 * @param string courseDirectory
 * @return boolean
 */
function install_course_tools( $courseDbName, $language, $courseDirectory )
{
    update_course_tool_list($courseDbName);
    
    if ( ! setup_course_tools( $courseDbName, $language, $courseDirectory ) )
    {
        return false;
    }
    
    return true;
}


/**
 * Run setup scripts for course tool modules
 * @param string courseDbName partial dbName form course table tu build real DbName
 * @param string language course language
 * @param string courseDirectory
 * @author Frederic Minne <zefredz@claroline.net>
 */
function setup_course_tools( $courseDbName, $language, $courseDirectory )
{
    $installableToolList = get_course_installable_tool_list();
    
    if ( !empty( $installableToolList ) )
    {
        foreach ( $installableToolList as $tool )
        {
            if ( $tool['add_in_course'] == 'AUTOMATIC' )
            {
                if ( ! install_module_at_course_creation( $tool['claro_label']
                    , $courseDbName, $language, $courseDirectory ) )
                {
                    return claro_failure::set_failure(
                        get_lang('Unable to create database tables for %label%'
                            , array('%label%' => $tool['claro_label'] ) ) );
                }
            }
        }
    }
    
    return true;
};


/**
 * To create a record in the course table of main database.  Also handles
 * the categories links creation.
 *
 * @param string    $courseSysCode
 * @param string    $courseScreenCode
 * @param int       $sourceCourseId
 * @param string    $courseRepository
 * @param string    $courseDbName
 * @param string    $titular
 * @param string    $email
 * @param array     $categories
 * @param string    $intitule
 * @param string    $languageCourse
 * @param string    $uidCreator
 * @param bool      $visibility
 * @param string    $registration ('open', 'close' or 'validation')
 * @param string    $registrationKey
 * @return bool     success;
 * @author Christophe Gesche <moosh@claroline.net>
 */
function register_course( $courseSysCode, $courseScreenCode, $sourceCourseId,
                          $courseRepository, $courseDbName,
                          $titular, $email, $categories, $intitule, $languageCourse='',
                          $uidCreator,
                          $access, $registration, $registrationKey='', $visibility=true,
                          $extLinkName='', $extLinkUrl='',$publicationDate,
                          $expirationDate, $status, $userLimit)
{
    global $versionDb, $clarolineVersion;
    
    $tblList                    = claro_sql_get_main_tbl();
    $tbl_course                 = $tblList['course'];
    $tbl_category               = $tblList['category'];
    $tbl_rel_course_category    = $tblList['rel_course_category'];
    
    // Needed parameters
    if ($courseSysCode    == '') return claro_failure::set_failure('courseSysCode is missing');
    if ($courseScreenCode == '') return claro_failure::set_failure('courseScreenCode is missing');
    if ($courseDbName     == '') return claro_failure::set_failure('courseDbName is missing');
    if ($courseRepository == '') return claro_failure::set_failure('course Repository is missing');
    if ($uidCreator       == '') return claro_failure::set_failure('uidCreator is missing');
    if (!in_array($registration, array('open', 'close', 'validation')))
    {
        return claro_failure::set_failure('wrong registration value');
    }
    
    // Optionnal settings
    $languageCourse = (!empty($languageCourse)) ? $languageCourse : 'english';
    
    $sourceCourseId = (!is_null($sourceCourseId) && !empty($sourceCourseId)) ?
        claro_sql_escape($sourceCourseId) : "NULL";
    
    $currentVersionFilePath = get_conf('rootSys') . 'platform/currentVersion.inc.php';
    file_exists($currentVersionFilePath) && require $currentVersionFilePath;
    
    $defaultProfileId = claro_get_profile_id('user');
    
    // Insert course
    $sql = "INSERT INTO `" . $tbl_course . "` SET
            code                 = '" . claro_sql_escape($courseSysCode)        . "',
            sourceCourseId       = " . $sourceCourseId . ",
            dbName               = '" . claro_sql_escape($courseDbName)         . "',
            directory            = '" . claro_sql_escape($courseRepository)     . "',
            language             = '" . claro_sql_escape($languageCourse)       . "',
            intitule             = '" . claro_sql_escape($intitule)             . "',
            visibility           = '" .  ($visibility?'VISIBLE':'INVISIBLE')    . "',
            access               = '" .  claro_sql_escape($access)              . "',
            registration         = '" .  claro_sql_escape($registration)        . "',
            registrationKey      = '" .  claro_sql_escape($registrationKey)     . "',
            diskQuota            = NULL,
            creationDate         = FROM_UNIXTIME(" . claro_sql_escape($publicationDate) . "),
            expirationDate       = FROM_UNIXTIME(" . claro_sql_escape($expirationDate)  . "),
            status               = '" . claro_sql_escape($status)           . "',
            userLimit            = '" . (int) $userLimit                    . "',
            versionDb            = '" . claro_sql_escape($versionDb)        . "',
            versionClaro         = '" . claro_sql_escape($clarolineVersion) . "',
            lastEdit             = NOW(),
            lastVisit            = NULL,
            titulaires           = '" . claro_sql_escape($titular)          . "',
            email                = '" . claro_sql_escape($email)            . "',
            administrativeNumber = '" . claro_sql_escape($courseScreenCode) . "',
            extLinkName          = '" . claro_sql_escape($extLinkName)      . "',
            extLinkUrl           = '" . claro_sql_escape($extLinkUrl)       . "',
            defaultProfileId     = " . $defaultProfileId ;
    
    if ( claro_sql_query($sql) == false)
    {
        return false;
    }
    
    $courseId = mysql_insert_id();
    
    // Insert categories
    if ( link_course_categories ( $courseId, $categories ) === false )
    {
        return false;
    }
    
    // Did we insert a session couse ?
    if (!is_null($sourceCourseId))
    {
        // If yes, flag its source course
        $sql = "UPDATE `" . $tbl_course . "`
                SET isSourceCourse = 1
                WHERE cours_id = $sourceCourseId";
        
        if ( claro_sql_query($sql) == false )
        {
            return false;
        }
    }
    
    return true;
}


/**
 * Get the list of all installable course tool modules from kernel
 * @author Frederic Minne <zefredz@claroline.net>
 */
function get_course_installable_tool_list()
{
    $tbl_mdb_names   = claro_sql_get_main_tbl();

    $tbl_courseTool = $tbl_mdb_names['tool'  ];

    $sql = "SELECT id, def_access, def_rank, claro_label, add_in_course "
        . "FROM `". $tbl_courseTool . "` "
        ;

    $list = claro_sql_query_fetch_all_rows($sql);
    
    return $list;
}

// TODO: check if tool installed successfuly !!!!
/**
 * Register installed course tool in course database
 * @author Frederic Minne <zefredz@claroline.net>
 */
function update_course_tool_list($courseDbName)
{
    $toolList = get_course_installable_tool_list();
    
    $courseDbName = get_conf('courseTablePrefix') . $courseDbName . get_conf('dbGlu');

    $tbl_cdb_names = claro_sql_get_course_tbl($courseDbName);
    $tbl_courseToolList    = $tbl_cdb_names['tool'];

    foreach ( $toolList as $courseTool )
    {
        $sql_insert = " INSERT INTO `" . $tbl_courseToolList . "` "
            . " (tool_id, rank, visibility, activated, installed) "
            . " VALUES ('" . $courseTool['id'] . "',"
            . "'" . $courseTool['def_rank'] . "',"
            . "'" .($courseTool['def_access']=='ALL'?1:0) . "',"
            . "'" .($courseTool['add_in_course']=='AUTOMATIC'?'true':'false') . "',"
            . "'" .($courseTool['add_in_course']=='AUTOMATIC'?'true':'false') . "' )"
            ;
            
        claro_sql_query_insert_id($sql_insert);
    }
}

/**
 * Create course database :
 * @param string courseDbName partial dbName form course table tu build real DbName
 * @return boolean
 */
function create_course_database( $courseDbName )
{
    // Create course database
    if ( !get_conf( 'singleDbEnabled' ) )
    {
        claro_sql_query('CREATE DATABASE `'.$courseDbName.'`');

        if (claro_sql_errno() > 0)
        {
            return claro_failure::set_failure(
                get_lang( 'Unable to create course database' ) );
        }
    }
    
    return true;
}

/**
 * Create course tables in database :
 * @param string courseDbName partial dbName form course table tu build real DbName
 * @return boolean
 */
function create_course_tables( $courseDbName )
{
    $sqlPath = get_path('clarolineRepositorySys') . 'course/setup/course_database.sql';
    
    return execute_sql_at_course_creation( $sqlPath, $courseDbName );
}

function fill_course_properties( $courseDbName )
{
    $currentCourseDbNameGlu = get_conf('courseTablePrefix')
        . $courseDbName . get_conf('dbGlu')
        ;
        
    $sql = "INSERT "
        . "INTO `{$currentCourseDbNameGlu}course_properties`(`name`, `value`, `category`)\n"
        . "VALUES\n"
        . "('self_registration'     , '1', 'GROUP'),\n"
        . "('self_unregistration'   , '0', 'GROUP'),\n"
        . "('nbGroupPerUser'        , '1', 'GROUP'),\n"
        . "('private'               , '1', 'GROUP')"
        ;
        
    $groupToolList = get_group_tool_label_list();
    
    foreach ( $groupToolList as $thisGroupTool )
    {
        $sql .= ",\n("
            . "'".claro_sql_escape($thisGroupTool['label'])."', '1', 'GROUP'"
            . ")"
            ;
    }
    
    return claro_sql_query( $sql );
}

// TODO: use module.lib functions instead (need to update $_course in global namespace)
/**
 * Install module databases at course creation
 */
function install_module_at_course_creation( $moduleLabel, $courseDbName, $language, $courseDirectory )
{
    $sqlPath = get_module_path( $moduleLabel ) . '/setup/course_install.sql';
    $phpPath = get_module_path( $moduleLabel ) . '/setup/course_install.php';

    if ( file_exists( $sqlPath ) )
    {
        if ( ! execute_sql_at_course_creation( $sqlPath, $courseDbName ) )
        {
            return false;
        }
    }

    if ( file_exists( $phpPath ) )
    {
        // include the language file with all language variables
        language::load_translation( $language );
        language::load_locale_settings( $language );
        language::load_module_translation( $moduleLabel, $language );
        
        // define tables to use in php install scripts
        $courseDbName = get_conf('courseTablePrefix') . $courseDbName.get_conf('dbGlu');
        $moduleCourseTblList = claro_sql_get_course_tbl($courseDbName);

        /*
         * @todo select database should not be needed if the setup scripts are
         * well written !
         */
        if ( ! get_conf('singleDbEnabled') )
        {
            claro_sql_select_db($courseDbName);
        }
        
        require_once $phpPath;
    }
    
    return true;
}

/**
 * Execute SQL files at course creation
 */
function execute_sql_at_course_creation( $sqlPath, $courseDbName )
{
    if ( file_exists( $sqlPath ) )
    {
        $sql = file_get_contents( $sqlPath );
        
        $currentCourseDbNameGlu = get_conf('courseTablePrefix') . $courseDbName . get_conf('dbGlu');

        $sql = str_replace('__CL_COURSE__', $currentCourseDbNameGlu, $sql );

        if ( ! claro_sql_multi_query($sql) )
        {
            return claro_failure::set_failure( 'SQL_QUERY_FAILED' );
        }
        else
        {
            return true;
        }
    }
    else
    {
        return claro_failure::set_failure( 'SQL_FILE_NOT_FOUND' );
    }
}
