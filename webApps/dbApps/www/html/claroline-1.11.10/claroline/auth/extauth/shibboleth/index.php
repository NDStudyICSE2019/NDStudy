<?php // $Id: index.php 13708 2011-10-19 10:46:34Z abourguignon $

/**
 * CLAROLINE
 *
 * Shibboleth / Switch AAI.
 * Authenticate User with Shibboleth authSource.
 *
 * @version     0.4
 * @author      Daniel Streiff <daniel.streiff@fh-htwchur.ch>
 */

// Shibboleth attributes available, process login
$_REQUEST['shibbolethLogin'] = true;

require ('../../../inc/claro_init_global.inc.php');

// The unique id has to contain something
if ( isset($_SERVER[$shibbolethUniqueIdAttr]) )
{
    if ( !$_SERVER[$shibbolethUniqueIdAttr] == '' )
    {
        // Redirect to rootWeb
        if ( isset($_REQUEST['sourceUrl']) )
        {
            $sourceUrl = base64_decode($_REQUEST['sourceUrl']);
            claro_redirect($sourceUrl);
        }
        else
        {
            claro_redirect($rootWeb);
        }
    }
    else
    {
        // Shibboleth authentication failed
        claro_die('<center>WARNING ! SHIBBOLETH AUTHENTICATION FAILED.</center>');
    }
}
else
{
    // Directory not protected
    claro_die('<center>WARNING ! PROTECT THIS FOLDER IN YOUR WEBSERVER CONFIGURATION.</center>');
}
