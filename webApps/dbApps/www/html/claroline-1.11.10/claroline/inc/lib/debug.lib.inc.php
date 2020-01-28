<?php // $Id: debug.lib.inc.php 14314 2012-11-07 09:09:19Z zefredz $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * debug functions
 * All this  function output only  if  debugClaro is on
 *
 * @version     1.9 $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     KERNEL
 * @author      Claro Team <cvs@claroline.net>
 * @author      Christophe Gesché <moosh@claroline.net>
 */

defined ( 'PRINT_DEBUG_INFO' ) || define ( 'PRINT_DEBUG_INFO', false ) ;
/**
 * Print out  content of session's variable
 *
 * @return htmlOutput if not PRINT_DEBUG_INFO
 * @author Christophe Gesché <moosh@claroline.net>
 *
 */
function echo_session_value()
{
    $infoResult = "" ;
    GLOBAL $statuts, $statut, $status, $is_admin ;
    if (! isset ( $is_admin ) || ! $is_admin)
    {
        exit ( 'not aivailable' ) ;
    }
    
    $infoResult .= '
    <hr />
    <a href="../claroline/admin/phpInfo.php">phpInfo Claroline</a>
    <PRE><strong>PHP Version</strong> : ' . phpversion () . '
    <strong>nivo d\'err</strong> : ' . error_reporting ( 2039 ) ;
    if (isset ( $statuts ))
    {
        $infoResult .= '
    <strong>statut</strong> : ';
        print_r($statuts);
    }
    if (isset($statut))
    {
        $infoResult .= '
    <strong>statut</strong> : ';
        print_r($statut);
    }
    if (isset($status))
    {
        $infoResult .= "
    <strong>status</strong> : ";
        print_r($status);
    }
    
    if ('' != trim(get_conf('dbHost')) || '' != trim(get_conf('dbLogin')))
    {
        $infoResult .= '
    <strong>mysql param</strong> :
     Serveur : ' . get_conf ( 'dbHost' ) . '
     User    : ' . get_conf ( 'dbLogin' ) ;
    }
    if (isset($_SESSION))
    {
        $infoResult .= "
    <strong>session</strong> : ";
        print_r($_SESSION);
    }
    if (isset($_POST))
    {
        $infoResult .= "
    <strong>Post</strong> : ";
        print_r($_POST);
    }
    if (isset($_GET))
    {
        $infoResult .= "
    <strong>GET</strong> : ";
        print_r($_GET);
    }
    
    $infoResult .= "
    <strong>Contantes</strong> : ";
    print_r(get_defined_constants());
    get_current_user();
    $infoResult .= "
    <strong>Fichiers inclus</strong> : ";
    print_r(get_included_files());
    $infoResult .= "
    <strong>Magic quote gpc</strong> : " . get_magic_quotes_gpc () . "
    <strong>Magig quote runtime</strong> : " . get_magic_quotes_runtime () . "
    <strong>date de dernière modification de la page</strong> : " . date ( "j-m-Y", getlastmod () ) ;
    /*
    get_cfg_var -- Retourne la valeur d'une option de PHP
    getenv -- Retourne la valeur de la variable d'environnement.
    ini_alter -- Change la valeur d'une option de configuration
    ini_get -- Lit la valeur d'une option de configuration.
    ini_get_all -- Lit toutes les valeurs de configuration
    ini_restore -- Restaure la valeur de l'option de configuration
    ini_set -- Change la valeur d'une option de configuration
    putenv -- Fixe la valeur d'une variable d'environnement.
    set_magic_quotes_runtime --  Active/désactive l'option magic_quotes_runtime.
    set_time_limit -- Fixe le temps maximum d'exécution d'un script.
    */
    $infoResult .= "
    <strong>Type d'interface utilisé entre le serveur web et PHP</strong> : " . php_sapi_name () . "
    <strong>informations OS</strong> : " . php_uname () . "
    <strong>Version courante du moteur Zend</strong> : " . zend_version () . "
    <strong>GID du propriétaire du script</strong> : " . getmygid () . "
    <strong>inode du script</strong> : " . getmyinode () . "
    <strong>numéro de processus courant</strong> : " . getmypid () . "
    <strong>UID du propriétaire du script actuel</strong> : " . getmyuid () . "
    <strong>niveau d'utilisation des ressources</strong> : " ;
    print_r ( @getrusage () ) ;
    
    $infoResult .= "
    </PRE>
    <hr />
        ";
    if (PRINT_DEBUG_INFO)
    {
        echo $infoResult ;
    }
    return $infoResult ;
}

/**
 * Return an  info from system if  function is not dislabed
 *
 * @param string $info
 * @param array $paramList
 * @return string
 */
function claro_call_function( $info, $paramList = null )
{
    static $disabled_function = null;
    if (is_null($disabled_function)) $disabled_function = ini_get('disable_functions');
    
    if(false===strpos($disabled_function,$info))
    {
        if (function_exists($info))
        if (is_null($paramList)) return call_user_func($info);
        else                     return call_user_func($info,$paramList);
    }
    
    return '';
}

/**
 * get verbose version of file right
 * @return string
 * @author Christophe Gesché <moosh@claroline.net>
 */

function debug_IO($file = '')
{
    $infoResult = '[Script :       ' . $_SERVER['PHP_SELF']        . ']'
    . '[Server :       ' . $_SERVER['SERVER_SOFTWARE'] . ']'
    . '[Php :          ' . claro_call_function('phpversion')                . ']'
    . '[sys :          ' . claro_call_function('php_uname')                 . ']'
    . '[My uid :       ' . claro_call_function('getmyuid')                  . ']'
    . '[current_user : ' . claro_call_function('get_current_user')          . ']'
    . '[my gid :       ' . claro_call_function('getmygid')                  . ']'
    . '[my inode :     ' . claro_call_function('getmyinode')                . ']'
    . '[my pid :       ' . claro_call_function('getmypid')                  . ']'
    . '[space  : '
    . ' - free -  :    ' . claro_call_function('disk_free_space' ,'..')
    . ' - total - :    ' . claro_call_function('disk_total_space' ,'..') . ']'
    ;

    if  ($file != '' )
    {
        $infoResult .= '<hr /> '
        .              '<strong>' . $file . '</strong> = '
        .              '[<strong>o</strong>:' . fileowner($file)
        .              ' <strong>g</strong>:' . filegroup($file)
        .              ' ' . display_perms(fileperms($file)) . ']'
        ;
        if ( is_dir(        $file ) ) $infoResult .=  '-Dir-' ;
        if ( is_file(       $file ) ) $infoResult .=  '-File-';
        if ( is_link(       $file ) ) $infoResult .=  '-Lnk-';
        if ( claro_call_function('is_executable', $file ) ) $infoResult .=  '-X-';
        if ( claro_call_function('is_readable',   $file ) ) $infoResult .=  '-R-';
        if ( claro_call_function('is_writeable',  $file ) ) $infoResult .=  '-W-';
    }

    $file = '.';
    $infoResult .= '<hr /> <strong>' . $file . '</strong> - '
    .              '[<strong>o</strong>:' . fileowner($file)
    .              ' <strong>g</strong>:' . filegroup($file)
    .              ' ' . display_perms(fileperms($file)) . ']'
    ;
    if ( is_dir(        $file ) ) $infoResult .=  '-Dir-';
    if ( is_file(       $file ) ) $infoResult .=  '-File-';
    if ( is_link(       $file ) ) $infoResult .=  '-Lnk-';
        if ( claro_call_function('is_executable', $file ) ) $infoResult .=  '-X-';
        if ( claro_call_function('is_readable',   $file ) ) $infoResult .=  '-R-';
        if ( claro_call_function('is_writeable',  $file ) ) $infoResult .=  '-W-';

    $file = '..';
    $infoResult .=  '<hr /> <strong>' . $file . '</strong> - '
    .    '[<strong>o</strong>:' . fileowner($file)
    .    ' <strong>g</strong>:' . filegroup($file)
    .    ' ' . display_perms(     fileperms($file) ) . ']'
    ;
    if ( is_dir(        $file ) ) $infoResult .=  '-Dir-';
    if ( is_file(       $file ) ) $infoResult .=  '-File-';
    if ( is_link(       $file ) ) $infoResult .=  '-Lnk-';
    if ( claro_call_function('is_executable', $file ) ) $infoResult .=  '-X-';
    if ( is_readable(   $file ) ) $infoResult .=  '-R-';
    if ( is_writeable(  $file ) ) $infoResult .=  '-W-';
    
    if (PRINT_DEBUG_INFO)
        echo $infoResult ;
    return $infoResult ;

}

function display_perms( $mode )
{
    /* Determine Type */
    if ($mode & 0x1000)
        $type = 'p' ; /* FIFO pipe */
    elseif ($mode & 0x2000)
        $type = 'c' ; /* Character special */
    elseif ($mode & 0x4000)
        $type = 'd' ; /* Directory */
    elseif ($mode & 0x6000)
        $type = 'b' ; /* Block special */
    elseif ($mode & 0x8000)
        $type = '-' ; /* Regular */
    elseif ($mode & 0xA000)
        $type = 'l' ; /* Symbolic Link */
    elseif ($mode & 0xC000)
        $type = 's' ; /* Socket */
    else
    $type='u'; /* UNKNOWN */
    
    /* Determine permissions */
    $owner['read'   ] = ($mode & 00400) ? 'r' : '-';
    $owner['write'  ] = ($mode & 00200) ? 'w' : '-';
    $owner['execute'] = ($mode & 00100) ? 'x' : '-';
    $group['read'   ] = ($mode & 00040) ? 'r' : '-';
    $group['write'  ] = ($mode & 00020) ? 'w' : '-';
    $group['execute'] = ($mode & 00010) ? 'x' : '-';
    $world['read'   ] = ($mode & 00004) ? 'r' : '-';
    $world['write'  ] = ($mode & 00002) ? 'w' : '-';
    $world['execute'] = ($mode & 00001) ? 'x' : '-';
    
    /* Adjust for SUID, SGID and sticky bit */
    if( $mode & 0x800 )
    $owner['execute'] = ($owner[execute]=='x') ? 's' : 'S';
    if( $mode & 0x400 )
    $group['execute'] = ($group[execute]=='x') ? 's' : 'S';
    if( $mode & 0x200 )
    $world['execute'] = ($world[execute]=='x') ? 't' : 'T';

    $strPerms = '<strong>t</strong>:' . $type
    .           '<strong>o</strong>:' . $owner['read'] . $owner['write'] . $owner['execute']
    .           '<strong>g</strong>:' . $group['read'] . $group['write'] . $group['execute']
    .           '<strong>w</strong>:' . $world['read'] . $world['write'] . $world['execute']
    ;
    return $strPerms;
}

function printVar($var, $varName="@")
{
    GLOBAL $DEBUG;
    if ($DEBUG)
    {
        echo '<blockquote>' . "\n"
        .    '<b>[' . $varName . ']</b>' . "\n"
        .    '<hr noshade="noshade" size="1" style="color:blue">' . "\n"
        .    '<pre style="color:red">' . "\n"
        .    var_export($var, 1) . "\n"
        .    '</pre>' . "\n"
        .    '<hr noshade size="1" style="color:blue">' . "\n"
        .    '</blockquote>' . "\n"
        ;
    }
    else
    {
        echo '<!-- DEBUG is OFF -->' . "\n"
        .    'DEBUG is OFF'
        ;
    }
}

function printInit($selection="*")
{
    GLOBAL
    $uidReset,    $cidReset,    $gidReset, $tidReset,
    $uidReq,    $cidReq,     $gidReq,   $tidReq, $tlabelReq,
    $_user,        $_course,
    $_groupUser,
    $_courseTool,
    $_SESSION,
    $_claro_local_run;
    
    if ($_claro_local_run)
    {
        echo "local init runned";
    }
    else
    {
        echo '<font color="red"> local init never runned during this script </font>';
    }
    echo '
<table width="100%" border="1" cellspacing="4" cellpadding="1" bordercolor="#808080" bgcolor="#C0C0C0" lang="en">
    <TR>';
    if($selection == "*" or strstr($selection,"u"))
    {
        echo '
        <TD valign="top" >
            <strong>User</strong> :
            (_uid)             : '.var_export(claro_get_current_user_id(),1).' |
            (session[_uid]) : '.var_export($_SESSION["_uid"],1).'
            <br />
            reset = '.var_export($uidReset,1).' |
            req = '.var_export($uidReq,1).'<br />
            _user : <pre>'.var_export($_user,1).'</pre>
            <br />is_platformAdmin            :'.var_export(claro_is_platform_admin(),1).'
            <br />is_allowedCreateCourse    :'.var_export(claro_is_allowed_to_create_course(),1).'
        </TD>';
    }
    if($selection == "*" or strstr($selection,"c"))
    {
        echo "
        <TD valign=\"top\" >
            <strong>Course</strong> : (_cid)".var_export(claro_get_current_course_id(),1)."
            <br />
            reset = ".var_export($cidReset,1)." | req = ".var_export($cidReq,1)."
            <br />
            _course : <pre>".var_export($_course,1)."</pre>
            <br />
            _groupProperties :
            <PRE>
                ".var_export(claro_get_current_group_properties_data(),1)."
            </PRE>
        </TD>";
    }
    echo '
    </TR>
    <TR>';
    if($selection == "*" or strstr($selection,"g"))
    {
        echo '<TD valign="top" ><strong>Group</strong> : (_gid) '
        .    var_export(claro_get_current_group_id(),1) . '<br />
        reset = ' . var_export($GLOBALS['gidReset'],1) . ' | req = ' . var_export($gidReq,1)."<br />
        _group :<pre>".var_export(claro_get_current_group_data(),1).
        "</pre></TD>";
    }
    if($selection == "*" or strstr($selection,"t"))
    {
        echo '<TD valign="top" ><strong>Tool</strong> : (_tid)'.var_export(claro_get_current_tool_id(),1).'<br />
        reset = ' . var_export($tidReset,1).' |
        req = ' .   var_export($tidReq,1).'|
        req = ' .   var_export($tlabelReq,1).'
        <br />
        _tool :' . var_export(get_init('_tool'),1).
        "</TD>";
    }
    echo "</TR>";
    if($selection == "*" or (strstr($selection,"u")&&strstr($selection,"c")))
    {
        echo '<TR><TD valign="top" colspan="2"><strong>Course-User</strong>';
        if (claro_is_user_authenticated()) echo '<br /><strong>User</strong> :'.var_export(claro_is_in_a_course(),1);
        if (claro_is_in_a_course()) echo ' in '.var_export(claro_get_current_course_id(),1).'<br />';
        if ( claro_is_user_authenticated()  && claro_get_current_course_id())
        echo '_courseUser            : <pre>'.var_export(getInit('_courseUser'),1).'</pre>';
        echo '<br />is_courseMember    : '.var_export(claro_is_course_member(),1);
        echo '<br />is_courseAdmin    : '.var_export(claro_is_course_manager(),1);
        echo '<br />is_courseAllowed    : '.var_export(claro_is_course_allowed(),1);
        echo '<br />is_courseTutor    : '.var_export(claro_is_course_tutor(),1);
        echo '</TD></TR>';
    }
    echo "";
    if($selection == "*" or (strstr($selection,"u")&&strstr($selection,"g")))
    {

        echo '<TR><TD valign="top"  colspan="2">'
        .    '<strong>Course-Group-User</strong>';
        if ( claro_is_user_authenticated() ) echo '<br /><strong>User</strong> :'.var_export(claro_is_in_a_course(),1);
        if ( claro_is_in_a_group() ) echo ' in '.var_export(claro_get_current_group_id(),1);
        if ( claro_is_in_a_group() ) echo '<br />_groupUser:' . var_export(get_init('_groupUser'),1);
        echo '<br />is_groupMember:' . var_export(claro_is_group_member(),1)
        .    '<br />is_groupTutor: ' . var_export( claro_is_group_tutor(),1)
        .    '<br />is_groupAllowed:' . var_export(claro_is_group_allowed(),1)
        .    '</TD>'
        .    '</tr>';
    }
    if($selection == "*" or (strstr($selection,"c")&&strstr($selection,"t")))
    {
        
        echo '<tr>
        <TD valign="top" colspan="2" ><strong>Course-Tool</strong><br />';
        if (claro_get_current_tool_id()) echo 'Tool :'.claro_get_current_tool_id();
        if ( claro_is_in_a_course() ) echo ' in '.claro_get_current_course_id().'<br />';

        if (claro_get_current_tool_id()) echo "_courseTool    : <pre>".var_export($_courseTool,1).'</pre><br />';
        echo 'is_toolAllowed : '.var_export(claro_is_tool_allowed(),1);
        echo "</TD>";
    }
    echo "</TR></TABLE>";
}

function printConfig()
{
    GLOBAL $clarolineVersion, $versionDb, $urlAppend, $serverAddress, $checkEmailByHAshSent             , $ShowEmailnotcheckedToStudent     , $userPasswordCrypted             , $userPasswordCrypted            , $platformLanguage     , $siteName                   , $clarolineRepositoryAppend;
    echo "<table width=\"100%\" border=\"1\" cellspacing=\"1\" cellpadding=\"1\" bordercolor=\"#808080\" bgcolor=\"#C0C0C0\" lang=\"en\"><TR>";
    echo "
    <tr><td colspan=2><strong>Mysql</strong></td></tr>
    <tr><td>dbHost</TD><TD>" . get_conf('dbHost') . "</td></tr>
    <tr><td>get_conf('dbLogin')     </TD><TD>" . get_conf('dbLogin') . "</td></tr>
    <tr><td>dbPass    </TD><TD>".str_repeat("*",strlen(get_conf('dbPass')))."</td></tr>
    <tr><td>mainDbName        </TD><TD>" . get_conf('mainDbName') . "</td></tr>
    <tr><td>clarolineVersion    </TD><TD>$clarolineVersion</td></tr>
    <tr><td>versionDb             </TD><TD>$versionDb </td></tr>
    <tr><td>rootWeb</TD><TD>" . get_path('rootWeb'). "</td></tr>
    <tr><td>urlAppend </TD><TD>$urlAppend</td></tr>
    <tr><td>serverAddress </TD><TD>$serverAddress</td></tr>
    <tr><td colspan=2><hr /></td></tr>
    <tr><td colspan=2><strong>param for new and future features</strong></td></tr>
    <tr><td>checkEmailByHashSent             </TD><TD>$checkEmailByHAshSent             </td></tr>
    <tr><td>ShowEmailnotcheckedToStudent     </TD><TD>$ShowEmailnotcheckedToStudent     </td></tr>
    <tr><td>userMailCanBeEmpty             </TD><TD>" . get_conf('userMailCanBeEmpty') . "</td></tr>
    <tr><td>userPasswordCrypted             </TD><TD>$userPasswordCrypted             </td></tr>
    <tr><td colspan=2></td></tr>
    <tr><td>platformLanguage     </TD><TD>". get_conf('platformLanguage') ."</td></tr>
    <tr><td>siteName            </TD><TD>". get_conf('siteName') ."</td></tr>
    <tr><td>rootWeb            </TD><TD>" . get_path('rootWeb'). "</td></tr>
    <tr><td>rootSys            </TD><TD>" . get_path('rootSys') . "</td></tr>
    <tr><td colspan=2></td></tr>
    <tr><td>clarolineRepository<strong>Append</strong>      </TD><TD>". get_path('clarolineRepositoryAppend') ."</td></tr>
    <tr><td>coursesRepository<strong>Append</strong>        </TD><TD>". get_path('coursesRepositoryAppend') ."</td></tr>
    <tr><td>rootAdmin<strong>Append</strong>                </TD><TD>". get_path('rootAdminAppend') ."</td></tr>
    <tr><td colspan=2></td></tr>
    <tr><td>clarolineRepository<strong>Web</strong>    </TD><TD>".get_path('clarolineRepositoryWeb')." </td></tr>
    <tr><td>clarolineRepository<strong>Sys</strong>    </TD><TD>" . get_path('clarolineRepositorySys') ."</td></tr>
    <tr><td>coursesRepository<strong>Web</strong>    </TD><TD>" . get_path('coursesRepositoryWeb') . "</td></tr>
    <tr><td>coursesRepository<strong>Sys</strong>    </TD><TD>" . get_path('coursesRepositorySys')."</td></tr>
    <tr><td>rootAdmin<strong>Sys</strong>            </TD><TD>". get_path('rootAdminSys') ."</td></tr>
    <tr><td>rootAdmin<strong>Web</strong>            </TD><TD>" . get_path('rootAdminWeb') . "                 </td></tr>
                ";
    echo "</TABLE>";
}

/**
 * Return an html list of function called until this.
 *
 * @return html stream
 */

function claro_html_debug_backtrace()
{
    $bt = debug_backtrace();
    $cbt = '<pre style="color:gray">' . "\n";
    $bt = array_reverse($bt);
    foreach ($bt as $btLevel)
    {
        if ($btLevel['function'] == __FUNCTION__) continue;
        
        $cbt .= 'L'.str_pad($btLevel['line'],5,' ',STR_PAD_LEFT) . ':'  ;
        $cbt .= '<a href="'.$btLevel['file'].'">#</a> ' ;
        $cbt .= str_pad(basename($btLevel['file']),30,' ', STR_PAD_BOTH) . '| ';
        $cbt .= '<b>' . $btLevel['function'] . '()</b>' . "\n";
    
    }
    return $cbt . '</pre>';
}

function profilePoint()
{
    static $start = null ;
    $bt = debug_backtrace () ;
    
    $line = '<tt>L' . str_pad ( $bt [ 0 ] [ 'line' ], 5, ' ', STR_PAD_LEFT ) . ':' ;
    $line .= '<abbr title="'.$bt [ 0 ] [ 'file' ].'">' . str_pad (  substr(basename ($bt [ 0 ] [ 'file' ]),0,30), 30, ' ', STR_PAD_BOTH ) . '</abbr>:' ;
    if (isset($bt [ 1 ]))
    {
        $line .= '<abbr title="' . str_pad ( $bt [ 1 ] [ 'line' ], 5, ' ', STR_PAD_LEFT ) . ':'.$bt [ 1 ] [ 'function' ].'() in '.$bt [ 1 ] [ 'file' ].'">-1</abbr>:' ;
    }
    if (isset($bt [ 2 ]))
    {
        $line .= '<abbr title="' . str_pad ( $bt [ 2 ] [ 'line' ], 5, ' ', STR_PAD_LEFT ) . ':'.$bt [ 1 ] [ 'function' ].'() in '.$bt [ 2 ] [ 'file' ].'">-2</abbr>:' ;
    }
    if (is_null ( $start ))
    { 
        $start = microtime();
        pushClaroMessage ( $line . '</tt>@' . date('H:i:s'), 'profile' ) ;
    } 
    else
    {
        pushClaroMessage ( $line . '</tt>@ (+ ' . (sprintf('%01.4f',microtime() -$start )) . 'ms)', 'profile' ) ;
    }
}

function claro_debug_assertion_handler($file, $line, $code)
{
    pushClaroMessage( claro_htmlspecialchars("Assertion failed in {$file} at lin {$line} : $code"), 'assert' );
}