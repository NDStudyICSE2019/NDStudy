<?php // $Id: shibboleth.lib.php 13708 2011-10-19 10:46:34Z abourguignon $
if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * Shibboleth / Switch AAI.
 * Library and configuration.
 *
 * @version     0.4
 * @author      Daniel Streiff <daniel.streiff@fh-htwchur.ch>
 */

/**
 * Set Shibboleth server attributes manually
 *
 * @author Daniel Streiff <daniel.streiff@fh-htwchur.ch>
 * @return
 */

function setShibbolethAttributes()
{
    global $shibboleth_conf ;

    // set server attributes
    foreach($shibboleth_conf as $key => $value)
    {
        $_SERVER[$key] = $value;
    }

}

/**
 * Check if a username is already used
 *
 * @author Daniel Streiff <daniel.streiff@fh-htwchur.ch>
 * @param string $username
 * @return boolean
 */

function shibbolethUsernameExists($username)
{
    global $tbl_user;
    global $shibbolethAuthSource;

    // unique for all authSources
    $sql = 'SELECT count(*)
            FROM `' . $tbl_user . '`
            WHERE
             username = "' . addslashes($username) . '"'
         ;
    $result = claro_sql_query($sql);
    $row = mysql_fetch_array($result);

    if ( $row[0] == 0 )
    {
        return false;
    }
    else
    {
        return true;
    }
}

/**
 * Generate a unique username
 *
 * @author Daniel Streiff <daniel.streiff@fh-htwchur.ch>
 * @param string $lastname
 * @param string $firstname
 * @return string
 */

function shibbolethUniqueUsername($lastname, $firstname, $lnLen = 10, $fnLen = 10)
{
    $maxLen = 20;
    $lastname  = substr(utf8_decode($lastname),  0, $lnLen);
    $firstname = substr(utf8_decode($firstname), 0, $fnLen);
    $shibbolethUsername = $lastname . $firstname;
    $i = 0;
    while (shibbolethUsernameExists($shibbolethUsername))
    {
        $i++;
        $shibbolethUsername = substr($lastname . $firstname, 0, $maxLen - strlen($i)) . $i;
    }
    return $shibbolethUsername;
}
