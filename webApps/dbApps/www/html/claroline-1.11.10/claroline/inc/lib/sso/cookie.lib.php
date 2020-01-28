<?php // $Id: cookie.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * SingleSignOn cookie
 *
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     PACKAGE_NAME
 */

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

class SingleSignOnCookie
{
    public static function setForUser( $_uid )
    {
        $tbl = claro_sql_get_main_tbl();
        
        $ssoCookieExpireTime = time() + get_conf('ssoCookiePeriodValidity',3600);
        $ssoCookieValue = md5( mktime() . rand(100, 1000000) );
        
        $sql = "UPDATE `{$tbl['sso']}`\n"
            . "SET cookie    = '".$ssoCookieValue."',\n"
            . "rec_time  = NOW()\n"
            . "WHERE user_id = ". (int) $_uid
            ;
        
        $affectedRowCount = claro_sql_query_affected_rows( $sql );
        
        if ( $affectedRowCount < 1 )
        {
            $sql = "INSERT INTO `{$tbl['sso']}`\n"
                . "SET cookie = '".$ssoCookieValue."',\n"
                . "rec_time = NOW(),\n"
                . "user_id = ". (int) $_uid
                ;
        
            claro_sql_query( $sql );
        }
        
        return setcookie( 
            get_conf('ssoCookieName','clarolineSsoCookie'),
            $ssoCookieValue,
            $ssoCookieExpireTime,
            get_conf( 'ssoCookiePath','/' ),
            get_conf( 'ssoCookieDomain','sso.claroline.net' ) );
        
        // Note. $ssoCookieName, $ssoCookieValussoCookieExpireTime,
        //       $soCookiePath and $ssoCookieDomain are coming from
        //       claroline/inc/conf/auth.conf.php
    }
}
