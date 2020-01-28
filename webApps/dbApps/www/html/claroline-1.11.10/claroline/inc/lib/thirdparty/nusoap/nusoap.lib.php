<?php
    // necessary classes
    require_once(dirname(__FILE__).'/class.nusoap_base.php');
    require_once(dirname(__FILE__).'/class.soapclient.php');
    require_once(dirname(__FILE__).'/class.soap_val.php');
    require_once(dirname(__FILE__).'/class.soap_parser.php');
    require_once(dirname(__FILE__).'/class.soap_fault.php');

    // transport classes
    require_once(dirname(__FILE__).'/class.soap_transport_http.php');

    // optional add-on classes
    require_once(dirname(__FILE__).'/class.xmlschema.php');
    require_once(dirname(__FILE__).'/class.wsdl.php');

    // server class
    require_once(dirname(__FILE__).'/class.soap_server.php');
?>
