<?php // $Id: shibbolethProcess.inc.php 13708 2011-10-19 10:46:34Z abourguignon $
if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * Shibboleth / Switch AAI.
 * Process Shibboleth authentication.
 *
 * @version     0.4
 * @author      Daniel Streiff <daniel.streiff@fh-htwchur.ch>
 */

require_once dirname(__FILE__) . '/shibboleth.lib.php' ;

// uncomment to fake attributes
// setShibbolethAttributes();

if ( $_REQUEST['shibbolethLogin'] && isset($_SERVER[$shibbolethUniqueIdAttr]) && !$_SERVER[$shibbolethUniqueIdAttr] == '' )
{
    // Collect Shibboleth uer data (both for insert and update)
    $sqlPrepareList = array();
    $sqlPrepareList[] = 'nom = "'          . addslashes(utf8_decode($_SERVER[$shibbolethData['nom']]))    . '"';
    $sqlPrepareList[] = 'prenom = "'       . addslashes(utf8_decode($_SERVER[$shibbolethData['prenom']])) . '"';
    // Use first email only
    $shibbolethEmail = explode($shibbolethEmailSep, $_SERVER[$shibbolethData['email']]);
    if ($shibbolethEmail[0] == '') {
        $shibbolethEmail[0] = $shibbolethDefaultEmail;
    }
    $sqlPrepareList[] = 'email = "'        . addslashes($shibbolethEmail[0]) . '"';
//    $sqlPrepareList[] = 'phoneNumber = "'  . addslashes(($_SERVER[$shibbolethData['phoneNumber']]  ? $_SERVER[$shibbolethData['phoneNumber']]  : $shibbolethData['phoneNumber']  )) . '"';    //optional field
//    $sqlPrepareList[] = 'officialCode = "' . addslashes(($_SERVER[$shibbolethData['officialCode']] ? $_SERVER[$shibbolethData['officialCode']] : $shibbolethData['officialCode'] )) . '"';    //optional field

    // Check if user exists
    $sql = 'SELECT user_id, nom, prenom
            FROM `' . $tbl_user . '`
            WHERE
             `' . $shibbolethUidTbl . '` = "' . $_SERVER[$shibbolethUniqueIdAttr] . '"
             AND
             authSource = "' . $shibbolethAuthSource . '"'
         ;
    $result = claro_sql_query($sql);

    // Value of $shibbolethUidTbl (containing the shibbolethUniqueId) is unique -> should be no or just one row!
    if ( mysql_num_rows($result) > 0 )
    {
        // Existing user -> update data
        $uData = mysql_fetch_array($result);
        $_uid  = $uData['user_id'];
        if ( ($uData['nom'] <> $_SERVER[$shibbolethData['nom']]) || ($uData['prenom'] <> $_SERVER[$shibbolethData['prenom']]) )
        {
            // something in the name has changed -> recreate username
            if ( $shibbolethUidTbl <> 'username' )
            {
                $sqlPrepareList[] = 'username = "'     . addslashes(shibbolethUniqueUsername($_SERVER[$shibbolethData['nom']], $_SERVER[$shibbolethData['prenom']])) . '"';
            }
        }

        $sql = 'UPDATE `' . $tbl_user . '` '
             . 'SET ' . implode(', ', $sqlPrepareList) . ' '
             . 'WHERE user_id = ' . (int)$_uid;
    }
    else
    {
        // New user -> insert data
        $_uid = null;

        // User data for insert only
        $sqlPrepareList[] = 'authSource = "'                  . $shibbolethAuthSource . '"';
        $sqlPrepareList[] = '`' . $shibbolethUidTbl . '` = "' . $_SERVER[$shibbolethUniqueIdAttr] . '"';
        $sqlPrepareList[] = 'isCourseCreator = "'             . addslashes(($_SERVER[$shibbolethData['isCourseCreator']] ? $_SERVER[$shibbolethData['isCourseCreator']] : $shibbolethData['isCourseCreator'] )) . '"';
        if ( $shibbolethUidTbl <> 'username' )
        {
            $sqlPrepareList[] = 'username = "'                . addslashes(shibbolethUniqueUsername($_SERVER[$shibbolethData['nom']], $_SERVER[$shibbolethData['prenom']])) . '"';
        }

        $sql = 'INSERT INTO `' . $tbl_user . '` '
             . 'SET ' . implode(', ', $sqlPrepareList);
    }

    $res  = mysql_query($sql)
            or die('<center>UPDATE QUERY FAILED LINE '.__LINE__.'<center>');

    if (!$_uid) $_uid = mysql_insert_id();

    $claro_loginRequested = true;
    $uidReset             = true;
    $claro_loginSucceeded = true;

}
