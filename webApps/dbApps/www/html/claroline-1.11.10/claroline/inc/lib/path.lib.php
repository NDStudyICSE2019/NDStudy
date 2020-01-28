<?php // $Id: path.lib.php 14181 2012-06-13 08:15:00Z zefredz $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * Built url and system paths.
 *
 * @version     $Revision: 14181 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      see 'credits' file
 * @since       Claroline 1.8.3
 * @package     KERNEL
 */


/**
Http://www.domain.tld/whereisMyCampus/claroline/blah

$rootWeb    = Http://www.domain.tld/whereisMyCampus/claroline/blah
$hostWeb    = Http://www.domain.tld
$urlAppend  = /whereisMyCampus/claroline/blah
$clarolineRepositorySys = Http://www.domain.tld/whereisMyCampus/claroline

*/
/**
 * Return a common path of claroline
 *
 * @param string $pathKey key name of the path ( varname in previous version of claroline)
 * @author Christophe Gesche <moosh@claroline.net>
 * @return path
 */
function get_path($pathKey)
{
    static $pathList = array() ;

    if ( count($pathList) == 0 )
    {
        $rootPath = dirname(dirname(dirname(dirname(__FILE__))));

        // root path
        $pathList['rootSys'] =  $rootPath . '/' ;
        $pathList['includePath'] =  $rootPath . '/claroline/inc' ;
        $pathList['incRepositorySys'] =  $rootPath . '/claroline/inc' ;

        // root url
        $pathList['url'] =  get_conf('urlAppend');
        $pathList['rootWeb'] =  get_conf('rootWeb') ;

        // append path
        $pathList['imgRepositoryAppend'] =  'web/img/';
        $pathList['coursesRepositoryAppend'] =  get_conf('coursesRepositoryAppend','courses/');

        // root path + append path
        $pathList['clarolineRepositorySys'] =  $rootPath . '/claroline/' ;
        $pathList['coursesRepositorySys'] =  $rootPath . '/' . $pathList['coursesRepositoryAppend'] ;
        $pathList['rootAdminSys'] =  $rootPath . '/claroline/admin/' ;
        $pathList['imgRepositorySys'] =  $rootPath . '/' . $pathList['imgRepositoryAppend'];

        // root url + append path
        $pathList['coursesRepositoryWeb'] =  $pathList['url'] . '/' . $pathList['coursesRepositoryAppend'];
        $pathList['imgRepositoryWeb'] = $pathList['url']  . '/' . $pathList['imgRepositoryAppend'];
        $pathList['clarolineRepositoryWeb'] =  $pathList['url'] . '/claroline/';
        $pathList['rootAdminWeb'] =  $pathList['url'] . '/claroline/admin/';

        // path special case
        $pathList['garbageRepositorySys'] =  get_conf('garbageRepositorySys');
        $pathList['mysqlRepositorySys'] =  get_conf('mysqlRepositorySys');
        
        // user folder
        $pathList['userRepositorySys'] = $pathList['rootSys'].'platform/users/';
        $pathList['userRepositoryWeb'] = $pathList['url'].'/platform/users/';
    }

    if ( array_key_exists( $pathKey, $pathList ) )
    {
        return $pathList[$pathKey];
    }
    else
    {
        trigger_error('Claroline : Unknown path name "' . $pathKey . '" passed to get_path function' , E_USER_NOTICE);
        return false;
    }

}

/**
 * return prefix for urls to externalize
 */
function get_url_domain()
{
    /*
    Array
    (
    [scheme] => http
    [host] => hostname
    [user] => username     [pass] => password
    [path] => /path
    [query] => arg=value
    [fragment] => anchor
    )
    */

    $urlPart = parse_url(get_conf('rootWeb'));

    $url  = $urlPart[scheme] . '://';
    if(! empty($urlPart[user]))
    {
        $url .= $urlPart[user] ;
        if(! empty($urlPart[pass])) $url .= ':' . $urlPart[pass] ;
        $url .= '@' ;
    }
    $url .= $urlPart[host] . '/';

}

/**
 * Get platform path url : return get_path('url') if not empty, 
 *  '/' if get_path('url') is empty.
 * Use this instead of in get_path('url') in claro_redirect
 * @return string platform base url without domain, port...
 * @since Claroline 1.11.0
 */
function get_platform_base_url()
{
    $url = get_path('url');
    
    if ( empty( $url ) )
    {
        return '/';
    }
    else
    {
        return $url;
    }
}
