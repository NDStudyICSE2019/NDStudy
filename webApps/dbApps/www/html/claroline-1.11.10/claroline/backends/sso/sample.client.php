<?php // -$Id: sample.client.php 11660 2009-03-05 14:14:50Z zefredz $
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * This is an example in PHP and SOAP of a Single Sign On (SSO) client allowing 
 * a system to request user parameter from a cookie retrieved on the user 
 * browser. 
 *
 * WARNING ! The SOAP request will return a SSO updated cookie. It's the job of 
 * the SOAP client to update the cookie into the user browser.
 */

/******************************************************************************
                              SSO CLIENT SETTINGS
 ******************************************************************************/

// SOAP LIBRARY PATH. The script is based on the nuSoap Library 
// (http://sourceforge.net/projects/nusoap/). Adapt the path of the line below 
// to fit the location of your own nuSoap library.

$nuSoapPath = '../../inc/lib/thirdparty/nusoap/nusoap.php';

// CLAROLINE SSO SERVER URL. Complete Address of the SSO server contained in 
// the Claroline platform you want to request on. Adapt this url to fit your 
// Claroline location.

$ssoServerUrl = 'http://my.domain.com/mycampus/claroline/backends/sso/server.php';

// COOKIE NAME. The name of the cookie the Claroline platform has set into the 
// user browser. By default this name is 'clarolineSsoCookie'. But it can be 
// changed by the Claroline platform administrator.

$cookieName = 'clarolineSsoCookie';

// AUTHENTICATION KEY. This is the key allowing your SOAP client to request on 
// the Claroline SSO server. This key should be communicated to you by the 
// Claroline platform administrator.

$serverAuthenticationKey = '';

// COURSE ID. Beside the user basic parameters, you can also check the user 
// status in a specific claroline course. Put here the system code of the 
// course you want check.

$courseId = '';

// GROUP ID. You can also the satus in a specific group from a course. 
// Put here the group system id you want to chek. This part of the user 
// checking only if you request course checking in the same time 
// (groups are coming from specific course).

$groupId  = '';




/******************************************************************************
                              SSO CLIENT EXECUTION
 ******************************************************************************/


if ( isset($_COOKIE[$cookieName]) )
{

    /*------------------------------------------------------------------------
                                SOAP CLIENT INIT
      ------------------------------------------------------------------------*/

    require_once $nuSoapPath;

    $paramList = array('auth'   => $serverAuthenticationKey, 
                       'cookie' => $_COOKIE[$cookieName],  
                       'cid'    => $courseId, 
                       'gid'    => $groupId              );

    $client = new nusoap_client($ssoServerUrl);

    $result = $client->call('get_user_info_from_cookie', $paramList);

    if ( $client->getError() ) // SERVER CONNECTION FAILURE
    {
        echo '<center>'
            .'<p><b>Soap error</b></p>'
            .'<p>'
            .$result['faultcode'  ].'<br />'
            .$result['faultstring']
            .'</p>';
    }
    elseif ( is_array($result) ) // USER ALREADY CONNECTED TO HE CLAROLINE 
                                 // PLATFORM. AUTHENTICATION SUCCEEDS.
    {
        /*--------------------------------------------------------------------
                     UPDATE THE COOKIE OF THE USER BROWSER
          --------------------------------------------------------------------*/
        
         setcookie($result['ssoCookieName'      ], 
                   $result['ssoCookieValue'     ], 
                   $result['ssoCookieExpireTime'], 
                   $result['ssoCookiePath'      ], 
                   $result['ssoCookieDomain'    ]);


        /*--------------------------------------------------------------------
                                BUSSINESS LOGIC
          --------------------------------------------------------------------*/

         // FROM HERE YOU INSERT YOUR CODE HERE TO SPECIAL OPERATIONS 
         // WITH THE USER DATA ON YOUR SYSTEM
         //
         //         ...
           
        echo '<h1 align="center">SSO Result</h1>'
            .'<pre>';

        var_dump($result);

        echo '</pre>';

    }
    else
    {
        // AUTHENTICATION FAILED
        echo '<center>Authentication failed</center>';
    }
    
}
