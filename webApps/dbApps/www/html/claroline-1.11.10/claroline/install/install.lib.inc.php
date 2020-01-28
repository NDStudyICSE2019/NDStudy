<?php // $Id: install.lib.inc.php 14712 2014-02-17 08:30:15Z zefredz $

if ( count( get_included_files() ) == 1 ) die( basename(__FILE__) );

/**
 * CLAROLINE
 *
 * This lib prupose function use by installer.
 *
 * @version     $Revision: 14712 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/index.php/Install
 * @author      Claro Team <cvs@claroline.net>
 * @author      Christophe Gesché <moosh@claroline.net>
 * @author      Sebastien Piraux <seb@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @package     INSTALL
 */

/**
 * check extention and  write  if exist  in a  <LI></LI>
 *
 * @param   string $extentionName name  of  php extention to be checked
 * @param   boolean $echoWhenOk true => show ok when  extention exist
 *
 */
function warnIfExtNotLoaded($extentionName,$echoWhenOk=false)
{
    if (extension_loaded ($extentionName))
    {
        if ($echoWhenOk)
        echo '<LI>'
        .    $extentionName
        .    ' - ok '
        .    '</LI>'
        ;
    }
    else
    {
        echo '<LI>'
        .    '<font color="red">Warning !</font>'
        .    $extentionName . ' is missing.</font>'
        .    '<br />'
        .    'Configure php to use this extention'
        .    '(see <a href="http://www.php.net/' . $extentionName . '">'
        .    $extentionName
        .    ' manual</a>)'
        .    '</LI>'
        ;
    }
}

/**
 * Search read and write access from the given directory to root
 *
 * @param path string path where begin the scan
 * @return array with 2 fields "topWritablePath" and "topReadablePath"
 *
 * @var $serchtop log is only use for debug
 */
function topRightPath($path='.')
{
    $whereIam = getcwd();
    chdir($path);
    $pathToCheck = realpath('.');
    $previousPath=$pathToCheck.'*****';

    $search_top_log = 'top right Path'.'<dl>';
    while(!empty($pathToCheck))
    {
        $pathToCheck = realpath('.');
        if (is_writable($pathToCheck))
        $topWritablePath = $pathToCheck;
        if (is_readable($pathToCheck))
        $topReadablePath = $pathToCheck;
        $search_top_log .= '<dt>' . $pathToCheck . '</dt>'
                        .  '<dd>write:'
                        .  (is_writable($pathToCheck)?'open':'close')
                        .  ' read:'
                        .  (is_readable($pathToCheck)?'open':'close')
                        .  '</dd>'
                        ;
        if (   $pathToCheck != '/'
           && $pathToCheck != $previousPath
           && (  is_writable($pathToCheck)
              || is_readable($pathToCheck)
              )
           )
        {
            chdir('..') ;
            $previousPath=$pathToCheck;
        }
        else
        {
            $pathToCheck ='';
        }

    }
    $search_top_log .= '</dl>'
    .  'topWritablePath = ' . $topWritablePath . '<br />'
    .  'topReadablePath = ' . $topReadablePath
    ;

    //echo $search_top_log;
    chdir($whereIam);
    return array("topWritablePath" => $topWritablePath, "topReadablePath" => $topReadablePath);
};

function check_if_db_exist($db_name,$db=null)
{

    // I HATE THIS SOLUTION .
    // It's would be better to have a SHOW DATABASE case insensitive
    // IF SHOW DATABASE IS NOT AIVAILABLE,   sql failed an function return false.
    if (PHP_OS != 'WIN32' && PHP_OS != 'WINNT')
    {
        $sql = "SHOW DATABASES LIKE '" . $db_name . "'";
    }
    else
    {
        $sql = "SHOW DATABASES LIKE '" . strtolower($db_name) . "'";
    }

    if ($db)
    {
        $res = claro_sql_query($sql,$db);
    }
    else
    {
        $res = claro_sql_query($sql);
    }

    if( mysql_errno() == 0 )
    {
        $foundDbName = mysql_fetch_array($res, MYSQL_NUM);
    }
    else
    {
        $foundDbName = false;
    }

    return $foundDbName;
}

/**
 * check current version is equal or greater than required version
 *
 * @param string $currentVersion like  '1.1.1'
 * @param string $requiredVersion like  '1.1.1'
 * @return boolean
 *
 * @todo check if param have a good format
 */
function checkVersion($currentVersion, $requiredVersion)
{
    $currentVersion = explode('.',$currentVersion);
    $requiredVersion = explode('.',$requiredVersion);

    if ((int) $currentVersion[0] < (int) $requiredVersion[0]) return false;
    elseif ((int) $currentVersion[0] > (int) $requiredVersion[0]) return true;
    else
    {
        if ((int) $currentVersion[1] < (int) $requiredVersion[1]) return false;
        elseif ((int) $currentVersion[1] < (int) $requiredVersion[1]) return true;
        else
        {
            if ((int) $currentVersion[2] < (int) $requiredVersion[2]) return false;
        }
    }
    return true;
}


/**
 */
function check_php_setting($php_setting, $recommended)
{
    $current = get_php_setting($php_setting);
    
    if( $current == strtoupper($recommended) )
    {
        return '<span class="ok">'.$current.'</span>';
    }
    else
    {
        return '<span class="ko">'.$current.'</span>';
    }
}

/**
 * Enter description here...
 *
 * @param string $val a php ini value
 * @return boolean: ON or OFF
 * @author Joomla <http://www.joomla.org>
 */
function get_php_setting( $val )
{
    return ( ini_get( $val ) == '1' ) ? 'ON' : 'OFF';
}

/**
 * Find all install.lang.php files in lang dirs and returns langs where this file is available
 *
 * @return array
 */
function get_available_install_language()
{
    $languageList = array();
    
    $it = new DirectoryIterator('../lang/');
    
    foreach( $it as $file )
    {
        if( $file->isDir() && !$file->isDot() )
        {
    
            if( file_exists( '../lang/' . $file->getFileName() . '/install.lang.php' ) )
            {
                $languageList[] = $file->getFileName();
            }
        }
        
    }
    
    return $languageList;
}

/**
 * Display database error
 * @param   string $query sql query
 * @param   string $error error message
 * @param   int $errno error number
 */
function displayDbError( $query, $error, $errno )
{
    echo '<hr size="1" noshade>'
        . $errno, " : ", $error, '<br>'
        . '<pre style="color:red">'
        . $query
        . '</pre>'
        . '<hr size="1" noshade>';
        
    return true;
}

function get_timezone_list ()
{
    $timezone_identifiers = DateTimeZone::listIdentifiers ();

    foreach ( $timezone_identifiers as $val )
    {
        $atz   = new DateTimeZone ( $val );
        $aDate = new DateTime ( "now", $atz );
        $timeArray[ "$val" ] = $val;
    }

    asort ( $timeArray );

    return $timeArray;
    
}