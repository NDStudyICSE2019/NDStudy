<?php // $Id: shibbolethUser.php 13708 2011-10-19 10:46:34Z abourguignon $

/**
 * CLAROLINE
 *
 * Shibboleth / Switch AAI.
 * Script to change user's authSource to Shibboleth.
 *
 * @version     0.4
 * @author      Daniel Streiff <daniel.streiff@fh-htwchur.ch>
 */

// Shibboleth attributes available, check if there is already an account with this uniqueId
require ('../../../inc/claro_init_global.inc.php');

// Library
require_once ('shibboleth.lib.php');

// uncomment to fake attributes
// setShibbolethAttributes();

// the unique id has to contain something
if ( isset($_SERVER[$shibbolethUniqueIdAttr]) )
{
    if ( !$_SERVER[$shibbolethUniqueIdAttr] == '' )
    {

        if ( isset($_uid) )
        {
            // check if the uniqueId is already used
            $sql = 'SELECT user_id
                    FROM `' . $tbl_user . '`
                    WHERE
                    `' . $shibbolethUidTbl . '` = "' . $_SERVER[$shibbolethUniqueIdAttr] . '"';

            $result = claro_sql_query($sql);
            if ( mysql_num_rows($result) > 0 )
            {
                // uniqueId already in use
                claro_die ("<center>WARNING ! UNABLE TO CHANGE AUTHSOURCE. YOU ALREADY HAVE A USERACCOUNT.</center>");
            }
            else
            {
                // change user's authSource
                $sqlPrepareList = array();
                $sqlPrepareList[] = 'nom = "'          . addslashes(utf8_decode($_SERVER[$shibbolethData['nom']]))    . '"';
                $sqlPrepareList[] = 'prenom = "'       . addslashes(utf8_decode($_SERVER[$shibbolethData['prenom']])) . '"';

                // Use first email only
                $shibbolethEmail = explode($shibbolethEmailSep, $_SERVER[$shibbolethData['email']]);
                if ($shibbolethEmail[0] == '') {
                    $shibbolethEmail[0] = $shibbolethDefaultEmail;
                }
                $sqlPrepareList[] = 'email = "'        . addslashes($shibbolethEmail[0]) . '"';
                $sqlPrepareList[] = 'authSource = "'                  . $shibbolethAuthSource . '"';
                $sqlPrepareList[] = '`' . $shibbolethUidTbl . '` = "' . $_SERVER[$shibbolethUniqueIdAttr] . '"';

                if ( $shibbolethUidTbl <> 'username' )
                {
                    $sqlPrepareList[] = 'username = "' . addslashes(shibbolethUniqueUsername($_SERVER[$shibbolethData['nom']], $_SERVER[$shibbolethData['prenom']])) . '"';
                }

                $sql = 'UPDATE `' . $tbl_user . '` '
                     . 'SET ' . implode(', ', $sqlPrepareList) . ' '
                     . 'WHERE user_id = ' . (int)$_uid;

                $res  = mysql_query($sql)
                        or die('<center>UPDATE QUERY FAILED LINE '.__LINE__.'<center>');

                // redirect as normal login back to "My User Account"
                session_destroy();
                claro_redirect(get_conf('claro_ShibbolethPath') . 'index.php?sourceUrl=' . base64_encode($rootWeb . "claroline/auth/profile.php"));
            }
        }
        else
        {
            // was not logged in
            claro_die("<center>WARNING ! UNABLE TO CHANGE AUTHSOURCE. <a href=\"" . $rootWeb . "\">LOGIN FIRST</a>!.</center>");
        }
    }
    else
    {
        // Shibboleth authentication failed
        claro_die ("<center>WARNING ! SHIBBOLETH AUTHENTICATION FAILED.</center>");
    }
}
else
{
    // Directory not protected
    claro_die("<center>WARNING ! PROTECT THIS FOLDER IN YOUR WEBSERVER CONFIGURATION.</center>");
}
