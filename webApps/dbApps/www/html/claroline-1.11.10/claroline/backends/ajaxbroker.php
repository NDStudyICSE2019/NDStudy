<?php // $Id: ajaxbroker.php 14132 2012-05-03 09:59:59Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Ajax Broker script
 *
 * Usage:
 *  1. Register Ajax remote service in module functions.php
 *      Claroline::ajaxServiceBroker()->register( .... );
 *  2. Execute AJAX requests on get_path('url').'/claroline/backends/ajaxbroker.php'
 *
 * @version     $Revision: 14132 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.utils.ajax
 * @since       Claroline 1.10
 */

try
{
    require_once dirname(__FILE__) . '/../inc/claro_init_global.inc.php';

    if ( ! get_conf('ajaxRemoteServiceBrokerEnabled', false ) )
    {
        $response = new Json_Error(
            "Ajax Remote Service is not available (you can allow it in the "
                . "claroline configuration advanced settings)"
        );
    }
    else
    {
        $moduleLabel = Claro_UserInput::getInstance()->get('moduleLabel',false);

        if ( $moduleLabel )
        {
            Ajax_Remote_Module_Service::registerModuleServiceInstance( $moduleLabel );
        }

        $ajaxRequest = Ajax_Request::getRequest(Claro_UserInput::getInstance());

        $response = Claroline::ajaxServiceBroker()->handle($ajaxRequest);
    }
}
catch ( Exception $e )
{
    $response = new Json_Exception( $e );
}

header('Content-type: application/json; charset=utf-8');
echo $response->toJson();
exit;


